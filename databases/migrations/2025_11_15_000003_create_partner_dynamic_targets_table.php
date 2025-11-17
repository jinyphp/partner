<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 파트너 동적 목표 관리 테이블 생성
     *
     * =======================================================================
     * 🎯 테이블 개요
     * =======================================================================
     * 파트너별 개인 맞춤 목표 설정 및 성과 추적을 위한 스마트 목표 관리 시스템입니다.
     * 기간별, 카테고리별 유연한 목표 설정으로 파트너의 성장을 체계적으로 지원합니다.
     *
     * =======================================================================
     * 🎯 핵심 기능
     * =======================================================================
     * ✓ 개인별 맞춤 목표 설정 (매출, 고객, 활동 등)
     * ✓ 다기간 목표 관리 (월별, 분기별, 연별)
     * ✓ 실시간 진행률 추적 (달성률 자동 계산)
     * ✓ 목표 대비 실적 분석 및 리포팅
     * ✓ 목표 달성 시 자동 리워드 지급
     * ✓ 목표 수정 및 조정 이력 관리
     * ✓ 상위/하위 목표 연계 시스템
     * ✓ 목표별 가중치 및 우선순위 설정
     *
     * =======================================================================
     * 📅 목표 기간 타입
     * =======================================================================
     * • monthly: 월별 목표 (가장 일반적, 단기 집중)
     * • quarterly: 분기별 목표 (중기 전략 목표)
     * • yearly: 연별 목표 (장기 비전 목표)
     *
     * =======================================================================
     * 📊 목표 카테고리
     * =======================================================================
     * • sales_revenue: 매출액 목표
     * • customer_acquisition: 신규 고객 확보
     * • activity_count: 활동 건수 (상담, 미팅 등)
     * • team_building: 팀 구성 (하위 파트너 모집)
     * • skill_development: 역량 개발 (교육 이수 등)
     * • custom: 맞춤형 목표
     *
     * =======================================================================
     * 🏆 목표 상태
     * =======================================================================
     * • draft: 초안 (아직 활성화 안됨)
     * • active: 활성 (진행 중인 목표)
     * • completed: 완료 (목표 달성)
     * • failed: 실패 (기간 만료, 미달성)
     * • cancelled: 취소 (중도 포기)
     * • paused: 일시정지 (특별한 사유)
     *
     * =======================================================================
     * 💰 리워드 시스템
     * =======================================================================
     * • reward_type: 리워드 유형 (보너스, 포인트, 등급업 등)
     * • reward_amount: 리워드 금액
     * • achievement_bonus: 달성 보너스 지급 여부
     * • milestone_rewards: 단계별 중간 리워드
     *
     * =======================================================================
     * 📈 진행률 추적
     * =======================================================================
     * • current_progress: 현재 진행 상황
     * • progress_percentage: 달성률 (0-100%)
     * • last_updated_at: 마지막 업데이트 시간
     * • achievement_date: 목표 달성 일시
     *
     * =======================================================================
     * 🔗 테이블 관계
     * =======================================================================
     * • partner_users → partner_dynamic_targets (1:N) : 파트너별 목표
     * • partner_dynamic_targets → partner_performance_metrics (연계)
     *
     * =======================================================================
     * 📈 성능 최적화
     * =======================================================================
     * • 파트너별 기간 타입 복합 인덱스
     * • 활성 목표 빠른 조회 인덱스
     * • 목표 기간 범위 검색 최적화
     * • 달성률 기준 정렬 지원
     */
    public function up(): void
    {
        Schema::create('partner_dynamic_targets', function (Blueprint $table) {
            // 기본 필드
            $table->id();
            $table->timestamps();
            $table->softDeletes();

            // 대상 파트너 정보
            $table->foreignId('partner_user_id')->constrained('partner_users')->onDelete('cascade')
                ->comment('대상 파트너 ID');

            // 목표 기간 설정
            $table->enum('target_period_type', ['monthly', 'quarterly', 'yearly'])->default('monthly')
                ->comment('목표 기간 타입');

            $table->year('target_year')->comment('목표 연도');
            $table->tinyInteger('target_month')->nullable()->comment('목표 월 (1-12, monthly인 경우)');
            $table->tinyInteger('target_quarter')->nullable()->comment('목표 분기 (1-4, quarterly인 경우)');

            // 기본 목표 계산 정보
            $table->decimal('base_sales_target', 15, 2)->comment('기본 매출 목표 (타입 최소기준 × 등급승수)');
            $table->integer('base_cases_target')->default(0)->comment('기본 처리건수 목표');
            $table->decimal('base_revenue_target', 15, 2)->default(0)->comment('기본 수익 목표');
            $table->integer('base_clients_target')->default(0)->comment('기본 고객 관리 목표');

            // 조정 계수들
            $table->decimal('personal_adjustment_factor', 5, 2)->default(1.0)
                ->comment('개인별 조정 계수 (1.0=기본, 1.2=20% 증가)');

            $table->decimal('market_condition_factor', 5, 2)->default(1.0)
                ->comment('시장 상황 반영 계수 (1.5=성수기, 0.8=비수기)');

            $table->decimal('seasonal_adjustment_factor', 5, 2)->default(1.0)
                ->comment('계절성 조정 계수');

            $table->decimal('team_performance_factor', 5, 2)->default(1.0)
                ->comment('팀 성과 반영 계수 (리더인 경우)');

            // 최종 계산된 목표
            $table->decimal('final_sales_target', 15, 2)->comment('최종 매출 목표');
            $table->integer('final_cases_target')->default(0)->comment('최종 처리건수 목표');
            $table->decimal('final_revenue_target', 15, 2)->default(0)->comment('최종 수익 목표');
            $table->integer('final_clients_target')->default(0)->comment('최종 고객 관리 목표');

            // 추가 목표 지표
            $table->decimal('quality_score_target', 5, 2)->default(80.0)->comment('품질 점수 목표 (0-100)');
            $table->decimal('customer_satisfaction_target', 5, 2)->default(80.0)->comment('고객 만족도 목표 (%)');
            $table->decimal('response_time_target', 8, 2)->default(24.0)->comment('응답 시간 목표 (시간)');

            // 현재 성과 추적
            $table->decimal('current_sales_achievement', 15, 2)->default(0)->comment('현재 매출 달성액');
            $table->integer('current_cases_achievement')->default(0)->comment('현재 처리건수 달성');
            $table->decimal('current_revenue_achievement', 15, 2)->default(0)->comment('현재 수익 달성액');
            $table->integer('current_clients_achievement')->default(0)->comment('현재 고객 관리 달성');

            // 달성률 계산 (자동 계산)
            $table->decimal('sales_achievement_rate', 5, 2)->default(0)->comment('매출 달성률 (%)');
            $table->decimal('cases_achievement_rate', 5, 2)->default(0)->comment('건수 달성률 (%)');
            $table->decimal('overall_achievement_rate', 5, 2)->default(0)->comment('종합 달성률 (%)');

            // 보너스 및 인센티브 정보
            $table->json('bonus_tier_config')->nullable()->comment('달성률별 보너스 설정');
            // 예시: {"150": {"rate": 3.0, "description": "초과달성"}, "100": {"rate": 1.0, "description": "목표달성"}}

            $table->decimal('achieved_bonus_rate', 5, 2)->default(0)->comment('달성한 보너스율 (%)');
            $table->decimal('calculated_bonus_amount', 15, 2)->default(0)->comment('계산된 보너스 금액');

            // 특별 목표 및 도전 과제
            $table->json('special_objectives')->nullable()->comment('특별 목표 및 도전 과제');
            // 예시: {"new_client_acquisition": 5, "innovation_project": true, "mentoring_target": 2}

            $table->json('achievement_milestones')->nullable()->comment('달성 마일스톤');
            // 예시: [{"date": "2024-01-15", "milestone": "25%", "achieved": true}, ...]

            // 목표 설정 및 승인 정보
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'active', 'completed', 'cancelled'])
                ->default('draft')->comment('목표 상태');

            $table->text('setting_notes')->nullable()->comment('목표 설정 메모');
            $table->text('approval_notes')->nullable()->comment('승인 메모');
            $table->text('completion_notes')->nullable()->comment('완료 메모');

            // 승인 및 관리 정보
            $table->foreignId('created_by')->nullable()->constrained('users')->comment('목표 설정자');
            $table->foreignId('approved_by')->nullable()->constrained('users')->comment('목표 승인자');
            $table->timestamp('approved_at')->nullable()->comment('승인 일시');
            $table->timestamp('activated_at')->nullable()->comment('활성화 일시');
            $table->timestamp('completed_at')->nullable()->comment('완료 일시');

            // 자동 업데이트 설정
            $table->boolean('auto_calculate_enabled')->default(true)->comment('자동 계산 활성화');
            $table->timestamp('last_calculated_at')->nullable()->comment('마지막 계산 일시');
            $table->timestamp('next_review_date')->nullable()->comment('다음 검토 예정일');

            // 성능 최적화 인덱스
            $table->index(['partner_user_id', 'target_year', 'target_month'], 'idx_partner_period');
            $table->index(['target_period_type', 'status'], 'idx_period_status');
            $table->index(['status', 'activated_at'], 'idx_active_targets');
            $table->index(['target_year', 'target_quarter'], 'idx_year_quarter');
            $table->index(['overall_achievement_rate'], 'idx_achievement_rate');

            // 유니크 제약조건 (동일 기간 중복 목표 방지)
            $table->unique(['partner_user_id', 'target_period_type', 'target_year', 'target_month', 'target_quarter'],
                          'uk_partner_unique_period');
        });

        // 기본 설정을 위한 뷰 생성
        $this->createPerformanceViews();
    }

    /**
     * 성과 추적을 위한 뷰 생성
     */
    private function createPerformanceViews(): void
    {
        // 월별 성과 요약 뷰
        DB::statement("
            CREATE VIEW partner_monthly_performance AS
            SELECT
                pdt.partner_user_id,
                pu.name as partner_name,
                pt.type_name as partner_type,
                ptr.tier_name as partner_tier,
                pdt.target_year,
                pdt.target_month,
                pdt.final_sales_target,
                pdt.current_sales_achievement,
                pdt.sales_achievement_rate,
                pdt.overall_achievement_rate,
                pdt.achieved_bonus_rate,
                pdt.status,
                pdt.updated_at as last_update
            FROM partner_dynamic_targets pdt
            JOIN partner_users pu ON pdt.partner_user_id = pu.id
            LEFT JOIN partner_types pt ON pu.partner_type_id = pt.id
            LEFT JOIN partner_tiers ptr ON pu.partner_tier_id = ptr.id
            WHERE pdt.target_period_type = 'monthly'
              AND pdt.status IN ('active', 'completed')
        ");

        // 팀 성과 요약 뷰 (관리자용)
        DB::statement("
            CREATE VIEW partner_team_performance AS
            SELECT
                manager.id as manager_id,
                manager.name as manager_name,
                COUNT(team_member.id) as team_size,
                AVG(pdt.overall_achievement_rate) as avg_team_achievement,
                SUM(pdt.current_sales_achievement) as total_team_sales,
                SUM(pdt.calculated_bonus_amount) as total_team_bonus
            FROM partner_users manager
            JOIN partner_users team_member ON team_member.parent_id = manager.id
            JOIN partner_dynamic_targets pdt ON pdt.partner_user_id = team_member.id
            WHERE pdt.status IN ('active', 'completed')
              AND pdt.target_period_type = 'monthly'
            GROUP BY manager.id, manager.name
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 뷰 삭제
        DB::statement('DROP VIEW IF EXISTS partner_team_performance');
        DB::statement('DROP VIEW IF EXISTS partner_monthly_performance');

        // 테이블 삭제
        Schema::dropIfExists('partner_dynamic_targets');
    }
};