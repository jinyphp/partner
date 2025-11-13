<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 파트너 지원서 관리 테이블 생성
     *
     * =======================================================================
     * 테이블 개요
     * =======================================================================
     * 일반 사용자가 파트너 신청을 할 때 사용되는 지원서 관리 테이블
     * 추천 파트너 기반의 계층적 구조 형성과 승인 프로세스를 통합 관리
     *
     * =======================================================================
     * 주요 기능
     * =======================================================================
     * ✓ 파트너 지원서 작성 및 제출 관리
     * ✓ 추천 파트너 기반 계층 구조 사전 설정
     * ✓ 다양한 추천 경로 및 캠페인 추적
     * ✓ 관리자 승인/거부 프로세스 및 면접 관리
     * ✓ 추천 보너스 자동 계산 및 지급 예약
     * ✓ 재신청 및 이력 관리
     * ✓ 샤딩 환경 지원
     *
     * =======================================================================
     * 테이블 관계
     * =======================================================================
     * • users → partner_applications (1:N) : 지원자 기본 정보
     * • partner_users → partner_applications (1:N) : 추천 파트너
     * • partner_applications → partner_applications (1:N) : 재신청 관계
     * • partner_applications → partner_users (1:1) : 승인 후 파트너 생성
     *
     * =======================================================================
     * 추천 시스템 특징
     * =======================================================================
     * • 9가지 추천 경로 지원 (직접추천, 온라인링크, 소셜미디어 등)
     * • 추천 코드 기반 추적 및 캠페인 관리
     * • 계층 구조 사전 계산으로 승인 시 즉시 네트워크 구성
     * • 추천 보너스 자동 계산 및 지급 예약
     * • 추천자 성과 추적 및 인센티브 관리
     */
    public function up(): void
    {
        // 1. partner_applications 테이블 생성
        Schema::create('partner_applications', function (Blueprint $table) {
            // 기본 필드
            $table->id(); // 지원서 고유 ID
            $table->timestamps(); // 생성일시, 수정일시
            $table->softDeletes(); // 소프트 삭제 지원

            // 지원자 정보
            $table->unsignedBigInteger('user_id'); // 지원자 사용자 ID (샤딩 환경에서 외래키 제약조건 제거)

            // 샤딩 지원 필드
            $table->string('user_uuid')->nullable()->index()->comment('사용자 UUID (샤딩 지원)');
            $table->integer('shard_number')->nullable()->comment('샤드 번호');

            // ====================================================================
            // 추천 파트너 및 계층 구조 정보 (Referral & Hierarchy)
            // ====================================================================

            // 추천 파트너 정보
            $table->unsignedBigInteger('referrer_partner_id')->nullable()->comment('추천한 파트너 ID');
            $table->foreign('referrer_partner_id')->references('id')->on('partner_users')->onDelete('set null');

            // 추천 코드 및 경로
            $table->string('referral_code', 50)->nullable()->comment('사용된 추천 코드');
            $table->enum('referral_source', [
                'direct',           // 직접 추천
                'online_link',      // 온라인 링크
                'offline_meeting',  // 오프라인 미팅
                'social_media',     // 소셜미디어
                'event',           // 이벤트/세미나
                'advertisement',   // 광고
                'word_of_mouth',   // 구전
                'self_application', // 자율 지원
                'other'            // 기타
            ])->nullable()->default('self_application')->comment('추천 경로');

            // 추천자 정보 (개별 필드)
            $table->string('referrer_name', 100)->nullable()->comment('추천자 이름');
            $table->string('referrer_contact', 100)->nullable()->comment('추천자 연락처');
            $table->string('referrer_relationship', 100)->nullable()->comment('추천자와의 관계');
            $table->date('meeting_date')->nullable()->comment('미팅 날짜');
            $table->string('meeting_location', 255)->nullable()->comment('미팅 장소');
            $table->string('introduction_method', 255)->nullable()->comment('소개 방식');

            // 추천 관련 상세 정보 (JSON)
            $table->json('referral_details')->nullable()->comment('추천 상세 정보');
            // 추천 상세 정보 구조 예시:
            // {
            //   "referrer_name": "김파트너",
            //   "referrer_contact": "010-1234-5678",
            //   "meeting_location": "서울 강남구 카페",
            //   "meeting_date": "2025-11-01",
            //   "introduction_method": "지인 소개",
            //   "motivation": "파트너 사업에 관심이 있어서",
            //   "referrer_relationship": "회사 동료",
            //   "referral_campaign_id": "CAMPAIGN_2025_11",
            //   "promotional_material": "파트너십 브로셔 v2.1"
            // }

            // 계층 구조 예상 정보
            $table->integer('expected_tier_level')->nullable()->comment('예상 계층 레벨');
            $table->string('expected_tier_path', 500)->nullable()->comment('예상 계층 경로');
            $table->decimal('expected_commission_rate', 5, 2)->nullable()->comment('예상 기본 커미션율');

            // 추천 보너스 관련
            $table->boolean('referral_bonus_eligible')->default(true)->comment('추천 보너스 지급 대상 여부');
            $table->decimal('referral_bonus_amount', 10, 2)->default(0)->comment('예상 추천 보너스 금액');
            $table->timestamp('referral_registered_at')->nullable()->comment('추천 등록 일시');

            // 지원서 상태
            $table->enum('application_status', [
                'draft',        // 작성 중
                'submitted',    // 제출됨
                'reviewing',    // 검토 중
                'interview',    // 면접 예정/진행
                'approved',     // 승인됨
                'rejected',     // 거부됨
                'reapplied'     // 재신청
            ])->default('draft');

            // 개인정보 (JSON)
            $table->json('personal_info')->nullable();
            // 구조 예시:
            // {
            //   "name": "홍길동",
            //   "phone": "010-1234-5678",
            //   "address": "서울시 강남구...",
            //   "birth_year": 1990,
            //   "education_level": "대학교 졸업",
            //   "emergency_contact": {
            //     "name": "홍부모",
            //     "phone": "010-9876-5432",
            //     "relationship": "부모"
            //   }
            // }

            // 경력정보 (JSON)
            $table->json('experience_info')->nullable();
            // 구조 예시:
            // {
            //   "total_years": 5,
            //   "career_summary": "웹 개발 5년 경력...",
            //   "previous_companies": [
            //     {
            //       "company": "ABC 회사",
            //       "position": "시니어 개발자",
            //       "period": "2020-2023",
            //       "description": "주요 업무..."
            //     }
            //   ],
            //   "portfolio_url": "https://portfolio.com",
            //   "bio": "개발자 소개..."
            // }

            // 기술정보 (JSON)
            $table->json('skills_info')->nullable();
            // 구조 예시:
            // {
            //   "skills": ["PHP", "Laravel", "JavaScript", "Vue.js"],
            //   "skill_levels": {
            //     "PHP": "상급",
            //     "Laravel": "상급",
            //     "JavaScript": "중급"
            //   },
            //   "certifications": ["정보처리기사", "AWS Developer"],
            //   "languages": ["한국어(모국어)", "영어(중급)"]
            // }

            // 제출 서류 (JSON) - 파일 경로와 메타데이터
            $table->json('documents')->nullable();
            // 구조 예시:
            // {
            //   "resume": {
            //     "original_name": "이력서.pdf",
            //     "stored_path": "applications/123/resume.pdf",
            //     "file_size": 1024000,
            //     "uploaded_at": "2025-11-04T10:00:00Z"
            //   },
            //   "portfolio": {
            //     "original_name": "포트폴리오.pdf",
            //     "stored_path": "applications/123/portfolio.pdf",
            //     "file_size": 2048000,
            //     "uploaded_at": "2025-11-04T10:05:00Z"
            //   }
            // }

            // 지원 동기 및 목표
            $table->text('motivation')->nullable()->comment('지원 동기');
            $table->text('goals')->nullable()->comment('향후 목표');

            // 제출 일시
            $table->timestamp('submitted_at')->nullable()->comment('신청서 제출 일시');

            // 면접 관련
            $table->datetime('interview_date')->nullable(); // 면접 일정
            $table->text('interview_notes')->nullable(); // 면접 전 메모
            $table->json('interview_feedback')->nullable(); // 면접 평가
            // 면접 평가 구조 예시:
            // {
            //   "technical_score": 85,
            //   "communication_score": 90,
            //   "attitude_score": 88,
            //   "overall_impression": "긍정적",
            //   "strengths": ["기술 역량 우수", "커뮤니케이션 원활"],
            //   "concerns": ["경험 부족한 영역 존재"],
            //   "recommendation": "approve"
            // }

            // 승인 처리
            $table->datetime('approval_date')->nullable(); // 승인일시
            $table->unsignedBigInteger('approved_by')->nullable(); // 승인 처리자 (샤딩 환경에서 외래키 제약조건 제거)

            // 거부 처리
            $table->datetime('rejection_date')->nullable(); // 거부일시
            $table->text('rejection_reason')->nullable(); // 거부 사유
            $table->unsignedBigInteger('rejected_by')->nullable(); // 거부 처리자 (샤딩 환경에서 외래키 제약조건 제거)

            // 관리자 메모
            $table->text('admin_notes')->nullable(); // 관리자 전용 메모

            // 근무 조건 희망사항
            $table->decimal('expected_hourly_rate', 8, 2)->nullable(); // 희망 시급
            $table->json('preferred_work_areas')->nullable(); // 선호 근무 지역
            // 근무 지역 구조 예시:
            // {
            //   "regions": ["서울", "경기"],
            //   "districts": ["강남구", "서초구", "송파구"],
            //   "max_distance_km": 30,
            //   "transport_preference": ["지하철", "버스"]
            // }

            $table->json('availability_schedule')->nullable(); // 근무 가능 시간
            // 근무 시간 구조 예시:
            // {
            //   "weekdays": {
            //     "monday": {"start": "09:00", "end": "18:00", "available": true},
            //     "tuesday": {"start": "09:00", "end": "18:00", "available": true},
            //     ...
            //   },
            //   "weekend": {
            //     "saturday": {"available": false},
            //     "sunday": {"available": false}
            //   },
            //   "holiday_work": true,
            //   "overtime_available": true
            // }

            // 재신청 관련
            $table->unsignedBigInteger('previous_application_id')->nullable(); // 이전 지원서 ID (재신청 시)
            $table->foreign('previous_application_id')->references('id')->on('partner_applications')->onDelete('set null');
            $table->text('reapplication_reason')->nullable(); // 재신청 사유

            // 성능 최적화를 위한 인덱스
            $table->index(['user_id', 'application_status']); // 사용자별 상태 조회용
            $table->index(['application_status', 'created_at']); // 상태별 정렬용
            $table->index(['interview_date']); // 면접 일정 조회용
            $table->index(['approval_date']); // 승인일 조회용
            $table->index(['expected_hourly_rate']); // 시급 검색용

            // 추천 관련 인덱스
            $table->index(['referrer_partner_id']); // 추천 파트너별 조회용
            $table->index(['referral_code']); // 추천 코드 검색용
            $table->index(['referral_source']); // 추천 경로별 조회용
            $table->index(['referrer_partner_id', 'application_status']); // 추천자별 지원서 상태 조회용
            $table->index(['referral_registered_at']); // 추천 등록일 정렬용
            $table->index(['referral_bonus_eligible', 'application_status']); // 보너스 대상별 상태 조회용

            // 제약조건
            $table->unique(['user_id'], 'unique_active_application'); // 사용자당 하나의 활성 지원서만 허용 (소프트 삭제 고려)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_applications');
    }
};