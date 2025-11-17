<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerDynamicTargets;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerDynamicTarget;

class DestroyController extends Controller
{
    public function destroy(PartnerDynamicTarget $target)
    {
        $target->delete();

        return redirect()->route('admin.partner.targets.index')
            ->with('success', '동적 목표가 성공적으로 삭제되었습니다.');
    }
}