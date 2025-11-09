<?php

namespace Jiny\Partner\Http\Controllers\Home\Dashboard;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Models\PartnerSales;
use Jiny\Partner\Models\PartnerCommission;
use Jiny\Partner\Models\PartnerNetworkRelationship;
use Illuminate\Http\Request;
//use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
//use Jiny\Auth\Http\Controllers\Traits\JWTAuthTrait;

use Jiny\Auth\Http\Controllers\HomeController;
class IndexController extends HomeController
{

    /**
     * 파트너 대시보드 메인 페이지
     * - 파트너 정보 (타입, 티어, 매출액, 커미션)
     * - 매출 기록
     * - 하위 파트너 정보
     */
    public function __invoke(Request $request)
    {
        // Step1. JWT 인증여부 처리
        $user = $this->auth($request);
        if(!$user) {
            return redirect()->route('login')
                ->with('error', 'JWT 인증이 필요합니다. 로그인해 주세요.')
                ->with('info', '파트너 서비스는 로그인 후 이용하실 수 있습니다.');
        }

        // Step2. 현재 사용자의 파트너 정보 조회 (UUID 기반)
        $partner = PartnerUser::with(['partnerType', 'partnerTier'])
            ->where('user_uuid', $user->uuid)
            ->first();

        if (!$partner) {
            return redirect()->route('home.partner.regist.index')
                ->with('info', '파트너 등록이 필요합니다.');
        }

        // Step3. 매출 통계 계산
        $salesStats = $this->calculateSalesStats($partner);

        // Step4. 커미션 통계 계산
        $commissionStats = $this->calculateCommissionStats($partner);

        // Step5. 최근 매출 기록 조회 (최근 10건)
        $recentSales = PartnerSales::where('partner_id', $partner->id)
            ->orderBy('sales_date', 'desc')
            ->limit(10)
            ->get();

        // Step6. 하위 파트너 정보 조회
        $subPartners = $this->getSubPartners($partner);

        // Step7. 네트워크 정보
        $networkInfo = $this->getNetworkInfo($partner);

        return view('jiny-partner::home.dashboard.index', [
            'user' => $user,
            'currentUser' => $user, // 현재 로그인 사용자 정보 명시적 전달
            'partner' => $partner,
            'salesStats' => $salesStats,
            'commissionStats' => $commissionStats,
            'recentSales' => $recentSales,
            'subPartners' => $subPartners,
            'networkInfo' => $networkInfo,
            'pageTitle' => '파트너 대시보드',
            'userInfo' => [
                'name' => $user->name ?? '',
                'email' => $user->email ?? '',
                'phone' => $user->profile->phone ?? '',
                'uuid' => $user->uuid
            ]
        ]);
    }

    /**
     * 매출 통계 계산
     */
    private function calculateSalesStats($partner)
    {
        $currentMonth = now()->format('Y-m');
        $currentYear = now()->format('Y');

        return [
            'monthly_sales' => $partner->monthly_sales ?? 0,
            'total_sales' => $partner->total_sales ?? 0,
            'team_sales' => $partner->team_sales ?? 0,
            'current_month_sales' => PartnerSales::where('partner_id', $partner->id)
                ->where('status', 'confirmed')
                ->whereRaw("strftime('%Y-%m', sales_date) = ?", [$currentMonth])
                ->sum('amount'),
            'current_year_sales' => PartnerSales::where('partner_id', $partner->id)
                ->where('status', 'confirmed')
                ->whereRaw("strftime('%Y', sales_date) = ?", [$currentYear])
                ->sum('amount'),
            'total_sales_count' => PartnerSales::where('partner_id', $partner->id)
                ->where('status', 'confirmed')
                ->count(),
        ];
    }

    /**
     * 커미션 통계 계산
     */
    private function calculateCommissionStats($partner)
    {
        return [
            'total_commission' => PartnerCommission::where('partner_id', $partner->id)
                ->where('status', 'paid')
                ->sum('amount'),
            'pending_commission' => PartnerCommission::where('partner_id', $partner->id)
                ->where('status', 'pending')
                ->sum('amount'),
            'this_month_commission' => PartnerCommission::where('partner_id', $partner->id)
                ->where('status', 'paid')
                ->whereRaw("strftime('%Y-%m', created_at) = ?", [now()->format('Y-m')])
                ->sum('amount'),
            'commission_count' => PartnerCommission::where('partner_id', $partner->id)
                ->count(),
        ];
    }

    /**
     * 하위 파트너 정보 조회
     */
    private function getSubPartners($partner)
    {
        // 네트워크 관계에서 현재 파트너가 parent인 관계들을 조회
        $childrenIds = PartnerNetworkRelationship::where('parent_id', $partner->id)
            ->where('is_active', true)
            ->pluck('child_id');

        return PartnerUser::whereIn('id', $childrenIds)
            ->with(['partnerType', 'partnerTier'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * 네트워크 정보 조회
     */
    private function getNetworkInfo($partner)
    {
        // 상위 파트너 조회
        $parentPartner = null;
        $parentRelationship = PartnerNetworkRelationship::where('child_id', $partner->id)->first();
        if ($parentRelationship) {
            $parentPartner = PartnerUser::with(['partnerType', 'partnerTier'])
                ->find($parentRelationship->parent_id);
        }

        // 하위 파트너 수 계산
        $childrenCount = PartnerNetworkRelationship::where('parent_id', $partner->id)->count();

        // 레벨 계산
        $level = $partner->level ?? 0;

        // 네트워크 경로 계산
        $path = $partner->path ?? '/';

        return [
            'parent_partner' => $parentPartner,
            'children_count' => $childrenCount,
            'level' => $level,
            'path' => $path,
        ];
    }
}
