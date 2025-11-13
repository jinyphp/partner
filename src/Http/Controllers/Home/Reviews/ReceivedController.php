<?php

namespace Jiny\Partner\Http\Controllers\Home\Reviews;

use Jiny\Partner\Http\Controllers\PartnerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Jiny\Partner\Models\PartnerUser;

class ReceivedController extends PartnerController
{
    /**
     * 받은 리뷰 목록
     */
    public function __invoke(Request $request)
    {
        try {
            // 세션 인증 확인
            $user = $this->auth($request);
            if (!$user) {
                return redirect()->route('login')->with('error', '로그인이 필요합니다.');
            }

            // 파트너 사용자 정보 조회 (UUID 기반, 샤딩 지원)
            $partnerUser = PartnerUser::with('tier')->where('user_uuid', $user->uuid)->first();

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

            // 필터링 옵션
            $rating = $request->get('rating', 'all'); // all, 5, 4, 3, 2, 1
            $period = $request->get('period', 'all'); // all, this_month, last_month, this_year
            $visibility = $request->get('visibility', 'all'); // all, public, private
            $perPage = $request->get('per_page', 20);

            // 받은 리뷰 목록 (임시 데이터 - 실제로는 리뷰 모델에서 조회)
            $receivedReviews = collect([
                // 임시 데이터 예시
                (object) [
                    'id' => 1,
                    'reviewer_name' => '고객1',
                    'rating' => 5,
                    'comment' => '매우 만족스러운 서비스였습니다.',
                    'created_at' => now()->subDays(1),
                    'project_title' => '웹사이트 개발',
                    'is_public' => true
                ],
                (object) [
                    'id' => 2,
                    'reviewer_name' => '고객2',
                    'rating' => 4,
                    'comment' => '전반적으로 좋았습니다.',
                    'created_at' => now()->subDays(3),
                    'project_title' => '앱 개발',
                    'is_public' => true
                ]
            ]);

            // 필터링 적용
            if ($rating !== 'all') {
                $receivedReviews = $receivedReviews->where('rating', $rating);
            }

            if ($visibility !== 'all') {
                $isPublic = $visibility === 'public';
                $receivedReviews = $receivedReviews->where('is_public', $isPublic);
            }

            // 통계 정보 (뷰에서 기대하는 형식으로 맞춤)
            $reviewStats = [
                'total_received' => $receivedReviews->count(),
                'average_rating' => $receivedReviews->avg('rating') ?? 0,
                'five_star_count' => $receivedReviews->where('rating', 5)->count(),
                'this_month_count' => $receivedReviews->where('created_at', '>=', now()->startOfMonth())->count(),
                'rating_distribution' => [
                    5 => $receivedReviews->where('rating', 5)->count(),
                    4 => $receivedReviews->where('rating', 4)->count(),
                    3 => $receivedReviews->where('rating', 3)->count(),
                    2 => $receivedReviews->where('rating', 2)->count(),
                    1 => $receivedReviews->where('rating', 1)->count()
                ],
                'public_count' => $receivedReviews->where('is_public', true)->count(),
                'private_count' => $receivedReviews->where('is_public', false)->count()
            ];

            $viewData = [
                'user' => $user,
                'partnerUser' => $partnerUser,
                'receivedReviews' => $receivedReviews,
                'reviewStats' => $reviewStats,
                'currentRating' => $rating,
                'currentPeriod' => $period,
                'currentVisibility' => $visibility,
                'pageTitle' => '받은 리뷰'
            ];

            if ($request->wantsJson()) {
                return $this->successResponse($viewData);
            }

            return view('jiny-partner::home.reviews.received', $viewData);

        } catch (\Exception $e) {
            \Log::error('Partner reviews received error: ' . $e->getMessage(), [
                'user_id' => $user->id ?? 'unknown',
                'user_uuid' => $user->uuid ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('home.partner.reviews.index')
                ->with('error', '받은 리뷰를 불러오는 중 오류가 발생했습니다.');
        }
    }
}