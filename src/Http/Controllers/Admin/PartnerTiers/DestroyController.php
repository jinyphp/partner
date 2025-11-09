<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerTiers;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerTier;

class DestroyController extends Controller
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
     * 파트너 등급 삭제
     */
    public function __invoke($id)
    {
        $item = $this->model::findOrFail($id);
        $item->delete();

        return redirect()->route("admin.{$this->routePrefix}.index")
            ->with('success', $this->title . ' 항목이 성공적으로 삭제되었습니다.');
    }

    protected function getValidationRules($item = null): array
    {
        return [];
    }
}