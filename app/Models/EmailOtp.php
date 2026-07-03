<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailOtp extends Model
{
    protected $fillable = [
        'email',
        'otp_hash',
        'expires_at',
        'attempts',
        'resend_available_at',
        'verified_at',
        'request_ip',
    ];

    protected $hidden = [
        'otp_hash',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'resend_available_at' => 'datetime',
        'verified_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at !== null && now()->greaterThan($this->expires_at);
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }
}
