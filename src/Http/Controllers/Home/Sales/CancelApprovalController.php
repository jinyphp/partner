<?php

namespace Jiny\Partner\Http\Controllers\Home\Sales;

use Jiny\Partner\Http\Controllers\PartnerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Jiny\Partner\Models\PartnerSales;
use Jiny\Partner\Models\PartnerUser;

class CancelApprovalController extends PartnerController
{
    /**
     * 매출 취소 승인 처리
     */
    public function approve(Request $request, $id)
    {
        try {
            // 파트너 인증 및 정보 조회 (공통 로직 사용)
            $authResult = $this->authenticateAndGetPartner($request, 'sales_cancel_approval');
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

            // 상태 확인: cancel_pending 상태만 승인 가능
            if ($sale->status !== 'cancel_pending') {
                return redirect()->route('home.partner.sales.show', $sale->id)
                    ->with('error', '취소 요청 대기 상태의 매출만 승인할 수 있습니다.');
            }

            // 권한 확인: 상위 파트너이거나 관리자 권한이 있는지 확인
            $hasApprovalAccess = $this->checkApprovalAccess($partnerUser, $sale);

            if (!$hasApprovalAccess) {
                return redirect()->route('home.partner.sales.show', $sale->id)
                    ->with('error', '해당 매출의 취소 승인 권한이 없습니다.');
            }

            // 취소 승인 처리
            $sale->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'status_reason' => $sale->status_reason . ' - 승인됨',
                'approved_by' => $partnerUser->id,
                'approved_at' => now(),
                'updated_by' => $partnerUser->id,
                'updated_at' => now()
            ]);

            // 로그 기록
            \Log::info('Partner sales cancellation approved', [
                'sale_id' => $sale->id,
                'partner_id' => $sale->partner_id,
                'approved_by_partner_id' => $partnerUser->id,
                'approved_by' => $partnerUser->name,
                'user_id' => $user->id,
                'original_amount' => $sale->amount,
                'product_name' => $sale->title,
                'approval_type' => 'cancellation_approved',
                'cancel_requested_at' => $sale->cancel_requested_at,
                'approved_at' => now()
            ]);

