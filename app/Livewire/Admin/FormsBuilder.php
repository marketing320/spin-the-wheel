<?php

namespace App\Livewire\Admin;

use App\Models\Campaign;
use App\Models\FormField;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin', ['title' => 'Form Builder'])]
class FormsBuilder extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    // Form state
    public string $label = '';
    public string $field_key = '';
    public string $field_type = 'text';
    public ?string $placeholder = null;
    public bool $is_required = false;
    public bool $is_active = true;
    public int $sort_order = 0;

    /** @var array<int, array{label: string, value: string}> */
    public array $options = [];

    public string $validation_rules_input = '';

    public function rules(): array
    {
        $campaignId = Campaign::current()?->id;

        $rules = [
            'label' => 'required|string|max:255',
            'field_key' => [
                'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('form_fields', 'field_key')
                    ->where('campaign_id', $campaignId)
                    ->ignore($this->editingId),
            ],
            'field_type' => ['required', Rule::in(FormField::TYPES)],
            'placeholder' => 'nullable|string|max:255',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'validation_rules_input' => 'nullable|string|max:500',
        ];

        if (in_array($this->field_type, FormField::OPTION_TYPES, true)) {
            $rules['options'] = 'required|array|min:1';
            $rules['options.*.label'] = 'required|string|max:255';
            $rules['options.*.value'] = 'required|string|max:255';
        }

        return $rules;
    }

    public function updatedLabel(string $value): void
    {
        if (! $this->editingId) {
            $this->field_key = Str::slug($value, '_');
        }
    }

    public function updatedFieldType(string $value): void
    {
        // Ensure the options editor always has at least one row for option types.
        if (in_array($value, FormField::OPTION_TYPES, true) && empty($this->options)) {
            $this->options = [['label' => '', 'value' => '']];
        }
    }

    public function addOption(): void
    {
        $this->options[] = ['label' => '', 'value' => ''];
    }

    public function removeOption(int $i): void
    {
        unset($this->options[$i]);
        $this->options = array_values($this->options);
    }

    public function create(): void
    {
        $this->reset(['editingId', 'label', 'field_key', 'placeholder', 'options', 'validation_rules_input']);
        $this->field_type = 'text';
        $this->is_required = false;
        $this->is_active = true;
        $this->sort_order = 0;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $f = FormField::findOrFail($id);
        $this->editingId = $f->id;
        $this->label = $f->label;
        $this->field_key = $f->field_key;
        $this->field_type = $f->field_type;
        $this->placeholder = $f->placeholder;
        $this->is_required = $f->is_required;
        $this->is_active = $f->is_active;
        $this->sort_order = $f->sort_order;
        $this->validation_rules_input = implode(',', $f->validation_rules ?? []);

        if (in_array($f->field_type, FormField::OPTION_TYPES, true)) {
            $opts = collect($f->options ?? [])->map(fn ($o) => [
                'label' => is_array($o) ? (string) ($o['label'] ?? $o['value'] ?? '') : (string) $o,
                'value' => is_array($o) ? (string) ($o['value'] ?? $o['label'] ?? '') : (string) $o,
            ])->values()->all();

            $this->options = empty($opts) ? [['label' => '', 'value' => '']] : $opts;
        } else {
            $this->options = [];
        }

        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $campaign = Campaign::current();

        if (! $campaign) {
            session()->flash('status', 'No active campaign to attach fields to.');
            $this->showModal = false;

            return;
        }

        $this->validate();

        $isOption = in_array($this->field_type, FormField::OPTION_TYPES, true);

        $validationRules = collect(explode(',', $this->validation_rules_input))
            ->map(fn ($rule) => trim($rule))
            ->filter()
            ->values()
            ->all();

        FormField::updateOrCreate(['id' => $this->editingId], [
            'campaign_id' => $campaign->id,
            'label' => $this->label,
            'field_key' => $this->field_key,
            'field_type' => $this->field_type,
            'placeholder' => $this->placeholder ?: null,
            'options' => $isOption ? array_values($this->options) : null,
            'validation_rules' => empty($validationRules) ? null : $validationRules,
            'is_required' => $this->is_required,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ]);

        $this->showModal = false;
        session()->flash('status', $this->editingId ? 'Field updated.' : 'Field added.');
    }

    public function delete(int $id): void
    {
        FormField::findOrFail($id)->delete();
        session()->flash('status', 'Field deleted.');
    }

    public function render()
    {
        $campaign = Campaign::current();

        return view('livewire.admin.forms-builder', [
            'campaign' => $campaign,
            'fields' => $campaign
                ? $campaign->formFields()->orderBy('sort_order')->orderBy('id')->get()
                : collect(),
        ]);
    }
}
