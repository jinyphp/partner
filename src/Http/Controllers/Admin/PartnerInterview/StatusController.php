<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerInterview;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerInterview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatusController extends Controller
{
    /**
     * 면접 시작
     */
    public function start(Request $request, $id)
    {
        $interview = PartnerInterview::findOrFail($id);

        if ($interview->interview_status !== 'scheduled') {
            return back()->withErrors(['general' => '예정된 면접만 시작할 수 있습니다.']);
        }

        try {
            $interview->startInterview(auth()->id());

            return redirect()
                ->route('admin.partner.interview.show', $interview->id)
                ->with('success', '면접이 시작되었습니다.');

        } catch (\Exception $e) {
            \Log::error('면접 시작 실패', [
                'error' => $e->getMessage(),
                'interview_id' => $interview->id,
                'user_id' => auth()->id()
            ]);

            return back()->withErrors(['general' => '면접 시작 중 오류가 발생했습니다.']);
        }
    }

    /**
     * 면접 완료
     */
    public function complete(Request $request, $id)
    {
        $interview = PartnerInterview::findOrFail($id);

        if (!in_array($interview->interview_status, ['scheduled', 'in_progress'])) {
            return back()->withErrors(['general' => '진행 중이거나 예정된 면접만 완료할 수 있습니다.']);
        }

        $validated = $request->validate([
            'technical_score' => 'nullable|numeric|min:0|max:5',
            'communication_score' => 'nullable|numeric|min:0|max:5',
            'experience_score' => 'nullable|numeric|min:0|max:5',
            'attitude_score' => 'nullable|numeric|min:0|max:5',
            'interview_result' => 'required|in:pass,fail,pending,hold,next_round',
            'strengths' => 'nullable|string|max:1000',
            'weaknesses' => 'nullable|string|max:1000',
            'recommendations' => 'nullable|string|max:1000',
            'interview_feedback' => 'nullable|array'
        ]);

        try {
            DB::beginTransaction();

            // 점수 정리
            $scores = [];
            foreach(['technical_score', 'communication_score', 'experience_score', 'attitude_score'] as $field) {
                if (isset($validated[$field]) && $validated[$field] !== null) {
                    $scores[$field] = $validated[$field];
                }
            }

            // 피드백 정리
            $feedback = $validated['interview_feedback'] ?? [];

            // 강점, 약점, 권장사항을 피드백에 추가
            if ($validated['strengths']) {
                $feedback['strengths'] = $validated['strengths'];
            }
            if ($validated['weaknesses']) {
                $feedback['weaknesses'] = $validated['weaknesses'];
            }
            if ($validated['recommendations']) {
                $feedback['recommendations'] = $validated['recommendations'];
            }

            // 면접 완료 처리
            $interview->completeInterview(
                $scores,
                $validated['interview_result'],
                $feedback
            );

            // 신청서 상태 업데이트
            if ($interview->application) {
                $newStatus = match($validated['interview_result']) {
                    'pass' => 'approved',
                    'fail' => 'rejected',
                    'next_round' => 'interview',
                    default => 'reviewing'
                };

                $interview->application->update([
                    'application_status' => $newStatus
                ]);

                // 통과한 경우 파트너 계정 생성
                if ($validated['interview_result'] === 'pass' && $newStatus === 'approved') {
                    $interview->application->approve(auth()->id());
                }
            }

            DB::commit();

            return redirect()
                ->route('admin.partner.interview.show', $interview->id)
                ->with('success', '면접이 완료되었습니다.');

        } catch (\Exception $e) {
            DB::rollback();

            \Log::error('면접 완료 실패', [
                'error' => $e->getMessage(),
                'interview_id' => $interview->id,
                'user_id' => auth()->id(),
                'request_data' => $validated
            ]);

            return back()
                ->withErrors(['general' => '면접 완료 처리 중 오류가 발생했습니다.'])
                ->withInput();
        }
    }

    /**
     * 면접 취소
     */
    public function cancel(Request $request, $id)
    {
        $interview = PartnerInterview::findOrFail($id);

        if (in_array($interview->interview_status, ['completed', 'cancelled'])) {
            return back()->withErrors(['general' => '이미 완료되거나 취소된 면접입니다.']);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500'
        ], [
            'reason.required' => '취소 사유를 입력해주세요.'
        ]);

        try {
            $interview->cancelInterview($validated['reason']);

            return redirect()
                ->route('admin.partner.interview.show', $interview->id)
                ->with('success', '면접이 취소되었습니다.');

        } catch (\Exception $e) {
            \Log::error('면접 취소 실패', [
                'error' => $e->getMessage(),
                'interview_id' => $interview->id,
                'user_id' => auth()->id()
            ]);

            return back()->withErrors(['general' => '면접 취소 중 오류가 발생했습니다.']);
        }
    }

    /**
     * 면접 재일정
     */
    public function reschedule(Request $request, $id)
    {
        $interview = PartnerInterview::findOrFail($id);

        if (!in_array($interview->interview_status, ['scheduled', 'rescheduled'])) {
            return back()->withErrors(['general' => '예정된 면접만 재일정할 수 있습니다.']);
        }

        $validated = $request->validate([
            'scheduled_at' => 'required|date|after:now',
            'reason' => 'nullable|string|max:500'
        ], [
            'scheduled_at.required' => '새로운 면접 일시를 선택해주세요.',
            'scheduled_at.after' => '면접 일시는 현재 시간 이후여야 합니다.'
        ]);

        try {
            $interview->rescheduleInterview(
                $validated['scheduled_at'],
                $validated['reason'] ?? '일정 변경'
            );

            // 신청서의 면접 일시도 업데이트
            if ($interview->application) {
                $interview->application->update([
                    'interview_date' => $validated['scheduled_at']
                ]);
            }

            return redirect()
                ->route('admin.partner.interview.show', $interview->id)
                ->with('success', '면접 일정이 변경되었습니다.');

        } catch (\Exception $e) {
            \Log::error('면접 재일정 실패', [
                'error' => $e->getMessage(),
                'interview_id' => $interview->id,
                'user_id' => auth()->id()
            ]);

            return back()->withErrors(['general' => '면접 재일정 중 오류가 발생했습니다.']);
        }
    }
}