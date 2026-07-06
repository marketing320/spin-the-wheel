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

    public bool $cta_enabled = false;
    public string $cta_message = '';
    public string $cta_color = 'sun';

    /** Flat, no-gradient accent palette the CTA banner's icon/accent bar can use. */
    public const CTA_COLORS = ['sun', 'grass', 'grape', 'tangerine', 'aqua', 'bubble'];

    public function mount(): void
    {
        $this->show_player_name = (bool) Settings::get('live_view.show_player_name');
        $this->show_masked_email = (bool) Settings::get('live_view.show_masked_email');
        $this->idle_message = (string) Settings::get('live_view.idle_message');
        $this->branding = Settings::get('live_view.branding');
        $this->auto_reset_seconds = (int) Settings::get('live_view.auto_reset_seconds');

        $this->cta_enabled = (bool) Settings::get('live_view.cta_enabled');
        $this->cta_message = (string) Settings::get('live_view.cta_message', '');
        $this->cta_color = (string) Settings::get('live_view.cta_color', 'sun');
    }

    public function rules(): array
    {
        return [
            'show_player_name' => 'boolean',
            'show_masked_email' => 'boolean',
            'idle_message' => 'required|string|max:255',
            'branding' => 'nullable|string|max:255',
            'auto_reset_seconds' => 'required|integer|min:3|max:120',
            'cta_enabled' => 'boolean',
            'cta_message' => 'nullable|string|max:255',
            'cta_color' => 'required|in:'.implode(',', self::CTA_COLORS),
        ];
    }

    public function save(): void
    {
        $this->validate();

        if ($this->cta_enabled && trim($this->cta_message) === '') {
            $this->addError('cta_message', 'Add a message, or turn off the CTA banner.');

            return;
        }

        Settings::setMany([
            'live_view.show_player_name' => $this->show_player_name,
            'live_view.show_masked_email' => $this->show_masked_email,
            'live_view.idle_message' => $this->idle_message,
            'live_view.branding' => $this->branding,
            'live_view.auto_reset_seconds' => (int) $this->auto_reset_seconds,
            'live_view.cta_enabled' => $this->cta_enabled,
            'live_view.cta_message' => trim($this->cta_message),
            'live_view.cta_color' => $this->cta_color,
        ]);

        $this->dispatch('admin-toast', message: 'Live view settings saved.');
    }

    public function render()
    {
        return view('livewire.admin.live-view-settings');
    }
}
