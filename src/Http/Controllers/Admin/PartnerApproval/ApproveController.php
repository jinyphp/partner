<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerApproval;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApproveController extends Controller
{
    /**
     * 파트너 신청 승인 처리
     */
    public function __invoke(Request $request, $id)
    {
        $application = PartnerApplication::findOrFail($id);

        // 승인 가능한 상태인지 확인
        if (!in_array($application->application_status, ['submitted', 'reviewing', 'interview', 'reapplied'])) {
            $errorMessage = '현재 상태에서는 승인할 수 없습니다.';
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['success' => false, 'message' => $errorMessage], 400);
            }
            return back()->with('error', $errorMessage);
        }

        // 이미 파트너 회원인지 확인 (UUID 기반)
        $existingPartner = \Jiny\Partner\Models\PartnerUser::where('user_uuid', $application->user_uuid)
            ->exists();

        if ($existingPartner) {
            $errorMessage = '이미 파트너로 등록된 사용자입니다.';
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['success' => false, 'message' => $errorMessage], 400);
            }
            return back()->with('error', $errorMessage);
        }

        $validatedData = $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
            'notify_user' => 'boolean',
            'welcome_message' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            // 승인 처리 및 파트너 회원 생성
            $partnerUser = $application->approve(Auth::id());

            // 관리자 메모 업데이트
            if ($validatedData['admin_notes']) {
                $application->update(['admin_notes' => $validatedData['admin_notes']]);
            }

            DB::commit();

            // 사용자 알림 (옵션)
            if ($request->boolean('notify_user')) {
                $this->sendApprovalNotification($application, $validatedData['welcome_message'] ?? null);
            }

            // 성공 메시지와 함께 리디렉션
            $message = '파트너 신청이 승인되었습니다. 파트너 회원으로 등록되었습니다.';
            if ($partnerUser) {
                $message .= ' (파트너 ID: ' . $partnerUser->id . ')';
            }

            // AJAX 요청인지 확인
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => [
                        'application_id' => $application->id,
                        'partner_user_id' => $partnerUser ? $partnerUser->id : null,
                        'status' => $application->application_status,
                        'approval_date' => $application->approval_date
                    ]
                ]);
            }

            return redirect()->route('admin.partner.approval.show', $application->id)
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Partner application approval failed: ' . $e->getMessage(), [
                'application_id' => $application->id,
                'admin_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            // AJAX 요청인지 확인
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => '승인 처리 중 오류가 발생했습니다: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', '승인 처리 중 오류가 발생했습니다: ' . $e->getMessage());
        }
    }

    /**
     * 승인 알림 발송
     */
    private function sendApprovalNotification($application, $welcomeMessage = null)
    {
        try {
            // 이메일 알림 (실제 구현 시 Mail 파사드 사용)
            // Mail::to($application->user->email)->send(new PartnerApprovalMail($application, $welcomeMessage));

            // 시스템 알림 (선택적)
            // Notification::send($application->user, new PartnerApprovedNotification($application));

            \Log::info('Partner approval notification sent', [
                'application_id' => $application->id,
                'user_email' => $application->user->email
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to send approval notification: ' . $e->getMessage());
        }
    }
}