<?php

namespace Database\Factories;

use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlayerFactory extends Factory
{
    protected $model = Player::class;

    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'display_name' => fake()->name(),
            'otp_verified' => true,
            'form_completed_at' => now(),
            'last_spin_at' => null,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['otp_verified' => false, 'email_verified_at' => null]);
    }

    public function formIncomplete(): static
    {
        return $this->state(fn () => ['form_completed_at' => null]);
    }
}
