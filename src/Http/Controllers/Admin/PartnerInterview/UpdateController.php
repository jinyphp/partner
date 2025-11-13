<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerInterview;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerInterview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UpdateController extends Controller
{
    /**
     * 면접 정보 업데이트
     */
    public function __invoke(Request $request, $id)
    {
        $interview = PartnerInterview::findOrFail($id);

        // 완료된 면접은 수정 제한
        if ($interview->interview_status === 'completed') {
            return back()->withErrors(['general' => '완료된 면접은 수정할 수 없습니다.']);
        }

        $validated = $request->validate([
            'interviewer_id' => 'nullable|exists:users,id',
            'interview_type' => 'nullable|in:phone,video,in_person,written',
            'interview_round' => 'nullable|in:first,second,final',
            'scheduled_at' => 'nullable|date|after:now',
            'duration_minutes' => 'nullable|integer|min:15|max:240',
            'meeting_location' => 'nullable|string|max:255',
            'meeting_url' => 'nullable|url|max:255',
            'meeting_password' => 'nullable|string|max:50',
            'preparation_notes' => 'nullable|string|max:1000',
            'interviewer_notes' => 'nullable|string|max:1000'
        ], [
            'interviewer_id.exists' => '존재하지 않는 면접관입니다.',
            'interview_type.in' => '올바른 면접 유형을 선택해주세요.',
            'interview_round.in' => '올바른 면접 차수를 선택해주세요.',
            'scheduled_at.date' => '올바른 날짜 형식을 입력해주세요.',
            'scheduled_at.after' => '면접 일시는 현재 시간 이후여야 합니다.',
            'duration_minutes.integer' => '면접 시간은 숫자로 입력해주세요.',
            'duration_minutes.min' => '면접 시간은 최소 15분 이상이어야 합니다.',
            'duration_minutes.max' => '면접 시간은 최대 240분을 초과할 수 없습니다.',
            'meeting_url.url' => '올바른 URL 형식을 입력해주세요.'
        ]);

        try {
            DB::beginTransaction();

            // 수정 가능한 필드만 필터링
            $editableFields = $this->getEditableFields($interview);
            $updateData = array_intersect_key($validated, $editableFields);

            // 변경사항 확인
            $changes = [];
            $originalData = $interview->toArray();

            foreach ($updateData as $field => $value) {
                if ($originalData[$field] != $value) {
                    $changes[$field] = [
                        'old' => $originalData[$field],
                        'new' => $value
                    ];
                }
            }

            if (empty($changes)) {
                return back()->with('info', '변경된 사항이 없습니다.');
            }

            // 일정 변경인 경우 특별 처리
            if (isset($changes['scheduled_at'])) {
                $interview->rescheduleInterview(
                    $updateData['scheduled_at'],
                    $request->input('reschedule_reason')
                );
                unset($updateData['scheduled_at']);
            }

            // 나머지 정보 업데이트
            if (!empty($updateData)) {
                $updateData['updated_by'] = auth()->id();
                $interview->update($updateData);
            }

            // 변경 로그 기록
            foreach ($changes as $field => $change) {
                $fieldLabel = $this->getFieldLabel($field);
                $interview->addLog('정보 수정', "{$fieldLabel}이(가) 변경되었습니다.", [
                    'field' => $field,
                    'old_value' => $change['old'],
                    'new_value' => $change['new']
                ]);
            }

            // 신청서 면접 정보도 업데이트
            if (isset($changes['scheduled_at'])) {
                $interview->application->update([
                    'interview_date' => $interview->scheduled_at
                ]);
            }

            DB::commit();

            return redirect()
                ->route('admin.partner.interview.show', $interview->id)
                ->with('success', '면접 정보가 성공적으로 수정되었습니다.');

        } catch (\Exception $e) {
            DB::rollback();

            \Log::error('면접 정보 수정 실패', [
                'error' => $e->getMessage(),
                'interview_id' => $interview->id,
                'user_id' => auth()->id(),
                'request_data' => $validated
            ]);

            return back()
                ->withErrors(['general' => '면접 정보 수정 중 오류가 발생했습니다.'])
                ->withInput();
        }
    }

    /**
     * 면접 상태에 따른 수정 가능한 필드
     */
    private function getEditableFields($interview)
    {
        $allFields = [
            'interviewer_id' => true,
            'interview_type' => true,
            'interview_round' => true,
            'scheduled_at' => true,
            'duration_minutes' => true,
            'meeting_location' => true,
            'meeting_url' => true,
            'meeting_password' => true,
            'preparation_notes' => true,
            'interviewer_notes' => true
        ];

        switch ($interview->interview_status) {
            case 'scheduled':
            case 'rescheduled':
                return $allFields;

            case 'in_progress':
                return array_intersect_key($allFields, array_flip([
                    'duration_minutes',
                    'meeting_location',
                    'meeting_url',
                    'meeting_password',
                    'interviewer_notes'
                ]));

            case 'cancelled':
            case 'no_show':
                return array_intersect_key($allFields, array_flip([
                    'interviewer_notes'
                ]));

            default:
                return [];
        }
    }

    /**
     * 필드명 한글 라벨
     */
    private function getFieldLabel($field)
    {
        return match($field) {
            'interviewer_id' => '면접관',
            'interview_type' => '면접 유형',
            'interview_round' => '면접 차수',
            'scheduled_at' => '면접 일시',
            'duration_minutes' => '면접 시간',
            'meeting_location' => '면접 장소',
            'meeting_url' => '회의 URL',
            'meeting_password' => '회의 비밀번호',
            'preparation_notes' => '준비 사항',
            'interviewer_notes' => '면접관 메모',
            default => $field
        };
    }
}