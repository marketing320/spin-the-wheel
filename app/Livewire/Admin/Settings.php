<?php

namespace App\Livewire\Admin;

use App\Support\Settings as SettingsStore;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.admin', ['title' => 'Settings'])]
class Settings extends Component
{
    use WithFileUploads;
    public int $otp_expiry_minutes = 10;

    public int $otp_resend_cooldown_seconds = 60;

    public int $otp_max_attempts = 5;

    public int $spin_lock_timeout_seconds = 30;

    public int $spin_default_duration_ms = 8000;

    public string $app_name = '';

    public ?string $tagline = null;

    public ?string $terms = null;

    // Image confetti (layered on top of the normal confetti).
    public bool $celebration_image_enabled = false;

    /** New uploaded image (TemporaryUploadedFile) or null. */
    public $celebration_image = null;

    /** Path of the image already stored. */
    public ?string $celebration_image_path = null;

    public int $celebration_image_count = 30;

    public int $celebration_image_size = 44;

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

        $this->celebration_image_enabled = (bool) SettingsStore::get('celebration.image_enabled');
        $this->celebration_image_path = SettingsStore::get('celebration.image_path');
        $this->celebration_image_count = (int) SettingsStore::get('celebration.image_count');
        $this->celebration_image_size = (int) SettingsStore::get('celebration.image_size');
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
            'celebration_image_enabled' => 'boolean',
            'celebration_image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:2048',
            'celebration_image_count' => 'required|integer|min:5|max:120',
            'celebration_image_size' => 'required|integer|min:16|max:160',
        ];
    }

    public function save(): void
    {
        $this->validate();

        if ($this->celebration_image) {
            $this->celebration_image_path = $this->celebration_image->store('celebration', 'public');
        }

        SettingsStore::setMany([
            'otp.expiry_minutes' => (int) $this->otp_expiry_minutes,
            'otp.resend_cooldown_seconds' => (int) $this->otp_resend_cooldown_seconds,
            'otp.max_attempts' => (int) $this->otp_max_attempts,
            'spin.lock_timeout_seconds' => (int) $this->spin_lock_timeout_seconds,
            'spin.default_duration_ms' => (int) $this->spin_default_duration_ms,
            'branding.app_name' => $this->app_name,
            'branding.tagline' => $this->tagline,
            'branding.terms' => $this->terms,
            'celebration.image_enabled' => (bool) $this->celebration_image_enabled,
            'celebration.image_path' => $this->celebration_image_path,
            'celebration.image_count' => (int) $this->celebration_image_count,
            'celebration.image_size' => (int) $this->celebration_image_size,
        ]);

        $this->reset('celebration_image');
        $this->dispatch('admin-toast', message: 'Settings saved.');
    }

    public function render()
    {
        return view('livewire.admin.settings');
    }
}
