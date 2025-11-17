<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 파트너 면접 관리 테이블 생성
     *
     * =======================================================================
     * 🎭 테이블 개요
     * =======================================================================
     * 파트너 지원자의 면접 일정, 진행 상황, 결과를 통합 관리하는 시스템입니다.
     * 면접 예약부터 결과 처리까지 전체 면접 프로세스를 체계적으로 지원합니다.
     *
     * =======================================================================
     * 🎯 핵심 기능
     * =======================================================================
     * ✓ 면접 일정 예약 및 관리
     * ✓ 다양한 면접 방식 지원 (화상, 전화, 대면, 온라인테스트)
     * ✓ 면접관 배정 및 관리
     * ✓ 면접 결과 및 점수 기록
     * ✓ 면접 피드백 및 메모 관리
     * ✓ 추천 파트너 연계 추적
     * ✓ 샤딩 환경 지원으로 대용량 처리
     * ✓ 면접 상태별 진행 관리
     *
     * =======================================================================
     * 📅 면접 상태
     * =======================================================================
     * • scheduled: 예정됨 (면접 일정이 잡힘)
     * • confirmed: 확정됨 (지원자가 참석 확인)
     * • in_progress: 진행 중 (면접이 진행 중)
     * • completed: 완료됨 (면접 종료)
     * • cancelled: 취소됨 (일정 취소)
     * • no_show: 불참 (지원자 미참석)
     * • rescheduled: 재예약됨 (일정 변경)
     *
     * =======================================================================
     * 🎥 면접 방식
     * =======================================================================
     * • video: 화상 면접 (줌, 구글 미트 등)
     * • phone: 전화 면접 (음성 통화)
     * • in_person: 대면 면접 (사무실 방문)
     * • online_test: 온라인 테스트 (기술 평가)
     * • hybrid: 하이브리드 (복합 방식)
     *
     * =======================================================================
     * ⭐ 면접 결과
     * =======================================================================
     * • passed: 합격 (다음 단계 진행)
     * • failed: 불합격 (탈락)
     * • conditional: 조건부 합격 (추가 검토 필요)
     * • pending: 결과 대기 (아직 평가 안됨)
     * • requires_reinterview: 재면접 필요
     *
     * =======================================================================
     * 👥 관련 인원 관리
     * =======================================================================
     * • user_*: 지원자 정보 (샤딩 지원)
     * • application_id: 연결된 지원서
     * • referrer_partner_id: 추천 파트너
     * • interviewer_id: 담당 면접관
     * • backup_interviewer_id: 백업 면접관
     *
     * =======================================================================
     * 🔗 테이블 관계
     * =======================================================================
     * • partner_applications → partner_interviews (1:N) : 지원서별 면접
     * • partner_users → partner_interviews (1:N) : 추천 파트너 추적
     * • users → partner_interviews (1:N) : 지원자 및 면접관 정보
     * • partner_interviews → partner_interview_evaluations (1:N) : 면접 평가
     *
     * =======================================================================
     * 📈 성능 최적화
     * =======================================================================
     * • 지원서별 면접 상태 복합 인덱스
     * • 면접 일시 기준 정렬 인덱스
     * • 면접관별 담당 면접 조회
     * • 추천 파트너별 면접 추적
     * • 샤딩 지원을 위한 사용자 정보 인덱스
     */
    public function up(): void
    {
        Schema::create('partner_interviews', function (Blueprint $table) {
            $table->id();

            // 지원자 정보 (샤딩 지원)
            $table->unsignedBigInteger('user_id');
            $table->string('user_uuid', 36)->nullable();
            $table->unsignedTinyInteger('shard_number')->default(0);
            $table->string('user_table', 50)->default('users');
            $table->string('email', 100);
            $table->string('name', 100);

            // 신청서 정보
            $table->unsignedBigInteger('application_id');
            $table->foreign('application_id')->references('id')->on('partner_applications')->onDelete('cascade');

            // 추천 파트너 정보
            $table->unsignedBigInteger('referrer_partner_id')->nullable();
            $table->foreign('referrer_partner_id')->references('id')->on('partner_users')->onDelete('set null');
            $table->string('referrer_code', 20)->nullable();
            $table->string('referrer_name', 100)->nullable();

            // 면접 기본 정보
            $table->enum('interview_status', [
                'scheduled',     // 예정
                'in_progress',   // 진행중
                'completed',     // 완료
                'cancelled',     // 취소
                'rescheduled',   // 재일정
                'no_show'        // 불참
            ])->default('scheduled');

            $table->enum('interview_type', [
                'phone',         // 전화면접
                'video',         // 화상면접
                'in_person',     // 대면면접
                'written'        // 서면면접
            ])->default('video');

            $table->enum('interview_round', [
                'first',         // 1차면접
                'second',        // 2차면접
                'final'          // 최종면접
            ])->default('first');

            // 면접 일정
            $table->datetime('scheduled_at')->nullable();
            $table->datetime('started_at')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->integer('duration_minutes')->nullable();

            // 면접관 정보
            $table->unsignedBigInteger('interviewer_id')->nullable();
            $table->foreign('interviewer_id')->references('id')->on('users')->onDelete('set null');
            $table->string('interviewer_name', 100)->nullable();

            // 면접 장소/정보
            $table->string('meeting_location')->nullable();
            $table->string('meeting_url')->nullable();
            $table->string('meeting_password')->nullable();
            $table->text('preparation_notes')->nullable();

            // 평가 점수 (1-5점)
            $table->decimal('technical_score', 3, 2)->nullable()->comment('기술역량 점수');
            $table->decimal('communication_score', 3, 2)->nullable()->comment('의사소통 점수');
            $table->decimal('experience_score', 3, 2)->nullable()->comment('경험평가 점수');
            $table->decimal('attitude_score', 3, 2)->nullable()->comment('태도평가 점수');
            $table->decimal('overall_score', 3, 2)->nullable()->comment('종합평가 점수');

            // 면접 결과
            $table->enum('interview_result', [
                'pass',          // 통과
                'fail',          // 불합격
                'pending',       // 검토중
                'hold',          // 보류
                'next_round'     // 다음 단계
            ])->nullable();

            // 면접 피드백 및 메모
            $table->json('interview_feedback')->nullable()->comment('면접관 피드백');
            $table->text('strengths')->nullable()->comment('강점');
            $table->text('weaknesses')->nullable()->comment('약점');
            $table->text('recommendations')->nullable()->comment('권장사항');
            $table->text('interviewer_notes')->nullable()->comment('면접관 메모');
            $table->text('candidate_notes')->nullable()->comment('지원자 메모');

            // 면접 로그 기록
            $table->json('interview_logs')->nullable()->comment('면접 진행 로그');

            // 다음 단계 정보
            $table->datetime('next_interview_date')->nullable();
            $table->text('next_steps')->nullable();

            // 관리 정보
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            // 인덱스
            $table->index(['user_id', 'shard_number']);
            $table->index(['application_id']);
            $table->index(['referrer_partner_id']);
            $table->index(['interview_status', 'scheduled_at']);
            $table->index(['interview_result']);
            $table->index(['interviewer_id']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_interviews');
    }
};