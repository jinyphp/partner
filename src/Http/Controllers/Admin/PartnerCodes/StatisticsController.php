<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerCodes;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    /**
     * 파트너 코드 통계 조회
     */
    public function index(Request $request)
    {
        $period = $request->get('period', '7'); // 기본 7일

        // 기본 통계
        $basicStats = $this->getBasicStatistics();

        // 기간별 통계
        $periodStats = $this->getPeriodStatistics($period);

        // 등급별 통계
        $tierStats = $this->getTierStatistics();

        // 일별 코드 생성 통계 (최근 30일)
        $dailyStats = $this->getDailyStatistics();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'basic' => $basicStats,
                    'period' => $periodStats,
                    'tiers' => $tierStats,
                    'daily' => $dailyStats
                ]
            ]);
        }

        return view('jiny-partner::admin.partner-codes.statistics', [
            'basicStats' => $basicStats,
            'periodStats' => $periodStats,
            'tierStats' => $tierStats,
            'dailyStats' => $dailyStats,
            'pageTitle' => '파트너 코드 통계'
        ]);
    }

    /**
     * 기본 통계 데이터
     */
    private function getBasicStatistics()
    {
        $totalPartners = PartnerUser::count();
        $withCodes = PartnerUser::whereNotNull('partner_code')->count();
        $withoutCodes = $totalPartners - $withCodes;

        return [
            'total_partners' => $totalPartners,
            'with_codes' => $withCodes,
            'without_codes' => $withoutCodes,
            'code_usage_rate' => $totalPartners > 0 ? round(($withCodes / $totalPartners) * 100, 1) : 0
        ];
    }

    /**
     * 기간별 통계 데이터
     */
    private function getPeriodStatistics($days)
    {
        $startDate = now()->subDays($days);

        $generatedInPeriod = PartnerUser::whereNotNull('partner_code')
            ->where('updated_at', '>=', $startDate)
            ->count();

        $newPartnersInPeriod = PartnerUser::where('created_at', '>=', $startDate)->count();

        $newPartnersWithCodes = PartnerUser::whereNotNull('partner_code')
            ->where('created_at', '>=', $startDate)
            ->count();

        return [
            'period_days' => $days,
            'codes_generated' => $generatedInPeriod,
            'new_partners' => $newPartnersInPeriod,
            'new_partners_with_codes' => $newPartnersWithCodes,
            'new_partners_code_rate' => $newPartnersInPeriod > 0 ?
                round(($newPartnersWithCodes / $newPartnersInPeriod) * 100, 1) : 0
        ];
    }

    /**
     * 등급별 통계 데이터
     */
    private function getTierStatistics()
    {
        return PartnerUser::leftJoin('partner_tiers', 'partner_users.tier_id', '=', 'partner_tiers.id')
            ->select(
                'partner_tiers.tier_name',
                DB::raw('COUNT(partner_users.id) as total_count'),
                DB::raw('COUNT(partner_users.partner_code) as code_count'),
                DB::raw('ROUND((COUNT(partner_users.partner_code) / COUNT(partner_users.id)) * 100, 1) as code_rate')
            )
            ->groupBy('partner_tiers.id', 'partner_tiers.tier_name')
            ->orderBy('partner_tiers.tier_order')
            ->get();
    }

    /**
     * 일별 코드 생성 통계 (최근 30일)
     */
    private function getDailyStatistics()
    {
        return PartnerUser::selectRaw('
                DATE(updated_at) as date,
                COUNT(*) as codes_generated
            ')
            ->whereNotNull('partner_code')
            ->where('updated_at', '>=', now()->subDays(30))
            ->groupBy(DB::raw('DATE(updated_at)'))
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'codes_generated' => $item->codes_generated
                ];
            });
    }
}