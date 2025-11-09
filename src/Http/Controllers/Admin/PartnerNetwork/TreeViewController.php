<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerNetwork;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Models\PartnerTier;
use Illuminate\Http\Request;

class TreeViewController extends Controller
{
    /**
     * 파트너 네트워크 트리 구조 조회
     */
    public function __invoke(Request $request)
    {
        $rootId = $request->get('root_id');
        $maxDepth = $request->get('max_depth', 5);
        $showInactive = $request->get('show_inactive', false);

        // 루트 파트너 결정
        if ($rootId) {
            $rootPartner = PartnerUser::findOrFail($rootId);
            $tree = $this->buildPartnerTree($rootPartner, $maxDepth, $showInactive);
        } else {
            // 최상위 파트너들 (parent_id가 null)
            $rootPartners = PartnerUser::with(['partnerTier', 'children'])
                ->roots()
                ->when(!$showInactive, function($query) {
                    $query->where('status', 'active');
                })
                ->orderBy('created_at')
                ->get();

            $tree = $rootPartners->map(function($partner) use ($maxDepth, $showInactive) {
                return $this->buildPartnerTree($partner, $maxDepth, $showInactive);
            });
        }

        // 통계 데이터
        $statistics = $this->getNetworkStatistics($rootId);

        return view('jiny-partner::admin.partner-network.tree', [
            'tree' => $tree,
            'statistics' => $statistics,
            'rootPartner' => $rootPartner ?? null,
            'maxDepth' => $maxDepth,
            'showInactive' => $showInactive,
            'availableTiers' => PartnerTier::active()->orderByPriority()->get(),
            'pageTitle' => '파트너 네트워크 트리'
        ]);
    }

    /**
     * 파트너 트리 구조 재귀적 생성
     */
    private function buildPartnerTree(PartnerUser $partner, $maxDepth, $showInactive, $currentDepth = 0)
    {
        if ($currentDepth >= $maxDepth) {
            return null;
        }

        $children = $partner->children()
            ->with(['partnerTier', 'children'])
            ->when(!$showInactive, function($query) {
                $query->where('status', 'active');
            })
            ->orderBy('created_at')
            ->get();

        $childrenData = $children->map(function($child) use ($maxDepth, $showInactive, $currentDepth) {
            return $this->buildPartnerTree($child, $maxDepth, $showInactive, $currentDepth + 1);
        })->filter();

        return [
            'partner' => $partner,
            'level' => $currentDepth,
            'children' => $childrenData,
            'children_count' => $children->count(),
            'total_descendants' => $partner->calculateTotalDescendants(),
            'tier' => $partner->partnerTier,
            'performance' => $this->getPartnerPerformance($partner),
            'network_stats' => $this->getPartnerNetworkStats($partner)
        ];
    }

    /**
     * 네트워크 전체 통계
     */
    private function getNetworkStatistics($rootId = null)
    {
        $query = PartnerUser::query();

        if ($rootId) {
            $query->descendantsOf($rootId);
        }

        $totalPartners = $query->count();
        $activePartners = $query->where('status', 'active')->count();
        $totalSales = $query->sum('monthly_sales');
        $totalTeamSales = $query->sum('team_sales');

        // 레벨별 분포
        $levelDistribution = $query
            ->selectRaw('level, COUNT(*) as count')
            ->groupBy('level')
            ->orderBy('level')
            ->pluck('count', 'level');

        // 티어별 분포
        $tierDistribution = $query
            ->join('partner_tiers', 'partner_users.partner_tier_id', '=', 'partner_tiers.id')
            ->selectRaw('partner_tiers.tier_name, COUNT(*) as count')
            ->groupBy('partner_tiers.tier_name')
            ->orderBy('count', 'desc')
            ->pluck('count', 'tier_name');

        // 모집 활동 통계 (새로운 쿼리 인스턴스 사용)
        $baseQuery = PartnerUser::query();
        if ($rootId) {
            $baseQuery->descendantsOf($rootId);
        }

        $recruitmentStats = [
            'total_recruiters' => $baseQuery->canRecruit()->count(),
            'total_relationships' => \Jiny\Partner\Models\PartnerNetworkRelationship::active()->count(),
            'this_month_recruits' => \Jiny\Partner\Models\PartnerNetworkRelationship::active()
                ->whereMonth('recruited_at', now()->month)
                ->count()
        ];

        return [
            'total_partners' => $totalPartners,
            'active_partners' => $activePartners,
            'inactive_partners' => $totalPartners - $activePartners,
            'total_sales' => $totalSales,
            'total_team_sales' => $totalTeamSales,
            'average_team_size' => $totalPartners > 0 ? round($query->sum('children_count') / $totalPartners, 1) : 0,
            'level_distribution' => $levelDistribution,
            'tier_distribution' => $tierDistribution,
            'recruitment_stats' => $recruitmentStats
        ];
    }

    /**
     * 개별 파트너 성과 데이터
     */
    private function getPartnerPerformance(PartnerUser $partner)
    {
        return [
            'monthly_sales' => $partner->monthly_sales,
            'team_sales' => $partner->team_sales,
            'children_count' => $partner->children_count,
            'total_children_count' => $partner->total_children_count,
            'commission_earned' => $partner->earned_commissions,
            'recruitment_rate' => $this->calculateRecruitmentRate($partner),
            'performance_score' => $this->calculatePerformanceScore($partner)
        ];
    }

    /**
     * 파트너 네트워크 통계
     */
    private function getPartnerNetworkStats(PartnerUser $partner)
    {
        $relationships = $partner->networkRelationships()->active()->get();

        return [
            'direct_recruits' => $relationships->where('depth', 1)->count(),
            'total_network_size' => $relationships->count(),
            'network_sales' => $relationships->sum('total_generated_sales'),
            'network_commissions' => $relationships->sum('total_commissions_paid'),
            'active_relationships' => $relationships->where('is_active', true)->count(),
            'recruitment_this_month' => $relationships->filter(function($rel) {
                return $rel->recruited_at->isCurrentMonth();
            })->count()
        ];
    }

    /**
     * 모집 성공률 계산
     */
    private function calculateRecruitmentRate(PartnerUser $partner)
    {
        $totalRecruits = $partner->networkRelationships()->count();
        $activeRecruits = $partner->networkRelationships()->active()->count();

        return $totalRecruits > 0 ? round(($activeRecruits / $totalRecruits) * 100, 1) : 0;
    }

    /**
     * 성과 점수 계산
     */
    private function calculatePerformanceScore(PartnerUser $partner)
    {
        $score = 0;

        // 매출 성과 (40점)
        if ($partner->monthly_sales >= 5000000) $score += 40;
        elseif ($partner->monthly_sales >= 1000000) $score += 30;
        elseif ($partner->monthly_sales >= 500000) $score += 20;
        elseif ($partner->monthly_sales >= 100000) $score += 10;

        // 팀 빌딩 성과 (30점)
        if ($partner->children_count >= 20) $score += 30;
        elseif ($partner->children_count >= 10) $score += 20;
        elseif ($partner->children_count >= 5) $score += 15;
        elseif ($partner->children_count >= 1) $score += 10;

        // 활동성 (30점)
        $lastActivity = $partner->last_activity_at;
        if ($lastActivity && $lastActivity->diffInDays() <= 7) $score += 30;
        elseif ($lastActivity && $lastActivity->diffInDays() <= 30) $score += 20;
        elseif ($lastActivity && $lastActivity->diffInDays() <= 90) $score += 10;

        return min($score, 100);
    }
}