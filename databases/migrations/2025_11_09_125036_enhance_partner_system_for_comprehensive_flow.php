<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 파트너 시스템 종합 개선 - 플로우 완성을 위한 추가 테이블 및 기능 개선
     *
     * 추가되는 기능:
     * 1. 파트너 활동 로그 시스템
     * 2. 알림 시스템
     * 3. 면접 평가 상세 관리
     * 4. 승인 프로세스 추적
     * 5. 성과 메트릭 관리
     * 6. 교육 및 인증 관리
     */
    public function up(): void
    {
        // ====================================================================
        // 1. 파트너 활동 로그 테이블
        // ====================================================================
        Schema::create('partner_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            // 관련 엔티티
            $table->unsignedBigInteger('partner_id')->nullable()->comment('파트너 ID');
            $table->unsignedBigInteger('application_id')->nullable()->comment('신청서 ID');
            $table->unsignedBigInteger('user_id')->comment('작업 수행자 ID');
            $table->string('user_uuid')->nullable()->comment('작업 수행자 UUID');

            // 활동 정보
            $table->string('activity_type', 50)->comment('활동 유형');
            // 활동 유형: application_submitted, status_changed, interview_scheduled,
            // approved, rejected, reapplied, tier_changed, performance_updated

            $table->string('old_value', 500)->nullable()->comment('이전 값');
            $table->string('new_value', 500)->nullable()->comment('새로운 값');
            $table->json('metadata')->nullable()->comment('추가 메타데이터');

            // 추적 정보
            $table->ipAddress('ip_address')->nullable()->comment('IP 주소');
            $table->text('user_agent')->nullable()->comment('사용자 에이전트');
            $table->text('notes')->nullable()->comment('메모');

            // 인덱스
            $table->index(['partner_id', 'created_at']);
            $table->index(['application_id', 'created_at']);
            $table->index(['activity_type', 'created_at']);
            $table->index(['user_uuid', 'created_at']);
        });

        // ====================================================================
        // 2. 파트너 알림 시스템
        // ====================================================================
        Schema::create('partner_notifications', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            // 수신자 정보
            $table->unsignedBigInteger('user_id')->comment('수신자 사용자 ID');
            $table->string('user_uuid')->nullable()->comment('수신자 UUID');

            // 알림 정보
            $table->string('title', 200)->comment('알림 제목');
            $table->text('message')->comment('알림 내용');
            $table->string('type', 50)->comment('알림 유형');
            // 알림 유형: status_update, interview_scheduled, approved, rejected,
            // reapply_available, tier_upgraded, performance_alert

            $table->string('priority', 20)->default('normal')->comment('우선순위');
            // 우선순위: low, normal, high, urgent

            // 관련 데이터
            $table->json('data')->nullable()->comment('관련 데이터');
            $table->string('action_url', 500)->nullable()->comment('액션 URL');

            // 상태 관리
            $table->boolean('is_read')->default(false)->comment('읽음 여부');
            $table->timestamp('read_at')->nullable()->comment('읽은 시간');
            $table->timestamp('expires_at')->nullable()->comment('만료 시간');

            // 전송 채널
            $table->json('channels')->default('[]')->comment('전송 채널');
            // 채널: ['web', 'email', 'sms', 'push']

            $table->json('delivery_status')->nullable()->comment('전송 상태');
            // {
            //   "web": {"status": "delivered", "delivered_at": "2025-11-09 10:00:00"},
            //   "email": {"status": "sent", "sent_at": "2025-11-09 10:01:00"}
            // }

            // 인덱스
            $table->index(['user_uuid', 'is_read', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index(['priority', 'is_read']);
        });

        // ====================================================================
        // 3. 면접 평가 상세 관리
        // ====================================================================
        Schema::create('partner_interview_evaluations', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            // 관련 정보
            $table->unsignedBigInteger('application_id')->comment('신청서 ID');
            $table->unsignedBigInteger('interviewer_id')->comment('면접관 ID');
            $table->string('interviewer_uuid')->nullable()->comment('면접관 UUID');

            // 면접 정보
            $table->timestamp('interview_date')->comment('면접 일시');
            $table->integer('duration_minutes')->nullable()->comment('면접 소요 시간(분)');
            $table->string('interview_type', 50)->default('video')->comment('면접 유형');
            // 면접 유형: video, phone, in_person, online_test

            // 평가 점수 (1-100점)
            $table->integer('technical_skills')->nullable()->comment('기술 역량');
            $table->integer('communication')->nullable()->comment('의사소통');
            $table->integer('motivation')->nullable()->comment('동기 및 열정');
            $table->integer('experience_relevance')->nullable()->comment('경력 연관성');
            $table->integer('cultural_fit')->nullable()->comment('조직 적합성');
            $table->integer('problem_solving')->nullable()->comment('문제 해결 능력');
            $table->integer('leadership_potential')->nullable()->comment('리더십 잠재력');

            // 종합 평가
            $table->integer('overall_rating')->nullable()->comment('종합 점수');
            $table->enum('recommendation', ['strongly_approve', 'approve', 'conditional', 'reject', 'strongly_reject'])
                  ->comment('최종 추천');

            // 상세 피드백
            $table->json('strengths')->nullable()->comment('강점들');
            $table->json('weaknesses')->nullable()->comment('약점들');
            $table->json('concerns')->nullable()->comment('우려사항들');
            $table->json('action_items')->nullable()->comment('개선 필요 사항들');
            $table->text('detailed_feedback')->nullable()->comment('상세 피드백');

            // 추가 정보
            $table->json('interview_notes')->nullable()->comment('면접 노트');
            $table->json('attachments')->nullable()->comment('첨부 파일들');

            // 외래키 및 인덱스
            $table->foreign('application_id')->references('id')->on('partner_applications')->onDelete('cascade');
            $table->index(['application_id', 'interview_date']);
            $table->index(['interviewer_uuid', 'interview_date']);
            $table->index(['recommendation', 'overall_rating']);
        });

        // ====================================================================
        // 4. 승인 프로세스 추적
        // ====================================================================
        Schema::create('partner_approval_processes', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            // 관련 정보
            $table->unsignedBigInteger('application_id')->comment('신청서 ID');
            $table->unsignedBigInteger('processor_id')->comment('처리자 ID');
            $table->string('processor_uuid')->nullable()->comment('처리자 UUID');

            // 프로세스 정보
            $table->enum('process_type', ['review', 'interview', 'approval', 'rejection', 'reapplication'])
                  ->comment('프로세스 유형');
            $table->enum('current_step', ['pending', 'in_progress', 'completed', 'skipped'])
                  ->default('pending')
                  ->comment('현재 단계');

            // 처리 정보
            $table->timestamp('started_at')->nullable()->comment('시작 시간');
            $table->timestamp('completed_at')->nullable()->comment('완료 시간');
            $table->integer('estimated_duration_hours')->nullable()->comment('예상 소요 시간');
            $table->integer('actual_duration_hours')->nullable()->comment('실제 소요 시간');

            // 결과 정보
            $table->enum('result', ['approved', 'rejected', 'requires_interview', 'requires_revision', 'escalated'])
                  ->nullable()
                  ->comment('처리 결과');

            $table->json('checklist')->nullable()->comment('체크리스트');
            // {
            //   "documents_verified": true,
            //   "background_check": false,
            //   "references_contacted": true,
            //   "technical_assessment": null
            // }

            $table->text('decision_rationale')->nullable()->comment('결정 근거');
            $table->json('required_actions')->nullable()->comment('필요 조치사항');

            // 다음 단계 정보
            $table->unsignedBigInteger('next_processor_id')->nullable()->comment('다음 처리자 ID');
            $table->timestamp('next_due_date')->nullable()->comment('다음 처리 예정일');

            // 외래키 및 인덱스
            $table->foreign('application_id')->references('id')->on('partner_applications')->onDelete('cascade');
            $table->index(['application_id', 'process_type']);
            $table->index(['processor_uuid', 'current_step']);
            $table->index(['current_step', 'started_at']);
        });

        // ====================================================================
        // 5. 파트너 성과 메트릭
        // ====================================================================
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

        // ====================================================================
        // 6. 교육 및 인증 관리
        // ====================================================================
        Schema::create('partner_trainings', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            // 기본 정보
            $table->string('training_code', 50)->unique()->comment('교육 코드');
            $table->string('title', 200)->comment('교육 제목');
            $table->text('description')->nullable()->comment('교육 설명');

            // 교육 설정
            $table->enum('training_type', ['onboarding', 'skill_development', 'compliance', 'certification'])
                  ->comment('교육 유형');
            $table->enum('delivery_method', ['online', 'in_person', 'hybrid', 'self_study'])
                  ->comment('교육 방식');
            $table->integer('duration_hours')->comment('교육 시간');
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced'])
                  ->comment('난이도');

            // 대상 및 요구사항
            $table->json('target_tiers')->comment('대상 등급들');
            $table->json('target_types')->comment('대상 타입들');
            $table->boolean('is_mandatory')->default(false)->comment('필수 여부');
            $table->json('prerequisites')->nullable()->comment('선수 조건들');

            // 내용 및 자료
            $table->json('curriculum')->nullable()->comment('커리큘럼');
            $table->json('materials')->nullable()->comment('교육 자료들');
            $table->json('assessment_criteria')->nullable()->comment('평가 기준');
            $table->integer('passing_score')->nullable()->comment('합격 점수');

            // 일정 및 상태
            $table->boolean('is_active')->default(true)->comment('활성 상태');
            $table->timestamp('starts_at')->nullable()->comment('시작 시간');
            $table->timestamp('ends_at')->nullable()->comment('종료 시간');
            $table->integer('max_participants')->nullable()->comment('최대 참가자 수');

            // 강사 정보
            $table->unsignedBigInteger('instructor_id')->nullable()->comment('강사 ID');
            $table->json('instructor_info')->nullable()->comment('강사 정보');

            // 인덱스
            $table->index(['training_type', 'is_active']);
            $table->index(['is_mandatory', 'is_active']);
            $table->index(['starts_at', 'ends_at']);
        });

        Schema::create('partner_training_enrollments', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            // 관련 정보
            $table->unsignedBigInteger('training_id')->comment('교육 ID');
            $table->unsignedBigInteger('partner_id')->comment('파트너 ID');

            // 등록 정보
            $table->timestamp('enrolled_at')->comment('등록 시간');
            $table->timestamp('started_at')->nullable()->comment('시작 시간');
            $table->timestamp('completed_at')->nullable()->comment('완료 시간');
            $table->timestamp('expires_at')->nullable()->comment('만료 시간');

            // 진행 상황
            $table->enum('status', ['enrolled', 'in_progress', 'completed', 'failed', 'expired', 'cancelled'])
                  ->default('enrolled')
                  ->comment('진행 상태');
            $table->decimal('progress_percentage', 5, 2)->default(0)->comment('진행률 (%)');

            // 평가 결과
            $table->integer('final_score')->nullable()->comment('최종 점수');
            $table->boolean('passed')->nullable()->comment('합격 여부');
            $table->integer('attempts_count')->default(0)->comment('시도 횟수');
            $table->json('attempt_scores')->nullable()->comment('시도별 점수들');

            // 추가 정보
            $table->text('notes')->nullable()->comment('메모');
            $table->json('completion_certificate')->nullable()->comment('수료증 정보');

            // 외래키 및 인덱스
            $table->foreign('training_id')->references('id')->on('partner_trainings')->onDelete('cascade');
            $table->foreign('partner_id')->references('id')->on('partner_users')->onDelete('cascade');
            $table->unique(['training_id', 'partner_id']);
            $table->index(['partner_id', 'status']);
            $table->index(['status', 'completed_at']);
        });

        // ====================================================================
        // 7. 기존 테이블 개선
        // ====================================================================

        // partner_applications 테이블에 추가 필드
        Schema::table('partner_applications', function (Blueprint $table) {
            // 처리 담당자 정보
            $table->unsignedBigInteger('assigned_reviewer_id')->nullable()->comment('배정된 검토자 ID');
            $table->timestamp('assigned_at')->nullable()->comment('배정 시간');
            $table->timestamp('review_started_at')->nullable()->comment('검토 시작 시간');
            $table->timestamp('review_deadline')->nullable()->comment('검토 마감일');

            // 우선순위 및 태그
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal')->comment('우선순위');
            $table->json('tags')->nullable()->comment('태그들');

            // 외부 시스템 연동
            $table->string('external_application_id')->nullable()->comment('외부 시스템 신청서 ID');
            $table->json('external_data')->nullable()->comment('외부 시스템 데이터');

            // 추가 인덱스
            $table->index(['assigned_reviewer_id', 'application_status']);
            $table->index(['priority', 'created_at']);
            $table->index(['review_deadline']);
        });

        // partner_users 테이블에 성과 관련 필드 추가
        Schema::table('partner_users', function (Blueprint $table) {
            // 현재 성과 지표
            $table->decimal('current_month_sales', 15, 2)->default(0)->comment('이번 달 매출');
            $table->integer('current_month_deals')->default(0)->comment('이번 달 거래 수');
            $table->decimal('ytd_sales', 15, 2)->default(0)->comment('연간 누적 매출');
            $table->integer('ytd_deals')->default(0)->comment('연간 누적 거래 수');

            // 성과 등급
            $table->enum('performance_grade', ['A+', 'A', 'B+', 'B', 'C', 'D'])
                  ->nullable()
                  ->comment('성과 등급');
            $table->timestamp('performance_updated_at')->nullable()->comment('성과 업데이트 시간');

            // 교육 상태
            $table->integer('completed_trainings')->default(0)->comment('완료한 교육 수');
            $table->integer('required_trainings')->default(0)->comment('필수 교육 수');
            $table->decimal('training_completion_rate', 5, 2)->default(0)->comment('교육 완료율 (%)');

            // 추가 인덱스
            $table->index(['performance_grade', 'partner_tier_id']);
            $table->index(['current_month_sales']);
            $table->index(['training_completion_rate']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 테이블 삭제 (역순)
        Schema::dropIfExists('partner_training_enrollments');
        Schema::dropIfExists('partner_trainings');
        Schema::dropIfExists('partner_performance_metrics');
        Schema::dropIfExists('partner_approval_processes');
        Schema::dropIfExists('partner_interview_evaluations');
        Schema::dropIfExists('partner_notifications');
        Schema::dropIfExists('partner_activity_logs');

        // 기존 테이블 컬럼 제거
        Schema::table('partner_users', function (Blueprint $table) {
            $table->dropColumn([
                'current_month_sales', 'current_month_deals', 'ytd_sales', 'ytd_deals',
                'performance_grade', 'performance_updated_at',
                'completed_trainings', 'required_trainings', 'training_completion_rate'
            ]);
        });

        Schema::table('partner_applications', function (Blueprint $table) {
            $table->dropColumn([
                'assigned_reviewer_id', 'assigned_at', 'review_started_at', 'review_deadline',
                'priority', 'tags', 'external_application_id', 'external_data'
            ]);
        });
    }
};