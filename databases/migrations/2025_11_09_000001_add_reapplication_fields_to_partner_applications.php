<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 재신청 관련 필드 추가
     *
     * 기존 partner_applications 테이블에 재신청 시 필요한 필드들을 추가합니다.
     * - motivation: 신청 동기 (기존 및 재신청 모두 사용)
     * - improvement_plan: 개선 계획 (재신청 전용)
     * - project_experience: 추가 프로젝트 경험 (선택사항)
     * - goals: 수정된 목표 및 계획 (선택사항)
     * - submitted_at: 실제 제출 시간 (draft와 구분)
     */
    public function up(): void
    {
        Schema::table('partner_applications', function (Blueprint $table) {
            // 신청 동기 (기존 신청과 재신청 모두 사용)
            $table->text('motivation')->nullable()->comment('신청 동기 및 개선사항');

            // 재신청 전용 필드들
            $table->text('improvement_plan')->nullable()->comment('개선 계획 (재신청 시 필수)');
            $table->text('project_experience')->nullable()->comment('추가 프로젝트 경험');
            $table->text('goals')->nullable()->comment('수정된 목표 및 계획');

            // 제출 시간 추가 (draft와 실제 제출을 구분)
            $table->timestamp('submitted_at')->nullable()->comment('실제 제출 시간');

            // 추천자 정보 상세 필드들 (MLM 지원)
            $table->string('referrer_name')->nullable()->comment('추천인 이름');
            $table->string('referrer_contact')->nullable()->comment('추천인 연락처');
            $table->string('referrer_relationship')->nullable()->comment('추천인과의 관계');
            $table->date('meeting_date')->nullable()->comment('만남 일자');
            $table->string('meeting_location')->nullable()->comment('만남 장소');
            $table->string('introduction_method')->nullable()->comment('소개 방법');

            // 인덱스 추가
            $table->index(['submitted_at']);
            $table->index(['previous_application_id', 'application_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partner_applications', function (Blueprint $table) {
            $table->dropColumn([
                'motivation',
                'improvement_plan',
                'project_experience',
                'goals',
                'submitted_at',
                'referrer_name',
                'referrer_contact',
                'referrer_relationship',
                'meeting_date',
                'meeting_location',
                'introduction_method'
            ]);
        });
    }
};