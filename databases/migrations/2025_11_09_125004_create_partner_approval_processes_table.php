<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 파트너 승인 프로세스 추적 테이블 생성
     *
     * =======================================================================
     * ⚖️ 테이블 개요
     * =======================================================================
     * 파트너 신청서의 승인 프로세스를 단계별로 추적하고 관리하는 워크플로우 시스템입니다.
     * 각 처리 단계의 진행 상태, 소요 시간, 결과를 체계적으로 기록합니다.
     *
     * =======================================================================
     * 🎯 핵심 기능
     * =======================================================================
     * ✓ 단계별 승인 프로세스 추적 (검토 → 면접 → 승인)
     * ✓ 처리자별 작업 시간 및 효율성 측정
     * ✓ 체크리스트 기반 표준화된 검토 프로세스
     * ✓ 결과별 다음 단계 자동 라우팅
     * ✓ 프로세스 병목 구간 식별 및 개선
     * ✓ 처리 기한 관리 및 알림 시스템 연동
     * ✓ 결정 근거 및 필요 조치사항 문서화
     *
     * =======================================================================
     * 🔄 프로세스 유형
     * =======================================================================
     * • review: 서류 검토 (기본 자격 요건 확인)
     * • interview: 면접 진행 (역량 및 적합성 평가)
     * • approval: 최종 승인 처리
     * • rejection: 거부 처리
     * • reapplication: 재신청 검토
     *
     * =======================================================================
     * 📊 처리 단계
     * =======================================================================
     * • pending: 대기 중 (아직 시작 안됨)
     * • in_progress: 진행 중 (작업자가 처리 중)
     * • completed: 완료됨 (해당 단계 완료)
     * • skipped: 생략됨 (조건에 따라 생략)
     *
     * =======================================================================
     * 🎯 처리 결과
     * =======================================================================
     * • approved: 승인 (다음 단계로 진행)
     * • rejected: 거부 (프로세스 종료)
     * • requires_interview: 면접 필요 (면접 단계로 이동)
     * • requires_revision: 수정 필요 (지원자에게 반려)
     * • escalated: 상위 승인자에게 이관
     *
     * =======================================================================
     * 🔗 테이블 관계
     * =======================================================================
     * • partner_applications → partner_approval_processes (1:N) : 신청서별 프로세스
     * • users → partner_approval_processes (1:N) : 처리자별 작업 이력
     *
     * =======================================================================
     * 📈 성능 최적화
     * =======================================================================
     * • 신청서별 프로세스 유형 복합 인덱스
     * • 처리자별 진행 단계 조회 최적화
     * • 현재 단계별 대기 작업 빠른 조회
     * • 처리 시간 분석용 인덱스
     */
    public function up(): void
    {
        Schema::create('partner_approval_processes', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            // 관련 정보
            $table->unsignedBigInteger('application_id')->comment('신청서 ID');
            $table->unsignedBigInteger('processor_id')->comment('처리자 ID');
            $table->string('processor_uuid')->nullable()->comment('처리자 UUID');

            // 프로세스 정보
            $table->enum('process_type', ['review', 'interview', 'approval', 'rejection', 'reapplication'])
                  ->comment('프로세스 유형');
            $table->enum('current_step', ['pending', 'in_progress', 'completed', 'skipped'])
                  ->default('pending')
                  ->comment('현재 단계');

            // 처리 정보
            $table->timestamp('started_at')->nullable()->comment('시작 시간');
            $table->timestamp('completed_at')->nullable()->comment('완료 시간');
            $table->integer('estimated_duration_hours')->nullable()->comment('예상 소요 시간');
            $table->integer('actual_duration_hours')->nullable()->comment('실제 소요 시간');

            // 결과 정보
            $table->enum('result', ['approved', 'rejected', 'requires_interview', 'requires_revision', 'escalated'])
                  ->nullable()
                  ->comment('처리 결과');

            $table->json('checklist')->nullable()->comment('체크리스트');
            // {
            //   "documents_verified": true,
            //   "background_check": false,
            //   "references_contacted": true,
            //   "technical_assessment": null
            // }

            $table->text('decision_rationale')->nullable()->comment('결정 근거');
            $table->json('required_actions')->nullable()->comment('필요 조치사항');

            // 다음 단계 정보
            $table->unsignedBigInteger('next_processor_id')->nullable()->comment('다음 처리자 ID');
            $table->timestamp('next_due_date')->nullable()->comment('다음 처리 예정일');

            // 외래키 및 인덱스
            $table->foreign('application_id')->references('id')->on('partner_applications')->onDelete('cascade');
            $table->index(['application_id', 'process_type']);
            $table->index(['processor_uuid', 'current_step']);
            $table->index(['current_step', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_approval_processes');
    }
};