<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerApproval;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InterviewController extends Controller
{
    /**
     * 파트너 신청 면접 설정 처리
     */
    public function __invoke(Request $request, $id)
    {
        $application = PartnerApplication::findOrFail($id);

        // 면접 설정 가능한 상태인지 확인
        if (!in_array($application->application_status, ['submitted', 'reviewing', 'reapplied'])) {
            return back()->with('error', '현재 상태에서는 면접 설정할 수 없습니다.');
        }

        $validatedData = $request->validate([
            'interview_date' => 'required|date|after:now',
            'interview_location' => 'nullable|string|max:255',
            'interview_notes' => 'nullable|string|max:1000',
            'notify_user' => 'boolean'
        ]);

        try {
            DB::beginTransaction();

            // 면접 정보 업데이트
            $application->update([
                'application_status' => 'interview',
                'interview_date' => $validatedData['interview_date'],
                'interview_notes' => $validatedData['interview_notes'] ?? null,
                'admin_notes' => $application->admin_notes . "\n[면접 설정] " . now()->format('Y-m-d H:i') . " - 면접 일정 설정됨"
            ]);

            // 면접 관련 상세 정보를 JSON으로 저장
            $interviewData = [
                'date' => $validatedData['interview_date'],
                'location' => $validatedData['interview_location'] ?? null,
                'notes' => $validatedData['interview_notes'] ?? null,
                'scheduled_by' => Auth::id(),
                'scheduled_at' => now()->toISOString()
            ];

            // interview_feedback 필드에 면접 설정 정보 저장
            $application->update([
                'interview_feedback' => $interviewData
            ]);

            DB::commit();

            // 사용자 알림 (옵션)
            if ($request->boolean('notify_user')) {
                $this->sendInterviewNotification($application, $interviewData);
            }

            return redirect()->route('admin.partner.approval.show', $application->id)
                ->with('success', '면접 일정이 설정되었습니다.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Partner interview scheduling failed: ' . $e->getMessage(), [
                'application_id' => $application->id,
                'admin_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return back()->with('error', '면접 설정 중 오류가 발생했습니다: ' . $e->getMessage());
        }
    }

    /**
     * 면접 설정 알림 발송
     */
    private function sendInterviewNotification($application, $interviewData)
    {
        try {
            // 이메일 알림 (실제 구현 시 Mail 파사드 사용)
            // Mail::to($application->user->email)->send(new PartnerInterviewScheduledMail($application, $interviewData));

            // 시스템 알림 (선택적)
            // Notification::send($application->user, new PartnerInterviewScheduledNotification($application));

            \Log::info('Partner interview notification sent', [
                'application_id' => $application->id,
                'user_email' => $application->user->email,
                'interview_date' => $interviewData['date']
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to send interview notification: ' . $e->getMessage());
        }
    }
}