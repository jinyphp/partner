<?php

namespace Jiny\Partner\Http\Controllers;

use Jiny\Auth\Http\Controllers\HomeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Models\PartnerApplication;

/**
 * 파트너 컨트롤러 기본 클래스
 *
 * 상속 구조:
 * - HomeController: 사용자 세션 인증 및 샤딩된 회원 정보 관리
 * - PartnerController: 파트너 등록 여부 확인 및 파트너 전용 기능 제공
 *
 * 주요 메소드:
 * 1. authenticateAndGetPartner() - 사용자 인증 + 파트너 검증 (가장 많이 사용)
 * 2. authenticateUser() - 사용자 인증만 (파트너 소개 페이지 등)
 * 3. isPartner() - 간단한 파트너 여부 확인 (Dashboard에서 사용)
 * 4. getStandardViewData() - 공통 뷰 데이터 구성
 * 5. handlePartnerError() - 파트너 관련 에러 처리
 * 6. handleJsonResponse() - JSON 응답 처리
 */
class PartnerController extends HomeController
{
    protected $partner;





    /**
     * 간단한 파트너 여부 확인
     *
     * 사용처: Dashboard/IndexController
     * 기능: 파트너 정보 조회 (관계 포함)
     */
    protected function isPartner($user)
    {
        $partner = PartnerUser::with(['partnerType', 'partnerTier'])
            ->where('user_uuid', $user->uuid)
            ->first();
        return $partner;
    }


    /**
     * 완전한 파트너 인증 (가장 많이 사용되는 메소드)
     *
     * 사용처: Commission/IndexController, Sales/IndexController, Reviews/GivenController
     *
     * Step 1: 사용자 인증 (HomeController 위임)
     * Step 2: 파트너 등록 여부 확인
     * Step 3: 파트너 상태별 분기 처리
     *
     * @return array ['success' => bool, 'redirect' => ?, 'user' => ?, 'partner' => ?]
     */
    protected function authenticateAndGetPartner(Request $request, string $context = 'partner')
    {
        // ========================================
        // Step 1: 사용자 인증 (HomeController 위임)
        // ========================================
        $user = $this->auth($request); // HomeController의 JWT/세션 인증
        if (!$user) {
            Log::info("Partner {$context} access: User authentication failed");
            return [
                'success' => false,
                'redirect' => redirect()->route('login')->with('error', '로그인이 필요합니다.'),
                'user' => null,
                'partner' => null
            ];
        }

        // 파트너 접근 로그 기록
        $this->logPartnerAccess($context, $user);

        // ========================================
        // Step 2: 파트너 등록 여부 확인 (PartnerController 책임)
        // ========================================
        $partnerUser = $this->findPartnerByUser($user);

        Log::info("Partner user query result for {$context}", [
            'user_uuid' => $user->uuid,
            'partner_found' => $partnerUser ? true : false,
            'partner_id' => $partnerUser->id ?? 'N/A',
            'partner_name' => $partnerUser->name ?? 'N/A'
        ]);

        // ========================================
        // Step 3: 파트너 상태별 분기 처리
        // ========================================
        if (!$partnerUser) {
            return $this->handleNonPartnerUser($user, $context);
        }

        // 파트너 등록 완료된 사용자
        return [
            'success' => true,
            'redirect' => null,
            'user' => $user,
            'partner' => $partnerUser
        ];
    }

    /**
     * 사용자로부터 파트너 정보 조회 (UUID 기반, 샤딩 지원)
     */
    protected function findPartnerByUser($user)
    {
        return PartnerUser::where('user_uuid', $user->uuid)->first();
    }

