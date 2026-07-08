<?php

namespace App\Livewire\Admin;

use App\Support\Settings;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.admin', ['title' => 'Front View Banner'])]
class FrontViewBanner extends Component
{
    use WithFileUploads;

    public const MAX_IMAGES = 10;

    public bool $enabled = false;
    public int $interval_seconds = 6;

    /** @var array<int, string> stored image paths, in display order */
    public array $images = [];

    /** Newly selected upload (TemporaryUploadedFile) or null. */
    public $newImage = null;

    public function mount(): void
    {
        $this->enabled = (bool) Settings::get('front_view.enabled');
        $this->interval_seconds = (int) Settings::get('front_view.interval_seconds', 6);
        $this->images = array_values((array) Settings::get('front_view.images', []));
    }

    public function rules(): array
    {
        return [
            'enabled' => 'boolean',
            'interval_seconds' => 'required|integer|min:2|max:60',
        ];
    }

    public function save(): void
    {
        $this->validate();

        if ($this->enabled && empty($this->images)) {
            $this->addError('enabled', 'Upload at least one image before enabling the banner.');

            return;
        }

        Settings::setMany([
            'front_view.enabled' => $this->enabled,
            'front_view.interval_seconds' => (int) $this->interval_seconds,
        ]);

        $this->dispatch('admin-toast', message: 'Front view settings saved.');
    }

    public function addImage(): void
    {
        $this->validate(['newImage' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096']);

        if (count($this->images) >= self::MAX_IMAGES) {
            $this->addError('newImage', 'You can upload up to '.self::MAX_IMAGES.' images — remove one first.');

            return;
        }

        $this->images[] = $this->newImage->store('front-view', 'public');
        $this->reset('newImage');
        $this->persistImages();
        $this->dispatch('admin-toast', message: 'Image added.');
    }

    public function removeImage(int $index): void
    {
        if (! isset($this->images[$index])) {
            return;
        }

        Storage::disk('public')->delete($this->images[$index]);
        unset($this->images[$index]);
        $this->images = array_values($this->images);
        $this->persistImages();
        $this->dispatch('admin-toast', message: 'Image removed.');
    }

    public function moveUp(int $index): void
    {
        $this->swap($index, $index - 1);
    }

    public function moveDown(int $index): void
    {
        $this->swap($index, $index + 1);
    }

    protected function swap(int $a, int $b): void
    {
        if (! isset($this->images[$a], $this->images[$b])) {
            return;
        }

        [$this->images[$a], $this->images[$b]] = [$this->images[$b], $this->images[$a]];
        $this->persistImages();
    }

    protected function persistImages(): void
    {
        Settings::set('front_view.images', array_values($this->images));
    }

    public function render()
    {
        return view('livewire.admin.front-view-banner');
    }
}
