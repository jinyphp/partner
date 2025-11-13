<?php

namespace Jiny\Partner\Http\Controllers\Home\Commission;

use Jiny\Partner\Http\Controllers\PartnerController;
use Illuminate\Http\Request;
use Jiny\Partner\Models\PartnerCommission;

class IndexController extends PartnerController
{
    /**
     * 커미션 대시보드
     */
    public function __invoke(Request $request)
    {
        try {
            // 파트너 인증 및 정보 조회 (공통 로직 사용)
            $authResult = $this->authenticateAndGetPartner($request, 'commission');
            if (!$authResult['success']) {
                return $authResult['redirect'];
            }

            $user = $authResult['user'];
            $partnerUser = $authResult['partner'];

            // 최근 커미션 데이터 조회
            $recentCommissions = PartnerCommission::where('partner_id', $partnerUser->id)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // 커미션 통계 계산
            $commissionStats = [
                'total_commission' => PartnerCommission::where('partner_id', $partnerUser->id)
                    ->where('status', 'paid')
                    ->sum('commission_amount'),
                'pending_commission' => PartnerCommission::where('partner_id', $partnerUser->id)
                    ->where('status', 'pending')
                    ->sum('commission_amount'),
                'monthly_commission' => PartnerCommission::where('partner_id', $partnerUser->id)
                    ->where('status', 'paid')
                    ->whereBetween('created_at', [
                        now()->startOfMonth(),
                        now()->endOfMonth()
                    ])
                    ->sum('commission_amount'),
                'total_transactions' => PartnerCommission::where('partner_id', $partnerUser->id)->count(),
                'avg_commission' => PartnerCommission::where('partner_id', $partnerUser->id)
                    ->where('status', 'paid')
                    ->avg('commission_amount') ?? 0,
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

            // 표준 뷰 데이터 구성 (공통 로직 사용)
            $viewData = $this->getStandardViewData($user, $partnerUser, [
                'recentCommissions' => $recentCommissions,
                'commissionStats' => $commissionStats,
                'monthlyTrend' => $monthlyTrend,
                'typeStats' => $typeStats,
                'dailyCommissions' => $dailyCommissions,
                'nextPayment' => $nextPayment
            ], '커미션 현황');

            // JSON 응답 처리 (공통 로직 사용)
            $jsonResponse = $this->handleJsonResponse($request, $viewData);
            if ($jsonResponse) {
                return $jsonResponse;
            }

            return view('jiny-partner::home.commission.index', $viewData);

        } catch (\Exception $e) {
            // 공통 에러 처리 로직 사용
            return $this->handlePartnerError(
                $e,
                $user ?? null,
                'commission',
                'home.partner.index',
                '커미션 정보를 불러오는 중 오류가 발생했습니다.'
            );
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
            $date = now()->copy()->subMonths($i);
            $months[] = $date->format('Y-m');

            $monthlyCommission = PartnerCommission::where('partner_id', $partnerId)
                ->where('status', 'paid')
                ->whereBetween('created_at', [
                    $date->copy()->startOfMonth(),
                    $date->copy()->endOfMonth()
                ])
                ->sum('commission_amount');

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
            ->selectRaw('commission_type, COUNT(*) as count, SUM(commission_amount) as total_amount')
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
                ->sum('commission_amount');

            $amounts[] = $dailyAmount;
        }

        return [
            'dates' => $dates,
            'amounts' => $amounts
        ];
    }
}