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
     * =======================================================================
     * 🏷️ 테이블 개요
     * =======================================================================
     * 파트너 분류 및 역할별 특성을 정의하는 핵심 마스터 테이블입니다.
     * 각 파트너 타입별 전문성, 수수료 체계, 성과 기준을 통합 관리합니다.
     *
     * =======================================================================
     * 🎯 핵심 기능
     * =======================================================================
     * ✓ 파트너 타입 분류 시스템 (컨설턴트, 세일즈, 마케팅, 기술지원 등)
     * ✓ 타입별 맞춤형 수수료 체계 (퍼센트/고정금액)
     * ✓ 전문성 및 필수 스킬 관리
     * ✓ 성과 평가 기준 설정
     * ✓ 파트너십 비용 구조 관리
     * ✓ UI 표시 설정 (아이콘, 색상, 정렬)
     *
     * =======================================================================
     * 📊 타입별 특성
     * =======================================================================
     * • CONSULTANT: 고급 컨설팅 (10% 수수료, 높은 진입장벽)
     * • SALES: 영업 전문 (9% 수수료, 실적 중심 평가)
     * • MARKETING: 마케팅 전문 (8% 수수료, 크리에이티브 역량)
     * • TECH_SUPPORT: 기술지원 (고정 5만원/건, 안정적 서비스)
     * • TRAINING: 교육 전문 (7% 수수료, 지식 전달 능력)
     * • CUSTOMER_SERVICE: 고객 서비스 (고정 3만원/건, 관계 관리)
     *
     * =======================================================================
     * 🔗 테이블 관계
     * =======================================================================
     * • partner_types → partner_users (1:N) : 파트너별 타입 분류
     * • partner_types → partner_applications (1:N) : 지원서 타입 선택
     * • users → partner_types (관리자 추적)
     *
     * =======================================================================
     * 💰 수수료 시스템
     * =======================================================================
     * • percentage: 매출의 일정 비율 (%, 영업/컨설팅 중심)
     * • fixed_amount: 건당 고정 금액 (원, 기술지원/서비스 중심)
     * • 타입별 차등 수수료로 전문성에 따른 보상 차별화
     */
    public function up(): void
    {
        Schema::create('partner_types', function (Blueprint $table) {
            // =============================================================
            // 🆔 기본 시스템 필드
            // =============================================================
            $table->id()->comment('파트너 타입 고유 식별자');
            $table->timestamps();
            $table->softDeletes()->comment('논리 삭제 지원 (타입 보존)');

            // =============================================================
            // 🏷️ 타입 기본 정보
            // =============================================================
            $table->string('type_code', 20)->unique()->comment('타입 코드 (SALES, TECH_SUPPORT, MARKETING 등)');
            $table->string('type_name', 100)->comment('타입 표시명 (한글)');
            $table->text('description')->nullable()->comment('타입 상세 설명 및 역할');

            // =============================================================
            // 🎨 UI 표시 설정
            // =============================================================
            $table->string('icon', 50)->nullable()->comment('아이콘 클래스명 (fe-users, fe-trending-up 등)');
            $table->string('color', 7)->default('#007bff')->comment('브랜드 색상 (HEX 코드)');
            $table->integer('sort_order')->default(0)->comment('목록 정렬 순서 (낮은 숫자 우선)');
            $table->boolean('is_active')->default(true)->comment('활성 상태 (비활성시 신규 가입 불가)');
            $table->integer('partner_tiers_count')->default(0)->comment('이 타입을 허용하는 파트너 티어 수 (캐시)');

            // =============================================================
            // 🎯 전문성 및 역량 정의
            // =============================================================
            $table->json('specialties')->nullable()->comment('전문 분야 목록 (JSON 배열)');
            /*
             * specialties JSON 구조:
             * [
             *   "business_consulting",    // 비즈니스 컨설팅
             *   "strategy_planning",      // 전략 기획
             *   "process_optimization",   // 프로세스 최적화
             *   "roi_analysis"           // ROI 분석
             * ]
             */

            $table->json('required_skills')->nullable()->comment('필수 스킬 목록 (JSON 배열)');
            /*
             * required_skills JSON 구조:
             * [
             *   "analytical_thinking",    // 분석적 사고
             *   "business_acumen",       // 비즈니스 감각
             *   "project_management",    // 프로젝트 관리
             *   "client_relationship"    // 고객 관계 관리
             * ]
             */

            // =============================================================
            // 💰 수수료 체계 설정
            // =============================================================
            $table->enum('default_commission_type', ['percentage', 'fixed_amount'])
                ->default('percentage')
                ->comment('기본 수수료 타입: percentage(퍼센트) 또는 fixed_amount(고정금액)');

            $table->decimal('default_commission_rate', 5, 2)
                ->default(0)
                ->comment('기본 수수료율 (퍼센트, 0-100)');

            $table->decimal('default_commission_amount', 15, 2)
                ->default(0)
                ->comment('고정 수수료 금액 (원, fixed_amount 타입시 사용)');

            $table->text('commission_notes')
                ->nullable()
                ->comment('수수료 관련 특별 조건 및 참고사항');

            // =============================================================
            // 💳 파트너십 비용 구조
            // =============================================================
            $table->decimal('registration_fee', 15, 2)
                ->default(0)
                ->comment('파트너 등록비 (최초 1회)');

            $table->decimal('monthly_maintenance_fee', 15, 2)
                ->default(0)
                ->comment('월 유지비 (매월 정기 결제)');

            $table->decimal('annual_maintenance_fee', 15, 2)
                ->default(0)
                ->comment('연 유지비 (매년 정기 결제)');

            $table->boolean('fee_waiver_available')
                ->default(false)
                ->comment('비용 면제 가능 여부 (성과 달성시 면제 가능)');

            $table->text('fee_structure_notes')
                ->nullable()
                ->comment('비용 구조 관련 특별 조건 및 할인 정책');

            // =============================================================
            // 📈 성과 평가 기준 (최소 요구 수준)
            // =============================================================
            $table->decimal('min_baseline_sales', 15, 2)
                ->default(0)
                ->comment('최소 매출 기준 (월별, 원)');

            $table->integer('min_baseline_cases')
                ->default(0)
                ->comment('최소 처리 건수 (월별)');

            $table->decimal('min_baseline_revenue', 15, 2)
                ->default(0)
                ->comment('최소 순수익 기준 (월별, 원)');

            $table->integer('min_baseline_clients')
                ->default(0)
                ->comment('최소 고객 수 (활성 고객)');

            $table->decimal('baseline_quality_score', 5, 2)
                ->default(0)
                ->comment('최소 품질 점수 (0-100, 고객 만족도 등)');

            // =============================================================
            // 🔧 관리 정보
            // =============================================================
            $table->text('admin_notes')->nullable()->comment('관리자 전용 내부 메모');
            $table->unsignedBigInteger('created_by')->nullable()->comment('타입 생성자 (관리자 ID)');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('최종 수정자 (관리자 ID)');

            // =============================================================
            // 📊 성능 최적화 인덱스
            // =============================================================

            // 주요 조회 인덱스
            $table->index(['is_active', 'sort_order'], 'idx_active_sort');
            $table->index(['type_code'], 'idx_type_code');

            // 수수료 관련 인덱스
            $table->index(['default_commission_type'], 'idx_commission_type');
            $table->index(['default_commission_rate'], 'idx_commission_rate');

            // 비용 관련 인덱스
            $table->index(['registration_fee'], 'idx_registration_fee');
            $table->index(['monthly_maintenance_fee'], 'idx_monthly_fee');
            $table->index(['fee_waiver_available'], 'idx_fee_waiver');

            // 성과 기준 인덱스
            $table->index(['min_baseline_sales'], 'idx_min_sales');
            $table->index(['baseline_quality_score'], 'idx_quality_score');

            // 관리자 추적 인덱스
            $table->index(['created_by'], 'idx_created_by');
            $table->index(['updated_by'], 'idx_updated_by');
        });

        // 기본 파트너 타입 데이터 삽입
        $this->insertDefaultPartnerTypes();
    }

    /**
     * 기본 파트너 타입 데이터 삽입
     *
     * 6가지 표준 파트너 타입을 사전 정의하여 시스템 초기화
     */
    private function insertDefaultPartnerTypes(): void
    {
        $now = now();

        $partnerTypes = [
            // ==========================================================
            // 💼 컨설턴트 파트너 (프리미엄 등급)
            // ==========================================================
            [
                'type_code' => 'CONSULTANT',
                'type_name' => '컨설턴트 파트너',
                'description' => '비즈니스 컨설팅과 전략적 조언을 제공하는 프리미엄 파트너입니다. 고객의 비즈니스 성장을 위한 맞춤형 솔루션을 제안하고 장기적인 성공을 지원합니다.',
                'icon' => 'fe-users',
                'color' => '#20c997', // 틸(Teal) - 신뢰와 전문성
                'sort_order' => 1,
                'specialties' => json_encode([
                    'business_consulting',      // 비즈니스 컨설팅
                    'strategy_planning',        // 전략 기획
                    'process_optimization',     // 프로세스 최적화
                    'roi_analysis'             // ROI 분석
                ]),
                'required_skills' => json_encode([
                    'analytical_thinking',      // 분석적 사고력
                    'business_acumen',         // 비즈니스 감각
                    'project_management',      // 프로젝트 관리
                    'client_relationship'      // 고객 관계 관리
                ]),
                'min_baseline_sales' => 8000000,        // 월 800만원 매출
                'min_baseline_cases' => 25,             // 월 25건 처리
                'min_baseline_revenue' => 4000000,      // 월 400만원 순이익
                'min_baseline_clients' => 3,            // 최소 3개 활성 고객
                'baseline_quality_score' => 95.0,       // 95점 이상 품질
                'default_commission_type' => 'percentage',
                'default_commission_rate' => 10.00,     // 10% 프리미엄 수수료
                'commission_notes' => '고급 컨설팅 서비스에 대한 프리미엄 수수료율 적용. 프로젝트 규모에 따른 추가 보너스 가능.',
                'registration_fee' => 500000.00,        // 50만원 등록비
                'monthly_maintenance_fee' => 100000.00, // 월 10만원 유지비
                'annual_maintenance_fee' => 1000000.00, // 연 100만원 유지비
                'fee_waiver_available' => true,
                'fee_structure_notes' => '프리미엄 파트너십 비용. 연 매출 1억원 이상 달성시 비용 면제 가능.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ==========================================================
            // 📈 세일즈 파트너 (영업 전문)
            // ==========================================================
            [
                'type_code' => 'SALES',
                'type_name' => '세일즈 파트너',
                'description' => '고객 발굴 및 영업 활동에 특화된 파트너입니다. 신규 고객 획득과 매출 증대에 집중하며, 강력한 영업 네트워크를 구축합니다.',
                'icon' => 'fe-trending-up',
                'color' => '#28a745', // 녹색 - 성장과 성공
                'sort_order' => 2,
                'specialties' => json_encode([
                    'sales',                   // 영업
                    'lead_generation',         // 리드 생성
                    'closing',                // 계약 성사
                    'customer_relations'       // 고객 관계 관리
                ]),
                'required_skills' => json_encode([
                    'communication',           // 커뮤니케이션
                    'negotiation',            // 협상력
                    'product_knowledge',      // 제품 지식
                    'crm_usage'              // CRM 활용
                ]),
                'min_baseline_sales' => 5000000,        // 월 500만원 매출
                'min_baseline_cases' => 50,             // 월 50건 처리
                'min_baseline_revenue' => 2000000,      // 월 200만원 순이익
                'min_baseline_clients' => 5,            // 최소 5개 활성 고객
                'baseline_quality_score' => 80.0,       // 80점 이상 품질
                'default_commission_type' => 'percentage',
                'default_commission_rate' => 9.00,      // 9% 수수료
                'commission_notes' => '매출 성과에 따른 차등 수수료 적용 가능. 목표 초과 달성시 추가 인센티브 제공.',
                'registration_fee' => 300000.00,        // 30만원 등록비
                'monthly_maintenance_fee' => 80000.00,  // 월 8만원 유지비
                'annual_maintenance_fee' => 800000.00,  // 연 80만원 유지비
                'fee_waiver_available' => true,
                'fee_structure_notes' => '영업 전문 파트너 비용. 분기별 매출 목표 달성시 할인 혜택 제공.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ==========================================================
            // 🎯 마케팅 파트너 (크리에이티브 전문)
            // ==========================================================
            [
                'type_code' => 'MARKETING',
                'type_name' => '마케팅 파트너',
                'description' => '브랜드 홍보와 마케팅 캠페인 실행에 특화된 파트너입니다. 창의적인 온라인/오프라인 마케팅 활동을 통해 브랜드 가치를 극대화합니다.',
                'icon' => 'fe-megaphone',
                'color' => '#ff6b35', // 오렌지 - 창의성과 활력
                'sort_order' => 3,
                'specialties' => json_encode([
                    'digital_marketing',       // 디지털 마케팅
                    'content_creation',        // 콘텐츠 제작
                    'social_media',           // 소셜미디어 마케팅
                    'campaign_management'      // 캠페인 관리
                ]),
                'required_skills' => json_encode([
                    'creative_thinking',       // 창의적 사고
                    'content_writing',        // 콘텐츠 작성
                    'social_media_management', // SNS 관리
                    'analytics'               // 데이터 분석
                ]),
                'min_baseline_sales' => 3000000,        // 월 300만원 매출
                'min_baseline_cases' => 30,             // 월 30건 처리
                'min_baseline_revenue' => 1500000,      // 월 150만원 순이익
                'min_baseline_clients' => 8,            // 최소 8개 활성 고객
                'baseline_quality_score' => 75.0,       // 75점 이상 품질
                'default_commission_type' => 'percentage',
                'default_commission_rate' => 8.00,      // 8% 수수료
                'commission_notes' => '마케팅 캠페인 성과에 따른 보너스 수수료 제공. 바이럴 성공시 특별 인센티브.',
                'registration_fee' => 200000.00,        // 20만원 등록비
                'monthly_maintenance_fee' => 60000.00,  // 월 6만원 유지비
                'annual_maintenance_fee' => 600000.00,  // 연 60만원 유지비
                'fee_waiver_available' => true,
                'fee_structure_notes' => '마케팅 전문 파트너 비용. 캠페인 성과 지표 달성시 할인 제공.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ==========================================================
            // 🔧 기술 지원 파트너 (기술 전문)
            // ==========================================================
            [
                'type_code' => 'TECH_SUPPORT',
                'type_name' => '기술 지원 파트너',
                'description' => '기술적 문제 해결과 고객 지원에 전문성을 가진 파트너입니다. 제품 설치, 설정, 문제 해결을 통해 안정적인 서비스를 제공합니다.',
                'icon' => 'fe-tool',
                'color' => '#007bff', // 파랑 - 신뢰성과 전문성
                'sort_order' => 4,
                'specialties' => json_encode([
                    'technical_support',       // 기술 지원
                    'problem_solving',         // 문제 해결
                    'installation',           // 설치 지원
                    'configuration'           // 설정 지원
                ]),
                'required_skills' => json_encode([
                    'technical_knowledge',     // 기술적 지식
                    'troubleshooting',        // 문제 진단
                    'documentation',          // 문서화 능력
                    'customer_service'        // 고객 서비스
                ]),
                'min_baseline_sales' => 2000000,        // 월 200만원 매출
                'min_baseline_cases' => 100,            // 월 100건 처리
                'min_baseline_revenue' => 800000,       // 월 80만원 순이익
                'min_baseline_clients' => 10,           // 최소 10개 활성 고객
                'baseline_quality_score' => 90.0,       // 90점 이상 품질
                'default_commission_type' => 'fixed_amount',
                'default_commission_rate' => 0,
                'default_commission_amount' => 50000.00, // 건당 5만원 고정
                'commission_notes' => '건당 고정 수수료 지급. 복잡도에 따른 추가 보상 및 대량 처리시 별도 협의.',
                'registration_fee' => 150000.00,        // 15만원 등록비
                'monthly_maintenance_fee' => 40000.00,  // 월 4만원 유지비
                'annual_maintenance_fee' => 400000.00,  // 연 40만원 유지비
                'fee_waiver_available' => false,        // 면제 불가 (안정성 중시)
                'fee_structure_notes' => '기술 지원 파트너 기본 비용. 안정적인 기술 서비스 제공을 위한 표준 요금.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ==========================================================
            // 📚 교육 파트너 (지식 전달 전문)
            // ==========================================================
            [
                'type_code' => 'TRAINING',
                'type_name' => '교육 파트너',
                'description' => '제품 교육과 고객 트레이닝을 전담하는 파트너입니다. 체계적인 온보딩부터 고급 사용법까지 단계별 교육을 제공합니다.',
                'icon' => 'fe-book-open',
                'color' => '#6f42c1', // 보라 - 지식과 교육
                'sort_order' => 5,
                'specialties' => json_encode([
                    'training',               // 교육 진행
                    'education',             // 교육 설계
                    'curriculum_development', // 커리큘럼 개발
                    'assessment'             // 평가 및 피드백
                ]),
                'required_skills' => json_encode([
                    'presentation',           // 프레젠테이션
                    'instructional_design',   // 교수 설계
                    'patience',              // 인내심
                    'knowledge_transfer'      // 지식 전달
                ]),
                'min_baseline_sales' => 1500000,        // 월 150만원 매출
                'min_baseline_cases' => 80,             // 월 80건 처리
                'min_baseline_revenue' => 600000,       // 월 60만원 순이익
                'min_baseline_clients' => 15,           // 최소 15개 활성 고객
                'baseline_quality_score' => 85.0,       // 85점 이상 품질
                'default_commission_type' => 'percentage',
                'default_commission_rate' => 7.00,      // 7% 수수료
                'commission_notes' => '교육 시간 및 난이도에 따른 수수료 조정 가능. 교육 만족도 높을시 추가 보상.',
                'registration_fee' => 100000.00,        // 10만원 등록비
                'monthly_maintenance_fee' => 30000.00,  // 월 3만원 유지비
                'annual_maintenance_fee' => 300000.00,  // 연 30만원 유지비
                'fee_waiver_available' => true,
                'fee_structure_notes' => '교육 파트너 비용. 교육 품질 평가 결과에 따른 비용 조정 정책 적용.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ==========================================================
            // 🎧 고객 서비스 파트너 (관계 관리 전문)
            // ==========================================================
            [
                'type_code' => 'CUSTOMER_SERVICE',
                'type_name' => '고객 서비스 파트너',
                'description' => '고객 문의 대응과 사후 관리에 전문성을 가진 파트너입니다. 뛰어난 고객 만족도 향상과 장기적인 관계 유지를 담당합니다.',
                'icon' => 'fe-headphones',
                'color' => '#ffc107', // 노랑 - 친근함과 서비스
                'sort_order' => 6,
                'specialties' => json_encode([
                    'customer_service',       // 고객 서비스
                    'complaint_handling',     // 불만 처리
                    'relationship_management', // 관계 관리
                    'follow_up'              // 사후 관리
                ]),
                'required_skills' => json_encode([
                    'empathy',               // 공감 능력
                    'communication',         // 커뮤니케이션
                    'problem_solving',       // 문제 해결
                    'patience'              // 인내심
                ]),
                'min_baseline_sales' => 1000000,        // 월 100만원 매출
                'min_baseline_cases' => 120,            // 월 120건 처리
                'min_baseline_revenue' => 400000,       // 월 40만원 순이익
                'min_baseline_clients' => 20,           // 최소 20개 활성 고객
                'baseline_quality_score' => 85.0,       // 85점 이상 품질
                'default_commission_type' => 'fixed_amount',
                'default_commission_rate' => 0,
                'default_commission_amount' => 30000.00, // 건당 3만원 고정
                'commission_notes' => '고객 만족도 점수에 따른 품질 인센티브 별도 지급. 우수 평가시 보너스 제공.',
                'registration_fee' => 50000.00,         // 5만원 등록비 (진입 장벽 낮음)
                'monthly_maintenance_fee' => 20000.00,  // 월 2만원 유지비
                'annual_maintenance_fee' => 200000.00,  // 연 20만원 유지비
                'fee_waiver_available' => true,
                'fee_structure_notes' => '고객 서비스 파트너 기본 비용. 신규 파트너 지원 정책 및 우수 평가시 할인.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ];

        // =============================================================
        // 🔒 안전한 데이터 삽입 (트랜잭션 처리)
        // =============================================================
        try {
            DB::beginTransaction();

            foreach ($partnerTypes as $partnerType) {
                DB::table('partner_types')->insert($partnerType);
            }

            DB::commit();
            \Log::info('Successfully inserted ' . count($partnerTypes) . ' default partner types');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to insert default partner types: ' . $e->getMessage());
            throw new \Exception('파트너 타입 기본 데이터 삽입에 실패했습니다: ' . $e->getMessage());
        }
    }

    /**
     * 테이블 삭제 및 관련 데이터 정리
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_types');
    }
};