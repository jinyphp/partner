<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerCodes;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IndexController extends Controller
{
    /**
     * 파트너 코드 관리 메인 페이지
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status', 'all');
        $perPage = $request->get('per_page', 20);

        // 파트너 코드 조회
        $query = PartnerUser::with(['partnerTier'])
            ->select('partner_users.*',
                DB::raw('CASE WHEN partner_code IS NOT NULL THEN "active" ELSE "inactive" END as code_status'));

        // 검색 필터
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('partner_code', 'like', "%{$search}%");
            });
        }

        // 상태 필터
        if ($status !== 'all') {
            if ($status === 'active') {
                $query->whereNotNull('partner_code');
            } elseif ($status === 'inactive') {
                $query->whereNull('partner_code');
            }
        }

        $partners = $query->latest()->paginate($perPage);

        // 통계 데이터
        $statistics = $this->getStatistics();

        return view('jiny-partner::admin.partner-codes.index', [
            'partners' => $partners,
            'statistics' => $statistics,
            'currentFilters' => [
                'search' => $search,
                'status' => $status,
                'per_page' => $perPage
            ],
            'pageTitle' => '파트너 코드 관리'
        ]);
    }

    /**
     * 통계 데이터 수집
     */
    private function getStatistics()
    {
        $totalPartners = PartnerUser::count();
        $withCodes = PartnerUser::whereNotNull('partner_code')->count();
        $withoutCodes = $totalPartners - $withCodes;
        $recentlyGenerated = PartnerUser::whereNotNull('partner_code')
            ->where('updated_at', '>=', now()->subDays(7))
            ->count();

        return [
            'total_partners' => $totalPartners,
            'with_codes' => $withCodes,
            'without_codes' => $withoutCodes,
            'recently_generated' => $recentlyGenerated,
            'code_usage_rate' => $totalPartners > 0 ? round(($withCodes / $totalPartners) * 100, 1) : 0
        ];
    }
}