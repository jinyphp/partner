<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerType;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerType;

class EditController extends Controller
{
    protected $model;
    protected $viewPath;
    protected $routePrefix;
    protected $title;

    public function __construct()
    {
        $this->model = PartnerType::class;
        $this->viewPath = 'jiny-partner::admin.partner-type';
        $this->routePrefix = 'partner.type';
        $this->title = '파트너 타입';
    }

    /**
     * 파트너 타입 수정 폼 표시
     *
     * 마이그레이션 구조에 맞춘 완전한 편집 폼 지원:
     * - 기본 정보 (타입 코드, 이름, 설명, 아이콘, 색상, 정렬순서)
     * - 전문 분야 및 필수 스킬 (JSON 필드)
     * - 최소 기준치 시스템 (매출, 건수, 수익, 고객수, 품질점수)
     * - 수수료 구조 (비율/고정금액 조건부 표시)
     * - 비용 구조 (가입비, 유지비, 면제 여부)
     * - 파트너 티어 연결 정보 (읽기 전용)
     * - 관리자 메모
     */
    public function __invoke($id)
    {
        $item = $this->model::with(['creator', 'updater'])->findOrFail($id);

        // 전문 분야 옵션 (CreateController와 동일)
        $specialtyOptions = [
            'sales' => '영업',
            'technical_support' => '기술지원',
            'customer_service' => '고객서비스',
            'marketing' => '마케팅',
            'training' => '교육',
            'consulting' => '컨설팅',
            'project_management' => '프로젝트 관리',
            'business_development' => '사업개발',
            'channel_management' => '채널 관리',
            'solution_architecture' => '솔루션 설계',
            'implementation' => '구현',
            'maintenance' => '유지보수'
        ];

        // 필수 스킬 옵션
        $skillOptions = [
            'communication' => '의사소통',
            'negotiation' => '협상',
            'presentation' => '프레젠테이션',
            'product_knowledge' => '제품지식',
            'technical_expertise' => '기술전문성',
            'problem_solving' => '문제해결',
            'leadership' => '리더십',
            'analytical_thinking' => '분석적 사고',
            'customer_relations' => '고객관계 관리',
            'project_coordination' => '프로젝트 조정',
            'strategic_planning' => '전략 기획',
            'market_analysis' => '시장 분석'
        ];

        // 수수료 타입 옵션
        $commissionTypes = [
            'percentage' => '비율 기반',
            'fixed_amount' => '고정 금액'
        ];

        // 아이콘 옵션
        $iconOptions = [
            'fa-users' => '사용자 그룹',
            'fa-user-tie' => '비즈니스맨',
            'fa-handshake' => '악수',
            'fa-chart-line' => '차트',
            'fa-cogs' => '설정',
            'fa-star' => '별',
            'fa-award' => '상패',
            'fa-crown' => '왕관',
            'fa-diamond' => '다이아몬드',
            'fa-shield-alt' => '방패'
        ];

        // 색상 프리셋
        $colorPresets = [
            '#3B82F6' => '파랑',
            '#10B981' => '초록',
            '#F59E0B' => '주황',
            '#EF4444' => '빨강',
            '#8B5CF6' => '보라',
            '#F97316' => '오렌지',
            '#06B6D4' => '청록',
            '#84CC16' => '라임',
            '#EC4899' => '핑크',
            '#6B7280' => '회색'
        ];

        // 파트너 티어 정보 (현재 연결된 티어 수 갱신)
        $item->updatePartnerTiersCount();

        // 관련 통계 정보
        $relatedStats = [
            'current_tiers' => $item->partner_tiers_count,
            'created_ago' => $item->created_at->diffForHumans(),
            'updated_ago' => $item->updated_at->diffForHumans(),
            'creator_name' => $item->creator->name ?? '알 수 없음',
            'updater_name' => $item->updater->name ?? '알 수 없음'
        ];

        return view($this->viewPath . '.edit', [
            'item' => $item,
            'title' => $this->title,
            'routePrefix' => $this->routePrefix,
            'specialtyOptions' => $specialtyOptions,
            'skillOptions' => $skillOptions,
            'commissionTypes' => $commissionTypes,
            'iconOptions' => $iconOptions,
            'colorPresets' => $colorPresets,
            'relatedStats' => $relatedStats
        ]);
    }
}