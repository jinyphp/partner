<?php

namespace Jiny\Partner\Http\Controllers\Home\PartnerApproval;

use Jiny\Partner\Http\Controllers\PartnerController;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LimitsController extends PartnerController
{
    /**
     * 승인 한도 및 용량 확인 페이지
     * 파트너의 현재 한도 상태와 이용 현황 제공
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
                return redirect()->route('home.partner.regist.status', $partnerApplication->id)
                    ->with('info', '파트너 신청이 아직 처리 중입니다.');
            } else {
                return redirect()->route('home.partner.intro')
                    ->with('info', '파트너 프로그램에 먼저 가입해 주세요.');
            }
        }

        // 한도 정보 수집
        $limitsData = $this->collectLimitsData($partner);

        return view('jiny-partner::home.partner-approval.limits', [
            'partner' => $partner,
            'limitsData' => $limitsData,
            'pageTitle' => '승인 한도 관리'
        ]);
    }

    /**
     * 한도 데이터 수집
     */
    private function collectLimitsData(PartnerUser $partner): array
    {
        $tierName = $partner->tier->name ?? 'Bronze';

        return [
            'current_tier' => $this->getCurrentTierInfo($partner),
            'approval_limits' => $this->getApprovalLimits($partner),
            'management_capacity' => $this->getManagementCapacity($partner),
            'monthly_usage' => $this->getMonthlyUsage($partner),
            'historical_data' => $this->getHistoricalData($partner),
            'tier_progression' => $this->getTierProgression($partner),
            'recommendations' => $this->generateRecommendations($partner),
            'upgrade_requirements' => $this->getUpgradeRequirements($partner)
        ];
    }

    /**
     * 현재 등급 정보
     */
    private function getCurrentTierInfo(PartnerUser $partner): array
    {
        $tierName = $partner->tier->name ?? 'Bronze';

        $tierDescriptions = [
            'Bronze' => '기본 파트너 등급',
            'Silver' => '하위 파트너 승인 권한 보유',
            'Gold' => '중급 파트너 승인 및 하위 관리 권한',
            'Platinum' => '최고급 파트너 - 전체 등급 승인 권한'
        ];

        $tierBenefits = [
            'Bronze' => ['파트너 등록', '기본 커미션 수령'],
            'Silver' => ['Bronze 승인 권한', '하위 파트너 관리 (최대 5명)', '월 2명 승인 가능'],
            'Gold' => ['Bronze, Silver 승인 권한', '하위 파트너 관리 (최대 15명)', '월 5명 승인 가능', '간접 추천 관리'],
            'Platinum' => ['전체 등급 승인 권한', '하위 파트너 관리 (최대 50명)', '월 15명 승인 가능', '전체 타입 관리 권한']
        ];

        return [
            'name' => $tierName,
            'description' => $tierDescriptions[$tierName] ?? '알 수 없는 등급',
            'benefits' => $tierBenefits[$tierName] ?? [],
            'joined_at' => $partner->joined_at,
            'tier_duration' => $partner->joined_at ? now()->diffInDays($partner->joined_at) : 0,
            'commission_rate' => $partner->commission_rate ?? 0
        ];
    }

    /**
     * 승인 한도 정보
     */
    private function getApprovalLimits(PartnerUser $partner): array
    {
        $tierName = $partner->tier->name ?? 'Bronze';

        $tierLimits = [
            'Bronze' => ['monthly_limit' => 0, 'can_approve' => false],
            'Silver' => ['monthly_limit' => 2, 'can_approve' => true],
            'Gold' => ['monthly_limit' => 5, 'can_approve' => true],
            'Platinum' => ['monthly_limit' => 15, 'can_approve' => true]
        ];

        $limits = $tierLimits[$tierName] ?? $tierLimits['Bronze'];

        // 현재 월 승인 수 (SQLite 호환 방식)
        $currentMonthApprovals = PartnerApplication::where('approved_by_uuid', $partner->user_uuid)
            ->where('application_status', 'approved')
            ->whereBetween('approval_date', [
                now()->startOfMonth(),
                now()->endOfMonth()
            ])
            ->count();

        // 이번 달 추천 수 (SQLite 호환 방식)
        $currentMonthRecommendations = PartnerApplication::whereJsonContains('referral_details->referrer_uuid', $partner->user_uuid)
            ->whereBetween('created_at', [
                now()->startOfMonth(),
                now()->endOfMonth()
            ])
            ->count();

        return [
            'monthly_approval_limit' => $limits['monthly_limit'],
            'current_month_approvals' => $currentMonthApprovals,
            'remaining_approvals' => max(0, $limits['monthly_limit'] - $currentMonthApprovals),
            'current_month_recommendations' => $currentMonthRecommendations,
            'can_approve' => $limits['can_approve'],
            'approval_percentage' => $limits['monthly_limit'] > 0
                ? round(($currentMonthApprovals / $limits['monthly_limit']) * 100, 1)
                : 0,
            'next_reset_date' => now()->endOfMonth()->addDay()->format('Y-m-d'),
            'days_until_reset' => now()->diffInDays(now()->endOfMonth()) + 1
        ];
    }

    /**
     * 관리 용량 정보
     */
    private function getManagementCapacity(PartnerUser $partner): array
    {
        $tierName = $partner->tier->name ?? 'Bronze';

        $tierCapacities = [
            'Bronze' => 0,
            'Silver' => 5,
            'Gold' => 15,
            'Platinum' => 50
        ];

        $maxCapacity = $tierCapacities[$tierName] ?? 0;

        // 현재 관리 중인 파트너 수
        $currentManaging = PartnerUser::where('referrer_uuid', $partner->user_uuid)
            ->where('status', 'active')
            ->count();

        // 관리 파트너들의 등급별 분포 (tier 관계 사용)
        $tierDistribution = PartnerUser::where('referrer_uuid', $partner->user_uuid)
            ->where('status', 'active')
            ->with('tier')
            ->get()
            ->groupBy('tier.name')
            ->map->count()
            ->toArray();

        return [
            'max_capacity' => $maxCapacity,
            'current_managing' => $currentManaging,
            'remaining_capacity' => max(0, $maxCapacity - $currentManaging),
            'capacity_percentage' => $maxCapacity > 0
                ? round(($currentManaging / $maxCapacity) * 100, 1)
                : 0,
            'tier_distribution' => $tierDistribution,
            'capacity_warning' => $currentManaging >= ($maxCapacity * 0.8), // 80% 이상 시 경고
            'capacity_status' => $this->getCapacityStatus($currentManaging, $maxCapacity)
        ];
    }

    /**
     * 용량 상태 계산
     */
    private function getCapacityStatus(int $current, int $max): string
    {
        if ($max === 0) {
            return 'unavailable';
        }

        $percentage = ($current / $max) * 100;

        if ($percentage >= 100) {
            return 'full';
        } elseif ($percentage >= 80) {
            return 'high';
        } elseif ($percentage >= 50) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * 월별 사용 현황
     */
    private function getMonthlyUsage(PartnerUser $partner): array
    {
        $currentMonth = now();
        $months = [];

        // 최근 12개월 데이터
        for ($i = 11; $i >= 0; $i--) {
            $month = $currentMonth->copy()->subMonths($i);

            $approvals = PartnerApplication::where('approved_by_uuid', $partner->user_uuid)
                ->where('application_status', 'approved')
                ->whereBetween('approval_date', [
                    $month->copy()->startOfMonth(),
                    $month->copy()->endOfMonth()
                ])
                ->count();

            $recommendations = PartnerApplication::whereJsonContains('referral_details->referrer_uuid', $partner->user_uuid)
                ->whereBetween('created_at', [
                    $month->copy()->startOfMonth(),
                    $month->copy()->endOfMonth()
                ])
                ->count();

            $rejections = PartnerApplication::where('rejected_by_uuid', $partner->user_uuid)
                ->where('application_status', 'rejected')
                ->whereBetween('rejection_date', [
                    $month->copy()->startOfMonth(),
                    $month->copy()->endOfMonth()
                ])
                ->count();

            $months[] = [
                'month' => $month->format('Y-m'),
                'month_name' => $month->format('Y년 n월'),
                'approvals' => $approvals,
                'recommendations' => $recommendations,
                'rejections' => $rejections,
                'total_actions' => $approvals + $rejections
            ];
        }

        return [
            'monthly_data' => $months,
            'peak_month' => $this->findPeakMonth($months),
            'average_monthly' => $this->calculateMonthlyAverage($months),
            'trend' => $this->calculateTrend($months)
        ];
    }

    /**
     * 최고 활동 월 찾기
     */
    private function findPeakMonth(array $months): array
    {
        $peakMonth = collect($months)->sortByDesc('total_actions')->first();
        return $peakMonth ?: ['month_name' => 'N/A', 'total_actions' => 0];
    }

    /**
     * 월 평균 계산
     */
    private function calculateMonthlyAverage(array $months): array
    {
        $totalApprovals = collect($months)->sum('approvals');
        $totalRecommendations = collect($months)->sum('recommendations');
        $totalRejections = collect($months)->sum('rejections');
        $monthCount = count($months);

        return [
            'approvals' => $monthCount > 0 ? round($totalApprovals / $monthCount, 1) : 0,
            'recommendations' => $monthCount > 0 ? round($totalRecommendations / $monthCount, 1) : 0,
            'rejections' => $monthCount > 0 ? round($totalRejections / $monthCount, 1) : 0
        ];
    }

    /**
     * 트렌드 계산
     */
    private function calculateTrend(array $months): string
    {
        if (count($months) < 2) {
            return 'insufficient_data';
        }

        $recent3 = array_slice($months, -3);
        $previous3 = array_slice($months, -6, 3);

        $recentAvg = collect($recent3)->avg('total_actions');
        $previousAvg = collect($previous3)->avg('total_actions');

        if ($recentAvg > $previousAvg * 1.1) {
            return 'increasing';
        } elseif ($recentAvg < $previousAvg * 0.9) {
            return 'decreasing';
        } else {
            return 'stable';
        }
    }

    /**
     * 이력 데이터
     */
    private function getHistoricalData(PartnerUser $partner): array
    {
        $totalApprovals = PartnerApplication::where('approved_by_uuid', $partner->user_uuid)
            ->where('application_status', 'approved')
            ->count();

        $totalRejections = PartnerApplication::where('rejected_by_uuid', $partner->user_uuid)
            ->where('application_status', 'rejected')
            ->count();

        $totalRecommendations = PartnerApplication::whereJsonContains('referral_details->referrer_uuid', $partner->user_uuid)
            ->count();

        $successRate = ($totalApprovals + $totalRejections) > 0
            ? round(($totalApprovals / ($totalApprovals + $totalRejections)) * 100, 1)
            : 0;

        return [
            'total_approvals' => $totalApprovals,
            'total_rejections' => $totalRejections,
            'total_recommendations' => $totalRecommendations,
            'success_rate' => $successRate,
            'first_approval' => $this->getFirstApprovalDate($partner),
            'most_recent_activity' => $this->getMostRecentActivity($partner),
            'best_month' => $this->getBestMonth($partner)
        ];
    }

    /**
     * 첫 승인 날짜
     */
    private function getFirstApprovalDate(PartnerUser $partner): ?string
    {
        $firstApproval = PartnerApplication::where('approved_by_uuid', $partner->user_uuid)
            ->where('application_status', 'approved')
            ->orderBy('approval_date', 'asc')
            ->first();

        return $firstApproval ? $firstApproval->approval_date->format('Y-m-d') : null;
    }

    /**
     * 최근 활동
     */
    private function getMostRecentActivity(PartnerUser $partner): ?array
    {
        $recentActivity = PartnerApplication::where(function ($query) use ($partner) {
            $query->where('approved_by_uuid', $partner->user_uuid)
                ->orWhere('rejected_by_uuid', $partner->user_uuid);
        })
        ->whereIn('application_status', ['approved', 'rejected'])
        ->orderBy('updated_at', 'desc')
        ->first();

        if (!$recentActivity) {
            return null;
        }

        return [
            'action' => $recentActivity->application_status,
            'date' => $recentActivity->updated_at->format('Y-m-d H:i'),
            'days_ago' => $recentActivity->updated_at->diffInDays(now())
        ];
    }

    /**
     * 최고 성과 월
     */
    private function getBestMonth(PartnerUser $partner): ?array
    {
        // SQLite 호환 방식으로 수정
        $bestMonth = PartnerApplication::where('approved_by_uuid', $partner->user_uuid)
            ->where('application_status', 'approved')
            ->selectRaw("strftime('%Y', approval_date) as year, strftime('%m', approval_date) as month, COUNT(*) as count")
            ->groupBy('year', 'month')
            ->orderBy('count', 'desc')
            ->first();

        if (!$bestMonth) {
            return null;
        }

        return [
            'year' => $bestMonth->year,
            'month' => $bestMonth->month,
            'count' => $bestMonth->count,
            'month_name' => date('Y년 n월', mktime(0, 0, 0, $bestMonth->month, 1, $bestMonth->year))
        ];
    }

    /**
     * 등급 진행 상황
     */
    private function getTierProgression(PartnerUser $partner): array
    {
        $currentTier = $partner->tier_name ?? 'Bronze';
        $tiers = ['Bronze', 'Silver', 'Gold', 'Platinum'];
        $currentIndex = array_search($currentTier, $tiers);

        return [
            'current_tier' => $currentTier,
            'current_index' => $currentIndex,
            'next_tier' => $currentIndex < count($tiers) - 1 ? $tiers[$currentIndex + 1] : null,
            'progress_percentage' => $this->calculateTierProgress($partner),
            'tiers_available' => $tiers,
            'tier_descriptions' => $this->getTierDescriptions()
        ];
    }

    /**
     * 등급 진행률 계산
     */
    private function calculateTierProgress(PartnerUser $partner): float
    {
        // 임시 구현: 활동 기반 진행률
        $totalActivities = PartnerApplication::where(function ($query) use ($partner) {
            $query->where('approved_by_uuid', $partner->user_uuid)
                ->orWhere('rejected_by_uuid', $partner->user_uuid)
                ->orWhereJsonContains('referral_details->referrer_uuid', $partner->user_uuid);
        })->count();

        $currentTier = $partner->tier_name ?? 'Bronze';
        $requirementsMap = [
            'Bronze' => 0,
            'Silver' => 5,
            'Gold' => 20,
            'Platinum' => 50
        ];

        $nextTier = $this->getNextTier($currentTier);
        if (!$nextTier) {
            return 100.0; // 최고 등급
        }

        $required = $requirementsMap[$nextTier] ?? 0;
        return $required > 0 ? min(100, round(($totalActivities / $required) * 100, 1)) : 100;
    }

    /**
     * 다음 등급 반환
     */
    private function getNextTier(string $currentTier): ?string
    {
        $tiers = ['Bronze' => 'Silver', 'Silver' => 'Gold', 'Gold' => 'Platinum'];
        return $tiers[$currentTier] ?? null;
    }

    /**
     * 등급 설명
     */
    private function getTierDescriptions(): array
    {
        return [
            'Bronze' => '기본 파트너 등급 - 추천 및 기본 활동',
            'Silver' => 'Bronze 승인 권한, 월 2명 승인 가능',
            'Gold' => 'Bronze/Silver 승인 권한, 월 5명 승인 가능',
            'Platinum' => '전체 등급 승인 권한, 월 15명 승인 가능'
        ];
    }

    /**
     * 추천사항 생성
     */
    private function generateRecommendations(PartnerUser $partner): array
    {
        $recommendations = [];
        $limitsData = $this->getApprovalLimits($partner);
        $capacityData = $this->getManagementCapacity($partner);

        // 용량 기반 추천
        if ($capacityData['capacity_warning']) {
            $recommendations[] = [
                'type' => 'capacity_warning',
                'title' => '관리 용량 경고',
                'message' => '관리 용량이 80%를 초과했습니다. 등급 상승을 고려해보세요.',
                'priority' => 'high'
            ];
        }

        // 한도 사용률 기반 추천
        if ($limitsData['approval_percentage'] >= 90) {
            $recommendations[] = [
                'type' => 'limit_warning',
                'title' => '월간 한도 경고',
                'message' => '월간 승인 한도를 거의 사용했습니다.',
                'priority' => 'medium'
            ];
        }

        // 등급 상승 추천
        $tierProgress = $this->calculateTierProgress($partner);
        if ($tierProgress >= 70) {
            $nextTier = $this->getNextTier($partner->tier_name ?? 'Bronze');
            if ($nextTier) {
                $recommendations[] = [
                    'type' => 'tier_upgrade',
                    'title' => '등급 상승 가능',
                    'message' => "{$nextTier} 등급 상승 조건을 {$tierProgress}% 달성했습니다.",
                    'priority' => 'low'
                ];
            }
        }

        // 활동 부족 알림
        if ($limitsData['current_month_approvals'] === 0 && now()->day > 15) {
            $recommendations[] = [
                'type' => 'activity_low',
                'title' => '활동 부족',
                'message' => '이번 달 승인 활동이 없습니다. 적극적인 참여를 권장합니다.',
                'priority' => 'low'
            ];
        }

        return $recommendations;
    }

    /**
     * 등급 상승 요구사항
     */
    private function getUpgradeRequirements(PartnerUser $partner): array
    {
        $currentTier = $partner->tier_name ?? 'Bronze';
        $nextTier = $this->getNextTier($currentTier);

        if (!$nextTier) {
            return [
                'available' => false,
                'message' => '최고 등급에 도달했습니다.'
            ];
        }

        $requirements = [
            'Silver' => [
                'min_approvals' => 5,
                'min_success_rate' => 70,
                'min_active_days' => 30,
                'description' => 'Bronze 파트너 5명 이상 승인, 성공률 70% 이상'
            ],
            'Gold' => [
                'min_approvals' => 20,
                'min_success_rate' => 75,
                'min_managed_partners' => 3,
                'min_active_days' => 90,
                'description' => 'Silver 이상 파트너 20명 이상 승인, 하위 파트너 3명 이상 관리'
            ],
            'Platinum' => [
                'min_approvals' => 50,
                'min_success_rate' => 80,
                'min_managed_partners' => 10,
                'min_active_days' => 180,
                'description' => 'Gold 이상 파트너 50명 이상 승인, 하위 파트너 10명 이상 관리'
            ]
        ];

        $requirement = $requirements[$nextTier] ?? [];
        if (empty($requirement)) {
            return ['available' => false, 'message' => '알 수 없는 등급입니다.'];
        }

        // 현재 진행률 계산
        $currentApprovals = PartnerApplication::where('approved_by_uuid', $partner->user_uuid)
            ->where('application_status', 'approved')
            ->count();

        $currentManaged = PartnerUser::where('referrer_uuid', $partner->user_uuid)
            ->where('status', 'active')
            ->count();

        $activeDays = $partner->joined_at ? now()->diffInDays($partner->joined_at) : 0;

        return [
            'available' => true,
            'target_tier' => $nextTier,
            'requirements' => $requirement,
            'current_progress' => [
                'approvals' => $currentApprovals,
                'managed_partners' => $currentManaged,
                'active_days' => $activeDays,
                'success_rate' => $this->calculateCurrentSuccessRate($partner)
            ],
            'completion_percentage' => $this->calculateUpgradeProgress($partner, $requirement)
        ];
    }

    /**
     * 현재 성공률 계산
     */
    private function calculateCurrentSuccessRate(PartnerUser $partner): float
    {
        $approvals = PartnerApplication::where('approved_by_uuid', $partner->user_uuid)
            ->where('application_status', 'approved')
            ->count();

        $rejections = PartnerApplication::where('rejected_by_uuid', $partner->user_uuid)
            ->where('application_status', 'rejected')
            ->count();

        $total = $approvals + $rejections;
        return $total > 0 ? round(($approvals / $total) * 100, 1) : 0;
    }

    /**
     * 등급 상승 진행률 계산
     */
    private function calculateUpgradeProgress(PartnerUser $partner, array $requirement): float
    {
        $scores = [];

        // 승인 수 진행률
        if (isset($requirement['min_approvals'])) {
            $currentApprovals = PartnerApplication::where('approved_by_uuid', $partner->user_uuid)
                ->where('application_status', 'approved')
                ->count();
            $scores[] = min(100, ($currentApprovals / $requirement['min_approvals']) * 100);
        }

        // 관리 파트너 수 진행률
        if (isset($requirement['min_managed_partners'])) {
            $currentManaged = PartnerUser::where('referrer_uuid', $partner->user_uuid)
                ->where('status', 'active')
                ->count();
            $scores[] = min(100, ($currentManaged / $requirement['min_managed_partners']) * 100);
        }

        // 활동 일수 진행률
        if (isset($requirement['min_active_days'])) {
            $activeDays = $partner->joined_at ? now()->diffInDays($partner->joined_at) : 0;
            $scores[] = min(100, ($activeDays / $requirement['min_active_days']) * 100);
        }

        // 성공률 진행률
        if (isset($requirement['min_success_rate'])) {
            $currentSuccessRate = $this->calculateCurrentSuccessRate($partner);
            $scores[] = min(100, ($currentSuccessRate / $requirement['min_success_rate']) * 100);
        }

        return empty($scores) ? 0 : round(array_sum($scores) / count($scores), 1);
    }
}