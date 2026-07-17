<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'service_name' => fake()->word(),
            'amount' => fake()->randomFloat(2, 10, 500000),
            'currency' => 'IDR',
            'amount_idr' => function (array $attributes) {
                return $attributes['currency'] === 'IDR' ? $attributes['amount'] : $attributes['amount'] * 15000;
            },
            'billing_day' => fake()->numberBetween(1, 28),
            'billing_cycle' => 'monthly',
            'notes' => fake()->sentence(),
            'active' => true,
            'next_billing_date' => fake()->dateTimeBetween('now', '+1 month'),
        ];
    }
}
