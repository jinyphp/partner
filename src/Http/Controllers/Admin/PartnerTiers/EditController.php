<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerTiers;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerTier;

class EditController extends Controller
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
     * 파트너 등급 수정 폼 표시
     */
    public function __invoke($id)
    {
        $item = $this->model::findOrFail($id);

        return view("{$this->viewPath}.edit", [
            'item' => $item,
            'title' => $this->title,
            'routePrefix' => $this->routePrefix
        ]);
    }

    protected function getValidationRules($item = null): array
    {
        return [];
    }
}