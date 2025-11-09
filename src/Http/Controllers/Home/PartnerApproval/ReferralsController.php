<?php

namespace Jiny\Partner\Http\Controllers\Home\PartnerApproval;

use Jiny\Partner\Http\Controllers\Home\HomeController;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReferralsController extends HomeController
{
    /**
     * 추천 관리 대시보드
     * 상위 파트너의 추천 현황 및 관리
     */
    public function __invoke(Request $request)
    {
        // JWT 인증 확인
        $user = $this->auth($request);
        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'JWT 인증이 필요합니다.');
        }

        // 파트너 정보 확인
        $partner = PartnerUser::where('user_uuid', $user->uuid)->first();
        if (!$partner) {
            return redirect()->route('home.partner.regist.index')
                ->with('error', '파트너 등록이 필요합니다.');
        }

        // 승인 권한 확인
        $approvalPermissions = $this->getApprovalPermissions($partner);
        if (!$approvalPermissions['can_approve']) {
            return redirect()->route('home.partner.approval.index')
                ->with('error', '추천 관리 권한이 없습니다.')
                ->with('info', $approvalPermissions['reason']);
        }

        // 필터 옵션
        $status = $request->get('status', 'all');
        $tier = $request->get('tier', 'all');
        $type = $request->get('type', 'all');
        $period = $request->get('period', '30days');
        $search = $request->get('search', '');
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $perPage = $request->get('per_page', 15);

        // 추천 데이터 수집
        $referralData = $this->collectReferralData($partner, $request);

        return view('jiny-partner::home.partner-approval.referrals', [
            'partner' => $partner,
            'permissions' => $approvalPermissions,
            'referralData' => $referralData,
            'currentFilters' => [
                'status' => $status,
                'tier' => $tier,
                'type' => $type,
                'period' => $period,
                'search' => $search,
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
                'per_page' => $perPage
            ],
            'filterOptions' => $this->getFilterOptions($approvalPermissions),
            'pageTitle' => '추천 관리'
        ]);
    }

    /**
     * 추천 데이터 수집
     */
    private function collectReferralData(PartnerUser $partner, Request $request): array
    {
        $status = $request->get('status', 'all');
        $tier = $request->get('tier', 'all');
        $type = $request->get('type', 'all');
        $period = $request->get('period', '30days');
        $search = $request->get('search', '');
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $perPage = $request->get('per_page', 15);

        $data = [
            'direct_referrals' => [],
            'indirect_referrals' => [],
            'managed_partners' => [],
            'statistics' => [],
            'performance_metrics' => []
        ];

        // 직접 추천한 신청서들
        $directReferralsQuery = PartnerApplication::whereJsonContains('referral_details->referrer_uuid', $partner->user_uuid);
        $data['direct_referrals'] = $this->applyFiltersAndPaginate($directReferralsQuery, $request);

        // 간접 추천 (하위 파트너가 추천한 것들 - Gold 이상만)
        if (in_array($partner->tier_name, ['Gold', 'Platinum'])) {
            $indirectReferralsQuery = $this->getIndirectReferralsQuery($partner);
            $data['indirect_referrals'] = $this->applyFiltersAndPaginate($indirectReferralsQuery, $request);
        }

        // 관리 중인 파트너 목록
        $data['managed_partners'] = $this->getManagedPartners($partner);

        // 통계 데이터
        $data['statistics'] = $this->calculateReferralStatistics($partner);

        // 성과 지표
        $data['performance_metrics'] = $this->calculatePerformanceMetrics($partner);

        return $data;
    }

    /**
     * 간접 추천 쿼리 구성
     */
    private function getIndirectReferralsQuery(PartnerUser $partner)
    {
        // 하위 파트너들의 UUID 목록 조회
        $subPartnerUuids = PartnerUser::where('referrer_uuid', $partner->user_uuid)
            ->pluck('user_uuid')
            ->toArray();

        if (empty($subPartnerUuids)) {
            return PartnerApplication::whereRaw('1=0'); // 빈 결과 반환
        }

        return PartnerApplication::where(function ($query) use ($subPartnerUuids) {
            foreach ($subPartnerUuids as $uuid) {
                $query->orWhereJsonContains('referral_details->referrer_uuid', $uuid);
            }
        });
    }

    /**
     * 필터 및 페이지네이션 적용
     */
    private function applyFiltersAndPaginate($query, Request $request)
    {
        $status = $request->get('status', 'all');
        $tier = $request->get('tier', 'all');
        $type = $request->get('type', 'all');
        $period = $request->get('period', '30days');
        $search = $request->get('search', '');
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $perPage = $request->get('per_page', 15);

        // 상태 필터
        if ($status !== 'all') {
            $query->where('application_status', $status);
        }

        // 등급 필터
        if ($tier !== 'all') {
            $query->whereJsonContains('application_preferences->target_tier', $tier);
        }

        // 타입 필터
        if ($type !== 'all') {
            $query->whereJsonContains('application_preferences->target_type', $type);
        }

        // 기간 필터
        $periodDates = $this->getPeriodDates($period);
        if ($periodDates) {
            $query->whereBetween('created_at', $periodDates);
        }

        // 검색 필터
        if ($search) {
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->whereJsonContains('personal_info->name', $search)
                    ->orWhereJsonContains('personal_info->email', $search)
                    ->orWhereJsonContains('personal_info->phone', $search);
            });
        }

        // 정렬
        $allowedSorts = ['created_at', 'updated_at', 'application_status'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage)->appends($request->query());
    }

    /**
     * 기간 날짜 범위 반환
     */
    private function getPeriodDates(string $period): ?array
    {
        $now = now();

        switch ($period) {
            case '7days':
                return [$now->copy()->subDays(7), $now];
            case '30days':
                return [$now->copy()->subDays(30), $now];
            case '90days':
                return [$now->copy()->subDays(90), $now];
            case '1year':
                return [$now->copy()->subYear(), $now];
            case 'all':
            default:
                return null;
        }
    }

    /**
     * 관리 중인 파트너 목록 조회
     */
    private function getManagedPartners(PartnerUser $partner): array
    {
        return PartnerUser::where('referrer_uuid', $partner->user_uuid)
            ->where('status', 'active')
            ->orderBy('joined_at', 'desc')
            ->get()
            ->map(function ($managedPartner) {
                return [
                    'user_uuid' => $managedPartner->user_uuid,
                    'tier_name' => $managedPartner->tier_name,
                    'type_name' => $managedPartner->type_name,
                    'commission_rate' => $managedPartner->commission_rate,
                    'joined_at' => $managedPartner->joined_at,
                    'total_referrals' => $this->countPartnerReferrals($managedPartner->user_uuid),
                    'active_referrals' => $this->countActivePartnerReferrals($managedPartner->user_uuid),
                    'performance_score' => $this->calculatePartnerPerformanceScore($managedPartner->user_uuid)
                ];
            })
            ->toArray();
    }

    /**
     * 파트너의 추천 수 계산
     */
    private function countPartnerReferrals(string $partnerUuid): int
    {
        return PartnerApplication::whereJsonContains('referral_details->referrer_uuid', $partnerUuid)
            ->count();
    }

    /**
     * 파트너의 활성 추천 수 계산
     */
    private function countActivePartnerReferrals(string $partnerUuid): int
    {
        return PartnerApplication::whereJsonContains('referral_details->referrer_uuid', $partnerUuid)
            ->where('application_status', 'approved')
            ->count();
    }

    /**
     * 파트너 성과 점수 계산
     */
    private function calculatePartnerPerformanceScore(string $partnerUuid): int
    {
        $totalReferrals = $this->countPartnerReferrals($partnerUuid);
        $successfulReferrals = $this->countActivePartnerReferrals($partnerUuid);

        if ($totalReferrals === 0) {
            return 0;
        }

        $successRate = ($successfulReferrals / $totalReferrals) * 100;

        // 기본 점수 (성공률 기반)
        $score = $successRate;

        // 추천 수량에 따른 보너스
        if ($totalReferrals >= 10) {
            $score += 10;
        } elseif ($totalReferrals >= 5) {
            $score += 5;
        }

        return min(100, (int) $score);
    }

    /**
     * 추천 통계 계산
     */
    private function calculateReferralStatistics(PartnerUser $partner): array
    {
        $directStats = $this->getDirectReferralStats($partner);
        $indirectStats = [];

        if (in_array($partner->tier_name, ['Gold', 'Platinum'])) {
            $indirectStats = $this->getIndirectReferralStats($partner);
        }

        return [
            'direct' => $directStats,
            'indirect' => $indirectStats,
            'total' => $this->mergeTotalStats($directStats, $indirectStats)
        ];
    }

    /**
     * 직접 추천 통계
     */
    private function getDirectReferralStats(PartnerUser $partner): array
    {
        $baseQuery = PartnerApplication::whereJsonContains('referral_details->referrer_uuid', $partner->user_uuid);

        return [
            'total' => (clone $baseQuery)->count(),
            'approved' => (clone $baseQuery)->where('application_status', 'approved')->count(),
            'rejected' => (clone $baseQuery)->where('application_status', 'rejected')->count(),
            'pending' => (clone $baseQuery)->whereIn('application_status', ['submitted', 'reviewing', 'interview'])->count(),
            'this_month' => (clone $baseQuery)->whereMonth('created_at', now()->month)->count(),
            'last_30_days' => (clone $baseQuery)->where('created_at', '>=', now()->subDays(30))->count()
        ];
    }

    /**
     * 간접 추천 통계
     */
    private function getIndirectReferralStats(PartnerUser $partner): array
    {
        $indirectQuery = $this->getIndirectReferralsQuery($partner);

        return [
            'total' => (clone $indirectQuery)->count(),
            'approved' => (clone $indirectQuery)->where('application_status', 'approved')->count(),
            'rejected' => (clone $indirectQuery)->where('application_status', 'rejected')->count(),
            'pending' => (clone $indirectQuery)->whereIn('application_status', ['submitted', 'reviewing', 'interview'])->count(),
            'this_month' => (clone $indirectQuery)->whereMonth('created_at', now()->month)->count(),
            'last_30_days' => (clone $indirectQuery)->where('created_at', '>=', now()->subDays(30))->count()
        ];
    }

    /**
     * 전체 통계 병합
     */
    private function mergeTotalStats(array $direct, array $indirect): array
    {
        return [
            'total' => $direct['total'] + ($indirect['total'] ?? 0),
            'approved' => $direct['approved'] + ($indirect['approved'] ?? 0),
            'rejected' => $direct['rejected'] + ($indirect['rejected'] ?? 0),
            'pending' => $direct['pending'] + ($indirect['pending'] ?? 0),
            'this_month' => $direct['this_month'] + ($indirect['this_month'] ?? 0),
            'last_30_days' => $direct['last_30_days'] + ($indirect['last_30_days'] ?? 0)
        ];
    }

    /**
     * 성과 지표 계산
     */
    private function calculatePerformanceMetrics(PartnerUser $partner): array
    {
        $directStats = $this->getDirectReferralStats($partner);

        $successRate = $directStats['total'] > 0
            ? round(($directStats['approved'] / $directStats['total']) * 100, 1)
            : 0.0;

        $monthlyAverage = $this->calculateMonthlyAverage($partner);
        $tierDistribution = $this->getTierDistribution($partner);
        $growthRate = $this->calculateGrowthRate($partner);

        return [
            'success_rate' => $successRate,
            'monthly_average' => $monthlyAverage,
            'tier_distribution' => $tierDistribution,
            'growth_rate' => $growthRate,
            'ranking' => $this->calculateRanking($partner),
            'commission_earned' => $this->estimateCommissionEarned($partner)
        ];
    }

    /**
     * 월평균 추천 수 계산
     */
    private function calculateMonthlyAverage(PartnerUser $partner): float
    {
        $monthsActive = max(1, now()->diffInMonths($partner->joined_at));
        $totalReferrals = PartnerApplication::whereJsonContains('referral_details->referrer_uuid', $partner->user_uuid)->count();

        return round($totalReferrals / $monthsActive, 1);
    }

    /**
     * 등급별 분포
     */
    private function getTierDistribution(PartnerUser $partner): array
    {
        $approvedApplications = PartnerApplication::whereJsonContains('referral_details->referrer_uuid', $partner->user_uuid)
            ->where('application_status', 'approved')
            ->get();

        $distribution = ['Bronze' => 0, 'Silver' => 0, 'Gold' => 0, 'Platinum' => 0];

        foreach ($approvedApplications as $application) {
            $tier = $application->assigned_tier ?? $application->application_preferences['target_tier'] ?? 'Bronze';
            if (isset($distribution[$tier])) {
                $distribution[$tier]++;
            }
        }

        return $distribution;
    }

    /**
     * 성장률 계산
     */
    private function calculateGrowthRate(PartnerUser $partner): float
    {
        $thisMonth = PartnerApplication::whereJsonContains('referral_details->referrer_uuid', $partner->user_uuid)
            ->whereMonth('created_at', now()->month)
            ->count();

        $lastMonth = PartnerApplication::whereJsonContains('referral_details->referrer_uuid', $partner->user_uuid)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->count();

        if ($lastMonth === 0) {
            return $thisMonth > 0 ? 100.0 : 0.0;
        }

        return round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1);
    }

    /**
     * 랭킹 계산 (임시 구현)
     */
    private function calculateRanking(PartnerUser $partner): array
    {
        // 실제 구현에서는 모든 파트너와 비교하여 랭킹 계산
        return [
            'current_rank' => rand(1, 100),
            'total_partners' => 100,
            'percentile' => rand(70, 95)
        ];
    }

    /**
     * 예상 커미션 수익 계산
     */
    private function estimateCommissionEarned(PartnerUser $partner): array
    {
        $approvedApplications = PartnerApplication::whereJsonContains('referral_details->referrer_uuid', $partner->user_uuid)
            ->where('application_status', 'approved')
            ->get();

        $totalCommission = 0;
        foreach ($approvedApplications as $application) {
            // 기본 추천 보너스 (등급별)
            $tier = $application->assigned_tier ?? 'Bronze';
            $bonuses = ['Bronze' => 50000, 'Silver' => 100000, 'Gold' => 200000, 'Platinum' => 500000];
            $totalCommission += $bonuses[$tier] ?? 50000;
        }

        return [
            'total_estimated' => $totalCommission,
            'this_month' => $totalCommission * 0.1, // 임시: 총 수익의 10%가 이번 달 수익
            'currency' => 'KRW'
        ];
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
                'approved' => '승인됨',
                'rejected' => '거부됨'
            ],
            'tiers' => [
                'all' => '전체',
                'Bronze' => 'Bronze',
                'Silver' => 'Silver',
                'Gold' => 'Gold',
                'Platinum' => 'Platinum'
            ],
            'types' => [
                'all' => '전체',
                'Developer' => '개발자',
                'Designer' => '디자이너',
                'Marketer' => '마케터',
                'Manager' => '관리자'
            ],
            'periods' => [
                '7days' => '최근 7일',
                '30days' => '최근 30일',
                '90days' => '최근 90일',
                '1year' => '최근 1년',
                'all' => '전체 기간'
            ],
            'sort_options' => [
                'created_at' => '신청일시',
                'updated_at' => '최종수정일',
                'application_status' => '상태'
            ],
            'per_page_options' => [10, 15, 25, 50]
        ];
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