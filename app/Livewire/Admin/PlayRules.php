<?php

namespace App\Livewire\Admin;

use App\Models\Campaign;
use App\Models\PlayRule;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin', ['title' => 'Play Rules'])]
class PlayRules extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    // Form state
    public string $rule_type = PlayRule::TYPE_ONCE_PER_CAMPAIGN;
    public ?int $cooldown_hours = null;
    public ?int $max_spins_per_campaign = null;
    public ?int $max_spins_per_day = null;
    public bool $is_active = true;

    /**
     * Human-readable labels for each rule type.
     */
    public const LABELS = [
        PlayRule::TYPE_ONCE_PER_CAMPAIGN => 'Once per campaign',
        PlayRule::TYPE_ONCE_PER_DAY => 'Once per day',
        PlayRule::TYPE_EVERY_X_HOURS => 'Every X hours (cooldown)',
        PlayRule::TYPE_MAX_PER_CAMPAIGN => 'Max spins per campaign',
        PlayRule::TYPE_MAX_PER_DAY => 'Max spins per day',
    ];

    /**
     * Short descriptions shown in the modal for each rule type.
     */
    public const DESCRIPTIONS = [
        PlayRule::TYPE_ONCE_PER_CAMPAIGN => 'Each email can spin only a single time for the whole campaign.',
        PlayRule::TYPE_ONCE_PER_DAY => 'Each email can spin once per calendar day.',
        PlayRule::TYPE_EVERY_X_HOURS => 'Each email must wait a fixed number of hours between spins.',
        PlayRule::TYPE_MAX_PER_CAMPAIGN => 'Cap the total number of spins an email may take across the campaign.',
        PlayRule::TYPE_MAX_PER_DAY => 'Cap the number of spins an email may take within a single day.',
    ];

    public function rules(): array
    {
        return [
            'rule_type' => ['required', Rule::in(PlayRule::TYPES)],
            'cooldown_hours' => 'nullable|integer|min:1',
            'max_spins_per_campaign' => 'nullable|integer|min:1',
            'max_spins_per_day' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ];
    }

    public function create(): void
    {
        $this->reset(['editingId', 'cooldown_hours', 'max_spins_per_campaign', 'max_spins_per_day']);
        $this->rule_type = PlayRule::TYPE_ONCE_PER_CAMPAIGN;
        $this->is_active = true;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $rule = PlayRule::findOrFail($id);
        $this->editingId = $rule->id;
        $this->rule_type = $rule->rule_type;
        $this->cooldown_hours = $rule->cooldown_hours;
        $this->max_spins_per_campaign = $rule->max_spins_per_campaign;
        $this->max_spins_per_day = $rule->max_spins_per_day;
        $this->is_active = $rule->is_active;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $campaign = Campaign::current();

        if (! $campaign) {
            $this->dispatch('admin-toast', message: 'No active campaign to attach rules to.');
            $this->showModal = false;

            return;
        }

        $data = $this->validate();

        // Only persist the numeric field relevant to the chosen rule type.
        $data['cooldown_hours'] = $data['rule_type'] === PlayRule::TYPE_EVERY_X_HOURS ? $data['cooldown_hours'] : null;
        $data['max_spins_per_campaign'] = $data['rule_type'] === PlayRule::TYPE_MAX_PER_CAMPAIGN ? $data['max_spins_per_campaign'] : null;
        $data['max_spins_per_day'] = $data['rule_type'] === PlayRule::TYPE_MAX_PER_DAY ? $data['max_spins_per_day'] : null;

        PlayRule::updateOrCreate(
            ['id' => $this->editingId],
            $data + ['campaign_id' => $campaign->id],
        );

        $this->showModal = false;
        $this->dispatch('admin-toast', message: $this->editingId ? 'Play rule updated.' : 'Play rule created.');
    }

    public function delete(int $id): void
    {
        PlayRule::findOrFail($id)->delete();
        $this->dispatch('admin-toast', message: 'Play rule deleted.');
    }

    public function render()
    {
        $campaign = Campaign::current();

        return view('livewire.admin.play-rules', [
            'campaign' => $campaign,
            'playRules' => $campaign
                ? $campaign->playRules()->orderByDesc('id')->get()
                : collect(),
        ]);
    }
}
