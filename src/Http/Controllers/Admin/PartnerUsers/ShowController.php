<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerUsers;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerUser;

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
        $item = $this->model::with(['partnerTier', 'creator', 'updater', 'parent', 'children'])->findOrFail($id);

        // 샤딩된 테이블에서 사용자 정보 조회
        $shardedUserInfo = $item->getUserFromShardedTable();

        // 성과 분석 데이터
        $performanceAnalysis = $this->getPerformanceAnalysis($item);

        // 등급 승급 가능성 체크
        $upgradeAnalysis = $this->getUpgradeAnalysis($item);

        return view("{$this->viewPath}.show", [
            'item' => $item,
            'title' => $this->title,
            'routePrefix' => $this->routePrefix,
            'shardedUserInfo' => $shardedUserInfo,
            'performanceAnalysis' => $performanceAnalysis,
            'upgradeAnalysis' => $upgradeAnalysis
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

    protected function getValidationRules($item = null): array
    {
        return [];
    }
}