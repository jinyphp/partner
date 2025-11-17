<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerDynamicTargets;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerDynamicTarget;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    protected $model;
    protected $routePrefix;
    protected $title;

    public function __construct()
    {
        $this->model = PartnerDynamicTarget::class;
        $this->routePrefix = 'partner.targets';
        $this->title = '동적 목표';
    }

    /**
     * 동적 목표 저장
     */
    public function store(Request $request)
    {
        $validated = $request->validate($this->getValidationRules());

        // JSON 필드 처리
        if ($request->has('bonus_tier_config')) {
            $validated['bonus_tier_config'] = json_decode($request->bonus_tier_config, true);
        }
        if ($request->has('special_objectives')) {
            $validated['special_objectives'] = json_decode($request->special_objectives, true);
        }

        // 생성자 정보 추가
        $validated['created_by'] = auth()->id();
        $validated['status'] = 'draft'; // 초기 상태는 초안

        $item = $this->model::create($validated);

        return redirect()->route("admin.{$this->routePrefix}.show", $item->id)
            ->with('success', $this->title . ' 항목이 성공적으로 생성되었습니다.');
    }

    protected function getValidationRules(): array
    {
        return [
            'partner_user_id' => 'required|exists:partner_users,id',
            'target_period_type' => 'required|in:monthly,quarterly,yearly',
            'target_year' => 'required|integer|min:2020|max:2030',
            'target_month' => 'nullable|integer|min:1|max:12',
            'target_quarter' => 'nullable|integer|min:1|max:4',
            'base_sales_target' => 'nullable|numeric|min:0',
            'base_cases_target' => 'nullable|integer|min:0',
            'base_revenue_target' => 'nullable|numeric|min:0',
            'base_clients_target' => 'nullable|integer|min:0',
            'personal_adjustment_factor' => 'nullable|numeric|min:0.1|max:5.0',
            'market_condition_factor' => 'nullable|numeric|min:0.1|max:5.0',
            'seasonal_adjustment_factor' => 'nullable|numeric|min:0.1|max:5.0',
            'team_performance_factor' => 'nullable|numeric|min:0.1|max:5.0',
            'quality_score_target' => 'nullable|numeric|min:0|max:100',
            'customer_satisfaction_target' => 'nullable|numeric|min:0|max:100',
            'response_time_target' => 'nullable|numeric|min:0',
            'bonus_tier_config' => 'nullable|json',
            'special_objectives' => 'nullable|json',
            'setting_notes' => 'nullable|string',
            'auto_calculate_enabled' => 'boolean',
            'next_review_date' => 'nullable|date|after:today',
        ];
    }
}