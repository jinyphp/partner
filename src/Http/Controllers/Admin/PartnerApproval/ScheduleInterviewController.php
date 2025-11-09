<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerApproval;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ScheduleInterviewController extends Controller
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
                'interview_location' => 'nullable|string|max:255',
                'interview_notes' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => '입력 데이터가 올바르지 않습니다.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // 지원서 조회
            $application = PartnerApplication::findOrFail($id);

            // 면접 일정 설정
            $application->scheduleInterview(
                $request->interview_date,
                $request->interview_notes
            );

            // 면접 장소가 있으면 피드백에 저장
            if ($request->interview_location) {
                $feedback = $application->interview_feedback ?? [];
                $feedback['location'] = $request->interview_location;
                $application->update(['interview_feedback' => $feedback]);
            }

            // 관리자 노트 추가
            $adminNotes = $application->admin_notes ?? '';
            $newNote = sprintf(
                "[%s] 면접 일정 설정: %s %s\n",
                now()->format('Y-m-d H:i'),
                $request->interview_date,
                $request->interview_location ? "({$request->interview_location})" : ''
            );
            $application->update(['admin_notes' => $adminNotes . $newNote]);

            return response()->json([
                'success' => true,
                'message' => '면접 일정이 성공적으로 설정되었습니다.',
                'data' => [
                    'application_id' => $application->id,
                    'interview_date' => $application->interview_date,
                    'interview_location' => $request->interview_location,
                    'interview_notes' => $application->interview_notes,
                    'status' => $application->application_status
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '면접 일정 설정 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }
}
