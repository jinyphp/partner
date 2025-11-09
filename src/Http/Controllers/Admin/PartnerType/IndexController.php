<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerType;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerType;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    protected $model;
    protected $viewPath;
    protected $routePrefix;
    protected $title;

    public function __construct()
    {
        $this->model = PartnerType::class;
        $this->viewPath = 'jiny-partner::admin.partner-type';
        $this->routePrefix = 'partner.type';
        $this->title = '파트너 타입';
    }

    /**
     * 파트너 타입 목록
     */
    public function __invoke(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $search = $request->get('search');
        $isActive = $request->get('is_active');

        $query = $this->model::query()
            ->with(['creator', 'updater'])
            ->withCount('partners');

        // 검색 처리
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('type_code', 'like', "%{$search}%")
                  ->orWhere('type_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // 활성 상태 필터
        if ($isActive !== null && $isActive !== '') {
            $query->where('is_active', (bool) $isActive);
        }

        $items = $query->ordered()->paginate($perPage);

        // 통계 정보
        $statistics = [
            'total_types' => $this->model::count(),
            'active_types' => $this->model::active()->count(),
            'inactive_types' => $this->model::where('is_active', false)->count(),
            'total_partners' => $this->model::withCount('partners')->get()->sum('partners_count')
        ];

        return view($this->viewPath . '.index', [
            'items' => $items,
            'statistics' => $statistics,
            'title' => $this->title,
            'routePrefix' => $this->routePrefix,
            'searchValue' => $search,
            'selectedIsActive' => $isActive,
            'perPage' => $perPage
        ]);
    }
}