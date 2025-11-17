<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerDynamicTargets;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerDynamicTarget;

class StatusController extends Controller
{
    public function activate(PartnerDynamicTarget $target)
    {
        $target->update([
            'status' => 'active',
            'activated_at' => now(),
        ]);

        return redirect()->back()->with('success', '동적 목표가 활성화되었습니다.');
    }

    public function complete(PartnerDynamicTarget $target)
    {
        $target->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return redirect()->back()->with('success', '동적 목표가 완료되었습니다.');
    }

    public function cancel(PartnerDynamicTarget $target)
    {
        $target->update([
            'status' => 'cancelled',
        ]);

        return redirect()->back()->with('success', '동적 목표가 취소되었습니다.');
    }
}