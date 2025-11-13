<?php

namespace Jiny\Partner\Http\Controllers\Home\Reviews;

use Jiny\Partner\Http\Controllers\PartnerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Jiny\Partner\Models\PartnerUser;

class IndexController extends PartnerController
{
    /**
     * 리뷰 현황 대시보드
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
                    return redirect()->route('home.partner.regist.status', $partnerApplication->id)
                        ->with('info', '파트너 신청이 아직 처리 중입니다.');
                } else {
                    return redirect()->route('home.partner.intro')
                        ->with('info', '파트너 프로그램에 먼저 가입해 주세요.');
                }
            }

            // 리뷰 통계 (임시 데이터 - 실제로는 리뷰 모델에서 조회)
            $reviewStats = [
                'total_received' => 0, // 받은 리뷰 총 개수
                'total_given' => 0,    // 작성한 리뷰 총 개수
                'average_rating' => 0, // 평균 평점
                'recent_reviews' => [], // 최근 리뷰
                'rating_distribution' => [ // 평점 분포
                    5 => 0,
                    4 => 0,
                    3 => 0,
                    2 => 0,
                    1 => 0
                ]
            ];

            // 월별 리뷰 트렌드
            $monthlyTrend = [
                'months' => [],
                'received' => [],
                'given' => []
            ];

            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthlyTrend['months'][] = $date->format('Y-m');
                $monthlyTrend['received'][] = 0; // 실제로는 DB에서 조회
                $monthlyTrend['given'][] = 0;    // 실제로는 DB에서 조회
            }

            $viewData = [
                'user' => $user,
                'partnerUser' => $partnerUser,
                'reviewStats' => $reviewStats,
                'monthlyTrend' => $monthlyTrend,
                'pageTitle' => '리뷰 현황'
            ];

            if ($request->wantsJson()) {
                return $this->successResponse($viewData);
            }

            return view('jiny-partner::home.reviews.index', $viewData);

        } catch (\Exception $e) {
            \Log::error('Partner reviews error: ' . $e->getMessage(), [
                'user_id' => $user->id ?? 'unknown',
                'user_uuid' => $user->uuid ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('home.partner.index')
                ->with('error', '리뷰 정보를 불러오는 중 오류가 발생했습니다.');
        }
    }
}