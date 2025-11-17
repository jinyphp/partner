<?php

namespace Jiny\Partner\Http\Controllers\Home\PartnerApproval;

use Jiny\Partner\Http\Controllers\PartnerController;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermissionsController extends PartnerController
{
    /**
     * 권한 확인 및 관리 페이지
     * 파트너의 현재 권한 상세 정보와 권한 범위 제공
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

        // 권한 데이터 수집
        $permissionsData = $this->collectPermissionsData($partner);

        // 특정 권한 확인 요청 (API 용도)
        if ($request->has('check_permission')) {
            return $this->checkSpecificPermission($request, $partner, $permissionsData);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $permissionsData
            ]);
        }

        return view('jiny-partner::home.partner-approval.permissions', [
            'partner' => $partner,
            'permissionsData' => $permissionsData,
            'pageTitle' => '권한 관리'
        ]);
    }

    /**
     * 특정 권한 확인
     */
    private function checkSpecificPermission(Request $request, PartnerUser $partner, array $permissionsData): \Illuminate\Http\JsonResponse
    {
        $permission = $request->get('check_permission');
        $targetTier = $request->get('target_tier');
        $targetType = $request->get('target_type');
        $applicationId = $request->get('application_id');

        $result = ['has_permission' => false, 'reason' => '', 'details' => []];

        switch ($permission) {
            case 'approve':
                $result = $this->checkApprovalPermission($partner, $targetTier, $targetType, $applicationId);
                break;
            case 'reject':
                $result = $this->checkRejectionPermission($partner, $targetTier, $targetType, $applicationId);
                break;
            case 'recommend':
                $result = $this->checkRecommendationPermission($partner, $targetTier, $targetType);
                break;
            case 'manage':
                $result = $this->checkManagementPermission($partner, $targetTier);
                break;
            default:
                $result = ['has_permission' => false, 'reason' => '알 수 없는 권한 유형입니다.'];
        }

        return response()->json([
            'success' => true,
            'permission' => $permission,
            'result' => $result
        ]);
    }

    /**
     * 권한 데이터 수집
     */
    private function collectPermissionsData(PartnerUser $partner): array
    {
        return [
            'basic_info' => $this->getBasicPermissionInfo($partner),
            'approval_permissions' => $this->getApprovalPermissions($partner),
            'tier_permissions' => $this->getTierPermissions($partner),
            'type_permissions' => $this->getTypePermissions($partner),
            'management_permissions' => $this->getManagementPermissions($partner),
            'special_permissions' => $this->getSpecialPermissions($partner),
            'permission_matrix' => $this->getPermissionMatrix($partner),
            'restrictions' => $this->getPermissionRestrictions($partner),
            'delegation_info' => $this->getDelegationInfo($partner),
            'audit_trail' => $this->getPermissionAuditTrail($partner)
        ];
    }

    /**
     * 기본 권한 정보
     */
    private function getBasicPermissionInfo(PartnerUser $partner): array
    {
        $tierName = $partner->tier_name ?? 'Bronze';

        return [
            'partner_tier' => $tierName,
            'partner_type' => $partner->type_name ?? 'General',
            'status' => $partner->status ?? 'inactive',
            'joined_date' => $partner->joined_at,
            'has_approval_rights' => in_array($tierName, ['Silver', 'Gold', 'Platinum']),
            'has_management_rights' => in_array($tierName, ['Gold', 'Platinum']),
            'has_admin_rights' => $tierName === 'Platinum',
            'commission_rate' => $partner->commission_rate ?? 0,
            'referrer_uuid' => $partner->referrer_uuid,
            'is_top_level' => empty($partner->referrer_uuid)
        ];
    }

    /**
     * 승인 권한 상세
     */
    private function getApprovalPermissions(PartnerUser $partner): array
    {
        $tierName = $partner->tier_name ?? 'Bronze';

        $tierPermissions = [
            'Bronze' => [
                'can_approve' => false,
                'monthly_limit' => 0,
                'max_managing' => 0,
                'approvable_tiers' => [],
                'description' => '승인 권한 없음'
            ],
            'Silver' => [
                'can_approve' => true,
                'monthly_limit' => 2,
                'max_managing' => 5,
                'approvable_tiers' => ['Bronze'],
                'description' => 'Bronze 등급만 승인 가능'
            ],
            'Gold' => [
                'can_approve' => true,
                'monthly_limit' => 5,
                'max_managing' => 15,
                'approvable_tiers' => ['Bronze', 'Silver'],
                'description' => 'Bronze, Silver 등급 승인 가능'
            ],
            'Platinum' => [
                'can_approve' => true,
                'monthly_limit' => 15,
                'max_managing' => 50,
                'approvable_tiers' => ['Bronze', 'Silver', 'Gold'],
                'description' => '모든 등급 승인 가능'
            ]
        ];

        $basePermissions = $tierPermissions[$tierName] ?? $tierPermissions['Bronze'];

        // 현재 사용량 계산
        $currentUsage = $this->calculateCurrentUsage($partner);

        return array_merge($basePermissions, [
            'current_month_approvals' => $currentUsage['approvals'],
            'remaining_monthly' => max(0, $basePermissions['monthly_limit'] - $currentUsage['approvals']),
            'current_managing' => $currentUsage['managing'],
            'remaining_capacity' => max(0, $basePermissions['max_managing'] - $currentUsage['managing']),
            'usage_percentage' => $basePermissions['monthly_limit'] > 0
                ? round(($currentUsage['approvals'] / $basePermissions['monthly_limit']) * 100, 1)
                : 0,
            'capacity_percentage' => $basePermissions['max_managing'] > 0
                ? round(($currentUsage['managing'] / $basePermissions['max_managing']) * 100, 1)
                : 0
        ]);
    }

    /**
     * 현재 사용량 계산
     */
    private function calculateCurrentUsage(PartnerUser $partner): array
    {
        $approvals = PartnerApplication::where('approved_by_uuid', $partner->user_uuid)
            ->where('application_status', 'approved')
            ->whereMonth('approval_date', now()->month)
            ->whereYear('approval_date', now()->year)
            ->count();

        $managing = PartnerUser::where('referrer_uuid', $partner->user_uuid)
            ->where('status', 'active')
            ->count();

        return [
            'approvals' => $approvals,
            'managing' => $managing
        ];
    }

    /**
     * 등급별 권한
     */
    private function getTierPermissions(PartnerUser $partner): array
    {
        $tierName = $partner->tier_name ?? 'Bronze';
        $permissions = [];

        $allTiers = ['Bronze', 'Silver', 'Gold', 'Platinum'];

        foreach ($allTiers as $tier) {
            $canApprove = $this->canApproveTier($tierName, $tier);
            $canReject = $canApprove; // 거부 권한은 승인 권한과 동일
            $canRecommend = $canApprove; // 추천 권한도 승인 권한과 동일

            $permissions[$tier] = [
                'can_approve' => $canApprove,
                'can_reject' => $canReject,
                'can_recommend' => $canRecommend,
                'can_manage' => $this->canManageTier($tierName, $tier),
                'restrictions' => $this->getTierRestrictions($tierName, $tier),
                'commission_eligibility' => $this->getCommissionEligibility($tierName, $tier)
            ];
        }

        return [
            'tier_matrix' => $permissions,
            'highest_approvable' => $this->getHighestApprovableTier($tierName),
            'total_approvable_count' => count(array_filter($permissions, function($p) { return $p['can_approve']; }))
        ];
    }

    /**
     * 등급 승인 가능 여부 확인
     */
    private function canApproveTier(string $partnerTier, string $targetTier): bool
    {
        $tierHierarchy = [
            'Platinum' => ['Bronze', 'Silver', 'Gold'],
            'Gold' => ['Bronze', 'Silver'],
            'Silver' => ['Bronze'],
            'Bronze' => []
        ];

        return in_array($targetTier, $tierHierarchy[$partnerTier] ?? []);
    }

    /**
     * 등급 관리 가능 여부 확인
     */
    private function canManageTier(string $partnerTier, string $targetTier): bool
    {
        // Gold 이상만 하위 파트너 관리 가능
        if (!in_array($partnerTier, ['Gold', 'Platinum'])) {
            return false;
        }

        return $this->canApproveTier($partnerTier, $targetTier);
    }

    /**
     * 등급별 제한사항
     */
    private function getTierRestrictions(string $partnerTier, string $targetTier): array
    {
        $restrictions = [];

        if (!$this->canApproveTier($partnerTier, $targetTier)) {
            $restrictions[] = '등급 권한 부족';
        }

        // 추가 제한사항들
        if ($targetTier === 'Platinum' && $partnerTier !== 'Platinum') {
            $restrictions[] = 'Platinum은 Platinum만 승인 가능';
        }

        if ($targetTier === 'Gold' && !in_array($partnerTier, ['Gold', 'Platinum'])) {
            $restrictions[] = 'Gold 이상만 Gold 승인 가능';
        }

        return $restrictions;
    }

    /**
     * 커미션 자격
     */
    private function getCommissionEligibility(string $partnerTier, string $targetTier): array
    {
        $baseRates = ['Bronze' => 5, 'Silver' => 10, 'Gold' => 15, 'Platinum' => 20];
        $multipliers = ['Silver' => 0.8, 'Gold' => 0.9, 'Platinum' => 1.0];

        $baseRate = $baseRates[$targetTier] ?? 0;
        $multiplier = $multipliers[$partnerTier] ?? 0;

        return [
            'eligible' => $this->canApproveTier($partnerTier, $targetTier),
            'base_rate' => $baseRate,
            'multiplier' => $multiplier,
            'final_rate' => $baseRate * $multiplier,
            'currency' => 'KRW'
        ];
    }

    /**
     * 최고 승인 가능 등급
     */
    private function getHighestApprovableTier(string $partnerTier): ?string
    {
        $approvableMap = [
            'Platinum' => 'Gold',
            'Gold' => 'Silver',
            'Silver' => 'Bronze',
            'Bronze' => null
        ];

        return $approvableMap[$partnerTier] ?? null;
    }

    /**
     * 타입별 권한
     */
    private function getTypePermissions(PartnerUser $partner): array
    {
        $partnerType = $partner->type_name ?? 'General';
        $tierName = $partner->tier_name ?? 'Bronze';

        $typePermissions = [
            'own_type' => [
                'can_approve' => in_array($tierName, ['Silver', 'Gold', 'Platinum']),
                'can_manage' => in_array($tierName, ['Gold', 'Platinum']),
                'description' => "자신의 타입 ({$partnerType}) 관리 권한"
            ],
            'cross_type' => [
                'can_approve' => $tierName === 'Platinum',
                'can_manage' => $tierName === 'Platinum',
                'description' => 'Platinum만 다른 타입 관리 가능'
            ],
            'all_types' => [
                'can_approve' => $tierName === 'Platinum',
                'can_manage' => $tierName === 'Platinum',
                'description' => 'Platinum만 모든 타입 관리 가능'
            ]
        ];

        return [
            'partner_type' => $partnerType,
            'type_matrix' => $typePermissions,
            'manageable_types' => $this->getManageableTypes($partner),
            'type_restrictions' => $this->getTypeRestrictions($partner)
        ];
    }

    /**
     * 관리 가능한 타입 목록
     */
    private function getManageableTypes(PartnerUser $partner): array
    {
        $tierName = $partner->tier_name ?? 'Bronze';
        $partnerType = $partner->type_name ?? 'General';

        if ($tierName === 'Platinum') {
            return ['all']; // 모든 타입 관리 가능
        } elseif (in_array($tierName, ['Silver', 'Gold'])) {
            return [$partnerType]; // 자신의 타입만
        } else {
            return []; // 관리 권한 없음
        }
    }

    /**
     * 타입별 제한사항
     */
    private function getTypeRestrictions(PartnerUser $partner): array
    {
        $tierName = $partner->tier_name ?? 'Bronze';
        $restrictions = [];

        if ($tierName === 'Bronze') {
            $restrictions[] = 'Bronze 등급은 타입 관리 권한이 없습니다';
        } elseif (in_array($tierName, ['Silver', 'Gold'])) {
            $restrictions[] = '자신의 타입만 관리 가능합니다';
        }

        return $restrictions;
    }

    /**
     * 관리 권한
     */
    private function getManagementPermissions(PartnerUser $partner): array
    {
        $tierName = $partner->tier_name ?? 'Bronze';

        return [
            'can_manage_partners' => in_array($tierName, ['Silver', 'Gold', 'Platinum']),
            'can_view_analytics' => in_array($tierName, ['Gold', 'Platinum']),
            'can_access_reports' => in_array($tierName, ['Gold', 'Platinum']),
            'can_modify_settings' => $tierName === 'Platinum',
            'can_delegate_permissions' => $tierName === 'Platinum',
            'management_scope' => $this->getManagementScope($partner),
            'reporting_access' => $this->getReportingAccess($partner)
        ];
    }

    /**
     * 관리 범위
     */
    private function getManagementScope(PartnerUser $partner): array
    {
        $tierName = $partner->tier_name ?? 'Bronze';

        $scopes = [
            'Bronze' => ['scope' => 'none', 'description' => '관리 권한 없음'],
            'Silver' => ['scope' => 'direct', 'description' => '직접 추천한 파트너만'],
            'Gold' => ['scope' => 'hierarchical', 'description' => '직접 + 간접 추천 파트너'],
            'Platinum' => ['scope' => 'global', 'description' => '타입 내 전체 파트너']
        ];

        return $scopes[$tierName] ?? $scopes['Bronze'];
    }

    /**
     * 리포팅 접근 권한
     */
    private function getReportingAccess(PartnerUser $partner): array
    {
        $tierName = $partner->tier_name ?? 'Bronze';

        return [
            'basic_stats' => true,
            'detailed_analytics' => in_array($tierName, ['Gold', 'Platinum']),
            'financial_reports' => in_array($tierName, ['Gold', 'Platinum']),
            'performance_metrics' => in_array($tierName, ['Gold', 'Platinum']),
            'system_logs' => $tierName === 'Platinum',
            'audit_trails' => $tierName === 'Platinum'
        ];
    }

    /**
     * 특별 권한
     */
    private function getSpecialPermissions(PartnerUser $partner): array
    {
        $tierName = $partner->tier_name ?? 'Bronze';
        $isTopLevel = empty($partner->referrer_uuid);

        return [
            'emergency_approval' => $tierName === 'Platinum',
            'tier_override' => false, // 특별한 경우에만 부여
            'bulk_operations' => in_array($tierName, ['Gold', 'Platinum']),
            'api_access' => in_array($tierName, ['Gold', 'Platinum']),
            'webhook_management' => $tierName === 'Platinum',
            'system_integration' => $tierName === 'Platinum',
            'training_access' => true,
            'priority_support' => in_array($tierName, ['Gold', 'Platinum']),
            'beta_features' => $tierName === 'Platinum',
            'top_level_privileges' => $isTopLevel && $tierName === 'Platinum'
        ];
    }

    /**
     * 권한 매트릭스
     */
    private function getPermissionMatrix(PartnerUser $partner): array
    {
        $tierName = $partner->tier_name ?? 'Bronze';
        $allActions = ['view', 'approve', 'reject', 'recommend', 'manage', 'report'];
        $allTiers = ['Bronze', 'Silver', 'Gold', 'Platinum'];

        $matrix = [];

        foreach ($allTiers as $targetTier) {
            foreach ($allActions as $action) {
                $matrix[$targetTier][$action] = $this->hasPermissionForAction($tierName, $targetTier, $action);
            }
        }

        return [
            'matrix' => $matrix,
            'summary' => $this->generateMatrixSummary($matrix),
            'legend' => [
                'view' => '조회 권한',
                'approve' => '승인 권한',
                'reject' => '거부 권한',
                'recommend' => '추천 권한',
                'manage' => '관리 권한',
                'report' => '리포팅 권한'
            ]
        ];
    }

    /**
     * 액션별 권한 확인
     */
    private function hasPermissionForAction(string $partnerTier, string $targetTier, string $action): bool
    {
        switch ($action) {
            case 'view':
                return true; // 모든 파트너는 조회 가능
            case 'approve':
            case 'reject':
            case 'recommend':
                return $this->canApproveTier($partnerTier, $targetTier);
            case 'manage':
                return $this->canManageTier($partnerTier, $targetTier);
            case 'report':
                return in_array($partnerTier, ['Gold', 'Platinum']);
            default:
                return false;
        }
    }

    /**
     * 매트릭스 요약 생성
     */
    private function generateMatrixSummary(array $matrix): array
    {
        $summary = [];

        foreach ($matrix as $tier => $actions) {
            $allowedActions = array_filter($actions);
            $summary[$tier] = [
                'total_permissions' => count($allowedActions),
                'allowed_actions' => array_keys($allowedActions),
                'permission_level' => $this->calculatePermissionLevel(count($allowedActions))
            ];
        }

        return $summary;
    }

    /**
     * 권한 레벨 계산
     */
    private function calculatePermissionLevel(int $permissionCount): string
    {
        if ($permissionCount >= 5) return 'high';
        if ($permissionCount >= 3) return 'medium';
        if ($permissionCount >= 1) return 'low';
        return 'none';
    }

    /**
     * 권한 제한사항
     */
    private function getPermissionRestrictions(PartnerUser $partner): array
    {
        $restrictions = [];

        // 계정 상태 확인
        if ($partner->status !== 'active') {
            $restrictions[] = [
                'type' => 'account_status',
                'description' => '비활성 계정으로 인한 권한 제한',
                'severity' => 'critical'
            ];
        }

        // 한도 제한
        $usage = $this->calculateCurrentUsage($partner);
        $permissions = $this->getApprovalPermissions($partner);

        if ($usage['approvals'] >= $permissions['monthly_limit'] && $permissions['monthly_limit'] > 0) {
            $restrictions[] = [
                'type' => 'monthly_limit',
                'description' => '월간 승인 한도 초과',
                'severity' => 'high'
            ];
        }

        if ($usage['managing'] >= $permissions['max_managing'] && $permissions['max_managing'] > 0) {
            $restrictions[] = [
                'type' => 'capacity_limit',
                'description' => '최대 관리 용량 초과',
                'severity' => 'high'
            ];
        }

        // 등급별 제한
        $tierName = $partner->tier_name ?? 'Bronze';
        if ($tierName === 'Bronze') {
            $restrictions[] = [
                'type' => 'tier_limitation',
                'description' => 'Bronze 등급은 승인 권한이 없습니다',
                'severity' => 'medium'
            ];
        }

        return $restrictions;
    }

    /**
     * 위임 정보
     */
    private function getDelegationInfo(PartnerUser $partner): array
    {
        // 실제 구현에서는 위임 테이블에서 조회
        return [
            'can_delegate' => $partner->tier_name === 'Platinum',
            'current_delegations' => [], // 현재 위임 목록
            'received_delegations' => [], // 받은 위임 목록
            'delegation_history' => [], // 위임 이력
            'max_delegations' => $partner->tier_name === 'Platinum' ? 3 : 0
        ];
    }

    /**
     * 권한 감사 추적
     */
    private function getPermissionAuditTrail(PartnerUser $partner): array
    {
        // 최근 권한 사용 이력
        $recentApprovals = PartnerApplication::where('approved_by_uuid', $partner->user_uuid)
            ->orderBy('approval_date', 'desc')
            ->limit(10)
            ->get(['id', 'user_uuid', 'approval_date', 'assigned_tier'])
            ->toArray();

        $recentRejections = PartnerApplication::where('rejected_by_uuid', $partner->user_uuid)
            ->orderBy('rejection_date', 'desc')
            ->limit(10)
            ->get(['id', 'user_uuid', 'rejection_date', 'rejection_reason'])
            ->toArray();

        return [
            'recent_approvals' => $recentApprovals,
            'recent_rejections' => $recentRejections,
            'permission_changes' => [], // 권한 변경 이력
            'access_logs' => [], // 접근 로그
            'violation_attempts' => [] // 권한 위반 시도
        ];
    }

    /**
     * 특정 승인 권한 확인
     */
    private function checkApprovalPermission(PartnerUser $partner, ?string $targetTier, ?string $targetType, ?string $applicationId): array
    {
        $tierName = $partner->tier_name ?? 'Bronze';

        // 기본 승인 권한 확인
        if (!in_array($tierName, ['Silver', 'Gold', 'Platinum'])) {
            return [
                'has_permission' => false,
                'reason' => 'Bronze 등급은 승인 권한이 없습니다.',
                'details' => ['required_tier' => 'Silver 이상']
            ];
        }

        // 등급 권한 확인
        if ($targetTier && !$this->canApproveTier($tierName, $targetTier)) {
            return [
                'has_permission' => false,
                'reason' => "{$targetTier} 등급을 승인할 권한이 없습니다.",
                'details' => ['approvable_tiers' => $this->getApprovalPermissions($partner)['approvable_tiers']]
            ];
        }

        // 월간 한도 확인
        $usage = $this->calculateCurrentUsage($partner);
        $permissions = $this->getApprovalPermissions($partner);

        if ($usage['approvals'] >= $permissions['monthly_limit']) {
            return [
                'has_permission' => false,
                'reason' => '월간 승인 한도를 초과했습니다.',
                'details' => [
                    'current_usage' => $usage['approvals'],
                    'limit' => $permissions['monthly_limit'],
                    'next_reset' => now()->endOfMonth()->addDay()->format('Y-m-d')
                ]
            ];
        }

        // 관리 용량 확인
        if ($usage['managing'] >= $permissions['max_managing']) {
            return [
                'has_permission' => false,
                'reason' => '최대 관리 용량을 초과했습니다.',
                'details' => [
                    'current_managing' => $usage['managing'],
                    'limit' => $permissions['max_managing']
                ]
            ];
        }

        return [
            'has_permission' => true,
            'reason' => '승인 권한이 있습니다.',
            'details' => [
                'remaining_monthly' => $permissions['monthly_limit'] - $usage['approvals'],
                'remaining_capacity' => $permissions['max_managing'] - $usage['managing']
            ]
        ];
    }

    /**
     * 특정 거부 권한 확인
     */
    private function checkRejectionPermission(PartnerUser $partner, ?string $targetTier, ?string $targetType, ?string $applicationId): array
    {
        // 거부 권한은 승인 권한과 동일한 로직 사용
        return $this->checkApprovalPermission($partner, $targetTier, $targetType, $applicationId);
    }

    /**
     * 특정 추천 권한 확인
     */
    private function checkRecommendationPermission(PartnerUser $partner, ?string $targetTier, ?string $targetType): array
    {
        // 추천 권한도 승인 권한과 유사한 로직 사용
        return $this->checkApprovalPermission($partner, $targetTier, $targetType, null);
    }

    /**
     * 특정 관리 권한 확인
     */
    private function checkManagementPermission(PartnerUser $partner, ?string $targetTier): array
    {
        $tierName = $partner->tier_name ?? 'Bronze';

        if (!in_array($tierName, ['Gold', 'Platinum'])) {
            return [
                'has_permission' => false,
                'reason' => 'Gold 등급 이상만 관리 권한이 있습니다.',
                'details' => ['required_tier' => 'Gold 이상']
            ];
        }

        if ($targetTier && !$this->canManageTier($tierName, $targetTier)) {
            return [
                'has_permission' => false,
                'reason' => "{$targetTier} 등급을 관리할 권한이 없습니다.",
                'details' => ['manageable_tiers' => $this->getApprovalPermissions($partner)['approvable_tiers']]
            ];
        }

        return [
            'has_permission' => true,
            'reason' => '관리 권한이 있습니다.'
        ];
    }
}