<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SpinSession extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_FAILED = 'failed';

    /** Sentinel value used with the unique `active_guard` index. */
    public const GUARD_ON = 1;

    protected $fillable = [
        'campaign_id',
        'player_id',
        'prize_id',
        'status',
        'started_at',
        'ends_at',
        'completed_at',
        'expires_at',
        'spin_duration_ms',
        'final_angle',
        'animation_seed',
        'request_ip',
        'user_agent',
        'metadata',
        'active_guard',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ends_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
        'spin_duration_ms' => 'integer',
        'final_angle' => 'float',
        'metadata' => 'array',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function prize(): BelongsTo
    {
        return $this->belongsTo(Prize::class);
    }

    public function result(): HasOne
    {
        return $this->hasOne(SpinResult::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function hasExpired(): bool
    {
        return $this->expires_at !== null && now()->greaterThan($this->expires_at);
    }
}
