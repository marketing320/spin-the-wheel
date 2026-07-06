<?php

namespace App\Support;

use App\Models\AppSetting;

/**
 * Runtime, admin-editable settings persisted in the `app_settings` table with
 * sensible fallbacks to config/spin.php. Values are memoized per request to
 * avoid repeated queries; call flush() after writing.
 */
class Settings
{
    /** @var array<string, mixed>|null */
    protected static ?array $cache = null;

    /**
     * Default values used when a key has not been overridden in the database.
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'otp.expiry_minutes' => config('spin.otp.expiry_minutes'),
            'otp.resend_cooldown_seconds' => config('spin.otp.resend_cooldown_seconds'),
            'otp.max_attempts' => config('spin.otp.max_attempts'),
            'otp.length' => config('spin.otp.length'),

            'spin.lock_timeout_seconds' => config('spin.spin.lock_timeout_seconds'),
            'spin.default_duration_ms' => config('spin.spin.default_duration_ms'),

            'live_view.show_player_name' => true,
            'live_view.show_masked_email' => true,
            'live_view.idle_message' => 'Waiting for the next lucky player…',
            'live_view.branding' => config('app.name'),
            'live_view.auto_reset_seconds' => 12,

            'branding.app_name' => config('app.name'),
            'branding.tagline' => 'Spin to win amazing prizes!',
            'branding.terms' => '',

            // Image confetti (confettea) layered on top of the normal confetti.
            'celebration.image_enabled' => false,
            'celebration.image_path' => null,
            'celebration.image_count' => 30,
            'celebration.image_size' => 44,

            // Global voucher redemption window; overridable per-prize.
            'redemption.voucher_expiry_hours' => 24,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function load(): array
    {
        if (static::$cache === null) {
            $stored = [];

            try {
                $stored = AppSetting::query()->pluck('value', 'key')->all();
            } catch (\Throwable $e) {
                // Table may not exist yet (e.g. before migrations). Fall back.
                $stored = [];
            }

            static::$cache = array_merge(static::defaults(), $stored);
        }

        return static::$cache;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = static::load();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public static function set(string $key, mixed $value): void
    {
        AppSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        static::$cache = null;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            AppSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        static::$cache = null;
    }

    public static function flush(): void
    {
        static::$cache = null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return static::load();
    }
}
