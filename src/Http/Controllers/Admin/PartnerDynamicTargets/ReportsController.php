<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerDynamicTargets;

use App\Http\Controllers\Controller;

class ReportsController extends Controller
{
    public function dashboard()
    {
        return view('jiny-partner::admin.partner-dynamic-targets.reports.dashboard', [
            'title' => '동적 목표 대시보드',
        ]);
    }
}