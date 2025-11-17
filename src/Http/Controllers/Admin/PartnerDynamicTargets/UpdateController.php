<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerDynamicTargets;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerDynamicTarget;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    public function update(Request $request, PartnerDynamicTarget $target)
    {
        $validated = $request->validate([
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
            'setting_notes' => 'nullable|string',
        ]);

        $target->update($validated);

        return redirect()->route('admin.partner.targets.show', $target->id)
            ->with('success', '동적 목표가 성공적으로 수정되었습니다.');
    }
}