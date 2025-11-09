<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerApplication;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;

class StatisticsController extends Controller
{
    /**
     * 지원서 통계
     */
    public function __invoke()
    {
        $stats = [
            'total' => PartnerApplication::count(),
            'submitted' => PartnerApplication::where('application_status', 'submitted')->count(),
            'reviewing' => PartnerApplication::where('application_status', 'reviewing')->count(),
            'interview' => PartnerApplication::where('application_status', 'interview')->count(),
            'approved' => PartnerApplication::where('application_status', 'approved')->count(),
            'rejected' => PartnerApplication::where('application_status', 'rejected')->count(),
            'this_month' => PartnerApplication::whereMonth('created_at', now()->month)->count(),
            'this_week' => PartnerApplication::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count()
        ];

        // 승인률 계산
        $processedApplications = $stats['approved'] + $stats['rejected'];
        $stats['approval_rate'] = $processedApplications > 0
            ? round(($stats['approved'] / $processedApplications) * 100, 1)
            : 0;

        // 월별 지원서 수 (최근 6개월)
        $monthlyStats = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthlyStats[] = [
                'month' => $date->format('Y-m'),
                'count' => PartnerApplication::whereYear('created_at', $date->year)
                                          ->whereMonth('created_at', $date->month)
                                          ->count()
            ];
        }

        // 평균 처리 시간
        $avgProcessingTime = PartnerApplication::whereIn('application_status', ['approved', 'rejected'])
                                            ->selectRaw('AVG(julianday(COALESCE(approval_date, rejection_date)) - julianday(created_at)) as avg_days')
                                            ->value('avg_days');

        return view('jiny-partner::admin.partner-applications.statistics', [
            'stats' => $stats,
            'monthlyStats' => $monthlyStats,
            'avgProcessingTime' => round($avgProcessingTime ?? 0, 1),
            'title' => '지원서 통계'
        ]);
    }
}