<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerSales;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerSales;
use Jiny\Partner\Services\CommissionCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DestroyController extends Controller
{
    protected $routePrefix;
    protected $commissionService;

    public function __construct(CommissionCalculationService $commissionService)
    {
        $this->routePrefix = 'partner.sales';
        $this->commissionService = $commissionService;
    }

    /**
     * 파트너 매출 삭제
     */
    public function __invoke(Request $request, $id)
    {
        $sales = PartnerSales::findOrFail($id);

        // 삭제 권한 확인
        $canDelete = $this->canDeleteSales($sales);
        if (!$canDelete['allowed']) {
            return back()->withErrors(['error' => $canDelete['reason']]);
        }

        try {
            DB::beginTransaction();

            // 커미션이 계산된 경우 역계산 수행
            if ($sales->commission_calculated) {
                $this->commissionService->reverseCalculation($sales);
                Log::info('매출 삭제 전 커미션 역계산 수행', [
                    'sales_id' => $sales->id,
                    'partner_id' => $sales->partner_id,
                    'amount' => $sales->amount,
                ]);
            }

            // 관련 커미션 레코드들 소프트 삭제
            $sales->commissions()->delete();

            // 매출 레코드 소프트 삭제
            $deletedSales = $sales->toArray(); // 삭제 전 데이터 백업
            $sales->delete();

            // 삭제 로그 기록
            Log::info('파트너 매출 삭제', [
                'sales_id' => $id,
                'partner_id' => $deletedSales['partner_id'],
                'partner_name' => $deletedSales['partner_name'],
                'amount' => $deletedSales['amount'],
                'status' => $deletedSales['status'],
                'commission_calculated' => $deletedSales['commission_calculated'],
                'deleted_by' => auth()->id(),
                'deleted_at' => now(),
            ]);

            DB::commit();

            return redirect()
                ->route('admin.' . $this->routePrefix . '.index')
                ->with('success', '매출이 성공적으로 삭제되었습니다.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('매출 삭제 실패', [
                'sales_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['error' => '매출 삭제 중 오류가 발생했습니다: ' . $e->getMessage()]);
        }
    }

    /**
     * 매출 삭제 권한 확인
     */
    private function canDeleteSales(PartnerSales $sales)
    {
        // 환불된 매출은 삭제 불가 (기록 보존 필요)
        if ($sales->status === 'refunded') {
            return [
                'allowed' => false,
                'reason' => '환불된 매출은 기록 보존을 위해 삭제할 수 없습니다. 취소 상태로 변경해주세요.',
            ];
        }

        // 확정되고 커미션이 지급된 매출의 경우 경고
        if ($sales->status === 'confirmed' && $sales->commission_calculated) {
            // 실제로는 삭제 가능하지만 신중하게 처리
            return [
                'allowed' => true,
                'reason' => null,
                'warning' => '이미 커미션이 분배된 매출입니다. 삭제 시 커미션이 회수됩니다.',
            ];
        }

        // 승인이 필요한 매출 중 이미 승인된 경우
        if ($sales->requires_approval && $sales->is_approved) {
            return [
                'allowed' => true,
                'reason' => null,
                'warning' => '승인된 매출입니다. 삭제 시 승인 이력도 함께 삭제됩니다.',
            ];
        }

        return ['allowed' => true, 'reason' => null];
    }

    /**
     * 대량 삭제 처리
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:partner_sales,id',
        ]);

        $results = [
            'total' => count($validated['ids']),
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        try {
            DB::beginTransaction();

            foreach ($validated['ids'] as $id) {
                try {
                    $sales = PartnerSales::findOrFail($id);

                    // 삭제 권한 확인
                    $canDelete = $this->canDeleteSales($sales);
                    if (!$canDelete['allowed']) {
                        $results['failed']++;
                        $results['errors'][] = [
                            'id' => $id,
                            'title' => $sales->title,
                            'error' => $canDelete['reason'],
                        ];
                        continue;
                    }

                    // 커미션 역계산
                    if ($sales->commission_calculated) {
                        $this->commissionService->reverseCalculation($sales);
                    }

                    // 관련 데이터 삭제
                    $sales->commissions()->delete();
                    $sales->delete();

                    $results['success']++;

                    Log::info('대량 매출 삭제', [
                        'sales_id' => $id,
                        'partner_id' => $sales->partner_id,
                        'amount' => $sales->amount,
                    ]);

                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'id' => $id,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            $message = "총 {$results['total']}개 중 {$results['success']}개 삭제 완료";
            if ($results['failed'] > 0) {
                $message .= ", {$results['failed']}개 실패";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => '대량 삭제 중 오류가 발생했습니다: ' . $e->getMessage(),
                'results' => $results,
            ], 500);
        }
    }

    /**
     * 완전 삭제 (관리자 전용)
     */
    public function forceDestroy(Request $request, $id)
    {
        // 관리자 권한 확인 (예: Super Admin만 허용)
        if (!auth()->user()->hasRole('super_admin')) {
            return back()->withErrors(['error' => '완전 삭제 권한이 없습니다.']);
        }

        try {
            DB::beginTransaction();

            $sales = PartnerSales::withTrashed()->findOrFail($id);

            // 관련 커미션 레코드들 완전 삭제
            $sales->commissions()->withTrashed()->forceDelete();

            // 매출 레코드 완전 삭제
            $sales->forceDelete();

            Log::warning('매출 완전 삭제', [
                'sales_id' => $id,
                'deleted_by' => auth()->id(),
                'ip' => $request->ip(),
            ]);

            DB::commit();

            return redirect()
                ->route('admin.' . $this->routePrefix . '.index')
                ->with('success', '매출이 완전히 삭제되었습니다.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => '완전 삭제 중 오류가 발생했습니다: ' . $e->getMessage()]);
        }
    }

    /**
     * 삭제된 매출 복원
     */
    public function restore(Request $request, $id)
    {
        try {
            $sales = PartnerSales::withTrashed()->findOrFail($id);

            if (!$sales->trashed()) {
                return back()->withErrors(['error' => '삭제되지 않은 매출입니다.']);
            }

            $sales->restore();

            // 관련 커미션 레코드들도 복원 (필요한 경우)
            $sales->commissions()->withTrashed()->restore();

            Log::info('매출 복원', [
                'sales_id' => $id,
                'partner_id' => $sales->partner_id,
                'restored_by' => auth()->id(),
            ]);

            return redirect()
                ->route('admin.' . $this->routePrefix . '.show', $id)
                ->with('success', '매출이 복원되었습니다.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => '매출 복원 중 오류가 발생했습니다: ' . $e->getMessage()]);
        }
    }
}