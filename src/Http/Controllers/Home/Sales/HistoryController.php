<?php

namespace Jiny\Partner\Http\Controllers\Home\Sales;

use Jiny\Partner\Http\Controllers\PartnerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Jiny\Partner\Models\PartnerSales;
use Jiny\Partner\Models\PartnerUser;

class HistoryController extends PartnerController
{
    /**
     * 판매 이력 조회
     */
    public function __invoke(Request $request)
    {
        try {
            // 파트너 인증 및 정보 조회 (공통 로직 사용)
            $authResult = $this->authenticateAndGetPartner($request, 'sales_history');
            if (!$authResult['success']) {
                return $authResult['redirect'];
            }

            $user = $authResult['user'];
            $partnerUser = $authResult['partner'];

            // 필터링 옵션
            $period = $request->get('period', 'all'); // all, this_month, last_month, this_year
            $status = $request->get('status', 'all'); // all, pending, confirmed, cancelled
            $perPage = $request->get('per_page', 20);

            // 기본 쿼리
            $query = PartnerSales::where('partner_id', $partnerUser->id);

            // 기간 필터
            switch ($period) {
                case 'this_month':
                    $query->whereMonth('created_at', now()->month)
                          ->whereYear('created_at', now()->year);
                    break;
                case 'last_month':
                    $lastMonth = now()->subMonth();
                    $query->whereMonth('created_at', $lastMonth->month)
                          ->whereYear('created_at', $lastMonth->year);
                    break;
                case 'this_year':
                    $query->whereYear('created_at', now()->year);
                    break;
            }

            // 상태 필터
            if ($status !== 'all') {
                $query->where('status', $status);
            }

            // 판매 이력 조회 (페이지네이션)
            $salesHistory = $query->orderBy('created_at', 'desc')
                ->paginate($perPage);

            // title 필드를 product_name으로 매핑
            $salesHistory->getCollection()->transform(function($sale) {
                $sale->product_name = $sale->title;
                return $sale;
            });

            // 필터링된 통계
            $filteredStats = [
                'total_count' => $query->count(),
                'total_amount' => $query->sum('amount'),
                'avg_amount' => $query->avg('amount') ?? 0,
                'confirmed_count' => $query->where('status', 'confirmed')->count(),
                'pending_count' => $query->where('status', 'pending')->count(),
                'cancelled_count' => $query->where('status', 'cancelled')->count()
            ];

            // 상태별 통계
            $statusStats = [
                'confirmed' => PartnerSales::where('partner_id', $partnerUser->id)
                    ->where('status', 'confirmed')->count(),
                'pending' => PartnerSales::where('partner_id', $partnerUser->id)
                    ->where('status', 'pending')->count(),
                'cancelled' => PartnerSales::where('partner_id', $partnerUser->id)
                    ->where('status', 'cancelled')->count()
            ];

            // 표준 뷰 데이터 구성 (공통 로직 사용)
            $viewData = $this->getStandardViewData($user, $partnerUser, [
                'salesHistory' => $salesHistory,
                'filteredStats' => $filteredStats,
                'statusStats' => $statusStats,
                'currentPeriod' => $period,
                'currentStatus' => $status
            ], '판매 이력');

            // JSON 응답 처리 (공통 로직 사용)
            $jsonResponse = $this->handleJsonResponse($request, $viewData);
            if ($jsonResponse) {
                return $jsonResponse;
            }

            return view('jiny-partner::home.sales.history', $viewData);

        } catch (\Exception $e) {
            // 공통 에러 처리 로직 사용
            return $this->handlePartnerError(
                $e,
                $user ?? null,
                'sales_history',
                'home.partner.sales.index',
                '판매 이력을 불러오는 중 오류가 발생했습니다.'
            );
        }
    }
}