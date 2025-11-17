<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerTypeTarget;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerType;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * 특정 파트너 타입 상세 실적 컨트롤러
 *
 * =======================================================================
 * 📊 핵심 기능
 * =======================================================================
 * ✓ 특정 타입의 상세 실적 분석
 * ✓ 해당 타입 파트너들의 개별 성과 조회
 * ✓ 월별/분기별/연별 상세 트렌드
 * ✓ 목표 대비 달성 현황
 * ✓ 파트너 랭킹 및 성과 분포
 */
class DetailController extends Controller
{
    /**
     * 특정 파트너 타입 상세 조회
     */
    public function __invoke(Request $request, $typeId)
    {
        // =============================================================
        // 🏷️ 파트너 타입 조회
        // =============================================================
        $partnerType = PartnerType::findOrFail($typeId);

        // =============================================================
        // 📅 기간 설정
        // =============================================================
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        $selectedYear = $request->get('year', $currentYear);
        $selectedMonth = $request->get('month', $currentMonth);
        $view = $request->get('view', 'monthly'); // monthly, quarterly, yearly

        // =============================================================
        // 📊 상세 데이터 수집
        // =============================================================
        $detailData = [
            'overview' => $this->getOverviewData($partnerType, $selectedYear, $selectedMonth),
            'performance_timeline' => $this->getPerformanceTimeline($partnerType, $selectedYear, $view),
            'partner_rankings' => $this->getPartnerRankings($partnerType, $selectedYear, $selectedMonth),
            'goal_achievement' => $this->getGoalAchievement($partnerType, $selectedYear, $selectedMonth),
            'performance_distribution' => $this->getPerformanceDistribution($partnerType, $selectedYear, $selectedMonth),
            'top_performers' => $this->getTopPerformers($partnerType, $selectedYear, $selectedMonth),
            'underperformers' => $this->getUnderperformers($partnerType, $selectedYear, $selectedMonth),
            'regional_analysis' => $this->getRegionalAnalysis($partnerType, $selectedYear, $selectedMonth),
        ];

        return view('jiny-partner::admin.partner-type-target.detail', [
            'pageTitle' => $partnerType->type_name . ' 상세 실적',
            'partnerType' => $partnerType,
            'detailData' => $detailData,
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'view' => $view,
            'availableYears' => $this->getAvailableYears(),
        ]);
    }

    /**
     * 개요 데이터
     */
    private function getOverviewData($partnerType, $year, $month)
    {
        // 현재 월 실적
        $currentMonth = DB::table('partner_users')
            ->join('partner_sales', 'partner_users.id', '=', 'partner_sales.partner_id')
            ->where('partner_users.partner_type_id', $partnerType->id)
            ->whereRaw('strftime("%Y", partner_sales.sales_date) = ?', [$year])
            ->whereRaw('strftime("%m", partner_sales.sales_date) = ?', [sprintf('%02d', $month)])
            ->where('partner_sales.status', 'confirmed')
            ->select([
                DB::raw('COUNT(DISTINCT partner_users.id) as active_partners'),
                DB::raw('SUM(partner_sales.amount) as total_sales'),
                DB::raw('COUNT(partner_sales.id) as total_cases'),
                DB::raw('SUM(partner_sales.total_commission_amount) as total_commission'),
                DB::raw('AVG(partner_sales.amount) as avg_amount'),
            ])
            ->first();

        // 전체 파트너 수
        $totalPartners = PartnerUser::where('partner_type_id', $partnerType->id)
            ->where('status', 'active')
            ->count();

        // 전월 대비 증감
        $previousMonth = $month > 1 ? $month - 1 : 12;
        $previousYear = $month > 1 ? $year : $year - 1;

        $previousMonthStats = DB::table('partner_users')
            ->join('partner_sales', 'partner_users.id', '=', 'partner_sales.partner_id')
            ->where('partner_users.partner_type_id', $partnerType->id)
            ->whereYear('partner_sales.sales_date', $previousYear)
            ->whereMonth('partner_sales.sales_date', $previousMonth)
            ->where('partner_sales.status', 'confirmed')
            ->select([
                DB::raw('SUM(partner_sales.amount) as total_sales'),
                DB::raw('COUNT(partner_sales.id) as total_cases'),
            ])
            ->first();

        // 증감률 계산
        $salesGrowth = $this->calculateGrowthRate(
            $previousMonthStats->total_sales ?? 0,
            $currentMonth->total_sales ?? 0
        );

        $casesGrowth = $this->calculateGrowthRate(
            $previousMonthStats->total_cases ?? 0,
            $currentMonth->total_cases ?? 0
        );

        return [
            'current_month' => $currentMonth,
            'total_partners' => $totalPartners,
            'participation_rate' => $totalPartners > 0 ? round(($currentMonth->active_partners ?? 0) / $totalPartners * 100, 1) : 0,
            'growth_rates' => [
                'sales' => $salesGrowth,
                'cases' => $casesGrowth,
            ],
            'efficiency_metrics' => [
                'sales_per_partner' => ($currentMonth->active_partners ?? 0) > 0 ?
                    round(($currentMonth->total_sales ?? 0) / $currentMonth->active_partners, 0) : 0,
                'cases_per_partner' => ($currentMonth->active_partners ?? 0) > 0 ?
                    round(($currentMonth->total_cases ?? 0) / $currentMonth->active_partners, 1) : 0,
                'commission_rate' => ($currentMonth->total_sales ?? 0) > 0 ?
                    round((($currentMonth->total_commission ?? 0) / ($currentMonth->total_sales ?? 0)) * 100, 2) : 0,
            ]
        ];
    }

