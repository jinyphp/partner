<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerInterview;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerInterview;
use Jiny\Partner\Models\PartnerApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{
    /**
     * 새 면접 일정 저장
     */
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'application_id' => 'required|exists:partner_applications,id',
            'interviewer_id' => 'required|exists:users,id',
            'interview_type' => 'required|in:phone,video,in_person,written',
            'interview_round' => 'required|in:first,second,final',
            'scheduled_at' => 'required|date|after:now',
            'duration_minutes' => 'nullable|integer|min:15|max:240',
            'meeting_location' => 'nullable|string|max:255',
            'meeting_url' => 'nullable|url|max:255',
            'meeting_password' => 'nullable|string|max:50',
            'preparation_notes' => 'nullable|string|max:1000',
            'interviewer_notes' => 'nullable|string|max:1000'
        ], [
            'application_id.required' => '신청서를 선택해주세요.',
            'application_id.exists' => '존재하지 않는 신청서입니다.',
            'interviewer_id.required' => '면접관을 선택해주세요.',
            'interviewer_id.exists' => '존재하지 않는 면접관입니다.',
            'interview_type.required' => '면접 유형을 선택해주세요.',
            'interview_type.in' => '올바른 면접 유형을 선택해주세요.',
            'interview_round.required' => '면접 차수를 선택해주세요.',
            'interview_round.in' => '올바른 면접 차수를 선택해주세요.',
            'scheduled_at.required' => '면접 일시를 입력해주세요.',
            'scheduled_at.date' => '올바른 날짜 형식을 입력해주세요.',
            'scheduled_at.after' => '면접 일시는 현재 시간 이후여야 합니다.',
            'duration_minutes.integer' => '면접 시간은 숫자로 입력해주세요.',
            'duration_minutes.min' => '면접 시간은 최소 15분 이상이어야 합니다.',
            'duration_minutes.max' => '면접 시간은 최대 240분을 초과할 수 없습니다.',
            'meeting_url.url' => '올바른 URL 형식을 입력해주세요.'
        ]);

        try {
            DB::beginTransaction();

            // 신청서 정보 조회
            $application = PartnerApplication::with(['user', 'referrerPartner'])->findOrFail($validated['application_id']);

            // 중복 면접 체크
            $existingInterview = PartnerInterview::where('application_id', $application->id)
                ->whereIn('interview_status', ['scheduled', 'in_progress'])
                ->first();

            if ($existingInterview) {
                return back()->withErrors(['application_id' => '이미 예정된 면접이 있습니다.'])->withInput();
            }

            // 면접 데이터 구성
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
                'interview_type' => $validated['interview_type'],
                'interview_round' => $validated['interview_round'],
                'scheduled_at' => $validated['scheduled_at'],
                'duration_minutes' => $validated['duration_minutes'] ?? 60,
                'interviewer_id' => $validated['interviewer_id'],

                // 장소/연결 정보
                'meeting_location' => $validated['meeting_location'],
                'meeting_url' => $validated['meeting_url'],
                'meeting_password' => $validated['meeting_password'],
                'preparation_notes' => $validated['preparation_notes'],
                'interviewer_notes' => $validated['interviewer_notes'],

                // 관리 정보
                'created_by' => auth()->id()
            ];

            // 면접 생성
            $interview = PartnerInterview::create($interviewData);

            // 신청서 상태를 면접으로 업데이트
            $application->update([
                'application_status' => 'interview',
                'interview_date' => $validated['scheduled_at'],
                'interview_notes' => $validated['preparation_notes']
            ]);

            // 면접 로그 추가
            $interview->addLog('면접 일정', '면접이 예정되었습니다.', [
                'interviewer' => $interview->interviewer->name ?? '알 수 없음',
                'scheduled_at' => $validated['scheduled_at'],
                'type' => $validated['interview_type'],
                'round' => $validated['interview_round']
            ]);

            DB::commit();

            return redirect()
                ->route('admin.partner.interview.show', $interview->id)
                ->with('success', '면접 일정이 성공적으로 생성되었습니다.');

        } catch (\Exception $e) {
            DB::rollback();

            \Log::error('면접 일정 생성 실패', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $validated
            ]);

            return back()
                ->withErrors(['general' => '면접 일정 생성 중 오류가 발생했습니다.'])
                ->withInput();
        }
    }
}