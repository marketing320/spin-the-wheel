<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Spin The Wheel — application defaults
    |--------------------------------------------------------------------------
    |
    | These are fallback defaults. Most of these values can be overridden at
    | runtime by an administrator through the admin settings screen, which
    | persists overrides in the `app_settings` table. See App\Support\Settings.
    |
    */

    'otp' => [
        // How long an OTP code remains valid.
        'expiry_minutes' => (int) env('OTP_EXPIRY_MINUTES', 10),
        // Cooldown before a player may request another OTP.
        'resend_cooldown_seconds' => (int) env('OTP_RESEND_COOLDOWN_SECONDS', 60),
        // Maximum number of incorrect verification attempts before lockout.
        'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),
        // Number of digits in the generated code.
        'length' => 6,
    ],

    'spin' => [
        // Failsafe: how long an active spin lock may live before it is
        // considered stuck and eligible for release.
        'lock_timeout_seconds' => (int) env('SPIN_LOCK_TIMEOUT_SECONDS', 45),
        // Fixed wheel animation duration in milliseconds.
        'default_duration_ms' => 8000,
        // Full soundtrack duration, including the congratulations ending.
        'sound_duration_ms' => 11000,
        // Result-display cooldown before the next queued player may spin.
        'buffer_duration_ms' => 7000,
        // Queue entries disappear when their browser has stopped checking in.
        'queue_presence_seconds' => 120,
        // Number of full rotations before landing on the target segment.
        'base_rotations' => 6,
    ],

];
