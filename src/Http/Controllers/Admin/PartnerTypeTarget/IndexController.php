<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerTypeTarget;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerType;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * 파트너 타입별 실적 관리 메인 컨트롤러
 *
 * =======================================================================
 * 📊 핵심 기능
 * =======================================================================
 * ✓ 파트너 타입별 월별/연별 매출 추이 분석
 * ✓ 타입별 실적 목표 대비 달성률 추적
 * ✓ 파트너 개별 성과와 타입 평균 비교
 * ✓ 실시간 대시보드 및 성과 지표 제공
 * ✓ 타입별 수수료 효율성 분석
 */
class IndexController extends Controller
{
    /**
     * 파트너 타입별 실적 관리 대시보드
     */
    public function __invoke(Request $request)
    {
        // =============================================================
        // 📅 기간 설정
        // =============================================================
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        $selectedYear = $request->get('year', $currentYear);
        $selectedMonth = $request->get('month', $currentMonth);

        // =============================================================
        // 🏷️ 활성 파트너 타입 조회
        // =============================================================
        $partnerTypes = PartnerType::active()
            ->ordered()
            ->get();

        // =============================================================
        // 📊 타입별 실적 데이터 수집
        // =============================================================
        $typePerformanceData = $this->getTypePerformanceData($partnerTypes, $selectedYear, $selectedMonth);

        // =============================================================
        // 📈 월별 추이 데이터 (최근 12개월)
        // =============================================================
        $monthlyTrends = $this->getMonthlyTrends($partnerTypes, $selectedYear);

        // =============================================================
        // 🎯 타입별 목표 대비 달성률
        // =============================================================
        $achievementRates = $this->getAchievementRates($partnerTypes, $selectedYear, $selectedMonth);

        // =============================================================
        // 💰 수수료 효율성 분석
        // =============================================================
        $commissionEfficiency = $this->getCommissionEfficiency($partnerTypes, $selectedYear, $selectedMonth);

        // =============================================================
        // 🔝 TOP 파트너 (타입별)
        // =============================================================
        $topPartnersByType = $this->getTopPartnersByType($partnerTypes, $selectedYear, $selectedMonth);

        return view('jiny-partner::admin.partner-type-target.index', [
            'pageTitle' => '파트너 타입별 실적 관리',
            'partnerTypes' => $partnerTypes,
            'typePerformanceData' => $typePerformanceData,
            'monthlyTrends' => $monthlyTrends,
            'achievementRates' => $achievementRates,
            'commissionEfficiency' => $commissionEfficiency,
            'topPartnersByType' => $topPartnersByType,
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'availableYears' => $this->getAvailableYears(),
        ]);
    }

