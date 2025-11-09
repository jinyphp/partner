<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerTiers;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerTier;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StoreController extends Controller
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
     * 파트너 등급 생성
     */
    public function __invoke(Request $request)
    {
        // 계층구조 및 수수료 검증
        $this->validateHierarchyAndCommission($request);

        $validated = $request->validate($this->getValidationRules());

        // tier_code 자동 생성
        if (empty($validated['tier_code'])) {
            $validated['tier_code'] = $this->generateTierCode($validated['tier_name']);
        }

        // display_order 자동 설정
        if (empty($validated['display_order'])) {
            $validated['display_order'] = $this->getNextDisplayOrder($validated['priority_level']);
        }

        // JSON 필드 처리
        if ($request->has('requirements') && is_string($request->requirements)) {
            $validated['requirements'] = json_decode($request->requirements, true);
        }
        if ($request->has('benefits') && is_string($request->benefits)) {
            $validated['benefits'] = json_decode($request->benefits, true);
        }

        // Checkbox 필드 처리
        $validated['can_recruit'] = $request->has('can_recruit') ? true : false;
        $validated['cost_management_enabled'] = $request->has('cost_management_enabled') ? true : false;
        $validated['is_active'] = $request->has('is_active') ? true : false;

        // 수수료 타입별 처리
        if ($validated['commission_type'] === 'percentage') {
            $validated['commission_amount'] = null;
        } else {
            $validated['commission_rate'] = null;
        }

        $item = $this->model::create($validated);

        // 등급 순서 재정렬
        $this->model::reorderTiers();

        return redirect()->route("admin.{$this->routePrefix}.show", $item->id)
            ->with('success', $this->title . ' 항목이 성공적으로 생성되었습니다.');
    }

    /**
     * 계층구조 및 수수료 검증
     */
    protected function validateHierarchyAndCommission(Request $request)
    {
        // 상위 등급이 있는 경우 수수료율 검증
        if ($request->parent_tier_id && $request->commission_type === 'percentage') {
            $parentTier = $this->model::find($request->parent_tier_id);
            if ($parentTier) {
                $maxAllowed = max(0, ($parentTier->commission_rate ?? 0) - 0.5);
                if ($request->commission_rate > $maxAllowed) {
                    throw ValidationException::withMessages([
                        'commission_rate' => "수수료율은 상위 등급({$parentTier->tier_name})의 {$maxAllowed}%를 초과할 수 없습니다."
                    ]);
                }
            }
        }

        // 우선순위 중복 검사
        if ($request->priority_level) {
            $existingTier = $this->model::where('priority_level', $request->priority_level)->first();
            if ($existingTier) {
                throw ValidationException::withMessages([
                    'priority_level' => "우선순위 {$request->priority_level}은(는) 이미 '{$existingTier->tier_name}' 등급에서 사용 중입니다."
                ]);
            }
        }
    }

    /**
     * 등급 코드 자동 생성
     */
    protected function generateTierCode($tierName)
    {
        $code = strtolower($tierName);
        $code = preg_replace('/[^a-z0-9가-힣\s-]/u', '', $code);
        $code = preg_replace('/\s+/', '_', $code);
        $code = trim($code, '_');

        // 중복 검사 및 번호 추가
        $originalCode = $code;
        $counter = 1;
        while ($this->model::where('tier_code', $code)->exists()) {
            $code = $originalCode . '_' . $counter;
            $counter++;
        }

        return $code;
    }

    /**
     * 다음 표시 순서 계산
     */
    protected function getNextDisplayOrder($priorityLevel)
    {
        $lastOrder = $this->model::where('priority_level', $priorityLevel)
            ->max('display_order');

        return ($lastOrder ?? ($priorityLevel * 10)) + 10;
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
            'tier_code' => 'nullable|string|max:20|unique:partner_tiers,tier_code' . ($item ? ',' . $item->id : ''),
            'tier_name' => 'required|string|max:100|unique:partner_tiers,tier_name' . ($item ? ',' . $item->id : ''),
            'description' => 'nullable|string',

            // 계층구조 및 우선순위
            'parent_tier_id' => 'nullable|exists:partner_tiers,id',
            'priority_level' => 'required|integer|min:1|max:999',
            'display_order' => 'nullable|integer|min:1|max:999',

            // 수수료 설정
            'commission_type' => 'required|in:percentage,fixed_amount',
            'commission_rate' => 'nullable|numeric|min:0|max:100|required_if:commission_type,percentage',
            'commission_amount' => 'nullable|numeric|min:0|required_if:commission_type,fixed_amount',
            'inherit_parent_commission' => 'boolean',
            'max_commission_rate' => 'nullable|numeric|min:0|max:100',

            'requirements' => 'nullable|json',
            'benefits' => 'nullable|json',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
            'min_completed_jobs' => 'nullable|integer|min:0',
            'min_rating' => 'nullable|numeric|min:0|max:5',
            'min_punctuality_rate' => 'nullable|numeric|min:0|max:100',
            'min_satisfaction_rate' => 'nullable|numeric|min:0|max:100',

            // 계층 관리 필드
            'can_recruit' => 'boolean',
            'max_children' => 'nullable|integer|min:0|max:999',
            'max_depth' => 'nullable|integer|min:1|max:20',

            // 비용 관리 필드
            'cost_management_enabled' => 'boolean',
            'registration_fee' => 'nullable|numeric|min:0',
            'activation_fee' => 'nullable|numeric|min:0',
            'upgrade_fee' => 'nullable|numeric|min:0',
            'monthly_maintenance_fee' => 'nullable|numeric|min:0',
            'annual_maintenance_fee' => 'nullable|numeric|min:0',
            'renewal_fee' => 'nullable|numeric|min:0',
            'service_fee_rate' => 'nullable|numeric|min:0|max:100',
            'platform_fee_rate' => 'nullable|numeric|min:0|max:100',
            'transaction_fee_rate' => 'nullable|numeric|min:0|max:100',
            'security_deposit' => 'nullable|numeric|min:0',
            'performance_bond' => 'nullable|numeric|min:0',
            'early_payment_discount_rate' => 'nullable|numeric|min:0|max:100',
            'loyalty_discount_rate' => 'nullable|numeric|min:0|max:100',
            'volume_discount_rate' => 'nullable|numeric|min:0|max:100'
        ];
    }
}