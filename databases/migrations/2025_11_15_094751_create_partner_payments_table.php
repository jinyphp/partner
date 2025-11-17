<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 파트너 지급 관리 테이블 생성
     *
     * =======================================================================
     * 💰 테이블 개요
     * =======================================================================
     * 파트너에게 커미션을 지급하는 전체 프로세스를 관리하는 핵심 시스템입니다.
     * 신청부터 실제 송금까지 모든 지급 단계를 추적하고 관리합니다.
     *
     * =======================================================================
     * 🎯 핵심 기능
     * =======================================================================
     * ✓ 커미션 지급 신청 및 승인 프로세스
     * ✓ 다양한 지급 방법 지원 (은행이체, 현금, 수표, 디지털지갑)
     * ✓ 수수료 및 세금 자동 계산
     * ✓ 지급 상태별 단계적 관리
     * ✓ 대량 지급 처리 (배치 시스템)
     * ✓ 외부 결제 시스템 연동
     * ✓ 지급 항목별 상세 추적 (어떤 커미션들이 포함되었는지)
     *
     * =======================================================================
     * 💳 지급 방법
     * =======================================================================
     * • bank_transfer: 은행 계좌 이체 (기본값, 가장 일반적)
     * • cash: 현금 지급 (소액 또는 특별한 경우)
     * • check: 수표 발행 (고액 또는 해외 지급)
     * • digital_wallet: 디지털 지갑 (페이팔, 토스 등)
     *
     * =======================================================================
     * 📊 지급 상태
     * =======================================================================
     * • requested: 지급 신청 (파트너가 신청)
     * • approved: 승인됨 (관리자가 승인)
     * • processing: 처리 중 (은행 송금 진행 중)
     * • completed: 완료됨 (실제 지급 완료)
     * • failed: 실패 (송금 실패, 계좌 오류 등)
     * • cancelled: 취소됨 (관리자 또는 파트너가 취소)
     *
     * =======================================================================
     * 💰 금액 계산 구조
     * =======================================================================
     * • requested_amount: 파트너가 신청한 원래 금액
     * • fee_amount: 지급 수수료 (은행 수수료 등)
     * • tax_amount: 세금 (원천징수세 등)
     * • final_amount: 실제 지급 금액 = requested - fee - tax
     *
     * =======================================================================
     * 🏦 계좌 정보 관리
     * =======================================================================
     * • bank_name: 은행명
     * • account_number: 계좌번호
     * • account_holder: 예금주명
     * (지급 시점의 계좌 정보를 스냅샷으로 보존)
     *
     * =======================================================================
     * 🔄 배치 처리
     * =======================================================================
     * • batch_id: 대량 처리 시 동일한 배치 ID로 그룹화
     * • is_bulk_payment: 대량 지급 여부
     * • external_transaction_id: 외부 시스템 거래 ID
     *
     * =======================================================================
     * 🔗 테이블 관계
     * =======================================================================
     * • partner_users → partner_payments (1:N) : 파트너별 지급 이력
     * • partner_payments → partner_payment_items (1:N) : 지급 항목 상세
     * • partner_commissions → partner_payment_items (1:N) : 포함된 커미션들
     *
     * =======================================================================
     * 📈 성능 최적화
     * =======================================================================
     * • 파트너별 지급 상태 복합 인덱스
     * • 신청일시 기준 정렬 인덱스
     * • 배치 ID 기준 그룹 조회 인덱스
     * • 지급 코드 유니크 제약조건
     */
    public function up(): void
    {
        Schema::create('partner_payments', function (Blueprint $table) {
            $table->id();

            // 파트너 정보
            $table->unsignedBigInteger('partner_id');
            $table->string('partner_name'); // 지급 당시 파트너 이름 (백업용)
            $table->string('partner_email'); // 지급 당시 파트너 이메일 (백업용)

            // 지급 정보
            $table->string('payment_code')->unique(); // 지급 코드 (PAY-YYYYMMDD-0001)
            $table->decimal('requested_amount', 15, 2); // 신청 금액
            $table->decimal('fee_amount', 15, 2)->default(0); // 수수료
            $table->decimal('tax_amount', 15, 2)->default(0); // 세금
            $table->decimal('final_amount', 15, 2); // 실제 지급 금액

            // 지급 방법 및 계좌 정보
            $table->enum('payment_method', ['bank_transfer', 'cash', 'check', 'digital_wallet'])->default('bank_transfer');
            $table->string('bank_name')->nullable(); // 은행명
            $table->string('account_number')->nullable(); // 계좌번호
            $table->string('account_holder')->nullable(); // 예금주

            // 상태 관리
            $table->enum('status', ['requested', 'approved', 'processing', 'completed', 'failed', 'cancelled'])->default('requested');
            $table->timestamp('requested_at'); // 신청일
            $table->timestamp('approved_at')->nullable(); // 승인일
            $table->timestamp('processed_at')->nullable(); // 처리일 (송금일)
            $table->timestamp('completed_at')->nullable(); // 완료일
            $table->timestamp('cancelled_at')->nullable(); // 취소일

            // 처리자 정보
            $table->unsignedBigInteger('approved_by')->nullable(); // 승인자 ID
            $table->unsignedBigInteger('processed_by')->nullable(); // 처리자 ID
            $table->string('approval_notes')->nullable(); // 승인 메모
            $table->string('processing_notes')->nullable(); // 처리 메모
            $table->string('failure_reason')->nullable(); // 실패 사유

            // 대량 처리 정보
            $table->string('batch_id')->nullable(); // 대량 처리 배치 ID
            $table->boolean('is_bulk_payment')->default(false); // 대량 지급 여부

            // 외부 시스템 연동 정보
            $table->string('external_transaction_id')->nullable(); // 외부 거래 ID
            $table->json('external_response')->nullable(); // 외부 API 응답

            // 메타데이터
            $table->json('metadata')->nullable(); // 추가 정보 (JSON)
            $table->text('notes')->nullable(); // 관리자 메모

            $table->timestamps();

            // 인덱스
            $table->index('partner_id');
            $table->index('status');
            $table->index('requested_at');
            $table->index('batch_id');
            $table->index(['status', 'requested_at']);

            // 외래키 제약조건
            $table->foreign('partner_id')->references('id')->on('partner_users')->onDelete('restrict');
        });

        // 지급 항목 세부 테이블 (어떤 커미션들이 이 지급에 포함되었는지)
        Schema::create('partner_payment_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('payment_id'); // 지급 ID
            $table->unsignedBigInteger('commission_id'); // 커미션 ID
            $table->decimal('commission_amount', 15, 2); // 커미션 금액 (스냅샷)
            $table->decimal('included_amount', 15, 2); // 지급에 포함된 금액

            $table->timestamps();

            // 인덱스 및 외래키
            $table->index(['payment_id', 'commission_id']);
            $table->foreign('payment_id')->references('id')->on('partner_payments')->onDelete('cascade');
            $table->foreign('commission_id')->references('id')->on('partner_commissions')->onDelete('restrict');

            // 중복 방지
            $table->unique(['payment_id', 'commission_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_payment_items');
        Schema::dropIfExists('partner_payments');
    }
};