    /**
     * 타입별 실적 데이터 수집
     */
    private function getTypePerformanceData($partnerTypes, $year, $month)
    {
        $performanceData = [];

        foreach ($partnerTypes as $type) {
            // 해당 타입 파트너들의 이번 달 실적
            $monthlyStats = DB::table('partner_users')
                ->join('partner_sales', 'partner_users.id', '=', 'partner_sales.partner_id')
                ->where('partner_users.partner_type_id', $type->id)
                ->whereRaw('strftime("%Y", partner_sales.sales_date) = ?', [$year])
                ->whereRaw('strftime("%m", partner_sales.sales_date) = ?', [sprintf('%02d', $month)])
                ->where('partner_sales.status', 'confirmed')
                ->select([
                    DB::raw('COUNT(DISTINCT partner_users.id) as active_partners'),
                    DB::raw('COUNT(partner_sales.id) as total_cases'),
                    DB::raw('SUM(partner_sales.amount) as total_sales'),
                    DB::raw('SUM(partner_sales.total_commission_amount) as total_commission'),
                    DB::raw('AVG(partner_sales.amount) as avg_sale_amount'),
                ])
                ->first();

            // 연간 누적 실적
            $yearlyStats = DB::table('partner_users')
                ->join('partner_sales', 'partner_users.id', '=', 'partner_sales.partner_id')
                ->where('partner_users.partner_type_id', $type->id)
                ->whereRaw('strftime("%Y", partner_sales.sales_date) = ?', [$year])
                ->where('partner_sales.status', 'confirmed')
                ->select([
                    DB::raw('SUM(partner_sales.amount) as yearly_sales'),
                    DB::raw('SUM(partner_sales.total_commission_amount) as yearly_commission'),
                    DB::raw('COUNT(partner_sales.id) as yearly_cases'),
                ])
                ->first();

            // 전체 파트너 수
            $totalPartners = PartnerUser::where('partner_type_id', $type->id)
                ->where('status', 'active')
                ->count();

            $performanceData[$type->id] = [
                'type' => $type,
                'monthly' => [
                    'active_partners' => $monthlyStats->active_partners ?? 0,
                    'total_partners' => $totalPartners,
                    'total_cases' => $monthlyStats->total_cases ?? 0,
                    'total_sales' => $monthlyStats->total_sales ?? 0,
                    'total_commission' => $monthlyStats->total_commission ?? 0,
                    'avg_sale_amount' => $monthlyStats->avg_sale_amount ?? 0,
                    'participation_rate' => $totalPartners > 0 ? round(($monthlyStats->active_partners ?? 0) / $totalPartners * 100, 1) : 0,
                ],
                'yearly' => [
                    'total_sales' => $yearlyStats->yearly_sales ?? 0,
                    'total_commission' => $yearlyStats->yearly_commission ?? 0,
                    'total_cases' => $yearlyStats->yearly_cases ?? 0,
                ]
            ];
        }

        return $performanceData;
    }

    /**
     * 월별 추이 데이터 (최근 12개월)
     */
    private function getMonthlyTrends($partnerTypes, $year)
    {
        $trends = [];
        $startDate = Carbon::create($year, 1, 1);
        $endDate = Carbon::create($year, 12, 31);

        foreach ($partnerTypes as $type) {
            $monthlyData = [];

            for ($i = 1; $i <= 12; $i++) {
                $monthStats = DB::table('partner_users')
                    ->join('partner_sales', 'partner_users.id', '=', 'partner_sales.partner_id')
                    ->where('partner_users.partner_type_id', $type->id)
                    ->whereRaw('strftime("%Y", partner_sales.sales_date) = ?', [$year])
                    ->whereRaw('strftime("%m", partner_sales.sales_date) = ?', [sprintf('%02d', $i)])
                    ->where('partner_sales.status', 'confirmed')
                    ->select([
                        DB::raw('SUM(partner_sales.amount) as sales'),
                        DB::raw('COUNT(partner_sales.id) as cases'),
                        DB::raw('COUNT(DISTINCT partner_users.id) as partners')
                    ])
                    ->first();

                $monthlyData[] = [
                    'month' => $i,
                    'month_name' => Carbon::create($year, $i, 1)->format('M'),
                    'sales' => $monthStats->sales ?? 0,
                    'cases' => $monthStats->cases ?? 0,
                    'partners' => $monthStats->partners ?? 0,
                ];
            }

            $trends[$type->id] = [
                'type' => $type,
                'data' => $monthlyData
            ];
        }

        return $trends;
    }

    /**
     * 타입별 목표 대비 달성률
     */
    private function getAchievementRates($partnerTypes, $year, $month)
    {
        $achievements = [];

        foreach ($partnerTypes as $type) {
            // 현재 실적
            $currentPerformance = DB::table('partner_users')
                ->join('partner_sales', 'partner_users.id', '=', 'partner_sales.partner_id')
                ->where('partner_users.partner_type_id', $type->id)
                ->whereRaw('strftime("%Y", partner_sales.sales_date) = ?', [$year])
                ->whereRaw('strftime("%m", partner_sales.sales_date) = ?', [sprintf('%02d', $month)])
                ->where('partner_sales.status', 'confirmed')
                ->sum('partner_sales.amount') ?? 0;

            // 목표 설정 (타입별 최소 기준의 150%를 월 목표로 설정)
            $monthlyTarget = $type->min_baseline_sales * 1.5;

            // 달성률 계산
            $achievementRate = $monthlyTarget > 0 ? ($currentPerformance / $monthlyTarget) * 100 : 0;

            $achievements[$type->id] = [
                'type' => $type,
                'current_performance' => $currentPerformance,
                'target' => $monthlyTarget,
                'achievement_rate' => round($achievementRate, 1),
                'status' => $this->getAchievementStatus($achievementRate),
            ];
        }

        return $achievements;
    }

