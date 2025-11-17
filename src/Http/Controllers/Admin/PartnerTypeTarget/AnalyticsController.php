<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerTypeTarget;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerType;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * 파트너 타입별 고급 분석 컨트롤러
 *
 * =======================================================================
 * 📊 핵심 기능
 * =======================================================================
 * ✓ 타입별 심화 성과 분석
 * ✓ 연간/분기별 트렌드 분석
 * ✓ 타입 간 비교 분석
 * ✓ 예측 및 인사이트 제공
 * ✓ 전략적 의사결정 지원 데이터
 */
class AnalyticsController extends Controller
{
    /**
     * 고급 분석 대시보드
     */
    public function __invoke(Request $request)
    {
        // =============================================================
        // 📅 분석 기간 설정
        // =============================================================
        $period = $request->get('period', 'year'); // year, quarter, custom
        $year = $request->get('year', Carbon::now()->year);
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if ($period === 'custom' && $startDate && $endDate) {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
        } else {
            $start = Carbon::create($year, 1, 1);
            $end = Carbon::create($year, 12, 31);
        }

        // =============================================================
        // 🏷️ 활성 파트너 타입 조회
        // =============================================================
        $partnerTypes = PartnerType::active()->ordered()->get();

        // =============================================================
        // 📈 고급 분석 데이터 수집
        // =============================================================
        $analyticsData = [
            'performance_comparison' => $this->getPerformanceComparison($partnerTypes, $start, $end),
            'growth_analysis' => $this->getGrowthAnalysis($partnerTypes, $year),
            'efficiency_metrics' => $this->getEfficiencyMetrics($partnerTypes, $start, $end),
            'market_share' => $this->getMarketShareAnalysis($partnerTypes, $start, $end),
            'seasonal_patterns' => $this->getSeasonalPatterns($partnerTypes, $year),
            'roi_analysis' => $this->getROIAnalysis($partnerTypes, $start, $end),
            'predictive_insights' => $this->getPredictiveInsights($partnerTypes),
        ];

        return view('jiny-partner::admin.partner-type-target.analytics', [
            'pageTitle' => '파트너 타입별 고급 분석',
            'partnerTypes' => $partnerTypes,
            'analyticsData' => $analyticsData,
            'period' => $period,
            'year' => $year,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'availableYears' => $this->getAvailableYears(),
        ]);
    }

    /**
     * 타입별 성과 비교 분석
     */
    private function getPerformanceComparison($partnerTypes, $start, $end)
    {
        $comparison = [];

        foreach ($partnerTypes as $type) {
            $stats = DB::table('partner_users')
                ->join('partner_sales', 'partner_users.id', '=', 'partner_sales.partner_id')
                ->where('partner_users.partner_type_id', $type->id)
                ->whereBetween('partner_sales.sales_date', [$start, $end])
                ->where('partner_sales.status', 'confirmed')
                ->select([
                    DB::raw('COUNT(DISTINCT partner_users.id) as active_partners'),
                    DB::raw('SUM(partner_sales.amount) as total_sales'),
                    DB::raw('AVG(partner_sales.amount) as avg_sale'),
                    DB::raw('COUNT(partner_sales.id) as total_cases'),
                    DB::raw('SUM(partner_sales.total_commission_amount) as total_commission'),
                ])
                ->first();

            $comparison[] = [
                'type' => $type,
                'metrics' => [
                    'active_partners' => $stats->active_partners ?? 0,
                    'total_sales' => $stats->total_sales ?? 0,
                    'avg_sale' => $stats->avg_sale ?? 0,
                    'total_cases' => $stats->total_cases ?? 0,
                    'total_commission' => $stats->total_commission ?? 0,
                    'sales_per_partner' => ($stats->active_partners ?? 0) > 0 ?
                        round(($stats->total_sales ?? 0) / $stats->active_partners, 0) : 0,
                    'commission_rate' => ($stats->total_sales ?? 0) > 0 ?
                        round((($stats->total_commission ?? 0) / ($stats->total_sales ?? 0)) * 100, 2) : 0,
                ]
            ];
        }

        return $comparison;
    }