    /**
     * 성과 타임라인
     */
    private function getPerformanceTimeline($partnerType, $year, $view)
    {
        $timeline = [];

        switch ($view) {
            case 'quarterly':
                for ($quarter = 1; $quarter <= 4; $quarter++) {
                    $startMonth = ($quarter - 1) * 3 + 1;
                    $endMonth = $quarter * 3;

                    $stats = DB::table('partner_users')
                        ->join('partner_sales', 'partner_users.id', '=', 'partner_sales.partner_id')
                        ->where('partner_users.partner_type_id', $partnerType->id)
                        ->whereRaw('strftime("%Y", partner_sales.sales_date) = ?', [$year])
                        ->whereBetween(DB::raw('strftime("%m", partner_sales.sales_date)'), [sprintf('%02d', $startMonth), sprintf('%02d', $endMonth)])
                        ->where('partner_sales.status', 'confirmed')
                        ->select([
                            DB::raw('SUM(partner_sales.amount) as sales'),
                            DB::raw('COUNT(partner_sales.id) as cases'),
                            DB::raw('COUNT(DISTINCT partner_users.id) as partners')
                        ])
                        ->first();

                    $timeline[] = [
                        'period' => "Q{$quarter}",
                        'period_name' => "{$quarter}분기",
                        'sales' => $stats->sales ?? 0,
                        'cases' => $stats->cases ?? 0,
                        'partners' => $stats->partners ?? 0,
                    ];
                }
                break;

            case 'yearly':
                for ($y = $year - 2; $y <= $year; $y++) {
                    $stats = DB::table('partner_users')
                        ->join('partner_sales', 'partner_users.id', '=', 'partner_sales.partner_id')
                        ->where('partner_users.partner_type_id', $partnerType->id)
                        ->whereYear('partner_sales.sales_date', $y)
                        ->where('partner_sales.status', 'confirmed')
                        ->select([
                            DB::raw('SUM(partner_sales.amount) as sales'),
                            DB::raw('COUNT(partner_sales.id) as cases'),
                            DB::raw('COUNT(DISTINCT partner_users.id) as partners')
                        ])
                        ->first();

                    $timeline[] = [
                        'period' => $y,
                        'period_name' => "{$y}년",
                        'sales' => $stats->sales ?? 0,
                        'cases' => $stats->cases ?? 0,
                        'partners' => $stats->partners ?? 0,
                    ];
                }
                break;

            default: // monthly
                for ($month = 1; $month <= 12; $month++) {
                    $stats = DB::table('partner_users')
                        ->join('partner_sales', 'partner_users.id', '=', 'partner_sales.partner_id')
                        ->where('partner_users.partner_type_id', $partnerType->id)
                        ->whereRaw('strftime("%Y", partner_sales.sales_date) = ?', [$year])
                        ->whereRaw('strftime("%m", partner_sales.sales_date) = ?', [sprintf('%02d', $month)])
                        ->where('partner_sales.status', 'confirmed')
                        ->select([
                            DB::raw('SUM(partner_sales.amount) as sales'),
                            DB::raw('COUNT(partner_sales.id) as cases'),
                            DB::raw('COUNT(DISTINCT partner_users.id) as partners')
                        ])
                        ->first();

                    $timeline[] = [
                        'period' => $month,
                        'period_name' => Carbon::create($year, $month, 1)->format('M'),
                        'sales' => $stats->sales ?? 0,
                        'cases' => $stats->cases ?? 0,
                        'partners' => $stats->partners ?? 0,
                    ];
                }
        }

        return $timeline;
    }

