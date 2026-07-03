<?php

namespace App\Livewire\Admin;

use App\Models\Campaign;
use App\Models\GeofenceSetting;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin', ['title' => 'Geofence'])]
class Geofence extends Component
{
    public ?Campaign $campaign = null;

    // Form state
    public bool $enabled = false;
    public ?string $location_name = null;
    public ?float $latitude = null;
    public ?float $longitude = null;
    public int $radius_meters = 100;
    public ?string $blocked_message = 'You must be at the event location to spin the wheel.';

    public function mount(): void
    {
        $this->campaign = Campaign::current();

        if (! $this->campaign) {
            return;
        }

        $setting = $this->campaign->geofenceSetting;

        if ($setting) {
            $this->enabled = $setting->enabled;
            $this->location_name = $setting->location_name;
            $this->latitude = $setting->latitude;
            $this->longitude = $setting->longitude;
            $this->radius_meters = $setting->radius_meters ?? 100;
            $this->blocked_message = $setting->blocked_message ?? $this->blocked_message;
        }
    }

    public function rules(): array
    {
        return [
            'enabled' => 'boolean',
            'location_name' => 'nullable|string|max:255',
            'latitude' => ['nullable', 'required_if:enabled,true', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_if:enabled,true', 'numeric', 'between:-180,180'],
            'radius_meters' => 'required|integer|min:1',
            'blocked_message' => 'nullable|string|max:500',
        ];
    }

    public function save(): void
    {
        if (! $this->campaign) {
            return;
        }

        $data = $this->validate();

        GeofenceSetting::updateOrCreate(
            ['campaign_id' => $this->campaign->id],
            $data,
        );

        session()->flash('status', 'Geofence settings saved.');
    }

    public function render()
    {
        return view('livewire.admin.geofence');
    }
}
