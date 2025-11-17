<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerType;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerType;
use Illuminate\Http\Request;

class IndexController extends Controller
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
     * 파트너 타입 목록 표시
     *
     * 마이그레이션 구조에 맞춰 개선된 파트너 타입 관리 화면
     * - 기본 정보: 타입 코드, 이름, 설명, 아이콘, 색상
     * - 전문 분야 및 필수 스킬 정보
     * - 최소 기준치 시스템
     * - 수수료 및 비용 구조
     * - 파트너 티어 연결 정보
     */
    public function __invoke(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $search = $request->get('search');
        $isActive = $request->get('is_active');
        $commissionType = $request->get('commission_type');

        $query = $this->model::query()
            ->with(['creator', 'updater'])
            ->withCount('partners');

        // 검색 처리 - 마이그레이션 필드 반영
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('type_code', 'like', "%{$search}%")
                  ->orWhere('type_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('specialties', 'like', "%{$search}%")
                  ->orWhere('required_skills', 'like', "%{$search}%");
            });
        }

        // 활성 상태 필터
        if ($isActive !== null && $isActive !== '') {
            $query->where('is_active', (bool) $isActive);
        }

        // 수수료 타입 필터 추가
        if ($commissionType) {
            $query->where('default_commission_type', $commissionType);
        }

        $items = $query->ordered()->paginate($perPage);

        // 향상된 통계 정보 - 마이그레이션 구조 반영
        $statistics = $this->getStatistics();

        // 필터 옵션
        $filterOptions = [
            'commission_types' => [
                'percentage' => '비율 기반',
                'fixed_amount' => '고정 금액'
            ]
        ];

        return view($this->viewPath . '.index', [
            'items' => $items,
            'statistics' => $statistics,
            'filterOptions' => $filterOptions,
            'title' => $this->title,
            'routePrefix' => $this->routePrefix,
            'searchValue' => $search,
            'selectedIsActive' => $isActive,
            'selectedCommissionType' => $commissionType,
            'perPage' => $perPage
        ]);
    }

    /**
     * 파트너 타입 통계 정보 생성
     *
     * @return array 통계 데이터
     */
    private function getStatistics(): array
    {
        $totalTypes = $this->model::count();
        $activeTypes = $this->model::active()->count();
        $inactiveTypes = $totalTypes - $activeTypes;

        // 수수료 타입별 분포
        $commissionStats = $this->model::selectRaw('
                default_commission_type,
                COUNT(*) as count,
                AVG(CASE WHEN default_commission_type = "percentage" THEN default_commission_rate END) as avg_percentage,
                AVG(CASE WHEN default_commission_type = "fixed_amount" THEN default_commission_amount END) as avg_amount
            ')
            ->groupBy('default_commission_type')
            ->get();

        // 파트너 수 통계
        $partnerStats = $this->model::selectRaw('
                COUNT(*) as type_count,
                SUM(partner_tiers_count) as total_tiers,
                AVG(partner_tiers_count) as avg_tiers_per_type
            ')
            ->where('is_active', true)
            ->first();

        // 비용 구조 통계
        $feeStats = $this->model::selectRaw('
                COUNT(CASE WHEN registration_fee > 0 THEN 1 END) as with_registration_fee,
                COUNT(CASE WHEN monthly_maintenance_fee > 0 THEN 1 END) as with_monthly_fee,
                COUNT(CASE WHEN annual_maintenance_fee > 0 THEN 1 END) as with_annual_fee,
                COUNT(CASE WHEN fee_waiver_available = 1 THEN 1 END) as with_fee_waiver,
                AVG(registration_fee) as avg_registration_fee,
                AVG(monthly_maintenance_fee) as avg_monthly_fee,
                AVG(annual_maintenance_fee) as avg_annual_fee
            ')
            ->where('is_active', true)
            ->first();

        return [
            // 기존 뷰 호환성을 위한 평면 구조
            'total_types' => $totalTypes,
            'active_types' => $activeTypes,
            'inactive_types' => $inactiveTypes,
            'total_partners' => $partnerStats->total_tiers ?? 0,
            'activation_rate' => $totalTypes > 0 ? round(($activeTypes / $totalTypes) * 100, 1) : 0,

            // 확장된 통계 정보 (중첩 구조)
            'basic' => [
                'total_types' => $totalTypes,
                'active_types' => $activeTypes,
                'inactive_types' => $inactiveTypes,
                'activation_rate' => $totalTypes > 0 ? round(($activeTypes / $totalTypes) * 100, 1) : 0
            ],
            'commission' => [
                'by_type' => $commissionStats->keyBy('default_commission_type'),
                'percentage_types' => $commissionStats->where('default_commission_type', 'percentage')->first(),
                'fixed_amount_types' => $commissionStats->where('default_commission_type', 'fixed_amount')->first()
            ],
            'partners' => [
                'total_tiers' => $partnerStats->total_tiers ?? 0,
                'avg_tiers_per_type' => round($partnerStats->avg_tiers_per_type ?? 0, 1),
                'types_with_tiers' => $this->model::where('partner_tiers_count', '>', 0)->count()
            ],
            'fees' => [
                'with_registration_fee' => $feeStats->with_registration_fee ?? 0,
                'with_monthly_fee' => $feeStats->with_monthly_fee ?? 0,
                'with_annual_fee' => $feeStats->with_annual_fee ?? 0,
                'with_fee_waiver' => $feeStats->with_fee_waiver ?? 0,
                'avg_registration_fee' => round($feeStats->avg_registration_fee ?? 0),
                'avg_monthly_fee' => round($feeStats->avg_monthly_fee ?? 0),
                'avg_annual_fee' => round($feeStats->avg_annual_fee ?? 0)
            ]
        ];
    }
}