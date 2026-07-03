<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormField extends Model
{
    use HasFactory;

    public const TYPES = [
        'text', 'email', 'phone', 'number',
        'select', 'radio', 'checkbox', 'date', 'consent',
    ];

    /** Types that carry a list of options. */
    public const OPTION_TYPES = ['select', 'radio', 'checkbox'];

    protected $fillable = [
        'campaign_id',
        'label',
        'field_key',
        'field_type',
        'placeholder',
        'options',
        'validation_rules',
        'is_required',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'options' => 'array',
        'validation_rules' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function hasOptions(): bool
    {
        return in_array($this->field_type, self::OPTION_TYPES, true);
    }
}
