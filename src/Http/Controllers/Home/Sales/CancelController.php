<?php

namespace Jiny\Partner\Http\Controllers\Home\Sales;

use Jiny\Partner\Http\Controllers\PartnerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Jiny\Partner\Models\PartnerSales;
use Jiny\Partner\Models\PartnerUser;

class CancelController extends PartnerController
{
    /**
     * 파트너 매출 취소 처리
     */
    public function __invoke(Request $request, $id)
    {
        try {
            // 파트너 인증 및 정보 조회 (공통 로직 사용)
            $authResult = $this->authenticateAndGetPartner($request, 'sales_cancel');
            if (!$authResult['success']) {
                return $authResult['redirect'];
            }

            $user = $authResult['user'];
            $partnerUser = $authResult['partner'];

            // 매출 정보 조회
            $sale = PartnerSales::find($id);

            if (!$sale) {
                return redirect()->route('home.partner.sales.history')
                    ->with('error', '매출 정보를 찾을 수 없습니다.');
            }

            // 권한 확인: 본인의 매출이거나 하위 파트너의 매출인지 확인
            $hasAccess = false;

            if ($sale->partner_id == $partnerUser->id) {
                // 본인의 매출
                $hasAccess = true;
            } else {
                // 하위 파트너의 매출인지 확인
                $hasAccess = $this->checkSubPartnerAccess($partnerUser->id, $sale->partner_id);
            }

            if (!$hasAccess) {
                return redirect()->route('home.partner.sales.history')
                    ->with('error', '해당 매출에 대한 수정 권한이 없습니다.');
            }

            // 상태 확인: pending, confirmed, rejected 상태만 취소 가능
            if (!in_array($sale->status, ['pending', 'confirmed', 'rejected'])) {
                return redirect()->route('home.partner.sales.show', $sale->id)
                    ->with('error', '대기, 확정 또는 반려 상태의 매출만 취소할 수 있습니다.');
            }

            // 취소 요청 이유 설정
            $originalStatus = $sale->status;
            $statusReason = '';

            switch ($originalStatus) {
                case 'confirmed':
                    $statusReason = '파트너가 확정 매출 취소 요청함';
                    break;
                case 'rejected':
                    $statusReason = '파트너가 반려 매출 취소 요청함';
                    break;
                case 'pending':
                default:
                    $statusReason = '파트너가 대기 매출 취소 요청함';
                    break;
            }

            // 매출 취소 요청 처리 (승인 대기 상태로 변경)
            $sale->update([
                'status' => 'cancel_pending',
                'cancel_requested_at' => now(),
                'status_reason' => $statusReason,
                'updated_by' => $partnerUser->id,
                'updated_at' => now()
            ]);

            // 로그 기록
            \Log::info('Partner sales cancellation requested', [
                'sale_id' => $sale->id,
                'partner_id' => $partnerUser->id,
                'user_id' => $user->id,
                'requested_by' => $partnerUser->name,
                'original_status' => $originalStatus,
                'new_status' => 'cancel_pending',
                'original_amount' => $sale->amount,
                'product_name' => $sale->title,
                'cancellation_request_type' => $originalStatus === 'confirmed'
                    ? 'confirmed_sale_cancellation_request'
                    : ($originalStatus === 'rejected'
                        ? 'rejected_sale_cancellation_request'
                        : 'pending_sale_cancellation_request'),
                'confirmed_at' => $sale->confirmed_at ?? null,
                'cancel_requested_at' => now()
            ]);

            return redirect()->route('home.partner.sales.show', $sale->id)
                ->with('success', '매출 취소 요청이 제출되었습니다. 승인을 기다려주세요.');

        } catch (\Exception $e) {
            \Log::error('Partner sales cancel error: ' . $e->getMessage(), [
                'user_id' => $user->id ?? 'unknown',
                'user_uuid' => $user->uuid ?? 'unknown',
                'sale_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('home.partner.sales.show', $id)
                ->with('error', '매출 취소 중 오류가 발생했습니다.');
        }
    }

    /**
     * 하위 파트너 접근 권한 확인
     */
    private function checkSubPartnerAccess($parentId, $childId)
    {
        try {
            if (class_exists('\Jiny\Partner\Models\PartnerNetworkRelationship')) {
                return \Jiny\Partner\Models\PartnerNetworkRelationship::where('parent_id', $parentId)
                    ->where('child_id', $childId)
                    ->where('is_active', true)
                    ->exists();
            }
            return false;
        } catch (\Exception $e) {
            \Log::error('Failed to check sub partner access: ' . $e->getMessage());
            return false;
        }
    }
}