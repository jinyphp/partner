<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerUsers;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Models\PartnerDynamicTarget;

class ShowController extends Controller
{
    protected $model;
    protected $viewPath;
    protected $routePrefix;
    protected $title;

    public function __construct()
    {
        $this->model = PartnerUser::class;
        $this->viewPath = 'jiny-partner::admin.partner-users';
        $this->routePrefix = 'partner.users';
        $this->title = '파트너 회원';
    }

    /**
     * 파트너 회원 상세 조회
     */
    public function __invoke($id)
    {
        $item = $this->model::with(['partnerTier', 'partnerType', 'creator', 'updater', 'parent', 'children'])->findOrFail($id);

        // 샤딩된 테이블에서 사용자 정보 조회
        $shardedUserInfo = $item->getUserFromShardedTable();

        // 성과 분석 데이터
        $performanceAnalysis = $this->getPerformanceAnalysis($item);

        // 등급 승급 가능성 체크
        $upgradeAnalysis = $this->getUpgradeAnalysis($item);

        // 동적 목표 데이터 조회
        $dynamicTargets = $this->getDynamicTargets($item);

        // 현재 활성 목표
        $activeTarget = $this->getActiveTarget($item);

        // 목표 추이 분석
        $targetTrendAnalysis = $this->getTargetTrendAnalysis($item);

        // 다음 목표 추천
        $recommendedNextTarget = $this->getRecommendedNextTarget($item);

        return view("{$this->viewPath}.show", [
            'item' => $item,
            'title' => $this->title,
            'routePrefix' => $this->routePrefix,
            'shardedUserInfo' => $shardedUserInfo,
            'performanceAnalysis' => $performanceAnalysis,
            'upgradeAnalysis' => $upgradeAnalysis,
            'dynamicTargets' => $dynamicTargets,
            'activeTarget' => $activeTarget,
            'targetTrendAnalysis' => $targetTrendAnalysis,
            'recommendedNextTarget' => $recommendedNextTarget
        ]);
    }

    /**
     * 성과 분석 데이터 생성
     */
    protected function getPerformanceAnalysis($item): array
    {
        $allPartners = PartnerUser::where('status', 'active');

        return [
            'performance_rank' => [
                'rating_rank' => $allPartners->where('average_rating', '>', $item->average_rating)->count() + 1,
                'jobs_rank' => $allPartners->where('total_completed_jobs', '>', $item->total_completed_jobs)->count() + 1,
                'punctuality_rank' => $allPartners->where('punctuality_rate', '>', $item->punctuality_rate)->count() + 1,
                'satisfaction_rank' => $allPartners->where('satisfaction_rate', '>', $item->satisfaction_rate)->count() + 1,
                'total_active_partners' => $allPartners->count()
            ],
            'tier_average' => [
                'avg_rating' => round(PartnerUser::where('partner_tier_id', $item->partner_tier_id)
                    ->where('status', 'active')->avg('average_rating') ?? 0, 2),
                'avg_jobs' => round(PartnerUser::where('partner_tier_id', $item->partner_tier_id)
                    ->where('status', 'active')->avg('total_completed_jobs') ?? 0),
                'avg_punctuality' => round(PartnerUser::where('partner_tier_id', $item->partner_tier_id)
                    ->where('status', 'active')->avg('punctuality_rate') ?? 0, 2),
                'avg_satisfaction' => round(PartnerUser::where('partner_tier_id', $item->partner_tier_id)
                    ->where('status', 'active')->avg('satisfaction_rate') ?? 0, 2)
            ],
            'activity_metrics' => [
                'days_since_joined' => $item->partner_joined_at ? $item->partner_joined_at->diffInDays(now()) : 0,
                'jobs_per_month' => $this->calculateJobsPerMonth($item),
                'last_review_days' => $item->last_performance_review_at
                    ? $item->last_performance_review_at->diffInDays(now())
                    : null
            ]
        ];
    }

