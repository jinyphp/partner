<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerTiers;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerTier;
use Jiny\Partner\Models\PartnerUser;

class ShowController extends Controller
{
    protected $model;
    protected $viewPath;
    protected $routePrefix;
    protected $title;

    public function __construct()
    {
        $this->model = PartnerTier::class;
        $this->viewPath = 'jiny-partner::admin.partner-tiers';
        $this->routePrefix = 'partner.tiers';
        $this->title = '파트너 등급';
    }

    /**
     * 파트너 등급 상세 정보 표시
     */
    public function __invoke($id)
    {
        $item = $this->model::with(['partnerUsers'])->findOrFail($id);

        // 이 등급에 속한 파트너 통계
        $partnerStats = $this->getPartnerStatistics($item);

        // 전체 파트너 통계 (비교용)
        $totalPartners = PartnerUser::count();

        return view("{$this->viewPath}.show", [
            'item' => $item,
            'title' => $this->title,
            'routePrefix' => $this->routePrefix,
            'partnerStats' => $partnerStats,
            'totalPartners' => $totalPartners
        ]);
    }

    /**
     * 파트너 통계 계산
     */
    protected function getPartnerStatistics(PartnerTier $tier): array
    {
        $partnersQuery = PartnerUser::where('partner_tier_id', $tier->id);

        return [
            'total_count' => $partnersQuery->count(),
            'active_count' => $partnersQuery->where('status', 'active')->count(),
            'pending_count' => $partnersQuery->where('status', 'pending')->count(),
            'suspended_count' => $partnersQuery->where('status', 'suspended')->count(),
            'inactive_count' => $partnersQuery->where('status', 'inactive')->count(),

            // 성과 통계
            'avg_rating' => round($partnersQuery->avg('average_rating') ?? 0, 2),
            'avg_completed_jobs' => round($partnersQuery->avg('total_completed_jobs') ?? 0),
            'avg_punctuality' => round($partnersQuery->avg('punctuality_rate') ?? 0, 1),
            'avg_satisfaction' => round($partnersQuery->avg('satisfaction_rate') ?? 0, 1),

            // 매출 통계
            'total_sales' => $partnersQuery->sum('monthly_sales') ?? 0,
            'total_commissions' => $partnersQuery->sum('earned_commissions') ?? 0,
            'avg_monthly_sales' => round($partnersQuery->avg('monthly_sales') ?? 0),
            'avg_commissions' => round($partnersQuery->avg('earned_commissions') ?? 0),

            // 최근 가입자 수 (30일)
            'recent_joins' => $partnersQuery->where('created_at', '>=', now()->subDays(30))->count(),

            // 네트워크 통계
            'with_children' => $partnersQuery->where('direct_children_count', '>', 0)->count(),
            'total_children' => $partnersQuery->sum('direct_children_count') ?? 0,
            'total_descendants' => $partnersQuery->sum('total_descendants_count') ?? 0,
        ];
    }

    protected function getValidationRules($item = null): array
    {
        return [];
    }
}