    /**
     * 성장률 분석 (전년 동기 대비)
     */
    private function getGrowthAnalysis($partnerTypes, $currentYear)
    {
        $previousYear = $currentYear - 1;
        $growth = [];

        foreach ($partnerTypes as $type) {
            // 현재 년도 실적
            $currentStats = $this->getYearlyStats($type->id, $currentYear);

            // 이전 년도 실적
            $previousStats = $this->getYearlyStats($type->id, $previousYear);

            $salesGrowth = $this->calculateGrowthRate(
                $previousStats->sales ?? 0,
                $currentStats->sales ?? 0
            );

            $partnersGrowth = $this->calculateGrowthRate(
                $previousStats->partners ?? 0,
                $currentStats->partners ?? 0
            );

            $casesGrowth = $this->calculateGrowthRate(
                $previousStats->cases ?? 0,
                $currentStats->cases ?? 0
            );

            $growth[] = [
                'type' => $type,
                'current_year' => $currentStats,
                'previous_year' => $previousStats,
                'growth_rates' => [
                    'sales' => $salesGrowth,
                    'partners' => $partnersGrowth,
                    'cases' => $casesGrowth,
                ],
                'trend' => $this->determineTrend($salesGrowth, $partnersGrowth, $casesGrowth),
            ];
        }

        return $growth;
    }

    /**
     * 연도별 통계 조회
     */
    private function getYearlyStats($typeId, $year)
    {
        return DB::table('partner_users')
            ->join('partner_sales', 'partner_users.id', '=', 'partner_sales.partner_id')
            ->where('partner_users.partner_type_id', $typeId)
            ->whereRaw('strftime("%Y", partner_sales.sales_date) = ?', [$year])
            ->where('partner_sales.status', 'confirmed')
            ->select([
                DB::raw('COUNT(DISTINCT partner_users.id) as partners'),
                DB::raw('SUM(partner_sales.amount) as sales'),
                DB::raw('COUNT(partner_sales.id) as cases'),
            ])
            ->first() ?? (object)['partners' => 0, 'sales' => 0, 'cases' => 0];
    }

    /**
     * 성장률 계산
     */
    private function calculateGrowthRate($previous, $current)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * 트렌드 판정
     */
    private function determineTrend($salesGrowth, $partnersGrowth, $casesGrowth)
    {
        $avgGrowth = ($salesGrowth + $partnersGrowth + $casesGrowth) / 3;

        if ($avgGrowth >= 15) return 'strong_growth';
        if ($avgGrowth >= 5) return 'moderate_growth';
        if ($avgGrowth >= -5) return 'stable';
        if ($avgGrowth >= -15) return 'declining';
        return 'concerning';
    }

    /**
     * 효율성 지표 분석
     */
    private function getEfficiencyMetrics($partnerTypes, $start, $end)
    {
        $metrics = [];

        foreach ($partnerTypes as $type) {
            $stats = DB::table('partner_users')
                ->join('partner_sales', 'partner_users.id', '=', 'partner_sales.partner_id')
                ->where('partner_users.partner_type_id', $type->id)
                ->whereBetween('partner_sales.sales_date', [$start, $end])
                ->where('partner_sales.status', 'confirmed')
                ->select([
                    DB::raw('COUNT(DISTINCT partner_users.id) as partners'),
                    DB::raw('SUM(partner_sales.amount) as sales'),
                    DB::raw('COUNT(partner_sales.id) as cases'),
                    DB::raw('SUM(partner_sales.total_commission_amount) as commission'),
                    DB::raw('AVG(julianday(partner_sales.updated_at) - julianday(partner_sales.created_at)) as avg_close_time'),
                ])
                ->first();

            $metrics[] = [
                'type' => $type,
                'efficiency' => [
                    'revenue_per_partner' => ($stats->partners ?? 0) > 0 ?
                        round(($stats->sales ?? 0) / $stats->partners, 0) : 0,
                    'cases_per_partner' => ($stats->partners ?? 0) > 0 ?
                        round(($stats->cases ?? 0) / $stats->partners, 1) : 0,
                    'avg_deal_size' => ($stats->cases ?? 0) > 0 ?
                        round(($stats->sales ?? 0) / $stats->cases, 0) : 0,
                    'commission_efficiency' => ($stats->sales ?? 0) > 0 ?
                        round((($stats->commission ?? 0) / ($stats->sales ?? 0)) * 100, 2) : 0,
                    'avg_close_time' => round($stats->avg_close_time ?? 0, 1),
                ]
            ];
        }

        return $metrics;
    }

