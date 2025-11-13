<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerInterview;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerInterview;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CalendarController extends Controller
{
    /**
     * 면접 일정 캘린더
     */
    public function __invoke(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));
        $view = $request->get('view', 'month'); // month, week, day
        $interviewer = $request->get('interviewer', 'all');

        // 날짜 범위 설정
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        if ($view === 'week') {
            $week = $request->get('week', 1);
            $startDate = Carbon::create($year, $month, 1)->startOfMonth()->addWeeks($week - 1)->startOfWeek();
            $endDate = $startDate->copy()->endOfWeek();
        } elseif ($view === 'day') {
            $day = $request->get('day', 1);
            $startDate = Carbon::create($year, $month, $day)->startOfDay();
            $endDate = $startDate->copy()->endOfDay();
        }

        // 면접 데이터 조회
        $query = PartnerInterview::with(['user', 'application', 'interviewer'])
            ->whereBetween('scheduled_at', [$startDate, $endDate]);

        if ($interviewer !== 'all') {
            $query->where('interviewer_id', $interviewer);
        }

        $interviews = $query->get();

        // 캘린더 이벤트 형식으로 변환
        $events = $interviews->map(function ($interview) {
            return [
                'id' => $interview->id,
                'title' => $interview->name,
                'start' => $interview->scheduled_at?->toISOString(),
                'end' => $interview->scheduled_at?->addMinutes($interview->duration_minutes ?? 60)->toISOString(),
                'color' => $this->getEventColor($interview->interview_status),
                'textColor' => $this->getTextColor($interview->interview_status),
                'extendedProps' => [
                    'interview_status' => $interview->interview_status,
                    'interview_type' => $interview->interview_type,
                    'interview_result' => $interview->interview_result,
                    'interviewer_name' => $interview->interviewer?->name,
                    'email' => $interview->email,
                    'referrer_code' => $interview->referrer_code,
                    'referrer_name' => $interview->referrer_name,
                    'meeting_url' => $interview->meeting_url,
                    'meeting_location' => $interview->meeting_location,
                    'overall_score' => $interview->overall_score,
                    'show_url' => route('admin.partner.interview.show', $interview->id),
                    'edit_url' => route('admin.partner.interview.edit', $interview->id)
                ]
            ];
        });

        // 일별 통계
        $dailyStats = $this->getDailyStats($startDate, $endDate, $interviewer);

        // 면접관 목록
        $interviewers = \App\Models\User::where('isAdmin', true)
            ->where('is_blocked', false)
            ->orderBy('name')
            ->get();

        // AJAX 요청인 경우 JSON 반환
        if ($request->expectsJson()) {
            return response()->json([
                'events' => $events,
                'stats' => $dailyStats
            ]);
        }

        return view('jiny-partner::admin.partner.interview.calendar', [
            'events' => $events,
            'dailyStats' => $dailyStats,
            'interviewers' => $interviewers,
            'currentYear' => $year,
            'currentMonth' => $month,
            'currentView' => $view,
            'currentInterviewer' => $interviewer,
            'pageTitle' => '면접 캘린더'
        ]);
    }

    /**
     * 면접 상태에 따른 이벤트 색상
     */
    private function getEventColor($status)
    {
        return match($status) {
            'scheduled' => '#ffc107', // warning
            'in_progress' => '#17a2b8', // info
            'completed' => '#28a745', // success
            'cancelled' => '#dc3545', // danger
            'rescheduled' => '#fd7e14', // orange
            'no_show' => '#6c757d', // secondary
            default => '#6c757d'
        };
    }

    /**
     * 면접 상태에 따른 텍스트 색상
     */
    private function getTextColor($status)
    {
        return match($status) {
            'scheduled', 'rescheduled' => '#212529', // dark text on light background
            default => '#ffffff' // white text on dark background
        };
    }

    /**
     * 일별 통계
     */
    private function getDailyStats($startDate, $endDate, $interviewer)
    {
        $query = PartnerInterview::whereBetween('scheduled_at', [$startDate, $endDate]);

        if ($interviewer !== 'all') {
            $query->where('interviewer_id', $interviewer);
        }

        $stats = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $dayStart = $current->copy()->startOfDay();
            $dayEnd = $current->copy()->endOfDay();

            $dayInterviews = $query->whereBetween('scheduled_at', [$dayStart, $dayEnd])->get();

            $stats[$current->format('Y-m-d')] = [
                'date' => $current->format('Y-m-d'),
                'total' => $dayInterviews->count(),
                'scheduled' => $dayInterviews->where('interview_status', 'scheduled')->count(),
                'in_progress' => $dayInterviews->where('interview_status', 'in_progress')->count(),
                'completed' => $dayInterviews->where('interview_status', 'completed')->count(),
                'cancelled' => $dayInterviews->where('interview_status', 'cancelled')->count(),
                'passed' => $dayInterviews->where('interview_result', 'pass')->count(),
                'failed' => $dayInterviews->where('interview_result', 'fail')->count(),
            ];

            $current->addDay();
        }

        return $stats;
    }

    /**
     * 면접 상세 정보 조회 (AJAX)
     */
    public function getInterviewDetails(Request $request, $id)
    {
        $interview = PartnerInterview::with(['user', 'application', 'interviewer', 'referrerPartner'])
            ->findOrFail($id);

        return response()->json([
            'interview' => [
                'id' => $interview->id,
                'name' => $interview->name,
                'email' => $interview->email,
                'scheduled_at' => $interview->scheduled_at?->format('Y-m-d H:i'),
                'duration_minutes' => $interview->duration_minutes,
                'interview_status' => $interview->interview_status,
                'status_label' => $interview->status_label,
                'interview_type' => $interview->interview_type,
                'type_label' => $interview->type_label,
                'interview_round' => $interview->interview_round,
                'round_label' => $interview->round_label,
                'interview_result' => $interview->interview_result,
                'result_label' => $interview->result_label,
                'interviewer_name' => $interview->interviewer?->name,
                'referrer_code' => $interview->referrer_code,
                'referrer_name' => $interview->referrer_name,
                'meeting_url' => $interview->meeting_url,
                'meeting_location' => $interview->meeting_location,
                'meeting_password' => $interview->meeting_password,
                'preparation_notes' => $interview->preparation_notes,
                'overall_score' => $interview->overall_score,
                'show_url' => route('admin.partner.interview.show', $interview->id),
                'edit_url' => route('admin.partner.interview.edit', $interview->id)
            ]
        ]);
    }
}