<?php

namespace Jiny\Partner\Http\Controllers\Home\Sales;

use Jiny\Partner\Http\Controllers\Home\HomeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Jiny\Partner\Models\PartnerSales;
use Jiny\Partner\Models\PartnerUser;

class IndexController extends HomeController
{
    /**
     * 파트너 세일즈 대시보드
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

            // 최근 판매 데이터 조회
            $recentSales = PartnerSales::where('partner_id', $partnerUser->id)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // 판매 통계 계산
            $salesStats = [
                'total_sales' => PartnerSales::where('partner_id', $partnerUser->id)->count(),
                'monthly_sales' => PartnerSales::where('partner_id', $partnerUser->id)
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'total_amount' => PartnerSales::where('partner_id', $partnerUser->id)
                    ->sum('amount'),
                'monthly_amount' => PartnerSales::where('partner_id', $partnerUser->id)
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->sum('amount'),
                'avg_sale_amount' => PartnerSales::where('partner_id', $partnerUser->id)
                    ->avg('amount') ?? 0,
                'success_rate' => $this->calculateSuccessRate($partnerUser->id)
            ];

            // 월별 판매 트렌드 데이터
            $monthlyTrend = $this->getMonthlyTrend($partnerUser->id);

            $viewData = [
                'user' => $user,
                'partnerUser' => $partnerUser,
                'recentSales' => $recentSales,
                'salesStats' => $salesStats,
                'monthlyTrend' => $monthlyTrend,
                'pageTitle' => '판매 대시보드'
            ];

            if ($request->wantsJson()) {
                return $this->successResponse($viewData);
            }

            return view('jiny-partner::home.sales.index', $viewData);

        } catch (\Exception $e) {
            return $this->errorResponse('판매 정보를 불러오는 중 오류가 발생했습니다.', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 성공률 계산
     */
    private function calculateSuccessRate($partnerId)
    {
        $totalSales = PartnerSales::where('partner_id', $partnerId)->count();
        $successfulSales = PartnerSales::where('partner_id', $partnerId)
            ->where('status', 'confirmed')
            ->count();

        if ($totalSales === 0) {
            return 0;
        }

        return round(($successfulSales / $totalSales) * 100, 2);
    }

    /**
     * 월별 판매 트렌드 조회
     */
    private function getMonthlyTrend($partnerId)
    {
        $months = [];
        $salesData = [];
        $amountData = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('Y-m');

            $monthlySales = PartnerSales::where('partner_id', $partnerId)
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();

            $monthlyAmount = PartnerSales::where('partner_id', $partnerId)
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('amount');

            $salesData[] = $monthlySales;
            $amountData[] = $monthlyAmount;
        }

        return [
            'months' => $months,
            'sales' => $salesData,
            'amounts' => $amountData
        ];
    }
}