    /**
     * 시장 점유율 분석
     */
    private function getMarketShareAnalysis($partnerTypes, $start, $end)
    {
        // 전체 매출 계산
        $totalSales = DB::table('partner_sales')
            ->whereBetween('sales_date', [$start, $end])
            ->where('status', 'confirmed')
            ->sum('amount');

        $marketShare = [];

        foreach ($partnerTypes as $type) {
            $typeSales = DB::table('partner_users')
                ->join('partner_sales', 'partner_users.id', '=', 'partner_sales.partner_id')
                ->where('partner_users.partner_type_id', $type->id)
                ->whereBetween('partner_sales.sales_date', [$start, $end])
                ->where('partner_sales.status', 'confirmed')
                ->sum('partner_sales.amount');

            $sharePercentage = $totalSales > 0 ? round(($typeSales / $totalSales) * 100, 2) : 0;

            $marketShare[] = [
                'type' => $type,
                'sales' => $typeSales,
                'share_percentage' => $sharePercentage,
                'rank' => 0, // 나중에 정렬 후 순위 부여
            ];
        }

        // 매출순으로 정렬 후 순위 부여
        usort($marketShare, fn($a, $b) => $b['sales'] <=> $a['sales']);
        foreach ($marketShare as $index => &$item) {
            $item['rank'] = $index + 1;
        }

        return $marketShare;
    }

    /**
     * 계절성 패턴 분석
     */
    private function getSeasonalPatterns($partnerTypes, $year)
    {
        $patterns = [];

        foreach ($partnerTypes as $type) {
            $monthlyData = [];

            for ($month = 1; $month <= 12; $month++) {
                $sales = DB::table('partner_users')
                    ->join('partner_sales', 'partner_users.id', '=', 'partner_sales.partner_id')
                    ->where('partner_users.partner_type_id', $type->id)
                    ->whereRaw('strftime("%Y", partner_sales.sales_date) = ?', [$year])
                    ->whereRaw('strftime("%m", partner_sales.sales_date) = ?', [sprintf('%02d', $month)])
                    ->where('partner_sales.status', 'confirmed')
                    ->sum('partner_sales.amount') ?? 0;

                $monthlyData[] = [
                    'month' => $month,
                    'month_name' => Carbon::create($year, $month, 1)->format('M'),
                    'sales' => $sales,
                ];
            }

            // 계절성 지수 계산 (평균 대비)
            $avgMonthlySales = array_sum(array_column($monthlyData, 'sales')) / 12;
            foreach ($monthlyData as &$data) {
                $data['seasonal_index'] = $avgMonthlySales > 0 ?
                    round(($data['sales'] / $avgMonthlySales) * 100, 1) : 100;
            }

            $patterns[] = [
                'type' => $type,
                'monthly_data' => $monthlyData,
                'peak_month' => $this->findPeakMonth($monthlyData),
                'low_month' => $this->findLowMonth($monthlyData),
            ];
        }

        return $patterns;
    }

    /**
     * 최고 성과 월 찾기
     */
    private function findPeakMonth($monthlyData)
    {
        return array_reduce($monthlyData,
            fn($carry, $item) => ($item['sales'] > ($carry['sales'] ?? 0)) ? $item : $carry,
            []
        );
    }

    /**
     * 최저 성과 월 찾기
     */
    private function findLowMonth($monthlyData)
    {
        return array_reduce($monthlyData,
            fn($carry, $item) => ($item['sales'] < ($carry['sales'] ?? PHP_INT_MAX)) ? $item : $carry,
            []
        );
    }

