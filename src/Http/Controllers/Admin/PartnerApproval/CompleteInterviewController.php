<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerApproval;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerInterview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CompleteInterviewController extends Controller
{
    /**
     * Handle the request
     */
    public function __invoke(Request $request, $id = null)
    {
        try {
            // 요청 데이터 검증
            $validator = Validator::make($request->all(), [
                'interview_result' => 'required|in:pass,fail,pending,hold,next_round',
                'technical_score' => 'nullable|numeric|min:0|max:5',
                'communication_score' => 'nullable|numeric|min:0|max:5',
                'experience_score' => 'nullable|numeric|min:0|max:5',
                'attitude_score' => 'nullable|numeric|min:0|max:5',
                'strengths' => 'nullable|string|max:1000',
                'weaknesses' => 'nullable|string|max:1000',
                'recommendations' => 'nullable|string|max:1000',
                'interview_feedback' => 'nullable|array',
                'interviewer_notes' => 'nullable|string|max:1000'
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

            // 기존 면접이 있는지 확인
            if ($application->application_status !== 'interview') {
                return response()->json([
                    'success' => false,
                    'message' => '면접이 예정되지 않은 지원서입니다.'
                ], 400);
            }

            // PartnerInterview 테이블에서 면접 찾기
            $interview = PartnerInterview::where('application_id', $application->id)
                ->whereIn('interview_status', ['scheduled', 'in_progress'])
                ->first();

            if (!$interview) {
                return response()->json([
                    'success' => false,
                    'message' => '면접 기록을 찾을 수 없습니다.'
                ], 404);
            }

            // 점수 정리
            $scores = [];
            foreach(['technical_score', 'communication_score', 'experience_score', 'attitude_score'] as $field) {
                if ($request->filled($field)) {
                    $scores[$field] = $request->input($field);
                }
            }

            // 피드백 정리
            $feedback = $request->input('interview_feedback', []);
            if ($request->filled('strengths')) {
                $feedback['strengths'] = $request->input('strengths');
            }
            if ($request->filled('weaknesses')) {
                $feedback['weaknesses'] = $request->input('weaknesses');
            }
            if ($request->filled('recommendations')) {
                $feedback['recommendations'] = $request->input('recommendations');
            }

            // 면접 완료 처리
            $interview->completeInterview(
                $scores,
                $request->input('interview_result'),
                $feedback
            );

            // 면접관 메모 추가
            if ($request->filled('interviewer_notes')) {
                $interview->update(['interviewer_notes' => $request->input('interviewer_notes')]);
            }

            // 신청서 상태 업데이트
            $newStatus = match($request->input('interview_result')) {
                'pass' => 'approved',
                'fail' => 'rejected',
                'next_round' => 'interview',
                default => 'reviewing'
            };

            $application->update(['application_status' => $newStatus]);

            // 통과한 경우 파트너 계정 생성
            if ($request->input('interview_result') === 'pass' && $newStatus === 'approved') {
                try {
                    $partnerUser = $application->approve(auth()->id());
                } catch (\Exception $e) {
                    \Log::warning('파트너 계정 생성 실패', [
                        'application_id' => $application->id,
                        'error' => $e->getMessage()
                    ]);
                    // 파트너 계정 생성 실패해도 면접 완료는 유지
                }
            }

            // 관리자 노트 추가
            $adminNotes = $application->admin_notes ?? '';
            $newNote = sprintf(
                "[%s] 면접 완료: %s (면접 ID: %d)\n  결과: %s\n",
                now()->format('Y-m-d H:i'),
                $interview->completed_at?->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i'),
                $interview->id,
                $interview->result_label ?? $request->input('interview_result')
            );

            if ($scores) {
                $scoreText = implode(', ', array_map(fn($k, $v) => "{$k}: {$v}", array_keys($scores), $scores));
                $newNote .= "  점수: {$scoreText}\n";
            }

            $application->update(['admin_notes' => $adminNotes . $newNote]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => '면접이 성공적으로 완료되었습니다.',
                'data' => [
                    'application_id' => $application->id,
                    'interview_id' => $interview->id,
                    'interview_result' => $interview->interview_result,
                    'overall_score' => $interview->overall_score,
                    'application_status' => $application->application_status,
                    'interview_url' => route('admin.partner.interview.show', $interview->id),
                    'partner_created' => isset($partnerUser) && $partnerUser
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            \Log::error('면접 완료 처리 실패', [
                'error' => $e->getMessage(),
                'application_id' => $id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => '면접 완료 처리 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }
}
