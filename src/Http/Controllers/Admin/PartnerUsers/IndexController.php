<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerUsers;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Models\PartnerTier;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    protected $model;
    protected $viewPath;
    protected $routePrefix;
    protected $title;

    public function __construct()
    {
        $this->model = PartnerUser::class;
        $this->viewPath = 'jiny-partner::admin.partner-users';
        $this->routePrefix = 'partner.users';
        $this->title = '파트너 회원';
    }

    /**
     * 파트너 회원 목록 조회
     */
    public function __invoke(Request $request)
    {
        $query = $this->model::with(['partnerTier', 'partnerType']);

        // 검색 기능
        if ($request->has('search') && $request->search) {
            $searchFields = $this->getSearchFields();
            $query->where(function($q) use ($request, $searchFields) {
                foreach ($searchFields as $field) {
                    $q->orWhere($field, 'like', '%' . $request->search . '%');
                }
            });
        }

        // 상태 필터
        if ($request->has('status') && $request->status !== '' && $request->status !== null) {
            $query->where('status', $request->status);
        }

        // 파트너 타입 필터
        $typeId = $request->input('partner_type_id');
        if ($typeId && $typeId !== '' && $typeId !== null) {
            $query->where('partner_type_id', $typeId);
        }

        // 등급 필터 (partner_tier_id 또는 tier 파라미터 모두 지원)
        $tierId = $request->input('partner_tier_id') ?: $request->input('tier');
        if ($tierId && $tierId !== '' && $tierId !== null) {
            $query->where('partner_tier_id', $tierId);
        }

        // 사용자 테이블 필터
        if ($request->has('user_table') && $request->user_table !== '' && $request->user_table !== null) {
            $query->where('user_table', $request->user_table);
        }

        // 가입일 범위 필터
        if ($request->has('joined_from') && $request->joined_from) {
            $query->where('partner_joined_at', '>=', $request->joined_from);
        }
        if ($request->has('joined_to') && $request->joined_to) {
            $query->where('partner_joined_at', '<=', $request->joined_to);
        }

        $items = $query->orderBy('created_at', 'desc')->paginate(20);

        // 페이지네이션에 필터 파라미터 유지
        $items->appends($request->query());

        // 각 파트너의 실시간 통계 계산
        $items->getCollection()->transform(function ($partner) {
            $partner->realtime_stats = $partner->getStatistics(true, 30); // 30분 캐시
            return $partner;
        });

        // 필터된 데이터를 기반으로 통계 계산
        $statistics = $this->getFilteredStatistics($request);

        // 필터 옵션
        $filterOptions = $this->getFilterOptions();

        return view("{$this->viewPath}.index", [
            'items' => $items,
            'title' => $this->title,
            'routePrefix' => $this->routePrefix,
            'searchValue' => $request->search,
            'selectedStatus' => $request->status,
            'selectedType' => $typeId,
            'selectedTier' => $tierId, // tier 또는 partner_tier_id 값 사용
            'selectedUserTable' => $request->user_table,
            'joinedFrom' => $request->joined_from,
            'joinedTo' => $request->joined_to,
            'statistics' => $statistics,
            'filterOptions' => $filterOptions
        ]);
    }

    /**
     * 검색 필드 목록
     */
    protected function getSearchFields(): array
    {
        return ['email', 'name', 'admin_notes'];
    }

    /**
     * 필터가 적용된 통계 데이터 생성
     */
    protected function getFilteredStatistics(Request $request): array
    {
        // 동일한 필터링 로직을 통계에도 적용
        $baseQuery = $this->model::query();

        // 검색 필터 적용
        if ($request->has('search') && $request->search) {
            $searchFields = $this->getSearchFields();
            $baseQuery->where(function($q) use ($request, $searchFields) {
                foreach ($searchFields as $field) {
                    $q->orWhere($field, 'like', '%' . $request->search . '%');
                }
            });
        }

        // 상태 필터 적용
        if ($request->has('status') && $request->status !== '' && $request->status !== null) {
            $baseQuery->where('status', $request->status);
        }

        // 파트너 타입 필터 적용
        $typeId = $request->input('partner_type_id');
        if ($typeId && $typeId !== '' && $typeId !== null) {
            $baseQuery->where('partner_type_id', $typeId);
        }

        // 등급 필터 적용
        $tierId = $request->input('partner_tier_id') ?: $request->input('tier');
        if ($tierId && $tierId !== '' && $tierId !== null) {
            $baseQuery->where('partner_tier_id', $tierId);
        }

        // 사용자 테이블 필터 적용
        if ($request->has('user_table') && $request->user_table !== '' && $request->user_table !== null) {
            $baseQuery->where('user_table', $request->user_table);
        }

        // 가입일 범위 필터 적용
        if ($request->has('joined_from') && $request->joined_from) {
            $baseQuery->where('partner_joined_at', '>=', $request->joined_from);
        }
        if ($request->has('joined_to') && $request->joined_to) {
            $baseQuery->where('partner_joined_at', '<=', $request->joined_to);
        }

        // 통계 계산 (필터 적용됨)
        return [
            'total' => $baseQuery->count(),
            'active' => (clone $baseQuery)->where('status', 'active')->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'suspended' => (clone $baseQuery)->where('status', 'suspended')->count(),
            'this_month' => (clone $baseQuery)->whereBetween('created_at', [
                now()->startOfMonth(),
                now()->endOfMonth()
            ])->count(),
            'avg_rating' => round((clone $baseQuery)->where('status', 'active')->avg('average_rating') ?? 0, 2),
            'high_performers' => (clone $baseQuery)->where('average_rating', '>=', 4.5)
                ->where('status', 'active')->count(),

            // 실시간 매출 및 커미션 통계 (필터 적용됨)
            'total_sales' => $this->calculateRealTimeTotalSales($baseQuery),
            'total_commissions' => $this->calculateRealTimeTotalCommissions($baseQuery),
            'total_team_sales' => $this->calculateRealTimeTeamSales($baseQuery),
            'avg_sales_per_partner' => $this->calculateAverageSalesPerPartner($baseQuery),
            'top_earners' => $this->calculateTopEarners($baseQuery),
            'commission_rate' => $this->calculateFilteredCommissionRate($baseQuery),
        ];
    }

    /**
     * 기본 통계 데이터 생성 (필터 미적용, 전체 데이터)
     */
    protected function getStatistics(): array
    {
        return [
            'total' => $this->model::count(),
            'active' => $this->model::where('status', 'active')->count(),
            'pending' => $this->model::where('status', 'pending')->count(),
            'suspended' => $this->model::where('status', 'suspended')->count(),
            'this_month' => $this->model::whereBetween('created_at', [
                now()->startOfMonth(),
                now()->endOfMonth()
            ])->count(),
            'avg_rating' => round($this->model::where('status', 'active')->avg('average_rating') ?? 0, 2),
            'high_performers' => $this->model::where('average_rating', '>=', 4.5)
                ->where('status', 'active')->count(),

            // 매출 및 커미션 통계
            'total_sales' => $this->model::sum('monthly_sales') ?? 0,
            'total_commissions' => $this->model::sum('earned_commissions') ?? 0,
            'total_team_sales' => $this->model::sum('team_sales') ?? 0,
            'avg_sales_per_partner' => round($this->model::where('monthly_sales', '>', 0)->avg('monthly_sales') ?? 0, 0),
            'top_earners' => $this->model::where('monthly_sales', '>=', 500000)->count(),
            'commission_rate' => $this->calculateOverallCommissionRate(),
        ];
    }

    /**
     * 실시간 총 매출액 계산
     */
    protected function calculateRealTimeTotalSales($query): float
    {
        $partnerIds = (clone $query)->pluck('id');

        return \Jiny\Partner\Models\PartnerSales::whereIn('partner_id', $partnerIds)
            ->where('status', 'confirmed')
            ->sum('amount') ?? 0;
    }

    /**
     * 실시간 총 커미션 계산
     */
    protected function calculateRealTimeTotalCommissions($query): float
    {
        $partnerIds = (clone $query)->pluck('id');

        return \Jiny\Partner\Models\PartnerCommission::whereIn('partner_id', $partnerIds)
            ->where('status', '!=', 'cancelled')
            ->sum('commission_amount') ?? 0;
    }

    /**
     * 실시간 팀 총 매출 계산
     */
    protected function calculateRealTimeTeamSales($query): float
    {
        $partners = (clone $query)->get();
        $totalTeamSales = 0;

        foreach ($partners as $partner) {
            $totalTeamSales += $partner->getTeamTotalSales();
        }

        return $totalTeamSales;
    }

    /**
     * 파트너당 평균 매출 계산
     */
    protected function calculateAverageSalesPerPartner($query): float
    {
        $partnerIds = (clone $query)->pluck('id');

        if ($partnerIds->isEmpty()) {
            return 0;
        }

        $partnersWithSales = \Jiny\Partner\Models\PartnerSales::whereIn('partner_id', $partnerIds)
            ->where('status', 'confirmed')
            ->where('amount', '>', 0)
            ->distinct('partner_id')
            ->count('partner_id');

        if ($partnersWithSales === 0) {
            return 0;
        }

        $totalSales = $this->calculateRealTimeTotalSales($query);

        return round($totalSales / $partnersWithSales, 0);
    }

    /**
     * 고수익 파트너 수 계산
     */
    protected function calculateTopEarners($query, $threshold = 500000): int
    {
        $partners = (clone $query)->get();
        $topEarners = 0;

        foreach ($partners as $partner) {
            if ($partner->getTotalSalesAmount() >= $threshold) {
                $topEarners++;
            }
        }

        return $topEarners;
    }

    /**
     * 필터가 적용된 커미션율 계산 (실시간)
     */
    protected function calculateFilteredCommissionRate($query): float
    {
        $totalSales = $this->calculateRealTimeTotalSales($query);
        $totalCommissions = $this->calculateRealTimeTotalCommissions($query);

        if ($totalSales > 0) {
            return round(($totalCommissions / $totalSales) * 100, 1);
        }

        return 0.0;
    }

    /**
     * 전체 커미션율 계산
     */
    protected function calculateOverallCommissionRate(): float
    {
        $totalSales = $this->model::sum('monthly_sales');
        $totalCommissions = $this->model::sum('earned_commissions');

        if ($totalSales > 0) {
            return round(($totalCommissions / $totalSales) * 100, 1);
        }

        return 0.0;
    }

    /**
     * 필터 옵션 데이터
     */
    protected function getFilterOptions(): array
    {
        return [
            'types' => \Jiny\Partner\Models\PartnerType::active()->orderBy('sort_order', 'asc')->orderBy('type_name', 'asc')->get(),
            'tiers' => PartnerTier::active()->orderBy('priority_level')->get(),
            'statuses' => [
                'active' => '활성',
                'inactive' => '비활성',
                'suspended' => '정지',
                'pending' => '대기'
            ],
            'userTables' => $this->model::distinct()->pluck('user_table')->filter()->sort()->values()
        ];
    }

    protected function getValidationRules($item = null): array
    {
        return [];
    }
}