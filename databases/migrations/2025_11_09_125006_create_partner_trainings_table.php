<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 파트너 교육 관리 테이블 생성
     *
     * =======================================================================
     * 🎓 테이블 개요
     * =======================================================================
     * 파트너들을 위한 교육 과정을 체계적으로 관리하는 학습 관리 시스템(LMS)입니다.
     * 온보딩부터 전문성 향상까지 파트너의 역량 개발을 종합적으로 지원합니다.
     *
     * =======================================================================
     * 🎯 핵심 기능
     * =======================================================================
     * ✓ 4가지 교육 유형별 맞춤 과정 관리
     * ✓ 다양한 교육 방식 지원 (온라인, 대면, 하이브리드)
     * ✓ 등급별/타입별 맞춤 교육 추천
     * ✓ 선수조건 기반 체계적 학습 경로
     * ✓ 평가 기준 및 합격 점수 설정
     * ✓ 강사 정보 및 교육 자료 관리
     * ✓ 참가자 수 제한 및 일정 관리
     *
     * =======================================================================
     * 📚 교육 유형
     * =======================================================================
     * • onboarding: 신규 파트너 온보딩 (필수 과정)
     * • skill_development: 스킬 개발 (전문성 향상)
     * • compliance: 컴플라이언스 (규정 및 정책 교육)
     * • certification: 인증 과정 (자격증 취득)
     *
     * =======================================================================
     * 🎯 교육 방식
     * =======================================================================
     * • online: 온라인 교육 (동영상, 웹세미나)
     * • in_person: 대면 교육 (집합 교육)
     * • hybrid: 하이브리드 (온라인 + 대면)
     * • self_study: 자율 학습 (교재 기반)
     *
     * =======================================================================
     * 📊 난이도 체계
     * =======================================================================
     * • beginner: 초급 (신규 파트너 대상)
     * • intermediate: 중급 (경험 파트너 대상)
     * • advanced: 고급 (전문 파트너 대상)
     *
     * =======================================================================
     * 🎯 대상 관리
     * =======================================================================
     * • target_tiers: 대상 등급 (브론즈, 실버, 골드 등)
     * • target_types: 대상 타입 (세일즈, 마케팅, 기술 등)
     * • prerequisites: 선수조건 (이전 교육 이수 등)
     * • is_mandatory: 필수 여부
     *
     * =======================================================================
     * 🔗 테이블 관계
     * =======================================================================
     * • partner_trainings → partner_training_enrollments (1:N) : 교육별 수강생
     * • users → partner_trainings (1:N) : 강사 정보
     *
     * =======================================================================
     * 📈 성능 최적화
     * =======================================================================
     * • 교육 유형별 활성 상태 인덱스
     * • 필수 교육 빠른 조회 인덱스
     * • 교육 일정 범위 검색 최적화
     * • 교육 코드 유니크 제약조건
     */
    public function up(): void
    {
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_trainings');
    }
};