<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerDynamicTargets;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerDynamicTarget;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Models\PartnerType;
use Jiny\Partner\Models\PartnerTier;
use Illuminate\Http\Request;

class PartnerDynamicTargetController extends Controller
{
    protected $model;
    protected $viewPath;
    protected $routePrefix;
    protected $title;

    public function __construct()
    {
        $this->model = PartnerDynamicTarget::class;
        $this->viewPath = 'jiny-partner::admin.partner-dynamic-targets';
        $this->routePrefix = 'partner-dynamic-targets';
        $this->title = '파트너 동적 목표';
    }

    /**
     * 목표 목록 표시
     */
    public function index(Request $request)
    {
        $query = $this->model::query()
            ->with(['partnerUser.user', 'partnerUser.partnerType', 'partnerUser.partnerTier']);

        // 검색 필터
        if ($request->has('search') && $request->search) {
            $query->whereHas('partnerUser.user', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        // 상태 필터
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // 기간 유형 필터
        if ($request->has('period_type') && $request->period_type) {
            $query->where('target_period_type', $request->period_type);
        }

        // 연도 필터
        if ($request->has('year') && $request->year) {
            $query->where('target_year', $request->year);
        }

        // 월 필터 (월별인 경우)
        if ($request->has('month') && $request->month) {
            $query->where('target_month', $request->month);
        }

        // 분기 필터 (분기별인 경우)
        if ($request->has('quarter') && $request->quarter) {
            $query->where('target_quarter', $request->quarter);
        }

        $items = $query->orderByDesc('created_at')->paginate(20);

        return view("{$this->viewPath}.index", [
            'items' => $items,
            'title' => $this->title,
            'routePrefix' => $this->routePrefix,
            'searchValue' => $request->search,
            'statusOptions' => $this->getStatusOptions(),
            'periodOptions' => $this->getPeriodOptions(),
            'selectedStatus' => $request->status,
            'selectedPeriodType' => $request->period_type,
            'selectedYear' => $request->year,
            'selectedMonth' => $request->month,
            'selectedQuarter' => $request->quarter
        ]);
    }

    /**
     * 목표 생성 폼 표시
     */
    public function create(Request $request)
    {
        $partners = PartnerUser::with(['user', 'partnerType', 'partnerTier'])
            ->where('is_active', true)->get();

        $partnerTypes = PartnerType::where('is_active', true)->get();
        $partnerTiers = PartnerTier::where('is_active', true)
            ->orderBy('priority_level')->get();

        // 선택된 파트너가 있는 경우 사전 정보 계산
        $selectedPartnerId = $request->get('partner_id');
        $calculatedTargets = null;

        if ($selectedPartnerId) {
            $calculatedTargets = $this->calculateBaseTargets($selectedPartnerId,
                $request->get('period_type', 'monthly'),
                $request->get('year', date('Y')),
                $request->get('month', date('n')),
                $request->get('quarter', ceil(date('n')/3))
            );
        }

        return view("{$this->viewPath}.create", [
            'title' => $this->title . ' 생성',
            'routePrefix' => $this->routePrefix,
            'partners' => $partners,
            'partnerTypes' => $partnerTypes,
            'partnerTiers' => $partnerTiers,
            'selectedPartnerId' => $selectedPartnerId,
            'calculatedTargets' => $calculatedTargets,
            'periodOptions' => $this->getPeriodOptions()
        ]);
    }

    /**
     * 목표 생성 처리
     */
    public function store(Request $request)
    {
        $validated = $request->validate($this->getValidationRules());

        // 기본 목표 계산
        $calculatedTargets = $this->calculateBaseTargets(
            $validated['partner_user_id'],
            $validated['target_period_type'],
            $validated['target_year'],
            $validated['target_month'] ?? null,
            $validated['target_quarter'] ?? null
        );

        // 계산된 값과 조정 계수를 적용하여 최종 목표 설정
        $validated = array_merge($validated, $calculatedTargets);

        $this->applyAdjustmentFactors($validated);
        $this->calculateFinalTargets($validated);

        // JSON 필드 처리
        $this->processJsonFields($validated, $request);

        // 생성자 정보 추가
        $validated['created_by'] = auth()->id();
        $validated['status'] = 'draft';

        $item = $this->model::create($validated);

        return redirect()->route("admin.{$this->routePrefix}.show", $item->id)
            ->with('success', $this->title . ' 항목이 성공적으로 생성되었습니다.');
    }

    /**
     * 목표 상세 표시
     */
    public function show($id)
    {
        $item = $this->model::with([
            'partnerUser.user',
            'partnerUser.partnerType',
            'partnerUser.partnerTier',
            'createdBy',
            'approvedBy'
        ])->findOrFail($id);

        return view("{$this->viewPath}.show", [
            'item' => $item,
            'title' => $this->title . ' 상세',
            'routePrefix' => $this->routePrefix
        ]);
    }

    /**
     * 목표 수정 폼 표시
     */
    public function edit($id)
    {
        $item = $this->model::findOrFail($id);

        $partners = PartnerUser::with(['user', 'partnerType', 'partnerTier'])
            ->where('is_active', true)->get();

        return view("{$this->viewPath}.edit", [
            'item' => $item,
            'title' => $this->title . ' 수정',
            'routePrefix' => $this->routePrefix,
            'partners' => $partners,
            'periodOptions' => $this->getPeriodOptions()
        ]);
    }

    /**
     * 목표 수정 처리
     */
    public function update(Request $request, $id)
    {
        $item = $this->model::findOrFail($id);
        $validated = $request->validate($this->getValidationRules($item));

        // 기존 기본 목표 값 유지하거나 재계산
        if ($request->has('recalculate_base') && $request->recalculate_base) {
            $calculatedTargets = $this->calculateBaseTargets(
                $validated['partner_user_id'],
                $validated['target_period_type'],
                $validated['target_year'],
                $validated['target_month'] ?? null,
                $validated['target_quarter'] ?? null
            );
            $validated = array_merge($validated, $calculatedTargets);
        }

        $this->applyAdjustmentFactors($validated);
        $this->calculateFinalTargets($validated);

        // JSON 필드 처리
        $this->processJsonFields($validated, $request);

        // 수정자 정보 추가
        $validated['updated_by'] = auth()->id();

        $item->update($validated);

        return redirect()->route("admin.{$this->routePrefix}.show", $item->id)
            ->with('success', $this->title . ' 항목이 성공적으로 수정되었습니다.');
    }

    /**
     * 목표 승인
     */
    public function approve(Request $request, $id)
    {
        $item = $this->model::findOrFail($id);

        $validated = $request->validate([
            'approval_notes' => 'nullable|string'
        ]);

        $item->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'approval_notes' => $validated['approval_notes'] ?? null
        ]);

        return redirect()->route("admin.{$this->routePrefix}.show", $item->id)
            ->with('success', '목표가 승인되었습니다.');
    }

