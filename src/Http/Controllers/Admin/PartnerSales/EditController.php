<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerSales;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerSales;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;

class EditController extends Controller
{
    protected $viewPath;
    protected $routePrefix;
    protected $title;

    public function __construct()
    {
        $this->viewPath = 'jiny-partner::admin.partner-sales';
        $this->routePrefix = 'partner.sales';
        $this->title = '파트너 매출 수정';
    }

    /**
     * 파트너 매출 수정 폼
     */
    public function __invoke(Request $request, $id)
    {
        $sales = PartnerSales::with(['partner.tier', 'partner.type'])->findOrFail($id);

        // 커미션이 이미 계산된 매출은 제한적 수정만 허용
        $isCommissionCalculated = $sales->commission_calculated;

        // 확정된 매출도 제한적 수정만 허용
        $isConfirmed = $sales->status === 'confirmed';

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

        // 상태 옵션
        $statuses = [
            'pending' => '대기중',
            'confirmed' => '확정',
            'cancelled' => '취소',
            'refunded' => '환불',
        ];

        // 수정 가능한 필드 목록
        $editableFields = $this->getEditableFields($sales, $isCommissionCalculated, $isConfirmed);

        // 경고 메시지
        $warnings = $this->getEditWarnings($sales, $isCommissionCalculated, $isConfirmed);

        return view($this->viewPath . '.edit', [
            'sales' => $sales,
            'partners' => $partners,
            'categories' => $categories,
            'productTypes' => $productTypes,
            'salesChannels' => $salesChannels,
            'currencies' => $currencies,
            'statuses' => $statuses,
            'editableFields' => $editableFields,
            'warnings' => $warnings,
            'isCommissionCalculated' => $isCommissionCalculated,
            'isConfirmed' => $isConfirmed,
            'title' => $this->title,
            'routePrefix' => $this->routePrefix,
        ]);
    }

    /**
     * 수정 가능한 필드 목록 반환
     */
    private function getEditableFields(PartnerSales $sales, $isCommissionCalculated, $isConfirmed)
    {
        $allFields = [
            'partner_id',
            'title',
            'description',
            'amount',
            'currency',
            'sales_date',
            'order_number',
            'category',
            'product_type',
            'sales_channel',
            'status',
            'admin_notes',
            'external_reference',
        ];

        // 커미션이 계산된 경우 제한
        if ($isCommissionCalculated) {
            return [
                'title',
                'description',
                'category',
                'product_type',
                'sales_channel',
                'admin_notes',
                'external_reference',
            ];
        }

        // 확정된 매출의 경우 제한
        if ($isConfirmed) {
            return array_diff($allFields, ['partner_id', 'amount', 'currency', 'sales_date']);
        }

        // 대기중이거나 취소된 매출은 모든 필드 수정 가능
        return $allFields;
    }

    /**
     * 수정 관련 경고 메시지 생성
     */
    private function getEditWarnings(PartnerSales $sales, $isCommissionCalculated, $isConfirmed)
    {
        $warnings = [];

        if ($isCommissionCalculated) {
            $warnings[] = [
                'type' => 'danger',
                'message' => '이미 커미션이 계산된 매출입니다. 일부 필드만 수정 가능합니다.',
                'detail' => '파트너, 금액, 통화, 매출일, 주문번호, 상태는 수정할 수 없습니다.',
            ];
        }

        if ($isConfirmed && !$isCommissionCalculated) {
            $warnings[] = [
                'type' => 'warning',
                'message' => '확정된 매출입니다. 중요 정보 수정 시 주의하세요.',
                'detail' => '파트너, 금액, 통화, 매출일 수정은 제한됩니다.',
            ];
        }

        if ($sales->status === 'cancelled') {
            $warnings[] = [
                'type' => 'info',
                'message' => '취소된 매출입니다.',
                'detail' => '상태를 변경하여 매출을 복원할 수 있습니다.',
            ];
        }

        if ($sales->requires_approval && !$sales->is_approved) {
            $warnings[] = [
                'type' => 'warning',
                'message' => '승인이 필요한 매출입니다.',
                'detail' => '관리자 승인 후 확정 처리가 가능합니다.',
            ];
        }

        return $warnings;
    }
}