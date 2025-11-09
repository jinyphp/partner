<?php

namespace Jiny\Partner\Http\Controllers\Home\PartnerApproval;

use Jiny\Partner\Http\Controllers\Home\HomeController;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Services\PartnerActivityLogService;
use Jiny\Partner\Services\PartnerNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RejectController extends HomeController
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
     * 상위 파트너의 신청서 거부 처리
     * 권한 범위 내에서만 거부 가능
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
            'rejection_reason' => 'required|string|in:insufficient_experience,incomplete_documents,skill_mismatch,tier_inappropriate,other',
            'rejection_comments' => 'required|string|max:1000|min:10',
            'allow_reapply' => 'nullable|boolean',
            'reapply_after_days' => 'nullable|integer|min:1|max:365'
        ], [
            'rejection_reason.required' => '거부 사유를 선택해주세요.',
            'rejection_comments.required' => '상세 의견을 입력해주세요.',
            'rejection_comments.min' => '상세 의견은 최소 10자 이상 입력해주세요.',
            'rejection_comments.max' => '상세 의견은 1000자를 초과할 수 없습니다.'
        ]);

        try {
            DB::beginTransaction();

            // 신청서 조회 및 잠금
            $application = PartnerApplication::lockForUpdate()->findOrFail($id);

            // 거부 권한 확인
            $validationResult = $this->validateRejectionRequest($application, $partner);
            if (!$validationResult['can_reject']) {
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

            // 거부 처리
            $rejectionData = $this->processRejection($application, $partner, $request);

            // 활동 로그 기록
            $this->activityLogService->logActivity(
                $partner->user_uuid,
                'partner_rejected',
                "파트너 거부: {$application->user_uuid}",
                [
                    'application_id' => $application->id,
                    'rejected_partner_uuid' => $application->user_uuid,
                    'rejection_reason' => $rejectionData['reason'],
                    'rejection_comments' => $rejectionData['comments'],
                    'allow_reapply' => $rejectionData['allow_reapply'],
                    'reapply_after_days' => $rejectionData['reapply_after_days']
                ]
            );

            // 거부 알림 발송
            $this->notificationService->sendRejectionNotification(
                $application->user_uuid,
                $rejectionData['reason'],
                $rejectionData['comments'],
                $rejectionData['allow_reapply'],
                $rejectionData['reapply_after_days']
            );

            // 추천인에게 알림 (있는 경우)
            if ($application->referral_details['referrer_uuid'] ?? null) {
                $this->notificationService->sendReferralFailedNotification(
                    $application->referral_details['referrer_uuid'],
                    $application->user_uuid,
                    $rejectionData['reason']
                );
            }

            DB::commit();

            Log::info('Partner application rejected by upper partner', [
                'rejector_uuid' => $partner->user_uuid,
                'rejector_tier' => $partner->tier_name,
                'application_id' => $application->id,
                'rejected_uuid' => $application->user_uuid,
                'rejection_reason' => $rejectionData['reason']
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => '신청서 거부가 완료되었습니다.',
                    'data' => [
                        'application_id' => $application->id,
                        'rejection_reason' => $rejectionData['reason'],
                        'allow_reapply' => $rejectionData['allow_reapply'],
                        'reapply_after_days' => $rejectionData['reapply_after_days']
                    ]
                ]);
            }

            return redirect()->route('home.partner.approval.pending')
                ->with('success', '신청서 거부가 완료되었습니다.')
                ->with('info', $rejectionData['allow_reapply'] ?
                    "재신청은 {$rejectionData['reapply_after_days']}일 후 가능합니다." :
                    '재신청이 제한되었습니다.');

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

            Log::error('Partner rejection failed', [
                'rejector_uuid' => $partner->user_uuid,
                'application_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '거부 처리 중 오류가 발생했습니다.'
                ], 500);
            }

            return redirect()->back()
                ->with('error', '거부 처리 중 오류가 발생했습니다.')
                ->with('debug', config('app.debug') ? $e->getMessage() : '');
        }
    }

    /**
     * 거부 요청 유효성 검증
     */
    private function validateRejectionRequest(PartnerApplication $application, PartnerUser $partner): array
    {
        // 기본 승인 권한 확인 (거부 권한은 승인 권한과 동일)
        $approvalPermissions = $this->getApprovalPermissions($partner);
        if (!$approvalPermissions['can_approve']) {
            return [
                'can_reject' => false,
                'reason' => '거부 권한이 없습니다.',
                'details' => $approvalPermissions['reason']
            ];
        }

        // 신청서 상태 확인
        if (!in_array($application->application_status, ['submitted', 'reviewing', 'interview'])) {
            return [
                'can_reject' => false,
                'reason' => '거부할 수 없는 신청서 상태입니다.',
                'details' => "현재 상태: {$application->application_status}"
            ];
        }

        // 거부 권한 범위 확인
        if (!$this->canRejectThisApplication($application, $partner, $approvalPermissions)) {
            return [
                'can_reject' => false,
                'reason' => '이 신청서를 거부할 권한이 없습니다.',
                'details' => '권한 범위를 확인해 주세요.'
            ];
        }

        return ['can_reject' => true];
    }

    /**
     * 특정 신청서 거부 권한 확인
     */
    private function canRejectThisApplication(PartnerApplication $application, PartnerUser $partner, array $permissions): bool
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
     * 거부 처리 데이터 구성
     */
    private function processRejection(PartnerApplication $application, PartnerUser $partner, Request $request): array
    {
        $rejectionReason = $request->input('rejection_reason');
        $rejectionComments = $request->input('rejection_comments');
        $allowReapply = $request->input('allow_reapply', true);
        $reapplyAfterDays = $request->input('reapply_after_days', 30);

        // 거부 사유에 따른 재신청 정책 조정
        $rejectionPolicies = $this->getRejectionPolicies($rejectionReason);
        if (!$rejectionPolicies['allow_reapply']) {
            $allowReapply = false;
        } else {
            $reapplyAfterDays = max($reapplyAfterDays, $rejectionPolicies['min_waiting_days']);
        }

        // 신청서 상태 업데이트
        $application->update([
            'application_status' => 'rejected',
            'rejected_by_uuid' => $partner->user_uuid,
            'rejected_by_type' => 'partner',
            'rejection_date' => now(),
            'rejection_reason' => $rejectionReason,
            'rejection_comments' => $rejectionComments,
            'allow_reapply' => $allowReapply,
            'reapply_after_date' => $allowReapply ? now()->addDays($reapplyAfterDays) : null
        ]);

        return [
            'reason' => $rejectionReason,
            'comments' => $rejectionComments,
            'allow_reapply' => $allowReapply,
            'reapply_after_days' => $allowReapply ? $reapplyAfterDays : null,
            'rejected_by' => $partner->user_uuid,
            'rejection_date' => now()
        ];
    }

    /**
     * 거부 사유별 재신청 정책
     */
    private function getRejectionPolicies(string $rejectionReason): array
    {
        $policies = [
            'insufficient_experience' => [
                'allow_reapply' => true,
                'min_waiting_days' => 90, // 경험 부족 시 3개월 대기
                'description' => '추가 경험을 쌓은 후 재신청 가능'
            ],
            'incomplete_documents' => [
                'allow_reapply' => true,
                'min_waiting_days' => 7, // 문서 미비 시 1주일 대기
                'description' => '누락된 문서 준비 후 재신청 가능'
            ],
            'skill_mismatch' => [
                'allow_reapply' => true,
                'min_waiting_days' => 60, // 기술 미스매치 시 2개월 대기
                'description' => '적합한 기술 습득 후 재신청 가능'
            ],
            'tier_inappropriate' => [
                'allow_reapply' => true,
                'min_waiting_days' => 30, // 등급 부적합 시 1개월 대기
                'description' => '적절한 등급으로 재신청 가능'
            ],
            'other' => [
                'allow_reapply' => true,
                'min_waiting_days' => 30, // 기타 사유 시 1개월 대기
                'description' => '사유에 따른 개선 후 재신청 가능'
            ]
        ];

        return $policies[$rejectionReason] ?? $policies['other'];
    }

    /**
     * 거부 사유 텍스트 변환
     */
    private function getRejectionReasonText(string $reason): string
    {
        $reasons = [
            'insufficient_experience' => '경험 부족',
            'incomplete_documents' => '서류 미비',
            'skill_mismatch' => '기술 미스매치',
            'tier_inappropriate' => '등급 부적합',
            'other' => '기타 사유'
        ];

        return $reasons[$reason] ?? '알 수 없는 사유';
    }

    /**
     * 승인 권한 정보 조회 (거부 권한과 동일)
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
                'reason' => 'Bronze 파트너는 승인/거부 권한이 없습니다.',
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