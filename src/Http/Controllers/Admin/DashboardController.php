<?php

namespace Jiny\Partner\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerTier;
use Jiny\Partner\Models\PartnerType;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerCommission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * 파트너 관리 대시보드
     */
    public function __invoke(Request $request)
    {
        // 기본 통계 데이터
        $statistics = $this->getBasicStatistics();

        // 최근 활동 데이터
        $recentActivities = $this->getRecentActivities();

        // 성과 데이터
        $performanceData = $this->getPerformanceData();

        // 등급별 분포 데이터
        $tierDistribution = $this->getTierDistribution();

        // 타입별 분포 데이터
        $typeDistribution = $this->getTypeDistribution();

        // 월별 성장 데이터
        $monthlyGrowth = $this->getMonthlyGrowth();

        return view('jiny-partner::admin.dashboard.index', [
            'title' => '파트너 관리 대시보드',
            'statistics' => $statistics,
            'recentActivities' => $recentActivities,
            'performanceData' => $performanceData,
            'tierDistribution' => $tierDistribution,
            'typeDistribution' => $typeDistribution,
            'monthlyGrowth' => $monthlyGrowth
        ]);
    }

    /**
     * 기본 통계 데이터
     */
    private function getBasicStatistics()
    {
        // 파트너 통계
        $totalPartners = PartnerUser::count();
        $activePartners = PartnerUser::where('status', 'active')->count();

        // 신청서 통계 (application_status 컬럼 사용)
        $totalApplications = PartnerApplication::count();
        $pendingApplications = PartnerApplication::whereIn('application_status', ['submitted', 'reviewing'])->count();
        $submittedApplications = PartnerApplication::where('application_status', 'submitted')->count();
        $reviewingApplications = PartnerApplication::where('application_status', 'reviewing')->count();
        $interviewApplications = PartnerApplication::where('application_status', 'interview')->count();
        $approvedApplications = PartnerApplication::where('application_status', 'approved')->count();
        $rejectedApplications = PartnerApplication::where('application_status', 'rejected')->count();

        // 처리 시간 계산 (SQLite 호환)
        $avgProcessingDays = PartnerApplication::whereNotNull('approval_date')
                                             ->get()
                                             ->map(function($app) {
                                                 return $app->approval_date->diffInDays($app->created_at);
                                             })
                                             ->avg() ?? 0;

        // 이번달 신청서 수
        $thisMonthApplications = PartnerApplication::whereMonth('created_at', now()->month)
                                                 ->whereYear('created_at', now()->year)
                                                 ->count();

        return [
            'total_partners' => $totalPartners,
            'active_partners' => $activePartners,
            'pending_applications' => $pendingApplications,
            'total_tiers' => PartnerTier::where('is_active', true)->count(),
            'total_types' => PartnerType::where('is_active', true)->count(),
            'monthly_commissions' => PartnerCommission::whereMonth('created_at', now()->month)
                                                   ->whereYear('created_at', now()->year)
                                                   ->sum('commission_amount'),
            'total_sales' => PartnerUser::sum('total_sales') ?? 0,
            'average_rating' => PartnerUser::avg('average_rating') ?? 0,

            // 신청서 상태별 통계
            'total_applications' => $totalApplications,
            'submitted_applications' => $submittedApplications,
            'reviewing_applications' => $reviewingApplications,
            'interview_applications' => $interviewApplications,
            'approved_applications' => $approvedApplications,
            'rejected_applications' => $rejectedApplications,

            // 처리 관련 통계
            'avg_processing_days' => round($avgProcessingDays),
            'this_month_applications' => $thisMonthApplications
        ];
    }

    /**
     * 최근 활동 데이터
     */
    private function getRecentActivities()
    {
        // 처리 대기중인 신청서 (우선도별 정렬: 오래된 것부터, 그 다음 최신순)
        $pendingApplications = PartnerApplication::whereIn('application_status', ['submitted', 'reviewing', 'interview'])
                                               ->orderByRaw('
                                                   CASE WHEN created_at <= ? THEN 0 ELSE 1 END,
                                                   CASE WHEN created_at <= ? THEN created_at ELSE created_at END DESC
                                               ', [now()->subDays(3), now()->subDays(3)])
                                               ->limit(15)
                                               ->get();

        return [
            'recent_partners' => PartnerUser::with(['tier', 'type'])
                                           ->orderBy('created_at', 'desc')
                                           ->limit(5)
                                           ->get(),
            'recent_applications' => PartnerApplication::whereIn('application_status', ['approved', 'rejected'])
                                                      ->orderBy('updated_at', 'desc')
                                                      ->limit(5)
                                                      ->get(),
            'recent_commissions' => PartnerCommission::with('partner')
                                                     ->orderBy('created_at', 'desc')
                                                     ->limit(5)
                                                     ->get(),
            // 통합된 처리 대기중 신청서
            'pending_applications' => $pendingApplications
        ];
    }

    /**
     * 성과 데이터
     */
    private function getPerformanceData()
    {
        return [
            'top_performers' => PartnerUser::with(['tier', 'type'])
                                          ->where('status', 'active')
                                          ->orderBy('total_sales', 'desc')
                                          ->limit(10)
                                          ->get(),
            'commission_trends' => $this->getCommissionTrends(),
            'sales_trends' => $this->getSalesTrends()
        ];
    }

    /**
     * 등급별 분포 데이터
     */
    private function getTierDistribution()
    {
        return PartnerUser::select('partner_tier_id', DB::raw('count(*) as count'))
                          ->with('tier')
                          ->groupBy('partner_tier_id')
                          ->get()
                          ->map(function ($item) {
                              return [
                                  'tier_name' => $item->tier->tier_name ?? '미지정',
                                  'tier_code' => $item->tier->tier_code ?? 'unknown',
                                  'count' => $item->count,
                                  'color' => $this->getTierColor($item->tier->tier_code ?? 'unknown')
                              ];
                          });
    }

    /**
     * 타입별 분포 데이터
     */
    private function getTypeDistribution()
    {
        return PartnerUser::select('partner_type_id', DB::raw('count(*) as count'))
                          ->with('type')
                          ->groupBy('partner_type_id')
                          ->get()
                          ->map(function ($item) {
                              return [
                                  'type_name' => $item->type->type_name ?? '미지정',
                                  'type_code' => $item->type->type_code ?? 'unknown',
                                  'count' => $item->count,
                                  'color' => $item->type->color ?? '#007bff'
                              ];
                          });
    }

    /**
     * 월별 성장 데이터
     */
    private function getMonthlyGrowth()
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $months[] = [
                'month' => $month->format('Y-m'),
                'label' => $month->format('Y년 m월'),
                'partners' => PartnerUser::whereYear('created_at', $month->year)
                                        ->whereMonth('created_at', $month->month)
                                        ->count(),
                'sales' => PartnerUser::whereYear('created_at', '<=', $month->year)
                                     ->whereMonth('created_at', '<=', $month->month)
                                     ->sum('monthly_sales') ?? 0,
                'commissions' => PartnerCommission::whereYear('created_at', $month->year)
                                                  ->whereMonth('created_at', $month->month)
                                                  ->sum('commission_amount') ?? 0
            ];
        }
        return collect($months);
    }

    /**
     * 커미션 트렌드 데이터
     */
    private function getCommissionTrends()
    {
        return PartnerCommission::select(
                                    DB::raw('DATE(created_at) as date'),
                                    DB::raw('SUM(commission_amount) as total')
                                )
                                ->where('created_at', '>=', now()->subDays(30))
                                ->groupBy('date')
                                ->orderBy('date')
                                ->get();
    }

    /**
     * 매출 트렌드 데이터
     */
    private function getSalesTrends()
    {
        return PartnerUser::select(
                              DB::raw('DATE(updated_at) as date'),
                              DB::raw('SUM(monthly_sales) as total')
                          )
                          ->where('updated_at', '>=', now()->subDays(30))
                          ->groupBy('date')
                          ->orderBy('date')
                          ->get();
    }

    /**
     * 등급별 색상 반환
     */
    private function getTierColor($tierCode)
    {
        $colors = [
            'bronze' => '#CD7F32',
            'silver' => '#C0C0C0',
            'gold' => '#FFD700',
            'platinum' => '#E5E4E2',
            'BRONZE' => '#CD7F32',
            'SILVER' => '#C0C0C0',
            'GOLD' => '#FFD700',
            'PLATINUM' => '#E5E4E2',
            'DIAMOND' => '#B9F2FF'
        ];

        return $colors[strtoupper($tierCode)] ?? '#6c757d';
    }
}