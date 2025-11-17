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

        // 수수료 타입 필터
        if ($request->has('commission_type') && $request->commission_type) {
            $query->where('commission_type', $request->commission_type);
        }

        $items = $query
                       ->orderBy('priority_level')
                       ->orderBy('sort_order')
                       ->paginate(20);

        // 각 등급별 파트너 수 계산 (실제 연결 관계에 맞게 수정)
        $partnerCounts = [];
        foreach ($items as $item) {
            $partnerCounts[$item->id] = $item->partnerUsers()->count();
        }

        // 전체 통계 계산
        $totalPartners = PartnerUser::count();
        $activePartners = PartnerUser::where('is_active', true)->count();

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