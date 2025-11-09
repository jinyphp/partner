<?php

namespace Database\Factories\Jiny\Partner\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Jiny\Partner\Models\PartnerTier;

class PartnerTierFactory extends Factory
{
    protected $model = PartnerTier::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $tierNames = ['Bronze', 'Silver', 'Gold', 'Platinum', 'Diamond'];

        return [
            'tier_name' => $this->faker->randomElement($tierNames),
            'tier_description' => $this->faker->sentence(10),
            'priority_level' => $this->faker->numberBetween(1, 10),
            'min_sales_requirement' => $this->faker->numberBetween(100000, 5000000),
            'min_team_size' => $this->faker->numberBetween(1, 20),
            'commission_rate' => $this->faker->randomFloat(3, 0.01, 0.15),
            'bonus_rate' => $this->faker->randomFloat(3, 0.01, 0.10),
            'is_active' => true,
            'can_recruit' => true,
            'max_children' => $this->faker->numberBetween(5, 50),
            'depth_limit' => $this->faker->numberBetween(3, 10),
            'recruitment_bonus' => $this->faker->numberBetween(10000, 100000),
            'monthly_target' => $this->faker->numberBetween(500000, 10000000),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the tier is active and can recruit
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'can_recruit' => true,
        ]);
    }

    /**
     * Indicate that the tier is inactive
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Bronze tier configuration
     */
    public function bronze(): static
    {
        return $this->state(fn (array $attributes) => [
            'tier_name' => 'Bronze',
            'priority_level' => 1,
            'commission_rate' => 0.05,
            'min_sales_requirement' => 100000,
            'max_children' => 5,
        ]);
    }

    /**
     * Silver tier configuration
     */
    public function silver(): static
    {
        return $this->state(fn (array $attributes) => [
            'tier_name' => 'Silver',
            'priority_level' => 2,
            'commission_rate' => 0.07,
            'min_sales_requirement' => 500000,
            'max_children' => 10,
        ]);
    }

    /**
     * Gold tier configuration
     */
    public function gold(): static
    {
        return $this->state(fn (array $attributes) => [
            'tier_name' => 'Gold',
            'priority_level' => 3,
            'commission_rate' => 0.10,
            'min_sales_requirement' => 1000000,
            'max_children' => 20,
        ]);
    }
}