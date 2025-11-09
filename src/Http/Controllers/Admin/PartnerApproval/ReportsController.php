<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerApproval;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    /**
     * ¸ ¹x ¬ì¸
     */
    public function __invoke(Request $request)
    {
        $reportType = $request->get('type', 'summary');
        $period = $request->get('period', '30days');

        switch ($reportType) {
            case 'detailed':
                return $this->detailedReport($period);
            case 'tier_analysis':
                return $this->tierAnalysisReport($period);
            case 'referrer_performance':
                return $this->referrerPerformanceReport($period);
            default:
                return $this->summaryReport($period);
        }
    }

    /**
     * ”} ¬ì¸
     */
    private function summaryReport(string $period)
    {
        $data = [
            'total_applications' => PartnerApplication::count(),
            'active_partners' => PartnerUser::where('status', 'active')->count(),
            'pending_reviews' => PartnerApplication::whereIn('application_status', ['submitted', 'reviewing'])->count(),
            'this_month_approved' => PartnerApplication::where('application_status', 'approved')
                ->whereMonth('approval_date', now()->month)
                ->count()
        ];

        return view('jiny-partner::admin.partner-approval.reports.summary', [
            'data' => $data,
            'period' => $period,
            'title' => '¸ ¹x ”} ¬ì¸'
        ]);
    }

    /**
     * Á8 ¬ì¸
     */
    private function detailedReport(string $period)
    {
        return view('jiny-partner::admin.partner-approval.reports.detailed', [
            'title' => '¸ ¹x Á8 ¬ì¸'
        ]);
    }

    /**
     * ñ	Ä „ ¬ì¸
     */
    private function tierAnalysisReport(string $period)
    {
        return view('jiny-partner::admin.partner-approval.reports.tier-analysis', [
            'title' => 'ñ	Ä „ ¬ì¸'
        ]);
    }

    /**
     * ”œx 1ü ¬ì¸
     */
    private function referrerPerformanceReport(string $period)
    {
        return view('jiny-partner::admin.partner-approval.reports.referrer-performance', [
            'title' => '”œx 1ü ¬ì¸'
        ]);
    }
}