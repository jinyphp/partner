<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerApproval;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RevokeController extends Controller
{
    /**
     * 파트너 신청 승인 취소 처리
     */
    public function __invoke(Request $request, $id)
    {
        $application = PartnerApplication::findOrFail($id);

        // 승인 취소 가능한 상태인지 확인
        if ($application->application_status !== 'approved') {
            $errorMessage = '승인된 신청서만 승인 취소할 수 있습니다.';
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['success' => false, 'message' => $errorMessage], 400);
            }
            return back()->with('error', $errorMessage);
        }

        $validatedData = $request->validate([
            'revoke_reason' => 'required|string|max:1000',
            'notify_user' => 'nullable|in:0,1'
        ], [
            'revoke_reason.required' => '취소 사유를 입력해주세요.',
            'revoke_reason.max' => '취소 사유는 1000자 이하로 입력해주세요.'
        ]);

        try {
            DB::beginTransaction();

            // 기존 파트너 회원 찾기
            $partnerUser = PartnerUser::where('user_uuid', $application->user_uuid)->first();

            if ($partnerUser) {
                // 파트너 회원 데이터 백업을 위한 로그
                \Log::info('Partner user being revoked', [
                    'application_id' => $application->id,
                    'partner_user_id' => $partnerUser->id,
                    'user_uuid' => $application->user_uuid,
                    'revoked_by' => Auth::id(),
                    'reason' => $validatedData['revoke_reason']
                ]);

                // 파트너 사용자 삭제
                $partnerUser->delete();
            }

            // 신청서 상태를 검토 중으로 되돌리기
            $application->update([
                'application_status' => 'reviewing',
                'approval_date' => null,
                'approved_by' => null,
                'admin_notes' => ($application->admin_notes ? $application->admin_notes . "\n\n" : '') .
                              '[' . now()->format('Y-m-d H:i:s') . '] 승인 취소: ' . $validatedData['revoke_reason'],
                'updated_at' => now()
            ]);

            // 승인 취소 로그 추가 (시스템 로그로 대체)
            \Log::info('Partner approval revoked', [
                'action' => 'approval_revoked',
                'message' => '파트너 승인이 취소되었습니다.',
                'application_id' => $application->id,
                'old_status' => 'approved',
                'new_status' => 'reviewing',
                'reason' => $validatedData['revoke_reason'],
                'partner_user_deleted' => $partnerUser ? true : false,
                'partner_user_id' => $partnerUser ? $partnerUser->id : null,
                'revoked_by' => Auth::id(),
                'revoked_at' => now()
            ]);

            DB::commit();

            // 사용자 알림 (옵션)
            if ($request->input('notify_user') == '1') {
                $this->sendRevokeNotification($application, $validatedData['revoke_reason']);
            }

            // 성공 메시지
            $message = '파트너 승인이 취소되었습니다.';
            if ($partnerUser) {
                $message .= ' 파트너 회원 계정도 삭제되었습니다.';
            }

            // AJAX 요청인지 확인
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => [
                        'application_id' => $application->id,
                        'status' => $application->application_status,
                        'partner_user_deleted' => $partnerUser ? true : false,
                        'revoke_date' => now()->format('Y-m-d H:i:s')
                    ]
                ]);
            }

            return redirect()->route('admin.partner.approval.show', $application->id)
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Partner application revoke failed: ' . $e->getMessage(), [
                'application_id' => $application->id,
                'admin_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // AJAX 요청인지 확인
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => '승인 취소 처리 중 오류가 발생했습니다: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', '승인 취소 처리 중 오류가 발생했습니다: ' . $e->getMessage());
        }
    }

    /**
     * 승인 취소 알림 발송
     */
    private function sendRevokeNotification($application, $reason)
    {
        try {
            // 이메일 알림 (실제 구현 시 Mail 파사드 사용)
            // Mail::to($application->user->email)->send(new PartnerApprovalRevokeMail($application, $reason));

            // 시스템 알림 (선택적)
            // Notification::send($application->user, new PartnerApprovalRevokedNotification($application, $reason));

            \Log::info('Partner approval revoke notification sent', [
                'application_id' => $application->id,
                'user_email' => $application->user->email ?? 'unknown',
                'reason' => $reason
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to send approval revoke notification: ' . $e->getMessage());
        }
    }
}