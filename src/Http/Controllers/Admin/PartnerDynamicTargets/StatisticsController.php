<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerDynamicTargets;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerDynamicTarget;

class StatisticsController extends Controller
{
    public function index()
    {
        // 통계 데이터 수집 (추후 구현)
        $statistics = [
            'total_targets' => PartnerDynamicTarget::count(),
            'active_targets' => PartnerDynamicTarget::where('status', 'active')->count(),
            'completed_targets' => PartnerDynamicTarget::where('status', 'completed')->count(),
            'pending_targets' => PartnerDynamicTarget::where('status', 'pending_approval')->count(),
        ];

        return view('jiny-partner::admin.partner-dynamic-targets.statistics', [
            'title' => '동적 목표 통계',
            'statistics' => $statistics,
        ]);
    }
}