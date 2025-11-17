<?php

namespace Jiny\Partner\Http\Controllers\Home\Commission;

use Jiny\Partner\Http\Controllers\PartnerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Jiny\Partner\Models\PartnerCommission;
use Jiny\Partner\Models\PartnerUser;

class HistoryController extends PartnerController
{
    /**
     * 커미션 이력 조회
     */
    public function __invoke(Request $request)
    {
        try {
            // 세션 인증 확인
            $user = $this->auth($request);
            if (!$user) {
                return redirect()->route('login')->with('error', '로그인이 필요합니다.');
            }

            // 파트너 사용자 정보 조회 (UUID 기반)
            $partnerUser = PartnerUser::where('user_uuid', $user->uuid)->first();

            if (!$partnerUser) {
                // 파트너 신청 정보 확인
                $partnerApplication = \Jiny\Partner\Models\PartnerApplication::where('user_uuid', $user->uuid)
                    ->latest()
                    ->first();

                if ($partnerApplication) {
                    return redirect()->route('home.partner.regist.status')
                        ->with('info', '파트너 신청이 아직 처리 중입니다.');
                } else {
                    return redirect()->route('home.partner.intro')
                        ->with('info', '파트너 프로그램에 먼저 가입해 주세요.');
                }
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
                    $query->whereBetween('created_at', [
                        now()->startOfMonth(),
                        now()->endOfMonth()
                    ]);
                    break;
                case 'last_month':
                    $lastMonth = now()->copy()->subMonth();
                    $query->whereBetween('created_at', [
                        $lastMonth->copy()->startOfMonth(),
                        $lastMonth->copy()->endOfMonth()
                    ]);
                    break;
                case 'this_year':
                    $query->whereBetween('created_at', [
                        now()->startOfYear(),
                        now()->endOfYear()
                    ]);
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
                'total_amount' => $query->sum('commission_amount'),
                'avg_amount' => $query->avg('commission_amount') ?? 0,
                'paid_amount' => $query->where('status', 'paid')->sum('commission_amount'),
                'pending_amount' => $query->where('status', 'pending')->sum('commission_amount'),
                'cancelled_amount' => $query->where('status', 'cancelled')->sum('commission_amount')
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
                'direct_sales' => PartnerCommission::where('partner_id', $partnerUser->id)
                    ->where('commission_type', 'direct_sales')->sum('commission_amount'),
                'team_bonus' => PartnerCommission::where('partner_id', $partnerUser->id)
                    ->where('commission_type', 'team_bonus')->sum('commission_amount'),
                'management_bonus' => PartnerCommission::where('partner_id', $partnerUser->id)
                    ->where('commission_type', 'management_bonus')->sum('commission_amount'),
                'override_bonus' => PartnerCommission::where('partner_id', $partnerUser->id)
                    ->where('commission_type', 'override_bonus')->sum('commission_amount'),
                'recruitment_bonus' => PartnerCommission::where('partner_id', $partnerUser->id)
                    ->where('commission_type', 'recruitment_bonus')->sum('commission_amount')
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
            \Log::error('Partner commission history error: ' . $e->getMessage(), [
                'user_id' => $user->id ?? 'unknown',
                'user_uuid' => $user->uuid ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('home.partner.commission.index')
                ->with('error', '커미션 이력을 불러오는 중 오류가 발생했습니다.');
        }
    }
}