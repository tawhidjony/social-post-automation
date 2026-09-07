<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'price' => fake()->randomFloat(2, 0, 99),
            'max_social_accounts' => fake()->numberBetween(1, 20),
            'max_posts_per_month' => fake()->numberBetween(10, 1000),
            'max_members' => fake()->numberBetween(1, 10),
            'has_analytics' => fake()->boolean(),
        ];
    }
}
