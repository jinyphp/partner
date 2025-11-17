<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 파트너 교육 등록 및 진행 상황 관리 테이블 생성
     *
     * =======================================================================
     * 📖 테이블 개요
     * =======================================================================
     * 파트너들의 교육 등록부터 완료까지 전체 학습 여정을 추적하는 시스템입니다.
     * 개별 교육 참여 이력, 진행 상황, 성과를 세밀하게 관리합니다.
     *
     * =======================================================================
     * 🎯 핵심 기능
     * =======================================================================
     * ✓ 교육별 파트너 등록 및 수강 관리
     * ✓ 학습 진행률 실시간 추적 (0-100%)
     * ✓ 다회차 시도 및 점수 이력 관리
     * ✓ 합격/불합격 자동 판정
     * ✓ 수료증 발급 및 관리
     * ✓ 교육 만료 및 재수강 관리
     * ✓ 학습 노트 및 개인 메모
     *
     * =======================================================================
     * 📊 진행 상태
     * =======================================================================
     * • enrolled: 등록됨 (아직 시작 안함)
     * • in_progress: 진행 중 (학습 시작함)
     * • completed: 완료 (합격)
     * • failed: 실패 (불합격)
     * • expired: 만료됨 (기간 초과)
     * • cancelled: 취소됨 (중도 포기)
     *
     * =======================================================================
     * 📈 학습 추적
     * =======================================================================
     * • enrolled_at: 등록 시간
     * • started_at: 학습 시작 시간
     * • completed_at: 완료 시간
     * • expires_at: 만료 시간
     * • progress_percentage: 진행률 (0-100%)
     *
     * =======================================================================
     * 🏆 평가 관리
     * =======================================================================
     * • final_score: 최종 점수
     * • passed: 합격 여부 (boolean)
     * • attempts_count: 시도 횟수
     * • attempt_scores: 시도별 점수 배열 (JSON)
     *
     * =======================================================================
     * 🎓 수료증 시스템
     * =======================================================================
     * • completion_certificate: 수료증 정보 (JSON)
     *   - certificate_id: 수료증 ID
     *   - issued_date: 발급일
     *   - valid_until: 유효기간
     *   - certificate_url: 수료증 파일 URL
     *
     * =======================================================================
     * 🔗 테이블 관계
     * =======================================================================
     * • partner_trainings → partner_training_enrollments (1:N) : 교육별 수강생
     * • partner_users → partner_training_enrollments (1:N) : 파트너별 수강 이력
     *
     * =======================================================================
     * 📈 성능 최적화
     * =======================================================================
     * • 교육-파트너 유니크 제약조건
     * • 파트너별 진행 상태 인덱스
     * • 완료일시 기준 정렬 인덱스
     * • 진행 중인 교육 빠른 조회
     */
    public function up(): void
    {
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_training_enrollments');
    }
};