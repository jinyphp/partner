<?php

namespace Jiny\Partner\Http\Controllers\Home\Reviews;

use Jiny\Partner\Http\Controllers\PartnerController;
use Illuminate\Http\Request;

class GivenController extends PartnerController
{
    /**
     * 작성한 리뷰 목록
     */
    public function __invoke(Request $request)
    {
        try {
            // 파트너 인증 및 정보 조회 (공통 로직 사용)
            $authResult = $this->authenticateAndGetPartner($request, 'reviews_given');
            if (!$authResult['success']) {
                return $authResult['redirect'];
            }

            $user = $authResult['user'];
            $partnerUser = $authResult['partner'];

            // 필터링 옵션
            $rating = $request->get('rating', 'all'); // all, 5, 4, 3, 2, 1
            $period = $request->get('period', 'all'); // all, this_month, last_month, this_year
            $visibility = $request->get('visibility', 'all'); // all, public, private
            $status = $request->get('status', 'all'); // all, completed, pending
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
                    'helpful_count' => 3,
                    'status' => 'published'
                ],
                (object) [
                    'id' => 2,
                    'reviewee_name' => '파트너B',
                    'rating' => 4,
                    'comment' => '만족스러운 결과였습니다.',
                    'created_at' => now()->subDays(5),
                    'project_title' => '브랜딩 작업',
                    'is_public' => true,
                    'helpful_count' => 1,
                    'status' => 'draft'
                ]
            ]);

            // 필터링 적용
            if ($rating !== 'all') {
                $givenReviews = $givenReviews->where('rating', $rating);
            }

            if ($status !== 'all') {
                $givenReviews = $givenReviews->where('status', $status);
            }

            if ($visibility !== 'all') {
                $isPublic = $visibility === 'public';
                $givenReviews = $givenReviews->where('is_public', $isPublic);
            }

            // 내가 리뷰할 수 있는 완료된 프로젝트 (임시 데이터)
            $pendingProjects = collect([
                (object) [
                    'id' => 1,
                    'title' => '신규 프로젝트A',
                    'partner_name' => '파트너C',
                    'completed_at' => now()->subDays(1),
                    'can_review' => true
                ]
            ]);

            // 통계 정보 (뷰에서 기대하는 형식으로 맞춤)
            $reviewStats = [
                'total_given' => $givenReviews->count(),
                'average_rating' => $givenReviews->avg('rating') ?? 0,
                'average_given_rating' => $givenReviews->avg('rating') ?? 0,
                'five_star_count' => $givenReviews->where('rating', 5)->count(),
                'this_month_count' => $givenReviews->where('created_at', '>=', now()->startOfMonth())->count(),
                'this_month_given' => $givenReviews->where('created_at', '>=', now()->startOfMonth())->count(),
                'pending_reviews' => $pendingProjects->count(),
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

            // 리뷰 작성 가능한 프로젝트 목록
            $availableProjects = $pendingProjects;

            // 표준 뷰 데이터 구성 (공통 로직 사용)
            $viewData = $this->getStandardViewData($user, $partnerUser, [
                'givenReviews' => $givenReviews,
                'pendingProjects' => $pendingProjects,
                'availableProjects' => $availableProjects,
                'reviewStats' => $reviewStats,
                'currentRating' => $rating,
                'currentPeriod' => $period,
                'currentVisibility' => $visibility,
                'currentStatus' => $status
            ], '작성한 리뷰');

            // JSON 응답 처리 (공통 로직 사용)
            $jsonResponse = $this->handleJsonResponse($request, $viewData);
            if ($jsonResponse) {
                return $jsonResponse;
            }

            return view('jiny-partner::home.reviews.given', $viewData);

        } catch (\Exception $e) {
            // 공통 에러 처리 로직 사용
            return $this->handlePartnerError(
                $e,
                $user ?? null,
                'reviews_given',
                'home.partner.reviews.index',
                '작성한 리뷰를 불러오는 중 오류가 발생했습니다.'
            );
        }
    }
}