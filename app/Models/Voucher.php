<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A unique, expiring redemption code auto-generated when a player wins a
 * voucher-type prize. Rendered to the player as a QR code + barcode (both
 * encoding `code`) and redeemed by staff via App\Services\VoucherService.
 */
class Voucher extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_REDEEMED = 'redeemed';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'spin_result_id',
        'campaign_id',
        'player_id',
        'prize_id',
        'code',
        'staff_redemption_reminder',
        'status',
        'expires_at',
        'redeemed_at',
        'redeemed_by',
        'rotated_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'redeemed_at' => 'datetime',
        'rotated_at' => 'datetime',
    ];

    public function spinResult(): BelongsTo
    {
        return $this->belongsTo(SpinResult::class);
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

    public function redeemedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'redeemed_by');
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED
            || ($this->status === self::STATUS_PENDING && $this->expires_at->isPast());
    }

    public function isRedeemed(): bool
    {
        return $this->status === self::STATUS_REDEEMED;
    }

    /**
     * Whether this voucher can be redeemed right now (still pending and not
     * past its expiry — evaluated lazily rather than swept by a job).
     */
    public function isRedeemable(): bool
    {
        return $this->status === self::STATUS_PENDING && ! $this->expires_at->isPast();
    }

    /**
     * Whether this voucher's prize has already been rotated back onto the wheel.
     */
    public function isRotated(): bool
    {
        return $this->rotated_at !== null;
    }

    /**
     * Whether this is an expired voucher that was never redeemed and has not
     * yet been rotated — i.e. a candidate to return to the wheel.
     */
    public function isUnusedExpired(): bool
    {
        return $this->status !== self::STATUS_REDEEMED
            && $this->redeemed_at === null
            && $this->rotated_at === null
            && $this->expires_at !== null
            && $this->expires_at->isPast();
    }

    /**
     * Coarse lifecycle label for admin display, collapsing the lazy-expiry and
     * rotation nuances into a single value: redeemed | rotated | expired | pending.
     */
    public function displayStatus(): string
    {
        if ($this->isRedeemed()) {
            return self::STATUS_REDEEMED;
        }

        if ($this->isRotated()) {
            return 'rotated';
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return self::STATUS_EXPIRED;
        }

        return self::STATUS_PENDING;
    }
}
