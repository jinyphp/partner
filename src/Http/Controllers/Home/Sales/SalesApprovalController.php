<?php

namespace Jiny\Partner\Http\Controllers\Home\Sales;

use Jiny\Partner\Http\Controllers\PartnerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Jiny\Partner\Models\PartnerSales;
use Jiny\Partner\Models\PartnerUser;

class SalesApprovalController extends PartnerController
{
    /**
     * 매출 승인 처리 (pending → confirmed)
     */
    public function approve(Request $request, $id)
    {
        try {
            // 파트너 인증 및 정보 조회 (공통 로직 사용)
            $authResult = $this->authenticateAndGetPartner($request, 'sales_approval');
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

            // 상태 확인: pending 상태만 승인 가능
            if ($sale->status !== 'pending') {
                return redirect()->route('home.partner.sales.show', $sale->id)
                    ->with('error', '대기 상태의 매출만 승인할 수 있습니다.');
            }

            // 권한 확인: 상위 파트너이거나 관리자 권한이 있는지 확인
            $hasApprovalAccess = $this->checkApprovalAccess($partnerUser, $sale);

            if (!$hasApprovalAccess) {
                return redirect()->route('home.partner.sales.show', $sale->id)
                    ->with('error', '해당 매출의 승인 권한이 없습니다.');
            }

            // 승인 처리
            $sale->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'status_reason' => '관리자에 의해 승인됨',
                'approved_by' => $partnerUser->id,
                'approved_at' => now(),
                'updated_by' => $partnerUser->id,
                'updated_at' => now()
            ]);

            // 로그 기록
            \Log::info('Partner sales approved', [
                'sale_id' => $sale->id,
                'partner_id' => $sale->partner_id,
                'approved_by_partner_id' => $partnerUser->id,
                'approved_by' => $partnerUser->name,
                'user_id' => $user->id,
                'original_amount' => $sale->amount,
                'product_name' => $sale->title,
                'approval_type' => 'sales_approved',
                'approved_at' => now()
            ]);

            return redirect()->route('home.partner.sales.show', $sale->id)
                ->with('success', '매출이 승인되었습니다.');

        } catch (\Exception $e) {
            \Log::error('Partner sales approval error: ' . $e->getMessage(), [
                'user_id' => $user->id ?? 'unknown',
                'user_uuid' => $user->uuid ?? 'unknown',
                'sale_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('home.partner.sales.show', $id)
                ->with('error', '매출 승인 중 오류가 발생했습니다.');
        }
    }

    /**
     * 매출 반려 처리 (pending → rejected)
     */
    public function reject(Request $request, $id)
    {
        try {
            // 파트너 인증 및 정보 조회 (공통 로직 사용)
            $authResult = $this->authenticateAndGetPartner($request, 'sales_rejection');
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

            // 상태 확인: pending 상태만 반려 가능
            if ($sale->status !== 'pending') {
                return redirect()->route('home.partner.sales.show', $sale->id)
                    ->with('error', '대기 상태의 매출만 반려할 수 있습니다.');
            }

            // 권한 확인: 상위 파트너이거나 관리자 권한이 있는지 확인
            $hasApprovalAccess = $this->checkApprovalAccess($partnerUser, $sale);

            if (!$hasApprovalAccess) {
                return redirect()->route('home.partner.sales.show', $sale->id)
                    ->with('error', '해당 매출의 반려 권한이 없습니다.');
            }

            // 반려 이유 입력 (요청사항)
            $rejectReason = $request->input('reject_reason', '관리자에 의해 반려됨');

            // 반려 처리
            $sale->update([
                'status' => 'rejected',
                'rejected_at' => now(),
                'status_reason' => '반려됨: ' . $rejectReason,
                'rejected_by' => $partnerUser->id,
                'updated_by' => $partnerUser->id,
                'updated_at' => now()
            ]);

            // 로그 기록
            \Log::info('Partner sales rejected', [
                'sale_id' => $sale->id,
                'partner_id' => $sale->partner_id,
                'rejected_by_partner_id' => $partnerUser->id,
                'rejected_by' => $partnerUser->name,
                'user_id' => $user->id,
                'original_amount' => $sale->amount,
                'product_name' => $sale->title,
                'rejection_type' => 'sales_rejected',
                'reject_reason' => $rejectReason,
                'rejected_at' => now()
            ]);

            return redirect()->route('home.partner.sales.show', $sale->id)
                ->with('success', '매출이 반려되었습니다.');

        } catch (\Exception $e) {
            \Log::error('Partner sales rejection error: ' . $e->getMessage(), [
                'user_id' => $user->id ?? 'unknown',
                'user_uuid' => $user->uuid ?? 'unknown',
                'sale_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('home.partner.sales.show', $id)
                ->with('error', '매출 반려 중 오류가 발생했습니다.');
        }
    }

    /**
     * 승인 권한 확인
     */
    private function checkApprovalAccess($approverPartner, $sale)
    {
        try {
            // 관리자 권한이 있는 경우
            if ($approverPartner->is_admin || $approverPartner->can_approve_sales) {
                return true;
            }

            // 상위 파트너인지 확인
            if (class_exists('\\Jiny\\Partner\\Models\\PartnerNetworkRelationship')) {
                return \Jiny\Partner\Models\PartnerNetworkRelationship::where('parent_id', $approverPartner->id)
                    ->where('child_id', $sale->partner_id)
                    ->where('is_active', true)
                    ->exists();
            }

            // 본인의 매출이면 승인 불가 (다른 사람이 승인해야 함)
            return false;

        } catch (\Exception $e) {
            \Log::error('Failed to check approval access: ' . $e->getMessage());
            return false;
        }
    }
}