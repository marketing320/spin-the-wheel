<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * A game participant, identified by email. Players authenticate through the
 * dedicated "player" guard after verifying an email OTP — they never have a
 * password.
 */
class Player extends Authenticatable implements AuthenticatableContract
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'email',
        'email_verified_at',
        'display_name',
        'otp_verified',
        'form_completed_at',
        'last_spin_at',
        'last_seen_at',
        'blocked_at',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'otp_verified' => 'boolean',
        'form_completed_at' => 'datetime',
        'last_spin_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'blocked_at' => 'datetime',
    ];

    public function formResponses(): HasMany
    {
        return $this->hasMany(PlayerFormResponse::class);
    }

    public function spinSessions(): HasMany
    {
        return $this->hasMany(SpinSession::class);
    }

    public function spinResults(): HasMany
    {
        return $this->hasMany(SpinResult::class);
    }

    public function isVerified(): bool
    {
        return $this->otp_verified && $this->email_verified_at !== null;
    }

    public function hasCompletedForm(): bool
    {
        return $this->form_completed_at !== null;
    }

    public function isBlocked(): bool
    {
        return $this->blocked_at !== null;
    }

    /**
     * Considered "online" if seen within the last 5 minutes.
     */
    public function isOnline(): bool
    {
        return $this->last_seen_at !== null && $this->last_seen_at->gt(now()->subMinutes(5));
    }

    /**
     * A privacy-preserving version of the email for public display.
     */
    public function maskedEmail(): string
    {
        [$name, $domain] = array_pad(explode('@', (string) $this->email, 2), 2, '');

        if ($name === '') {
            return $this->email;
        }

        $visible = mb_substr($name, 0, 1);
        $masked = $visible.str_repeat('*', max(1, mb_strlen($name) - 1));

        return $domain !== '' ? "{$masked}@{$domain}" : $masked;
    }

    public function publicName(): string
    {
        return $this->display_name ?: $this->maskedEmail();
    }
}
