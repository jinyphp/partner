<?php

namespace Database\Factories\Jiny\Partner\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Jiny\Partner\Models\PartnerNetworkRelationship;
use Jiny\Partner\Models\PartnerUser;

class PartnerNetworkRelationshipFactory extends Factory
{
    protected $model = PartnerNetworkRelationship::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'parent_id' => PartnerUser::factory(),
            'child_id' => PartnerUser::factory(),
            'recruiter_id' => function (array $attributes) {
                return $attributes['parent_id']; // Default recruiter is the parent
            },
            'depth' => $this->faker->numberBetween(1, 5),
            'relationship_path' => function (array $attributes) {
                return "{$attributes['parent_id']}/{$attributes['child_id']}";
            },
            'is_active' => true,
            'recruited_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'recruitment_notes' => $this->faker->optional()->sentence(10),

            // Performance tracking
            'total_generated_sales' => $this->faker->numberBetween(0, 10000000),
            'total_commissions_paid' => $this->faker->numberBetween(0, 500000),
            'monthly_performance_score' => $this->faker->numberBetween(0, 100),
            'last_activity_at' => $this->faker->dateTimeBetween('-1 month', 'now'),

            // Deactivation fields (null for active relationships)
            'deactivated_at' => null,
            'deactivated_by' => null,
            'deactivation_reason' => null,

            // Metadata
            'metadata' => json_encode([
                'recruitment_channel' => $this->faker->randomElement(['direct', 'referral', 'online', 'event']),
                'initial_tier' => $this->faker->randomElement(['Bronze', 'Silver']),
                'recruitment_location' => $this->faker->city,
                'onboarding_completed' => $this->faker->boolean(80),
                'training_completed' => $this->faker->boolean(70)
            ]),

            'created_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the relationship is active
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'deactivated_at' => null,
            'deactivated_by' => null,
            'deactivation_reason' => null,
        ]);
    }

    /**
     * Indicate that the relationship is inactive/deactivated
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'deactivated_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'deactivated_by' => $this->faker->numberBetween(1, 10),
            'deactivation_reason' => $this->faker->randomElement([
                'Performance issues',
                'Violation of terms',
                'Voluntary resignation',
                'Restructuring',
                'Inactivity'
            ]),
        ]);
    }

    /**
     * Set as direct relationship (depth 1)
     */
    public function directRelationship(): static
    {
        return $this->state(fn (array $attributes) => [
            'depth' => 1,
            'recruiter_id' => $attributes['parent_id'], // Direct recruiter is the parent
        ]);
    }

    /**
     * Set as indirect relationship (depth > 1)
     */
    public function indirectRelationship(int $depth = 2): static
    {
        return $this->state(fn (array $attributes) => [
            'depth' => $depth,
        ]);
    }

    /**
     * High performance relationship
     */
    public function highPerformance(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_generated_sales' => $this->faker->numberBetween(5000000, 50000000),
            'total_commissions_paid' => $this->faker->numberBetween(250000, 2500000),
            'monthly_performance_score' => $this->faker->numberBetween(80, 100),
            'last_activity_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Low performance relationship
     */
    public function lowPerformance(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_generated_sales' => $this->faker->numberBetween(0, 500000),
            'total_commissions_paid' => $this->faker->numberBetween(0, 25000),
            'monthly_performance_score' => $this->faker->numberBetween(0, 30),
            'last_activity_at' => $this->faker->dateTimeBetween('-3 months', '-1 month'),
        ]);
    }

    /**
     * Recent recruitment (recruited this month)
     */
    public function recentRecruitment(): static
    {
        return $this->state(fn (array $attributes) => [
            'recruited_at' => $this->faker->dateTimeBetween(now()->startOfMonth(), 'now'),
            'created_at' => $this->faker->dateTimeBetween(now()->startOfMonth(), 'now'),
            'total_generated_sales' => $this->faker->numberBetween(0, 1000000),
            'total_commissions_paid' => $this->faker->numberBetween(0, 50000),
        ]);
    }

    /**
     * Old recruitment (recruited over a year ago)
     */
    public function oldRecruitment(): static
    {
        return $this->state(fn (array $attributes) => [
            'recruited_at' => $this->faker->dateTimeBetween('-3 years', '-1 year'),
            'created_at' => $this->faker->dateTimeBetween('-3 years', '-1 year'),
            'total_generated_sales' => $this->faker->numberBetween(1000000, 20000000),
            'total_commissions_paid' => $this->faker->numberBetween(50000, 1000000),
        ]);
    }

    /**
     * With specific parent and child
     */
    public function withRelationship(int $parentId, int $childId, int $recruiterId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parentId,
            'child_id' => $childId,
            'recruiter_id' => $recruiterId ?? $parentId,
            'relationship_path' => "{$parentId}/{$childId}",
        ]);
    }

    /**
     * Completed onboarding and training
     */
    public function completedOnboarding(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => json_encode([
                'recruitment_channel' => $this->faker->randomElement(['direct', 'referral', 'online', 'event']),
                'initial_tier' => $this->faker->randomElement(['Bronze', 'Silver']),
                'recruitment_location' => $this->faker->city,
                'onboarding_completed' => true,
                'training_completed' => true,
                'onboarding_date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
                'training_completion_date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d')
            ]),
        ]);
    }
}