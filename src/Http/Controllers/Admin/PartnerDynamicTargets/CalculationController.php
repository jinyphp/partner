<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerDynamicTargets;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerDynamicTarget;

class CalculationController extends Controller
{
    public function recalculate(PartnerDynamicTarget $target)
    {
        // 목표 재계산 로직 (추후 구현)
        $target->update([
            'last_calculated_at' => now(),
        ]);

        return redirect()->back()->with('success', '동적 목표가 재계산되었습니다.');
    }
}