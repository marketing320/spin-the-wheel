<?php

namespace App\Livewire\Admin;

use App\Models\Campaign;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin', ['title' => 'Wheel Design'])]
class WheelDesign extends Component
{
    public ?Campaign $campaign = null;

    // Wheel visual settings
    public string $label_style = 'light';
    public string $pointer_color = '#ffffff';
    public string $hub_logo = '🎡';
    public string $background_style = 'aurora';
    public int $animation_duration_ms = 6500;
    public bool $sound_enabled = false;
    public int $glow_intensity = 60;
    public int $three_intensity = 70;

    public function mount(): void
    {
        $this->campaign = Campaign::current();

        if (! $this->campaign) {
            return;
        }

        $wheel = data_get($this->campaign->settings, 'wheel', []);

        $this->label_style = data_get($wheel, 'label_style', 'light');
        $this->pointer_color = data_get($wheel, 'pointer_color', '#ffffff');
        $this->hub_logo = data_get($wheel, 'hub_logo', '🎡');
        $this->background_style = data_get($wheel, 'background_style', 'aurora');
        $this->animation_duration_ms = (int) data_get($wheel, 'animation_duration_ms', 6500);
        $this->sound_enabled = (bool) data_get($wheel, 'sound_enabled', false);
        $this->glow_intensity = (int) data_get($wheel, 'glow_intensity', 60);
        $this->three_intensity = (int) data_get($wheel, 'three_intensity', 70);
    }

    public function rules(): array
    {
        return [
            'label_style' => ['required', Rule::in(['light', 'dark'])],
            'pointer_color' => ['required', 'string', 'max:9'],
            'hub_logo' => ['required', 'string', 'max:32'],
            'background_style' => ['required', Rule::in(['aurora', 'midnight', 'stage'])],
            'animation_duration_ms' => ['required', 'integer', 'min:3000', 'max:15000'],
            'sound_enabled' => ['boolean'],
            'glow_intensity' => ['required', 'integer', 'min:0', 'max:100'],
            'three_intensity' => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function save(): void
    {
        if (! $this->campaign) {
            return;
        }

        $data = $this->validate();

        $settings = $this->campaign->settings ?? [];
        $settings['wheel'] = [
            'label_style' => $data['label_style'],
            'pointer_color' => $data['pointer_color'],
            'hub_logo' => $data['hub_logo'],
            'background_style' => $data['background_style'],
            'animation_duration_ms' => $data['animation_duration_ms'],
            'sound_enabled' => $data['sound_enabled'],
            'glow_intensity' => $data['glow_intensity'],
            'three_intensity' => $data['three_intensity'],
        ];

        $this->campaign->update(['settings' => $settings]);

        session()->flash('status', 'Wheel design saved.');
    }

    public function render()
    {
        return view('livewire.admin.wheel-design', [
            'campaign' => $this->campaign,
        ]);
    }
}
