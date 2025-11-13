<?php

namespace Jiny\Partner\Http\Controllers\Home\PartnerApproval;

use Jiny\Partner\Http\Controllers\PartnerController;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ApproveController extends PartnerController
{

    /**
     * 상위 파트너의 제한적 승인 처리
     * 등급별 권한과 한도 내에서만 승인 가능
     */
    public function __invoke(Request $request, $id)
    {
        Log::info('ApproveController called', ['id' => $id, 'method' => $request->method()]);

        // 세션 인증 확인
        $user = $this->auth($request);
        if (!$user) {
            Log::warning('ApproveController: User not authenticated');
            return redirect()->route('login')->with('error', '로그인이 필요합니다.');
        }

        Log::info('ApproveController: User authenticated', ['user_uuid' => $user->uuid]);

        // 파트너 정보 확인 (tier 관계 포함 로드)
        $partner = PartnerUser::with('partnerTier')->where('user_uuid', $user->uuid)->first();
        if (!$partner) {
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

            // 활동 로그 기록 (간단한 로그로 대체)
            Log::info('Partner application approved', [
                'approver_uuid' => $partner->user_uuid,
                'application_id' => $application->id,
                'approved_partner_uuid' => $newPartner->user_uuid,
                'assigned_tier' => $approvalData['tier'],
                'commission_rate' => $approvalData['commission_rate'],
                'approval_comments' => $approvalData['comments']
            ]);

            // TODO: 알림 발송 기능 구현 예정

            DB::commit();

            Log::info('Partner application approved by upper partner', [
                'approver_uuid' => $partner->user_uuid,
                'approver_tier' => $partner->partnerTier->tier_name,
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
        $canApprove = $this->canApproveThisApplication($application, $partner, $approvalPermissions);
        Log::info('validateApprovalRequest: canApproveThisApplication result', [
            'can_approve' => $canApprove,
            'application_id' => $application->id,
            'partner_id' => $partner->id,
            'partner_tier' => $partner->partnerTier->tier_name,
            'referrer_partner_id' => $application->referrer_partner_id
        ]);

        if (!$canApprove) {
            Log::warning('validateApprovalRequest: Application approval permission denied');
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

        // 1. 자신의 tier보다 높은 등급 부여 금지
        $tierHierarchy = ['Bronze' => 1, 'Silver' => 2, 'Gold' => 3, 'Platinum' => 4];
        $approverTierLevel = $tierHierarchy[$partner->partnerTier->tier_name ?? 'Bronze'] ?? 1;
        $assignedTierLevel = $tierHierarchy[$assignedTier] ?? 1;

        if ($assignedTierLevel > $approverTierLevel) {
            return [
                'can_approve' => false,
                'reason' => "자신의 등급({$partner->partnerTier->tier_name})보다 높은 등급({$assignedTier})은 부여할 수 없습니다.",
                'details' => '자신과 같거나 낮은 등급만 승인 가능합니다.'
            ];
        }

        // 2. 자신보다 높은 커미션 비율 부여 금지
        $assignedCommissionRate = (float) $request->input('commission_rate', 0);
        $approverCommissionRate = (float) $partner->personal_commission_rate ?? 0;

        if ($assignedCommissionRate > $approverCommissionRate) {
            return [
                'can_approve' => false,
                'reason' => "자신의 커미션 비율({$approverCommissionRate}%)보다 높은 비율({$assignedCommissionRate}%)은 부여할 수 없습니다.",
                'details' => "최대 승인 가능 커미션: {$approverCommissionRate}%"
            ];
        }

        return ['can_approve' => true];
    }

    /**
     * 특정 신청서 승인 권한 확인
     */
    private function canApproveThisApplication(PartnerApplication $application, PartnerUser $partner, array $permissions): bool
    {
        // 1. 직접 추천한 신청자 (referrer_partner_id로 확인)
        if ($application->referrer_partner_id === $partner->id) {
            return true;
        }

        // 2. 직접 추천한 신청자 (referral_details UUID로 확인)
        if ($application->referral_details['referrer_uuid'] ?? null === $partner->user_uuid) {
            return true;
        }

        // 3. Gold 이상: 하위 파트너가 추천한 신청자
        if (in_array($partner->partnerTier->tier_name, ['Gold', 'Platinum'])) {
            $referrerUuid = $application->referral_details['referrer_uuid'] ?? null;
            if ($referrerUuid) {
                $referrer = PartnerUser::where('user_uuid', $referrerUuid)->first();
                if ($referrer && $referrer->referrer_uuid === $partner->user_uuid) {
                    return true;
                }
            }
        }

        // 4. Platinum: 타입 기반 권한
        if ($partner->partnerTier->tier_name === 'Platinum' && $partner->type_name) {
            $targetType = $application->application_preferences['target_type'] ?? null;
            if ($targetType === $partner->type_name || $permissions['approvable_types'] === ['all']) {
                return true;
            }
        }

        // 5. Gold 이상은 모든 신청서 검토 가능 (임시로 추가)
        if (in_array($partner->partnerTier->tier_name, ['Gold', 'Platinum'])) {
            return true;
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
            $commissionRate = $this->calculateDefaultCommissionRate($assignedTier, $partner->partnerTier->tier_name);
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
        // 기본값 설정
        $personalInfo = $application->personal_info ?? [];
        $tier = \Jiny\Partner\Models\PartnerTier::where('tier_name', $approvalData['tier'])->first();

        return PartnerUser::create([
            'user_id' => $application->user_id ?? 0,
            'user_uuid' => $application->user_uuid,
            'email' => $personalInfo['email'] ?? '',
            'name' => $personalInfo['name'] ?? '',
            'partner_tier_id' => $tier->id ?? 1,
            'partner_type_id' => 1, // 기본 타입
            'status' => 'active',
            'personal_commission_rate' => $approvalData['commission_rate'],
            'partner_joined_at' => now()->toDateString(),
            'tier_assigned_at' => now()->toDateString(),
            'partner_code' => $this->generatePartnerCode(),
        ]);
    }

    /**
     * 파트너 코드 생성
     */
    private function generatePartnerCode(): string
    {
        do {
            $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 20));
        } while (PartnerUser::where('partner_code', $code)->exists());

        return $code;
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
        $tierName = $partner->partnerTier->tier_name ?? 'Bronze';
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