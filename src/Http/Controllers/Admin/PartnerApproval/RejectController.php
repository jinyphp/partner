<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerApproval;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RejectController extends Controller
{
    /**
     * 파트너 신청 거부 처리
     */
    public function __invoke(Request $request, $id)
    {
        $application = PartnerApplication::findOrFail($id);

        // 거부 가능한 상태인지 확인
        if (!in_array($application->application_status, ['submitted', 'reviewing', 'interview', 'reapplied'])) {
            return back()->with('error', '현재 상태에서는 거부할 수 없습니다.');
        }

        $validatedData = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
            'admin_notes' => 'nullable|string|max:1000',
            'notify_user' => 'nullable|in:0,1',
            'allow_reapply' => 'nullable|in:0,1',
            'feedback_message' => 'nullable|string|max:500'
        ], [
            'rejection_reason.required' => '거부 사유를 입력해주세요.',
            'rejection_reason.max' => '거부 사유는 1000자를 초과할 수 없습니다.'
        ]);

        try {
            DB::beginTransaction();

            // 거부 처리
            $application->reject(Auth::id(), $validatedData['rejection_reason']);

            // 관리자 메모 업데이트
            if ($validatedData['admin_notes']) {
                $application->update(['admin_notes' => $validatedData['admin_notes']]);
            }

            DB::commit();

            // 사용자 알림 (옵션)
            if ($request->input('notify_user') == '1') {
                $this->sendRejectionNotification(
                    $application,
                    $validatedData['feedback_message'] ?? null,
                    $request->input('allow_reapply') == '1'
                );
            }

            return redirect()->route('admin.partner.approval.show', $application->id)
                ->with('success', '파트너 신청이 거부되었습니다.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Partner application rejection failed: ' . $e->getMessage(), [
                'application_id' => $application->id,
                'admin_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return back()->with('error', '거부 처리 중 오류가 발생했습니다: ' . $e->getMessage());
        }
    }

    /**
     * 거부 알림 발송
     */
    private function sendRejectionNotification($application, $feedbackMessage = null, $allowReapply = false)
    {
        try {
            // 이메일 알림 (실제 구현 시 Mail 파사드 사용)
            // Mail::to($application->user->email)->send(new PartnerRejectionMail($application, $feedbackMessage, $allowReapply));

            // 시스템 알림 (선택적)
            // Notification::send($application->user, new PartnerRejectedNotification($application));

            \Log::info('Partner rejection notification sent', [
                'application_id' => $application->id,
                'user_email' => $application->user->email,
                'allow_reapply' => $allowReapply
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to send rejection notification: ' . $e->getMessage());
        }
    }
}