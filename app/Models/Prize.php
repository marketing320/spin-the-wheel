<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Prize extends Model
{
    use HasFactory;

    public const RARITIES = ['common', 'uncommon', 'rare', 'epic', 'legendary'];
    public const CONFETTI_LEVELS = ['light', 'medium', 'strong', 'heavy', 'max'];

    public const TYPE_PHYSICAL = 'physical';
    public const TYPE_VOUCHER = 'voucher';
    public const TYPES = [self::TYPE_PHYSICAL, self::TYPE_VOUCHER];

    protected $fillable = [
        'campaign_id',
        'name',
        'description',
        'image_path',
        'rarity',
        'type',
        'voucher_expiry_hours',
        'color',
        'win_percentage',
        'weight',
        'inventory_quantity',
        'inventory_enabled',
        'confetti_level',
        'redemption_message',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'win_percentage' => 'decimal:4',
        'weight' => 'integer',
        'inventory_quantity' => 'integer',
        'inventory_enabled' => 'boolean',
        'voucher_expiry_hours' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Whether this prize can currently be won (active + in stock).
     */
    public function isWinnable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->inventory_enabled && $this->inventory_quantity !== null && $this->inventory_quantity <= 0) {
            return false;
        }

        return true;
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return Storage::disk('public')->url($this->image_path);
    }

    /**
     * Default segment color when the admin did not choose one.
     */
    public function displayColor(): string
    {
        return $this->color ?: '#6366f1';
    }

    public function isVoucher(): bool
    {
        return $this->type === self::TYPE_VOUCHER;
    }
}
