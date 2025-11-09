<?php

namespace Jiny\Partner\Http\Controllers\Home\Commission;

use Jiny\Partner\Http\Controllers\Home\HomeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Jiny\Partner\Models\PartnerCommission;
use Jiny\Partner\Models\PartnerUser;

class IndexController extends HomeController
{
    /**
     * 커미션 대시보드
     */
    public function __invoke(Request $request)
    {
        try {
            // JWT 인증 확인
            $user = $this->auth($request);
            if (!$user) {
                return $this->errorResponse('인증이 필요합니다.');
            }

            // 파트너 사용자 정보 조회
            $partnerUser = PartnerUser::where('user_id', $user->id ?? $user['id'])
                ->where('status', 'active')
                ->first();

            if (!$partnerUser) {
                return $this->errorResponse('파트너 권한이 없습니다.');
            }

            // 최근 커미션 데이터 조회
            $recentCommissions = PartnerCommission::where('partner_id', $partnerUser->id)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // 커미션 통계 계산
            $commissionStats = [
                'total_commission' => PartnerCommission::where('partner_id', $partnerUser->id)
                    ->where('status', 'paid')
                    ->sum('amount'),
                'pending_commission' => PartnerCommission::where('partner_id', $partnerUser->id)
                    ->where('status', 'pending')
                    ->sum('amount'),
                'monthly_commission' => PartnerCommission::where('partner_id', $partnerUser->id)
                    ->where('status', 'paid')
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->sum('amount'),
                'total_transactions' => PartnerCommission::where('partner_id', $partnerUser->id)->count(),
                'avg_commission' => PartnerCommission::where('partner_id', $partnerUser->id)
                    ->where('status', 'paid')
                    ->avg('amount') ?? 0,
                'commission_rate' => $partnerUser->tier->commission_rate ?? 0
            ];

            // 월별 커미션 트렌드 데이터
            $monthlyTrend = $this->getMonthlyCommissionTrend($partnerUser->id);

            // 커미션 타입별 통계
            $typeStats = $this->getCommissionTypeStats($partnerUser->id);

            // 최근 30일 일별 커미션
            $dailyCommissions = $this->getDailyCommissions($partnerUser->id);

            // 다음 지급 예정 커미션
            $nextPayment = PartnerCommission::where('partner_id', $partnerUser->id)
                ->where('status', 'pending')
                ->whereNotNull('payment_date')
                ->orderBy('payment_date', 'asc')
                ->first();

            $viewData = [
                'user' => $user,
                'partnerUser' => $partnerUser,
                'recentCommissions' => $recentCommissions,
                'commissionStats' => $commissionStats,
                'monthlyTrend' => $monthlyTrend,
                'typeStats' => $typeStats,
                'dailyCommissions' => $dailyCommissions,
                'nextPayment' => $nextPayment,
                'pageTitle' => '커미션 현황'
            ];

            if ($request->wantsJson()) {
                return $this->successResponse($viewData);
            }

            return view('jiny-partner::home.commission.index', $viewData);

        } catch (\Exception $e) {
            return $this->errorResponse('커미션 정보를 불러오는 중 오류가 발생했습니다.', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 월별 커미션 트렌드 조회
     */
    private function getMonthlyCommissionTrend($partnerId)
    {
        $months = [];
        $commissionData = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('Y-m');

            $monthlyCommission = PartnerCommission::where('partner_id', $partnerId)
                ->where('status', 'paid')
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('amount');

            $commissionData[] = $monthlyCommission;
        }

        return [
            'months' => $months,
            'commissions' => $commissionData
        ];
    }

    /**
     * 커미션 타입별 통계
     */
    private function getCommissionTypeStats($partnerId)
    {
        return PartnerCommission::where('partner_id', $partnerId)
            ->where('status', 'paid')
            ->selectRaw('commission_type, COUNT(*) as count, SUM(amount) as total_amount')
            ->groupBy('commission_type')
            ->orderBy('total_amount', 'desc')
            ->get();
    }

    /**
     * 최근 30일 일별 커미션
     */
    private function getDailyCommissions($partnerId)
    {
        $dates = [];
        $amounts = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dates[] = $date->format('m-d');

            $dailyAmount = PartnerCommission::where('partner_id', $partnerId)
                ->where('status', 'paid')
                ->whereDate('created_at', $date)
                ->sum('amount');

            $amounts[] = $dailyAmount;
        }

        return [
            'dates' => $dates,
            'amounts' => $amounts
        ];
    }
}