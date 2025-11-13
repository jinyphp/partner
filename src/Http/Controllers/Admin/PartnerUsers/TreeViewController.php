<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerUsers;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;

class TreeViewController extends Controller
{
    protected $viewPath;
    protected $routePrefix;
    protected $title;

    public function __construct()
    {
        $this->viewPath = 'jiny-partner::admin.partner-users';
        $this->routePrefix = 'partner.users';
        $this->title = '파트너 계층구조';
    }

    /**
     * 파트너 계층구조 트리 보기
     */
    public function __invoke(Request $request, PartnerUser $user)
    {
        // 로드할 관계들
        $user->load(['parent', 'children', 'partnerTier', 'partnerType']);

        // 직계 상위 파트너 (1개만)
        $directParent = $user->parent;

        // 모든 상위 파트너들 (조상들)
        $ancestors = $user->getAncestors();

        // 모든 하위 파트너들 (재귀적으로)
        $descendants = $this->getDescendantsTree($user);

        // 트리 구조 통계
        $treeStats = $this->getTreeStatistics($user);

        return view("{$this->viewPath}.tree", [
            'user' => $user,
            'directParent' => $directParent,
            'ancestors' => $ancestors,
            'descendants' => $descendants,
            'treeStats' => $treeStats,
            'title' => $this->title . ' - ' . $user->name,
            'routePrefix' => $this->routePrefix
        ]);
    }

    /**
     * 하위 파트너들의 트리 구조 가져오기
     */
    protected function getDescendantsTree(PartnerUser $user, $depth = 0)
    {
        // 직접 하위 파트너들만 조회
        $children = $user->children()
            ->with(['partnerTier', 'partnerType'])
            ->orderBy('name')
            ->get();

        $tree = [];

        foreach ($children as $child) {
            $tree[] = [
                'user' => $child,
                'depth' => $depth + 1,
                'children' => $this->getDescendantsTree($child, $depth + 1), // 재귀적으로 하위 파트너들 조회
                'stats' => [
                    'direct_children' => $child->children_count,
                    'total_descendants' => $child->total_children_count,
                    'monthly_sales' => $child->monthly_sales,
                    'earned_commissions' => $child->earned_commissions
                ]
            ];
        }

        return $tree;
    }

    /**
     * 트리 구조 통계 계산
     */
    protected function getTreeStatistics(PartnerUser $user)
    {
        return [
            'user_level' => $user->level,
            'direct_children' => $user->children_count,
            'total_descendants' => $user->total_children_count,
            'max_depth' => $this->calculateMaxDepth($user),
            'team_sales' => $user->team_sales,
            'personal_sales' => $user->monthly_sales,
            'total_commissions' => $user->earned_commissions,
            'can_recruit' => $user->can_recruit,
            'recruitment_limit' => $user->max_children,
        ];
    }

    /**
     * 최대 깊이 계산
     */
    protected function calculateMaxDepth(PartnerUser $user)
    {
        $maxDepth = $user->level;

        // 모든 하위 파트너들의 최대 레벨 조회
        $descendants = PartnerUser::where('tree_path', 'like', '%/' . $user->id . '/%')
            ->orWhere('tree_path', 'like', '%/' . $user->id)
            ->max('level');

        return $descendants ?: $maxDepth;
    }

    /**
     * 상위 계층 경로 가져오기 (브레드크럼 용)
     */
    protected function getAncestorPath(PartnerUser $user)
    {
        $ancestors = $user->getAncestors();
        return $ancestors->sortBy('level');
    }
}