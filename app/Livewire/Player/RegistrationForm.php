<?php

namespace App\Livewire\Player;

use App\Models\Campaign;
use App\Models\FormField;
use App\Models\PlayerFormResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class RegistrationForm extends Component
{
    /** @var array<string, mixed> */
    public array $responses = [];

    public ?Campaign $campaign = null;

    /** @var \Illuminate\Support\Collection<int, FormField> */
    public $fields;

    public function mount(): void
    {
        $this->campaign = Campaign::current();
        $this->fields = $this->campaign
            ? $this->campaign->formFields()->where('is_active', true)->orderBy('sort_order')->get()
            : collect();

        $player = Auth::guard('player')->user();

        // Pre-fill from any previous submission for this campaign.
        $existing = $this->campaign
            ? PlayerFormResponse::where('player_id', $player->id)
                ->where('campaign_id', $this->campaign->id)
                ->first()
            : null;

        foreach ($this->fields as $field) {
            $default = $field->field_type === 'checkbox' ? [] : ($field->field_type === 'consent' ? false : '');
            $this->responses[$field->field_key] = data_get($existing?->responses, $field->field_key, $default);
        }
    }

    public function submit()
    {
        if (! $this->campaign) {
            return $this->redirectRoute('home', navigate: true);
        }

        [$rules, $attributes] = $this->buildValidation();
        $validated = $this->validate($rules, [], $attributes)['responses'] ?? [];

        $player = Auth::guard('player')->user();

        PlayerFormResponse::updateOrCreate(
            ['player_id' => $player->id, 'campaign_id' => $this->campaign->id],
            ['responses' => $this->responses]
        );

        $player->forceFill([
            'form_completed_at' => now(),
            'display_name' => $this->resolveDisplayName() ?: $player->display_name,
        ])->save();

        return $this->redirectRoute('spin', navigate: true);
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    protected function buildValidation(): array
    {
        $rules = [];
        $attributes = [];

        foreach ($this->fields as $field) {
            $key = "responses.{$field->field_key}";
            $attributes[$key] = $field->label;
            $set = [];

            if ($field->is_required) {
                $set[] = match ($field->field_type) {
                    'consent' => 'accepted',
                    'checkbox' => 'required',
                    default => 'required',
                };
            } else {
                $set[] = 'nullable';
            }

            switch ($field->field_type) {
                case 'email':
                    $set[] = 'email:rfc';
                    break;
                case 'number':
                    $set[] = 'numeric';
                    break;
                case 'date':
                    $set[] = 'date';
                    break;
                case 'phone':
                    $set[] = 'string';
                    $set[] = 'max:30';
                    $set[] = 'regex:/^[0-9 +().\-]{6,30}$/';
                    break;
                case 'text':
                    $set[] = 'string';
                    $set[] = 'max:1000';
                    break;
                case 'select':
                case 'radio':
                    $set[] = Rule::in($this->optionValues($field));
                    break;
                case 'checkbox':
                    $set[] = 'array';
                    $rules["{$key}.*"] = [Rule::in($this->optionValues($field))];
                    break;
                case 'consent':
                    $set[] = 'boolean';
                    break;
            }

            // Merge any admin-authored extra rules (array of rule strings).
            foreach ((array) ($field->validation_rules ?? []) as $extra) {
                if (is_string($extra) && $extra !== '') {
                    $set[] = $extra;
                }
            }

            $rules[$key] = $set;
        }

        return [$rules, $attributes];
    }

    /**
     * @return array<int, string>
     */
    protected function optionValues(FormField $field): array
    {
        return collect($field->options ?? [])
            ->map(fn ($o) => is_array($o) ? ($o['value'] ?? $o['label'] ?? null) : $o)
            ->filter()
            ->map(fn ($v) => (string) $v)
            ->values()
            ->all();
    }

    protected function resolveDisplayName(): ?string
    {
        foreach (['full_name', 'name', 'first_name'] as $key) {
            if (! empty($this->responses[$key]) && is_string($this->responses[$key])) {
                return trim($this->responses[$key]);
            }
        }

        return null;
    }

    public function render()
    {
        return view('livewire.player.registration-form');
    }
}
