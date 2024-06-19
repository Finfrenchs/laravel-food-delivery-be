<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
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
            'restaurant_id' => User::factory()->state(['roles' => 'restaurant']),
            'driver_id' => $this->faker->optional()->randomElement([User::factory()->state(['roles' => 'driver']), null]),
            'total_price' => $this->faker->numberBetween(1000, 5000),
            'shipping_cost' => $this->faker->numberBetween(100, 500),
            'total_bill' => function (array $attributes) {
                return $attributes['total_price'] + $attributes['shipping_cost'];
            },
            'payment_method' => $this->faker->randomElement(['bank_transfer', 'e_wallet']),
            'payment_e_wallet' => $this->faker->optional()->creditCardNumber,
            'status' => 'pending',
            'shipping_address' => $this->faker->address,
            'shipping_latlong' => $this->faker->latitude . ',' . $this->faker->longitude,
        ];
    }
}
