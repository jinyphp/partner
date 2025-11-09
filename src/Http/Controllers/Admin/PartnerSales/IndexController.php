<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerSales;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerSales;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    protected $model;
    protected $viewPath;
    protected $routePrefix;
    protected $title;

    public function __construct()
    {
        $this->model = PartnerSales::class;
        $this->viewPath = 'jiny-partner::admin.partner-sales';
        $this->routePrefix = 'partner.sales';
        $this->title = '파트너 매출 관리';
    }

    /**
     * 파트너 매출 목록
     */
    public function __invoke(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $search = $request->get('search');
        $status = $request->get('status');
        $partnerId = $request->get('partner_id');
        $category = $request->get('category');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = $this->model::query()
            ->with(['partner.tier', 'partner.type', 'creator', 'approver']);

        // 검색 처리
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('order_number', 'like', "%{$search}%")
                  ->orWhere('partner_name', 'like', "%{$search}%")
                  ->orWhere('partner_email', 'like', "%{$search}%");
            });
        }

        // 상태별 필터
        if ($status) {
            $query->where('status', $status);
        }

        // 파트너별 필터
        if ($partnerId) {
            $query->where('partner_id', $partnerId);
        }

        // 카테고리별 필터
        if ($category) {
            $query->where('category', $category);
        }

        // 날짜 범위 필터
        if ($startDate && $endDate) {
            $query->whereBetween('sales_date', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('sales_date', '>=', $startDate);
        } elseif ($endDate) {
            $query->where('sales_date', '<=', $endDate);
        }

        $items = $query->orderBy('sales_date', 'desc')
                       ->orderBy('created_at', 'desc')
                       ->paginate($perPage);

        // 파트너별 특화 정보 조회
        $partnerInfo = null;
        $partnerStats = null;
        if ($partnerId) {
            $partnerInfo = PartnerUser::with(['tier', 'type', 'parent', 'children'])
                                    ->find($partnerId);
            if ($partnerInfo) {
                $partnerStats = $this->getPartnerSpecificStats($partnerInfo, $query);
            }
        }

        // 통계 정보 (파트너별 페이지에서는 파트너 실제 통계 우선 표시)
        $statistics = $this->getStatistics($query, $partnerInfo);

        // 필터 옵션
        $filterOptions = $this->getFilterOptions();

        // 파트너별 제목 설정
        $pageTitle = $partnerInfo
            ? $this->title . ' - ' . $partnerInfo->name . '님'
            : $this->title;

        return view($this->viewPath . '.index', [
            'items' => $items,
            'statistics' => $statistics,
            'filterOptions' => $filterOptions,
            'title' => $pageTitle,
            'routePrefix' => $this->routePrefix,
            'searchValue' => $search,
            'selectedStatus' => $status,
            'selectedPartnerId' => $partnerId,
            'selectedCategory' => $category,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'perPage' => $perPage,
            'partnerInfo' => $partnerInfo,
            'partnerStats' => $partnerStats
        ]);
    }

    /**
     * 통계 정보 조회
     */
    private function getStatistics($baseQuery = null, $partnerInfo = null)
    {
        $query = $baseQuery ? clone $baseQuery : $this->model::query();

        // 기본 매출 테이블 기반 통계
        $basicStats = [
            'total_sales' => $query->sum('amount'),
            'total_count' => $query->count(),
            'confirmed_sales' => $query->where('status', 'confirmed')->sum('amount'),
            'confirmed_count' => $query->where('status', 'confirmed')->count(),
            'pending_sales' => $query->where('status', 'pending')->sum('amount'),
            'pending_count' => $query->where('status', 'pending')->count(),
            'cancelled_sales' => $query->where('status', 'cancelled')->sum('amount'),
            'cancelled_count' => $query->where('status', 'cancelled')->count(),
            'total_commission' => $query->sum('total_commission_amount'),
            'commission_calculated_count' => $query->where('commission_calculated', true)->count(),
            'commission_pending_count' => $query->where('commission_calculated', false)->count(),
            'average_sales' => $query->avg('amount') ?? 0,
            'unique_partners' => $query->distinct('partner_id')->count('partner_id'),
        ];

        // 파트너별 페이지인 경우 파트너의 실제 통계 필드 우선 표시
        if ($partnerInfo) {
            $basicStats['partner_total_sales'] = $partnerInfo->total_sales;
            $basicStats['partner_monthly_sales'] = $partnerInfo->monthly_sales;
            $basicStats['partner_team_sales'] = $partnerInfo->team_sales;
            $basicStats['partner_earned_commissions'] = $partnerInfo->earned_commissions;

            // 파트너별 페이지에서는 파트너의 실제 통계를 메인 통계로 교체
            $basicStats['total_sales'] = $partnerInfo->total_sales;
            $basicStats['total_commission'] = $partnerInfo->earned_commissions;
        }

        return $basicStats;
    }

    /**
     * 파트너별 특화 통계 조회
     */
    private function getPartnerSpecificStats(PartnerUser $partner, $salesQuery = null)
    {
        $baseQuery = $salesQuery ?: $this->model::where('partner_id', $partner->id);

        // 파트너의 전체 매출 통계 (필터 무관)
        $allTimeStats = $this->model::where('partner_id', $partner->id);

        return [
            // 현재 필터 적용된 통계
            'filtered_stats' => [
                'total_sales' => $baseQuery->sum('amount'),
                'total_count' => $baseQuery->count(),
                'confirmed_sales' => $baseQuery->where('status', 'confirmed')->sum('amount'),
                'confirmed_count' => $baseQuery->where('status', 'confirmed')->count(),
                'pending_sales' => $baseQuery->where('status', 'pending')->sum('amount'),
                'pending_count' => $baseQuery->where('status', 'pending')->count(),
                'total_commission' => $baseQuery->sum('total_commission_amount'),
                'average_sales' => $baseQuery->avg('amount') ?? 0,
            ],

            // 파트너 전체 기간 통계
            'all_time_stats' => [
                'total_sales' => $allTimeStats->sum('amount'),
                'total_count' => $allTimeStats->count(),
                'confirmed_sales' => $allTimeStats->where('status', 'confirmed')->sum('amount'),
                'confirmed_count' => $allTimeStats->where('status', 'confirmed')->count(),
                'total_commission' => $allTimeStats->sum('total_commission_amount'),
                'first_sale_date' => $allTimeStats->min('sales_date'),
                'last_sale_date' => $allTimeStats->max('sales_date'),
                'best_month_sales' => $this->getBestMonthSales($partner->id),
            ],

            // 파트너 성과 지표
            'performance_metrics' => [
                'monthly_sales_sync' => $partner->monthly_sales,
                'total_sales_sync' => $partner->total_sales,
                'team_sales_sync' => $partner->team_sales,
                'earned_commissions_sync' => $partner->earned_commissions,
                'sales_vs_sync' => [
                    'monthly_difference' => $this->getCurrentMonthSales($partner->id) - $partner->monthly_sales,
                    'total_difference' => $allTimeStats->where('status', 'confirmed')
                                                       ->sum('amount') - $partner->total_sales,
                ],
            ],

            // 카테고리별 분포
            'category_breakdown' => $allTimeStats->select('category')
                                                 ->selectRaw('COUNT(*) as count, SUM(amount) as total')
                                                 ->whereNotNull('category')
                                                 ->groupBy('category')
                                                 ->get(),

            // 최근 활동
            'recent_activity' => [
                'last_30_days_sales' => $allTimeStats->where('sales_date', '>=', now()->subDays(30))->sum('amount'),
                'last_30_days_count' => $allTimeStats->where('sales_date', '>=', now()->subDays(30))->count(),
                'this_month_sales' => $this->getCurrentMonthSales($partner->id),
                'this_month_count' => $this->getCurrentMonthCount($partner->id),
            ],
        ];
    }

    /**
     * 현재 월 매출 조회 (SQLite/MySQL 호환)
     */
    private function getCurrentMonthSales($partnerId)
    {
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}.driver");

        if ($connection === 'sqlite') {
            return $this->model::where('partner_id', $partnerId)
                              ->whereRaw("strftime('%Y-%m', sales_date) = ?", [now()->format('Y-m')])
                              ->sum('amount');
        } else {
            return $this->model::where('partner_id', $partnerId)
                              ->whereMonth('sales_date', now()->month)
                              ->whereYear('sales_date', now()->year)
                              ->sum('amount');
        }
    }

    /**
     * 현재 월 매출 건수 조회 (SQLite/MySQL 호환)
     */
    private function getCurrentMonthCount($partnerId)
    {
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}.driver");

        if ($connection === 'sqlite') {
            return $this->model::where('partner_id', $partnerId)
                              ->whereRaw("strftime('%Y-%m', sales_date) = ?", [now()->format('Y-m')])
                              ->count();
        } else {
            return $this->model::where('partner_id', $partnerId)
                              ->whereMonth('sales_date', now()->month)
                              ->whereYear('sales_date', now()->year)
                              ->count();
        }
    }

    /**
     * 파트너의 최고 월 매출 조회
     */
    private function getBestMonthSales($partnerId)
    {
        // SQLite와 MySQL 호환성을 위한 동적 쿼리
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}.driver");

        if ($connection === 'sqlite') {
            return $this->model::where('partner_id', $partnerId)
                              ->where('status', 'confirmed')
                              ->selectRaw("strftime('%Y', sales_date) as year, strftime('%m', sales_date) as month, SUM(amount) as total")
                              ->groupByRaw("strftime('%Y', sales_date), strftime('%m', sales_date)")
                              ->orderBy('total', 'desc')
                              ->first();
        } else {
            return $this->model::where('partner_id', $partnerId)
                              ->where('status', 'confirmed')
                              ->selectRaw('YEAR(sales_date) as year, MONTH(sales_date) as month, SUM(amount) as total')
                              ->groupBy('year', 'month')
                              ->orderBy('total', 'desc')
                              ->first();
        }
    }

    /**
     * 필터 옵션 조회
     */
    private function getFilterOptions()
    {
        return [
            'statuses' => [
                'pending' => '대기중',
                'confirmed' => '확정',
                'cancelled' => '취소',
                'refunded' => '환불',
            ],
            'categories' => $this->model::distinct('category')
                                       ->whereNotNull('category')
                                       ->pluck('category')
                                       ->mapWithKeys(function ($category) {
                                           return [$category => ucfirst($category)];
                                       }),
            'partners' => PartnerUser::select('id', 'name', 'email')
                                    ->where('status', 'active')
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function ($partner) {
                                        return [$partner->id => $partner->name . ' (' . $partner->email . ')'];
                                    }),
        ];
    }
}