<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 파트너 성과 메트릭 테이블 생성
     *
     * =======================================================================
     * 📊 테이블 개요
     * =======================================================================
     * 파트너의 성과를 다면적으로 측정하고 분석하는 종합 성과 관리 시스템입니다.
     * 매출, 활동, 품질, 네트워크 등 4대 영역의 정량적 지표를 시계열로 관리합니다.
     *
     * =======================================================================
     * 🎯 핵심 기능
     * =======================================================================
     * ✓ 4대 성과 영역 종합 측정 (매출, 활동, 품질, 네트워크)
     * ✓ 기간별 성과 추이 분석 (주간, 월간, 분기, 연간)
     * ✓ 파트너별 성과 순위 및 벤치마킹
     * ✓ 목표 대비 실적 달성률 추적
     * ✓ 성장률 및 효율성 지표 자동 계산
     * ✓ 성과 기반 등급 승급 기준 제공
     * ✓ 상세 메트릭 데이터 유연한 확장 지원
     *
     * =======================================================================
     * 💰 매출 메트릭
     * =======================================================================
     * • total_sales: 총 매출액
     * • commission_earned: 수수료 수익
     * • deals_closed: 성사된 거래 수
     * • average_deal_size: 평균 거래 규모
     *
     * =======================================================================
     * 🚀 활동 메트릭
     * =======================================================================
     * • leads_generated: 생성된 리드 수
     * • customers_acquired: 신규 고객 확보 수
     * • support_tickets_resolved: 지원 티켓 해결 수
     * • training_sessions_conducted: 교육 세션 진행 수
     *
     * =======================================================================
     * ⭐ 품질 메트릭
     * =======================================================================
     * • customer_satisfaction_score: 고객 만족도 점수
     * • response_time_hours: 평균 응답 시간
     * • complaints_received: 접수된 불만 건수
     * • task_completion_rate: 작업 완료율
     *
     * =======================================================================
     * 🌐 네트워크 메트릭
     * =======================================================================
     * • referrals_made: 추천한 파트너 수
     * • team_members_managed: 관리 팀원 수
     * • team_performance_bonus: 팀 성과 보너스
     *
     * =======================================================================
     * 📈 계산된 지표
     * =======================================================================
     * • efficiency_score: 효율성 점수 (매출/활동 비율)
     * • growth_rate: 전년 동기 대비 성장률
     * • rank_in_tier: 동일 등급 내 순위
     *
     * =======================================================================
     * 🔗 테이블 관계
     * =======================================================================
     * • partner_users → partner_performance_metrics (1:N) : 파트너별 성과 이력
     *
     * =======================================================================
     * 📈 성능 최적화
     * =======================================================================
     * • 파트너별 기간 복합 인덱스
     * • 기간 유형별 조회 최적화
     * • 매출액 기준 정렬 인덱스
     * • 중복 방지 유니크 제약조건
     */
    public function up(): void
    {
        Schema::create('partner_performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            // 파트너 정보
            $table->unsignedBigInteger('partner_id')->comment('파트너 ID');

            // 기간 정보
            $table->date('period_start')->comment('측정 시작일');
            $table->date('period_end')->comment('측정 종료일');
            $table->enum('period_type', ['weekly', 'monthly', 'quarterly', 'yearly'])
                  ->comment('기간 유형');

            // 매출 메트릭
            $table->decimal('total_sales', 15, 2)->default(0)->comment('총 매출');
            $table->decimal('commission_earned', 15, 2)->default(0)->comment('수수료 수익');
            $table->integer('deals_closed')->default(0)->comment('성사된 거래 수');
            $table->decimal('average_deal_size', 15, 2)->default(0)->comment('평균 거래 규모');

            // 활동 메트릭
            $table->integer('leads_generated')->default(0)->comment('생성된 리드 수');
            $table->integer('customers_acquired')->default(0)->comment('신규 고객 수');
            $table->integer('support_tickets_resolved')->default(0)->comment('해결된 지원 티켓 수');
            $table->integer('training_sessions_conducted')->default(0)->comment('진행한 교육 세션 수');

            // 품질 메트릭
            $table->decimal('customer_satisfaction_score', 3, 2)->nullable()->comment('고객 만족도 점수');
            $table->decimal('response_time_hours', 8, 2)->nullable()->comment('평균 응답 시간');
            $table->integer('complaints_received')->default(0)->comment('접수된 불만 수');
            $table->decimal('task_completion_rate', 5, 2)->default(0)->comment('작업 완료율 (%)');

            // 네트워크 메트릭
            $table->integer('referrals_made')->default(0)->comment('추천한 파트너 수');
            $table->integer('team_members_managed')->default(0)->comment('관리하는 팀원 수');
            $table->decimal('team_performance_bonus', 15, 2)->default(0)->comment('팀 성과 보너스');

            // 계산된 메트릭
            $table->decimal('efficiency_score', 5, 2)->nullable()->comment('효율성 점수');
            $table->decimal('growth_rate', 5, 2)->nullable()->comment('성장률 (%)');
            $table->integer('rank_in_tier')->nullable()->comment('등급 내 순위');

            // 추가 데이터
            $table->json('detailed_metrics')->nullable()->comment('상세 메트릭 데이터');
            $table->json('goals_vs_actual')->nullable()->comment('목표 대비 실적');

            // 외래키 및 인덱스
            $table->foreign('partner_id')->references('id')->on('partner_users')->onDelete('cascade');
            $table->index(['partner_id', 'period_start', 'period_end']);
            $table->index(['period_type', 'period_start']);
            $table->index(['total_sales', 'period_start']);
            $table->unique(['partner_id', 'period_start', 'period_end', 'period_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_performance_metrics');
    }
};