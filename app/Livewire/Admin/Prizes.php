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

    /** Columns the admin can click to sort the table by. */
    public const SORTABLE_FIELDS = ['name', 'rarity', 'type', 'odds', 'stock', 'is_active'];

    public string $sortField = 'sort_order';
    public string $sortDirection = 'asc';

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

        // Blank number inputs arrive over the wire as empty strings, not
        // null. Left as '', `nullable|integer`/`nullable|numeric` don't
        // reliably treat it as absent, and it reaches the DB as a literal
        // '' — which MySQL's strict mode rejects for these integer/decimal
        // columns. Normalize before validating so a blank field actually
        // means "use the default" instead of a 500.
        foreach (['voucher_expiry_hours', 'win_percentage', 'weight', 'inventory_quantity'] as $field) {
            if ($this->{$field} === '') {
                $this->{$field} = null;
            }
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

    /**
     * Clone a prize onto a new row (new id). Starts inactive so its odds/
     * weight don't instantly join the live pool alongside the original —
     * the admin reviews and activates it manually once it's been adjusted.
     */
    public function duplicate(int $id): void
    {
        $original = Prize::findOrFail($id);

        $clone = $original->replicate();
        $clone->is_active = false;
        $clone->save();

        $this->dispatch('admin-toast', message: 'Prize duplicated — review and activate it when ready.');
    }

    /**
     * Toggle sorting for a table column. Clicking the currently-sorted
     * column flips direction; clicking a different one switches to it
     * ascending. Unknown fields are ignored (defensive — component
     * properties are client-settable, so this keeps `sortBy()` itself, not
     * just the UI, the source of truth for which fields are sortable).
     */
    public function sortBy(string $field): void
    {
        if (! in_array($field, self::SORTABLE_FIELDS, true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function render(PrizeSelectionService $prizeService)
    {
        $campaign = Campaign::current();

        $prizes = collect();

        if ($campaign) {
            $query = $campaign->prizes();
            $direction = $this->sortDirection === 'desc' ? 'desc' : 'asc';

            match ($this->sortField) {
                'name' => $query->orderBy('name', $direction),
                'type' => $query->orderBy('type', $direction),
                'is_active' => $query->orderBy('is_active', $direction),
                'rarity' => $query->orderByRaw(
                    'CASE rarity '
                    .collect(Prize::RARITIES)->map(fn (string $r, int $i) => "WHEN '{$r}' THEN {$i}")->implode(' ')
                    ." ELSE 99 END {$direction}"
                ),
                // "Odds" shows win_percentage in strict mode, weight in weighted mode —
                // sort by whichever column is actually on screen.
                'odds' => $query->orderBy(
                    $campaign->prize_mode === Campaign::MODE_STRICT ? 'win_percentage' : 'weight',
                    $direction,
                ),
                // Untracked stock displays as "∞" — treat it as larger than any real
                // quantity so it consistently sorts to the "most stock" end.
                'stock' => $query->orderByRaw(
                    "(CASE WHEN inventory_enabled = 1 AND inventory_quantity IS NOT NULL THEN inventory_quantity ELSE 999999999 END) {$direction}"
                ),
                default => $query->orderBy('sort_order'),
            };

            $prizes = $query->orderBy('id')->get();
        }

        $config = $campaign ? $prizeService->validateConfiguration($campaign) : null;

        return view('livewire.admin.prizes', [
            'campaign' => $campaign,
            'prizes' => $prizes,
            'config' => $config,
        ]);
    }
}
