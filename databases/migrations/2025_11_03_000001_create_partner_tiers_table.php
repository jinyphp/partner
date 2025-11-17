<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * =======================================================================
     * 🏆 파트너 등급(티어) 시스템 테이블 생성 (리팩터링 버전 v2.0)
     * =======================================================================
     *
     * 📖 시스템 개요
     * -----------------------------------------------------------------------
     * 파트너의 성과와 경험에 따른 6단계 등급 분류 및 관리 시스템입니다.
     * Starter → Bronze → Silver → Gold → Platinum → Diamond 단계별 승급 체계로 구성되며,
     * 각 등급별로 수수료율(3%~10%), 우선순위, 혜택, 비용이 세분화되어 적용됩니다.
     *
     * 🎯 핵심 기능
     * -----------------------------------------------------------------------
     * ✓ 6단계 등급 시스템 (Starter/Bronze/Silver/Gold/Platinum/Diamond)
     * ✓ 등급별 차등 수수료 체계 (3% → 5% → 6% → 7% → 8% → 10%)
     * ✓ 등급별 가입비 및 월/연 유지비 관리
     * ✓ 파트너 타입과의 연동 시스템 (제한/허용 설정)
     * ✓ 요구사항 및 혜택의 JSON 기반 유연한 관리
     * ✓ 우선순위 기반 업무 배정 시스템
     *
     * 💰 등급별 수수료 구조 (6단계 세분화)
     * -----------------------------------------------------------------------
     * • Starter (스타터)  : 3% 수수료 + 무료 (신규 입문 단계)
     * • Bronze (브론즈)   : 5% 수수료 + 무료 (기초 단계)
     * • Silver (실버)     : 6% 수수료 + 50,000원 가입비 + 30,000원/월
     * • Gold (골드)       : 7% 수수료 + 100,000원 가입비 + 50,000원/월
     * • Platinum (플래)   : 8% 수수료 + 200,000원 가입비 + 100,000원/월
     * • Diamond (다이아)  : 10% 수수료 + 500,000원 가입비 + 200,000원/월
     *
     * 🔗 파트너 타입 연동 (6단계)
     * -----------------------------------------------------------------------
     * • Starter  : 기술지원 타입만 허용 (제한된 업무)
     * • Bronze   : 기본 타입 허용 (기술지원 + 고객서비스)
     * • Silver   : SALES (세일즈) 타입 연동 (영업 기초)
     * • Gold     : MARKETING (마케팅) 타입 연동 (마케팅 전문)
     * • Platinum : CONSULTANT (컨설턴트) 타입 연동 (컨설팅 전문)
     * • Diamond  : TRAINING (교육) 타입 연동 (교육 전문가)
     *
     * 📊 데이터베이스 구조
     * -----------------------------------------------------------------------
     * • 기본 정보: tier_code, tier_name, description
     * • 수수료 시스템: commission_type, commission_rate/amount
     * • 비용 관리: registration_fee, monthly_fee, annual_fee
     * • 타입 연동: parent_partner_type_id, restrict_to_parent_type
     * • 요구사항: requirements (JSON)
     * • 혜택 정보: benefits (JSON)
     *
     * 🔄 업그레이드 내역
     * -----------------------------------------------------------------------
     * v1.0: 기본 등급 시스템
     * v2.0: 파트너 타입 연동, 비용 시스템, 리팩터링
     */
    public function up(): void
    {
        Schema::create('partner_tiers', function (Blueprint $table) {
            // =============================================================
            // 🆔 시스템 기본 필드
            // =============================================================
            $table->id()->comment('등급 고유 식별자 (Primary Key)');
            $table->timestamps();
            $table->softDeletes();

            // =============================================================
            // 🏷️ 등급 기본 정보
            // =============================================================
            $table->string('tier_code', 20)
                  ->unique()
                  ->comment('등급 고유 코드 (bronze, silver, gold, platinum)');

            $table->string('tier_name', 100)
                  ->comment('등급 표시명 (브론즈 파트너, 실버 파트너 등)');

            $table->text('description')
                  ->nullable()
                  ->comment('등급 상세 설명 및 특징');

            // =============================================================
            // 💰 수수료 시스템 (단순화)
            // =============================================================
            $table->enum('commission_type', ['percentage', 'fixed_amount'])
                  ->default('percentage')
                  ->comment('수수료 산정 방식: percentage(비율), fixed_amount(고정금액)');

            $table->decimal('commission_rate', 5, 2)
                  ->nullable()
                  ->comment('수수료율 (%) - percentage 방식일 때 사용 (예: 65.00 = 65%)');

            $table->decimal('commission_amount', 12, 2)
                  ->nullable()
                  ->comment('고정 수수료 금액 (원) - fixed_amount 방식일 때 사용');

            // =============================================================
            // 🎯 우선순위 시스템
            // =============================================================
            $table->integer('priority_level')
                  ->comment('업무 배정 우선순위 (1=최고, 숫자가 낮을수록 높은 우선순위)');

            // =============================================================
            // 💳 등급별 비용 관리 시스템
            // =============================================================
            $table->decimal('registration_fee', 12, 2)
                  ->default(0)
                  ->comment('등급 가입비 (원) - 등급 획득 시 일회성 비용');

            $table->decimal('monthly_fee', 12, 2)
                  ->default(0)
                  ->comment('월별 유지비 (원) - 매월 청구되는 등급 유지 비용');

            $table->decimal('annual_fee', 12, 2)
                  ->default(0)
                  ->comment('연간 유지비 (원) - 매년 청구되는 등급 유지 비용');

            $table->boolean('fee_waiver_available')
                  ->default(false)
                  ->comment('비용 면제 가능 여부 (성과 우수자 대상 면제 정책)');

            $table->text('fee_structure_notes')
                  ->nullable()
                  ->comment('비용 구조 관련 특별 조건 및 면제 정책 설명');

            // =============================================================
            // 📋 요구사항 및 혜택 관리 (JSON 구조)
            // =============================================================
            $table->json('requirements');
            /*
             * requirements JSON 구조 예시:
             * {
             *   "min_experience_months": 12,           // 최소 경력 (개월)
             *   "min_completed_jobs": 150,             // 최소 완료 업무 수
             *   "min_rating": 4.5,                     // 최소 평점
             *   "required_certifications": [           // 필수 자격증
             *     "기본 자격증", "전문 자격증"
             *   ],
             *   "leadership_experience": true,         // 리더십 경험 필요 여부
             *   "customer_complaints": ["< 5회/월"]     // 고객 불만 허용 기준
             * }
             */

            $table->json('benefits');
            /*
             * benefits JSON 구조 예시:
             * {
             *   "job_assignment_priority": "high",     // 업무 배정 우선순위
             *   "maximum_concurrent_jobs": 6,          // 동시 진행 가능 업무 수
             *   "support_response_time": "6시간",      // 지원팀 응답 시간
             *   "training_access": [                   // 교육 접근 권한
             *     "모든 교육 과정"
             *   ],
             *   "bonus_eligibility": true,             // 보너스 지급 대상 여부
             *   "performance_bonus_rate": 10,          // 성과급 비율 (%)
             *   "premium_projects_access": true,       // 프리미엄 프로젝트 접근
             *   "flexible_schedule": true              // 유연 근무 허용
             * }
             */

            // =============================================================
            // ⚙️ 시스템 관리 및 설정
            // =============================================================
            $table->boolean('is_active')
                  ->default(true)
                  ->comment('등급 활성 상태 (false일 경우 신규 승급 불가)');

            $table->integer('sort_order')
                  ->default(0)
                  ->comment('화면 표시 정렬 순서 (낮은 숫자 우선)');

            // =============================================================
            // 📈 성능 최적화 인덱스
            // =============================================================
            $table->index(['is_active', 'priority_level'], 'idx_tier_active_priority');
            $table->index(['tier_code'], 'idx_tier_code');
            $table->index(['commission_type'], 'idx_tier_commission_type');
            $table->index(['priority_level'], 'idx_tier_priority');
            $table->index(['sort_order'], 'idx_tier_sort_order');

        });

        // 기본 등급 데이터 삽입 (Starter/Bronze/Silver/Gold/Platinum/Diamond)
        $this->insertDefaultTierData();
    }

    /**
     * 기본 등급 데이터 삽입
     */
    private function insertDefaultTierData(): void
    {
        $tiers = $this->getDefaultTiers();

        try {
            DB::beginTransaction();
            DB::table('partner_tiers')->insert($tiers);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('파트너 등급 기본 데이터 삽입 실패: ' . $e->getMessage());
        }
    }

    /**
     * 기본 등급 구성 데이터
     */
    private function getDefaultTiers(): array
    {
        $now = now();

        return [
            [
                'tier_code' => 'starter',
                'tier_name' => '스타터 파트너',
                'description' => '신규 파트너를 위한 입문 등급',
                'commission_type' => 'percentage',
                'commission_rate' => 3.00,
                'priority_level' => 6,
                'requirements' => json_encode(['onboarding_completed' => true]),
                'benefits' => json_encode(['maximum_concurrent_jobs' => 1, 'support_response_time' => '48시간']),
                'is_active' => true,
                'sort_order' => 1,
                'registration_fee' => 0,
                'monthly_fee' => 0,
                'annual_fee' => 0,
                'fee_waiver_available' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'tier_code' => 'bronze',
                'tier_name' => '브론즈 파트너',
                'description' => '기초 경험을 쌓은 파트너를 위한 기본 등급',
                'commission_type' => 'percentage',
                'commission_rate' => 5.00,
                'priority_level' => 5,
                'requirements' => json_encode(['min_experience_months' => 3, 'min_completed_jobs' => 10]),
                'benefits' => json_encode(['maximum_concurrent_jobs' => 2, 'support_response_time' => '24시간']),
                'is_active' => true,
                'sort_order' => 2,
                'registration_fee' => 0,
                'monthly_fee' => 0,
                'annual_fee' => 0,
                'fee_waiver_available' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'tier_code' => 'silver',
                'tier_name' => '실버 파트너',
                'description' => '중급 파트너를 위한 등급',
                'commission_type' => 'percentage',
                'commission_rate' => 6.00,
                'priority_level' => 4,
                'requirements' => json_encode(['min_experience_months' => 6, 'min_completed_jobs' => 50, 'min_rating' => 4.0]),
                'benefits' => json_encode(['maximum_concurrent_jobs' => 4, 'support_response_time' => '12시간', 'bonus_eligibility' => true]),
                'is_active' => true,
                'sort_order' => 3,
                'registration_fee' => 50000,
                'monthly_fee' => 30000,
                'annual_fee' => 300000,
                'fee_waiver_available' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'tier_code' => 'gold',
                'tier_name' => '골드 파트너',
                'description' => '고급 파트너를 위한 프리미엄 등급',
                'commission_type' => 'percentage',
                'commission_rate' => 7.00,
                'priority_level' => 3,
                'requirements' => json_encode(['min_experience_months' => 12, 'min_completed_jobs' => 150, 'min_rating' => 4.5, 'leadership_experience' => true]),
                'benefits' => json_encode(['maximum_concurrent_jobs' => 6, 'support_response_time' => '6시간', 'premium_projects_access' => true]),
                'is_active' => true,
                'sort_order' => 4,
                'registration_fee' => 100000,
                'monthly_fee' => 50000,
                'annual_fee' => 500000,
                'fee_waiver_available' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'tier_code' => 'platinum',
                'tier_name' => '플래티넘 파트너',
                'description' => 'VIP 파트너를 위한 프리미엄 등급',
                'commission_type' => 'percentage',
                'commission_rate' => 8.00,
                'priority_level' => 2,
                'requirements' => json_encode(['min_experience_months' => 24, 'min_completed_jobs' => 300, 'min_rating' => 4.8]),
                'benefits' => json_encode(['maximum_concurrent_jobs' => 10, 'support_response_time' => '즉시', 'vip_customer_access' => true]),
                'is_active' => true,
                'sort_order' => 5,
                'registration_fee' => 200000,
                'monthly_fee' => 100000,
                'annual_fee' => 1000000,
                'fee_waiver_available' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'tier_code' => 'diamond',
                'tier_name' => '다이아몬드 파트너',
                'description' => '최상위 엘리트 파트너를 위한 최고급 등급',
                'commission_type' => 'percentage',
                'commission_rate' => 10.00,
                'priority_level' => 1,
                'requirements' => json_encode(['min_experience_months' => 36, 'min_completed_jobs' => 500, 'min_rating' => 4.9, 'expert_specialization' => true]),
                'benefits' => json_encode(['maximum_concurrent_jobs' => 15, 'support_response_time' => '즉시', 'exclusive_projects_access' => true, 'strategic_partnership' => true]),
                'is_active' => true,
                'sort_order' => 6,
                'registration_fee' => 500000,
                'monthly_fee' => 200000,
                'annual_fee' => 2000000,
                'fee_waiver_available' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
    }



    /**
     * =======================================================================
     * 🗑️ 테이블 삭제 및 정리
     * =======================================================================
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_tiers');
    }
};