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

    protected $fillable = [
        'campaign_id',
        'name',
        'description',
        'image_path',
        'rarity',
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
}
