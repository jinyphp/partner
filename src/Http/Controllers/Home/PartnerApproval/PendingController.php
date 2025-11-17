<?php

namespace Jiny\Partner\Http\Controllers\Home\PartnerApproval;

use Jiny\Partner\Http\Controllers\PartnerController;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;

class PendingController extends PartnerController
{
    /**
     * 승인 가능한 신청서 목록 (권한 기반 필터링)
     */
    public function __invoke(Request $request)
    {
        // 세션 인증 확인
        $user = $this->auth($request);
        if (!$user) {
            return redirect()->route('login')->with('error', '로그인이 필요합니다.');
        }

        // 파트너 정보 및 권한 확인 (tier 관계 포함 로드)
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

        // 승인 권한 확인
        $approvalPermissions = $this->getApprovalPermissions($partner);
        if (!$approvalPermissions['can_approve']) {
            return redirect()->route('home.partner.approval.index')
                ->with('error', '승인 권한이 없습니다.')
                ->with('info', $approvalPermissions['reason']);
        }

        // 필터 옵션
        $status = $request->get('status', 'all');
        $tierFilter = $request->get('tier', 'all');
        $search = $request->get('search', '');
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $perPage = $request->get('per_page', 15);

        // 승인 가능한 신청서 쿼리 구성
        $query = $this->buildPendingApplicationsQuery($partner, $approvalPermissions);

        // 상태 필터 적용
        if ($status !== 'all') {
            switch ($status) {
                case 'submitted':
                    $query->where('application_status', 'submitted');
                    break;
                case 'reviewing':
                    $query->where('application_status', 'reviewing');
                    break;
                case 'interview':
                    $query->where('application_status', 'interview');
                    break;
                case 'urgent':
                    $query->whereIn('application_status', ['submitted', 'reviewing'])
                        ->where('created_at', '<', now()->subDays(7));
                    break;
            }
        } else {
            // 기본적으로 대기 중인 것들만
            $query->whereIn('application_status', ['submitted', 'reviewing', 'interview']);
        }

        // 등급 필터 적용
        if ($tierFilter !== 'all' && in_array($tierFilter, $approvalPermissions['approvable_tiers'])) {
            $query->whereJsonContains('application_preferences->target_tier', $tierFilter);
        }

        // 검색 필터 적용
        if ($search) {
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->whereJsonContains('personal_info->name', $search)
                    ->orWhereJsonContains('personal_info->phone', $search)
                    ->orWhereJsonContains('personal_info->email', $search);
            });
        }

        // 정렬 적용
        $allowedSorts = ['created_at', 'updated_at', 'application_status'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // 페이지네이션
        $applications = $query->paginate($perPage)->appends($request->query());

        // 각 신청서에 대한 추가 정보 설정
        $applications->getCollection()->transform(function ($application) use ($approvalPermissions) {
            $application->can_approve_this = $this->canApproveThisApplication($application, $approvalPermissions);
            $application->urgency_level = $this->calculateUrgencyLevel($application);
            $application->estimated_tier = $this->estimateTargetTier($application);
            return $application;
        });

        // 필터 옵션 데이터
        $filterOptions = $this->getFilterOptions($approvalPermissions);

        return view('jiny-partner::home.partner-approval.pending', [
            'applications' => $applications,
            'partner' => $partner,
            'permissions' => $approvalPermissions,
            'filterOptions' => $filterOptions,
            'currentFilters' => [
                'status' => $status,
                'tier' => $tierFilter,
                'search' => $search,
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
                'per_page' => $perPage
            ],
            'statistics' => $this->getPendingStatistics($partner, $approvalPermissions),
            'pageTitle' => '승인 대기 신청서'
        ]);
    }

    /**
     * 승인 가능한 신청서 쿼리 구성
     */
    private function buildPendingApplicationsQuery(PartnerUser $partner, array $permissions)
    {
        $query = PartnerApplication::query();

        // 기본적으로 처리되지 않은 신청서들만
        $query->whereIn('application_status', ['submitted', 'reviewing', 'interview']);

        // 권한 기반 필터링
        $query->where(function ($permissionQuery) use ($partner, $permissions) {
            // 1. 직접 추천한 신청자
            $permissionQuery->whereJsonContains('referral_details->referrer_uuid', $partner->user_uuid);

            // 2. 하위 파트너가 추천한 신청자 (Gold 이상)
            if (in_array($partner->tier_name, ['Gold', 'Platinum'])) {
                $permissionQuery->orWhereExists(function ($subQuery) use ($partner) {
                    $subQuery->select(DB::raw(1))
                        ->from('partner_users as referrer_partners')
                        ->whereColumn('referrer_partners.user_uuid', 'partner_applications.referral_details->referrer_uuid')
                        ->where('referrer_partners.referrer_uuid', $partner->user_uuid);
                });
            }

            // 3. Platinum은 타입 기반 추가 권한
            if ($partner->tier_name === 'Platinum' && $partner->type_name) {
                $permissionQuery->orWhereJsonContains('application_preferences->target_type', $partner->type_name);
            }
        });

        return $query;
    }

    /**
     * 특정 신청서 승인 가능 여부 확인
     */
    private function canApproveThisApplication(PartnerApplication $application, array $permissions): bool
    {
        // 월별 한도 확인
        if ($permissions['remaining_monthly'] <= 0) {
            return false;
        }

        // 관리 용량 확인
        if ($permissions['remaining_capacity'] <= 0) {
            return false;
        }

        // 등급 권한 확인
        $targetTier = $application->application_preferences['target_tier'] ?? 'Bronze';
        if (!in_array($targetTier, $permissions['approvable_tiers'])) {
            return false;
        }

        // 타입 권한 확인 (Platinum 제외)
        if ($permissions['approvable_types'] !== ['all']) {
            $targetType = $application->application_preferences['target_type'] ?? null;
            if ($targetType && !in_array($targetType, $permissions['approvable_types'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * 신청서 긴급도 계산
     */
    private function calculateUrgencyLevel(PartnerApplication $application): string
    {
        $daysSinceSubmission = now()->diffInDays($application->created_at);

        if ($daysSinceSubmission >= 14) {
            return 'high';
        } elseif ($daysSinceSubmission >= 7) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * 목표 등급 추정
     */
    private function estimateTargetTier(PartnerApplication $application): string
    {
        // 신청서 정보를 기반으로 적절한 등급 추정
        $experienceYears = $application->experience_info['total_years'] ?? 0;
        $skillsCount = count($application->skills_info['primary_skills'] ?? []);
        $hasPortfolio = !empty($application->documents['portfolio_url']);

        if ($experienceYears >= 5 && $skillsCount >= 5 && $hasPortfolio) {
            return 'Silver';
        } elseif ($experienceYears >= 3 && $skillsCount >= 3) {
            return 'Bronze';
        } else {
            return 'Bronze';
        }
    }

    /**
     * 승인 권한 정보 조회 (IndexController와 동일)
     */
    private function getApprovalPermissions(PartnerUser $partner): array
    {
        // IndexController의 getApprovalPermissions 메서드와 동일한 로직
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

    /**
     * 필터 옵션 데이터
     */
    private function getFilterOptions(array $permissions): array
    {
        return [
            'statuses' => [
                'all' => '전체',
                'submitted' => '제출됨',
                'reviewing' => '검토중',
                'interview' => '면접예정',
                'urgent' => '긴급 (7일 이상)'
            ],
            'tiers' => array_combine(
                $permissions['approvable_tiers'],
                $permissions['approvable_tiers']
            ),
            'sort_options' => [
                'created_at' => '신청일시',
                'updated_at' => '최종수정일',
                'application_status' => '상태'
            ],
            'per_page_options' => [10, 15, 25, 50]
        ];
    }

    /**
     * 대기 신청서 통계
     */
    private function getPendingStatistics(PartnerUser $partner, array $permissions): array
    {
        $baseQuery = $this->buildPendingApplicationsQuery($partner, $permissions);

        return [
            'total_pending' => (clone $baseQuery)->count(),
            'submitted' => (clone $baseQuery)->where('application_status', 'submitted')->count(),
            'reviewing' => (clone $baseQuery)->where('application_status', 'reviewing')->count(),
            'interview' => (clone $baseQuery)->where('application_status', 'interview')->count(),
            'urgent' => (clone $baseQuery)
                ->whereIn('application_status', ['submitted', 'reviewing'])
                ->where('created_at', '<', now()->subDays(7))
                ->count(),
            'can_approve_now' => $permissions['remaining_monthly'],
            'capacity_remaining' => $permissions['remaining_capacity']
        ];
    }
}