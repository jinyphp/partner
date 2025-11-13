<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerApproval;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerInterview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

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
            $application = PartnerApplication::with(['user', 'referrerPartner'])->findOrFail($id);

            // 기존 면접이 있는지 확인
            $existingInterview = $application->interviews()
                ->whereIn('interview_status', ['scheduled', 'in_progress'])
                ->first();

            if ($existingInterview) {
                return response()->json([
                    'success' => false,
                    'message' => '이미 예정된 면접이 있습니다.'
                ], 422);
            }

            // 면접 일정 설정
            $application->scheduleInterview(
                $request->interview_date,
                $request->interview_notes
            );

            // PartnerInterview 테이블에 기록 생성
            $interviewData = [
                // 지원자 정보
                'user_id' => $application->user_id,
                'user_uuid' => $application->user_uuid,
                'shard_number' => $application->shard_number ?? 0,
                'user_table' => $application->shard_number ? 'user_' . str_pad($application->shard_number, 3, '0', STR_PAD_LEFT) : 'users',
                'email' => $application->personal_info['email'] ?? ($application->user->email ?? ''),
                'name' => $application->personal_info['name'] ?? ($application->user->name ?? ''),

                // 신청서 정보
                'application_id' => $application->id,

                // 추천 파트너 정보
                'referrer_partner_id' => $application->referrer_partner_id,
                'referrer_code' => $application->referrerPartner->partner_code ?? null,
                'referrer_name' => $application->referrerPartner->name ?? null,

                // 면접 정보
                'interview_status' => 'scheduled',
                'interview_type' => $request->interview_type ?? 'video',
                'interview_round' => 'first',
                'scheduled_at' => $request->interview_date,
                'duration_minutes' => $request->duration_minutes ?? 60,
                'interviewer_id' => $request->interviewer_id,

                // 장소/연결 정보
                'meeting_location' => $request->interview_location,
                'meeting_url' => $request->meeting_url,
                'meeting_password' => $request->meeting_password,
                'preparation_notes' => $request->interview_notes,

                // 관리 정보
                'created_by' => auth()->id()
            ];

            $interview = PartnerInterview::create($interviewData);

            // 면접 로그 추가
            $interview->addLog('면접 일정', '승인 관리에서 면접이 예정되었습니다.', [
                'scheduled_at' => $request->interview_date,
                'type' => $request->interview_type ?? 'video',
                'location' => $request->interview_location,
                'created_by' => auth()->user()->name ?? '알 수 없음'
            ]);

            // 면접 장소가 있으면 피드백에 저장 (기존 로직 유지)
            if ($request->interview_location) {
                $feedback = $application->interview_feedback ?? [];
                $feedback['location'] = $request->interview_location;
                $application->update(['interview_feedback' => $feedback]);
            }

            // 관리자 노트 추가
            $adminNotes = $application->admin_notes ?? '';
            $newNote = sprintf(
                "[%s] 면접 일정 설정: %s %s (면접 ID: %d)\n",
                now()->format('Y-m-d H:i'),
                $request->interview_date,
                $request->interview_location ? "({$request->interview_location})" : '',
                $interview->id
            );
            $application->update(['admin_notes' => $adminNotes . $newNote]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '면접 일정이 성공적으로 설정되었습니다.',
                'data' => [
                    'application_id' => $application->id,
                    'interview_id' => $interview->id,
                    'interview_date' => $application->interview_date,
                    'interview_location' => $request->interview_location,
                    'interview_notes' => $application->interview_notes,
                    'status' => $application->application_status,
                    'interview_url' => route('admin.partner.interview.show', $interview->id)
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            \Log::error('면접 일정 설정 실패', [
                'error' => $e->getMessage(),
                'application_id' => $id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => '면접 일정 설정 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }
}
