<?php

namespace Jiny\Partner\Http\Controllers\Home\Sales;

use Jiny\Partner\Http\Controllers\Home\HomeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Jiny\Partner\Models\PartnerSales;
use Jiny\Partner\Models\PartnerUser;

class HistoryController extends HomeController
{
    /**
     * 판매 이력 조회
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

            $viewData = [
                'user' => $user,
                'partnerUser' => $partnerUser,
                'salesHistory' => $salesHistory,
                'filteredStats' => $filteredStats,
                'statusStats' => $statusStats,
                'currentPeriod' => $period,
                'currentStatus' => $status,
                'pageTitle' => '판매 이력'
            ];

            if ($request->wantsJson()) {
                return $this->successResponse($viewData);
            }

            return view('jiny-partner::home.sales.history', $viewData);

        } catch (\Exception $e) {
            return $this->errorResponse('판매 이력을 불러오는 중 오류가 발생했습니다.', ['error' => $e->getMessage()]);
        }
    }
}