    /**
     * 파트너 미등록 사용자 처리
     * 파트너 신청 상태에 따른 적절한 안내
     */
    protected function handleNonPartnerUser($user, string $context)
    {
        // 파트너 신청 정보 확인
        $partnerApplication = PartnerApplication::where('user_uuid', $user->uuid)
            ->latest()
            ->first();

        Log::info("Partner application check for {$context}", [
            'application_found' => $partnerApplication ? true : false,
            'application_id' => $partnerApplication->id ?? 'N/A',
            'application_status' => $partnerApplication->application_status ?? 'N/A'
        ]);

        if ($partnerApplication) {
            // 신청했지만 아직 승인되지 않은 경우 → 상태 페이지로 안내
            return [
                'success' => false,
                'redirect' => redirect()->route('home.partner.regist.status', $partnerApplication->id)
                    ->with('info', '파트너 신청이 아직 처리 중입니다.'),
                'user' => $user,
                'partner' => null
            ];
        } else {
            // 파트너 신청을 하지 않은 경우 → 소개 페이지로 안내
            return [
                'success' => false,
                'redirect' => redirect()->route('home.partner.intro')
                    ->with('info', '파트너 프로그램에 먼저 가입해 주세요.'),
                'user' => $user,
                'partner' => null
            ];
        }
    }

    /**
     * 파트너 접근 로깅
     */
    protected function logPartnerAccess(string $context, $user)
    {
        Log::info("Partner {$context} access attempt", [
            'user_id' => $user->id ?? 'N/A',
            'user_uuid' => $user->uuid ?? 'N/A',
            'user_email' => $user->email ?? 'N/A',
            'context' => $context
        ]);
    }

    /**
     * 파트너 관련 에러 처리
     */
    protected function handlePartnerError(\Exception $e, $user, string $context, string $redirectRoute = 'home.partner.index', string $errorMessage = null)
    {
        Log::error("Partner {$context} error: " . $e->getMessage(), [
            'user_id' => $user->id ?? 'unknown',
            'user_uuid' => $user->uuid ?? 'unknown',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'context' => $context
        ]);

        $message = $errorMessage ?? "{$context} 정보를 불러오는 중 오류가 발생했습니다.";

        return redirect()->route($redirectRoute)->with('error', $message);
    }

    /**
     * JSON 응답 처리
     */
    protected function handleJsonResponse(Request $request, array $viewData)
    {
        if ($request->wantsJson()) {
            return $this->successResponse($viewData);
        }

        return null;
    }

    /**
     * 표준 뷰 데이터 구성
     */
    protected function getStandardViewData($user, $partnerUser, array $additionalData = [], string $pageTitle = '파트너')
    {
        return array_merge([
            'user' => $user,
            'partnerUser' => $partnerUser,
            'pageTitle' => $pageTitle,
            'userInfo' => [
                'name' => $user->name ?? '',
                'email' => $user->email ?? '',
                'phone' => $user->profile->phone ?? '',
                'uuid' => $user->uuid
            ]
        ], $additionalData);
    }

    /**
     * 사용자 인증만 (파트너 등록 여부 확인 없음)
     *
     * 사용처: Search/ReferrerController
     *
     * 용도:
     * - 파트너 소개 페이지 (미등록 사용자도 접근 가능)
     * - 추천인 검색 (등록 여부와 무관한 기능)
     *
     * @return array ['success' => bool, 'redirect' => ?, 'user' => ?]
     */
    protected function authenticateUser(Request $request, string $context = 'partner')
    {
        // HomeController의 사용자 인증만 수행
        $user = $this->auth($request); // HomeController 위임
        if (!$user) {
            Log::info("User authentication failed for {$context}");
            return [
                'success' => false,
                'redirect' => redirect()->route('login')->with('error', '로그인이 필요합니다.'),
                'user' => null
            ];
        }

        // 접근 로그 기록
        $this->logPartnerAccess($context, $user);

        return [
            'success' => true,
            'redirect' => null,
            'user' => $user
        ];
    }

    /**
     * 파트너로부터 실제 회원 정보 조회
     *
     * 파트너 객체의 UUID를 사용하여 샤딩된 회원 테이블에서 실제 사용자 정보를 조회
     *
     * @param PartnerUser $partner 파트너 객체
     * @return object|null 실제 회원 정보 (users_xxx 테이블)
     */
    public function getUserByPartner(PartnerUser $partner)
    {
        try {
            if (!$partner || !$partner->user_uuid) {
                return null;
            }

            // HomeController의 getUserFromUuid 메서드를 활용하여 회원 정보 조회
            $user = $this->getUserFromUuid($partner->user_uuid);

            return $user;

        } catch (\Exception $e) {
            Log::error('Failed to get user by partner: ' . $e->getMessage(), [
                'partner_id' => $partner->id ?? null,
                'partner_uuid' => $partner->user_uuid ?? null,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

}
