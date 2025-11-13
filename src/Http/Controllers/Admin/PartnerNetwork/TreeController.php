<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerNetwork;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;

class TreeController extends Controller
{
    protected $viewPath;
    protected $routePrefix;
    protected $title;

    public function __construct()
    {
        $this->viewPath = 'jiny-partner::admin.partner-network';
        $this->routePrefix = 'admin.partner.network';
        $this->title = '파트너 네트워크 트리';
    }

    /**
     * 파트너 네트워크 트리 메인 페이지
     */
    public function index(Request $request)
    {
        $parentId = $request->get('parent');
        $breadcrumbs = [];
        $currentLevel = 0;

        // 최상위(루트) 파트너들 조회
        if (!$parentId) {
            $partners = PartnerUser::whereNull('parent_id')
                ->with(['partnerTier'])
                ->withCount(['children as children_count'])
                ->orderBy('name')
                ->get();

            $pageTitle = $this->title;
        } else {
            // 특정 파트너의 하위 파트너들 조회
            $parentPartner = PartnerUser::with(['partnerTier'])->findOrFail($parentId);
            $currentLevel = $parentPartner->level + 1;

            $partners = PartnerUser::where('parent_id', $parentId)
                ->with(['partnerTier'])
                ->withCount(['children as children_count'])
                ->orderBy('name')
                ->get();

            // 브레드크럼 생성
            $breadcrumbs = $this->buildBreadcrumbs($parentPartner);
            $pageTitle = $parentPartner->name . '의 하위 파트너';
        }

        // 네트워크 전체 통계
        $networkStats = $this->getNetworkStatistics();

        return view("{$this->viewPath}.tree", [
            'partners' => $partners,
            'parentId' => $parentId,
            'breadcrumbs' => $breadcrumbs,
            'currentLevel' => $currentLevel,
            'networkStats' => $networkStats,
            'pageTitle' => $pageTitle,
            'routePrefix' => $this->routePrefix
        ]);
    }

    /**
     * AJAX로 파트너 하위 목록 조회
     */
    public function children(Request $request, $partnerId)
    {
        $partner = PartnerUser::with(['partnerTier'])->findOrFail($partnerId);

        $children = PartnerUser::where('parent_id', $partnerId)
            ->with(['partnerTier'])
            ->withCount(['children as children_count'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'parent' => [
                    'id' => $partner->id,
                    'name' => $partner->name,
                    'level' => $partner->level,
                    'tier' => $partner->partnerTier->tier_name ?? 'Bronze'
                ],
                'children' => $children->map(function($child) {
                    return [
                        'id' => $child->id,
                        'name' => $child->name,
                        'email' => $child->email,
                        'level' => $child->level,
                        'tier' => $child->partnerTier->tier_name ?? 'Bronze',
                        'children_count' => $child->children_count,
                        'can_recruit' => $child->can_recruit,
                        'status' => $child->status
                    ];
                })
            ]
        ]);
    }

    /**
     * 브레드크럼 생성
     */
    private function buildBreadcrumbs(PartnerUser $partner)
    {
        $breadcrumbs = [];
        $ancestors = $partner->getAncestors();

        // 루트로 가는 링크
        $breadcrumbs[] = [
            'name' => '최상위',
            'url' => route($this->routePrefix . '.tree'),
            'level' => 0
        ];

        // 상위 파트너들
        foreach ($ancestors as $ancestor) {
            $breadcrumbs[] = [
                'name' => $ancestor->name,
                'url' => route($this->routePrefix . '.tree', ['parent' => $ancestor->id]),
                'level' => $ancestor->level
            ];
        }

        // 현재 파트너
        $breadcrumbs[] = [
            'name' => $partner->name,
            'url' => null, // 현재 페이지
            'level' => $partner->level
        ];

        return $breadcrumbs;
    }

    /**
     * 네트워크 전체 통계
     */
    private function getNetworkStatistics()
    {
        $totalPartners = PartnerUser::count();
        $rootPartners = PartnerUser::whereNull('parent_id')->count();
        $maxLevel = PartnerUser::max('level') ?? 0;
        $activePartners = PartnerUser::where('status', 'active')->count();

        return [
            'total_partners' => $totalPartners,
            'root_partners' => $rootPartners,
            'max_level' => $maxLevel,
            'active_partners' => $activePartners,
            'network_depth' => $maxLevel + 1
        ];
    }
}