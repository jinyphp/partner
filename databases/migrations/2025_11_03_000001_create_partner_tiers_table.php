<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 파트너 등급 시스템 테이블 생성 (통합 버전)
     *
     * 파트너(엔지니어)의 성과와 경험에 따른 등급 분류 시스템
     * Bronze → Silver → Gold → Platinum 순으로 승급하며,
     * 각 등급별로 수수료율, 우선순위, 혜택이 차등 적용됨
     *
     * 통합 기능:
     * - 계층 관리 및 모집 권한 (hierarchy_settings)
     * - 비용 관리 시스템 (cost_management)
     * - 허용 파트너 타입 관리 (allowed_types)
     * - 고급 수수료 시스템 (commission_enhancements)
     * - 성과 기반 자동 등급 평가
     * - 상위/하위 등급 계층 구조 지원
     */
    public function up(): void
    {
        Schema::create('partner_tiers', function (Blueprint $table) {
            // 기본 필드
            $table->id(); // 등급 고유 ID
            $table->timestamps(); // 생성일시, 수정일시
            $table->softDeletes(); // 소프트 삭제 지원

            // 기본 정보
            $table->string('tier_code', 20)->unique(); // 등급 코드 (bronze, silver, gold, platinum)
            $table->string('tier_name', 100); // 등급명 (브론즈 파트너, 실버 파트너 등)
            $table->text('description')->nullable(); // 등급에 대한 상세 설명

            // 수수료 및 우선순위
            $table->decimal('commission_rate', 5, 2); // 수수료율 (60.00% ~ 75.00%)

            // 고급 수수료 시스템
            $table->enum('commission_type', ['percentage', 'fixed_amount'])
                  ->default('percentage')
                  ->comment('수수료 타입: percentage(%), fixed_amount(고정금액)');
            $table->decimal('commission_amount', 15, 2)
                  ->nullable()
                  ->comment('고정 금액 수수료');

            $table->integer('priority_level'); // 우선순위 레벨 (낮을수록 높은 우선순위, 1=최고)

            // 우선순위 관리 개선
            $table->integer('display_order')
                  ->default(999)
                  ->comment('표시 순서 (낮을수록 상위)');

            // 상위 등급 참조 (계층 구조)
            $table->unsignedBigInteger('parent_tier_id')
                  ->nullable()
                  ->comment('상위 등급 ID');

            // 수수료율 제한 설정
            $table->boolean('inherit_parent_commission')
                  ->default(false)
                  ->comment('상위 등급 수수료율 상속 여부');

            // 최대 수수료율 (상위 등급을 초과할 수 없음)
            $table->decimal('max_commission_rate', 8, 4)
                  ->nullable()
                  ->comment('최대 수수료율 (상위 등급 제한)');

            // 요구사항 및 혜택 (JSON)
            $table->json('requirements');
            // 등급 달성 요구사항 - 구조 예시:
            // {
            //   "min_experience_months": 12,
            //   "min_completed_jobs": 150,
            //   "min_rating": 4.5,
            //   "required_certifications": ["기본 자격증", "전문 자격증"],
            //   "leadership_experience": true
            // }

            $table->json('benefits');
            // 등급별 혜택 - 구조 예시:
            // {
            //   "job_assignment_priority": "high",
            //   "maximum_concurrent_jobs": 6,
            //   "support_response_time": "6시간",
            //   "training_access": ["모든 교육 과정"],
            //   "bonus_eligibility": true,
            //   "performance_bonus_rate": 10
            // }

            // 등급 관리
            $table->boolean('is_active')->default(true); // 등급 활성화 상태
            $table->integer('sort_order')->default(0); // 정렬 순서

            // 성과 기준 (자동 등급 평가용)
            $table->integer('min_completed_jobs')->default(0); // 최소 완료 작업 수
            $table->decimal('min_rating', 3, 2)->default(0); // 최소 평점 (0.00 ~ 5.00)
            $table->decimal('min_punctuality_rate', 5, 2)->default(0); // 최소 시간 준수율 (%)
            $table->decimal('min_satisfaction_rate', 5, 2)->default(0); // 최소 고객 만족도 (%)

            // ====================================================================
            // 계층 관리 및 모집 권한 설정 (Hierarchy Management)
            // ====================================================================
            $table->integer('max_children')->default(0)->comment('관리 가능한 최대 하위 파트너 수');
            $table->integer('max_depth')->default(1)->comment('최대 계층 깊이');
            $table->boolean('can_recruit')->default(false)->comment('모집 권한 여부');

            // 커미션 및 할인율 설정
            $table->decimal('base_commission_rate', 5, 2)->default(0)->comment('기본 커미션율 (%)');
            $table->decimal('management_bonus_rate', 5, 2)->default(0)->comment('관리 보너스율 (%)');
            $table->decimal('discount_rate', 5, 2)->default(0)->comment('할인율 (%)');
            $table->decimal('override_commission_rate', 5, 2)->default(0)->comment('오버라이드 커미션율 (%)');

            // 승급 조건 설정
            $table->decimal('required_monthly_sales', 15, 2)->default(0)->comment('월간 매출 요구사항');
            $table->decimal('required_team_sales', 15, 2)->default(0)->comment('팀 매출 요구사항');
            $table->integer('required_team_size')->default(0)->comment('팀 규모 요구사항');
            $table->integer('required_active_children')->default(0)->comment('활성 하위 파트너 요구사항');

            // 혜택 및 제한 설정 (JSON)
            $table->json('recruitment_settings')->nullable()->comment('모집 관련 설정');
            $table->json('commission_settings')->nullable()->comment('커미션 관련 설정');
            $table->json('network_limitations')->nullable()->comment('네트워크 제한 설정');

            // ====================================================================
            // 비용 관리 설정 (Cost Management)
            // ====================================================================

            // 가입 관련 비용
            $table->decimal('registration_fee', 10, 2)->default(0)->comment('가입비용');
            $table->decimal('activation_fee', 10, 2)->default(0)->comment('활성화 비용');
            $table->decimal('upgrade_fee', 10, 2)->default(0)->comment('등급 업그레이드 비용');

            // 정기 유지 비용
            $table->decimal('monthly_maintenance_fee', 10, 2)->default(0)->comment('월 유지비용');
            $table->decimal('annual_maintenance_fee', 10, 2)->default(0)->comment('연 유지비용');
            $table->decimal('renewal_fee', 10, 2)->default(0)->comment('갱신비용');

            // 서비스 이용 비용
            $table->decimal('service_fee_rate', 5, 4)->default(0)->comment('서비스 이용료율 (%)');
            $table->decimal('platform_fee_rate', 5, 4)->default(0)->comment('플랫폼 이용료율 (%)');
            $table->decimal('transaction_fee_rate', 5, 4)->default(0)->comment('거래 수수료율 (%)');

            // 보증금 및 담보
            $table->decimal('security_deposit', 10, 2)->default(0)->comment('보증금');
            $table->decimal('performance_bond', 10, 2)->default(0)->comment('이행보증금');

            // 인센티브 및 할인
            $table->decimal('early_payment_discount_rate', 5, 4)->default(0)->comment('조기납부 할인율 (%)');
            $table->decimal('loyalty_discount_rate', 5, 4)->default(0)->comment('충성도 할인율 (%)');
            $table->decimal('volume_discount_rate', 5, 4)->default(0)->comment('볼륨 할인율 (%)');

            // 비용 정책 설정 (JSON)
            $table->json('cost_policy')->nullable()->comment('비용 정책 설정');
            // 비용 정책 구조 예시:
            // {
            //   "billing_cycle": "monthly|quarterly|annually",
            //   "payment_terms": 30,
            //   "late_payment_penalty_rate": 0.05,
            //   "grace_period_days": 7,
            //   "auto_renewal": true,
            //   "payment_methods": ["bank_transfer", "card", "digital_wallet"],
            //   "currency": "KRW",
            //   "tax_inclusive": true,
            //   "refund_policy": "pro_rated"
            // }

            $table->json('fee_exemptions')->nullable()->comment('비용 면제 조건');
            $table->json('promotional_pricing')->nullable()->comment('프로모션 가격 정책');

            // 비용 관리 활성화
            $table->boolean('cost_management_enabled')->default(false)->comment('비용 관리 활성화 여부');
            $table->timestamp('cost_policy_updated_at')->nullable()->comment('비용 정책 최종 수정일');

            // ====================================================================
            // 허용 파트너 타입 관리 (Allowed Partner Types)
            // ====================================================================

            // 허용 파트너 타입 ID 배열 (JSON 형태)
            $table->json('allowed_types')->nullable()->comment('허용 파트너 타입 ID 배열');
            // 구조 예시: [1, 2, 3] (partner_types 테이블의 ID 배열)
            // null인 경우 모든 활성 타입 허용

            // 등급별 타입 제한 설정
            $table->boolean('restrict_types')->default(false)->comment('타입 제한 여부');
            // true: allowed_types에 지정된 타입만 허용
            // false: 모든 활성 타입 허용

            // 성능 최적화를 위한 인덱스
            $table->index(['is_active', 'priority_level']); // 활성 등급별 우선순위 조회용
            $table->index(['tier_code']); // 등급 코드 조회용

            // 계층 관리 관련 인덱스
            $table->index(['can_recruit', 'max_children']); // 모집 권한 및 최대 하위 파트너 수 조회용
            $table->index(['priority_level', 'base_commission_rate']); // 우선순위 및 기본 커미션율 조회용

            // 타입 제한 관련 인덱스
            $table->index(['restrict_types']); // 타입 제한 여부별 조회용

            // 고급 수수료 시스템 관련 인덱스
            $table->index(['priority_level', 'display_order'], 'idx_tier_priority_display'); // 우선순위와 표시 순서 조합
            $table->index(['parent_tier_id'], 'idx_tier_parent'); // 상위 등급 조회용
            $table->index(['commission_type'], 'idx_tier_commission_type'); // 수수료 타입별 조회용

            // 외래키 제약조건
            $table->foreign('parent_tier_id')
                  ->references('id')
                  ->on('partner_tiers')
                  ->onDelete('set null');
        });

        // Insert default partner tiers
        DB::table('partner_tiers')->insert([
            [
                'tier_code' => 'bronze',
                'tier_name' => '브론즈 파트너',
                'description' => '신입 파트너를 위한 기본 등급',
                'commission_rate' => 60.00,
                'priority_level' => 4,
                'requirements' => json_encode([
                    'min_experience_months' => 0,
                    'min_completed_jobs' => 0,
                    'min_rating' => 0,
                    'required_certifications' => [],
                    'onboarding_completed' => true
                ]),
                'benefits' => json_encode([
                    'job_assignment_priority' => 'low',
                    'maximum_concurrent_jobs' => 2,
                    'support_response_time' => '24시간',
                    'training_access' => ['기본 교육'],
                    'bonus_eligibility' => false
                ]),
                'is_active' => true,
                'sort_order' => 1,
                'min_completed_jobs' => 0,
                'min_rating' => 0,
                'min_punctuality_rate' => 0,
                'min_satisfaction_rate' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tier_code' => 'silver',
                'tier_name' => '실버 파트너',
                'description' => '경험이 쌓인 중급 파트너 등급',
                'commission_rate' => 65.00,
                'priority_level' => 3,
                'requirements' => json_encode([
                    'min_experience_months' => 6,
                    'min_completed_jobs' => 50,
                    'min_rating' => 4.0,
                    'required_certifications' => ['기본 자격증'],
                    'customer_complaints' => ['< 5회/월']
                ]),
                'benefits' => json_encode([
                    'job_assignment_priority' => 'normal',
                    'maximum_concurrent_jobs' => 4,
                    'support_response_time' => '12시간',
                    'training_access' => ['기본 교육', '중급 교육'],
                    'bonus_eligibility' => true,
                    'performance_bonus_rate' => 5
                ]),
                'is_active' => true,
                'sort_order' => 2,
                'min_completed_jobs' => 50,
                'min_rating' => 4.00,
                'min_punctuality_rate' => 85.00,
                'min_satisfaction_rate' => 80.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tier_code' => 'gold',
                'tier_name' => '골드 파트너',
                'description' => '숙련된 고급 파트너 등급',
                'commission_rate' => 70.00,
                'priority_level' => 2,
                'requirements' => json_encode([
                    'min_experience_months' => 12,
                    'min_completed_jobs' => 150,
                    'min_rating' => 4.5,
                    'required_certifications' => ['기본 자격증', '전문 자격증'],
                    'leadership_experience' => true,
                    'mentoring_junior_partners' => '최소 2명'
                ]),
                'benefits' => json_encode([
                    'job_assignment_priority' => 'high',
                    'maximum_concurrent_jobs' => 6,
                    'support_response_time' => '6시간',
                    'training_access' => ['모든 교육 과정'],
                    'bonus_eligibility' => true,
                    'performance_bonus_rate' => 10,
                    'premium_projects_access' => true,
                    'flexible_schedule' => true
                ]),
                'is_active' => true,
                'sort_order' => 3,
                'min_completed_jobs' => 150,
                'min_rating' => 4.50,
                'min_punctuality_rate' => 90.00,
                'min_satisfaction_rate' => 90.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tier_code' => 'platinum',
                'tier_name' => '플래티넘 파트너',
                'description' => '최고 수준의 전문 파트너 등급',
                'commission_rate' => 75.00,
                'priority_level' => 1,
                'requirements' => json_encode([
                    'min_experience_months' => 24,
                    'min_completed_jobs' => 300,
                    'min_rating' => 4.8,
                    'required_certifications' => ['모든 관련 자격증'],
                    'expert_specialization' => true,
                    'customer_testimonials' => '최소 10개',
                    'innovation_contributions' => true
                ]),
                'benefits' => json_encode([
                    'job_assignment_priority' => 'highest',
                    'maximum_concurrent_jobs' => 10,
                    'support_response_time' => '즉시',
                    'training_access' => ['모든 교육 + VIP 세미나'],
                    'bonus_eligibility' => true,
                    'performance_bonus_rate' => 15,
                    'premium_projects_access' => true,
                    'vip_customer_access' => true,
                    'flexible_schedule' => true,
                    'annual_performance_bonus' => true,
                    'stock_option_eligibility' => true
                ]),
                'is_active' => true,
                'sort_order' => 4,
                'min_completed_jobs' => 300,
                'min_rating' => 4.80,
                'min_punctuality_rate' => 95.00,
                'min_satisfaction_rate' => 95.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // 기본 등급별 허용 타입 설정
        $this->setDefaultAllowedTypes();
    }

    /**
     * 기본 등급별 허용 타입 설정
     */
    private function setDefaultAllowedTypes(): void
    {
        // 활성 타입 조회
        $salesType = DB::table('partner_types')->where('type_code', 'SALES')->where('is_active', true)->first();
        $techType = DB::table('partner_types')->where('type_code', 'TECH_SUPPORT')->where('is_active', true)->first();
        $marketingType = DB::table('partner_types')->where('type_code', 'MARKETING')->where('is_active', true)->first();
        $trainingType = DB::table('partner_types')->where('type_code', 'TRAINING')->where('is_active', true)->first();
        $consultantType = DB::table('partner_types')->where('type_code', 'CONSULTANT')->where('is_active', true)->first();
        $customerType = DB::table('partner_types')->where('type_code', 'CUSTOMER_SERVICE')->where('is_active', true)->first();

        if (!$salesType || !$techType) {
            return; // 기본 타입이 없으면 설정하지 않음
        }

        $basicTypes = [$salesType->id, $techType->id];
        $standardTypes = array_filter([$salesType->id, $techType->id, $marketingType->id ?? null]);
        $premiumTypes = array_filter([$salesType->id, $marketingType->id ?? null, $consultantType->id ?? null]);
        $allTypes = array_filter([
            $salesType->id,
            $techType->id,
            $marketingType->id ?? null,
            $trainingType->id ?? null,
            $consultantType->id ?? null,
            $customerType->id ?? null
        ]);

        // 브론즈, 실버 등급: 기본 타입만 (세일즈, 기술지원)
        DB::table('partner_tiers')
            ->whereIn('tier_code', ['bronze', 'silver', 'BRONZE', 'SILVER'])
            ->update([
                'allowed_types' => json_encode($basicTypes),
                'restrict_types' => true,
                'updated_at' => now()
            ]);

        // 골드 등급: 일반 타입 (세일즈, 기술지원, 마케팅)
        DB::table('partner_tiers')
            ->where('tier_code', 'gold')
            ->update([
                'allowed_types' => json_encode($standardTypes),
                'restrict_types' => true,
                'updated_at' => now()
            ]);

        // 플래티넘 등급: 고급 타입 (세일즈, 마케팅, 컨설턴트)
        DB::table('partner_tiers')
            ->where('tier_code', 'platinum')
            ->update([
                'allowed_types' => json_encode($premiumTypes),
                'restrict_types' => true,
                'updated_at' => now()
            ]);

        // 다이아몬드 등급: 모든 타입 허용
        DB::table('partner_tiers')
            ->where('tier_code', 'DIAMOND')
            ->update([
                'allowed_types' => json_encode($allTypes),
                'restrict_types' => false, // 모든 타입 허용
                'updated_at' => now()
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_tiers');
    }
};