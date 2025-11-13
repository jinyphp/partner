<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerInterview;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerInterview;
use App\Models\User;
use Illuminate\Http\Request;

class EditController extends Controller
{
    /**
     * 면접 정보 수정 폼
     */
    public function __invoke(Request $request, $id)
    {
        $interview = PartnerInterview::with([
            'user',
            'application',
            'referrerPartner',
            'interviewer'
        ])->findOrFail($id);

        // 완료된 면접은 수정 제한
        if ($interview->interview_status === 'completed') {
            return redirect()
                ->route('admin.partner.interview.show', $interview->id)
                ->with('warning', '완료된 면접은 수정할 수 없습니다.');
        }

        // 면접관 목록
        $interviewers = User::where('isAdmin', true)
            ->where('is_blocked', false)
            ->orderBy('name')
            ->get();

        // 수정 가능한 필드 확인
        $editableFields = $this->getEditableFields($interview);

        return view('jiny-partner::admin.partner.interview.edit', [
            'interview' => $interview,
            'interviewers' => $interviewers,
            'editableFields' => $editableFields,
            'pageTitle' => '면접 정보 수정 - ' . $interview->name
        ]);
    }

    /**
     * 면접 상태에 따른 수정 가능한 필드
     */
    private function getEditableFields($interview)
    {
        $allFields = [
            'interviewer_id' => '면접관',
            'interview_type' => '면접 유형',
            'interview_round' => '면접 차수',
            'scheduled_at' => '면접 일시',
            'duration_minutes' => '면접 시간',
            'meeting_location' => '면접 장소',
            'meeting_url' => '회의 URL',
            'meeting_password' => '회의 비밀번호',
            'preparation_notes' => '준비 사항',
            'interviewer_notes' => '면접관 메모'
        ];

        switch ($interview->interview_status) {
            case 'scheduled':
            case 'rescheduled':
                // 예정된 면접은 모든 필드 수정 가능
                return $allFields;

            case 'in_progress':
                // 진행 중인 면접은 일부 필드만 수정 가능
                return array_intersect_key($allFields, array_flip([
                    'duration_minutes',
                    'meeting_location',
                    'meeting_url',
                    'meeting_password',
                    'interviewer_notes'
                ]));

            case 'cancelled':
            case 'no_show':
                // 취소되거나 불참인 면접은 메모만 수정 가능
                return array_intersect_key($allFields, array_flip([
                    'interviewer_notes'
                ]));

            default:
                return [];
        }
    }
}