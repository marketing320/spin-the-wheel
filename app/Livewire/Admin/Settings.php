<?php

namespace App\Livewire\Admin;

use App\Support\Settings as SettingsStore;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin', ['title' => 'Settings'])]
class Settings extends Component
{
    public int $otp_expiry_minutes = 10;
    public int $otp_resend_cooldown_seconds = 60;
    public int $otp_max_attempts = 5;

    public int $spin_lock_timeout_seconds = 30;
    public int $spin_default_duration_ms = 6000;

    public string $app_name = '';
    public ?string $tagline = null;
    public ?string $terms = null;

    public function mount(): void
    {
        $this->otp_expiry_minutes = (int) SettingsStore::get('otp.expiry_minutes');
        $this->otp_resend_cooldown_seconds = (int) SettingsStore::get('otp.resend_cooldown_seconds');
        $this->otp_max_attempts = (int) SettingsStore::get('otp.max_attempts');

        $this->spin_lock_timeout_seconds = (int) SettingsStore::get('spin.lock_timeout_seconds');
        $this->spin_default_duration_ms = (int) SettingsStore::get('spin.default_duration_ms');

        $this->app_name = (string) SettingsStore::get('branding.app_name');
        $this->tagline = SettingsStore::get('branding.tagline');
        $this->terms = SettingsStore::get('branding.terms');
    }

    public function rules(): array
    {
        return [
            'otp_expiry_minutes' => 'required|integer|min:1|max:120',
            'otp_resend_cooldown_seconds' => 'required|integer|min:10|max:3600',
            'otp_max_attempts' => 'required|integer|min:1|max:20',
            'spin_lock_timeout_seconds' => 'required|integer|min:10|max:600',
            'spin_default_duration_ms' => 'required|integer|min:2000|max:20000',
            'app_name' => 'required|string|max:100',
            'tagline' => 'nullable|string|max:255',
            'terms' => 'nullable|string',
        ];
    }

    public function save(): void
    {
        $this->validate();

        SettingsStore::setMany([
            'otp.expiry_minutes' => (int) $this->otp_expiry_minutes,
            'otp.resend_cooldown_seconds' => (int) $this->otp_resend_cooldown_seconds,
            'otp.max_attempts' => (int) $this->otp_max_attempts,
            'spin.lock_timeout_seconds' => (int) $this->spin_lock_timeout_seconds,
            'spin.default_duration_ms' => (int) $this->spin_default_duration_ms,
            'branding.app_name' => $this->app_name,
            'branding.tagline' => $this->tagline,
            'branding.terms' => $this->terms,
        ]);

        session()->flash('status', 'Settings saved.');
    }

    public function render()
    {
        return view('livewire.admin.settings');
    }
}
