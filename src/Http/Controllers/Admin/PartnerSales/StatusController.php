<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerSales;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerSales;
use Jiny\Partner\Services\CommissionCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class StatusController extends Controller
{
    protected $commissionService;

    public function __construct(CommissionCalculationService $commissionService)
    {
        $this->commissionService = $commissionService;
    }

    /**
     * 매출 승인
     */
    public function approve(Request $request, $salesId)
    {
        try {
            $sales = PartnerSales::findOrFail($salesId);

            if ($sales->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => '대기중인 매출만 승인할 수 있습니다.'
                ], 400);
            }

            $sales->update([
                'is_approved' => true,
                'approved_at' => now(),
                'approved_by' => auth()->id(),
                'approval_notes' => $request->input('notes', '관리자 승인'),
            ]);

            // 로그 기록
            Log::info('매출 승인 완료', [
                'sales_id' => $sales->id,
                'partner_id' => $sales->partner_id,
                'approved_by' => auth()->user()->name,
                'amount' => $sales->amount,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => '매출이 승인되었습니다.',
                    'data' => [
                        'status' => $sales->fresh()->status,
                        'is_approved' => $sales->is_approved,
                        'approved_at' => $sales->approved_at?->format('Y-m-d H:i'),
                    ]
                ]);
            }

            return redirect()->back()->with('success', '매출이 승인되었습니다.');

        } catch (Exception $e) {
            Log::error('매출 승인 실패', [
                'sales_id' => $salesId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '매출 승인 중 오류가 발생했습니다: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', '매출 승인 중 오류가 발생했습니다.');
        }
    }

    /**
     * 매출 확정
     */
    public function confirm(Request $request, $salesId)
    {
        try {
            DB::beginTransaction();

            $sales = PartnerSales::findOrFail($salesId);

            if ($sales->status !== 'pending') {
                throw new Exception('대기중인 매출만 확정할 수 있습니다.');
            }

            $sales->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'status_reason' => $request->input('reason', '관리자 확정'),
            ]);

            // 파트너 매출 통계 동기화
            $sales->syncPartnerSales('status_changed');

            DB::commit();

            // 로그 기록
            Log::info('매출 확정 완료', [
                'sales_id' => $sales->id,
                'partner_id' => $sales->partner_id,
                'confirmed_by' => auth()->user()->name,
                'amount' => $sales->amount,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => '매출이 확정되었습니다. 이제 커미션 계산이 가능합니다.',
                    'data' => [
                        'status' => $sales->fresh()->status,
                        'confirmed_at' => $sales->confirmed_at?->format('Y-m-d H:i'),
                        'can_calculate_commission' => true,
                    ]
                ]);
            }

            return redirect()->back()->with('success', '매출이 확정되었습니다. 이제 커미션 계산이 가능합니다.');

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('매출 확정 실패', [
                'sales_id' => $salesId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '매출 확정 중 오류가 발생했습니다: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', '매출 확정 중 오류가 발생했습니다.');
        }
    }

    /**
     * 매출 취소
     */
    public function cancel(Request $request, $salesId)
    {
        try {
            DB::beginTransaction();

            $sales = PartnerSales::findOrFail($salesId);

            if ($sales->status === 'cancelled') {
                throw new Exception('이미 취소된 매출입니다.');
            }

            $oldStatus = $sales->status;
            $cancelReason = $request->input('reason', '관리자 취소');

            // 커미션이 계산되었다면 역계산 실행
            if ($sales->commission_calculated) {
                $this->commissionService->reverseCalculation($sales);
            }

            $sales->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'status_reason' => $cancelReason,
            ]);

            // 파트너 매출 통계 동기화
            $sales->syncPartnerSales('status_changed');

            DB::commit();

            // 로그 기록
            Log::info('매출 취소 완료', [
                'sales_id' => $sales->id,
                'partner_id' => $sales->partner_id,
                'cancelled_by' => auth()->user()->name,
                'old_status' => $oldStatus,
                'amount' => $sales->amount,
                'reason' => $cancelReason,
                'commission_reversed' => $sales->commission_calculated,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => '매출이 취소되었습니다.' . ($sales->commission_calculated ? ' 커미션도 회수되었습니다.' : ''),
                    'data' => [
                        'status' => $sales->fresh()->status,
                        'cancelled_at' => $sales->cancelled_at?->format('Y-m-d H:i'),
                        'commission_reversed' => $sales->commission_calculated,
                    ]
                ]);
            }

            return redirect()->back()->with('success', '매출이 취소되었습니다.' . ($sales->commission_calculated ? ' 커미션도 회수되었습니다.' : ''));

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('매출 취소 실패', [
                'sales_id' => $salesId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '매출 취소 중 오류가 발생했습니다: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', '매출 취소 중 오류가 발생했습니다.');
        }
    }

    /**
     * 커미션 계산
     */
    public function calculateCommission(Request $request, $salesId)
    {
        try {
            $sales = PartnerSales::findOrFail($salesId);

            if ($sales->status !== 'confirmed') {
                throw new Exception('확정된 매출만 커미션 계산이 가능합니다.');
            }

            if ($sales->commission_calculated) {
                throw new Exception('이미 커미션이 계산된 매출입니다.');
            }

            $result = $this->commissionService->calculateAndDistribute($sales);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "커미션 계산이 완료되었습니다. 총 {$result['recipients_count']}명에게 " .
                                number_format($result['total_commission']) . "원이 분배되었습니다.",
                    'data' => [
                        'total_commission' => $result['total_commission'],
                        'recipients_count' => $result['recipients_count'],
                        'distributions' => $result['distributions'],
                        'commission_calculated' => true,
                    ]
                ]);
            }

            return redirect()->back()->with('success',
                "커미션 계산이 완료되었습니다. 총 {$result['recipients_count']}명에게 " .
                number_format($result['total_commission']) . "원이 분배되었습니다."
            );

        } catch (Exception $e) {
            Log::error('커미션 계산 실패', [
                'sales_id' => $salesId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '커미션 계산 중 오류가 발생했습니다: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', '커미션 계산 중 오류가 발생했습니다.');
        }
    }

    /**
     * 매출 상태 변경 이력 조회 (AJAX용)
     */
    public function getStatusHistory(Request $request, $salesId)
    {
        try {
            $sales = PartnerSales::with(['creator', 'approver'])->findOrFail($salesId);

            $history = [];

            // 등록 이력
            $history[] = [
                'status' => 'created',
                'status_korean' => '등록',
                'date' => $sales->created_at,
                'user' => $sales->creator,
                'notes' => '매출이 등록되었습니다.',
                'icon' => 'fas fa-plus-circle',
                'color' => 'info'
            ];

            // 승인 이력
            if ($sales->is_approved && $sales->approved_at) {
                $history[] = [
                    'status' => 'approved',
                    'status_korean' => '승인',
                    'date' => $sales->approved_at,
                    'user' => $sales->approver,
                    'notes' => $sales->approval_notes ?: '매출이 승인되었습니다.',
                    'icon' => 'fas fa-check-circle',
                    'color' => 'success'
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
                    'icon' => 'fas fa-handshake',
                    'color' => 'primary'
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
                    'icon' => 'fas fa-calculator',
                    'color' => 'warning'
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
                    'icon' => 'fas fa-times-circle',
                    'color' => 'danger'
                ];
            }

            return response()->json([
                'success' => true,
                'data' => collect($history)->sortBy('date')->values()
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '상태 이력 조회 중 오류가 발생했습니다.'
            ], 500);
        }
    }
}