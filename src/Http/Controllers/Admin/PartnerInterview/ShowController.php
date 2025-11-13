<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerInterview;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerInterview;
use Illuminate\Http\Request;

class ShowController extends Controller
{
    /**
     * 파트너 면접 상세보기
     */
    public function __invoke(Request $request, $id)
    {
        $interview = PartnerInterview::with([
            'user',
            'application',
            'referrerPartner',
            'interviewer',
            'creator',
            'updater'
        ])->findOrFail($id);

        // 면접 히스토리 (같은 지원자의 다른 면접들)
        $interviewHistory = PartnerInterview::where('application_id', $interview->application_id)
            ->where('id', '!=', $interview->id)
            ->with(['interviewer'])
            ->orderBy('scheduled_at', 'desc')
            ->get();

        // 면접 로그 정리
        $logs = $interview->interview_logs ?? [];
        usort($logs, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        // 이전/다음 면접
        $navigation = $this->getNavigation($interview);

        // 면접 평가 통계
        $evaluationStats = $this->getEvaluationStats($interview);

        return view('jiny-partner::admin.partner.interview.show', [
            'interview' => $interview,
            'interviewHistory' => $interviewHistory,
            'logs' => $logs,
            'navigation' => $navigation,
            'evaluationStats' => $evaluationStats,
            'pageTitle' => '면접 상세보기 - ' . $interview->name
        ]);
    }

    /**
     * 이전/다음 면접 네비게이션
     */
    private function getNavigation($currentInterview)
    {
        $prevInterview = PartnerInterview::where('id', '<', $currentInterview->id)
            ->orderBy('id', 'desc')
            ->select('id', 'name', 'interview_status', 'scheduled_at')
            ->first();

        $nextInterview = PartnerInterview::where('id', '>', $currentInterview->id)
            ->orderBy('id', 'asc')
            ->select('id', 'name', 'interview_status', 'scheduled_at')
            ->first();

        return [
            'prev' => $prevInterview,
            'next' => $nextInterview
        ];
    }

    /**
     * 면접 평가 통계
     */
    private function getEvaluationStats($interview)
    {
        $stats = [
            'total_score' => 0,
            'score_breakdown' => [],
            'strengths' => [],
            'areas_for_improvement' => []
        ];

        // 점수 분석
        $scores = [
            'technical_score' => '기술역량',
            'communication_score' => '의사소통',
            'experience_score' => '경험평가',
            'attitude_score' => '태도평가'
        ];

        $totalScore = 0;
        $scoreCount = 0;

        foreach ($scores as $field => $label) {
            $score = $interview->{$field};
            if ($score !== null) {
                $stats['score_breakdown'][$label] = $score;
                $totalScore += $score;
                $scoreCount++;

                // 강점/개선점 분석
                if ($score >= 4.0) {
                    $stats['strengths'][] = $label . ' 우수 (' . $score . '점)';
                } elseif ($score <= 2.5) {
                    $stats['areas_for_improvement'][] = $label . ' 보완 필요 (' . $score . '점)';
                }
            }
        }

        if ($scoreCount > 0) {
            $stats['total_score'] = round($totalScore / $scoreCount, 2);
        }

        // 종합 평가
        $overallScore = $interview->overall_score ?? $stats['total_score'];
        $stats['overall_assessment'] = $this->getOverallAssessment($overallScore);

        return $stats;
    }

    /**
     * 종합 평가 메시지
     */
    private function getOverallAssessment($score)
    {
        if ($score >= 4.5) {
            return [
                'level' => 'excellent',
                'message' => '매우 우수한 후보자입니다. 즉시 채용을 권장합니다.',
                'color' => 'success'
            ];
        } elseif ($score >= 3.5) {
            return [
                'level' => 'good',
                'message' => '좋은 후보자입니다. 채용을 권장합니다.',
                'color' => 'primary'
            ];
        } elseif ($score >= 2.5) {
            return [
                'level' => 'average',
                'message' => '평균적인 후보자입니다. 추가 검토가 필요합니다.',
                'color' => 'warning'
            ];
        } else {
            return [
                'level' => 'poor',
                'message' => '기준에 미달하는 후보자입니다. 채용을 권장하지 않습니다.',
                'color' => 'danger'
            ];
        }
    }
}