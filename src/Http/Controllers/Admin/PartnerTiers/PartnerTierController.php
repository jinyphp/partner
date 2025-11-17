<?php

namespace Jiny\Partner\Http\Controllers\Admin;

use Jiny\Partner\Models\PartnerTier;
use Illuminate\Http\Request;

class PartnerTierController extends BaseAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->model = PartnerTier::class;
        $this->viewPath = 'jiny-partner::admin.partner-tiers';
        $this->routePrefix = 'partner-tiers';
        $this->title = '파트너 등급';
    }

    protected function getValidationRules($item = null): array
    {
        $tierCodeRule = 'required|string|max:20';
        if ($item) {
            $tierCodeRule .= '|unique:partner_tiers,tier_code,' . $item->id;
        } else {
            $tierCodeRule .= '|unique:partner_tiers,tier_code';
        }

        return [
            'tier_code' => $tierCodeRule,
            'tier_name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'priority_level' => 'required|integer|min:1',

            // 새로운 승수 시스템 필드
            'sales_target_multiplier' => 'required|numeric|min:0.1|max:10',
            'cases_target_multiplier' => 'required|numeric|min:0.1|max:10',
            'quality_expectation_multiplier' => 'required|numeric|min:0.1|max:5',
            'client_target_multiplier' => 'required|numeric|min:0.1|max:10',

            // 성과 기준
            'min_achievement_rate' => 'required|numeric|min:0|max:200',
            'consecutive_months_required' => 'required|integer|min:1|max:12',

            // 등급 관리
            'max_team_size' => 'nullable|integer|min:0|max:100',
            'team_performance_weight' => 'nullable|numeric|min:0|max:1',

            // 자동 조정 설정
            'auto_demotion_enabled' => 'boolean',
            'demotion_grace_months' => 'nullable|integer|min:1|max:12',

            // JSON 필드
            'requirements' => 'nullable|json',
            'benefits' => 'nullable|json',
            'tier_benefits' => 'nullable|json',
            'advancement_criteria' => 'nullable|json',

            // 기존 필드
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
            'min_completed_jobs' => 'nullable|integer|min:0',
            'min_rating' => 'nullable|numeric|min:0|max:5',
            'min_punctuality_rate' => 'nullable|numeric|min:0|max:100',
            'min_satisfaction_rate' => 'nullable|numeric|min:0|max:100'
        ];
    }

    protected function getSearchFields(): array
    {
        return ['tier_code', 'tier_name', 'description'];
    }

    public function index(Request $request)
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

        $items = $query->orderBy('priority_level')->paginate(20);

        return view("{$this->viewPath}.index", [
            'items' => $items,
            'title' => $this->title,
            'routePrefix' => $this->routePrefix,
            'searchValue' => $request->search,
            'selectedIsActive' => $request->is_active
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->getValidationRules());

        // JSON 필드 처리
        if ($request->has('requirements') && is_string($request->requirements)) {
            $validated['requirements'] = json_decode($request->requirements, true);
        }
        if ($request->has('benefits') && is_string($request->benefits)) {
            $validated['benefits'] = json_decode($request->benefits, true);
        }

        $item = $this->model::create($validated);

        return redirect()->route("admin.{$this->routePrefix}.show", $item->id)
            ->with('success', $this->title . ' 항목이 성공적으로 생성되었습니다.');
    }

    public function update(Request $request, $id)
    {
        $item = $this->model::findOrFail($id);
        $validated = $request->validate($this->getValidationRules($item));

        // JSON 필드 처리
        if ($request->has('requirements') && is_string($request->requirements)) {
            $validated['requirements'] = json_decode($request->requirements, true);
        }
        if ($request->has('benefits') && is_string($request->benefits)) {
            $validated['benefits'] = json_decode($request->benefits, true);
        }

        $item->update($validated);

        return redirect()->route("admin.{$this->routePrefix}.show", $item->id)
            ->with('success', $this->title . ' 항목이 성공적으로 수정되었습니다.');
    }

    /**
     * 등급별 파트너 통계
     */
    public function statistics()
    {
        $stats = [];
        $tiers = $this->model::active()->orderByPriority()->get();

        foreach ($tiers as $tier) {
            $stats[] = [
                'tier' => $tier,
                'partner_count' => $tier->partnerEngineers()->count(),
                'active_partners' => $tier->partnerEngineers()->active()->count(),
                'avg_rating' => $tier->partnerEngineers()->avg('average_rating'),
                'total_earnings' => $tier->partnerEngineers()->sum('total_earnings')
            ];
        }

        return view("{$this->viewPath}.statistics", [
            'stats' => $stats,
            'title' => '등급별 통계'
        ]);
    }

    /**
     * 등급 승급 대상 파트너 조회
     */
    public function eligibleForUpgrade($tierCode)
    {
        $currentTier = $this->model::where('tier_code', $tierCode)->firstOrFail();
        $nextTier = $this->model::where('priority_level', '<', $currentTier->priority_level)
                                 ->orderByDesc('priority_level')
                                 ->first();

        if (!$nextTier) {
            return redirect()->back()->with('info', '최고 등급입니다.');
        }

        $eligiblePartners = $currentTier->partnerEngineers()
            ->where('total_completed_jobs', '>=', $nextTier->min_completed_jobs)
            ->where('average_rating', '>=', $nextTier->min_rating)
            ->where('punctuality_rate', '>=', $nextTier->min_punctuality_rate)
            ->where('customer_satisfaction', '>=', $nextTier->min_satisfaction_rate)
            ->with('user')
            ->get();

        return view("{$this->viewPath}.eligible-upgrades", [
            'currentTier' => $currentTier,
            'nextTier' => $nextTier,
            'eligiblePartners' => $eligiblePartners,
            'title' => '승급 대상 파트너'
        ]);
    }

    /**
     * 파트너 등급 일괄 승급
     */
    public function bulkUpgrade(Request $request)
    {
        $request->validate([
            'partner_ids' => 'required|array',
            'partner_ids.*' => 'exists:partner_engineers,id',
            'new_tier' => 'required|exists:partner_tiers,tier_code'
        ]);

        $upgraded = 0;
        foreach ($request->partner_ids as $partnerId) {
            $partner = \Jiny\Partner\Models\PartnerEngineer::find($partnerId);
            if ($partner) {
                $partner->update(['current_tier' => $request->new_tier]);
                $upgraded++;
            }
        }

        return redirect()->back()->with('success', "{$upgraded}명의 파트너가 성공적으로 승급되었습니다.");
    }
}