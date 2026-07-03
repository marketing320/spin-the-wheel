<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpinResult extends Model
{
    protected $fillable = [
        'spin_session_id',
        'campaign_id',
        'player_id',
        'prize_id',
        'result_payload',
    ];

    protected $casts = [
        'result_payload' => 'array',
    ];

    public function spinSession(): BelongsTo
    {
        return $this->belongsTo(SpinSession::class);
    }

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
}
