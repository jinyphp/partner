<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerInterview;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerInterview;
use Jiny\Partner\Models\PartnerApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IndexController extends Controller
{
    /**
     * 파트너 면접 관리 대시보드
     */
    public function __invoke(Request $request)
    {
        // 필터 옵션
        $status = $request->get('status', 'all');
        $result = $request->get('result', 'all');
        $type = $request->get('type', 'all');
        $interviewer = $request->get('interviewer', 'all');
        $search = $request->get('search', '');
        $sortBy = $request->get('sort_by', 'scheduled_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $perPage = $request->get('per_page', 15);
        $dateRange = $request->get('date_range', 'all');

        // 기본 쿼리
        $query = PartnerInterview::with([
            'user',
            'application',
            'referrerPartner',
            'interviewer',
            'creator'
        ]);

        // 상태 필터
        if ($status !== 'all') {
            $query->where('interview_status', $status);
        }

        // 결과 필터
        if ($result !== 'all') {
            $query->where('interview_result', $result);
        }

        // 면접 타입 필터
        if ($type !== 'all') {
            $query->where('interview_type', $type);
        }

        // 면접관 필터
        if ($interviewer !== 'all') {
            $query->where('interviewer_id', $interviewer);
        }

        // 날짜 범위 필터
        if ($dateRange !== 'all') {
            switch ($dateRange) {
                case 'today':
                    $query->whereDate('scheduled_at', today());
                    break;
                case 'this_week':
                    $query->whereBetween('scheduled_at', [
                        now()->startOfWeek(),
                        now()->endOfWeek()
                    ]);
                    break;
                case 'this_month':
                    $query->whereMonth('scheduled_at', now()->month)
                          ->whereYear('scheduled_at', now()->year);
                    break;
                case 'upcoming':
                    $query->where('scheduled_at', '>=', now())
                          ->whereIn('interview_status', ['scheduled', 'rescheduled']);
                    break;
                case 'past':
                    $query->where('scheduled_at', '<', now());
                    break;
            }
        }

        // 검색 필터
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('referrer_name', 'like', "%{$search}%")
                  ->orWhere('referrer_code', 'like', "%{$search}%")
                  ->orWhereHas('interviewer', function($interviewerQuery) use ($search) {
                      $interviewerQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // 정렬
        $allowedSorts = [
            'scheduled_at', 'created_at', 'updated_at', 'interview_status',
            'interview_result', 'overall_score', 'interview_type'
        ];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('scheduled_at', 'desc');
        }

        // 페이지네이션
        $interviews = $query->paginate($perPage)->appends($request->query());

        // 통계 데이터
        $statistics = $this->getStatistics();

        // 오늘의 면접 일정
        $todayInterviews = $this->getTodayInterviews();

        // 면접관 목록
        $interviewers = $this->getInterviewers();

        // 필터 옵션 데이터
        $filterOptions = $this->getFilterOptions();

        return view('jiny-partner::admin.partner.interview.index', [
            'interviews' => $interviews,
            'statistics' => $statistics,
            'todayInterviews' => $todayInterviews,
            'interviewers' => $interviewers,
            'filterOptions' => $filterOptions,
            'currentFilters' => [
                'status' => $status,
                'result' => $result,
                'type' => $type,
                'interviewer' => $interviewer,
                'search' => $search,
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
                'per_page' => $perPage,
                'date_range' => $dateRange
            ],
            'pageTitle' => '파트너 면접 관리'
        ]);
    }

    /**
     * 통계 데이터 생성
     */
    private function getStatistics()
    {
        $stats = [
            'total' => PartnerInterview::count(),
            'scheduled' => PartnerInterview::where('interview_status', 'scheduled')->count(),
            'in_progress' => PartnerInterview::where('interview_status', 'in_progress')->count(),
            'completed' => PartnerInterview::where('interview_status', 'completed')->count(),
            'cancelled' => PartnerInterview::where('interview_status', 'cancelled')->count(),
            'no_show' => PartnerInterview::where('interview_status', 'no_show')->count(),
        ];

        $results = [
            'passed' => PartnerInterview::where('interview_result', 'pass')->count(),
            'failed' => PartnerInterview::where('interview_result', 'fail')->count(),
            'pending' => PartnerInterview::where('interview_result', 'pending')->count(),
            'hold' => PartnerInterview::where('interview_result', 'hold')->count(),
        ];

        // 이번 달 통계
        $thisMonth = [
            'scheduled' => PartnerInterview::whereMonth('scheduled_at', now()->month)
                ->whereYear('scheduled_at', now()->year)
                ->count(),
            'completed' => PartnerInterview::where('interview_status', 'completed')
                ->whereMonth('completed_at', now()->month)
                ->whereYear('completed_at', now()->year)
                ->count(),
            'passed' => PartnerInterview::where('interview_result', 'pass')
                ->whereMonth('completed_at', now()->month)
                ->whereYear('completed_at', now()->year)
                ->count(),
        ];

        // 통과율 계산
        $totalCompleted = $results['passed'] + $results['failed'];
        $passRate = $totalCompleted > 0 ? round(($results['passed'] / $totalCompleted) * 100, 1) : 0;

        // 평균 면접 시간
        $avgDuration = PartnerInterview::whereNotNull('duration_minutes')
            ->avg('duration_minutes');

        // 평균 평점
        $avgScore = PartnerInterview::whereNotNull('overall_score')
            ->avg('overall_score');

        return [
            'counts' => $stats,
            'results' => $results,
            'this_month' => $thisMonth,
            'pass_rate' => $passRate,
            'avg_duration_minutes' => $avgDuration ? round($avgDuration, 1) : 0,
            'avg_score' => $avgScore ? round($avgScore, 2) : 0,
            'today_count' => PartnerInterview::whereDate('scheduled_at', today())->count(),
            'urgent_count' => PartnerInterview::where('interview_status', 'scheduled')
                ->where('scheduled_at', '<=', now()->addDays(1))
                ->count()
        ];
    }

    /**
     * 오늘의 면접 일정
     */
    private function getTodayInterviews()
    {
        return PartnerInterview::with(['user', 'application', 'interviewer'])
            ->whereDate('scheduled_at', today())
            ->whereIn('interview_status', ['scheduled', 'in_progress'])
            ->orderBy('scheduled_at')
            ->limit(10)
            ->get();
    }

    /**
     * 면접관 목록
     */
    private function getInterviewers()
    {
        return PartnerInterview::join('users', 'partner_interviews.interviewer_id', '=', 'users.id')
            ->select('users.id', 'users.name')
            ->distinct()
            ->orderBy('users.name')
            ->get();
    }

    /**
     * 필터 옵션 데이터
     */
    private function getFilterOptions()
    {
        return [
            'statuses' => [
                'all' => '전체',
                'scheduled' => '예정',
                'in_progress' => '진행중',
                'completed' => '완료',
                'cancelled' => '취소',
                'rescheduled' => '재일정',
                'no_show' => '불참'
            ],
            'results' => [
                'all' => '전체',
                'pass' => '통과',
                'fail' => '불합격',
                'pending' => '검토중',
                'hold' => '보류',
                'next_round' => '다음단계'
            ],
            'types' => [
                'all' => '전체',
                'phone' => '전화면접',
                'video' => '화상면접',
                'in_person' => '대면면접',
                'written' => '서면면접'
            ],
            'date_ranges' => [
                'all' => '전체',
                'today' => '오늘',
                'this_week' => '이번주',
                'this_month' => '이번달',
                'upcoming' => '예정된 면접',
                'past' => '지난 면접'
            ],
            'sort_options' => [
                'scheduled_at' => '면접일시',
                'created_at' => '생성일시',
                'updated_at' => '최종 수정일',
                'interview_status' => '상태',
                'interview_result' => '결과',
                'overall_score' => '종합평점'
            ],
            'per_page_options' => [10, 15, 25, 50]
        ];
    }
}