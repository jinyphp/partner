<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerDynamicTargets;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerDynamicTarget;

class EditController extends Controller
{
    public function edit(PartnerDynamicTarget $target)
    {
        $target->load(['partnerUser', 'partnerUser.partnerType', 'partnerUser.partnerTier']);

        return view('jiny-partner::admin.partner-dynamic-targets.edit', [
            'title' => '동적 목표 수정',
            'routePrefix' => 'partner.targets',
            'item' => $target,
        ]);
    }
}