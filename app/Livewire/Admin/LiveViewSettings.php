<?php

namespace App\Livewire\Admin;

use App\Support\Settings;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin', ['title' => 'Live View'])]
class LiveViewSettings extends Component
{
    public bool $show_player_name = true;
    public bool $show_masked_email = true;
    public string $idle_message = '';
    public ?string $branding = null;
    public int $auto_reset_seconds = 12;

    public function mount(): void
    {
        $this->show_player_name = (bool) Settings::get('live_view.show_player_name');
        $this->show_masked_email = (bool) Settings::get('live_view.show_masked_email');
        $this->idle_message = (string) Settings::get('live_view.idle_message');
        $this->branding = Settings::get('live_view.branding');
        $this->auto_reset_seconds = (int) Settings::get('live_view.auto_reset_seconds');
    }

    public function rules(): array
    {
        return [
            'show_player_name' => 'boolean',
            'show_masked_email' => 'boolean',
            'idle_message' => 'required|string|max:255',
            'branding' => 'nullable|string|max:255',
            'auto_reset_seconds' => 'required|integer|min:3|max:120',
        ];
    }

    public function save(): void
    {
        $this->validate();

        Settings::setMany([
            'live_view.show_player_name' => $this->show_player_name,
            'live_view.show_masked_email' => $this->show_masked_email,
            'live_view.idle_message' => $this->idle_message,
            'live_view.branding' => $this->branding,
            'live_view.auto_reset_seconds' => (int) $this->auto_reset_seconds,
        ]);

        session()->flash('status', 'Live view settings saved.');
    }

    public function render()
    {
        return view('livewire.admin.live-view-settings');
    }
}
