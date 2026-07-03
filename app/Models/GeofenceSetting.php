<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeofenceSetting extends Model
{
    protected $fillable = [
        'campaign_id',
        'enabled',
        'location_name',
        'latitude',
        'longitude',
        'radius_meters',
        'blocked_message',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'radius_meters' => 'integer',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function isConfigured(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }
}
