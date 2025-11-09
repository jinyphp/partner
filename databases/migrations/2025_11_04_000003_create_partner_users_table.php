<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 파트너 회원 관리 테이블 생성 (통합 버전)
     *
     * 파트너로 등록된 사용자들을 관리하며,
     * 각 파트너는 특정 등급(partner_tier)에 할당됨
     * user_0xx 샤딩 테이블에서 이메일로 검색하여 등록 가능
     *
     * 통합 기능:
     * - 파트너 타입 관리 (partner_type_id)
     * - 계층구조 관리 (parent_id, level, tree_path)
     * - 네트워크 관리 (commission rates, management)
     * - 실적 관리 (sales tracking, commissions)
     * - 상태 관리 (recruitment, activity tracking)
     */
    public function up(): void
    {
        Schema::create('partner_users', function (Blueprint $table) {
            // 기본 필드
            $table->id(); // 파트너 회원 고유 ID
            $table->timestamps(); // 생성일시, 수정일시
            $table->softDeletes(); // 소프트 삭제 지원

            // 사용자 정보 (샤딩 지원)
            $table->unsignedBigInteger('user_id'); // 사용자 ID
            $table->string('user_table', 20)->default('users'); // 사용자 테이블명 (users_001, users_002 등)
            $table->string('user_uuid', 36)->nullable(); // 사용자 UUID
            $table->unsignedTinyInteger('shard_number')->nullable(); // 샤딩 번호 (001, 002 등)
            $table->string('email', 191); // 사용자 이메일 (검색용 캐시)
            $table->string('name', 100); // 사용자 이름 (캐시)

            // 파트너 등급 정보
            $table->foreignId('partner_tier_id')->constrained('partner_tiers')->onDelete('cascade');

            // 파트너 타입 정보 (전문성 구분: 세일즈, 기술지원, 마케팅 등)
            $table->foreignId('partner_type_id')
                  ->nullable()
                  ->constrained('partner_types')
                  ->onDelete('set null'); // 타입 삭제 시 NULL로 설정

            // ====================================================================
            // 계층구조 관리 (Hierarchy Management)
            // ====================================================================
            $table->unsignedBigInteger('parent_id')->nullable()->comment('상위 파트너 ID');
            $table->integer('level')->default(0)->comment('계층 깊이 (0=최상위)');
            $table->string('tree_path', 500)->nullable()->comment('트리 경로 (예: 1/5/12)');
            $table->integer('children_count')->default(0)->comment('직접 하위 파트너 수');
            $table->integer('total_children_count')->default(0)->comment('전체 하위 파트너 수');

            // ====================================================================
            // 네트워크 관리 (Network Management)
            // ====================================================================
            $table->integer('max_children')->nullable()->comment('관리 가능한 최대 하위 파트너 수');
            $table->decimal('personal_commission_rate', 5, 2)->default(0)->comment('개인 커미션율 (%)');
            $table->decimal('management_bonus_rate', 5, 2)->default(0)->comment('관리 보너스율 (%)');
            $table->decimal('discount_rate', 5, 2)->default(0)->comment('할인율 (%)');

            // ====================================================================
            // 실적 관리 (Sales & Performance Tracking)
            // ====================================================================
            $table->decimal('monthly_sales', 15, 2)->default(0)->comment('월간 매출');
            $table->decimal('total_sales', 15, 2)->default(0)->comment('총 매출');
            $table->decimal('team_sales', 15, 2)->default(0)->comment('팀 매출');
            $table->decimal('earned_commissions', 15, 2)->default(0)->comment('획득 커미션');

            // ====================================================================
            // 상태 관리 (Status & Activity Management)
            // ====================================================================
            $table->boolean('can_recruit')->default(true)->comment('모집 가능 여부');
            $table->timestamp('last_activity_at')->nullable()->comment('마지막 활동 시간');
            $table->json('network_settings')->nullable()->comment('네트워크 설정');
            // 네트워크 설정 구조 예시:
            // {
            //   "auto_assign_leads": true,
            //   "commission_sharing": {
            //     "enabled": true,
            //     "share_rate": 0.1
            //   },
            //   "recruitment_settings": {
            //     "max_monthly_recruits": 10,
            //     "approval_required": false
            //   }
            // }

            // 파트너 상태
            $table->enum('status', ['active', 'inactive', 'suspended', 'pending'])->default('pending');
            $table->text('status_reason')->nullable(); // 상태 변경 사유

            // 파트너 성과 정보
            $table->integer('total_completed_jobs')->default(0); // 총 완료 작업 수
            $table->decimal('average_rating', 3, 2)->default(0); // 평균 평점 (0.00 ~ 5.00)
            $table->decimal('punctuality_rate', 5, 2)->default(0); // 시간 준수율 (%)
            $table->decimal('satisfaction_rate', 5, 2)->default(0); // 고객 만족도 (%)

            // 파트너 가입 정보
            $table->date('partner_joined_at'); // 파트너 가입일
            $table->date('tier_assigned_at'); // 현재 등급 할당일
            $table->date('last_performance_review_at')->nullable(); // 마지막 성과 평가일

            // 추가 정보 (JSON)
            $table->json('profile_data')->nullable();
            // 프로필 추가 정보 구조 예시:
            // {
            //   "specializations": ["웹개발", "모바일앱"],
            //   "certifications": ["정보처리기사", "AWS Developer"],
            //   "experience_years": 5,
            //   "preferred_locations": ["서울", "경기"],
            //   "available_hours": "09:00-18:00",
            //   "phone": "010-1234-5678",
            //   "portfolio_url": "https://portfolio.com",
            //   "bio": "웹 개발 전문가입니다."
            // }

            // 관리자 메모
            $table->text('admin_notes')->nullable(); // 관리자 메모
            $table->unsignedBigInteger('created_by')->nullable(); // 등록한 관리자 ID
            $table->unsignedBigInteger('updated_by')->nullable(); // 수정한 관리자 ID

            // 성능 최적화를 위한 인덱스
            $table->index(['status', 'partner_tier_id']); // 상태별, 등급별 조회용
            $table->index(['email']); // 이메일 검색용
            $table->index(['user_id', 'user_table']); // 사용자 조회용
            $table->index(['partner_joined_at']); // 가입일 정렬용
            $table->index(['total_completed_jobs', 'average_rating']); // 성과 조회용

            // 파트너 타입 관련 인덱스
            $table->index(['partner_type_id', 'status']); // 타입별, 상태별 조회용
            $table->index(['partner_tier_id', 'partner_type_id']); // 등급+타입 조합 조회용

            // 계층구조 관련 인덱스
            $table->index(['parent_id', 'level']); // 계층 조회용
            $table->index(['tree_path']); // 트리 경로 조회용
            $table->index(['partner_tier_id', 'level']); // 등급별 레벨 조회용
            $table->index(['can_recruit', 'children_count']); // 모집 권한 및 하위 수 조회용

            // 외래키 제약조건
            $table->foreign('parent_id')->references('id')->on('partner_users')->onDelete('set null');

            // 유니크 제약조건
            $table->unique(['user_id', 'user_table']); // 동일 사용자 중복 등록 방지
            $table->index(['user_uuid']); // UUID 검색용 인덱스
            $table->index(['shard_number']); // 샤딩 번호 인덱스
        });

        // 기존 파트너들에게 기본 타입 할당
        $this->assignDefaultTypesToExistingPartners();
    }

    /**
     * 기존 파트너들에게 기본 타입 할당
     */
    private function assignDefaultTypesToExistingPartners(): void
    {
        // 기본 타입 가져오기 (세일즈 파트너)
        $defaultType = DB::table('partner_types')
            ->where('type_code', 'SALES')
            ->where('is_active', true)
            ->first();

        if ($defaultType) {
            // 타입이 없는 기존 파트너들에게 기본 타입 할당
            DB::table('partner_users')
                ->whereNull('partner_type_id')
                ->update([
                    'partner_type_id' => $defaultType->id,
                    'updated_at' => now()
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_users');
    }
};