<?php

namespace Jiny\Partner\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerInterview;
use Jiny\Partner\Models\PartnerSales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PartnerDashboardController extends Controller
{
    /**
     * 파트너 현황 대시보드
     */
    public function index(Request $request)
    {
        // 기본 통계
        $stats = $this->getBasicStatistics();

        // 최근 활동
        $recentActivities = $this->getRecentActivities();

        // 파트너 현황 차트 데이터
        $chartData = $this->getChartData();

        // 등급별 분포
        $tierDistribution = $this->getTierDistribution();

        // 월별 성장률
        $monthlyGrowth = $this->getMonthlyGrowth();

        // 최고 성과 파트너들
        $topPerformers = $this->getTopPerformers();

        return view('jiny-partner::admin.partner-dashboard.index', [
            'pageTitle' => '파트너 현황 대시보드',
            'stats' => $stats,
            'recentActivities' => $recentActivities,
            'chartData' => $chartData,
            'tierDistribution' => $tierDistribution,
            'monthlyGrowth' => $monthlyGrowth,
            'topPerformers' => $topPerformers
        ]);
    }

    /**
     * 기본 통계 데이터
     */
    private function getBasicStatistics()
    {
        $totalPartners = PartnerUser::count();
        $activePartners = PartnerUser::where('status', 'active')->count();
        $inactivePartners = $totalPartners - $activePartners;

        $pendingApplications = PartnerApplication::where('status', 'pending')->count();
        $scheduledInterviews = PartnerInterview::where('status', 'scheduled')->count();

        $thisMonthPartners = PartnerUser::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $lastMonthPartners = PartnerUser::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        $growthRate = $lastMonthPartners > 0 ?
            round((($thisMonthPartners - $lastMonthPartners) / $lastMonthPartners) * 100, 1) : 0;

        return [
            'total_partners' => $totalPartners,
            'active_partners' => $activePartners,
            'inactive_partners' => $inactivePartners,
            'pending_applications' => $pendingApplications,
            'scheduled_interviews' => $scheduledInterviews,
            'this_month_partners' => $thisMonthPartners,
            'growth_rate' => $growthRate
        ];
    }

    /**
     * 최근 활동
     */
    private function getRecentActivities()
    {
        $activities = collect();

        // 최근 파트너 등록
        $recentPartners = PartnerUser::with('partnerTier')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function($partner) {
                return [
                    'type' => 'partner_registered',
                    'title' => '새 파트너 등록',
                    'description' => $partner->name . '님이 파트너로 등록되었습니다',
                    'time' => $partner->created_at,
                    'icon' => 'fe-user-plus',
                    'color' => 'success'
                ];
            });

        // 최근 지원서
        $recentApplications = PartnerApplication::latest()
            ->limit(3)
            ->get()
            ->map(function($application) {
                return [
                    'type' => 'application_submitted',
                    'title' => '새 파트너 지원서',
                    'description' => '새로운 파트너 지원서가 접수되었습니다',
                    'time' => $application->created_at,
                    'icon' => 'fe-file-text',
                    'color' => 'info'
                ];
            });

        // 최근 면접
        $recentInterviews = PartnerInterview::with('application')
            ->latest()
            ->limit(3)
            ->get()
            ->map(function($interview) {
                return [
                    'type' => 'interview_scheduled',
                    'title' => '면접 일정',
                    'description' => '파트너 면접이 예약되었습니다',
                    'time' => $interview->created_at,
                    'icon' => 'fe-calendar',
                    'color' => 'warning'
                ];
            });

        return $activities
            ->concat($recentPartners)
            ->concat($recentApplications)
            ->concat($recentInterviews)
            ->sortByDesc('time')
            ->take(10);
    }

    /**
     * 차트 데이터 (최근 6개월 파트너 등록 추이)
     */
    private function getChartData()
    {
        $months = [];
        $data = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('M');

            $count = PartnerUser::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();

            $data[] = $count;
        }

        return [
            'labels' => $months,
            'data' => $data
        ];
    }

    /**
     * 등급별 분포
     */
    private function getTierDistribution()
    {
        return PartnerUser::select('partner_tiers.tier_name', DB::raw('count(*) as count'))
            ->leftJoin('partner_tiers', 'partner_users.partner_tier_id', '=', 'partner_tiers.id')
            ->groupBy('partner_tiers.tier_name')
            ->orderByDesc('count')
            ->get()
            ->mapWithKeys(function($item) {
                return [$item->tier_name ?? 'Bronze' => $item->count];
            });
    }

    /**
     * 월별 성장률
     */
    private function getMonthlyGrowth()
    {
        $growth = [];

        for ($i = 5; $i >= 0; $i--) {
            $currentMonth = now()->subMonths($i);
            $previousMonth = now()->subMonths($i + 1);

            $currentCount = PartnerUser::whereYear('created_at', $currentMonth->year)
                ->whereMonth('created_at', $currentMonth->month)
                ->count();

            $previousCount = PartnerUser::whereYear('created_at', $previousMonth->year)
                ->whereMonth('created_at', $previousMonth->month)
                ->count();

            $rate = $previousCount > 0 ?
                round((($currentCount - $previousCount) / $previousCount) * 100, 1) : 0;

            $growth[] = [
                'month' => $currentMonth->format('M'),
                'count' => $currentCount,
                'rate' => $rate
            ];
        }

        return $growth;
    }

    /**
     * 최고 성과 파트너들
     */
    private function getTopPerformers()
    {
        return PartnerUser::with(['partnerTier'])
            ->withCount(['children as children_count'])
            ->where('status', 'active')
            ->orderByDesc('children_count')
            ->orderByDesc('monthly_sales')
            ->limit(10)
            ->get()
            ->map(function($partner) {
                return [
                    'id' => $partner->id,
                    'name' => $partner->name,
                    'email' => $partner->email,
                    'tier' => $partner->partnerTier ? $partner->partnerTier->tier_name : 'Bronze',
                    'children_count' => $partner->children_count ?? 0,
                    'monthly_sales' => $partner->monthly_sales ?? 0,
                    'performance_score' => $this->calculatePerformanceScore($partner)
                ];
            });
    }

    /**
     * 성과 점수 계산
     */
    private function calculatePerformanceScore($partner)
    {
        $childrenScore = min(($partner->children_count ?? 0) * 10, 50); // 최대 50점
        $salesScore = min((($partner->monthly_sales ?? 0) / 1000000) * 10, 30); // 최대 30점
        $tierName = $partner->partnerTier ? $partner->partnerTier->tier_name : 'Bronze';
        $tierScore = $this->getTierScore($tierName); // 최대 20점

        return round($childrenScore + $salesScore + $tierScore, 1);
    }

    /**
     * 등급별 점수
     */
    private function getTierScore($tierName)
    {
        $scores = [
            'Bronze' => 5,
            'Silver' => 10,
            'Gold' => 15,
            'Platinum' => 18,
            'Diamond' => 20
        ];

        return $scores[$tierName] ?? 5;
    }
}