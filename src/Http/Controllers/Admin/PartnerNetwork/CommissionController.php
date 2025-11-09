<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerNetwork;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Models\PartnerCommission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommissionController extends Controller
{
    /**
     * 커미션 관리 대시보드
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'all');
        $type = $request->get('type', 'all');
        $partnerId = $request->get('partner_id');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $perPage = $request->get('per_page', 20);

        // 커미션 조회
        $query = PartnerCommission::with(['partner', 'sourcePartner'])
            ->orderBy('earned_at', 'desc');

        // 상태 필터
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // 타입 필터
        if ($type !== 'all') {
            $query->where('commission_type', $type);
        }

        // 파트너 필터
        if ($partnerId) {
            $query->where('partner_id', $partnerId);
        }

        // 날짜 범위 필터
        if ($startDate && $endDate) {
            $query->whereBetween('earned_at', [$startDate, $endDate]);
        }

        $commissions = $query->paginate($perPage);

        // 통계 데이터
        $statistics = $this->getCommissionStatistics($partnerId, $startDate, $endDate);

        // 타입별 통계
        $typeStatistics = $this->getCommissionTypeStatistics();

        return view('jiny-partner::admin.partner-network.commission', [
            'commissions' => $commissions,
            'statistics' => $statistics,
            'typeStatistics' => $typeStatistics,
            'availablePartners' => PartnerUser::active()->get(),
            'commissionTypes' => $this->getCommissionTypes(),
            'currentFilters' => [
                'status' => $status,
                'type' => $type,
                'partner_id' => $partnerId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'per_page' => $perPage
            ],
            'pageTitle' => '커미션 관리'
        ]);
    }

    /**
     * 커미션 계산 및 분배
     */
    public function calculate(Request $request)
    {
        $request->validate([
            'source_partner_id' => 'required|exists:partner_users,id',
            'sale_amount' => 'required|numeric|min:0',
            'order_data' => 'nullable|array'
        ]);

        try {
            DB::transaction(function () use ($request) {
                $sourcePartner = PartnerUser::findOrFail($request->source_partner_id);
                $saleAmount = $request->sale_amount;
                $orderData = $request->order_data ?? [];

                // 커미션 분배 계산
                $commissions = $sourcePartner->calculateCommissionDistribution($saleAmount, $orderData);

                // 커미션 레코드 생성
                foreach ($commissions as $commissionData) {
                    PartnerCommission::create($commissionData);
                }

                // 매출 업데이트
                $sourcePartner->increment('monthly_sales', $saleAmount);
                $sourcePartner->increment('total_sales', $saleAmount);
                $sourcePartner->updateTeamSales();
            });

            return response()->json([
                'success' => true,
                'message' => '커미션이 성공적으로 계산되고 분배되었습니다.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '커미션 계산 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * 커미션 일괄 처리
     */
    public function bulkProcess(Request $request)
    {
        $request->validate([
            'commission_ids' => 'required|array',
            'commission_ids.*' => 'exists:partner_commissions,id',
            'action' => 'required|in:calculate,pay,cancel'
        ]);

        $successful = 0;
        $failed = 0;
        $errors = [];

        foreach ($request->commission_ids as $commissionId) {
            try {
                $commission = PartnerCommission::findOrFail($commissionId);

                switch ($request->action) {
                    case 'calculate':
                        if ($commission->calculate()) {
                            $successful++;
                        } else {
                            $failed++;
                            $errors[] = "커미션 ID {$commissionId}: 계산 불가";
                        }
                        break;

                    case 'pay':
                        if ($commission->markAsPaid($request->notes)) {
                            $successful++;
                        } else {
                            $failed++;
                            $errors[] = "커미션 ID {$commissionId}: 지급 처리 불가";
                        }
                        break;

                    case 'cancel':
                        if ($commission->cancel($request->reason)) {
                            $successful++;
                        } else {
                            $failed++;
                            $errors[] = "커미션 ID {$commissionId}: 취소 불가";
                        }
                        break;
                }
            } catch (\Exception $e) {
                $failed++;
                $errors[] = "커미션 ID {$commissionId}: " . $e->getMessage();
            }
        }

        return response()->json([
            'success' => $successful > 0,
            'message' => "일괄 처리 완료: 성공 {$successful}건, 실패 {$failed}건",
            'successful' => $successful,
            'failed' => $failed,
            'errors' => $errors
        ]);
    }

    /**
     * 커미션 상세 조회
     */
    public function show($id)
    {
        $commission = PartnerCommission::with([
            'partner.partnerTier',
            'sourcePartner.partnerTier'
        ])->findOrFail($id);

        // 관련 커미션들 (같은 소스에서 발생한)
        $relatedCommissions = PartnerCommission::where('source_partner_id', $commission->source_partner_id)
            ->where('id', '!=', $commission->id)
            ->where('earned_at', '>=', $commission->earned_at->subHours(1))
            ->where('earned_at', '<=', $commission->earned_at->addHours(1))
            ->with(['partner'])
            ->get();

        return view('jiny-partner::admin.partner-network.commission-detail', [
            'commission' => $commission,
            'relatedCommissions' => $relatedCommissions,
            'pageTitle' => '커미션 상세정보'
        ]);
    }

    /**
     * 파트너별 커미션 요약
     */
    public function partnerSummary(Request $request, $partnerId)
    {
        $partner = PartnerUser::with('partnerTier')->findOrFail($partnerId);
        $period = $request->get('period', 'this_month');

        // 기간 설정
        $dateRange = $this->getDateRange($period);

        // 커미션 통계
        $commissionStats = $this->getPartnerCommissionStats($partnerId, $dateRange);

        // 커미션 내역
        $commissions = PartnerCommission::where('partner_id', $partnerId)
            ->whereBetween('earned_at', $dateRange)
            ->orderBy('earned_at', 'desc')
            ->get();

        // 성과 비교 (이전 기간과)
        $previousDateRange = $this->getPreviousDateRange($period);
        $previousStats = $this->getPartnerCommissionStats($partnerId, $previousDateRange);

        return view('jiny-partner::admin.partner-network.commission-summary', [
            'partner' => $partner,
            'commissionStats' => $commissionStats,
            'commissions' => $commissions,
            'previousStats' => $previousStats,
            'period' => $period,
            'dateRange' => $dateRange,
            'pageTitle' => $partner->name . ' 커미션 요약'
        ]);
    }

    /**
     * 커미션 통계
     */
    private function getCommissionStatistics($partnerId = null, $startDate = null, $endDate = null)
    {
        $query = PartnerCommission::query();

        if ($partnerId) {
            $query->where('partner_id', $partnerId);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('earned_at', [$startDate, $endDate]);
        }

        $total = $query->sum('commission_amount');
        $paid = $query->where('status', 'paid')->sum('commission_amount');
        $pending = $query->where('status', 'pending')->sum('commission_amount');
        $calculated = $query->where('status', 'calculated')->sum('commission_amount');

        return [
            'total_commission' => $total,
            'paid_commission' => $paid,
            'pending_commission' => $pending,
            'calculated_commission' => $calculated,
            'unpaid_commission' => $pending + $calculated,
            'total_count' => $query->count(),
            'paid_count' => $query->where('status', 'paid')->count(),
            'pending_count' => $query->where('status', 'pending')->count(),
            'average_commission' => $query->count() > 0 ? round($total / $query->count(), 2) : 0
        ];
    }

    /**
     * 타입별 커미션 통계
     */
    private function getCommissionTypeStatistics()
    {
        return PartnerCommission::selectRaw('
                commission_type,
                COUNT(*) as count,
                SUM(commission_amount) as total_amount,
                AVG(commission_amount) as avg_amount
            ')
            ->groupBy('commission_type')
            ->orderBy('total_amount', 'desc')
            ->get()
            ->keyBy('commission_type');
    }

    /**
     * 파트너별 커미션 통계
     */
    private function getPartnerCommissionStats($partnerId, $dateRange)
    {
        $commissions = PartnerCommission::where('partner_id', $partnerId)
            ->whereBetween('earned_at', $dateRange)
            ->get();

        $byType = $commissions->groupBy('commission_type');
        $byStatus = $commissions->groupBy('status');

        return [
            'total_earned' => $commissions->sum('commission_amount'),
            'total_paid' => $commissions->where('status', 'paid')->sum('commission_amount'),
            'total_pending' => $commissions->whereIn('status', ['pending', 'calculated'])->sum('commission_amount'),
            'commission_count' => $commissions->count(),
            'by_type' => $byType->map(function($items) {
                return [
                    'count' => $items->count(),
                    'amount' => $items->sum('commission_amount')
                ];
            }),
            'by_status' => $byStatus->map(function($items) {
                return [
                    'count' => $items->count(),
                    'amount' => $items->sum('commission_amount')
                ];
            })
        ];
    }

    /**
     * 커미션 타입 목록
     */
    private function getCommissionTypes()
    {
        return [
            'direct_sales' => '직접 판매',
            'team_bonus' => '팀 보너스',
            'management_bonus' => '관리 보너스',
            'override_bonus' => '오버라이드 보너스',
            'recruitment_bonus' => '모집 보너스',
            'rank_bonus' => '등급 보너스'
        ];
    }

    /**
     * 기간별 날짜 범위 계산
     */
    private function getDateRange($period)
    {
        switch ($period) {
            case 'today':
                return [now()->startOfDay(), now()->endOfDay()];
            case 'this_week':
                return [now()->startOfWeek(), now()->endOfWeek()];
            case 'this_month':
                return [now()->startOfMonth(), now()->endOfMonth()];
            case 'this_quarter':
                return [now()->startOfQuarter(), now()->endOfQuarter()];
            case 'this_year':
                return [now()->startOfYear(), now()->endOfYear()];
            default:
                return [now()->startOfMonth(), now()->endOfMonth()];
        }
    }

    /**
     * 이전 기간 날짜 범위 계산
     */
    private function getPreviousDateRange($period)
    {
        switch ($period) {
            case 'today':
                return [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()];
            case 'this_week':
                return [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()];
            case 'this_month':
                return [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()];
            case 'this_quarter':
                return [now()->subQuarter()->startOfQuarter(), now()->subQuarter()->endOfQuarter()];
            case 'this_year':
                return [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()];
            default:
                return [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()];
        }
    }
}