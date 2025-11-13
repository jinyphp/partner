<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerInterview;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerInterview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BulkActionController extends Controller
{
    /**
     * 대량 처리 작업
     */
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:delete,cancel,reschedule,assign_interviewer',
            'interview_ids' => 'required|array|min:1',
            'interview_ids.*' => 'exists:partner_interviews,id',
            'reason' => 'nullable|string|max:500',
            'new_date' => 'nullable|required_if:action,reschedule|date|after:now',
            'interviewer_id' => 'nullable|required_if:action,assign_interviewer|exists:users,id'
        ], [
            'action.required' => '작업을 선택해주세요.',
            'action.in' => '올바른 작업을 선택해주세요.',
            'interview_ids.required' => '면접을 선택해주세요.',
            'interview_ids.min' => '최소 1개의 면접을 선택해주세요.',
            'new_date.required_if' => '재일정 작업 시 새로운 날짜를 선택해주세요.',
            'new_date.after' => '새로운 날짜는 현재 시간 이후여야 합니다.',
            'interviewer_id.required_if' => '면접관 배정 시 면접관을 선택해주세요.',
            'interviewer_id.exists' => '존재하지 않는 면접관입니다.'
        ]);

        $interviews = PartnerInterview::whereIn('id', $validated['interview_ids'])->get();

        if ($interviews->count() !== count($validated['interview_ids'])) {
            return back()->withErrors(['general' => '일부 면접을 찾을 수 없습니다.']);
        }

        try {
            DB::beginTransaction();

            $results = match($validated['action']) {
                'delete' => $this->bulkDelete($interviews, $validated),
                'cancel' => $this->bulkCancel($interviews, $validated),
                'reschedule' => $this->bulkReschedule($interviews, $validated),
                'assign_interviewer' => $this->bulkAssignInterviewer($interviews, $validated),
            };

            DB::commit();

            $message = $this->getSuccessMessage($validated['action'], $results);

            return redirect()
                ->route('admin.partner.interview.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();

            \Log::error('대량 처리 실패', [
                'error' => $e->getMessage(),
                'action' => $validated['action'],
                'interview_ids' => $validated['interview_ids'],
                'user_id' => auth()->id()
            ]);

            return back()
                ->withErrors(['general' => '대량 처리 중 오류가 발생했습니다: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * 대량 삭제
     */
    private function bulkDelete($interviews, $validated)
    {
        $successCount = 0;
        $errors = [];

        foreach ($interviews as $interview) {
            // 완료된 면접이나 진행 중인 면접은 삭제할 수 없음
            if (in_array($interview->interview_status, ['completed', 'in_progress'])) {
                $errors[] = "{$interview->name} - 완료되거나 진행 중인 면접은 삭제할 수 없습니다.";
                continue;
            }

            try {
                // 로그 추가
                $interview->addLog('대량 삭제', '대량 삭제 작업으로 삭제되었습니다.', [
                    'reason' => $validated['reason'] ?? '관리자에 의한 대량 삭제',
                    'deleted_by' => auth()->user()->name ?? '알 수 없음'
                ]);

                // 신청서 상태 복원
                if ($interview->application) {
                    $interview->application->update([
                        'application_status' => 'submitted',
                        'interview_date' => null,
                        'interview_notes' => null
                    ]);
                }

                $interview->delete();
                $successCount++;

            } catch (\Exception $e) {
                $errors[] = "{$interview->name} - 삭제 실패: " . $e->getMessage();
            }
        }

        return ['success' => $successCount, 'errors' => $errors];
    }

    /**
     * 대량 취소
     */
    private function bulkCancel($interviews, $validated)
    {
        $successCount = 0;
        $errors = [];

        foreach ($interviews as $interview) {
            // 이미 완료되거나 취소된 면접은 취소할 수 없음
            if (in_array($interview->interview_status, ['completed', 'cancelled'])) {
                $errors[] = "{$interview->name} - 이미 완료되거나 취소된 면접입니다.";
                continue;
            }

            try {
                $interview->cancelInterview($validated['reason'] ?? '관리자에 의한 대량 취소');
                $successCount++;

            } catch (\Exception $e) {
                $errors[] = "{$interview->name} - 취소 실패: " . $e->getMessage();
            }
        }

        return ['success' => $successCount, 'errors' => $errors];
    }

    /**
     * 대량 재일정
     */
    private function bulkReschedule($interviews, $validated)
    {
        $successCount = 0;
        $errors = [];

        foreach ($interviews as $interview) {
            // 예정된 면접만 재일정 가능
            if (!in_array($interview->interview_status, ['scheduled', 'rescheduled'])) {
                $errors[] = "{$interview->name} - 예정된 면접만 재일정할 수 있습니다.";
                continue;
            }

            try {
                $interview->rescheduleInterview(
                    $validated['new_date'],
                    $validated['reason'] ?? '관리자에 의한 대량 재일정'
                );

                // 신청서의 면접 일시도 업데이트
                if ($interview->application) {
                    $interview->application->update([
                        'interview_date' => $validated['new_date']
                    ]);
                }

                $successCount++;

            } catch (\Exception $e) {
                $errors[] = "{$interview->name} - 재일정 실패: " . $e->getMessage();
            }
        }

        return ['success' => $successCount, 'errors' => $errors];
    }

    /**
     * 대량 면접관 배정
     */
    private function bulkAssignInterviewer($interviews, $validated)
    {
        $successCount = 0;
        $errors = [];

        $interviewer = \App\Models\User::find($validated['interviewer_id']);
        if (!$interviewer) {
            throw new \Exception('면접관을 찾을 수 없습니다.');
        }

        foreach ($interviews as $interview) {
            // 완료된 면접은 면접관 변경 불가
            if ($interview->interview_status === 'completed') {
                $errors[] = "{$interview->name} - 완료된 면접은 면접관을 변경할 수 없습니다.";
                continue;
            }

            try {
                $oldInterviewer = $interview->interviewer?->name ?? '미배정';

                $interview->update([
                    'interviewer_id' => $validated['interviewer_id'],
                    'interviewer_name' => $interviewer->name,
                    'updated_by' => auth()->id()
                ]);

                // 로그 추가
                $interview->addLog('면접관 배정', '대량 작업으로 면접관이 변경되었습니다.', [
                    'old_interviewer' => $oldInterviewer,
                    'new_interviewer' => $interviewer->name,
                    'reason' => $validated['reason'] ?? '관리자에 의한 대량 배정'
                ]);

                $successCount++;

            } catch (\Exception $e) {
                $errors[] = "{$interview->name} - 면접관 배정 실패: " . $e->getMessage();
            }
        }

        return ['success' => $successCount, 'errors' => $errors];
    }

    /**
     * 성공 메시지 생성
     */
    private function getSuccessMessage($action, $results)
    {
        $actionNames = [
            'delete' => '삭제',
            'cancel' => '취소',
            'reschedule' => '재일정',
            'assign_interviewer' => '면접관 배정'
        ];

        $actionName = $actionNames[$action] ?? $action;
        $message = "{$results['success']}건의 면접이 성공적으로 {$actionName}되었습니다.";

        if (!empty($results['errors'])) {
            $errorCount = count($results['errors']);
            $message .= " (실패: {$errorCount}건)";
        }

        return $message;
    }
}