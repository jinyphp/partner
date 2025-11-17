<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 파트너 면접 평가 상세 관리 테이블 생성
     *
     * =======================================================================
     * 🎤 테이블 개요
     * =======================================================================
     * 파트너 면접의 상세 평가와 피드백을 체계적으로 관리하는 평가 시스템입니다.
     * 정성적, 정량적 평가를 통해 파트너의 역량과 적합성을 종합 판단합니다.
     *
     * =======================================================================
     * 🎯 핵심 기능
     * =======================================================================
     * ✓ 다면적 역량 평가 (기술, 소통, 동기, 경험, 적합성 등)
     * ✓ 1-100점 척도의 정량적 평가
     * ✓ 면접 유형별 맞춤 평가 (화상, 전화, 대면, 온라인)
     * ✓ 5단계 최종 추천 등급
     * ✓ 구조화된 강점/약점/우려사항 분석
     * ✓ 개선 액션 아이템 제안
     * ✓ 면접 노트 및 첨부 자료 관리
     *
     * =======================================================================
     * 📊 평가 영역 (1-100점)
     * =======================================================================
     * • technical_skills: 기술 역량 및 전문성
     * • communication: 의사소통 능력
     * • motivation: 동기 및 열정
     * • experience_relevance: 경력 연관성
     * • cultural_fit: 조직 적합성
     * • problem_solving: 문제 해결 능력
     * • leadership_potential: 리더십 잠재력
     *
     * =======================================================================
     * 🏆 추천 등급
     * =======================================================================
     * • strongly_approve: 강력 추천 (90점 이상)
     * • approve: 추천 (70-89점)
     * • conditional: 조건부 승인 (50-69점)
     * • reject: 불합격 (30-49점)
     * • strongly_reject: 강력 불합격 (30점 미만)
     *
     * =======================================================================
     * 📹 면접 유형
     * =======================================================================
     * • video: 화상 면접 (기본값)
     * • phone: 전화 면접
     * • in_person: 대면 면접
     * • online_test: 온라인 테스트
     *
     * =======================================================================
     * 🔗 테이블 관계
     * =======================================================================
     * • partner_applications → partner_interview_evaluations (1:N) : 신청서별 면접 평가
     * • users → partner_interview_evaluations (1:N) : 면접관별 평가 이력
     *
     * =======================================================================
     * 📈 성능 최적화
     * =======================================================================
     * • 신청서별 면접일시 복합 인덱스
     * • 면접관별 평가 이력 인덱스
     * • 추천등급별 통계 조회 최적화
     * • 종합점수 기준 정렬 지원
     */
    public function up(): void
    {
        Schema::create('partner_interview_evaluations', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            // 관련 정보
            $table->unsignedBigInteger('interview_id')->nullable()->comment('면접 ID');
            $table->unsignedBigInteger('application_id')->comment('신청서 ID');
            $table->unsignedBigInteger('interviewer_id')->comment('면접관 ID');
            $table->string('interviewer_uuid')->nullable()->comment('면접관 UUID');

            // 면접 정보
            $table->timestamp('interview_date')->comment('면접 일시');
            $table->integer('duration_minutes')->nullable()->comment('면접 소요 시간(분)');
            $table->string('interview_type', 50)->default('video')->comment('면접 유형');
            // 면접 유형: video, phone, in_person, online_test

            // 평가 점수 (1-100점)
            $table->integer('technical_skills')->nullable()->comment('기술 역량');
            $table->integer('communication')->nullable()->comment('의사소통');
            $table->integer('motivation')->nullable()->comment('동기 및 열정');
            $table->integer('experience_relevance')->nullable()->comment('경력 연관성');
            $table->integer('cultural_fit')->nullable()->comment('조직 적합성');
            $table->integer('problem_solving')->nullable()->comment('문제 해결 능력');
            $table->integer('leadership_potential')->nullable()->comment('리더십 잠재력');

            // 종합 평가
            $table->integer('overall_rating')->nullable()->comment('종합 점수');
            $table->enum('recommendation', ['strongly_approve', 'approve', 'conditional', 'reject', 'strongly_reject'])
                  ->comment('최종 추천');

            // 상세 피드백
            $table->json('strengths')->nullable()->comment('강점들');
            $table->json('weaknesses')->nullable()->comment('약점들');
            $table->json('concerns')->nullable()->comment('우려사항들');
            $table->json('action_items')->nullable()->comment('개선 필요 사항들');
            $table->text('detailed_feedback')->nullable()->comment('상세 피드백');

            // 추가 정보
            $table->json('interview_notes')->nullable()->comment('면접 노트');
            $table->json('attachments')->nullable()->comment('첨부 파일들');

            // 외래키 및 인덱스
            $table->foreign('interview_id')->references('id')->on('partner_interviews')->onDelete('set null');
            $table->foreign('application_id')->references('id')->on('partner_applications')->onDelete('cascade');
            $table->index(['interview_id']);
            $table->index(['application_id', 'interview_date']);
            $table->index(['interviewer_uuid', 'interview_date']);
            $table->index(['recommendation', 'overall_rating']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_interview_evaluations');
    }
};