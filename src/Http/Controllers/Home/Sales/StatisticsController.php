<?php

namespace Jiny\Partner\Http\Controllers\Home\Sales;

use Jiny\Partner\Http\Controllers\Home\HomeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Jiny\Partner\Models\PartnerSales;
use Jiny\Partner\Models\PartnerUser;

class StatisticsController extends HomeController
{
    /**
     * 판매 통계 조회
     */
    public function __invoke(Request $request)
    {
        try {
            // JWT 인증 확인
            $user = $this->auth($request);
            if (!$user) {
                return $this->errorResponse('인증이 필요합니다.');
            }

            // 파트너 사용자 정보 조회
            $partnerUser = PartnerUser::where('user_id', $user->id ?? $user['id'])
                ->where('status', 'active')
                ->first();

            if (!$partnerUser) {
                return $this->errorResponse('파트너 권한이 없습니다.');
            }

            // 기간별 통계
            $periodStats = [
                'today' => $this->getPeriodStats($partnerUser->id, 'today'),
                'this_week' => $this->getPeriodStats($partnerUser->id, 'this_week'),
                'this_month' => $this->getPeriodStats($partnerUser->id, 'this_month'),
                'this_year' => $this->getPeriodStats($partnerUser->id, 'this_year'),
                'all_time' => $this->getPeriodStats($partnerUser->id, 'all_time')
            ];

            // 월별 판매 통계 (최근 12개월)
            $monthlyStats = $this->getMonthlyStats($partnerUser->id);

            // 카테고리별 통계
            $categoryStats = $this->getCategoryStats($partnerUser->id);

            // 상태별 통계
            $statusStats = $this->getStatusStats($partnerUser->id);

            // 성과 지표
            $performanceMetrics = [
                'conversion_rate' => $this->getConversionRate($partnerUser->id),
                'avg_sale_amount' => $this->getAverageSaleAmount($partnerUser->id),
                'best_month' => $this->getBestMonth($partnerUser->id),
                'growth_rate' => $this->getGrowthRate($partnerUser->id)
            ];

            // 랭킹 정보
            $ranking = $this->getPartnerRanking($partnerUser->id);

            $viewData = [
                'user' => $user,
                'partnerUser' => $partnerUser,
                'periodStats' => $periodStats,
                'monthlyStats' => $monthlyStats,
                'categoryStats' => $categoryStats,
                'statusStats' => $statusStats,
                'performanceMetrics' => $performanceMetrics,
                'ranking' => $ranking,
                'pageTitle' => '판매 통계'
            ];

            if ($request->wantsJson()) {
                return $this->successResponse($viewData);
            }

            return view('jiny-partner::home.sales.statistics', $viewData);

        } catch (\Exception $e) {
            return $this->errorResponse('판매 통계를 불러오는 중 오류가 발생했습니다.', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 기간별 통계 조회
     */
    private function getPeriodStats($partnerId, $period)
    {
        $query = PartnerSales::where('partner_id', $partnerId);

        switch ($period) {
            case 'today':
                $query->whereDate('created_at', today());
                break;
            case 'this_week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'this_month':
                $query->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
                break;
            case 'this_year':
                $query->whereYear('created_at', now()->year);
                break;
        }

        return [
            'count' => $query->count(),
            'amount' => $query->sum('amount'),
            'confirmed' => $query->where('status', 'confirmed')->count(),
            'pending' => $query->where('status', 'pending')->count()
        ];
    }

    /**
     * 월별 통계 조회 (최근 12개월)
     */
    private function getMonthlyStats($partnerId)
    {
        $monthlyData = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $count = PartnerSales::where('partner_id', $partnerId)
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();

            $amount = PartnerSales::where('partner_id', $partnerId)
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('amount');

            $monthlyData[] = [
                'month' => $date->format('Y-m'),
                'count' => $count,
                'amount' => $amount
            ];
        }

        return $monthlyData;
    }

    /**
     * 카테고리별 통계
     */
    private function getCategoryStats($partnerId)
    {
        return PartnerSales::where('partner_id', $partnerId)
            ->selectRaw('product_category, COUNT(*) as count, SUM(amount) as total_amount')
            ->groupBy('product_category')
            ->orderBy('total_amount', 'desc')
            ->get();
    }

    /**
     * 상태별 통계
     */
    private function getStatusStats($partnerId)
    {
        return PartnerSales::where('partner_id', $partnerId)
            ->selectRaw('status, COUNT(*) as count, SUM(amount) as total_amount')
            ->groupBy('status')
            ->get();
    }

    /**
     * 전환율 계산
     */
    private function getConversionRate($partnerId)
    {
        $totalSales = PartnerSales::where('partner_id', $partnerId)->count();
        $confirmedSales = PartnerSales::where('partner_id', $partnerId)
            ->where('status', 'confirmed')
            ->count();

        if ($totalSales === 0) return 0;
        return round(($confirmedSales / $totalSales) * 100, 2);
    }

    /**
     * 평균 판매 금액
     */
    private function getAverageSaleAmount($partnerId)
    {
        return PartnerSales::where('partner_id', $partnerId)
            ->where('status', 'confirmed')
            ->avg('amount') ?? 0;
    }

    /**
     * 최고 실적 월
     */
    private function getBestMonth($partnerId)
    {
        return PartnerSales::where('partner_id', $partnerId)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('total', 'desc')
            ->first();
    }

    /**
     * 성장률 계산 (이번 달 vs 지난 달)
     */
    private function getGrowthRate($partnerId)
    {
        $thisMonth = PartnerSales::where('partner_id', $partnerId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $lastMonth = PartnerSales::where('partner_id', $partnerId)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('amount');

        if ($lastMonth == 0) return $thisMonth > 0 ? 100 : 0;
        return round((($thisMonth - $lastMonth) / $lastMonth) * 100, 2);
    }

    /**
     * 파트너 랭킹 조회
     */
    private function getPartnerRanking($partnerId)
    {
        // 이번 달 실적 기준 랭킹
        $thisMonthRanking = PartnerSales::join('partner_users', 'partner_sales.partner_id', '=', 'partner_users.id')
            ->whereMonth('partner_sales.created_at', now()->month)
            ->whereYear('partner_sales.created_at', now()->year)
            ->selectRaw('partner_users.id, partner_users.name, SUM(partner_sales.amount) as total_amount')
            ->groupBy('partner_users.id', 'partner_users.name')
            ->orderBy('total_amount', 'desc')
            ->get();

        $myRank = $thisMonthRanking->search(function ($item) use ($partnerId) {
            return $item->id == $partnerId;
        });

        return [
            'my_rank' => $myRank !== false ? $myRank + 1 : null,
            'total_partners' => $thisMonthRanking->count(),
            'top_partners' => $thisMonthRanking->take(5)
        ];
    }
}