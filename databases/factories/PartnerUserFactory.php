<?php

namespace Database\Factories\Jiny\Partner\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Models\PartnerTier;

class PartnerUserFactory extends Factory
{
    protected $model = PartnerUser::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => 1, // Will be overridden in tests
            'partner_tier_id' => PartnerTier::factory(),
            'partner_code' => $this->faker->unique()->regexify('[A-Z]{2}[0-9]{6}'),
            'status' => $this->faker->randomElement(['active', 'inactive', 'pending', 'suspended']),
            'join_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'monthly_sales' => $this->faker->numberBetween(0, 5000000),
            'total_sales' => $this->faker->numberBetween(0, 50000000),
            'team_sales' => $this->faker->numberBetween(0, 20000000),
            'earned_commissions' => $this->faker->numberBetween(0, 1000000),
            'last_activity_at' => $this->faker->dateTimeBetween('-1 month', 'now'),

            // MLM Hierarchy fields
            'parent_id' => null,
            'level' => 0,
            'tree_path' => '/',
            'children_count' => 0,
            'total_children_count' => 0,
            'descendants_sales' => 0,
            'generation_depth' => 0,
            'max_children' => $this->faker->numberBetween(5, 50),
            'can_recruit' => true,
            'recruited_by' => null,
            'recruitment_date' => null,
            'direct_commission_rate' => $this->faker->randomFloat(3, 0.01, 0.10),
            'team_commission_rate' => $this->faker->randomFloat(3, 0.005, 0.05),
            'generation_commission_rates' => json_encode([
                'generation_1' => 0.05,
                'generation_2' => 0.03,
                'generation_3' => 0.01
            ]),
            'rank_bonus_rate' => $this->faker->randomFloat(3, 0.001, 0.02),

            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the partner is active
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'can_recruit' => true,
        ]);
    }

    /**
     * Indicate that the partner is inactive
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
            'can_recruit' => false,
        ]);
    }

    /**
     * Set as root partner (level 0, no parent)
     */
    public function rootPartner(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => null,
            'level' => 0,
            'tree_path' => '/',
            'status' => 'active',
            'can_recruit' => true,
        ]);
    }

    /**
     * Set as child partner with specific level
     */
    public function childPartner(int $level = 1, int $parentId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parentId,
            'level' => $level,
            'tree_path' => $parentId ? "/{$parentId}/" : '/',
            'status' => 'active',
        ]);
    }

    /**
     * Set high sales performance
     */
    public function highPerformance(): static
    {
        return $this->state(fn (array $attributes) => [
            'monthly_sales' => $this->faker->numberBetween(2000000, 10000000),
            'total_sales' => $this->faker->numberBetween(20000000, 100000000),
            'team_sales' => $this->faker->numberBetween(10000000, 50000000),
            'earned_commissions' => $this->faker->numberBetween(500000, 2000000),
            'children_count' => $this->faker->numberBetween(10, 30),
            'total_children_count' => $this->faker->numberBetween(50, 200),
        ]);
    }

    /**
     * Set low sales performance
     */
    public function lowPerformance(): static
    {
        return $this->state(fn (array $attributes) => [
            'monthly_sales' => $this->faker->numberBetween(0, 500000),
            'total_sales' => $this->faker->numberBetween(0, 5000000),
            'team_sales' => $this->faker->numberBetween(0, 2000000),
            'earned_commissions' => $this->faker->numberBetween(0, 100000),
            'children_count' => $this->faker->numberBetween(0, 5),
            'total_children_count' => $this->faker->numberBetween(0, 20),
        ]);
    }

    /**
     * Set as recruiter with ability to recruit
     */
    public function recruiter(): static
    {
        return $this->state(fn (array $attributes) => [
            'can_recruit' => true,
            'status' => 'active',
            'children_count' => $this->faker->numberBetween(1, 10),
            'max_children' => $this->faker->numberBetween(10, 50),
        ]);
    }

    /**
     * Set maximum children reached
     */
    public function maxChildrenReached(): static
    {
        $maxChildren = $this->faker->numberBetween(3, 10);
        return $this->state(fn (array $attributes) => [
            'max_children' => $maxChildren,
            'children_count' => $maxChildren,
            'can_recruit' => true,
            'status' => 'active',
        ]);
    }
}