<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerSales;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;

class CreateController extends Controller
{
    protected $viewPath;
    protected $routePrefix;
    protected $title;

    public function __construct()
    {
        $this->viewPath = 'jiny-partner::admin.partner-sales';
        $this->routePrefix = 'partner.sales';
        $this->title = '파트너 매출 등록';
    }

    /**
     * 파트너 매출 등록 폼
     */
    public function __invoke(Request $request)
    {
        // 파트너 목록 조회 (활성 파트너만)
        $partners = PartnerUser::with(['tier', 'type'])
                              ->where('status', 'active')
                              ->orderBy('name')
                              ->get()
                              ->map(function ($partner) {
                                  return [
                                      'id' => $partner->id,
                                      'name' => $partner->name,
                                      'email' => $partner->email,
                                      'tier_name' => $partner->tier->tier_name ?? '미지정',
                                      'type_name' => $partner->type->type_name ?? '미지정',
                                      'display_name' => $partner->name . ' (' . $partner->email . ') - ' .
                                                       ($partner->tier->tier_name ?? '미지정') . '/' .
                                                       ($partner->type->type_name ?? '미지정'),
                                  ];
                              });

        // 카테고리 옵션
        $categories = [
            'product_sales' => '제품 판매',
            'service_sales' => '서비스 판매',
            'subscription' => '구독 서비스',
            'license' => '라이선스 판매',
            'consulting' => '컨설팅 서비스',
            'training' => '교육 서비스',
            'support' => '기술 지원',
            'other' => '기타',
        ];

        // 제품 타입 옵션
        $productTypes = [
            'premium' => '프리미엄',
            'standard' => '스탠다드',
            'basic' => '베이직',
            'enterprise' => '엔터프라이즈',
            'starter' => '스타터',
            'professional' => '프로페셔널',
        ];

        // 판매 채널 옵션
        $salesChannels = [
            'online' => '온라인',
            'offline' => '오프라인',
            'mobile' => '모바일앱',
            'phone' => '전화 상담',
            'partner' => '파트너 추천',
            'direct' => '직접 영업',
        ];

        // 통화 옵션
        $currencies = [
            'KRW' => '원 (KRW)',
            'USD' => '달러 (USD)',
            'EUR' => '유로 (EUR)',
            'JPY' => '엔 (JPY)',
        ];

        // 선택된 파트너 ID (URL 파라미터에서)
        $selectedPartnerId = $request->get('partner_id');

        return view($this->viewPath . '.create', [
            'title' => $this->title,
            'routePrefix' => $this->routePrefix,
            'partners' => $partners,
            'categories' => $categories,
            'productTypes' => $productTypes,
            'salesChannels' => $salesChannels,
            'currencies' => $currencies,
            'selectedPartnerId' => $selectedPartnerId,
        ]);
    }
}