    /**
     * 파트너 랭킹
     */
    private function getPartnerRankings($partnerType, $year, $month, $limit = 10)
    {
        return DB::table('partner_users')
            ->join('partner_sales', 'partner_users.id', '=', 'partner_sales.partner_id')
            ->join('users', 'partner_users.user_id', '=', 'users.id')
            ->where('partner_users.partner_type_id', $partnerType->id)
            ->whereRaw('strftime("%Y", partner_sales.sales_date) = ?', [$year])
            ->whereRaw('strftime("%m", partner_sales.sales_date) = ?', [sprintf('%02d', $month)])
            ->where('partner_sales.status', 'confirmed')
            ->select([
                'partner_users.id',
                'users.name',
                'partner_users.partner_code',
                'partner_users.tier_level',
                DB::raw('SUM(partner_sales.amount) as total_sales'),
                DB::raw('SUM(partner_sales.total_commission_amount) as total_commission'),
                DB::raw('COUNT(partner_sales.id) as total_cases'),
                DB::raw('AVG(partner_sales.amount) as avg_amount'),
            ])
            ->groupBy('partner_users.id', 'users.name', 'partner_users.partner_code', 'partner_users.tier_level')
            ->orderBy('total_sales', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($partner, $index) {
                $partner->rank = $index + 1;
                $partner->achievement_rate = $this->calculateAchievementRate($partner->total_sales);
                return $partner;
            });
    }

    /**
     * 목표 달성 현황
     */
    private function getGoalAchievement($partnerType, $year, $month)
    {
        // 타입별 월 목표 (기준치의 150%)
        $monthlyTarget = $partnerType->min_baseline_sales * 1.5;

        // 현재 실적
        $currentPerformance = DB::table('partner_users')
            ->join('partner_sales', 'partner_users.id', '=', 'partner_sales.partner_id')
            ->where('partner_users.partner_type_id', $partnerType->id)
            ->whereRaw('strftime("%Y", partner_sales.sales_date) = ?', [$year])
            ->whereRaw('strftime("%m", partner_sales.sales_date) = ?', [sprintf('%02d', $month)])
            ->where('partner_sales.status', 'confirmed')
            ->sum('partner_sales.amount') ?? 0;

        $achievementRate = $monthlyTarget > 0 ? ($currentPerformance / $monthlyTarget) * 100 : 0;

        // 목표 달성한 파트너 수
        $achievingPartners = DB::table('partner_users')
            ->join('partner_sales', 'partner_users.id', '=', 'partner_sales.partner_id')
            ->where('partner_users.partner_type_id', $partnerType->id)
            ->whereRaw('strftime("%Y", partner_sales.sales_date) = ?', [$year])
            ->whereRaw('strftime("%m", partner_sales.sales_date) = ?', [sprintf('%02d', $month)])
            ->where('partner_sales.status', 'confirmed')
            ->select([
                'partner_users.id',
                DB::raw('SUM(partner_sales.amount) as partner_sales')
            ])
            ->groupBy('partner_users.id')
            ->havingRaw('partner_sales >= ?', [$partnerType->min_baseline_sales])
            ->count();

        $totalActivePartners = PartnerUser::where('partner_type_id', $partnerType->id)
            ->where('status', 'active')
            ->count();

        return [
            'monthly_target' => $monthlyTarget,
            'current_performance' => $currentPerformance,
            'achievement_rate' => round($achievementRate, 1),
            'status' => $this->getAchievementStatus($achievementRate),
            'achieving_partners' => $achievingPartners,
            'total_active_partners' => $totalActivePartners,
            'partner_achievement_rate' => $totalActivePartners > 0 ? round(($achievingPartners / $totalActivePartners) * 100, 1) : 0,
        ];
    }