    /**
     * 목표 활성화
     */
    public function activate($id)
    {
        $item = $this->model::findOrFail($id);

        if ($item->status !== 'approved') {
            return redirect()->back()->with('error', '승인된 목표만 활성화할 수 있습니다.');
        }

        $item->update([
            'status' => 'active',
            'activated_at' => now()
        ]);

        return redirect()->route("admin.{$this->routePrefix}.show", $item->id)
            ->with('success', '목표가 활성화되었습니다.');
    }

    /**
     * 성과 업데이트 (자동/수동)
     */
    public function updatePerformance(Request $request, $id)
    {
        $item = $this->model::findOrFail($id);

        $validated = $request->validate([
            'current_sales_achievement' => 'required|numeric|min:0',
            'current_cases_achievement' => 'required|integer|min:0',
            'current_revenue_achievement' => 'required|numeric|min:0',
            'current_clients_achievement' => 'required|integer|min:0'
        ]);

        // 달성률 자동 계산
        $this->calculateAchievementRates($validated, $item);
        $this->calculateBonuses($validated, $item);

        $validated['last_calculated_at'] = now();

        $item->update($validated);

        return redirect()->route("admin.{$this->routePrefix}.show", $item->id)
            ->with('success', '성과가 업데이트되었습니다.');
    }

    /**
     * 기본 목표 계산 (타입별 최소 기준치 × 등급별 승수)
     */
    private function calculateBaseTargets($partnerId, $periodType, $year, $month = null, $quarter = null)
    {
        $partner = PartnerUser::with(['partnerType', 'partnerTier'])->findOrFail($partnerId);
        $type = $partner->partnerType;
        $tier = $partner->partnerTier;

        $baseTargets = [
            'base_sales_target' => $type->min_baseline_sales * $tier->sales_target_multiplier,
            'base_cases_target' => $type->min_baseline_cases * $tier->cases_target_multiplier,
            'base_revenue_target' => $type->min_baseline_revenue * $tier->sales_target_multiplier,
            'base_clients_target' => $type->min_baseline_clients * $tier->client_target_multiplier
        ];

        return $baseTargets;
    }

    /**
     * 조정 계수 적용
     */
    private function applyAdjustmentFactors(&$validated)
    {
        // 기본값 설정
        $validated['personal_adjustment_factor'] = $validated['personal_adjustment_factor'] ?? 1.0;
        $validated['market_condition_factor'] = $validated['market_condition_factor'] ?? 1.0;
        $validated['seasonal_adjustment_factor'] = $validated['seasonal_adjustment_factor'] ?? 1.0;
        $validated['team_performance_factor'] = $validated['team_performance_factor'] ?? 1.0;
    }

