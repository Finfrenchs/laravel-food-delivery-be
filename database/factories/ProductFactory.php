<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'price' => $this->faker->numberBetween(1000, 100000),
            'stock' => $this->faker->numberBetween(1, 100),
            'description' => $this->faker->sentence(),
            'is_available' => $this->faker->boolean(),
            'is_favorite' => $this->faker->boolean(),
            'image' => $this->faker->imageUrl(640, 480, 'food', true, 'Product'),
            'user_id' => User::factory()->state(['roles' => 'restaurant'])->create()->id,
        ];
    }
}
