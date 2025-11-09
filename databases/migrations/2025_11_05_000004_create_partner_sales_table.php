<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 파트너 매출 관리 테이블 생성
     *
     * 파트너의 매출 실적을 기록하고 커미션 분배 계산의 기준이 되는 테이블
     * 매출 등록, 수정, 취소에 따른 커미션 자동 계산 및 분배 처리
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
            $table->date('sales_date'); // 매출 발생일
            $table->string('order_number', 100)->nullable(); // 주문번호/참조번호

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
            $table->unsignedBigInteger('approved_by')->nullable(); // 승인자 ID
            $table->timestamp('approved_at')->nullable(); // 승인 일시
            $table->text('approval_notes')->nullable(); // 승인 메모

            // 관리 정보
            $table->unsignedBigInteger('created_by')->nullable(); // 등록자 ID
            $table->unsignedBigInteger('updated_by')->nullable(); // 수정자 ID
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
            $table->index(['created_at']); // 등록일별 조회
            $table->index(['amount']); // 금액별 조회
            $table->index(['confirmed_at']); // 확정일별 조회
            $table->index(['commission_calculated_at']); // 커미션 계산일별 조회

            // 외래키 제약조건
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
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
