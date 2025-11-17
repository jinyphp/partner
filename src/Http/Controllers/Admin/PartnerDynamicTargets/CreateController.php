<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerDynamicTargets;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerDynamicTarget;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;

class CreateController extends Controller
{
    protected $model;
    protected $viewPath;
    protected $routePrefix;
    protected $title;

    public function __construct()
    {
        $this->model = PartnerDynamicTarget::class;
        $this->viewPath = 'jiny-partner::admin.partner-dynamic-targets';
        $this->routePrefix = 'partner.targets';
        $this->title = '동적 목표 생성';
    }

    /**
     * 동적 목표 생성 폼
     */
    public function create(Request $request)
    {
        // 파트너 목록 가져오기
        $partners = PartnerUser::with(['partnerType', 'partnerTier'])
                              ->where('status', 'active')
                              ->orderBy('name')
                              ->get();

        // 미리 선택된 파트너 ID
        $selectedPartnerId = $request->partner_id;

        return view("{$this->viewPath}.create", [
            'title' => $this->title,
            'routePrefix' => $this->routePrefix,
            'partners' => $partners,
            'selectedPartnerId' => $selectedPartnerId,
        ]);
    }
}