    /**
     * 승급 분석 데이터 생성
     */
    protected function getUpgradeAnalysis($item): array
    {
        // 현재 등급보다 높은 등급들 조회
        $higherTiers = \Jiny\Partner\Models\PartnerTier::where('priority_level', '<', $item->partnerTier->priority_level)
            ->where('is_active', true)
            ->orderBy('priority_level', 'desc')
            ->get();

        $upgradeOptions = [];

        foreach ($higherTiers as $tier) {
            $canUpgrade = $item->canUpgradeToTier($tier);
            $requirements = [];

            // 요구사항 체크
            if ($item->total_completed_jobs < $tier->min_completed_jobs) {
                $requirements[] = '완료 작업 수: ' . ($tier->min_completed_jobs - $item->total_completed_jobs) . '개 더 필요';
            }

            if ($item->average_rating < $tier->min_rating) {
                $requirements[] = '평점: ' . ($tier->min_rating - $item->average_rating) . '점 더 필요';
            }

            if ($item->punctuality_rate < $tier->min_punctuality_rate) {
                $requirements[] = '시간 준수율: ' . ($tier->min_punctuality_rate - $item->punctuality_rate) . '% 더 필요';
            }

            if ($item->satisfaction_rate < $tier->min_satisfaction_rate) {
                $requirements[] = '만족도: ' . ($tier->min_satisfaction_rate - $item->satisfaction_rate) . '% 더 필요';
            }

            $upgradeOptions[] = [
                'tier' => $tier,
                'can_upgrade' => $canUpgrade,
                'missing_requirements' => $requirements,
                'progress_percentage' => $this->calculateUpgradeProgress($item, $tier)
            ];
        }

        return [
            'next_tier' => $higherTiers->first(),
            'all_upgrade_options' => $upgradeOptions,
            'is_top_tier' => $higherTiers->isEmpty()
        ];
    }

    /**
     * 승급 진행률 계산
     */
    protected function calculateUpgradeProgress($item, $tier): float
    {
        $scores = [];

        // Division by zero 방지를 위한 안전한 계산
        if ($tier->min_completed_jobs > 0) {
            $scores[] = min(100, ($item->total_completed_jobs / $tier->min_completed_jobs) * 100);
        } else {
            $scores[] = 100; // 최소 요구사항이 0이면 100%로 간주
        }

        if ($tier->min_rating > 0) {
            $scores[] = min(100, ($item->average_rating / $tier->min_rating) * 100);
        } else {
            $scores[] = 100;
        }

        if ($tier->min_punctuality_rate > 0) {
            $scores[] = min(100, ($item->punctuality_rate / $tier->min_punctuality_rate) * 100);
        } else {
            $scores[] = 100;
        }

        if ($tier->min_satisfaction_rate > 0) {
            $scores[] = min(100, ($item->satisfaction_rate / $tier->min_satisfaction_rate) * 100);
        } else {
            $scores[] = 100;
        }

        // 점수가 있을 때만 평균 계산
        return count($scores) > 0 ? round(array_sum($scores) / count($scores), 1) : 0;
    }

    /**
     * 월별 작업 수를 안전하게 계산
     */
    protected function calculateJobsPerMonth($item): float
    {
        if (!$item->partner_joined_at) {
            return 0;
        }

        $monthsSinceJoined = $item->partner_joined_at->diffInMonths(now());

        if ($monthsSinceJoined <= 0) {
            // 가입한 지 1개월 미만인 경우, 총 작업 수를 반환
            return (float) $item->total_completed_jobs;
        }

        return round($item->total_completed_jobs / $monthsSinceJoined, 1);
    }

    /**
     * 동적 목표 데이터 조회
     */
    protected function getDynamicTargets($item): array
    {
        $currentYear = date('Y');
        $currentMonth = date('n');
        $currentQuarter = ceil($currentMonth / 3);

        // 최근 6개월간의 목표 데이터
        $recentTargets = PartnerDynamicTarget::where('partner_user_id', $item->id)
            ->where('target_year', '>=', $currentYear - 1)
            ->orderByDesc('target_year')
            ->orderByDesc('target_month')
            ->orderByDesc('target_quarter')
            ->with('createdBy', 'approvedBy')
            ->limit(10)
            ->get();

        return [
            'recent_targets' => $recentTargets,
            'current_month_target' => PartnerDynamicTarget::where('partner_user_id', $item->id)
                ->where('target_period_type', 'monthly')
                ->where('target_year', $currentYear)
                ->where('target_month', $currentMonth)
                ->first(),
            'current_quarter_target' => PartnerDynamicTarget::where('partner_user_id', $item->id)
                ->where('target_period_type', 'quarterly')
                ->where('target_year', $currentYear)
                ->where('target_quarter', $currentQuarter)
                ->first(),
            'current_year_target' => PartnerDynamicTarget::where('partner_user_id', $item->id)
                ->where('target_period_type', 'yearly')
                ->where('target_year', $currentYear)
                ->first()
        ];
    }

