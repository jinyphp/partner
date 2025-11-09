<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerTiers;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerTier;
use Illuminate\Http\Request;

class UpdateController extends Controller
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
     * 파트너 등급 수정
     */
    public function __invoke(Request $request, $id)
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

        // Checkbox 필드 처리
        $validated['can_recruit'] = $request->has('can_recruit') ? true : false;
        $validated['cost_management_enabled'] = $request->has('cost_management_enabled') ? true : false;
        $validated['is_active'] = $request->has('is_active') ? true : false;

        $item->update($validated);

        return redirect()->route("admin.{$this->routePrefix}.show", $item->id)
            ->with('success', $this->title . ' 항목이 성공적으로 수정되었습니다.');
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