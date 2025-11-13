<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerApproval;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerInterview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StatusController extends Controller
{
    /**
     * 파트너 신청 상태 변경 처리
     */
    public function __invoke(Request $request, $id = null)
    {
        try {
            // 요청 데이터 검증
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:submitted,reviewing,interview,approved,rejected,reapplied',
                'admin_notes' => 'nullable|string|max:1000',
                'interview_date' => 'required_if:status,interview|nullable|date|after:now',
                'interview_type' => 'nullable|in:phone,video,in_person,written',
                'interview_location' => 'nullable|string|max:255',
                'interview_notes' => 'nullable|string|max:1000',
                'notify_user' => 'nullable|boolean'
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
            $oldStatus = $application->application_status;
            $newStatus = $request->status;

            // 상태 변경
            $updateData = [
                'application_status' => $newStatus,
                'updated_by' => Auth::id()
            ];

            // 관리자 노트 추가
            if ($request->admin_notes) {
                $adminNotes = $application->admin_notes ?? '';
                $newNote = sprintf(
                    "[%s] 상태 변경: %s → %s\n%s\n\n",
                    now()->format('Y-m-d H:i'),
                    $oldStatus,
                    $newStatus,
                    $request->admin_notes
                );
                $updateData['admin_notes'] = $adminNotes . $newNote;
            }

            // 면접 상태로 변경하는 경우 면접 관련 정보 설정
            if ($newStatus === 'interview') {
                if ($request->interview_date) {
                    $updateData['interview_date'] = $request->interview_date;
                    $updateData['interview_notes'] = $request->interview_notes;

                    // 면접 피드백 정보 업데이트
                    $interviewFeedback = $application->interview_feedback ?? [];
                    $interviewFeedback = array_merge($interviewFeedback, [
                        'date' => $request->interview_date,
                        'type' => $request->interview_type ?? 'video',
                        'location' => $request->interview_location,
                        'notes' => $request->interview_notes,
                        'scheduled_by' => Auth::id(),
                        'scheduled_at' => now()->toISOString()
                    ]);
                    $updateData['interview_feedback'] = $interviewFeedback;

                    // partner_interviews 테이블에 면접 기록 생성
                    $this->createInterviewRecord($application, $request);
                }
            }

            // 지원서 업데이트
            $application->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '상태가 성공적으로 변경되었습니다.',
                'data' => [
                    'application_id' => $application->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'interview_date' => $application->interview_date,
                    'admin_notes' => $application->admin_notes
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            \Log::error('파트너 신청 상태 변경 실패', [
                'error' => $e->getMessage(),
                'application_id' => $id,
                'requested_status' => $request->status,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => '상태 변경 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 면접 기록 생성
     */
    private function createInterviewRecord(PartnerApplication $application, Request $request)
    {
        // 기존에 scheduled 상태인 면접이 있는지 확인
        $existingInterview = PartnerInterview::where('application_id', $application->id)
            ->where('interview_status', 'scheduled')
            ->first();

        if ($existingInterview) {
            // 기존 면접 기록이 있으면 업데이트
            $existingInterview->update([
                'scheduled_at' => $request->interview_date,
                'interview_type' => $request->interview_type ?? 'video',
                'meeting_location' => $request->interview_location,
                'preparation_notes' => $request->interview_notes,
                'updated_by' => Auth::id()
            ]);

            // 면접 로그 추가
            $existingInterview->addLog('면접 일정 변경', '승인 관리에서 면접 일정이 변경되었습니다.', [
                'scheduled_at' => $request->interview_date,
                'type' => $request->interview_type ?? 'video',
                'location' => $request->interview_location,
                'updated_by' => Auth::user()->name ?? '알 수 없음'
            ]);

            return $existingInterview;
        }

        // 새로운 면접 기록 생성
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
            'duration_minutes' => 60,

            // 장소/연결 정보
            'meeting_location' => $request->interview_location,
            'preparation_notes' => $request->interview_notes,

            // 관리 정보
            'created_by' => Auth::id()
        ];

        $interview = PartnerInterview::create($interviewData);

        // 면접 로그 추가
        $interview->addLog('면접 일정', '승인 관리에서 면접이 예정되었습니다.', [
            'scheduled_at' => $request->interview_date,
            'type' => $request->interview_type ?? 'video',
            'location' => $request->interview_location,
            'created_by' => Auth::user()->name ?? '알 수 없음'
        ]);

        return $interview;
    }
}
