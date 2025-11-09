<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 파트너 네트워크 관계 관리 테이블 생성
     *
     * =======================================================================
     * 테이블 개요
     * =======================================================================
     * 파트너 간의 계층적 관계와 조직 구조를 관리하는 핵심 네트워크 테이블
     * 모집, 승계, 이전 등 다양한 관계 형성 및 변화 과정을 상세 추적
     *
     * =======================================================================
     * 주요 기능
     * =======================================================================
     * ✓ 멀티레벨 파트너 조직 구조 관리
     *   - 직접 모집 관계 (direct)
     *   - 상속된 관계 (inherited) : 상위 파트너 변경 시 자동 형성
     *   - 이전된 관계 (transferred) : 조직 재편 시 관계 이전
     * ✓ 실시간 관계 깊이(depth) 계산 및 경로 추적
     * ✓ 관계별 성과 및 기여도 측정
     * ✓ 관계 활성화/비활성화 상태 관리
     * ✓ 모집자와 실제 관계자 구분 추적
     *
     * =======================================================================
     * 테이블 관계
     * =======================================================================
     * • partner_users → relationships (1:N) : 상위 파트너 (parent_id)
     * • partner_users → relationships (1:N) : 하위 파트너 (child_id)
     * • partner_users → relationships (1:N) : 실제 모집자 (recruiter_id)
     * • partner_commissions → relationships (N:1) : 관계 기반 커미션 계산
     *
     * =======================================================================
     * 네트워크 구조 관리
     * =======================================================================
     * • 트리 구조 무결성 보장 (순환 관계 방지)
     * • 관계 변경 시 하위 관계들의 자동 업데이트
     * • 깊이 제한을 통한 조직 크기 관리
     * • 관계 경로(path) 저장으로 빠른 계층 조회
     *
     * =======================================================================
     * 성과 추적 시스템
     * =======================================================================
     * • 관계별 매출 기여도 실시간 집계
     * • 지급된 커미션 총액 추적
     * • 모집 성과 및 조직 확장 기여도 측정
     * • 비활성화된 관계의 히스토리 보존
     *
     * =======================================================================
     * 데이터 무결성 보장
     * =======================================================================
     * • 외래키 제약조건으로 참조 무결성 유지
     * • 소프트 삭제로 관계 히스토리 보존
     * • 관계 변경 로그 자동 기록
     * • 이중 관계 방지를 위한 유니크 제약조건
     */
    public function up(): void
    {
        Schema::create('partner_network_relationships', function (Blueprint $table) {
            $table->id();

            // 관계 당사자
            $table->unsignedBigInteger('parent_id')->comment('상위 파트너 ID');
            $table->unsignedBigInteger('child_id')->comment('하위 파트너 ID');

            // 관계 정보
            $table->integer('depth')->comment('계층 깊이 (1=직접 하위)');
            $table->string('relationship_path', 1000)->comment('관계 경로');
            $table->enum('relationship_type', [
                'direct',     // 직접 모집
                'inherited',  // 상속된 관계
                'transferred' // 이전된 관계
            ])->default('direct')->comment('관계 타입');

            // 모집 정보
            $table->unsignedBigInteger('recruiter_id')->comment('실제 모집한 파트너 ID');
            $table->timestamp('recruited_at')->comment('모집 일시');
            $table->json('recruitment_details')->nullable()->comment('모집 상세 정보');

            // 상태 정보
            $table->boolean('is_active')->default(true)->comment('활성 관계 여부');
            $table->timestamp('activated_at')->nullable()->comment('활성화 일시');
            $table->timestamp('deactivated_at')->nullable()->comment('비활성화 일시');
            $table->text('deactivation_reason')->nullable()->comment('비활성화 사유');

            // 성과 추적
            $table->decimal('total_generated_sales', 15, 2)->default(0)->comment('이 관계로 인한 총 매출');
            $table->decimal('total_commissions_paid', 15, 2)->default(0)->comment('이 관계로 지급된 총 커미션');
            $table->integer('sub_partners_recruited')->default(0)->comment('이 관계를 통해 모집된 하위 파트너 수');

            $table->timestamps();
            $table->softDeletes();

            // 외래키 및 인덱스
            $table->foreign('parent_id')->references('id')->on('partner_users')->onDelete('cascade');
            $table->foreign('child_id')->references('id')->on('partner_users')->onDelete('cascade');
            $table->foreign('recruiter_id')->references('id')->on('partner_users')->onDelete('cascade');

            // 유니크 제약조건 - 동일한 parent-child 관계는 하나만 존재
            $table->unique(['parent_id', 'child_id']);

            $table->index(['parent_id', 'depth', 'is_active']);
            $table->index(['child_id', 'is_active']);
            $table->index(['recruiter_id', 'recruited_at']);
            $table->index(['relationship_type', 'is_active']);
            $table->index(['depth', 'recruited_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_network_relationships');
    }
};