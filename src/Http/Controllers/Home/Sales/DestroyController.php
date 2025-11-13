<?php

namespace Jiny\Partner\Http\Controllers\Home\Sales;

use Jiny\Partner\Http\Controllers\PartnerController;
use Illuminate\Http\Request;
use Jiny\Partner\Models\PartnerSales;
use Illuminate\Support\Facades\Log;

class DestroyController extends PartnerController
{
    /**
     * 파트너 매출 삭제 처리 (대기 상태만 삭제 가능)
     *
     * 삭제 조건:
     * 1. 본인의 매출만 삭제 가능
     * 2. 'pending' (대기) 상태만 삭제 가능
     * 3. 'confirmed' 또는 'cancel_pending' 상태는 삭제 불가
     */
    public function __invoke(Request $request, $id)
    {
        try {
            // ========================================
            // Step 1: 사용자 인증 및 파트너 검증
            // ========================================
            $authResult = $this->authenticateAndGetPartner($request, 'sales.delete');
            if (!$authResult['success']) {
                return $authResult['redirect'];
            }

            $user = $authResult['user'];
            $partner = $authResult['partner'];

            Log::info("Sales delete attempt", [
                'sale_id' => $id,
                'partner_id' => $partner->id,
                'user_uuid' => $user->uuid
            ]);

            // ========================================
            // Step 2: 매출 정보 조회 및 권한 확인
            // ========================================
            $sale = PartnerSales::find($id);

            if (!$sale) {
                Log::warning("Sale not found for deletion", [
                    'sale_id' => $id,
                    'partner_id' => $partner->id
                ]);

                return redirect()->route('home.partner.sales.index')
                    ->with('error', '매출 정보를 찾을 수 없습니다.');
            }

            // 본인 매출만 삭제 가능
            if ($sale->partner_id !== $partner->id) {
                Log::warning("Unauthorized sale deletion attempt", [
                    'sale_id' => $id,
                    'sale_partner_id' => $sale->partner_id,
                    'request_partner_id' => $partner->id
                ]);

                return redirect()->route('home.partner.sales.index')
                    ->with('error', '본인의 매출만 삭제할 수 있습니다.');
            }

            // ========================================
            // Step 3: 삭제 가능 상태 확인
            // ========================================
            if ($sale->status !== 'pending') {
                Log::info("Sale deletion blocked - invalid status", [
                    'sale_id' => $id,
                    'current_status' => $sale->status,
                    'partner_id' => $partner->id
                ]);

                $statusMessage = $this->getStatusMessage($sale->status);
                return redirect()->route('home.partner.sales.show', $id)
                    ->with('error', "대기 상태의 매출만 삭제할 수 있습니다. 현재 상태: {$statusMessage}");
            }

            // ========================================
            // Step 4: 매출 삭제 실행
            // ========================================
            $deletedSale = [
                'id' => $sale->id,
                'title' => $sale->title,
                'amount' => $sale->amount,
                'sales_date' => $sale->sales_date,
                'partner_name' => $sale->partner_name
            ];

            $sale->delete();

            Log::info("Sale successfully deleted", [
                'deleted_sale' => $deletedSale,
                'partner_id' => $partner->id,
                'user_uuid' => $user->uuid
            ]);

            // ========================================
            // Step 5: 성공 응답
            // ========================================
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'code' => 200,
                    'message' => '매출이 성공적으로 삭제되었습니다.',
                    'data' => [
                        'deleted_sale' => $deletedSale
                    ]
                ]);
            }

            return redirect()->route('home.partner.sales.index')
                ->with('success', "매출 '{$deletedSale['title']}'이(가) 성공적으로 삭제되었습니다.");

        } catch (\Exception $e) {
            // ========================================
            // Step 6: 에러 처리
            // ========================================
            Log::error('Sales delete error: ' . $e->getMessage(), [
                'sale_id' => $id,
                'user_id' => $user->id ?? 'unknown',
                'user_uuid' => $user->uuid ?? 'unknown',
                'partner_id' => $partner->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'code' => 500,
                    'message' => '매출 삭제 중 오류가 발생했습니다.'
                ], 500);
            }

            return redirect()->route('home.partner.sales.index')
                ->with('error', '매출 삭제 중 오류가 발생했습니다.');
        }
    }

    /**
     * 상태별 메시지 반환
     */
    private function getStatusMessage($status)
    {
        $statusMessages = [
            'pending' => '대기',
            'confirmed' => '승인됨',
            'rejected' => '반려됨',
            'cancelled' => '취소됨',
            'cancel_pending' => '취소 승인 대기'
        ];

        return $statusMessages[$status] ?? $status;
    }
}