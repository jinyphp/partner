<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 파트너 커미션 관리 테이블 생성
     *
     * =======================================================================
     * 테이블 개요
     * =======================================================================
     * 멀티레벨 마케팅(MLM) 구조의 파트너 커미션 계산 및 분배를 관리하는 핵심 테이블
     * 직접/간접 매출에 따른 다단계 커미션 자동 계산 및 지급 처리
     *
     * =======================================================================
     * 주요 기능
     * =======================================================================
     * ✓ 다양한 커미션 타입별 차별화 계산
     *   - 직접 판매 커미션 (direct_sales)
     *   - 팀 보너스 (team_bonus)
     *   - 관리 보너스 (management_bonus)
     *   - 오버라이드 보너스 (override_bonus)
     *   - 모집 보너스 (recruitment_bonus)
     *   - 등급 보너스 (rank_bonus)
     * ✓ 계층별 커미션율 적용 및 자동 분배
     * ✓ 실시간 커미션 계산 및 상태 추적
     * ✓ 세금 계산 및 실수령액 관리
     * ✓ 커미션 분쟁 및 취소 처리
     *
     * =======================================================================
     * 테이블 관계
     * =======================================================================
     * • partner_users → partner_commissions (1:N) : 커미션 수령자
     * • partner_users → partner_commissions (1:N) : 매출 발생자
     * • orders → partner_commissions (1:N) : 주문 기반 커미션
     * • partner_sales → partner_commissions (1:N) : 매출 기반 커미션
     *
     * =======================================================================
     * 커미션 계산 로직
     * =======================================================================
     * • 매출 발생 시 트리 구조를 순회하며 상위 파트너들에게 자동 분배
     * • 각 레벨별로 설정된 커미션율 적용
     * • 파트너 등급에 따른 보너스 커미션 추가 지급
     * • 실시간 계산으로 즉시 커미션 현황 파악 가능
     *
     * =======================================================================
     * 데이터 무결성
     * =======================================================================
     * • 커미션 계산 시점의 트리 경로 스냅샷 저장
     * • 이중 지급 방지를 위한 유니크 제약조건
     * • 소프트 삭제로 커미션 히스토리 보존
     */
    public function up(): void
    {
        Schema::create('partner_commissions', function (Blueprint $table) {
            $table->id();

            // 기본 정보
            $table->unsignedBigInteger('partner_id')->comment('커미션을 받을 파트너 ID');
            $table->unsignedBigInteger('source_partner_id')->comment('매출을 발생시킨 파트너 ID');
            $table->unsignedBigInteger('order_id')->nullable()->comment('주문 ID (주문 기반 커미션)');

            // 커미션 타입 및 계층 정보
            $table->enum('commission_type', [
                'direct_sales',      // 직접 판매
                'team_bonus',        // 팀 보너스
                'management_bonus',  // 관리 보너스
                'override_bonus',    // 오버라이드 보너스
                'recruitment_bonus', // 모집 보너스
                'rank_bonus'         // 등급 보너스
            ])->comment('커미션 타입');

            $table->integer('level_difference')->comment('계층 차이 (1=직접 하위, 2=2단계 하위)');
            $table->string('tree_path_at_time', 500)->comment('커미션 계산 시점의 트리 경로');

            // 금액 정보
            $table->decimal('original_amount', 15, 2)->comment('원본 매출 금액');
            $table->decimal('commission_rate', 5, 2)->comment('적용된 커미션율 (%)');
            $table->decimal('commission_amount', 15, 2)->comment('커미션 금액');
            $table->decimal('tax_amount', 15, 2)->default(0)->comment('세금');
            $table->decimal('net_amount', 15, 2)->comment('실수령액');

            // 상태 및 처리 정보
            $table->enum('status', [
                'pending',    // 대기중
                'calculated', // 계산완료
                'paid',       // 지급완료
                'cancelled',  // 취소됨
                'disputed'    // 분쟁중
            ])->default('pending')->comment('커미션 상태');

            $table->timestamp('earned_at')->comment('커미션 발생일시');
            $table->timestamp('calculated_at')->nullable()->comment('계산 완료일시');
            $table->timestamp('paid_at')->nullable()->comment('지급 완료일시');

            // 추가 정보
            $table->json('calculation_details')->nullable()->comment('계산 상세 정보');
            $table->text('notes')->nullable()->comment('메모');

            $table->timestamps();
            $table->softDeletes();

            // 외래키 및 인덱스
            $table->foreign('partner_id')->references('id')->on('partner_users')->onDelete('cascade');
            $table->foreign('source_partner_id')->references('id')->on('partner_users')->onDelete('cascade');

            $table->index(['partner_id', 'status', 'earned_at']);
            $table->index(['source_partner_id', 'commission_type']);
            $table->index(['commission_type', 'status', 'earned_at']);
            $table->index(['earned_at', 'paid_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_commissions');
    }
};