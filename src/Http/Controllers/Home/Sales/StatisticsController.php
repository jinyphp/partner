<?php

namespace Jiny\Partner\Http\Controllers\Home\Sales;

use Jiny\Partner\Http\Controllers\PartnerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Jiny\Partner\Models\PartnerSales;
use Jiny\Partner\Models\PartnerUser;
use Carbon\Carbon;

class StatisticsController extends PartnerController
{
    /**
     * 매출 통계 대시보드
     */
    public function __invoke(Request $request)
    {
        try {
            // 파트너 인증 및 정보 조회 (공통 로직 사용)
            $authResult = $this->authenticateAndGetPartner($request, 'sales_statistics');
            if (!$authResult['success']) {
                return $authResult['redirect'];
            }

            $user = $authResult['user'];
            $partnerUser = $authResult['partner'];

            // 기본 통계 기간 설정
            $period = $request->get('period', 'this_month');

            // 본인 및 하위 파트너 ID 수집
            $accessiblePartnerIds = $this->getAccessiblePartnerIds($partnerUser->id);

            // 기간별 통계
            $periodStats = [
                'today' => $this->getPeriodStats($accessiblePartnerIds, 'today'),
                'this_week' => $this->getPeriodStats($accessiblePartnerIds, 'this_week'),
                'this_month' => $this->getPeriodStats($accessiblePartnerIds, 'this_month'),
                'this_year' => $this->getPeriodStats($accessiblePartnerIds, 'this_year'),
                'all_time' => $this->getPeriodStats($accessiblePartnerIds, 'all_time')
            ];

            // 월별 판매 통계 (최근 12개월)
            $monthlyStats = $this->getMonthlyStats($accessiblePartnerIds);

            // 카테고리별 통계
            $categoryStats = $this->getCategoryStats($accessiblePartnerIds);

            // 상태별 통계
            $statusStats = $this->getStatusStats($accessiblePartnerIds);

            // 성과 지표
            $performanceMetrics = [
                'conversion_rate' => $this->getConversionRate($accessiblePartnerIds),
                'avg_sale_amount' => $this->getAverageSaleAmount($accessiblePartnerIds),
                'best_month' => $this->getBestMonth($accessiblePartnerIds),
                'growth_rate' => $this->getGrowthRate($accessiblePartnerIds)
            ];

            // 최근 매출 활동
            $recentSales = $this->getRecentSales($accessiblePartnerIds);

            // 성장률 상세 정보
            $growthDetails = $this->calculateGrowthRate($accessiblePartnerIds, $period);

            return view('jiny-partner::home.sales.statistics', compact(
                'user',
                'partnerUser',
                'periodStats',
                'monthlyStats',
                'categoryStats',
                'statusStats',
                'performanceMetrics',
                'recentSales',
                'growthDetails',
                'period'
            ));

        } catch (\Exception $e) {
            \Log::error('Partner sales statistics error: ' . $e->getMessage(), [
                'user_id' => $user->id ?? 'unknown',
                'user_uuid' => $user->uuid ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('home.partner.sales.index')
                ->with('error', '통계 조회 중 오류가 발생했습니다.');
        }
    }

    /**
     * 접근 가능한 파트너 ID 목록 조회 (본인 + 하위 파트너)
     */
    private function getAccessiblePartnerIds($partnerId)
    {
        $partnerIds = [$partnerId]; // 본인 포함

        try {
            // 하위 파트너들 조회
            if (class_exists('\Jiny\Partner\Models\PartnerNetworkRelationship')) {
                $subPartnerIds = \Jiny\Partner\Models\PartnerNetworkRelationship::where('parent_id', $partnerId)
                    ->where('is_active', true)
                    ->pluck('child_id')
                    ->toArray();

                $partnerIds = array_merge($partnerIds, $subPartnerIds);
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to get sub partners for statistics: ' . $e->getMessage());
        }

        return array_unique($partnerIds);
    }

    /**
     * 기간별 통계 조회
     */
    private function getPeriodStats($partnerIds, $period)
    {
        $query = PartnerSales::whereIn('partner_id', $partnerIds);

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

        $baseQuery = clone $query;

        return [
            'count' => $baseQuery->count(),
            'amount' => $baseQuery->sum('amount'),
            'confirmed' => (clone $query)->where('status', 'confirmed')->count(),
            'confirmed_amount' => (clone $query)->where('status', 'confirmed')->sum('amount'),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'pending_amount' => (clone $query)->where('status', 'pending')->sum('amount'),
            'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
            'rejected' => (clone $query)->where('status', 'rejected')->count(),
        ];
    }

    /**
     * 월별 통계 조회 (최근 12개월)
     */
    private function getMonthlyStats($partnerIds)
    {
        $monthlyData = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);

            $monthStats = PartnerSales::whereIn('partner_id', $partnerIds)
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->selectRaw('
                    COUNT(*) as count,
                    SUM(amount) as amount,
                    SUM(CASE WHEN status = "confirmed" THEN amount ELSE 0 END) as confirmed_amount,
                    COUNT(CASE WHEN status = "confirmed" THEN 1 END) as confirmed_count
                ')
                ->first();

            $monthlyData[] = [
                'month' => $date->format('Y-m'),
                'month_name' => $date->format('Y년 m월'),
                'count' => $monthStats->count ?? 0,
                'amount' => $monthStats->amount ?? 0,
                'confirmed_amount' => $monthStats->confirmed_amount ?? 0,
                'confirmed_count' => $monthStats->confirmed_count ?? 0,
            ];
        }

        return $monthlyData;
    }

    /**
     * 카테고리별 통계
     */
    private function getCategoryStats($partnerIds)
    {
        return PartnerSales::whereIn('partner_id', $partnerIds)
            ->selectRaw('COALESCE(category, "일반") as category_name, COUNT(*) as count, SUM(amount) as total_amount, AVG(amount) as avg_amount')
            ->groupBy('category')
            ->orderBy('total_amount', 'desc')
            ->get();
    }

    /**
     * 상태별 통계
     */
    private function getStatusStats($partnerIds)
    {
        return PartnerSales::whereIn('partner_id', $partnerIds)
            ->selectRaw('status, COUNT(*) as count, SUM(amount) as total_amount')
            ->groupBy('status')
            ->orderBy('total_amount', 'desc')
            ->get();
    }

    /**
     * 전환율 계산
     */
    private function getConversionRate($partnerIds)
    {
        $totalSales = PartnerSales::whereIn('partner_id', $partnerIds)->count();
        $confirmedSales = PartnerSales::whereIn('partner_id', $partnerIds)
            ->where('status', 'confirmed')
            ->count();

        if ($totalSales === 0) return 0;
        return round(($confirmedSales / $totalSales) * 100, 2);
    }

    /**
     * 평균 판매 금액
     */
    private function getAverageSaleAmount($partnerIds)
    {
        return PartnerSales::whereIn('partner_id', $partnerIds)
            ->where('status', 'confirmed')
            ->avg('amount') ?? 0;
    }

    /**
     * 최고 실적 월
     */
    private function getBestMonth($partnerIds)
    {
        // SQLite와 MySQL 모두 호환되는 방식 사용
        return PartnerSales::whereIn('partner_id', $partnerIds)
            ->selectRaw('strftime("%Y-%m", created_at) as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('total', 'desc')
            ->first();
    }

    /**
     * 성장률 계산 (이번 달 vs 지난 달)
     */
    private function getGrowthRate($partnerIds)
    {
        $thisMonth = PartnerSales::whereIn('partner_id', $partnerIds)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('status', 'confirmed')
            ->sum('amount');

        $lastMonth = PartnerSales::whereIn('partner_id', $partnerIds)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->where('status', 'confirmed')
            ->sum('amount');

        if ($lastMonth == 0) return $thisMonth > 0 ? 100 : 0;
        return round((($thisMonth - $lastMonth) / $lastMonth) * 100, 2);
    }

    /**
     * 최근 매출 활동 (최근 10건)
     */
    private function getRecentSales($partnerIds)
    {
        return PartnerSales::whereIn('partner_id', $partnerIds)
            ->with(['partner:id,name'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
    }

    /**
     * 성장률 계산 (이전 기간 대비)
     */
    private function calculateGrowthRate($partnerIds, $period)
    {
        try {
            $currentPeriodStart = null;
            $currentPeriodEnd = null;
            $previousPeriodStart = null;
            $previousPeriodEnd = null;

            switch ($period) {
                case 'this_week':
                    $currentPeriodStart = Carbon::now()->startOfWeek();
                    $currentPeriodEnd = Carbon::now()->endOfWeek();
                    $previousPeriodStart = Carbon::now()->subWeek()->startOfWeek();
                    $previousPeriodEnd = Carbon::now()->subWeek()->endOfWeek();
                    break;
                case 'this_month':
                default:
                    $currentPeriodStart = Carbon::now()->startOfMonth();
                    $currentPeriodEnd = Carbon::now()->endOfMonth();
                    $previousPeriodStart = Carbon::now()->subMonth()->startOfMonth();
                    $previousPeriodEnd = Carbon::now()->subMonth()->endOfMonth();
                    break;
                case 'this_year':
                    $currentPeriodStart = Carbon::now()->startOfYear();
                    $currentPeriodEnd = Carbon::now()->endOfYear();
                    $previousPeriodStart = Carbon::now()->subYear()->startOfYear();
                    $previousPeriodEnd = Carbon::now()->subYear()->endOfYear();
                    break;
            }

            $currentAmount = PartnerSales::whereIn('partner_id', $partnerIds)
                ->whereBetween('created_at', [$currentPeriodStart, $currentPeriodEnd])
                ->where('status', 'confirmed')
                ->sum('amount');

            $previousAmount = PartnerSales::whereIn('partner_id', $partnerIds)
                ->whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])
                ->where('status', 'confirmed')
                ->sum('amount');

            if ($previousAmount > 0) {
                $growthRate = round((($currentAmount - $previousAmount) / $previousAmount) * 100, 1);
            } else {
                $growthRate = $currentAmount > 0 ? 100 : 0;
            }

            return [
                'rate' => $growthRate,
                'current_amount' => $currentAmount,
                'previous_amount' => $previousAmount,
                'is_positive' => $growthRate >= 0
            ];

        } catch (\Exception $e) {
            \Log::error('Growth rate calculation error: ' . $e->getMessage());
            return ['rate' => 0, 'current_amount' => 0, 'previous_amount' => 0, 'is_positive' => true];
        }
    }
}