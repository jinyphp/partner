<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerTiers;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerTier;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;

class IndexController extends Controller
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
     * 파트너 등급 목록 조회
     */
    public function __invoke(Request $request)
    {
        $query = $this->model::query();

        // 검색 기능
        if ($request->has('search') && $request->search) {
            $searchFields = $this->getSearchFields();
            $query->where(function($q) use ($request, $searchFields) {
                foreach ($searchFields as $field) {
                    $q->orWhere($field, 'like', '%' . $request->search . '%');
                }
            });
        }

        // 활성 상태 필터
        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', (bool)$request->is_active);
        }

        $items = $query->with(['parentTier', 'childTiers'])
                       ->orderBy('priority_level')
                       ->orderBy('display_order')
                       ->paginate(20);

        // 각 등급별 파트너 수 계산
        $partnerCounts = PartnerUser::selectRaw('partner_tier_id, count(*) as partner_count')
            ->whereIn('partner_tier_id', $items->pluck('id'))
            ->groupBy('partner_tier_id')
            ->pluck('partner_count', 'partner_tier_id');

        // 전체 통계 계산
        $totalPartners = PartnerUser::count();
        $activePartners = PartnerUser::where('status', 'active')->count();

        return view("{$this->viewPath}.index", [
            'items' => $items,
            'title' => $this->title,
            'routePrefix' => $this->routePrefix,
            'searchValue' => $request->search,
            'selectedIsActive' => $request->is_active,
            'partnerCounts' => $partnerCounts,
            'totalPartners' => $totalPartners,
            'activePartners' => $activePartners
        ]);
    }

    protected function getSearchFields(): array
    {
        return ['tier_code', 'tier_name', 'description'];
    }

    protected function getValidationRules($item = null): array
    {
        return [];
    }
}