<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 파트너 활동 로그 테이블 생성
     *
     * =======================================================================
     * 📋 테이블 개요
     * =======================================================================
     * 파트너 시스템의 모든 활동을 추적하고 감사(Audit)하는 핵심 로그 테이블입니다.
     * 파트너의 모든 행동과 시스템 변경사항을 시간순으로 기록하여 투명성을 보장합니다.
     *
     * =======================================================================
     * 🎯 핵심 기능
     * =======================================================================
     * ✓ 파트너 신청부터 승인까지 전 과정 추적
     * ✓ 상태 변경 이력 관리 (이전값 → 새값)
     * ✓ 시스템 액션 및 사용자 액션 구분 기록
     * ✓ IP 주소 및 브라우저 정보 보안 추적
     * ✓ 관리자 작업 로그 및 감사 추적
     * ✓ 파트너 성과 변경 이력 관리
     * ✓ 메타데이터를 통한 상세 컨텍스트 저장
     *
     * =======================================================================
     * 📊 로그 활동 유형
     * =======================================================================
     * • application_submitted: 파트너 신청서 제출
     * • status_changed: 파트너 상태 변경 (승인, 거부 등)
     * • interview_scheduled: 면접 일정 설정
     * • approved: 파트너 승인 완료
     * • rejected: 파트너 신청 거부
     * • reapplied: 재신청 접수
     * • tier_changed: 파트너 등급 변경
     * • performance_updated: 성과 지표 업데이트
     *
     * =======================================================================
     * 🔗 테이블 관계
     * =======================================================================
     * • partner_users → partner_activity_logs (1:N) : 파트너별 활동 이력
     * • partner_applications → partner_activity_logs (1:N) : 신청서별 활동
     * • users → partner_activity_logs (1:N) : 작업 수행자 추적
     *
     * =======================================================================
     * 🔒 보안 및 감사
     * =======================================================================
     * • IP 주소 기록으로 접근 위치 추적
     * • User-Agent 정보로 접근 환경 파악
     * • 작업 수행자 UUID로 샤딩 환경 지원
     * • 메타데이터로 상세 컨텍스트 보존
     * • 시간순 정렬로 활동 순서 보장
     *
     * =======================================================================
     * 📈 성능 최적화
     * =======================================================================
     * • 파트너별 + 시간순 복합 인덱스
     * • 활동 유형별 조회 최적화
     * • 신청서별 활동 추적 인덱스
     * • 사용자별 작업 로그 조회 지원
     */
    public function up(): void
    {
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_activity_logs');
    }
};