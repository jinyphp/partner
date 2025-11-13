<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerApproval;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerInterview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class UpdateInterviewController extends Controller
{
    /**
     * Handle the request
     */
    public function __invoke(Request $request, $id = null)
    {
        try {
            // 요청 데이터 검증
            $validator = Validator::make($request->all(), [
                'interview_date' => 'required|date|after:now',
                'interview_type' => 'nullable|in:phone,video,in_person,written',
                'interview_location' => 'nullable|string|max:255',
                'meeting_url' => 'nullable|url|max:255',
                'meeting_password' => 'nullable|string|max:50',
                'interview_notes' => 'nullable|string|max:1000',
                'interviewer_id' => 'nullable|exists:users,id',
                'duration_minutes' => 'nullable|integer|min:15|max:240'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => '입력 데이터가 올바르지 않습니다.',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // 지원서 조회
            $application = PartnerApplication::findOrFail($id);

            // 기존 면접 일정이 있는지 확인
            if ($application->application_status !== 'interview') {
                return response()->json([
                    'success' => false,
                    'message' => '면접 일정이 설정되지 않은 지원서입니다.'
                ], 400);
            }

            // PartnerInterview 테이블에서 기존 면접 찾기
            $interview = PartnerInterview::where('application_id', $application->id)
                ->whereIn('interview_status', ['scheduled', 'in_progress'])
                ->first();

            $oldInterviewDate = $application->interview_date;
            $oldLocation = $application->interview_feedback['location'] ?? null;

            // 면접 일정 업데이트
            $application->update([
                'interview_date' => $request->interview_date,
                'interview_notes' => $request->interview_notes
            ]);

            // PartnerInterview 테이블도 업데이트
            if ($interview) {
                $updateData = [
                    'scheduled_at' => $request->interview_date,
                    'interview_type' => $request->interview_type ?? $interview->interview_type,
                    'duration_minutes' => $request->duration_minutes ?? $interview->duration_minutes,
                    'interviewer_id' => $request->interviewer_id ?? $interview->interviewer_id,
                    'meeting_location' => $request->interview_location,
                    'meeting_url' => $request->meeting_url,
                    'meeting_password' => $request->meeting_password,
                    'preparation_notes' => $request->interview_notes,
                    'updated_by' => auth()->id()
                ];

                // 일정이 변경된 경우 상태 업데이트
                if ($interview->scheduled_at != $request->interview_date) {
                    $updateData['interview_status'] = 'rescheduled';
                }

                $interview->update($updateData);

                // 면접 로그 추가
                $interview->addLog('면접 일정 수정', '승인 관리에서 면접 일정이 수정되었습니다.', [
                    'old_date' => $oldInterviewDate,
                    'new_date' => $request->interview_date,
                    'old_location' => $oldLocation,
                    'new_location' => $request->interview_location,
                    'updated_by' => auth()->user()->name ?? '알 수 없음'
                ]);
            }

            // 면접 장소가 있으면 피드백에 저장 (기존 로직 유지)
            if ($request->interview_location) {
                $feedback = $application->interview_feedback ?? [];
                $feedback['location'] = $request->interview_location;
                $application->update(['interview_feedback' => $feedback]);
            }

            // 관리자 노트 추가 (변경 이력)
            $adminNotes = $application->admin_notes ?? '';
            $newNote = sprintf(
                "[%s] 면접 일정 수정:\n  기존: %s %s\n  변경: %s %s%s\n",
                now()->format('Y-m-d H:i'),
                $oldInterviewDate ? $oldInterviewDate->format('Y-m-d H:i') : 'N/A',
                $oldLocation ? "({$oldLocation})" : '',
                $request->interview_date,
                $request->interview_location ? "({$request->interview_location})" : '',
                $interview ? " (면접 ID: {$interview->id})" : ''
            );
            $application->update(['admin_notes' => $adminNotes . $newNote]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '면접 일정이 성공적으로 수정되었습니다.',
                'data' => [
                    'application_id' => $application->id,
                    'interview_id' => $interview?->id,
                    'interview_date' => $application->interview_date,
                    'interview_location' => $request->interview_location,
                    'interview_notes' => $application->interview_notes,
                    'status' => $application->application_status,
                    'changes' => [
                        'old_date' => $oldInterviewDate,
                        'new_date' => $request->interview_date,
                        'old_location' => $oldLocation,
                        'new_location' => $request->interview_location
                    ],
                    'interview_url' => $interview ? route('admin.partner.interview.show', $interview->id) : null
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            \Log::error('면접 일정 수정 실패', [
                'error' => $e->getMessage(),
                'application_id' => $id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => '면접 일정 수정 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }
}
