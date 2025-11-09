<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 파트너 엔지니어 관리 테이블 생성
     *
     * =======================================================================
     * 테이블 개요
     * =======================================================================
     * 서비스 제공 파트너 엔지니어들의 실무 정보를 관리하는 핵심 테이블
     * partner_users와 별개로 실제 작업 수행자의 기술적/업무적 정보를 저장
     *
     * =======================================================================
     * 주요 기능
     * =======================================================================
     * ✓ 엔지니어별 고유 코드 관리 (PE000001 형태)
     * ✓ 파트너 등급별 능력 및 권한 차별화
     * ✓ 실시간 성과 지표 추적 (평점, 완료율, 만족도)
     * ✓ 월/누적 수익 관리 및 정산 기준
     * ✓ 업무 가용성 및 스케줄 관리
     * ✓ 전문 분야별 기술 스킬 매트릭스
     *
     * =======================================================================
     * 테이블 관계
     * =======================================================================
     * • users → partner_engineers (1:1) : 기본 계정 정보 연결
     * • partner_tiers → partner_engineers (1:N) : 등급별 혜택/제한
     * • partner_engineers ← jobs/projects (1:N) : 작업 할당 및 실적
     * • partner_engineers ← reviews (1:N) : 고객 평가 및 피드백
     *
     * =======================================================================
     * 데이터 구조 특징
     * =======================================================================
     * • 성과 데이터는 실시간 업데이트로 최신성 보장
     * • JSON 필드로 유연한 기술 스택 및 전문성 관리
     * • 소프트 삭제로 히스토리 보존 및 데이터 무결성 유지
     * • 인덱스 최적화로 빠른 엔지니어 검색 및 매칭 지원
     */
    public function up(): void
    {
        Schema::create('partner_engineers', function (Blueprint $table) {
            // 기본 필드
            $table->id(); // 파트너 엔지니어 고유 ID
            $table->timestamps(); // 생성일시, 수정일시
            $table->softDeletes(); // 소프트 삭제 지원

            // 기본 정보
            $table->unsignedBigInteger('user_id'); // 연결된 사용자 ID
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->string('engineer_code', 20)->unique(); // 파트너 엔지니어 고유 코드 (예: PE000001)

            // 등급 및 상태
            $table->string('current_tier', 20)->default('bronze'); // 현재 등급
            $table->foreign('current_tier')->references('tier_code')->on('partner_tiers');

            $table->enum('status', ['pending', 'active', 'inactive', 'suspended'])->default('pending');
            // pending: 승인대기, active: 활성, inactive: 비활성, suspended: 정지

            $table->date('hire_date')->nullable(); // 파트너 계약 시작일

            // 수익 정보
            $table->decimal('total_earnings', 12, 2)->default(0); // 총 누적 수익
            $table->decimal('current_month_earnings', 10, 2)->default(0); // 이번달 수익
            $table->decimal('last_month_earnings', 10, 2)->default(0); // 지난달 수익

            // 성과 지표
            $table->decimal('average_rating', 3, 2)->default(0); // 평균 고객 평점 (0.00 ~ 5.00)
            $table->integer('total_completed_jobs')->default(0); // 총 완료 작업 수
            $table->integer('current_month_jobs')->default(0); // 이번달 완료 작업 수
            $table->decimal('punctuality_rate', 5, 2)->default(0); // 시간 준수율 (%)
            $table->decimal('customer_satisfaction', 5, 2)->default(0); // 고객 만족도 (%)

            // 등급 관리
            $table->timestamp('last_tier_evaluation')->nullable(); // 마지막 등급 평가일
            $table->date('next_tier_eligible_date')->nullable(); // 다음 등급 승격 가능일

            // 추가 정보 (JSON)
            $table->json('specializations')->nullable();
            // 전문 분야 - 구조 예시:
            // ["에어컨 수리", "배관 작업", "전기 작업", "청소"]

            $table->json('certifications')->nullable();
            // 보유 자격증 - 구조 예시:
            // [
            //   {"name": "전기기사", "issued_by": "한국산업인력공단", "issue_date": "2023-06-15", "expiry_date": "2028-06-14"},
            //   {"name": "배관기능사", "issued_by": "한국산업인력공단", "issue_date": "2022-03-20"}
            // ]

            $table->text('bio')->nullable(); // 자기소개 및 경력 설명

            $table->json('availability')->nullable();
            // 근무 가능 시간 - 구조 예시:
            // {
            //   "weekdays": {"start": "09:00", "end": "18:00"},
            //   "weekends": {"start": "10:00", "end": "16:00"},
            //   "holidays": false,
            //   "emergency_calls": true
            // }

            $table->decimal('hourly_rate', 8, 2)->nullable(); // 희망 시급 (참고용)

            // 위치 정보
            $table->string('preferred_region')->nullable(); // 선호 근무 지역
            $table->decimal('max_travel_distance_km', 5, 2)->default(30); // 최대 이동 가능 거리 (km)

            // 성능 최적화를 위한 인덱스
            $table->index(['user_id']); // 사용자별 파트너 정보 조회용
            $table->index(['current_tier', 'status']); // 등급별 상태 조회용
            $table->index(['status', 'hire_date']); // 상태별 계약일 조회용
            $table->index(['average_rating', 'total_completed_jobs']); // 성과별 조회용
        });

        // Note: Default data insertion removed to avoid foreign key constraints
        // Add sample data after users and partner_tiers are properly set up
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_engineers');
    }
};