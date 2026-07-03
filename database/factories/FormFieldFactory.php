<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\FormField;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FormFieldFactory extends Factory
{
    protected $model = FormField::class;

    public function definition(): array
    {
        $label = fake()->unique()->words(2, true);

        return [
            'campaign_id' => Campaign::factory(),
            'label' => ucfirst($label),
            'field_key' => Str::slug($label, '_'),
            'field_type' => 'text',
            'placeholder' => null,
            'options' => null,
            'validation_rules' => null,
            'is_required' => true,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
