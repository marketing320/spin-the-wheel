<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Prize;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrizeFactory extends Factory
{
    protected $model = Prize::class;

    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'name' => fake()->unique()->words(2, true),
            'description' => null,
            'rarity' => 'common',
            'color' => fake()->hexColor(),
            'win_percentage' => 10,
            'weight' => 10,
            'inventory_enabled' => false,
            'inventory_quantity' => null,
            'confetti_level' => 'light',
            'redemption_message' => 'Redeem at the counter.',
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['inventory_enabled' => true, 'inventory_quantity' => 0]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
