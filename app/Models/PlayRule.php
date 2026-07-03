<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayRule extends Model
{
    public const TYPE_ONCE_PER_CAMPAIGN = 'once_per_campaign';
    public const TYPE_ONCE_PER_DAY = 'once_per_day';
    public const TYPE_EVERY_X_HOURS = 'every_x_hours';
    public const TYPE_MAX_PER_CAMPAIGN = 'max_per_campaign';
    public const TYPE_MAX_PER_DAY = 'max_per_day';

    public const TYPES = [
        self::TYPE_ONCE_PER_CAMPAIGN,
        self::TYPE_ONCE_PER_DAY,
        self::TYPE_EVERY_X_HOURS,
        self::TYPE_MAX_PER_CAMPAIGN,
        self::TYPE_MAX_PER_DAY,
    ];

    protected $fillable = [
        'campaign_id',
        'rule_type',
        'cooldown_hours',
        'max_spins_per_campaign',
        'max_spins_per_day',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'cooldown_hours' => 'integer',
        'max_spins_per_campaign' => 'integer',
        'max_spins_per_day' => 'integer',
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
