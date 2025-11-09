<?php

namespace Jiny\Partner\Http\Controllers\Home\Reviews;

use Jiny\Partner\Http\Controllers\Home\HomeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Jiny\Partner\Models\PartnerUser;

class GivenController extends HomeController
{
    /**
     * 작성한 리뷰 목록
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
            $rating = $request->get('rating', 'all'); // all, 5, 4, 3, 2, 1
            $period = $request->get('period', 'all'); // all, this_month, last_month, this_year
            $perPage = $request->get('per_page', 20);

            // 작성한 리뷰 목록 (임시 데이터 - 실제로는 리뷰 모델에서 조회)
            $givenReviews = collect([
                // 임시 데이터 예시
                (object) [
                    'id' => 1,
                    'reviewee_name' => '파트너A',
                    'rating' => 5,
                    'comment' => '훌륭한 협업이었습니다.',
                    'created_at' => now()->subDays(2),
                    'project_title' => '마케팅 캠페인',
                    'is_public' => true,
                    'helpful_count' => 3
                ],
                (object) [
                    'id' => 2,
                    'reviewee_name' => '파트너B',
                    'rating' => 4,
                    'comment' => '만족스러운 결과였습니다.',
                    'created_at' => now()->subDays(5),
                    'project_title' => '브랜딩 작업',
                    'is_public' => true,
                    'helpful_count' => 1
                ]
            ]);

            // 필터링 적용
            if ($rating !== 'all') {
                $givenReviews = $givenReviews->where('rating', $rating);
            }

            // 통계 정보
            $stats = [
                'total_count' => $givenReviews->count(),
                'average_rating' => $givenReviews->avg('rating') ?? 0,
                'rating_distribution' => [
                    5 => $givenReviews->where('rating', 5)->count(),
                    4 => $givenReviews->where('rating', 4)->count(),
                    3 => $givenReviews->where('rating', 3)->count(),
                    2 => $givenReviews->where('rating', 2)->count(),
                    1 => $givenReviews->where('rating', 1)->count()
                ],
                'public_count' => $givenReviews->where('is_public', true)->count(),
                'private_count' => $givenReviews->where('is_public', false)->count(),
                'total_helpful' => $givenReviews->sum('helpful_count')
            ];

            // 내가 리뷰할 수 있는 완료된 프로젝트 (임시 데이터)
            $pendingReviews = collect([
                (object) [
                    'project_id' => 1,
                    'project_title' => '신규 프로젝트A',
                    'partner_name' => '파트너C',
                    'completed_at' => now()->subDays(1),
                    'can_review' => true
                ]
            ]);

            $viewData = [
                'user' => $user,
                'partnerUser' => $partnerUser,
                'givenReviews' => $givenReviews,
                'pendingReviews' => $pendingReviews,
                'stats' => $stats,
                'currentRating' => $rating,
                'currentPeriod' => $period,
                'pageTitle' => '작성한 리뷰'
            ];

            if ($request->wantsJson()) {
                return $this->successResponse($viewData);
            }

            return view('jiny-partner::home.reviews.given', $viewData);

        } catch (\Exception $e) {
            return $this->errorResponse('작성한 리뷰를 불러오는 중 오류가 발생했습니다.', ['error' => $e->getMessage()]);
        }
    }
}