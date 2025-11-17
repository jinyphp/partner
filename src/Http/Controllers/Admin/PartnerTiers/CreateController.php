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
        return view("{$this->viewPath}.create", [
            'title' => $this->title,
            'routePrefix' => $this->routePrefix
        ]);
    }

    protected function getValidationRules($item = null): array
    {
        return [];
    }
}