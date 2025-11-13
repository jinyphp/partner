<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerInterview;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerInterview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatisticsController extends Controller
{
    /**
     * 면접 통계 대시보드
     */
    public function __invoke(Request $request)
    {
        $period = $request->get('period', '30_days');
        $interviewer = $request->get('interviewer', 'all');

        // 기간 설정
        $dateRange = $this->getDateRange($period);

        // 기본 쿼리
        $query = PartnerInterview::whereBetween('created_at', $dateRange);

        if ($interviewer !== 'all') {
            $query->where('interviewer_id', $interviewer);
        }

        // 전체 통계
        $overallStats = $this->getOverallStatistics($query);

        // 일별/주별/월별 통계
        $timeSeriesStats = $this->getTimeSeriesStatistics($query, $period);

        // 면접관별 통계
        $interviewerStats = $this->getInterviewerStatistics($dateRange);

        // 평점 분포
        $scoreDistribution = $this->getScoreDistribution($query);

        // 면접 유형별 통계
        $typeStats = $this->getTypeStatistics($query);

        // 결과별 통계
        $resultStats = $this->getResultStatistics($query);

        // 추천인별 통계
        $referrerStats = $this->getReferrerStatistics($query);

        // 면접관 목록
        $interviewers = \App\Models\User::where('isAdmin', true)
            ->where('is_blocked', false)
            ->whereHas('conductedInterviews')
            ->orderBy('name')
            ->get();

        return view('jiny-partner::admin.partner.interview.statistics', [
            'overallStats' => $overallStats,
            'timeSeriesStats' => $timeSeriesStats,
            'interviewerStats' => $interviewerStats,
            'scoreDistribution' => $scoreDistribution,
            'typeStats' => $typeStats,
            'resultStats' => $resultStats,
            'referrerStats' => $referrerStats,
            'interviewers' => $interviewers,
            'currentPeriod' => $period,
            'currentInterviewer' => $interviewer,
            'pageTitle' => '면접 통계'
        ]);
    }

    /**
     * 날짜 범위 계산
     */
    private function getDateRange($period)
    {
        $endDate = now();

        $startDate = match($period) {
            '7_days' => $endDate->copy()->subDays(7),
            '30_days' => $endDate->copy()->subDays(30),
            '90_days' => $endDate->copy()->subDays(90),
            '6_months' => $endDate->copy()->subMonths(6),
            '1_year' => $endDate->copy()->subYear(),
            'this_month' => $endDate->copy()->startOfMonth(),
            'last_month' => $endDate->copy()->subMonth()->startOfMonth(),
            'this_year' => $endDate->copy()->startOfYear(),
            default => $endDate->copy()->subDays(30)
        };

        return [$startDate, $endDate];
    }

    /**
     * 전체 통계
     */
    private function getOverallStatistics($query)
    {
        $total = $query->count();
        $completed = $query->where('interview_status', 'completed')->count();
        $scheduled = $query->where('interview_status', 'scheduled')->count();
        $inProgress = $query->where('interview_status', 'in_progress')->count();
        $cancelled = $query->where('interview_status', 'cancelled')->count();

        $passed = $query->where('interview_result', 'pass')->count();
        $failed = $query->where('interview_result', 'fail')->count();

        $avgScore = $query->whereNotNull('overall_score')->avg('overall_score');
        $avgDuration = $query->whereNotNull('duration_minutes')->avg('duration_minutes');

        // 완료율
        $completionRate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;

        // 통과율 (완료된 면접 중)
        $totalCompleted = $passed + $failed;
        $passRate = $totalCompleted > 0 ? round(($passed / $totalCompleted) * 100, 1) : 0;

        return [
            'total' => $total,
            'completed' => $completed,
            'scheduled' => $scheduled,
            'in_progress' => $inProgress,
            'cancelled' => $cancelled,
            'passed' => $passed,
            'failed' => $failed,
            'completion_rate' => $completionRate,
            'pass_rate' => $passRate,
            'avg_score' => $avgScore ? round($avgScore, 2) : 0,
            'avg_duration' => $avgDuration ? round($avgDuration, 1) : 0
        ];
    }

    /**
     * 시계열 통계
     */
    private function getTimeSeriesStatistics($query, $period)
    {
        $groupBy = match($period) {
            '7_days', '30_days' => 'DATE(created_at)',
            '90_days', '6_months' => 'YEARWEEK(created_at)',
            default => 'YEAR(created_at), MONTH(created_at)'
        };

        return $query->select(
                DB::raw($groupBy . ' as period'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN interview_status = "completed" THEN 1 ELSE 0 END) as completed'),
                DB::raw('SUM(CASE WHEN interview_result = "pass" THEN 1 ELSE 0 END) as passed'),
                DB::raw('AVG(CASE WHEN overall_score IS NOT NULL THEN overall_score END) as avg_score')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(function ($item) {
                $item->avg_score = $item->avg_score ? round($item->avg_score, 2) : 0;
                return $item;
            });
    }

    /**
     * 면접관별 통계
     */
    private function getInterviewerStatistics($dateRange)
    {
        return PartnerInterview::join('users', 'partner_interviews.interviewer_id', '=', 'users.id')
            ->whereBetween('partner_interviews.created_at', $dateRange)
            ->select(
                'users.id',
                'users.name',
                DB::raw('COUNT(*) as total_interviews'),
                DB::raw('SUM(CASE WHEN interview_status = "completed" THEN 1 ELSE 0 END) as completed'),
                DB::raw('SUM(CASE WHEN interview_result = "pass" THEN 1 ELSE 0 END) as passed'),
                DB::raw('AVG(CASE WHEN overall_score IS NOT NULL THEN overall_score END) as avg_score'),
                DB::raw('AVG(CASE WHEN duration_minutes IS NOT NULL THEN duration_minutes END) as avg_duration')
            )
            ->groupBy('users.id', 'users.name')
            ->orderBy('total_interviews', 'desc')
            ->get()
            ->map(function ($item) {
                $item->pass_rate = $item->completed > 0 ? round(($item->passed / $item->completed) * 100, 1) : 0;
                $item->avg_score = $item->avg_score ? round($item->avg_score, 2) : 0;
                $item->avg_duration = $item->avg_duration ? round($item->avg_duration, 1) : 0;
                return $item;
            });
    }

    /**
     * 점수 분포
     */
    private function getScoreDistribution($query)
    {
        return [
            'technical' => $this->getScoreRangeDistribution($query, 'technical_score'),
            'communication' => $this->getScoreRangeDistribution($query, 'communication_score'),
            'experience' => $this->getScoreRangeDistribution($query, 'experience_score'),
            'attitude' => $this->getScoreRangeDistribution($query, 'attitude_score'),
            'overall' => $this->getScoreRangeDistribution($query, 'overall_score')
        ];
    }

    /**
     * 점수 범위별 분포
     */
    private function getScoreRangeDistribution($query, $scoreField)
    {
        return $query->whereNotNull($scoreField)
            ->select(
                DB::raw('
                    CASE
                        WHEN ' . $scoreField . ' >= 4.5 THEN "4.5-5.0"
                        WHEN ' . $scoreField . ' >= 4.0 THEN "4.0-4.4"
                        WHEN ' . $scoreField . ' >= 3.5 THEN "3.5-3.9"
                        WHEN ' . $scoreField . ' >= 3.0 THEN "3.0-3.4"
                        WHEN ' . $scoreField . ' >= 2.5 THEN "2.5-2.9"
                        WHEN ' . $scoreField . ' >= 2.0 THEN "2.0-2.4"
                        WHEN ' . $scoreField . ' >= 1.5 THEN "1.5-1.9"
                        WHEN ' . $scoreField . ' >= 1.0 THEN "1.0-1.4"
                        ELSE "0.0-0.9"
                    END as score_range
                '),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('score_range')
            ->orderBy('score_range', 'desc')
            ->get();
    }

    /**
     * 면접 유형별 통계
     */
    private function getTypeStatistics($query)
    {
        return $query->select(
                'interview_type',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN interview_status = "completed" THEN 1 ELSE 0 END) as completed'),
                DB::raw('SUM(CASE WHEN interview_result = "pass" THEN 1 ELSE 0 END) as passed'),
                DB::raw('AVG(CASE WHEN overall_score IS NOT NULL THEN overall_score END) as avg_score')
            )
            ->groupBy('interview_type')
            ->get()
            ->map(function ($item) {
                $item->pass_rate = $item->completed > 0 ? round(($item->passed / $item->completed) * 100, 1) : 0;
                $item->avg_score = $item->avg_score ? round($item->avg_score, 2) : 0;
                $item->type_label = match($item->interview_type) {
                    'video' => '화상면접',
                    'phone' => '전화면접',
                    'in_person' => '대면면접',
                    'written' => '서면면접',
                    default => $item->interview_type
                };
                return $item;
            });
    }

    /**
     * 결과별 통계
     */
    private function getResultStatistics($query)
    {
        return $query->whereNotNull('interview_result')
            ->select(
                'interview_result',
                DB::raw('COUNT(*) as count'),
                DB::raw('AVG(CASE WHEN overall_score IS NOT NULL THEN overall_score END) as avg_score')
            )
            ->groupBy('interview_result')
            ->get()
            ->map(function ($item) {
                $item->avg_score = $item->avg_score ? round($item->avg_score, 2) : 0;
                $item->result_label = match($item->interview_result) {
                    'pass' => '통과',
                    'fail' => '불합격',
                    'pending' => '검토중',
                    'hold' => '보류',
                    'next_round' => '다음단계',
                    default => $item->interview_result
                };
                return $item;
            });
    }

    /**
     * 추천인별 통계
     */
    private function getReferrerStatistics($query)
    {
        return $query->whereNotNull('referrer_partner_id')
            ->select(
                'referrer_code',
                'referrer_name',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN interview_status = "completed" THEN 1 ELSE 0 END) as completed'),
                DB::raw('SUM(CASE WHEN interview_result = "pass" THEN 1 ELSE 0 END) as passed'),
                DB::raw('AVG(CASE WHEN overall_score IS NOT NULL THEN overall_score END) as avg_score')
            )
            ->groupBy('referrer_code', 'referrer_name')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $item->pass_rate = $item->completed > 0 ? round(($item->passed / $item->completed) * 100, 1) : 0;
                $item->avg_score = $item->avg_score ? round($item->avg_score, 2) : 0;
                return $item;
            });
    }
}