<?php

namespace App\Livewire\Admin;

use App\Models\Campaign;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin', ['title' => 'Campaigns'])]
class Campaigns extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    // Form state
    public string $name = '';
    public string $slug = '';
    public string $status = Campaign::STATUS_DRAFT;
    public ?string $starts_at = null;
    public ?string $ends_at = null;
    public bool $active = false;
    public string $prize_mode = Campaign::MODE_WEIGHTED;

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('campaigns', 'slug')->ignore($this->editingId)],
            'status' => ['required', Rule::in([Campaign::STATUS_DRAFT, Campaign::STATUS_ACTIVE, Campaign::STATUS_PAUSED, Campaign::STATUS_ENDED])],
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'active' => 'boolean',
            'prize_mode' => ['required', Rule::in([Campaign::MODE_STRICT, Campaign::MODE_WEIGHTED])],
        ];
    }

    public function updatedName(string $value): void
    {
        if (! $this->editingId) {
            $this->slug = Str::slug($value);
        }
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'slug', 'starts_at', 'ends_at']);
        $this->status = Campaign::STATUS_DRAFT;
        $this->active = false;
        $this->prize_mode = Campaign::MODE_WEIGHTED;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $c = Campaign::findOrFail($id);
        $this->editingId = $c->id;
        $this->name = $c->name;
        $this->slug = $c->slug;
        $this->status = $c->status;
        $this->starts_at = $c->starts_at?->format('Y-m-d\TH:i');
        $this->ends_at = $c->ends_at?->format('Y-m-d\TH:i');
        $this->active = $c->active;
        $this->prize_mode = $c->prize_mode;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate();

        // Only one campaign may be active at a time.
        if ($data['active']) {
            Campaign::where('id', '!=', $this->editingId ?? 0)->update(['active' => false]);
        }

        Campaign::updateOrCreate(['id' => $this->editingId], $data);

        $this->showModal = false;
        $this->dispatch('admin-toast', message: $this->editingId ? 'Campaign updated.' : 'Campaign created.');
    }

    public function activate(int $id): void
    {
        Campaign::query()->update(['active' => false]);
        $campaign = Campaign::findOrFail($id);
        $campaign->update(['active' => true, 'status' => Campaign::STATUS_ACTIVE]);
        $this->dispatch('admin-toast', message: "“{$campaign->name}” is now the active campaign.");
    }

    public function delete(int $id): void
    {
        Campaign::findOrFail($id)->delete();
        $this->dispatch('admin-toast', message: 'Campaign deleted.');
    }

    public function render()
    {
        return view('livewire.admin.campaigns', [
            'campaigns' => Campaign::withCount(['prizes', 'spinSessions'])->latest()->get(),
        ]);
    }
}
