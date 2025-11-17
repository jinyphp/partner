<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 파트너 지원서 관리 테이블 생성
     *
     * =======================================================================
     * 📋 테이블 개요
     * =======================================================================
     * 파트너 지원자들의 신청서를 관리하는 핵심 테이블입니다.
     * 추천 기반 파트너십 시스템과 단계별 승인 프로세스를 지원합니다.
     *
     * =======================================================================
     * 🎯 핵심 기능
     * =======================================================================
     * ✓ 파트너 지원서 작성 및 제출 관리
     * ✓ 추천인 기반 계층 구조 및 MLM 지원
     * ✓ 다양한 추천 경로 추적 (9가지 채널)
     * ✓ 관리자 심사 및 면접 관리
     * ✓ 재신청 시스템 및 이력 관리
     * ✓ 추천 보너스 자동 계산
     * ✓ 샤딩 환경 대응
     *
     * =======================================================================
     * 🔄 워크플로우
     * =======================================================================
     * 1. 지원자가 추천 코드로 신청서 작성 (draft)
     * 2. 신청서 제출 (submitted)
     * 3. 관리자 1차 검토 (reviewing)
     * 4. 면접 진행 (interview)
     * 5. 최종 승인/거부 (approved/rejected)
     * 6. 거부 시 재신청 가능 (reapplied)
     *
     * =======================================================================
     * 🔗 테이블 관계
     * =======================================================================
     * • users → partner_applications (1:N) : 지원자 정보
     * • partner_users → partner_applications (1:N) : 추천 파트너
     * • partner_applications → partner_applications (1:N) : 재신청 관계
     * • partner_types → partner_applications (1:N) : 지원 파트너 타입
     *
     * =======================================================================
     * 💰 추천 시스템
     * =======================================================================
     * • 9가지 추천 경로 지원 (직접, 온라인, SNS, 이벤트 등)
     * • 추천 코드 기반 캠페인 추적
     * • 계층 구조 사전 계산
     * • 추천 보너스 자동 지급
     */
    public function up(): void
    {
        Schema::create('partner_applications', function (Blueprint $table) {
            // =============================================================
            // 🆔 기본 시스템 필드
            // =============================================================
            $table->id()->comment('지원서 고유 식별자');
            $table->timestamps();
            $table->softDeletes()->comment('논리 삭제 지원 (복구 가능)');

            // =============================================================
            // 👤 지원자 기본 정보
            // =============================================================
            $table->unsignedBigInteger('user_id')->comment('지원자 사용자 ID');
            $table->string('user_uuid')->nullable()->index()->comment('사용자 UUID (샤딩 환경 지원)');
            $table->integer('shard_number')->nullable()->comment('데이터 샤드 번호');

            // =============================================================
            // 📊 지원서 상태 관리
            // =============================================================
            $table->enum('application_status', [
                'draft',        // 📝 작성 중 (임시저장)
                'submitted',    // 📤 제출 완료
                'reviewing',    // 👀 관리자 검토 중
                'interview',    // 🎤 면접 단계
                'approved',     // ✅ 승인 완료
                'rejected',     // ❌ 거부됨
                'reapplied'     // 🔄 재신청됨
            ])->default('draft')->comment('지원서 처리 상태');

            $table->timestamp('submitted_at')->nullable()->comment('실제 제출 완료 시간 (draft 상태와 구분)');

            // =============================================================
            // 🤝 추천인 및 추천 시스템
            // =============================================================

            // 추천 파트너 연결
            $table->unsignedBigInteger('referrer_partner_id')->nullable()->comment('추천한 파트너 ID');
            $table->foreign('referrer_partner_id')->references('id')->on('partner_users')->onDelete('set null');

            // 추천 경로 및 코드
            $table->string('referral_code', 50)->nullable()->index()->comment('사용된 추천 코드');
            $table->enum('referral_source', [
                'direct',           // 👥 직접 추천 (대면)
                'online_link',      // 🌐 온라인 링크
                'offline_meeting',  // ☕ 오프라인 미팅
                'social_media',     // 📱 소셜미디어 (SNS)
                'event',           // 🎪 이벤트/세미나
                'advertisement',   // 📺 광고 캠페인
                'word_of_mouth',   // 💬 입소문
                'self_application', // 🙋 자발적 지원
                'other'            // 🔧 기타
            ])->nullable()->default('self_application')->comment('추천 유입 경로');

            // 추천인 상세 정보 (MLM 지원)
            $table->string('referrer_name', 100)->nullable()->comment('추천인 성명');
            $table->string('referrer_contact', 100)->nullable()->comment('추천인 연락처 (전화/이메일)');
            $table->string('referrer_relationship', 100)->nullable()->comment('추천인과의 관계 (동료, 친구 등)');
            $table->date('meeting_date')->nullable()->comment('추천인과의 만남 일자');
            $table->string('meeting_location', 255)->nullable()->comment('만남 장소');
            $table->string('introduction_method', 255)->nullable()->comment('소개 방식 및 경위');

            // 추천 상세 정보 (JSON 저장)
            $table->json('referral_details')->nullable()->comment('추천 관련 추가 상세 정보 (JSON)');
            /*
             * referral_details JSON 구조:
             * {
             *   "campaign_id": "SUMMER_2025_PROMO",      // 캠페인 식별자
             *   "promotional_material": "브로셔 v2.1",    // 홍보 자료
             *   "referrer_notes": "적극적인 지원 의사",   // 추천인 메모
             *   "meeting_notes": "카페에서 2시간 상담",   // 미팅 내용
             *   "follow_up_required": true,               // 후속 조치 필요
             *   "referrer_commission_tier": "gold"        // 추천인 등급
             * }
             */

            // =============================================================
            // 🏗️ 계층 구조 및 수수료 예상 정보
            // =============================================================
            $table->integer('expected_tier_level')->nullable()->comment('예상 파트너 계층 레벨 (1=최상위)');
            $table->string('expected_tier_path', 500)->nullable()->comment('예상 계층 경로 (1-2-3-4...)');
            $table->decimal('expected_commission_rate', 5, 2)->nullable()->comment('예상 기본 수수료율 (%)');

            // 추천 보너스 관련
            $table->boolean('referral_bonus_eligible')->default(true)->comment('추천 보너스 지급 대상 여부');
            $table->decimal('referral_bonus_amount', 10, 2)->default(0)->comment('계산된 추천 보너스 금액');
            $table->timestamp('referral_registered_at')->nullable()->comment('추천 등록 완료 일시');

            // =============================================================
            // 📋 지원자 개인 정보 (JSON 구조)
            // =============================================================

            // 기본 개인 정보
            $table->json('personal_info')->nullable()->comment('지원자 개인 정보 (JSON)');
            $table->string('country', 2)->default('KR')->comment('국가 코드 (KR, US, JP, CN 등)');
            /*
             * personal_info JSON 구조:
             * {
             *   "name": "홍길동",
             *   "phone": "010-1234-5678",
             *   "email": "hong@example.com",
             *   "country": "KR", // 국가 코드 (별도 컬럼에도 저장됨)
             *   "address": {
             *     "postal_code": "12345",
             *     "address_line1": "서울시 강남구 테헤란로 123",
             *     "address_line2": "ABC빌딩 456호",
             *     "country": "KR"
             *   },
             *   "birth_year": 1990,
             *   "gender": "male",
             *   "education_level": "대학교 졸업",
             *   "emergency_contact": {
             *     "name": "홍부모",
             *     "phone": "010-9876-5432",
             *     "relationship": "부모"
             *   }
             * }
             */

            // 경력 및 전문성 정보
            $table->json('experience_info')->nullable()->comment('경력 및 업무 경험 정보 (JSON)');
            /*
             * experience_info JSON 구조:
             * {
             *   "total_years": 5,
             *   "current_status": "재직중",
             *   "career_summary": "웹 개발 및 시스템 설계 5년 경력",
             *   "previous_companies": [
             *     {
             *       "company": "ABC 테크놀러지",
             *       "position": "시니어 개발자",
             *       "period": "2020-2023",
             *       "responsibilities": ["웹 앱 개발", "팀 리딩", "고객 지원"],
             *       "achievements": ["매출 20% 증가에 기여", "신규 서비스 런칭"]
             *     }
             *   ],
             *   "portfolio_url": "https://portfolio.com",
             *   "linkedin_url": "https://linkedin.com/in/hong",
             *   "bio": "성장하는 스타트업에서 함께 일하고 싶습니다."
             * }
             */

            // 기술 및 스킬 정보
            $table->json('skills_info')->nullable()->comment('기술 스킬 및 역량 정보 (JSON)');
            /*
             * skills_info JSON 구조:
             * {
             *   "technical_skills": ["PHP", "Laravel", "JavaScript", "Vue.js", "MySQL"],
             *   "skill_levels": {
             *     "PHP": "상급",
             *     "Laravel": "상급",
             *     "JavaScript": "중급",
             *     "Vue.js": "중급"
             *   },
             *   "certifications": [
             *     {"name": "정보처리기사", "date": "2020-06", "issuer": "한국산업인력공단"},
             *     {"name": "AWS Developer Associate", "date": "2021-03", "issuer": "Amazon"}
             *   ],
             *   "languages": [
             *     {"language": "한국어", "level": "모국어"},
             *     {"language": "영어", "level": "비즈니스 회화"}
             *   ]
             * }
             */

            // 제출 서류 관리
            $table->json('documents')->nullable()->comment('제출 서류 파일 정보 (JSON)');
            /*
             * documents JSON 구조:
             * {
             *   "resume": {
             *     "original_name": "이력서_홍길동.pdf",
             *     "stored_path": "applications/123/resume.pdf",
             *     "file_size": 1024000,
             *     "mime_type": "application/pdf",
             *     "uploaded_at": "2025-11-04T10:00:00Z"
             *   },
             *   "portfolio": {
             *     "original_name": "포트폴리오.pdf",
             *     "stored_path": "applications/123/portfolio.pdf",
             *     "file_size": 2048000,
             *     "mime_type": "application/pdf",
             *     "uploaded_at": "2025-11-04T10:05:00Z"
             *   },
             *   "certificates": [
             *     {
             *       "original_name": "정보처리기사_자격증.jpg",
             *       "stored_path": "applications/123/cert_1.jpg",
             *       "file_size": 512000,
             *       "mime_type": "image/jpeg",
             *       "uploaded_at": "2025-11-04T10:10:00Z"
             *     }
             *   ]
             * }
             */

            // =============================================================
            // 📝 지원 동기 및 목표
            // =============================================================
            $table->text('motivation')->nullable()->comment('지원 동기 및 개선사항');
            $table->text('goals')->nullable()->comment('파트너십 목표 및 향후 계획');

            // 재신청 관련 필드
            $table->text('improvement_plan')->nullable()->comment('개선 계획 (재신청 시 필수 작성)');
            $table->text('project_experience')->nullable()->comment('추가 프로젝트 경험 및 성과');

            // =============================================================
            // 🎯 근무 조건 및 선호도
            // =============================================================
            $table->decimal('expected_hourly_rate', 8, 2)->nullable()->comment('희망 시간당 수수료 (원)');

            // 선호 근무 지역
            $table->json('preferred_work_areas')->nullable()->comment('선호 근무 지역 설정 (JSON)');
            /*
             * preferred_work_areas JSON 구조:
             * {
             *   "regions": ["서울", "경기"],
             *   "districts": ["강남구", "서초구", "송파구"],
             *   "max_distance_km": 30,
             *   "transport_preference": ["지하철", "버스"],
             *   "remote_work_available": true
             * }
             */

            // 근무 가능 시간
            $table->json('availability_schedule')->nullable()->comment('근무 가능 시간표 (JSON)');
            /*
             * availability_schedule JSON 구조:
             * {
             *   "weekdays": {
             *     "monday": {"start": "09:00", "end": "18:00", "available": true},
             *     "tuesday": {"start": "09:00", "end": "18:00", "available": true},
             *     "wednesday": {"start": "09:00", "end": "18:00", "available": true},
             *     "thursday": {"start": "09:00", "end": "18:00", "available": true},
             *     "friday": {"start": "09:00", "end": "18:00", "available": true}
             *   },
             *   "weekend": {
             *     "saturday": {"start": "10:00", "end": "15:00", "available": false},
             *     "sunday": {"available": false}
             *   },
             *   "holiday_work": true,
             *   "overtime_available": true,
             *   "timezone": "Asia/Seoul"
             * }
             */

            // =============================================================
            // 🎤 면접 및 평가 관리
            // =============================================================
            $table->datetime('interview_date')->nullable()->comment('면접 일정');
            $table->text('interview_notes')->nullable()->comment('면접 전 관리자 메모');

            // 면접 평가 결과
            $table->json('interview_feedback')->nullable()->comment('면접 평가 결과 (JSON)');
            /*
             * interview_feedback JSON 구조:
             * {
             *   "interviewer": "김관리자",
             *   "interview_duration": 60,
             *   "scores": {
             *     "technical_score": 85,
             *     "communication_score": 90,
             *     "attitude_score": 88,
             *     "motivation_score": 92
             *   },
             *   "overall_impression": "긍정적",
             *   "strengths": ["기술 역량 우수", "커뮤니케이션 능력 뛰어남"],
             *   "concerns": ["경험 부족한 업무 영역 존재"],
             *   "recommendation": "approve",
             *   "notes": "즉시 파트너 등록 추천"
             * }
             */

            // =============================================================
            // ⚖️ 승인/거부 처리
            // =============================================================

            // 승인 관련
            $table->datetime('approval_date')->nullable()->comment('승인 처리 일시');
            $table->unsignedBigInteger('approved_by')->nullable()->comment('승인 처리자 (관리자 ID)');

            // 거부 관련
            $table->datetime('rejection_date')->nullable()->comment('거부 처리 일시');
            $table->text('rejection_reason')->nullable()->comment('거부 사유 (구체적 피드백)');
            $table->unsignedBigInteger('rejected_by')->nullable()->comment('거부 처리자 (관리자 ID)');

            // =============================================================
            // 🔄 재신청 시스템
            // =============================================================
            $table->unsignedBigInteger('previous_application_id')->nullable()->comment('이전 지원서 ID (재신청 시)');
            $table->foreign('previous_application_id')->references('id')->on('partner_applications')->onDelete('set null');
            $table->text('reapplication_reason')->nullable()->comment('재신청 사유 및 개선 내용');

            // =============================================================
            // 🔧 관리자 도구
            // =============================================================

            // 처리 담당자 정보
            $table->unsignedBigInteger('assigned_reviewer_id')->nullable()->comment('배정된 검토자 ID');
            $table->timestamp('assigned_at')->nullable()->comment('배정 시간');
            $table->timestamp('review_started_at')->nullable()->comment('검토 시작 시간');
            $table->timestamp('review_deadline')->nullable()->comment('검토 마감일');

            // 우선순위 및 태그
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal')->comment('우선순위');
            $table->json('tags')->nullable()->comment('태그들');

            // 외부 시스템 연동
            $table->string('external_application_id')->nullable()->comment('외부 시스템 신청서 ID');
            $table->json('external_data')->nullable()->comment('외부 시스템 데이터');

            $table->text('admin_notes')->nullable()->comment('관리자 전용 내부 메모');

            // =============================================================
            // 📈 성능 최적화 인덱스
            // =============================================================

            // 주요 쿼리 최적화
            $table->index(['user_id', 'application_status'], 'idx_user_status');
            $table->index(['application_status', 'created_at'], 'idx_status_created');
            $table->index(['submitted_at'], 'idx_submitted_at');

            // 면접 관련 인덱스
            $table->index(['interview_date'], 'idx_interview_date');
            $table->index(['approval_date'], 'idx_approval_date');

            // 추천 시스템 인덱스
            $table->index(['referrer_partner_id'], 'idx_referrer_partner');
            $table->index(['referral_code'], 'idx_referral_code');
            $table->index(['referral_source'], 'idx_referral_source');
            $table->index(['referrer_partner_id', 'application_status'], 'idx_referrer_status');
            $table->index(['referral_registered_at'], 'idx_referral_registered');
            $table->index(['referral_bonus_eligible', 'application_status'], 'idx_bonus_eligible_status');

            // 재신청 관련 인덱스
            $table->index(['previous_application_id', 'application_status'], 'idx_previous_app_status');

            // 조건 검색 인덱스
            $table->index(['expected_hourly_rate'], 'idx_expected_rate');
            $table->index(['country'], 'idx_country'); // 국가별 지원자 조회

            // 관리자 도구 관련 인덱스
            $table->index(['assigned_reviewer_id', 'application_status'], 'idx_assigned_reviewer_status');
            $table->index(['priority', 'created_at'], 'idx_priority_created');
            $table->index(['review_deadline'], 'idx_review_deadline');
        });

        // =================================================================
        // 🔒 고급 제약조건 설정
        // =================================================================

        // 사용자당 활성 지원서 1개 제한 (재신청 허용)
        // 거부된 신청서가 있어도 새로운 신청 가능
        DB::statement('
            CREATE UNIQUE INDEX "unique_active_application_per_user"
            ON "partner_applications" ("user_id")
            WHERE "application_status" IN (\'submitted\', \'reviewing\', \'interview\', \'approved\')
            AND "deleted_at" IS NULL
        ');

        // 재신청 쿼리 성능 최적화 인덱스
        DB::statement('
            CREATE INDEX "partner_applications_user_status_deleted"
            ON "partner_applications" ("user_id", "application_status", "deleted_at")
        ');

        // 추천 보너스 계산 최적화 인덱스
        DB::statement('
            CREATE INDEX "referral_bonus_calculation"
            ON "partner_applications" ("referrer_partner_id", "application_status", "referral_bonus_eligible")
            WHERE "application_status" = \'approved\'
            AND "referral_bonus_eligible" = true
            AND "deleted_at" IS NULL
        ');
    }

    /**
     * 테이블 삭제 및 인덱스 정리
     */
    public function down(): void
    {
        // 커스텀 인덱스 먼저 삭제
        DB::statement('DROP INDEX IF EXISTS "unique_active_application_per_user"');
        DB::statement('DROP INDEX IF EXISTS "partner_applications_user_status_deleted"');
        DB::statement('DROP INDEX IF EXISTS "referral_bonus_calculation"');

        // 테이블 삭제
        Schema::dropIfExists('partner_applications');
    }
};