<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerType;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerType;

class ShowController extends Controller
{
    protected $model;
    protected $viewPath;
    protected $routePrefix;
    protected $title;

    public function __construct()
    {
        $this->model = PartnerType::class;
        $this->viewPath = 'jiny-partner::admin.partner-type';
        $this->routePrefix = 'partner.type';
        $this->title = '파트너 타입';
    }

    /**
     * 파트너 타입 상세보기
     */
    public function __invoke($id)
    {
        $item = $this->model::with(['creator', 'updater'])
            ->withCount([
                'partners',
                'partners as active_partners_count' => function($query) {
                    $query->where('status', 'active');
                }
            ])
            ->findOrFail($id);

        // 타입별 파트너 성과 통계
        $partnerStats = [
            'total_sales' => $item->partners()->sum('total_sales') ?? 0,
            'monthly_sales' => $item->partners()->sum('monthly_sales') ?? 0,
            'avg_rating' => $item->partners()->avg('average_rating') ?? 0,
            'top_performers' => $item->partners()
                ->where('status', 'active')
                ->orderBy('monthly_sales', 'desc')
                ->limit(5)
                ->get()
        ];

        return view($this->viewPath . '.show', [
            'item' => $item,
            'partnerStats' => $partnerStats,
            'title' => $this->title,
            'routePrefix' => $this->routePrefix
        ]);
    }
}