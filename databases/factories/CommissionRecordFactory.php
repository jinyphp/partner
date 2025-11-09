<?php

namespace Database\Factories\Jiny\Partner\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Jiny\Partner\Models\CommissionRecord;
use Jiny\Partner\Models\PartnerUser;

class CommissionRecordFactory extends Factory
{
    protected $model = CommissionRecord::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $commissionTypes = [
            'direct_sales',
            'team_sales',
            'recruitment_bonus',
            'rank_bonus',
            'generation_bonus',
            'leadership_bonus',
            'manual_bonus'
        ];

        $status = $this->faker->randomElement(['pending', 'approved', 'paid', 'cancelled']);

        return [
            'partner_id' => PartnerUser::factory(),
            'commission_type' => $this->faker->randomElement($commissionTypes),
            'amount' => $this->faker->numberBetween(1000, 500000),
            'currency' => 'KRW',
            'status' => $status,
            'description' => $this->faker->sentence(8),
            'notes' => $this->faker->optional()->paragraph(3),

            // Source information
            'source_type' => $this->faker->randomElement(['order', 'recruitment', 'manual', 'bonus']),
            'source_id' => $this->faker->numberBetween(1, 1000),
            'source_data' => json_encode([
                'reference_number' => $this->faker->uuid,
                'calculation_method' => 'percentage',
                'base_amount' => $this->faker->numberBetween(10000, 5000000)
            ]),

            // Calculation details
            'calculation_data' => json_encode([
                'rate' => $this->faker->randomFloat(3, 0.01, 0.15),
                'base_amount' => $this->faker->numberBetween(10000, 5000000),
                'formula' => 'base_amount * rate',
                'tier_bonus' => $this->faker->numberBetween(0, 50000)
            ]),

            // Period information
            'period_year' => $this->faker->numberBetween(2023, 2025),
            'period_month' => $this->faker->numberBetween(1, 12),
            'period_week' => $this->faker->numberBetween(1, 52),

            // Processing information
            'processed_at' => $status === 'paid' ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
            'processed_by' => $status === 'paid' ? $this->faker->numberBetween(1, 10) : null,
            'approved_at' => in_array($status, ['approved', 'paid']) ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
            'approved_by' => in_array($status, ['approved', 'paid']) ? $this->faker->numberBetween(1, 10) : null,
            'paid_at' => $status === 'paid' ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
            'payment_method' => $status === 'paid' ? $this->faker->randomElement(['bank_transfer', 'check', 'cash', 'digital_wallet']) : null,
            'payment_reference' => $status === 'paid' ? $this->faker->uuid : null,

            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the commission is pending
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'processed_at' => null,
            'processed_by' => null,
            'approved_at' => null,
            'approved_by' => null,
            'paid_at' => null,
            'payment_method' => null,
            'payment_reference' => null,
        ]);
    }

    /**
     * Indicate that the commission is approved
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'approved_by' => $this->faker->numberBetween(1, 10),
            'paid_at' => null,
            'payment_method' => null,
            'payment_reference' => null,
        ]);
    }

    /**
     * Indicate that the commission is paid
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'approved_at' => $this->faker->dateTimeBetween('-1 month', '-1 week'),
            'approved_by' => $this->faker->numberBetween(1, 10),
            'paid_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'payment_method' => $this->faker->randomElement(['bank_transfer', 'check', 'cash', 'digital_wallet']),
            'payment_reference' => $this->faker->uuid,
            'processed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'processed_by' => $this->faker->numberBetween(1, 10),
        ]);
    }

    /**
     * Direct sales commission
     */
    public function directSales(): static
    {
        return $this->state(fn (array $attributes) => [
            'commission_type' => 'direct_sales',
            'description' => 'Direct sales commission',
            'source_type' => 'order',
        ]);
    }

    /**
     * Team sales commission
     */
    public function teamSales(): static
    {
        return $this->state(fn (array $attributes) => [
            'commission_type' => 'team_sales',
            'description' => 'Team sales commission',
            'source_type' => 'order',
        ]);
    }

    /**
     * Recruitment bonus
     */
    public function recruitmentBonus(): static
    {
        return $this->state(fn (array $attributes) => [
            'commission_type' => 'recruitment_bonus',
            'description' => 'New partner recruitment bonus',
            'source_type' => 'recruitment',
            'amount' => $this->faker->numberBetween(50000, 200000),
        ]);
    }

    /**
     * Manual bonus
     */
    public function manualBonus(): static
    {
        return $this->state(fn (array $attributes) => [
            'commission_type' => 'manual_bonus',
            'description' => 'Manual bonus by admin',
            'source_type' => 'manual',
        ]);
    }

    /**
     * This month commission
     */
    public function thisMonth(): static
    {
        return $this->state(fn (array $attributes) => [
            'period_year' => now()->year,
            'period_month' => now()->month,
            'created_at' => now()->startOfMonth()->addDays(rand(0, 28)),
        ]);
    }

    /**
     * Last month commission
     */
    public function lastMonth(): static
    {
        $lastMonth = now()->subMonth();
        return $this->state(fn (array $attributes) => [
            'period_year' => $lastMonth->year,
            'period_month' => $lastMonth->month,
            'created_at' => $lastMonth->startOfMonth()->addDays(rand(0, 28)),
        ]);
    }
}