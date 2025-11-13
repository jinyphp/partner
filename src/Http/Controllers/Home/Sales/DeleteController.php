<?php

namespace Jiny\Partner\Http\Controllers\Home\Sales;

use Jiny\Partner\Http\Controllers\PartnerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Jiny\Partner\Models\PartnerSales;
use Jiny\Partner\Models\PartnerUser;

class DeleteController extends PartnerController
{
    /**
     * 파트너 매출 삭제 처리
     */
    public function __invoke(Request $request, $id)
    {
        try {
            // 파트너 인증 및 정보 조회 (공통 로직 사용)
            $authResult = $this->authenticateAndGetPartner($request, 'sales_delete');
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
                    ->with('error', '해당 매출에 대한 삭제 권한이 없습니다.');
            }

            // 상태 확인: cancelled 상태만 삭제 가능
            if ($sale->status !== 'cancelled') {
                return redirect()->route('home.partner.sales.show', $sale->id)
                    ->with('error', '승인된 취소 상태의 매출만 삭제할 수 있습니다.');
            }

            // 커미션이 계산된 매출은 삭제 불가
            if ($sale->commission_calculated) {
                return redirect()->route('home.partner.sales.show', $sale->id)
                    ->with('error', '커미션이 계산된 매출은 삭제할 수 없습니다. 취소 기능을 사용해 주세요.');
            }

            // 삭제 전 로그 기록
            \Log::warning('Partner sales deletion', [
                'sale_id' => $sale->id,
                'partner_id' => $partnerUser->id,
                'user_id' => $user->id,
                'deleted_by' => $partnerUser->name,
                'original_amount' => $sale->amount,
                'product_name' => $sale->title,
                'sales_date' => $sale->sales_date,
                'created_at' => $sale->created_at,
                'cancelled_at' => $sale->cancelled_at,
                'deletion_reason' => 'Partner requested deletion of approved cancelled sale',
                'original_status' => 'cancelled',
                'cancellation_reason' => $sale->status_reason
            ]);

            // 매출 삭제 (실제 데이터베이스에서 제거)
            $sale->delete();

            return redirect()->route('home.partner.sales.history')
                ->with('success', '매출이 성공적으로 삭제되었습니다.');

        } catch (\Exception $e) {
            \Log::error('Partner sales delete error: ' . $e->getMessage(), [
                'user_id' => $user->id ?? 'unknown',
                'user_uuid' => $user->uuid ?? 'unknown',
                'sale_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('home.partner.sales.show', $id)
                ->with('error', '매출 삭제 중 오류가 발생했습니다.');
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