    /**
     * 달성률에 따른 상태 반환
     */
    private function getAchievementStatus($rate)
    {
        if ($rate >= 120) return 'excellent';
        if ($rate >= 100) return 'success';
        if ($rate >= 80) return 'warning';
        return 'danger';
    }

    /**
     * 수수료 효율성 분석
     */
    private function getCommissionEfficiency($partnerTypes, $year, $month)
    {
        $efficiency = [];

        foreach ($partnerTypes as $type) {
            $stats = DB::table('partner_users')
                ->join('partner_sales', 'partner_users.id', '=', 'partner_sales.partner_id')
                ->where('partner_users.partner_type_id', $type->id)
                ->whereRaw('strftime("%Y", partner_sales.sales_date) = ?', [$year])
                ->whereRaw('strftime("%m", partner_sales.sales_date) = ?', [sprintf('%02d', $month)])
                ->where('partner_sales.status', 'confirmed')
                ->select([
                    DB::raw('SUM(partner_sales.amount) as total_sales'),
                    DB::raw('SUM(partner_sales.total_commission_amount) as total_commission'),
                    DB::raw('COUNT(partner_sales.id) as total_cases'),
                ])
                ->first();

            $totalSales = $stats->total_sales ?? 0;
            $totalCommission = $stats->total_commission ?? 0;
            $totalCases = $stats->total_cases ?? 0;

            $efficiency[$type->id] = [
                'type' => $type,
                'commission_rate' => $totalSales > 0 ? round(($totalCommission / $totalSales) * 100, 2) : 0,
                'revenue_per_case' => $totalCases > 0 ? round($totalSales / $totalCases, 0) : 0,
                'commission_per_case' => $totalCases > 0 ? round($totalCommission / $totalCases, 0) : 0,
                'roi' => $totalCommission > 0 ? round($totalSales / $totalCommission, 2) : 0,
            ];
        }

        return $efficiency;
    }

    /**
     * 타입별 TOP 파트너 조회
     */
    private function getTopPartnersByType($partnerTypes, $year, $month, $limit = 3)
    {
        $topPartners = [];

        foreach ($partnerTypes as $type) {
            $partners = DB::table('partner_users')
                ->join('partner_sales', 'partner_users.id', '=', 'partner_sales.partner_id')
                ->join('users', 'partner_users.user_id', '=', 'users.id')
                ->where('partner_users.partner_type_id', $type->id)
                ->whereRaw('strftime("%Y", partner_sales.sales_date) = ?', [$year])
                ->whereRaw('strftime("%m", partner_sales.sales_date) = ?', [sprintf('%02d', $month)])
                ->where('partner_sales.status', 'confirmed')
                ->select([
                    'partner_users.id',
                    'users.name',
                    'partner_users.partner_code',
                    DB::raw('SUM(partner_sales.amount) as total_sales'),
                    DB::raw('SUM(partner_sales.total_commission_amount) as total_commission'),
                    DB::raw('COUNT(partner_sales.id) as total_cases'),
                ])
                ->groupBy('partner_users.id', 'users.name', 'partner_users.partner_code')
                ->orderBy('total_sales', 'desc')
                ->limit($limit)
                ->get();

            $topPartners[$type->id] = [
                'type' => $type,
                'partners' => $partners
            ];
        }

        return $topPartners;
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