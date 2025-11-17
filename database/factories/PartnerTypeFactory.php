<?php

namespace Jiny\Partner\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Jiny\Partner\Models\PartnerType;

class PartnerTypeFactory extends Factory
{
    protected $model = PartnerType::class;

    public function definition(): array
    {
        $commissionTypes = ['percentage', 'fixed_amount'];
        $commissionType = $this->faker->randomElement($commissionTypes);

        return [
            'type_code' => strtoupper($this->faker->unique()->lexify('?????')),
            'type_name' => $this->faker->words(2, true) . ' 파트너',
            'description' => $this->faker->sentence(10),
            'icon' => 'fe-' . $this->faker->randomElement(['users', 'star', 'trending-up', 'tool', 'book-open', 'headphones']),
            'color' => $this->faker->hexColor(),
            'sort_order' => $this->faker->numberBetween(0, 100),
            'is_active' => $this->faker->boolean(80), // 80% 확률로 활성

            // 전문성 설정
            'specialties' => $this->faker->randomElements([
                'sales', 'technical_support', 'customer_service', 'marketing',
                'training', 'consulting', 'project_management', 'business_development'
            ], $this->faker->numberBetween(1, 4)),

            'required_skills' => $this->faker->randomElements([
                'communication', 'negotiation', 'presentation', 'product_knowledge',
                'technical_expertise', 'problem_solving', 'leadership', 'analytical_thinking'
            ], $this->faker->numberBetween(1, 4)),

            // 성과 기준
            'min_baseline_sales' => $this->faker->numberBetween(500000, 10000000),
            'min_baseline_cases' => $this->faker->numberBetween(10, 200),
            'min_baseline_revenue' => $this->faker->numberBetween(200000, 5000000),
            'min_baseline_clients' => $this->faker->numberBetween(1, 50),
            'baseline_quality_score' => $this->faker->randomFloat(1, 60, 100),

            // 수수료 설정
            'default_commission_type' => $commissionType,
            'default_commission_rate' => $commissionType === 'percentage' ? $this->faker->randomFloat(2, 5, 20) : 0,
            'default_commission_amount' => $commissionType === 'fixed_amount' ? $this->faker->numberBetween(10000, 100000) : 0,
            'commission_notes' => $this->faker->sentence(),

            // 비용 설정
            'registration_fee' => $this->faker->numberBetween(0, 1000000),
            'monthly_maintenance_fee' => $this->faker->numberBetween(0, 200000),
            'annual_maintenance_fee' => $this->faker->numberBetween(0, 2000000),
            'fee_waiver_available' => $this->faker->boolean(30), // 30% 확률로 면제 가능
            'fee_structure_notes' => $this->faker->sentence(),

            'admin_notes' => $this->faker->optional()->sentence(),
            'created_by' => 1, // 기본 관리자 ID
            'updated_by' => null,
        ];
    }

    /**
     * 활성 상태의 파트너 타입
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * 비활성 상태의 파트너 타입
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * 퍼센트 기반 수수료
     */
    public function percentageCommission(): static
    {
        return $this->state(fn (array $attributes) => [
            'default_commission_type' => 'percentage',
            'default_commission_rate' => $this->faker->randomFloat(2, 5, 20),
            'default_commission_amount' => 0,
        ]);
    }

    /**
     * 고정 금액 기반 수수료
     */
    public function fixedAmountCommission(): static
    {
        return $this->state(fn (array $attributes) => [
            'default_commission_type' => 'fixed_amount',
            'default_commission_rate' => 0,
            'default_commission_amount' => $this->faker->numberBetween(10000, 100000),
        ]);
    }

    /**
     * 높은 성과 기준
     */
    public function highPerformance(): static
    {
        return $this->state(fn (array $attributes) => [
            'min_baseline_sales' => $this->faker->numberBetween(5000000, 20000000),
            'min_baseline_cases' => $this->faker->numberBetween(100, 500),
            'baseline_quality_score' => $this->faker->randomFloat(1, 85, 100),
        ]);
    }

    /**
     * 낮은 성과 기준 (초급)
     */
    public function lowPerformance(): static
    {
        return $this->state(fn (array $attributes) => [
            'min_baseline_sales' => $this->faker->numberBetween(100000, 2000000),
            'min_baseline_cases' => $this->faker->numberBetween(5, 50),
            'baseline_quality_score' => $this->faker->randomFloat(1, 60, 80),
        ]);
    }
}