    /**
     * 성과 분포
     */
    private function getPerformanceDistribution($partnerType, $year, $month)
    {
        $partnerPerformances = DB::table('partner_users')
            ->leftJoin('partner_sales', function ($join) use ($year, $month) {
                $join->on('partner_users.id', '=', 'partner_sales.partner_id')
                    ->whereRaw('strftime("%Y", partner_sales.sales_date) = ?', [$year])
                    ->whereRaw('strftime("%m", partner_sales.sales_date) = ?', [sprintf('%02d', $month)])
                    ->where('partner_sales.status', 'confirmed');
            })
            ->where('partner_users.partner_type_id', $partnerType->id)
            ->where('partner_users.status', 'active')
            ->select([
                'partner_users.id',
                DB::raw('COALESCE(SUM(partner_sales.amount), 0) as sales')
            ])
            ->groupBy('partner_users.id')
            ->get()
            ->pluck('sales')
            ->toArray();

        // 성과 구간별 분포
        $ranges = [
            'no_sales' => 0,
            'low' => 0,        // 0 ~ 기준치 50%
            'medium' => 0,     // 기준치 50% ~ 100%
            'high' => 0,       // 기준치 100% ~ 150%
            'excellent' => 0,  // 기준치 150% 이상
        ];

        $baseline = $partnerType->min_baseline_sales;

        foreach ($partnerPerformances as $sales) {
            if ($sales == 0) {
                $ranges['no_sales']++;
            } elseif ($sales < $baseline * 0.5) {
                $ranges['low']++;
            } elseif ($sales < $baseline) {
                $ranges['medium']++;
            } elseif ($sales < $baseline * 1.5) {
                $ranges['high']++;
            } else {
                $ranges['excellent']++;
            }
        }

        return [
            'ranges' => $ranges,
            'total_partners' => count($partnerPerformances),
            'avg_performance' => count($partnerPerformances) > 0 ? round(array_sum($partnerPerformances) / count($partnerPerformances), 0) : 0,
            'median_performance' => $this->calculateMedian($partnerPerformances),
        ];
    }

    /**
     * 최고 성과자
     */
    private function getTopPerformers($partnerType, $year, $month, $limit = 5)
    {
        return $this->getPartnerRankings($partnerType, $year, $month, $limit);
    }

    /**
     * 성과 미달자
     */
    private function getUnderperformers($partnerType, $year, $month, $limit = 5)
    {
        return DB::table('partner_users')
            ->leftJoin('partner_sales', function ($join) use ($year, $month) {
                $join->on('partner_users.id', '=', 'partner_sales.partner_id')
                    ->whereRaw('strftime("%Y", partner_sales.sales_date) = ?', [$year])
                    ->whereRaw('strftime("%m", partner_sales.sales_date) = ?', [sprintf('%02d', $month)])
                    ->where('partner_sales.status', 'confirmed');
            })
            ->join('users', 'partner_users.user_id', '=', 'users.id')
            ->where('partner_users.partner_type_id', $partnerType->id)
            ->where('partner_users.status', 'active')
            ->select([
                'partner_users.id',
                'users.name',
                'partner_users.partner_code',
                'partner_users.tier_level',
                DB::raw('COALESCE(SUM(partner_sales.amount), 0) as total_sales'),
                DB::raw('COALESCE(COUNT(partner_sales.id), 0) as total_cases'),
            ])
            ->groupBy('partner_users.id', 'users.name', 'partner_users.partner_code', 'partner_users.tier_level')
            ->having('total_sales', '<', $partnerType->min_baseline_sales)
            ->orderBy('total_sales', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * 지역별 분석 (간단한 예시)
     */
    private function getRegionalAnalysis($partnerType, $year, $month)
    {
        // 파트너의 주소 정보가 있다면 지역별 분석 가능
        // 현재는 샘플 데이터로 구성
        return [
            'regions' => [
                ['name' => '서울', 'partners' => 15, 'sales' => 12500000],
                ['name' => '경기', 'partners' => 8, 'sales' => 8200000],
                ['name' => '부산', 'partners' => 5, 'sales' => 4100000],
                ['name' => '기타', 'partners' => 3, 'sales' => 2200000],
            ]
        ];
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
     * 달성률 상태 반환
     */
    private function getAchievementStatus($rate)
    {
        if ($rate >= 120) return 'excellent';
        if ($rate >= 100) return 'success';
        if ($rate >= 80) return 'warning';
        return 'danger';
    }

    /**
     * 개별 달성률 계산
     */
    private function calculateAchievementRate($sales)
    {
        // 간단한 예시: 500만원을 기준으로 달성률 계산
        $baseTarget = 5000000;
        return $baseTarget > 0 ? round(($sales / $baseTarget) * 100, 1) : 0;
    }

    /**
     * 중앙값 계산
     */
    private function calculateMedian($array)
    {
        if (empty($array)) return 0;

        sort($array);
        $count = count($array);
        $middle = floor($count / 2);

        if ($count % 2) {
            return $array[$middle];
        } else {
            return ($array[$middle - 1] + $array[$middle]) / 2;
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