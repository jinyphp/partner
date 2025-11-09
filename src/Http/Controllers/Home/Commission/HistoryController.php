<?php

namespace Jiny\Partner\Http\Controllers\Home\Commission;

use Jiny\Partner\Http\Controllers\Home\HomeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Jiny\Partner\Models\PartnerCommission;
use Jiny\Partner\Models\PartnerUser;

class HistoryController extends HomeController
{
    /**
     * 커미션 이력 조회
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
            $status = $request->get('status', 'all'); // all, pending, paid, cancelled
            $type = $request->get('type', 'all'); // all, direct_sale, referral_bonus, tier_bonus
            $perPage = $request->get('per_page', 20);

            // 기본 쿼리
            $query = PartnerCommission::where('partner_id', $partnerUser->id);

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

            // 타입 필터
            if ($type !== 'all') {
                $query->where('commission_type', $type);
            }

            // 커미션 이력 조회 (페이지네이션)
            $commissionHistory = $query->orderBy('created_at', 'desc')
                ->paginate($perPage);

            // 필터링된 통계
            $filteredStats = [
                'total_count' => $query->count(),
                'total_amount' => $query->sum('amount'),
                'avg_amount' => $query->avg('amount') ?? 0,
                'paid_amount' => $query->where('status', 'paid')->sum('amount'),
                'pending_amount' => $query->where('status', 'pending')->sum('amount'),
                'cancelled_amount' => $query->where('status', 'cancelled')->sum('amount')
            ];

            // 상태별 통계
            $statusStats = [
                'paid' => PartnerCommission::where('partner_id', $partnerUser->id)
                    ->where('status', 'paid')->count(),
                'pending' => PartnerCommission::where('partner_id', $partnerUser->id)
                    ->where('status', 'pending')->count(),
                'cancelled' => PartnerCommission::where('partner_id', $partnerUser->id)
                    ->where('status', 'cancelled')->count()
            ];

            // 커미션 타입별 통계
            $typeStats = [
                'direct_sale' => PartnerCommission::where('partner_id', $partnerUser->id)
                    ->where('commission_type', 'direct_sale')->sum('amount'),
                'referral_bonus' => PartnerCommission::where('partner_id', $partnerUser->id)
                    ->where('commission_type', 'referral_bonus')->sum('amount'),
                'tier_bonus' => PartnerCommission::where('partner_id', $partnerUser->id)
                    ->where('commission_type', 'tier_bonus')->sum('amount')
            ];

            $viewData = [
                'user' => $user,
                'partnerUser' => $partnerUser,
                'commissionHistory' => $commissionHistory,
                'filteredStats' => $filteredStats,
                'statusStats' => $statusStats,
                'typeStats' => $typeStats,
                'currentPeriod' => $period,
                'currentStatus' => $status,
                'currentType' => $type,
                'pageTitle' => '커미션 이력'
            ];

            if ($request->wantsJson()) {
                return $this->successResponse($viewData);
            }

            return view('jiny-partner::home.commission.history', $viewData);

        } catch (\Exception $e) {
            return $this->errorResponse('커미션 이력을 불러오는 중 오류가 발생했습니다.', ['error' => $e->getMessage()]);
        }
    }
}