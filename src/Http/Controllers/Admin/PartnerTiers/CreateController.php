<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerTiers;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerTier;

class CreateController extends Controller
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
     * 파트너 등급 생성 폼 표시
     */
    public function __invoke()
    {
        // 상위 등급으로 선택 가능한 등급들 조회
        $availableParentTiers = $this->model::active()
            ->orderBy('priority_level')
            ->orderBy('display_order')
            ->get();

        return view("{$this->viewPath}.create", [
            'title' => $this->title,
            'routePrefix' => $this->routePrefix,
            'availableParentTiers' => $availableParentTiers
        ]);
    }

    protected function getValidationRules($item = null): array
    {
        return [];
    }
}