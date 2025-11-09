<?php

namespace Jiny\Partner\Http\Controllers\Home\PartnerApproval;

use Jiny\Partner\Http\Controllers\Home\HomeController;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Models\PartnerTier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IndexController extends HomeController
{
    /**
     * 상위 파트너 승인 대시보드
     * 파트너 등급에 따른 제한적 승인 권한 제공
     */
    public function __invoke(Request $request)
    {
        // JWT 인증 확인
        $user = $this->auth($request);
        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'JWT 인증이 필요합니다. 로그인해 주세요.');
        }

        // 파트너 정보 조회
        $partner = PartnerUser::where('user_uuid', $user->uuid)->first();
        if (!$partner) {
            return redirect()->route('home.partner.regist.index')
                ->with('error', '파트너 등록이 필요합니다.')
                ->with('info', '파트너 신청을 먼저 진행해 주세요.');
        }

        // 승인 권한 확인
        $approvalPermissions = $this->getApprovalPermissions($partner);
        if (!$approvalPermissions['can_approve']) {
            return redirect()->route('home.partner.index')
                ->with('error', '승인 권한이 없습니다.')
                ->with('info', $approvalPermissions['reason']);
        }

        // 대시보드 데이터 수집
        $dashboardData = $this->getDashboardData($partner, $approvalPermissions);

        return view('jiny-partner::home.partner-approval.index', [
            'partner' => $partner,
            'permissions' => $approvalPermissions,
            'dashboard' => $dashboardData,
            'pageTitle' => '파트너 승인 관리'
        ]);
    }

    /**
     * 파트너 등급별 승인 권한 확인
     */
    private function getApprovalPermissions(PartnerUser $partner): array
    {
        $tierName = $partner->tier_name ?? 'Bronze';
        $currentApprovals = $this->getCurrentMonthApprovals($partner);
        $totalManaging = $this->getTotalManagedPartners($partner);

        // 등급별 권한 매트릭스
        $permissions = [
            'Bronze' => [
                'can_approve' => false,
                'reason' => 'Bronze 파트너는 승인 권한이 없습니다. Silver 등급 이상부터 가능합니다.',
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

        // 현재 상태 체크
        $canApproveNow = $basePermission['can_approve'] &&
                        $currentApprovals < $basePermission['monthly_limit'] &&
                        $totalManaging < $basePermission['max_managing'];

        if (!$canApproveNow && $basePermission['can_approve']) {
            if ($currentApprovals >= $basePermission['monthly_limit']) {
                $basePermission['reason'] = "이번 달 승인 한도({$basePermission['monthly_limit']}명)에 도달했습니다.";
            } elseif ($totalManaging >= $basePermission['max_managing']) {
                $basePermission['reason'] = "최대 관리 가능 파트너 수({$basePermission['max_managing']}명)에 도달했습니다.";
            }
        }

        return array_merge($basePermission, [
            'can_approve_now' => $canApproveNow,
            'current_month_approvals' => $currentApprovals,
            'remaining_monthly' => max(0, $basePermission['monthly_limit'] - $currentApprovals),
            'total_managing' => $totalManaging,
            'remaining_capacity' => max(0, $basePermission['max_managing'] - $totalManaging)
        ]);
    }

    /**
     * 이번 달 승인 수 조회
     */
    private function getCurrentMonthApprovals(PartnerUser $partner): int
    {
        return PartnerApplication::where('approved_by_uuid', $partner->user_uuid)
            ->where('application_status', 'approved')
            ->whereMonth('approval_date', now()->month)
            ->whereYear('approval_date', now()->year)
            ->count();
    }

    /**
     * 현재 관리 중인 파트너 수 조회
     */
    private function getTotalManagedPartners(PartnerUser $partner): int
    {
        return PartnerUser::where('referrer_uuid', $partner->user_uuid)
            ->where('status', 'active')
            ->count();
    }

    /**
     * 대시보드 데이터 수집
     */
    private function getDashboardData(PartnerUser $partner, array $permissions): array
    {
        $data = [
            'pending_applications' => 0,
            'recent_activities' => [],
            'managed_partners' => [],
            'statistics' => [],
            'pending_by_tier' => []
        ];

        if (!$permissions['can_approve']) {
            return $data;
        }

        // 승인 대기 중인 신청서 수 (권한 범위 내)
        $pendingQuery = PartnerApplication::whereIn('application_status', ['submitted', 'reviewing'])
            ->where(function ($query) use ($partner) {
                // 추천인이 현재 파트너인 경우 또는 하위 파트너의 추천
                $query->whereJsonContains('referral_details->referrer_uuid', $partner->user_uuid)
                    ->orWhereHas('referrer', function ($subQuery) use ($partner) {
                        $subQuery->where('referrer_uuid', $partner->user_uuid);
                    });
            });

        $data['pending_applications'] = $pendingQuery->count();

        // 등급별 대기 현황
        if ($permissions['approvable_tiers']) {
            foreach ($permissions['approvable_tiers'] as $tier) {
                $data['pending_by_tier'][$tier] = (clone $pendingQuery)
                    ->whereJsonContains('application_preferences->target_tier', $tier)
                    ->count();
            }
        }

        // 최근 활동 (승인/거부 기록)
        $data['recent_activities'] = PartnerApplication::where('approved_by_uuid', $partner->user_uuid)
            ->orWhere('rejected_by_uuid', $partner->user_uuid)
            ->with(['user'])
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($application) {
                return [
                    'id' => $application->id,
                    'applicant_name' => $application->personal_info['name'] ?? 'Unknown',
                    'action' => $application->application_status === 'approved' ? '승인' : '거부',
                    'date' => $application->updated_at,
                    'tier' => $application->application_preferences['target_tier'] ?? 'Bronze'
                ];
            });

        // 관리 중인 파트너 목록
        $data['managed_partners'] = PartnerUser::where('referrer_uuid', $partner->user_uuid)
            ->where('status', 'active')
            ->orderBy('joined_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($managedPartner) {
                return [
                    'user_uuid' => $managedPartner->user_uuid,
                    'name' => $managedPartner->name ?? 'Unknown',
                    'tier' => $managedPartner->tier_name,
                    'type' => $managedPartner->type_name,
                    'joined_at' => $managedPartner->joined_at,
                    'performance_score' => $this->calculatePerformanceScore($managedPartner)
                ];
            });

        // 통계 데이터
        $data['statistics'] = [
            'total_approved' => PartnerApplication::where('approved_by_uuid', $partner->user_uuid)
                ->where('application_status', 'approved')
                ->count(),
            'total_rejected' => PartnerApplication::where('rejected_by_uuid', $partner->user_uuid)
                ->where('application_status', 'rejected')
                ->count(),
            'approval_rate' => $this->calculateApprovalRate($partner),
            'avg_processing_days' => $this->calculateAvgProcessingDays($partner)
        ];

        return $data;
    }

    /**
     * 파트너 성과 점수 계산 (임시 구현)
     */
    private function calculatePerformanceScore(PartnerUser $partner): int
    {
        // 향후 실제 성과 데이터를 기반으로 계산
        // 현재는 임시로 랜덤 점수 반환
        return rand(70, 95);
    }

    /**
     * 승인율 계산
     */
    private function calculateApprovalRate(PartnerUser $partner): float
    {
        $totalProcessed = PartnerApplication::where('approved_by_uuid', $partner->user_uuid)
            ->whereIn('application_status', ['approved', 'rejected'])
            ->count();

        if ($totalProcessed === 0) {
            return 0.0;
        }

        $approved = PartnerApplication::where('approved_by_uuid', $partner->user_uuid)
            ->where('application_status', 'approved')
            ->count();

        return round(($approved / $totalProcessed) * 100, 1);
    }

    /**
     * 평균 처리 시간 계산 (일)
     */
    private function calculateAvgProcessingDays(PartnerUser $partner): float
    {
        $avgDays = PartnerApplication::where('approved_by_uuid', $partner->user_uuid)
            ->whereIn('application_status', ['approved', 'rejected'])
            ->whereNotNull('approval_date')
            ->orWhereNotNull('rejection_date')
            ->select(DB::raw('AVG(julianday(COALESCE(approval_date, rejection_date)) - julianday(created_at)) as avg_days'))
            ->value('avg_days');

        return $avgDays ? round($avgDays, 1) : 0.0;
    }
}