    /**
     * 최종 목표 계산
     */
    private function calculateFinalTargets(&$validated)
    {
        $combinedFactor = $validated['personal_adjustment_factor']
                        * $validated['market_condition_factor']
                        * $validated['seasonal_adjustment_factor']
                        * $validated['team_performance_factor'];

        $validated['final_sales_target'] = $validated['base_sales_target'] * $combinedFactor;
        $validated['final_cases_target'] = $validated['base_cases_target'] * $combinedFactor;
        $validated['final_revenue_target'] = $validated['base_revenue_target'] * $combinedFactor;
        $validated['final_clients_target'] = $validated['base_clients_target'] * $combinedFactor;
    }

    /**
     * 달성률 계산
     */
    private function calculateAchievementRates(&$validated, $item)
    {
        $validated['sales_achievement_rate'] = $item->final_sales_target > 0
            ? ($validated['current_sales_achievement'] / $item->final_sales_target) * 100
            : 0;

        $validated['cases_achievement_rate'] = $item->final_cases_target > 0
            ? ($validated['current_cases_achievement'] / $item->final_cases_target) * 100
            : 0;

        // 종합 달성률 (매출 70%, 건수 30% 가중 평균)
        $validated['overall_achievement_rate'] = ($validated['sales_achievement_rate'] * 0.7)
                                               + ($validated['cases_achievement_rate'] * 0.3);
    }

    /**
     * 보너스 계산
     */
    private function calculateBonuses(&$validated, $item)
    {
        $bonusConfig = json_decode($item->bonus_tier_config, true) ?? [];
        $achievementRate = $validated['overall_achievement_rate'];

        $bonusRate = 0;
        foreach ($bonusConfig as $threshold => $config) {
            if ($achievementRate >= $threshold) {
                $bonusRate = $config['rate'] ?? 0;
                break;
            }
        }

        $validated['achieved_bonus_rate'] = $bonusRate;
        $validated['calculated_bonus_amount'] = $validated['current_sales_achievement'] * ($bonusRate / 100);
    }

    /**
     * JSON 필드 처리
     */
    private function processJsonFields(&$validated, $request)
    {
        $jsonFields = ['bonus_tier_config', 'special_objectives', 'achievement_milestones'];

        foreach ($jsonFields as $field) {
            if ($request->has($field) && is_string($request->$field)) {
                $validated[$field] = json_decode($request->$field, true);
            }
        }
    }

    /**
     * 유효성 검증 규칙
     */
    protected function getValidationRules($item = null): array
    {
        $periodRule = 'required|in:monthly,quarterly,yearly';

        return [
            'partner_user_id' => 'required|exists:partner_users,id',
            'target_period_type' => $periodRule,
            'target_year' => 'required|integer|min:2020|max:2030',
            'target_month' => 'nullable|integer|min:1|max:12',
            'target_quarter' => 'nullable|integer|min:1|max:4',

            // 기본 목표 (자동 계산되지만 수동 조정 가능)
            'base_sales_target' => 'nullable|numeric|min:0',
            'base_cases_target' => 'nullable|integer|min:0',
            'base_revenue_target' => 'nullable|numeric|min:0',
            'base_clients_target' => 'nullable|integer|min:0',

            // 조정 계수
            'personal_adjustment_factor' => 'nullable|numeric|min:0.1|max:3.0',
            'market_condition_factor' => 'nullable|numeric|min:0.1|max:3.0',
            'seasonal_adjustment_factor' => 'nullable|numeric|min:0.1|max:3.0',
            'team_performance_factor' => 'nullable|numeric|min:0.1|max:3.0',

            // 추가 목표
            'quality_score_target' => 'nullable|numeric|min:0|max:100',
            'customer_satisfaction_target' => 'nullable|numeric|min:0|max:100',
            'response_time_target' => 'nullable|numeric|min:0',

            // JSON 필드
            'bonus_tier_config' => 'nullable|json',
            'special_objectives' => 'nullable|json',
            'achievement_milestones' => 'nullable|json',

            // 메모
            'setting_notes' => 'nullable|string',
            'approval_notes' => 'nullable|string',

            // 자동 계산 설정
            'auto_calculate_enabled' => 'boolean',
            'next_review_date' => 'nullable|date|after:today'
        ];
    }

    /**
     * 상태 옵션
     */
    private function getStatusOptions()
    {
        return [
            'draft' => '초안',
            'pending_approval' => '승인대기',
            'approved' => '승인완료',
            'active' => '활성',
            'completed' => '완료',
            'cancelled' => '취소'
        ];
    }

    /**
     * 기간 옵션
     */
    private function getPeriodOptions()
    {
        return [
            'monthly' => '월별',
            'quarterly' => '분기별',
            'yearly' => '연별'
        ];
    }

    /**
     * 목표 삭제
     */
    public function destroy($id)
    {
        $item = $this->model::findOrFail($id);

        if (in_array($item->status, ['active', 'completed'])) {
            return redirect()->back()->with('error', '활성 또는 완료된 목표는 삭제할 수 없습니다.');
        }

        $item->delete();

        return redirect()->route("admin.{$this->routePrefix}.index")
            ->with('success', $this->title . ' 항목이 삭제되었습니다.');
    }
}