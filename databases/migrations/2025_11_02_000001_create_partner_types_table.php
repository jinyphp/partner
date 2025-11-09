<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 파트너 타입 관리 테이블 생성
     *
     * 파트너의 전문성과 역할을 구분하기 위한 타입 시스템
     * 세일즈, 기술지원, 마케팅 등의 전문 분야별 파트너 관리
     */
    public function up(): void
    {
        Schema::create('partner_types', function (Blueprint $table) {
            // 기본 필드
            $table->id(); // 파트너 타입 고유 ID
            $table->timestamps(); // 생성일시, 수정일시
            $table->softDeletes(); // 소프트 삭제 지원

            // 타입 기본 정보
            $table->string('type_code', 20)->unique(); // 타입 코드 (SALES, TECH_SUPPORT, MARKETING 등)
            $table->string('type_name', 100); // 타입 이름
            $table->text('description')->nullable(); // 타입 설명
            $table->string('icon', 50)->nullable(); // 아이콘 클래스명

            // 타입 설정
            $table->boolean('is_active')->default(true); // 활성 상태
            $table->integer('sort_order')->default(0); // 정렬 순서
            $table->string('color', 7)->default('#007bff'); // 표시 색상 (HEX)

            // 전문성 설정
            $table->json('specialties')->nullable(); // 전문 분야 목록
            // 예시: ["sales", "lead_generation", "closing"]

            $table->json('required_skills')->nullable(); // 필수 스킬
            // 예시: ["communication", "negotiation", "product_knowledge"]

            $table->json('certifications')->nullable(); // 관련 자격증
            // 예시: ["sales_certification", "technical_support_certification"]

            // 성과 기준
            $table->decimal('target_sales_amount', 15, 2)->default(0); // 목표 매출액
            $table->integer('target_support_cases')->default(0); // 목표 지원 건수
            $table->decimal('commission_bonus_rate', 5, 2)->default(0); // 추가 수수료율 (%)

            // 업무 권한
            $table->json('permissions')->nullable(); // 타입별 권한 설정
            // 예시: ["can_handle_enterprise", "can_provide_technical_support"]

            $table->json('access_levels')->nullable(); // 접근 레벨
            // 예시: ["level_1_support", "level_2_support", "level_3_support"]

            // 교육 및 인증
            $table->json('training_requirements')->nullable(); // 교육 요구사항
            $table->integer('training_hours_required')->default(0); // 필수 교육 시간
            $table->date('certification_valid_until')->nullable(); // 인증 유효 기간

            // 관리 정보
            $table->text('admin_notes')->nullable(); // 관리자 메모
            $table->unsignedBigInteger('created_by')->nullable(); // 생성한 관리자 ID
            $table->unsignedBigInteger('updated_by')->nullable(); // 수정한 관리자 ID

            // 인덱스
            $table->index(['is_active', 'sort_order']); // 활성 상태별, 정렬용
            $table->index(['type_code']); // 타입 코드 검색용
            $table->index(['created_by']); // 생성자별 조회
            $table->index(['updated_by']); // 수정자별 조회
        });

        // 기본 파트너 타입 데이터 삽입
        $this->insertDefaultPartnerTypes();
    }

    /**
     * 기본 파트너 타입 데이터 삽입
     */
    private function insertDefaultPartnerTypes(): void
    {
        $now = now();

        $partnerTypes = [
            [
                'type_code' => 'SALES',
                'type_name' => '세일즈 파트너',
                'description' => '고객 발굴 및 영업 활동에 특화된 파트너입니다. 신규 고객 획득과 매출 증대에 집중합니다.',
                'icon' => 'fe-trending-up',
                'color' => '#28a745',
                'sort_order' => 1,
                'specialties' => json_encode(['sales', 'lead_generation', 'closing', 'customer_relations']),
                'required_skills' => json_encode(['communication', 'negotiation', 'product_knowledge', 'crm_usage']),
                'certifications' => json_encode(['영업 전문가 자격증', '고객관리 전문가']),
                'target_sales_amount' => 5000000,
                'target_support_cases' => 50,
                'commission_bonus_rate' => 2.5,
                'permissions' => json_encode(['can_handle_enterprise', 'can_access_sales_tools', 'can_view_customer_data']),
                'access_levels' => json_encode(['level_1_sales', 'level_2_sales']),
                'training_requirements' => json_encode(['세일즈 기초 교육', '제품 지식 교육', '고객 응대 교육']),
                'training_hours_required' => 40,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type_code' => 'TECH_SUPPORT',
                'type_name' => '기술 지원 파트너',
                'description' => '기술적 문제 해결과 고객 지원에 전문성을 가진 파트너입니다. 제품 설치, 설정, 문제 해결을 담당합니다.',
                'icon' => 'fe-tool',
                'color' => '#007bff',
                'sort_order' => 2,
                'specialties' => json_encode(['technical_support', 'problem_solving', 'installation', 'configuration']),
                'required_skills' => json_encode(['technical_knowledge', 'troubleshooting', 'documentation', 'customer_service']),
                'certifications' => json_encode(['기술지원 전문가', 'IT 서비스 관리사']),
                'target_sales_amount' => 2000000,
                'target_support_cases' => 100,
                'commission_bonus_rate' => 1.5,
                'permissions' => json_encode(['can_provide_technical_support', 'can_access_support_tools', 'can_escalate_issues']),
                'access_levels' => json_encode(['level_1_support', 'level_2_support']),
                'training_requirements' => json_encode(['기술 기초 교육', '제품 기술 교육', '고객 지원 절차']),
                'training_hours_required' => 60,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type_code' => 'MARKETING',
                'type_name' => '마케팅 파트너',
                'description' => '브랜드 홍보와 마케팅 캠페인 실행에 특화된 파트너입니다. 온라인/오프라인 마케팅 활동을 담당합니다.',
                'icon' => 'fe-megaphone',
                'color' => '#ff6b35',
                'sort_order' => 3,
                'specialties' => json_encode(['digital_marketing', 'content_creation', 'social_media', 'campaign_management']),
                'required_skills' => json_encode(['creative_thinking', 'content_writing', 'social_media_management', 'analytics']),
                'certifications' => json_encode(['디지털 마케팅 전문가', '소셜미디어 마케팅 자격증']),
                'target_sales_amount' => 3000000,
                'target_support_cases' => 30,
                'commission_bonus_rate' => 2.0,
                'permissions' => json_encode(['can_create_content', 'can_manage_campaigns', 'can_access_analytics']),
                'access_levels' => json_encode(['content_creator', 'campaign_manager']),
                'training_requirements' => json_encode(['마케팅 전략 교육', '콘텐츠 제작 교육', '소셜미디어 활용']),
                'training_hours_required' => 35,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type_code' => 'TRAINING',
                'type_name' => '교육 파트너',
                'description' => '제품 교육과 고객 트레이닝을 전담하는 파트너입니다. 온보딩부터 고급 사용법까지 교육을 제공합니다.',
                'icon' => 'fe-book-open',
                'color' => '#6f42c1',
                'sort_order' => 4,
                'specialties' => json_encode(['training', 'education', 'curriculum_development', 'assessment']),
                'required_skills' => json_encode(['presentation', 'instructional_design', 'patience', 'knowledge_transfer']),
                'certifications' => json_encode(['교육 전문가', '강사 자격증']),
                'target_sales_amount' => 1500000,
                'target_support_cases' => 80,
                'commission_bonus_rate' => 1.8,
                'permissions' => json_encode(['can_conduct_training', 'can_create_materials', 'can_assess_progress']),
                'access_levels' => json_encode(['basic_trainer', 'advanced_trainer']),
                'training_requirements' => json_encode(['교수법 교육', '제품 전문 교육', '평가 방법론']),
                'training_hours_required' => 50,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type_code' => 'CONSULTANT',
                'type_name' => '컨설턴트 파트너',
                'description' => '비즈니스 컨설팅과 전략적 조언을 제공하는 파트너입니다. 고객의 비즈니스 성장을 위한 맞춤형 솔루션을 제안합니다.',
                'icon' => 'fe-users',
                'color' => '#20c997',
                'sort_order' => 5,
                'specialties' => json_encode(['business_consulting', 'strategy_planning', 'process_optimization', 'roi_analysis']),
                'required_skills' => json_encode(['analytical_thinking', 'business_acumen', 'project_management', 'client_relationship']),
                'certifications' => json_encode(['경영 컨설턴트', '프로젝트 관리 전문가']),
                'target_sales_amount' => 8000000,
                'target_support_cases' => 25,
                'commission_bonus_rate' => 3.0,
                'permissions' => json_encode(['can_access_enterprise_data', 'can_propose_solutions', 'can_manage_projects']),
                'access_levels' => json_encode(['junior_consultant', 'senior_consultant']),
                'training_requirements' => json_encode(['컨설팅 방법론', '비즈니스 분석', '프로젝트 관리']),
                'training_hours_required' => 80,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type_code' => 'CUSTOMER_SERVICE',
                'type_name' => '고객 서비스 파트너',
                'description' => '고객 문의 대응과 사후 관리에 전문성을 가진 파트너입니다. 고객 만족도 향상과 관계 유지를 담당합니다.',
                'icon' => 'fe-headphones',
                'color' => '#ffc107',
                'sort_order' => 6,
                'specialties' => json_encode(['customer_service', 'complaint_handling', 'relationship_management', 'follow_up']),
                'required_skills' => json_encode(['empathy', 'communication', 'problem_solving', 'patience']),
                'certifications' => json_encode(['고객서비스 전문가', '고객관계관리 자격증']),
                'target_sales_amount' => 1000000,
                'target_support_cases' => 120,
                'commission_bonus_rate' => 1.2,
                'permissions' => json_encode(['can_handle_complaints', 'can_access_customer_history', 'can_escalate_issues']),
                'access_levels' => json_encode(['customer_service_rep', 'senior_service_rep']),
                'training_requirements' => json_encode(['고객 서비스 교육', '불만 처리 방법', '커뮤니케이션 스킬']),
                'training_hours_required' => 30,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ];

        // DB 삽입 (안전한 삽입을 위한 try-catch)
        try {
            DB::beginTransaction();

            foreach ($partnerTypes as $partnerType) {
                DB::table('partner_types')->insert($partnerType);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to insert default partner types: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_types');
    }
};