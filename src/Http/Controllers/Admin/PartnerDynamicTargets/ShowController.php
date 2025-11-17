<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerDynamicTargets;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerDynamicTarget;

class ShowController extends Controller
{
    public function show(PartnerDynamicTarget $target)
    {
        $target->load(['partnerUser', 'partnerUser.partnerType', 'partnerUser.partnerTier']);

        return view('jiny-partner::admin.partner-dynamic-targets.show', [
            'title' => '동적 목표 상세',
            'routePrefix' => 'partner.targets',
            'item' => $target,
        ]);
    }
}