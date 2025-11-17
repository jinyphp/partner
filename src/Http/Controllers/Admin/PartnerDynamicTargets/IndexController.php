<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerDynamicTargets;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerDynamicTarget;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    protected $model;
    protected $viewPath;
    protected $routePrefix;
    protected $title;

    public function __construct()
    {
        $this->model = PartnerDynamicTarget::class;
        $this->viewPath = 'jiny-partner::admin.partner-dynamic-targets';
        $this->routePrefix = 'partner.targets';
        $this->title = '동적 목표 관리';
    }

    /**
     * 동적 목표 목록
     */
    public function index(Request $request)
    {
        $query = $this->model::query()->with(['partnerUser', 'partnerUser.partnerType', 'partnerUser.partnerTier']);

        // 파트너별 필터링
        if ($request->filled('partner_id')) {
            $query->where('partner_user_id', $request->partner_id);
        }

        // 기간별 필터링
        if ($request->filled('period_type')) {
            $query->where('target_period_type', $request->period_type);
        }

        // 상태별 필터링
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 연도별 필터링
        if ($request->filled('year')) {
            $query->where('target_year', $request->year);
        }

        // 검색
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('partnerUser', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('target_year', 'desc')
                      ->orderBy('target_month', 'desc')
                      ->orderBy('target_quarter', 'desc')
                      ->paginate(20);

        // 통계 정보
        $statistics = [
            'total_targets' => $this->model::count(),
            'active_targets' => $this->model::where('status', 'active')->count(),
            'completed_targets' => $this->model::where('status', 'completed')->count(),
            'pending_targets' => $this->model::where('status', 'pending_approval')->count(),
        ];

        return view("{$this->viewPath}.index", [
            'title' => $this->title,
            'routePrefix' => $this->routePrefix,
            'items' => $items,
            'statistics' => $statistics,
            'searchValue' => $request->search,
            'selectedPartnerId' => $request->partner_id,
            'selectedPeriodType' => $request->period_type,
            'selectedStatus' => $request->status,
            'selectedYear' => $request->year,
        ]);
    }
}