    /**
     * ROI 분석
     */
    private function getROIAnalysis($partnerTypes, $start, $end)
    {
        $roi = [];

        foreach ($partnerTypes as $type) {
            $stats = DB::table('partner_users')
                ->join('partner_sales', 'partner_users.id', '=', 'partner_sales.partner_id')
                ->where('partner_users.partner_type_id', $type->id)
                ->whereBetween('partner_sales.sales_date', [$start, $end])
                ->where('partner_sales.status', 'confirmed')
                ->select([
                    DB::raw('SUM(partner_sales.amount) as revenue'),
                    DB::raw('SUM(partner_sales.total_commission_amount) as commission_cost'),
                    DB::raw('COUNT(DISTINCT partner_users.id) as partners'),
                ])
                ->first();

            $revenue = $stats->revenue ?? 0;
            $commissionCost = $stats->commission_cost ?? 0;
            $partners = $stats->partners ?? 0;

            // 추가 비용 계산 (등록비, 유지비 등)
            $additionalCosts = $partners * ($type->registration_fee + $type->monthly_maintenance_fee * 6); // 6개월 평균

            $totalCosts = $commissionCost + $additionalCosts;
            $netProfit = $revenue - $totalCosts;
            $roiPercentage = $totalCosts > 0 ? round(($netProfit / $totalCosts) * 100, 2) : 0;

            $roi[] = [
                'type' => $type,
                'revenue' => $revenue,
                'commission_cost' => $commissionCost,
                'additional_costs' => $additionalCosts,
                'total_costs' => $totalCosts,
                'net_profit' => $netProfit,
                'roi_percentage' => $roiPercentage,
                'profit_margin' => $revenue > 0 ? round(($netProfit / $revenue) * 100, 2) : 0,
            ];
        }

        return $roi;
    }

    /**
     * 예측 인사이트 제공
     */
    private function getPredictiveInsights($partnerTypes)
    {
        $insights = [];

        foreach ($partnerTypes as $type) {
            // 최근 3개월 트렌드 분석
            $trends = [];
            for ($i = 2; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $sales = DB::table('partner_users')
                    ->join('partner_sales', 'partner_users.id', '=', 'partner_sales.partner_id')
                    ->where('partner_users.partner_type_id', $type->id)
                    ->whereYear('partner_sales.sales_date', $date->year)
                    ->whereMonth('partner_sales.sales_date', $date->month)
                    ->where('partner_sales.status', 'confirmed')
                    ->sum('partner_sales.amount') ?? 0;

                $trends[] = $sales;
            }

            // 단순 선형 예측 (다음 달)
            $nextMonthPrediction = $this->linearForecast($trends);

            // 인사이트 생성
            $insight = $this->generateInsight($type, $trends, $nextMonthPrediction);

            $insights[] = [
                'type' => $type,
                'current_trend' => $trends,
                'next_month_prediction' => $nextMonthPrediction,
                'insight' => $insight,
            ];
        }

        return $insights;
    }

    /**
     * 선형 예측
     */
    private function linearForecast($trends)
    {
        if (count($trends) < 2) return 0;

        $n = count($trends);
        $x = array_keys($trends);
        $y = array_values($trends);

        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = array_sum(array_map(fn($i) => $x[$i] * $y[$i], array_keys($x)));
        $sumX2 = array_sum(array_map(fn($val) => $val * $val, $x));

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;

        return max(0, round($slope * $n + $intercept, 0));
    }

    /**
     * 인사이트 생성
     */
    private function generateInsight($type, $trends, $prediction)
    {
        $current = end($trends);
        $previous = $trends[count($trends) - 2] ?? 0;

        $changeRate = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;

        if ($changeRate > 10) {
            return "📈 {$type->type_name} 타입이 강한 성장세를 보이고 있습니다. 다음 달 예상 매출은 " . number_format($prediction) . "원입니다.";
        } elseif ($changeRate < -10) {
            return "📉 {$type->type_name} 타입의 성과가 하락하고 있습니다. 개선 전략이 필요합니다.";
        } else {
            return "📊 {$type->type_name} 타입이 안정적인 성과를 유지하고 있습니다.";
        }
    }

    /**
     * 이용 가능한 연도 목록 조회
     */
    private function getAvailableYears()
    {
        $years = DB::table('partner_sales')
            ->selectRaw('DISTINCT strftime("%Y", sales_date) as year')
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        if (empty($years)) {
            $years = [Carbon::now()->year];
        }

        return $years;
    }
}