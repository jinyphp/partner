<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerInterview;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use App\Models\User;
use Illuminate\Http\Request;

class CreateController extends Controller
{
    /**
     * 새 면접 일정 생성 폼
     */
    public function __invoke(Request $request, $applicationId = null)
    {
        $application = null;

        // 특정 신청서에 대한 면접인 경우
        if ($applicationId) {
            $application = PartnerApplication::with(['user', 'referrerPartner'])
                ->findOrFail($applicationId);

            // 이미 면접이 예정되어 있는지 확인
            $existingInterview = $application->interviews()
                ->whereIn('interview_status', ['scheduled', 'in_progress'])
                ->first();

            if ($existingInterview) {
                return redirect()
                    ->route('admin.partner.interview.show', $existingInterview->id)
                    ->with('warning', '이미 예정된 면접이 있습니다.');
            }
        }

        // 면접 가능한 신청서 목록 (면접 상태이거나 승인 대기 중인 신청서)
        $availableApplications = PartnerApplication::with(['user', 'referrerPartner'])
            ->whereIn('application_status', ['interview', 'submitted', 'reviewing'])
            ->whereDoesntHave('interviews', function($query) {
                $query->whereIn('interview_status', ['scheduled', 'in_progress']);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // 면접관 목록 (관리자 권한을 가진 사용자들)
        $interviewers = User::where('isAdmin', true)
            ->where('is_blocked', false)
            ->orderBy('name')
            ->get();

        // 기본 설정값
        $defaultSettings = [
            'interview_type' => 'video',
            'interview_round' => 'first',
            'duration' => 60,
            'preparation_time' => 15
        ];

        return view('jiny-partner::admin.partner.interview.create', [
            'application' => $application,
            'availableApplications' => $availableApplications,
            'interviewers' => $interviewers,
            'defaultSettings' => $defaultSettings,
            'pageTitle' => '새 면접 일정'
        ]);
    }
}