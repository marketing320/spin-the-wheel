<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Campaign extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_ENDED = 'ended';

    public const MODE_STRICT = 'strict';
    public const MODE_WEIGHTED = 'weighted';

    protected $fillable = [
        'name',
        'slug',
        'status',
        'starts_at',
        'ends_at',
        'active',
        'prize_mode',
        'settings',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'active' => 'boolean',
        'settings' => 'array',
    ];

    public function prizes(): HasMany
    {
        return $this->hasMany(Prize::class);
    }

    public function activePrizes(): HasMany
    {
        return $this->hasMany(Prize::class)->where('is_active', true)->orderBy('sort_order');
    }

    public function formFields(): HasMany
    {
        return $this->hasMany(FormField::class);
    }

    public function playRules(): HasMany
    {
        return $this->hasMany(PlayRule::class);
    }

    public function geofenceSetting(): HasOne
    {
        return $this->hasOne(GeofenceSetting::class);
    }

    public function spinSessions(): HasMany
    {
        return $this->hasMany(SpinSession::class);
    }

    public function spinResults(): HasMany
    {
        return $this->hasMany(SpinResult::class);
    }

    /**
     * Whether the campaign is currently open for play (active + within window).
     */
    public function isPlayable(): bool
    {
        if (! $this->active || $this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    /**
     * Resolve the single active campaign, if any.
     */
    public static function current(): ?self
    {
        return static::where('active', true)
            ->where('status', self::STATUS_ACTIVE)
            ->orderByDesc('id')
            ->first();
    }
}
