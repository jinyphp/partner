<?php

namespace Jiny\Partner\Http\Controllers\Home\PartnerApproval;

use Jiny\Partner\Http\Controllers\PartnerController;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Services\PartnerActivityLogService;
use Jiny\Partner\Services\PartnerNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RecommendController extends PartnerController
{
    protected $activityLogService;
    protected $notificationService;

    public function __construct(
        PartnerActivityLogService $activityLogService,
        PartnerNotificationService $notificationService
    ) {
        $this->activityLogService = $activityLogService;
        $this->notificationService = $notificationService;
    }

    /**
     * 파트너 추천 처리
     * 상위 파트너가 후보자를 추천하여 신청서 생성
     */
    public function __invoke(Request $request)
    {
        // 세션 인증 확인
        $user = $this->auth($request);
        if (!$user) {
            return redirect()->route('login')->with('error', '로그인이 필요합니다.');
        }

        // 파트너 정보 확인 (tier 관계 포함 로드)
        $partner = PartnerUser::with('tier')->where('user_uuid', $user->uuid)->first();
        if (!$partner) {
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

        // 입력 검증
        $request->validate([
            'candidate_email' => 'required|email|max:255',
            'candidate_name' => 'required|string|max:100',
            'candidate_phone' => 'nullable|string|max:20',
            'recommended_tier' => 'required|string|in:Bronze,Silver,Gold,Platinum',
            'recommended_type' => 'required|string|max:50',
            'recommendation_reason' => 'required|string|max:1000|min:20',
            'expected_skills' => 'nullable|array',
            'expected_skills.*' => 'string|max:50',
            'experience_years' => 'nullable|integer|min:0|max:50',
            'priority_level' => 'nullable|string|in:low,medium,high,urgent',
            'send_invitation' => 'nullable|boolean'
        ], [
            'candidate_email.required' => '후보자 이메일을 입력해주세요.',
            'candidate_email.email' => '올바른 이메일 형식을 입력해주세요.',
            'candidate_name.required' => '후보자 이름을 입력해주세요.',
            'recommended_tier.required' => '추천 등급을 선택해주세요.',
            'recommended_tier.in' => '올바른 등급을 선택해주세요.',
            'recommended_type.required' => '추천 타입을 입력해주세요.',
            'recommendation_reason.required' => '추천 사유를 입력해주세요.',
            'recommendation_reason.min' => '추천 사유는 최소 20자 이상 입력해주세요.'
        ]);

        try {
            DB::beginTransaction();

            // 추천 권한 확인
            $validationResult = $this->validateRecommendationRequest($partner, $request);
            if (!$validationResult['can_recommend']) {
                DB::rollBack();

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $validationResult['reason']
                    ], 403);
                }

                return redirect()->back()
                    ->with('error', $validationResult['reason'])
                    ->with('info', $validationResult['details'] ?? '')
                    ->withInput();
            }

            // 중복 추천 확인
            $duplicateCheck = $this->checkDuplicateRecommendation($request->input('candidate_email'), $partner);
            if (!$duplicateCheck['allowed']) {
                DB::rollBack();

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $duplicateCheck['reason']
                    ], 409);
                }

                return redirect()->back()
                    ->with('error', $duplicateCheck['reason'])
                    ->with('info', $duplicateCheck['details'] ?? '')
                    ->withInput();
            }

            // 추천 처리
            $recommendationData = $this->processRecommendation($partner, $request);

            // 임시 신청서 생성 (후보자가 실제 신청하기 전까지)
            $draftApplication = $this->createDraftApplication($recommendationData);

            // 활동 로그 기록
            $this->activityLogService->logActivity(
                $partner->user_uuid,
                'candidate_recommended',
                "후보자 추천: {$recommendationData['candidate_email']}",
                [
                    'candidate_email' => $recommendationData['candidate_email'],
                    'candidate_name' => $recommendationData['candidate_name'],
                    'recommended_tier' => $recommendationData['recommended_tier'],
                    'recommended_type' => $recommendationData['recommended_type'],
                    'priority_level' => $recommendationData['priority_level'],
                    'draft_application_id' => $draftApplication->id
                ]
            );

            // 초대 알림 발송 (요청된 경우)
            if ($request->input('send_invitation', true)) {
                $this->sendRecommendationInvitation($recommendationData, $partner);
            }

            DB::commit();

            Log::info('Partner recommendation created', [
                'recommender_uuid' => $partner->user_uuid,
                'recommender_tier' => $partner->tier_name,
                'candidate_email' => $recommendationData['candidate_email'],
                'recommended_tier' => $recommendationData['recommended_tier'],
                'draft_application_id' => $draftApplication->id
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => '후보자 추천이 완료되었습니다.',
                    'data' => [
                        'recommendation_id' => $draftApplication->id,
                        'candidate_email' => $recommendationData['candidate_email'],
                        'recommended_tier' => $recommendationData['recommended_tier'],
                        'invitation_sent' => $request->input('send_invitation', true)
                    ]
                ]);
            }

            return redirect()->route('home.partner.approval.referrals')
                ->with('success', '후보자 추천이 완료되었습니다.')
                ->with('info', $request->input('send_invitation', true)
                    ? '초대 이메일이 발송되었습니다.'
                    : '초대 이메일 발송을 선택하지 않았습니다.');

        } catch (ValidationException $e) {
            DB::rollBack();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '입력 데이터에 오류가 있습니다.',
                    'errors' => $e->errors()
                ], 422);
            }

            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Partner recommendation failed', [
                'recommender_uuid' => $partner->user_uuid,
                'candidate_email' => $request->input('candidate_email'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '추천 처리 중 오류가 발생했습니다.'
                ], 500);
            }

            return redirect()->back()
                ->with('error', '추천 처리 중 오류가 발생했습니다.')
                ->with('debug', config('app.debug') ? $e->getMessage() : '')
                ->withInput();
        }
    }

    /**
     * 추천 요청 유효성 검증
     */
    private function validateRecommendationRequest(PartnerUser $partner, Request $request): array
    {
        // 기본 승인 권한 확인 (추천 권한은 승인 권한과 연계)
        $approvalPermissions = $this->getApprovalPermissions($partner);
        if (!$approvalPermissions['can_approve']) {
            return [
                'can_recommend' => false,
                'reason' => '추천 권한이 없습니다.',
                'details' => $approvalPermissions['reason']
            ];
        }

        // 등급 추천 권한 확인
        $recommendedTier = $request->input('recommended_tier');
        if (!in_array($recommendedTier, $approvalPermissions['approvable_tiers'])) {
            return [
                'can_recommend' => false,
                'reason' => "{$recommendedTier} 등급을 추천할 권한이 없습니다.",
                'details' => '추천 가능 등급: ' . implode(', ', $approvalPermissions['approvable_tiers'])
            ];
        }

        // 관리 용량 확인
        if ($approvalPermissions['remaining_capacity'] <= 0) {
            return [
                'can_recommend' => false,
                'reason' => '최대 관리 가능 파트너 수에 도달했습니다.',
                'details' => "현재 {$approvalPermissions['total_managing']}/{$approvalPermissions['max_managing']}명 관리 중"
            ];
        }

        // 월별 추천 한도 확인 (추천도 승인 한도에 포함)
        $monthlyRecommendations = $this->getMonthlyRecommendationCount($partner);
        if ($monthlyRecommendations >= $approvalPermissions['monthly_limit']) {
            return [
                'can_recommend' => false,
                'reason' => '이번 달 추천 한도에 도달했습니다.',
                'details' => "현재 {$monthlyRecommendations}/{$approvalPermissions['monthly_limit']}명 추천"
            ];
        }

        return ['can_recommend' => true];
    }

    /**
     * 월별 추천 수 조회
     */
    private function getMonthlyRecommendationCount(PartnerUser $partner): int
    {
        return PartnerApplication::whereJsonContains('referral_details->referrer_uuid', $partner->user_uuid)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }

    /**
     * 중복 추천 확인
     */
    private function checkDuplicateRecommendation(string $candidateEmail, PartnerUser $partner): array
    {
        // 이미 파트너인지 확인
        $existingPartner = DB::table('user_profile')
            ->where('email', $candidateEmail)
            ->first();

        if ($existingPartner) {
            $isPartner = PartnerUser::where('user_uuid', $existingPartner->user_uuid)->exists();
            if ($isPartner) {
                return [
                    'allowed' => false,
                    'reason' => '이미 파트너로 등록된 사용자입니다.',
                    'details' => '기존 파트너를 추천할 수 없습니다.'
                ];
            }
        }

        // 최근 추천/신청 기록 확인
        $recentApplication = PartnerApplication::whereJsonContains('personal_info->email', $candidateEmail)
            ->where('created_at', '>=', now()->subDays(30))
            ->first();

        if ($recentApplication) {
            $status = $recentApplication->application_status;
            if (in_array($status, ['submitted', 'reviewing', 'interview'])) {
                return [
                    'allowed' => false,
                    'reason' => '이미 진행 중인 신청이 있습니다.',
                    'details' => "현재 상태: {$status}"
                ];
            } elseif ($status === 'approved') {
                return [
                    'allowed' => false,
                    'reason' => '이미 승인된 신청자입니다.',
                    'details' => '승인된 신청자는 재추천할 수 없습니다.'
                ];
            }
        }

        // 같은 파트너의 최근 중복 추천 확인
        $recentRecommendation = PartnerApplication::whereJsonContains('referral_details->referrer_uuid', $partner->user_uuid)
            ->whereJsonContains('personal_info->email', $candidateEmail)
            ->where('created_at', '>=', now()->subDays(7))
            ->first();

        if ($recentRecommendation) {
            return [
                'allowed' => false,
                'reason' => '최근에 이미 추천한 후보자입니다.',
                'details' => '동일 후보자는 7일 후 재추천 가능합니다.'
            ];
        }

        return ['allowed' => true];
    }

    /**
     * 추천 처리 데이터 구성
     */
    private function processRecommendation(PartnerUser $partner, Request $request): array
    {
        $candidateEmail = $request->input('candidate_email');
        $candidateName = $request->input('candidate_name');
        $candidatePhone = $request->input('candidate_phone');
        $recommendedTier = $request->input('recommended_tier');
        $recommendedType = $request->input('recommended_type');
        $recommendationReason = $request->input('recommendation_reason');
        $expectedSkills = $request->input('expected_skills', []);
        $experienceYears = $request->input('experience_years', 0);
        $priorityLevel = $request->input('priority_level', 'medium');

        return [
            'candidate_email' => $candidateEmail,
            'candidate_name' => $candidateName,
            'candidate_phone' => $candidatePhone,
            'recommended_tier' => $recommendedTier,
            'recommended_type' => $recommendedType,
            'recommendation_reason' => $recommendationReason,
            'expected_skills' => $expectedSkills,
            'experience_years' => $experienceYears,
            'priority_level' => $priorityLevel,
            'recommender_uuid' => $partner->user_uuid,
            'recommender_tier' => $partner->tier_name,
            'recommended_at' => now()
        ];
    }

    /**
     * 임시 신청서 생성
     */
    private function createDraftApplication(array $recommendationData): PartnerApplication
    {
        // 임시 UUID 생성 (실제 사용자 등록 전까지)
        $tempUuid = 'temp_' . uniqid() . '_' . time();

        return PartnerApplication::create([
            'user_uuid' => $tempUuid,
            'application_status' => 'draft',
            'personal_info' => [
                'name' => $recommendationData['candidate_name'],
                'email' => $recommendationData['candidate_email'],
                'phone' => $recommendationData['candidate_phone']
            ],
            'application_preferences' => [
                'target_tier' => $recommendationData['recommended_tier'],
                'target_type' => $recommendationData['recommended_type']
            ],
            'experience_info' => [
                'total_years' => $recommendationData['experience_years']
            ],
            'skills_info' => [
                'primary_skills' => $recommendationData['expected_skills']
            ],
            'referral_details' => [
                'referrer_uuid' => $recommendationData['recommender_uuid'],
                'referrer_tier' => $recommendationData['recommender_tier'],
                'recommendation_reason' => $recommendationData['recommendation_reason'],
                'priority_level' => $recommendationData['priority_level'],
                'recommended_at' => $recommendationData['recommended_at']
            ],
            'documents' => [],
            'interview_details' => [],
            'additional_notes' => "파트너 추천으로 생성된 임시 신청서"
        ]);
    }

    /**
     * 추천 초대 알림 발송
     */
    private function sendRecommendationInvitation(array $recommendationData, PartnerUser $partner): void
    {
        try {
            // 실제 구현에서는 이메일 발송 서비스 사용
            $this->notificationService->sendRecommendationInvitation(
                $recommendationData['candidate_email'],
                $recommendationData['candidate_name'],
                $partner->user_uuid,
                [
                    'recommended_tier' => $recommendationData['recommended_tier'],
                    'recommended_type' => $recommendationData['recommended_type'],
                    'recommendation_reason' => $recommendationData['recommendation_reason'],
                    'recommender_name' => $partner->name ?? 'Unknown',
                    'recommender_tier' => $partner->tier_name
                ]
            );

            Log::info('Recommendation invitation sent', [
                'candidate_email' => $recommendationData['candidate_email'],
                'recommender_uuid' => $partner->user_uuid,
                'recommended_tier' => $recommendationData['recommended_tier']
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send recommendation invitation', [
                'candidate_email' => $recommendationData['candidate_email'],
                'recommender_uuid' => $partner->user_uuid,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 승인 권한 정보 조회
     */
    private function getApprovalPermissions(PartnerUser $partner): array
    {
        $tierName = $partner->tier_name ?? 'Bronze';
        $currentApprovals = PartnerApplication::where('approved_by_uuid', $partner->user_uuid)
            ->where('application_status', 'approved')
            ->whereMonth('approval_date', now()->month)
            ->whereYear('approval_date', now()->year)
            ->count();

        $totalManaging = PartnerUser::where('referrer_uuid', $partner->user_uuid)
            ->where('status', 'active')
            ->count();

        $permissions = [
            'Bronze' => [
                'can_approve' => false,
                'reason' => 'Bronze 파트너는 추천 권한이 없습니다.',
                'approvable_tiers' => [],
                'monthly_limit' => 0,
                'max_managing' => 0
            ],
            'Silver' => [
                'can_approve' => true,
                'reason' => '',
                'approvable_tiers' => ['Bronze'],
                'monthly_limit' => 2,
                'max_managing' => 5
            ],
            'Gold' => [
                'can_approve' => true,
                'reason' => '',
                'approvable_tiers' => ['Bronze', 'Silver'],
                'monthly_limit' => 5,
                'max_managing' => 15
            ],
            'Platinum' => [
                'can_approve' => true,
                'reason' => '',
                'approvable_tiers' => ['Bronze', 'Silver', 'Gold'],
                'monthly_limit' => 15,
                'max_managing' => 50
            ]
        ];

        $basePermission = $permissions[$tierName] ?? $permissions['Bronze'];

        return array_merge($basePermission, [
            'current_month_approvals' => $currentApprovals,
            'remaining_monthly' => max(0, $basePermission['monthly_limit'] - $currentApprovals),
            'total_managing' => $totalManaging,
            'remaining_capacity' => max(0, $basePermission['max_managing'] - $totalManaging)
        ]);
    }
}