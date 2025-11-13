<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
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