    /**
     * 현재 활성 목표 조회
     */
    protected function getActiveTarget($item)
    {
        return PartnerDynamicTarget::where('partner_user_id', $item->id)
            ->where('status', 'active')
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * 목표 달성 추이 분석
     */
    protected function getTargetTrendAnalysis($item): array
    {
        $targets = PartnerDynamicTarget::where('partner_user_id', $item->id)
            ->whereIn('status', ['completed', 'active'])
            ->where('target_period_type', 'monthly')
            ->where('target_year', '>=', date('Y') - 1)
            ->orderBy('target_year')
            ->orderBy('target_month')
            ->get();

        $trendData = [];
        foreach ($targets as $target) {
            $trendData[] = [
                'period' => $target->period_display,
                'sales_achievement_rate' => $target->sales_achievement_rate,
                'overall_achievement_rate' => $target->overall_achievement_rate,
                'final_sales_target' => $target->final_sales_target,
                'current_sales_achievement' => $target->current_sales_achievement,
                'calculated_bonus_amount' => $target->calculated_bonus_amount
            ];
        }

        return [
            'trend_data' => $trendData,
            'avg_achievement_rate' => $targets->avg('overall_achievement_rate'),
            'best_month' => $targets->sortByDesc('overall_achievement_rate')->first(),
            'total_bonus_earned' => $targets->sum('calculated_bonus_amount')
        ];
    }

    /**
     * 다음 목표 추천 계산
     */
    protected function getRecommendedNextTarget($item): array
    {
        $type = $item->partnerType;
        $tier = $item->partnerTier;

        if (!$type || !$tier) {
            return [];
        }

        // 최근 성과 기반으로 조정 계수 추천
        $recentTargets = PartnerDynamicTarget::where('partner_user_id', $item->id)
            ->whereIn('status', ['completed', 'active'])
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        $avgAchievement = $recentTargets->avg('overall_achievement_rate');

        // 성과에 따른 조정 계수 추천
        $recommendedFactor = 1.0;
        if ($avgAchievement > 120) {
            $recommendedFactor = 1.2; // 성과가 좋으면 목표 증가
        } elseif ($avgAchievement < 80) {
            $recommendedFactor = 0.9; // 성과가 낮으면 목표 감소
        }

        $baseSalesTarget = $type->min_baseline_sales * $tier->sales_target_multiplier;
        $baseCasesTarget = $type->min_baseline_cases * $tier->cases_target_multiplier;

        return [
            'recommended_adjustment_factor' => $recommendedFactor,
            'recommended_sales_target' => $baseSalesTarget * $recommendedFactor,
            'recommended_cases_target' => $baseCasesTarget * $recommendedFactor,
            'reasoning' => $this->getRecommendationReasoning($avgAchievement, $recommendedFactor),
            'recent_avg_achievement' => round($avgAchievement, 1)
        ];
    }

    /**
     * 추천 이유 텍스트 생성
     */
    protected function getRecommendationReasoning($avgAchievement, $factor): string
    {
        if ($avgAchievement > 120) {
            return "최근 3개월 평균 달성률이 {$avgAchievement}%로 우수하여 목표를 {$factor}배 상향 조정을 추천합니다.";
        } elseif ($avgAchievement < 80) {
            return "최근 3개월 평균 달성률이 {$avgAchievement}%로 개선이 필요하여 목표를 {$factor}배 하향 조정을 추천합니다.";
        } else {
            return "최근 3개월 평균 달성률이 {$avgAchievement}%로 안정적이므로 현재 수준 유지를 추천합니다.";
        }
    }

    protected function getValidationRules($item = null): array
    {
        return [];
    }
}