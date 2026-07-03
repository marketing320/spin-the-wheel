<?php

namespace Database\Factories;

use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        $name = fake()->unique()->catchPhrase();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'status' => Campaign::STATUS_ACTIVE,
            'active' => true,
            'prize_mode' => Campaign::MODE_WEIGHTED,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'settings' => null,
        ];
    }

    public function strict(): static
    {
        return $this->state(fn () => ['prize_mode' => Campaign::MODE_STRICT]);
    }
}
