<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 파트너 매출 관리 테이블 생성
     *
     * =======================================================================
     * 📊 테이블 개요
     * =======================================================================
     * 파트너의 매출 실적을 기록하고 커미션 분배 계산의 핵심 기준이 되는 테이블입니다.
     * MLM(Multi-Level Marketing) 구조에서 모든 커미션 계산의 출발점 역할을 담당합니다.
     *
     * =======================================================================
     * 🎯 핵심 기능
     * =======================================================================
     * ✓ 파트너별 매출 실적 기록 및 관리
     * ✓ 커미션 계산 트리거 역할 (매출 확정 시 자동 분배)
     * ✓ 매출 상태 관리 (대기 → 확정 → 취소/환불)
     * ✓ 시분초 포함 정확한 매출 발생 시점 기록
     * ✓ 트리 구조 스냅샷 보존 (계산 시점 네트워크 상태)
     * ✓ 매출 승인 워크플로우 지원
     * ✓ 외부 시스템 연동 (주문번호, 코드)
     *
     * =======================================================================
     * 🔄 매출 처리 워크플로우
     * =======================================================================
     * 1. pending: 매출 등록 (임시 상태)
     * 2. confirmed: 매출 확정 → 커미션 계산 트리거
     * 3. cancelled: 매출 취소 → 커미션 회수
     * 4. refunded: 환불 처리 → 커미션 조정
     *
     * =======================================================================
     * 🔗 테이블 관계
     * =======================================================================
     * • partner_users → partner_sales (1:N) : 매출 발생자
     * • partner_sales → partner_commissions (1:N) : 커미션 계산 기준
     * • orders → partner_sales (1:1) : 주문 기반 매출 연결
     *
     * =======================================================================
     * 💰 커미션 계산 시점
     * =======================================================================
     * • 매출 확정(confirmed) 시 자동 커미션 계산 실행
     * • 계산 시점의 파트너 트리 구조 스냅샷 저장
     * • 상위 파트너들에게 단계별 커미션 분배
     * • 매출 취소/환불 시 커미션 회수 처리
     *
     * =======================================================================
     * 📈 성능 최적화
     * =======================================================================
     * • 커미션 계산 상태별 인덱스
     * • 매출일과 상태 복합 인덱스
     * • 파트너별 실적 조회 최적화
     * • 대용량 매출 데이터 처리 지원
     */
    public function up(): void
    {
        Schema::create('partner_sales', function (Blueprint $table) {
            // 기본 필드
            $table->id(); // 매출 고유 ID
            $table->timestamps(); // 생성일시, 수정일시
            $table->softDeletes(); // 소프트 삭제 지원

            // 파트너 관계
            $table->foreignId('partner_id')->constrained('partner_users')->onDelete('cascade');
            $table->string('partner_name', 100); // 파트너 이름 (캐싱용)
            $table->string('partner_email', 255); // 파트너 이메일 (캐싱용)

            // 매출 기본 정보
            $table->string('title', 200); // 매출 제목/설명
            $table->text('description')->nullable(); // 매출 상세 설명
            $table->decimal('amount', 15, 2); // 매출 금액
            $table->string('currency', 3)->default('KRW'); // 통화 코드
            $table->datetime('sales_date'); // 매출 발생일 (시분초 포함)
            $table->string('order_number', 100)->nullable(); // 주문번호/참조번호
            $table->string('order_code', 100)->nullable(); // 주문 코드 (커미션 계산용)

            // 매출 분류
            $table->string('category', 50)->nullable(); // 매출 카테고리
            $table->string('product_type', 50)->nullable(); // 상품 유형
            $table->string('sales_channel', 50)->nullable(); // 판매 채널 (온라인, 오프라인 등)

            // 상태 관리
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'refunded'])->default('pending');
            // pending: 대기중, confirmed: 확정, cancelled: 취소, refunded: 환불

            $table->string('status_reason', 200)->nullable(); // 상태 변경 사유
            $table->timestamp('confirmed_at')->nullable(); // 확정 일시
            $table->timestamp('cancelled_at')->nullable(); // 취소 일시

            // 커미션 계산 관련
            $table->boolean('commission_calculated')->default(false); // 커미션 계산 완료 여부
            $table->timestamp('commission_calculated_at')->nullable(); // 커미션 계산 일시
            $table->decimal('total_commission_amount', 15, 2)->default(0); // 총 분배 커미션 금액
            $table->integer('commission_recipients_count')->default(0); // 커미션 수령자 수
            $table->json('commission_distribution')->nullable(); // 커미션 분배 상세 내역

            // 트리 구조 스냅샷 (계산 시점의 네트워크 상태 보존)
            $table->text('tree_snapshot')->nullable(); // 계산 당시의 트리 구조 JSON
            $table->string('partner_tier_at_time', 50)->nullable(); // 계산 당시 파트너 등급
            $table->string('partner_type_at_time', 50)->nullable(); // 계산 당시 파트너 타입

            // 매출 검증 및 승인
            $table->boolean('requires_approval')->default(false); // 승인 필요 여부
            $table->boolean('is_approved')->default(false); // 승인 상태
            $table->unsignedBigInteger('approved_by')->nullable(); // 승인자 파트너 ID
            $table->timestamp('approved_at')->nullable(); // 승인 일시
            $table->text('approval_notes')->nullable(); // 승인 메모

            // 관리 정보 (파트너 시스템에서는 partner_users.id 참조)
            $table->unsignedBigInteger('created_by')->nullable(); // 등록자 파트너 ID
            $table->unsignedBigInteger('updated_by')->nullable(); // 수정자 파트너 ID
            $table->text('admin_notes')->nullable(); // 관리자 메모

            // 외부 연동
            $table->string('external_reference', 100)->nullable(); // 외부 시스템 참조 ID
            $table->json('external_data')->nullable(); // 외부 시스템 연동 데이터

            // 인덱스
            $table->index(['partner_id', 'status']); // 파트너별 상태별 조회
            $table->index(['sales_date', 'status']); // 매출일별 상태별 조회
            $table->index(['status', 'commission_calculated']); // 커미션 계산 대상 조회
            $table->index(['category', 'sales_channel']); // 분류별 조회
            $table->index(['order_number']); // 주문번호 검색
            $table->index(['order_code']); // 주문 코드 검색
            $table->index(['created_at']); // 등록일별 조회
            $table->index(['amount']); // 금액별 조회
            $table->index(['confirmed_at']); // 확정일별 조회
            $table->index(['commission_calculated_at']); // 커미션 계산일별 조회

            // 외래키 제약조건 (파트너 시스템에서는 partner_users 테이블 참조)
            $table->foreign('approved_by')->references('id')->on('partner_users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('partner_users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('partner_users')->onDelete('set null');
        });

    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_sales');
    }
};
