<?php

namespace Jiny\Partner\Http\Controllers\Home\Search;

use Jiny\Partner\Http\Controllers\Home\HomeController;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Services\PartnerActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VerifyReferrerController extends HomeController
{
    protected $activityLogService;

    public function __construct(PartnerActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * 추천인 검증 처리
     * 검색된 추천인의 자격을 최종 확인하고 사용 승인
     */
    public function __invoke(Request $request)
    {
        // JWT 인증은 선택사항 (공개 API로도 사용 가능)
        $user = $this->auth($request);

        // 입력 검증
        $request->validate([
            'referrer_email' => 'required|email',
            'applicant_email' => 'required|email',
            'verification_type' => 'required|string|in:eligibility,capacity,compatibility',
            'target_tier' => 'nullable|string|in:Bronze,Silver,Gold,Platinum',
            'target_type' => 'nullable|string|max:50'
        ], [
            'referrer_email.required' => '추천인 이메일을 입력해주세요.',
            'referrer_email.email' => '올바른 추천인 이메일 형식을 입력해주세요.',
            'applicant_email.required' => '신청자 이메일을 입력해주세요.',
            'applicant_email.email' => '올바른 신청자 이메일 형식을 입력해주세요.',
            'verification_type.required' => '검증 유형을 선택해주세요.',
            'verification_type.in' => '올바른 검증 유형을 선택해주세요.'
        ]);

        try {
            $referrerEmail = $request->input('referrer_email');
            $applicantEmail = $request->input('applicant_email');
            $verificationType = $request->input('verification_type');
            $targetTier = $request->input('target_tier', 'Bronze');
            $targetType = $request->input('target_type', '');

            // 추천인 정보 재검색
            $referrerInfo = $this->searchUserByEmail($referrerEmail);
            if (!$referrerInfo) {
                return $this->handleErrorResponse(
                    '추천인을 찾을 수 없습니다.',
                    ['referrer_email' => $referrerEmail],
                    $request
                );
            }

            // 파트너 정보 확인
            $partnerInfo = $this->getPartnerInfo($referrerInfo['user_uuid']);
            if (!$partnerInfo) {
                return $this->handleErrorResponse(
                    '추천인이 파트너로 등록되지 않았습니다.',
                    ['referrer_info' => $referrerInfo],
                    $request
                );
            }

            // 검증 수행
            $verificationResult = $this->performVerification($partnerInfo, $applicantEmail, $verificationType, $targetTier, $targetType);

            // 활동 로그 기록
            if ($user) {
                $this->activityLogService->logActivity(
                    $user->uuid,
                    'referrer_verification',
                    "추천인 검증: {$referrerEmail} -> {$applicantEmail}",
                    [
                        'referrer_email' => $referrerEmail,
                        'applicant_email' => $applicantEmail,
                        'verification_type' => $verificationType,
                        'target_tier' => $targetTier,
                        'verification_result' => $verificationResult['verified']
                    ]
                );
            }

            // 검증 이력 저장
            $this->saveVerificationHistory($referrerInfo, $applicantEmail, $verificationType, $verificationResult);

            return $this->handleSuccessResponse($verificationResult, $request);

        } catch (\Exception $e) {
            Log::error('Referrer verification failed', [
                'referrer_email' => $request->input('referrer_email'),
                'applicant_email' => $request->input('applicant_email'),
                'verification_type' => $request->input('verification_type'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->handleErrorResponse(
                '검증 처리 중 오류가 발생했습니다.',
                ['error_detail' => config('app.debug') ? $e->getMessage() : ''],
                $request
            );
        }
    }

    /**
     * 이메일로 사용자 검색 (ReferrerController와 동일)
     */
    private function searchUserByEmail(string $email): ?array
    {
        // user_profile 테이블에서 먼저 검색
        $userProfile = DB::table('user_profile')
            ->where('email', $email)
            ->first();

        if ($userProfile) {
            return [
                'user_uuid' => $userProfile->user_uuid,
                'shard_id' => $userProfile->shard_id,
                'name' => $userProfile->name,
                'email' => $userProfile->email,
                'source_table' => 'user_profile'
            ];
        }

        // user_auth 테이블에서도 검색 시도
        $userAuth = DB::table('user_auth')
            ->where('email', $email)
            ->first();

        if ($userAuth) {
            $additionalInfo = DB::table('user_profile')
                ->where('user_uuid', $userAuth->user_uuid)
                ->first();

            return [
                'user_uuid' => $userAuth->user_uuid,
                'shard_id' => $additionalInfo->shard_id ?? null,
                'name' => $additionalInfo->name ?? $userAuth->name ?? 'Unknown',
                'email' => $email,
                'source_table' => 'user_auth'
            ];
        }

        // 다른 user_xxx 테이블들에서도 검색
        $userTables = ['user_admin', 'user_locale', 'user_phone', 'user_address', 'user_social'];

        foreach ($userTables as $tableName) {
            try {
                $user = DB::table($tableName)
                    ->where('email', $email)
                    ->first();

                if ($user) {
                    return [
                        'user_uuid' => $user->user_uuid ?? $user->uuid ?? null,
                        'shard_id' => $user->shard_id ?? null,
                        'name' => $user->name ?? 'Unknown',
                        'email' => $email,
                        'source_table' => $tableName
                    ];
                }
            } catch (\Exception $e) {
                Log::debug("Could not search in table {$tableName}", [
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        return null;
    }

    /**
     * 파트너 정보 조회
     */
    private function getPartnerInfo(string $userUuid): ?array
    {
        $partner = PartnerUser::where('user_uuid', $userUuid)->first();

        if (!$partner) {
            return null;
        }

        return [
            'user_uuid' => $partner->user_uuid,
            'tier_name' => $partner->tier_name,
            'type_name' => $partner->type_name,
            'status' => $partner->status,
            'commission_rate' => $partner->commission_rate,
            'joined_at' => $partner->joined_at,
            'referrer_uuid' => $partner->referrer_uuid,
            'managed_partners_count' => $this->getManagedPartnersCount($partner->user_uuid),
            'total_referrals' => $this->getTotalReferralsCount($partner->user_uuid),
            'approval_permissions' => $this->getApprovalPermissions($partner)
        ];
    }

    /**
     * 관리 중인 파트너 수 조회
     */
    private function getManagedPartnersCount(string $userUuid): int
    {
        return PartnerUser::where('referrer_uuid', $userUuid)
            ->where('status', 'active')
            ->count();
    }

    /**
     * 총 추천 수 조회
     */
    private function getTotalReferralsCount(string $userUuid): int
    {
        return PartnerUser::where('referrer_uuid', $userUuid)->count();
    }

    /**
     * 검증 수행
     */
    private function performVerification(array $partnerInfo, string $applicantEmail, string $verificationType, string $targetTier, string $targetType): array
    {
        $verificationResult = [
            'verified' => false,
            'verification_type' => $verificationType,
            'referrer_info' => $partnerInfo,
            'eligibility' => [],
            'capacity' => [],
            'compatibility' => [],
            'warnings' => [],
            'recommendations' => [],
            'verification_details' => []
        ];

        switch ($verificationType) {
            case 'eligibility':
                $verificationResult = array_merge($verificationResult, $this->verifyEligibility($partnerInfo, $applicantEmail, $targetTier));
                break;

            case 'capacity':
                $verificationResult = array_merge($verificationResult, $this->verifyCapacity($partnerInfo, $targetTier));
                break;

            case 'compatibility':
                $verificationResult = array_merge($verificationResult, $this->verifyCompatibility($partnerInfo, $targetTier, $targetType));
                break;

            default:
                // 종합 검증 (모든 유형 검사)
                $eligibility = $this->verifyEligibility($partnerInfo, $applicantEmail, $targetTier);
                $capacity = $this->verifyCapacity($partnerInfo, $targetTier);
                $compatibility = $this->verifyCompatibility($partnerInfo, $targetTier, $targetType);

                $verificationResult['verified'] = $eligibility['verified'] && $capacity['verified'] && $compatibility['verified'];
                $verificationResult['eligibility'] = $eligibility['eligibility'];
                $verificationResult['capacity'] = $capacity['capacity'];
                $verificationResult['compatibility'] = $compatibility['compatibility'];
                $verificationResult['warnings'] = array_merge(
                    $eligibility['warnings'] ?? [],
                    $capacity['warnings'] ?? [],
                    $compatibility['warnings'] ?? []
                );
                break;
        }

        // 최종 검증 점수 계산
        $verificationResult['verification_score'] = $this->calculateVerificationScore($verificationResult);

        return $verificationResult;
    }

    /**
     * 자격 검증
     */
    private function verifyEligibility(array $partnerInfo, string $applicantEmail, string $targetTier): array
    {
        $eligibility = [
            'partner_status' => false,
            'tier_eligibility' => false,
            'no_conflicts' => false,
            'no_duplicates' => false
        ];

        $warnings = [];

        // 파트너 상태 확인
        if ($partnerInfo['status'] === 'active') {
            $eligibility['partner_status'] = true;
        } else {
            $warnings[] = '추천인 파트너가 비활성 상태입니다.';
        }

        // 등급 자격 확인
        $approvalPermissions = $partnerInfo['approval_permissions'];
        if (in_array($targetTier, $approvalPermissions['approvable_tiers'])) {
            $eligibility['tier_eligibility'] = true;
        } else {
            $warnings[] = "추천인은 {$targetTier} 등급을 승인할 권한이 없습니다.";
        }

        // 중복 확인
        $duplicateCheck = $this->checkDuplicateApplicant($applicantEmail);
        if (!$duplicateCheck['has_duplicate']) {
            $eligibility['no_duplicates'] = true;
        } else {
            $warnings[] = $duplicateCheck['reason'];
        }

        // 이해관계 충돌 확인
        $conflictCheck = $this->checkConflictOfInterest($partnerInfo['user_uuid'], $applicantEmail);
        if (!$conflictCheck['has_conflict']) {
            $eligibility['no_conflicts'] = true;
        } else {
            $warnings[] = $conflictCheck['reason'];
        }

        $verified = array_reduce($eligibility, function($carry, $item) {
            return $carry && $item;
        }, true);

        return [
            'verified' => $verified,
            'eligibility' => $eligibility,
            'warnings' => $warnings,
            'verification_details' => [
                'checks_performed' => count($eligibility),
                'checks_passed' => count(array_filter($eligibility)),
                'check_details' => $eligibility
            ]
        ];
    }

    /**
     * 용량 검증
     */
    private function verifyCapacity(array $partnerInfo, string $targetTier): array
    {
        $approvalPermissions = $partnerInfo['approval_permissions'];

        $capacity = [
            'monthly_capacity' => false,
            'management_capacity' => false,
            'tier_specific_capacity' => false
        ];

        $warnings = [];

        // 월간 승인 용량 확인
        if ($approvalPermissions['remaining_monthly'] > 0) {
            $capacity['monthly_capacity'] = true;
        } else {
            $warnings[] = '월간 승인 한도를 초과했습니다.';
        }

        // 관리 용량 확인
        if ($approvalPermissions['remaining_capacity'] > 0) {
            $capacity['management_capacity'] = true;
        } else {
            $warnings[] = '최대 관리 가능 파트너 수에 도달했습니다.';
        }

        // 등급별 특정 용량 확인 (예: Gold는 Silver 승인 시 추가 제한)
        $tierSpecificCheck = $this->checkTierSpecificCapacity($partnerInfo, $targetTier);
        if ($tierSpecificCheck['passed']) {
            $capacity['tier_specific_capacity'] = true;
        } else {
            $warnings[] = $tierSpecificCheck['reason'];
        }

        $verified = array_reduce($capacity, function($carry, $item) {
            return $carry && $item;
        }, true);

        return [
            'verified' => $verified,
            'capacity' => $capacity,
            'warnings' => $warnings,
            'verification_details' => [
                'remaining_monthly' => $approvalPermissions['remaining_monthly'],
                'remaining_capacity' => $approvalPermissions['remaining_capacity'],
                'tier_specific_limit' => $tierSpecificCheck['limit'] ?? null
            ]
        ];
    }

    /**
     * 호환성 검증
     */
    private function verifyCompatibility(array $partnerInfo, string $targetTier, string $targetType): array
    {
        $compatibility = [
            'tier_compatibility' => false,
            'type_compatibility' => false,
            'experience_compatibility' => false
        ];

        $warnings = [];

        // 등급 호환성 확인
        $tierCompatibility = $this->checkTierCompatibility($partnerInfo['tier_name'], $targetTier);
        if ($tierCompatibility['compatible']) {
            $compatibility['tier_compatibility'] = true;
        } else {
            $warnings[] = $tierCompatibility['reason'];
        }

        // 타입 호환성 확인
        if (empty($targetType) || $partnerInfo['type_name'] === $targetType || $partnerInfo['tier_name'] === 'Platinum') {
            $compatibility['type_compatibility'] = true;
        } else {
            $warnings[] = '추천인과 신청자의 파트너 타입이 호환되지 않습니다.';
        }

        // 경험 호환성 확인 (추천인이 신청 등급보다 높은지)
        $experienceCompatibility = $this->checkExperienceCompatibility($partnerInfo['tier_name'], $targetTier);
        if ($experienceCompatibility['compatible']) {
            $compatibility['experience_compatibility'] = true;
        } else {
            $warnings[] = $experienceCompatibility['reason'];
        }

        $verified = array_reduce($compatibility, function($carry, $item) {
            return $carry && $item;
        }, true);

        return [
            'verified' => $verified,
            'compatibility' => $compatibility,
            'warnings' => $warnings,
            'verification_details' => [
                'partner_tier' => $partnerInfo['tier_name'],
                'target_tier' => $targetTier,
                'partner_type' => $partnerInfo['type_name'],
                'target_type' => $targetType
            ]
        ];
    }

    /**
     * 중복 신청자 확인
     */
    private function checkDuplicateApplicant(string $applicantEmail): array
    {
        // 기존 파트너 확인
        $existingUser = DB::table('user_profile')
            ->where('email', $applicantEmail)
            ->first();

        if ($existingUser) {
            $existingPartner = PartnerUser::where('user_uuid', $existingUser->user_uuid)->first();
            if ($existingPartner) {
                return [
                    'has_duplicate' => true,
                    'reason' => '이미 파트너로 등록된 사용자입니다.',
                    'existing_status' => $existingPartner->status
                ];
            }
        }

        // 진행 중인 신청 확인
        $recentApplication = DB::table('partner_applications')
            ->whereJsonContains('personal_info->email', $applicantEmail)
            ->where('application_status', 'in', ['submitted', 'reviewing', 'interview'])
            ->where('created_at', '>=', now()->subDays(30))
            ->first();

        if ($recentApplication) {
            return [
                'has_duplicate' => true,
                'reason' => '진행 중인 신청이 있습니다.',
                'application_status' => $recentApplication->application_status
            ];
        }

        return ['has_duplicate' => false];
    }

    /**
     * 이해관계 충돌 확인
     */
    private function checkConflictOfInterest(string $referrerUuid, string $applicantEmail): array
    {
        // 가족 관계 확인 (실제 구현에서는 별도 테이블 사용)
        // 현재는 간단한 체크만 수행

        // 동일한 회사/조직 확인 (실제 구현에서는 사용자 프로필 정보 사용)

        // 직접적인 비즈니스 관계 확인

        return ['has_conflict' => false]; // 임시로 충돌 없음으로 반환
    }

    /**
     * 등급별 특정 용량 확인
     */
    private function checkTierSpecificCapacity(array $partnerInfo, string $targetTier): array
    {
        $partnerTier = $partnerInfo['tier_name'];

        // Silver 파트너가 Bronze를 너무 많이 승인하는지 확인
        if ($partnerTier === 'Silver' && $targetTier === 'Bronze') {
            $bronzeCount = PartnerUser::where('referrer_uuid', $partnerInfo['user_uuid'])
                ->where('tier_name', 'Bronze')
                ->count();

            if ($bronzeCount >= 3) { // Silver는 Bronze 3명까지만
                return [
                    'passed' => false,
                    'reason' => 'Silver 파트너는 Bronze 파트너를 최대 3명까지만 관리할 수 있습니다.',
                    'limit' => 3,
                    'current' => $bronzeCount
                ];
            }
        }

        return ['passed' => true];
    }

    /**
     * 등급 호환성 확인
     */
    private function checkTierCompatibility(string $partnerTier, string $targetTier): array
    {
        $tierHierarchy = ['Bronze', 'Silver', 'Gold', 'Platinum'];
        $partnerIndex = array_search($partnerTier, $tierHierarchy);
        $targetIndex = array_search($targetTier, $tierHierarchy);

        if ($partnerIndex === false || $targetIndex === false) {
            return [
                'compatible' => false,
                'reason' => '알 수 없는 등급입니다.'
            ];
        }

        if ($partnerIndex <= $targetIndex) {
            return [
                'compatible' => false,
                'reason' => '추천인은 자신보다 높거나 같은 등급을 승인할 수 없습니다.'
            ];
        }

        return ['compatible' => true];
    }

    /**
     * 경험 호환성 확인
     */
    private function checkExperienceCompatibility(string $partnerTier, string $targetTier): array
    {
        // 추천인이 충분한 경험을 가지고 있는지 확인
        $minimumExperience = [
            'Bronze' => 0,
            'Silver' => 3,
            'Gold' => 12,
            'Platinum' => 24
        ];

        $partnerExp = $minimumExperience[$partnerTier] ?? 0;
        $requiredExp = ($minimumExperience[$targetTier] ?? 0) * 2; // 추천하려면 2배 경험 필요

        if ($partnerExp >= $requiredExp) {
            return ['compatible' => true];
        }

        return [
            'compatible' => false,
            'reason' => "추천인의 경험이 {$targetTier} 등급 추천에 부족합니다."
        ];
    }

    /**
     * 승인 권한 정보 조회
     */
    private function getApprovalPermissions(PartnerUser $partner): array
    {
        $tierName = $partner->tier_name ?? 'Bronze';
        $currentApprovals = DB::table('partner_applications')
            ->where('approved_by_uuid', $partner->user_uuid)
            ->where('application_status', 'approved')
            ->whereMonth('approval_date', now()->month)
            ->whereYear('approval_date', now()->year)
            ->count();

        $totalManaging = PartnerUser::where('referrer_uuid', $partner->user_uuid)
            ->where('status', 'active')
            ->count();

        $permissions = [
            'Bronze' => ['monthly_limit' => 0, 'max_managing' => 0, 'approvable_tiers' => []],
            'Silver' => ['monthly_limit' => 2, 'max_managing' => 5, 'approvable_tiers' => ['Bronze']],
            'Gold' => ['monthly_limit' => 5, 'max_managing' => 15, 'approvable_tiers' => ['Bronze', 'Silver']],
            'Platinum' => ['monthly_limit' => 15, 'max_managing' => 50, 'approvable_tiers' => ['Bronze', 'Silver', 'Gold']]
        ];

        $basePermission = $permissions[$tierName] ?? $permissions['Bronze'];

        return array_merge($basePermission, [
            'current_month_approvals' => $currentApprovals,
            'remaining_monthly' => max(0, $basePermission['monthly_limit'] - $currentApprovals),
            'total_managing' => $totalManaging,
            'remaining_capacity' => max(0, $basePermission['max_managing'] - $totalManaging)
        ]);
    }

    /**
     * 검증 점수 계산
     */
    private function calculateVerificationScore(array $verificationResult): int
    {
        $score = 0;

        // 기본 검증 통과 점수
        if ($verificationResult['verified']) {
            $score += 70;
        }

        // 개별 항목별 점수
        if (!empty($verificationResult['eligibility'])) {
            $eligibilityPassed = count(array_filter($verificationResult['eligibility']));
            $eligibilityTotal = count($verificationResult['eligibility']);
            $score += ($eligibilityPassed / $eligibilityTotal) * 15;
        }

        if (!empty($verificationResult['capacity'])) {
            $capacityPassed = count(array_filter($verificationResult['capacity']));
            $capacityTotal = count($verificationResult['capacity']);
            $score += ($capacityPassed / $capacityTotal) * 10;
        }

        if (!empty($verificationResult['compatibility'])) {
            $compatibilityPassed = count(array_filter($verificationResult['compatibility']));
            $compatibilityTotal = count($verificationResult['compatibility']);
            $score += ($compatibilityPassed / $compatibilityTotal) * 5;
        }

        return min(100, (int) $score);
    }

    /**
     * 검증 이력 저장
     */
    private function saveVerificationHistory(array $referrerInfo, string $applicantEmail, string $verificationType, array $verificationResult): void
    {
        try {
            DB::table('partner_verification_history')->insert([
                'referrer_uuid' => $referrerInfo['user_uuid'],
                'referrer_email' => $referrerInfo['email'],
                'applicant_email' => $applicantEmail,
                'verification_type' => $verificationType,
                'verification_result' => json_encode($verificationResult),
                'verified' => $verificationResult['verified'],
                'verification_score' => $verificationResult['verification_score'] ?? 0,
                'verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to save verification history', [
                'error' => $e->getMessage(),
                'referrer_uuid' => $referrerInfo['user_uuid'],
                'applicant_email' => $applicantEmail
            ]);
        }
    }

    /**
     * 성공 응답 (HomeController 메서드 사용)
     */
    private function handleSuccessResponse(array $verificationResult, Request $request)
    {
        if ($request->wantsJson()) {
            return $this->successResponse($verificationResult, '추천인 검증이 완료되었습니다.');
        }

        return redirect()->back()
            ->with('success', '추천인 검증이 완료되었습니다.')
            ->with('verification_result', $verificationResult);
    }

    /**
     * 오류 응답 (HomeController 메서드 사용)
     */
    private function handleErrorResponse(string $message, array $details, Request $request)
    {
        if ($request->wantsJson()) {
            return $this->errorResponse($message, $details, 400);
        }

        return redirect()->back()
            ->with('error', $message)
            ->with('error_details', $details)
            ->withInput();
    }
}