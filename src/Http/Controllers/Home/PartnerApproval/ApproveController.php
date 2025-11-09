<?php

namespace Jiny\Partner\Http\Controllers\Home\PartnerApproval;

use Jiny\Partner\Http\Controllers\Home\HomeController;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Services\PartnerActivityLogService;
use Jiny\Partner\Services\PartnerNotificationService;
use Jiny\Partner\Services\PartnerApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ApproveController extends HomeController
{
    protected $activityLogService;
    protected $notificationService;
    protected $approvalService;

    public function __construct(
        PartnerActivityLogService $activityLogService,
        PartnerNotificationService $notificationService,
        PartnerApprovalService $approvalService
    ) {
        $this->activityLogService = $activityLogService;
        $this->notificationService = $notificationService;
        $this->approvalService = $approvalService;
    }

    /**
     * 상위 파트너의 제한적 승인 처리
     * 등급별 권한과 한도 내에서만 승인 가능
     */
    public function __invoke(Request $request, $id)
    {
        // JWT 인증 확인
        $user = $this->auth($request);
        if (!$user) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'JWT 인증이 필요합니다.'], 401);
            }
            return redirect()->route('login')->with('error', 'JWT 인증이 필요합니다.');
        }

        // 파트너 정보 확인
        $partner = PartnerUser::where('user_uuid', $user->uuid)->first();
        if (!$partner) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => '파트너 등록이 필요합니다.'], 403);
            }
            return redirect()->route('home.partner.regist.index')
                ->with('error', '파트너 등록이 필요합니다.');
        }

        // 입력 검증
        $request->validate([
            'comments' => 'nullable|string|max:1000',
            'assigned_tier' => 'nullable|string|in:Bronze,Silver,Gold,Platinum',
            'commission_rate' => 'nullable|numeric|min:0|max:50'
        ]);

        try {
            DB::beginTransaction();

            // 신청서 조회 및 잠금
            $application = PartnerApplication::lockForUpdate()->findOrFail($id);

            // 승인 권한 및 가능성 확인
            $validationResult = $this->validateApprovalRequest($application, $partner, $request);
            if (!$validationResult['can_approve']) {
                DB::rollBack();

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $validationResult['reason']
                    ], 403);
                }

                return redirect()->back()
                    ->with('error', $validationResult['reason'])
                    ->with('info', $validationResult['details'] ?? '');
            }

            // 승인 처리
            $approvalData = $this->processApproval($application, $partner, $request);

            // 파트너 사용자 생성
            $newPartner = $this->createPartnerUser($application, $approvalData);

            // 활동 로그 기록
            $this->activityLogService->logActivity(
                $partner->user_uuid,
                'partner_approved',
                "파트너 승인: {$newPartner->user_uuid}",
                [
                    'application_id' => $application->id,
                    'approved_partner_uuid' => $newPartner->user_uuid,
                    'assigned_tier' => $approvalData['tier'],
                    'commission_rate' => $approvalData['commission_rate'],
                    'approval_comments' => $approvalData['comments']
                ]
            );

            // 알림 발송
            $this->notificationService->sendApprovalNotification(
                $application->user_uuid,
                $approvalData['tier'],
                $approvalData['commission_rate'],
                $approvalData['comments']
            );

            // 추천인에게 알림
            if ($application->referral_details['referrer_uuid'] ?? null) {
                $this->notificationService->sendReferralSuccessNotification(
                    $application->referral_details['referrer_uuid'],
                    $application->user_uuid,
                    $approvalData['tier']
                );
            }

            DB::commit();

            Log::info('Partner application approved by upper partner', [
                'approver_uuid' => $partner->user_uuid,
                'approver_tier' => $partner->tier_name,
                'application_id' => $application->id,
                'new_partner_uuid' => $newPartner->user_uuid,
                'assigned_tier' => $approvalData['tier']
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => '파트너 승인이 완료되었습니다.',
                    'data' => [
                        'partner_uuid' => $newPartner->user_uuid,
                        'tier' => $approvalData['tier'],
                        'commission_rate' => $approvalData['commission_rate']
                    ]
                ]);
            }

            return redirect()->route('home.partner.approval.pending')
                ->with('success', '파트너 승인이 완료되었습니다.')
                ->with('info', "{$approvalData['tier']} 등급으로 승인되었습니다.");

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

            Log::error('Partner approval failed', [
                'approver_uuid' => $partner->user_uuid,
                'application_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '승인 처리 중 오류가 발생했습니다.'
                ], 500);
            }

            return redirect()->back()
                ->with('error', '승인 처리 중 오류가 발생했습니다.')
                ->with('debug', config('app.debug') ? $e->getMessage() : '');
        }
    }

    /**
     * 승인 요청 유효성 검증
     */
    private function validateApprovalRequest(PartnerApplication $application, PartnerUser $partner, Request $request): array
    {
        // 기본 승인 권한 확인
        $approvalPermissions = $this->getApprovalPermissions($partner);
        if (!$approvalPermissions['can_approve']) {
            return [
                'can_approve' => false,
                'reason' => '승인 권한이 없습니다.',
                'details' => $approvalPermissions['reason']
            ];
        }

        // 신청서 상태 확인
        if (!in_array($application->application_status, ['submitted', 'reviewing', 'interview'])) {
            return [
                'can_approve' => false,
                'reason' => '승인할 수 없는 신청서 상태입니다.',
                'details' => "현재 상태: {$application->application_status}"
            ];
        }

        // 월별 승인 한도 확인
        if ($approvalPermissions['remaining_monthly'] <= 0) {
            return [
                'can_approve' => false,
                'reason' => '이번 달 승인 한도에 도달했습니다.',
                'details' => "한도: {$approvalPermissions['monthly_limit']}명"
            ];
        }

        // 관리 용량 확인
        if ($approvalPermissions['remaining_capacity'] <= 0) {
            return [
                'can_approve' => false,
                'reason' => '최대 관리 가능 파트너 수에 도달했습니다.',
                'details' => "최대: {$approvalPermissions['max_managing']}명"
            ];
        }

        // 승인 권한 범위 확인
        if (!$this->canApproveThisApplication($application, $partner, $approvalPermissions)) {
            return [
                'can_approve' => false,
                'reason' => '이 신청서를 승인할 권한이 없습니다.',
                'details' => '권한 범위를 확인해 주세요.'
            ];
        }

        // 등급 권한 확인
        $assignedTier = $request->input('assigned_tier', $application->application_preferences['target_tier'] ?? 'Bronze');
        if (!in_array($assignedTier, $approvalPermissions['approvable_tiers'])) {
            return [
                'can_approve' => false,
                'reason' => "{$assignedTier} 등급을 승인할 권한이 없습니다.",
                'details' => '승인 가능 등급: ' . implode(', ', $approvalPermissions['approvable_tiers'])
            ];
        }

        return ['can_approve' => true];
    }

    /**
     * 특정 신청서 승인 권한 확인
     */
    private function canApproveThisApplication(PartnerApplication $application, PartnerUser $partner, array $permissions): bool
    {
        // 직접 추천한 신청자
        if ($application->referral_details['referrer_uuid'] ?? null === $partner->user_uuid) {
            return true;
        }

        // Gold 이상: 하위 파트너가 추천한 신청자
        if (in_array($partner->tier_name, ['Gold', 'Platinum'])) {
            $referrerUuid = $application->referral_details['referrer_uuid'] ?? null;
            if ($referrerUuid) {
                $referrer = PartnerUser::where('user_uuid', $referrerUuid)->first();
                if ($referrer && $referrer->referrer_uuid === $partner->user_uuid) {
                    return true;
                }
            }
        }

        // Platinum: 타입 기반 권한
        if ($partner->tier_name === 'Platinum' && $partner->type_name) {
            $targetType = $application->application_preferences['target_type'] ?? null;
            if ($targetType === $partner->type_name || $permissions['approvable_types'] === ['all']) {
                return true;
            }
        }

        return false;
    }

    /**
     * 승인 처리 데이터 구성
     */
    private function processApproval(PartnerApplication $application, PartnerUser $partner, Request $request): array
    {
        $assignedTier = $request->input('assigned_tier', $application->application_preferences['target_tier'] ?? 'Bronze');
        $comments = $request->input('comments', '');

        // 커미션 비율 결정
        $commissionRate = $request->input('commission_rate');
        if (!$commissionRate) {
            $commissionRate = $this->calculateDefaultCommissionRate($assignedTier, $partner->tier_name);
        }

        // 신청서 상태 업데이트
        $application->update([
            'application_status' => 'approved',
            'approved_by_uuid' => $partner->user_uuid,
            'approved_by_type' => 'partner',
            'approval_date' => now(),
            'approval_comments' => $comments,
            'assigned_tier' => $assignedTier,
            'assigned_commission_rate' => $commissionRate
        ]);

        return [
            'tier' => $assignedTier,
            'commission_rate' => $commissionRate,
            'comments' => $comments,
            'approved_by' => $partner->user_uuid,
            'approval_date' => now()
        ];
    }

    /**
     * 파트너 사용자 생성
     */
    private function createPartnerUser(PartnerApplication $application, array $approvalData): PartnerUser
    {
        return PartnerUser::create([
            'user_uuid' => $application->user_uuid,
            'tier_name' => $approvalData['tier'],
            'type_name' => $application->application_preferences['target_type'] ?? 'General',
            'status' => 'active',
            'referrer_uuid' => $application->referral_details['referrer_uuid'] ?? null,
            'commission_rate' => $approvalData['commission_rate'],
            'joined_at' => now(),
            'approved_by_uuid' => $approvalData['approved_by'],
            'approved_at' => $approvalData['approval_date']
        ]);
    }

    /**
     * 기본 커미션 비율 계산
     */
    private function calculateDefaultCommissionRate(string $tier, string $approverTier): float
    {
        $defaultRates = [
            'Bronze' => 5.0,
            'Silver' => 10.0,
            'Gold' => 15.0,
            'Platinum' => 20.0
        ];

        $baseRate = $defaultRates[$tier] ?? 5.0;

        // 승인자 등급에 따른 조정
        $approverMultipliers = [
            'Silver' => 0.8,   // Silver가 승인하면 80%
            'Gold' => 0.9,     // Gold가 승인하면 90%
            'Platinum' => 1.0  // Platinum이 승인하면 100%
        ];

        $multiplier = $approverMultipliers[$approverTier] ?? 0.8;

        return round($baseRate * $multiplier, 1);
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
                'reason' => 'Bronze 파트너는 승인 권한이 없습니다.',
                'approvable_tiers' => [],
                'monthly_limit' => 0,
                'max_managing' => 0,
                'approvable_types' => []
            ],
            'Silver' => [
                'can_approve' => true,
                'reason' => '',
                'approvable_tiers' => ['Bronze'],
                'monthly_limit' => 2,
                'max_managing' => 5,
                'approvable_types' => [$partner->type_name]
            ],
            'Gold' => [
                'can_approve' => true,
                'reason' => '',
                'approvable_tiers' => ['Bronze', 'Silver'],
                'monthly_limit' => 5,
                'max_managing' => 15,
                'approvable_types' => [$partner->type_name, 'additional_type']
            ],
            'Platinum' => [
                'can_approve' => true,
                'reason' => '',
                'approvable_tiers' => ['Bronze', 'Silver', 'Gold'],
                'monthly_limit' => 15,
                'max_managing' => 50,
                'approvable_types' => ['all']
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