            return redirect()->route('home.partner.sales.show', $sale->id)
                ->with('success', '매출 취소가 승인되었습니다.');

        } catch (\Exception $e) {
            \Log::error('Partner sales cancel approval error: ' . $e->getMessage(), [
                'user_id' => $user->id ?? 'unknown',
                'user_uuid' => $user->uuid ?? 'unknown',
                'sale_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('home.partner.sales.show', $id)
                ->with('error', '매출 취소 승인 중 오류가 발생했습니다.');
        }
    }

    /**
     * 매출 취소 거부 처리
     */
    public function reject(Request $request, $id)
    {
        try {
            // 파트너 인증 및 정보 조회 (공통 로직 사용)
            $authResult = $this->authenticateAndGetPartner($request, 'sales_cancel_rejection');
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

            // 상태 확인: cancel_pending 상태만 거부 가능
            if ($sale->status !== 'cancel_pending') {
                return redirect()->route('home.partner.sales.show', $sale->id)
                    ->with('error', '취소 요청 대기 상태의 매출만 거부할 수 있습니다.');
            }

            // 권한 확인: 상위 파트너이거나 관리자 권한이 있는지 확인
            $hasApprovalAccess = $this->checkApprovalAccess($partnerUser, $sale);

            if (!$hasApprovalAccess) {
                return redirect()->route('home.partner.sales.show', $sale->id)
                    ->with('error', '해당 매출의 취소 거부 권한이 없습니다.');
            }

            // 거부 이유 입력 (요청사항)
            $rejectReason = $request->input('reject_reason', '승인자에 의해 거부됨');

            // 원래 상태로 복원 (취소 요청 전 상태)
            $originalStatus = $this->getOriginalStatusFromReason($sale->status_reason);

            $sale->update([
                'status' => $originalStatus,
                'status_reason' => $sale->status_reason . ' - 거부됨: ' . $rejectReason,
                'rejected_by' => $partnerUser->id,
                'rejected_at' => now(),
                'cancel_requested_at' => null, // 취소 요청 시간 초기화
                'updated_by' => $partnerUser->id,
                'updated_at' => now()
            ]);

            // 로그 기록
            \Log::info('Partner sales cancellation rejected', [
                'sale_id' => $sale->id,
                'partner_id' => $sale->partner_id,
                'rejected_by_partner_id' => $partnerUser->id,
                'rejected_by' => $partnerUser->name,
                'user_id' => $user->id,
                'original_amount' => $sale->amount,
                'product_name' => $sale->title,
                'rejection_type' => 'cancellation_rejected',
                'reject_reason' => $rejectReason,
                'restored_status' => $originalStatus,
                'rejected_at' => now()
            ]);

            return redirect()->route('home.partner.sales.show', $sale->id)
                ->with('success', '매출 취소 요청이 거부되었습니다.');

        } catch (\Exception $e) {
            \Log::error('Partner sales cancel rejection error: ' . $e->getMessage(), [
                'user_id' => $user->id ?? 'unknown',
                'user_uuid' => $user->uuid ?? 'unknown',
                'sale_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('home.partner.sales.show', $id)
                ->with('error', '매출 취소 거부 중 오류가 발생했습니다.');
        }
    }

    /**
     * 취소 승인 권한 확인
     */
    private function checkApprovalAccess($approverPartner, $sale)
    {
        try {
            // 관리자 권한이 있는 경우
            if ($approverPartner->is_admin || $approverPartner->can_approve_cancellations) {
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

    /**
     * 매출 취소 복원 처리 (취소된 매출을 원래 상태로 되돌림)
     */
    public function restore(Request $request, $id)
    {
        try {
            // 세션 인증 확인
            $user = $this->auth($request);
            if (!$user) {
                return redirect()->route('login')->with('error', '로그인이 필요합니다.');
            }

            // 파트너 사용자 정보 조회 (UUID 기반)
            $partnerUser = PartnerUser::where('user_uuid', $user->uuid)->first();

            if (!$partnerUser) {
                return redirect()->route('home.partner.intro')
                    ->with('info', '파트너 프로그램에 먼저 가입해 주세요.');
            }

            // 매출 정보 조회
            $sale = PartnerSales::find($id);

            if (!$sale) {
                return redirect()->route('home.partner.sales.history')
                    ->with('error', '매출 정보를 찾을 수 없습니다.');
            }

            // 상태 확인: cancelled 상태만 복원 가능
            if ($sale->status !== 'cancelled') {
                return redirect()->route('home.partner.sales.show', $sale->id)
                    ->with('error', '취소된 상태의 매출만 복원할 수 있습니다.');
            }

            // 권한 확인: 상위 파트너이거나 관리자 권한이 있는지 확인
            $hasApprovalAccess = $this->checkApprovalAccess($partnerUser, $sale);

            if (!$hasApprovalAccess) {
                return redirect()->route('home.partner.sales.show', $sale->id)
                    ->with('error', '해당 매출의 복원 권한이 없습니다.');
            }

            // 원래 상태 추출
            $originalStatus = $this->getOriginalStatusFromReason($sale->status_reason);

            // 복원 이유 설정
            $restoreReason = '관리자에 의해 취소가 복원됨 - 원래 상태: ' .
                ($originalStatus === 'confirmed' ? '확정' : '대기');

            // 매출 복원 처리
            $sale->update([
                'status' => $originalStatus,
                'status_reason' => $restoreReason,
                'cancelled_at' => null,
                'approved_by' => null,
                'approved_at' => null,
                'restored_by' => $partnerUser->id,
                'restored_at' => now(),
                'updated_by' => $partnerUser->id,
                'updated_at' => now()
            ]);

            // 로그 기록
            \Log::info('Partner sales cancellation restored', [
                'sale_id' => $sale->id,
                'partner_id' => $sale->partner_id,
                'restored_by_partner_id' => $partnerUser->id,
                'restored_by' => $partnerUser->name,
                'user_id' => $user->id,
                'original_amount' => $sale->amount,
                'product_name' => $sale->title,
                'restore_type' => 'cancellation_restored',
                'restored_to_status' => $originalStatus,
                'restored_at' => now()
            ]);

            return redirect()->route('home.partner.sales.show', $sale->id)
                ->with('success', '매출 취소가 복원되었습니다. 상태가 "' .
                    ($originalStatus === 'confirmed' ? '확정' : '대기') . '"으로 변경되었습니다.');

        } catch (\Exception $e) {
            \Log::error('Partner sales restore error: ' . $e->getMessage(), [
                'user_id' => $user->id ?? 'unknown',
                'user_uuid' => $user->uuid ?? 'unknown',
                'sale_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('home.partner.sales.show', $id)
                ->with('error', '매출 복원 중 오류가 발생했습니다.');
        }
    }

    /**
     * 상태 이유에서 원래 상태 추출
     */
    private function getOriginalStatusFromReason($statusReason)
    {
        if (str_contains($statusReason, '확정 매출')) {
            return 'confirmed';
        } elseif (str_contains($statusReason, '반려 매출')) {
            return 'rejected';
        } elseif (str_contains($statusReason, '대기 매출')) {
            return 'pending';
        }

        // 기본값은 pending
        return 'pending';
    }
}