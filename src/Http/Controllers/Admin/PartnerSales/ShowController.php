<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerSales;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerSales;
use Jiny\Partner\Models\PartnerCommission;
use Illuminate\Http\Request;

class ShowController extends Controller
{
    protected $viewPath;
    protected $routePrefix;
    protected $title;

    public function __construct()
    {
        $this->viewPath = 'jiny-partner::admin.partner-sales';
        $this->routePrefix = 'partner.sales';
        $this->title = '파트너 매출 상세';
    }

    /**
     * 파트너 매출 상세 보기
     */
    public function __invoke(Request $request, $id)
    {
        $sales = PartnerSales::with([
            'partner.tier',
            'partner.type',
            'creator',
            'approver',
            'commissions.partner'
        ])->findOrFail($id);

        // 커미션 분배 내역 조회
        $commissions = PartnerCommission::with(['partner.tier', 'partner.type'])
            ->where('order_id', $sales->id)
            ->orderBy('level_difference')
            ->get();

        // 커미션 통계
        $commissionStats = [
            'total_commission' => $commissions->sum('commission_amount'),
            'total_net_amount' => $commissions->sum('net_amount'),
            'total_tax_amount' => $commissions->sum('tax_amount'),
            'recipients_count' => $commissions->count(),
            'direct_commission' => $commissions->where('commission_type', 'direct_sales')->sum('commission_amount'),
            'indirect_commission' => $commissions->where('commission_type', 'indirect_sales')->sum('commission_amount'),
        ];

        // 트리 스냅샷 정보
        $treeSnapshot = null;
        if ($sales->tree_snapshot) {
            $treeSnapshot = json_decode($sales->tree_snapshot, true);
        }

        // 파트너 계층 구조 시각화 데이터
        $hierarchyData = $this->buildHierarchyVisualization($sales, $commissions);

        // 관련 매출 내역 (같은 파트너의 최근 매출)
        $relatedSales = PartnerSales::with(['creator'])
            ->where('partner_id', $sales->partner_id)
            ->where('id', '!=', $sales->id)
            ->orderBy('sales_date', 'desc')
            ->limit(5)
            ->get();

        // 매출 상태 변경 이력 (예상 구현)
        $statusHistory = $this->getStatusHistory($sales);

        return view($this->viewPath . '.show', [
            'sales' => $sales,
            'commissions' => $commissions,
            'commissionStats' => $commissionStats,
            'treeSnapshot' => $treeSnapshot,
            'hierarchyData' => $hierarchyData,
            'relatedSales' => $relatedSales,
            'statusHistory' => $statusHistory,
            'title' => $this->title,
            'routePrefix' => $this->routePrefix,
        ]);
    }

    /**
     * 계층 구조 시각화 데이터 생성
     */
    private function buildHierarchyVisualization(PartnerSales $sales, $commissions)
    {
        $hierarchy = [];

        // 매출을 올린 파트너 (레벨 0)
        $directCommission = $commissions->where('commission_type', 'direct_sales')->first();
        $hierarchy[] = [
            'level' => 0,
            'partner' => $sales->partner,
            'commission' => $directCommission,
            'type' => 'direct',
            'is_source' => true,
        ];

        // 상위 파트너들 (레벨별 정렬)
        $indirectCommissions = $commissions->where('commission_type', 'indirect_sales')
                                         ->sortBy('level_difference');

        foreach ($indirectCommissions as $commission) {
            $hierarchy[] = [
                'level' => $commission->level_difference,
                'partner' => $commission->partner,
                'commission' => $commission,
                'type' => 'indirect',
                'is_source' => false,
            ];
        }

        return collect($hierarchy)->sortBy('level');
    }

    /**
     * 매출 상태 변경 이력 조회
     */
    private function getStatusHistory(PartnerSales $sales)
    {
        $history = [];

        // 등록 이력
        $history[] = [
            'status' => 'created',
            'status_korean' => '등록',
            'date' => $sales->created_at,
            'user' => $sales->creator,
            'notes' => '매출이 등록되었습니다.',
        ];

        // 승인 이력
        if ($sales->is_approved && $sales->approved_at) {
            $history[] = [
                'status' => 'approved',
                'status_korean' => '승인',
                'date' => $sales->approved_at,
                'user' => $sales->approver,
                'notes' => $sales->approval_notes ?: '매출이 승인되었습니다.',
            ];
        }

        // 확정 이력
        if ($sales->status === 'confirmed' && $sales->confirmed_at) {
            $history[] = [
                'status' => 'confirmed',
                'status_korean' => '확정',
                'date' => $sales->confirmed_at,
                'user' => null,
                'notes' => '매출이 확정되었습니다.',
            ];
        }

        // 커미션 계산 이력
        if ($sales->commission_calculated && $sales->commission_calculated_at) {
            $history[] = [
                'status' => 'commission_calculated',
                'status_korean' => '커미션 계산',
                'date' => $sales->commission_calculated_at,
                'user' => null,
                'notes' => "총 {$sales->commission_recipients_count}명에게 " .
                          number_format($sales->total_commission_amount) . "원의 커미션이 분배되었습니다.",
            ];
        }

        // 취소 이력
        if ($sales->status === 'cancelled' && $sales->cancelled_at) {
            $history[] = [
                'status' => 'cancelled',
                'status_korean' => '취소',
                'date' => $sales->cancelled_at,
                'user' => null,
                'notes' => $sales->status_reason ?: '매출이 취소되었습니다.',
            ];
        }

        return collect($history)->sortBy('date');
    }
}