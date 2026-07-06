<?php

namespace App\Livewire\Admin;

use App\Models\Campaign;
use App\Models\Prize;
use App\Services\PrizeSelectionService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.admin', ['title' => 'Prizes'])]
class Prizes extends Component
{
    use WithFileUploads;

    public bool $showModal = false;

    public ?int $editingId = null;

    // Path of the image already stored for the prize being edited.
    public ?string $existingImagePath = null;

    // Form state
    public string $name = '';
    public ?string $description = null;
    public string $rarity = 'common';
    public string $type = 'physical';
    public $voucher_expiry_hours = null;
    public ?string $color = '#6366f1';
    public $win_percentage = null;
    public $weight = null;
    public bool $inventory_enabled = false;
    public $inventory_quantity = null;
    public string $confetti_level = 'light';
    public ?string $redemption_message = null;
    public bool $is_active = true;
    public int $sort_order = 0;

    // Uploaded image (TemporaryUploadedFile) or null.
    public $image = null;

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'rarity' => ['required', Rule::in(Prize::RARITIES)],
            'type' => ['required', Rule::in(Prize::TYPES)],
            'voucher_expiry_hours' => 'nullable|integer|min:1|max:8760',
            'color' => 'nullable|string|max:32',
            'win_percentage' => 'nullable|numeric|min:0|max:100',
            'weight' => 'nullable|integer|min:0',
            'inventory_enabled' => 'boolean',
            'inventory_quantity' => 'nullable|integer|min:0',
            'confetti_level' => ['required', Rule::in(Prize::CONFETTI_LEVELS)],
            'redemption_message' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];
    }

    public function create(): void
    {
        $this->reset([
            'editingId', 'name', 'description', 'win_percentage', 'weight',
            'inventory_quantity', 'redemption_message', 'image', 'existingImagePath',
            'voucher_expiry_hours',
        ]);
        $this->rarity = 'common';
        $this->type = 'physical';
        $this->color = '#6366f1';
        $this->confetti_level = 'light';
        $this->inventory_enabled = false;
        $this->is_active = true;
        $this->sort_order = 0;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $p = Prize::findOrFail($id);
        $this->editingId = $p->id;
        $this->name = $p->name;
        $this->description = $p->description;
        $this->rarity = $p->rarity;
        $this->type = $p->type;
        $this->voucher_expiry_hours = $p->voucher_expiry_hours;
        $this->color = $p->color ?? '#6366f1';
        $this->win_percentage = $p->win_percentage;
        $this->weight = $p->weight;
        $this->inventory_enabled = $p->inventory_enabled;
        $this->inventory_quantity = $p->inventory_quantity;
        $this->confetti_level = $p->confetti_level;
        $this->redemption_message = $p->redemption_message;
        $this->is_active = $p->is_active;
        $this->sort_order = $p->sort_order;
        $this->existingImagePath = $p->image_path;
        $this->image = null;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $campaign = Campaign::current();
        if (! $campaign) {
            $this->showModal = false;

            return;
        }

        $data = $this->validate();

        // Image is stored separately; do not pass the upload object to the model.
        unset($data['image']);

        if ($this->image) {
            $data['image_path'] = $this->image->store('prizes', 'public');
        }

        $data['campaign_id'] = $campaign->id;

        Prize::updateOrCreate(['id' => $this->editingId], $data);

        $this->reset('image');
        $this->showModal = false;
        $this->dispatch('admin-toast', message: $this->editingId ? 'Prize updated.' : 'Prize created.');
    }

    public function delete(int $id): void
    {
        Prize::findOrFail($id)->delete();
        $this->dispatch('admin-toast', message: 'Prize deleted.');
    }

    public function render(PrizeSelectionService $prizeService)
    {
        $campaign = Campaign::current();

        $prizes = $campaign
            ? $campaign->prizes()->orderBy('sort_order')->orderBy('id')->get()
            : collect();

        $config = $campaign ? $prizeService->validateConfiguration($campaign) : null;

        return view('livewire.admin.prizes', [
            'campaign' => $campaign,
            'prizes' => $prizes,
            'config' => $config,
        ]);
    }
}
