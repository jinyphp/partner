<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerApproval;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerTier;
use Illuminate\Http\Request;

class ShowController extends Controller
{
    /**
     * 파트너 신청서 상세보기
     */
    public function __invoke(Request $request, $id)
    {
        $application = PartnerApplication::with(['user', 'approver', 'rejector', 'referrerPartner'])
            ->findOrFail($id);

        // 완성도 점수 계산
        $completenessScore = $application->getCompletenessScore();

        // 평가 기준 분석
        $evaluation = $this->evaluateApplication($application);

        // 파트너 등급 정보
        $partnerTiers = PartnerTier::active()->orderBy('priority_level')->get();

        // 이전/다음 신청서
        $navigation = $this->getNavigation($application);

        return view('jiny-partner::admin.partner-approval.show', [
            'application' => $application,
            'completenessScore' => $completenessScore,
            'evaluation' => $evaluation,
            'partnerTiers' => $partnerTiers,
            'navigation' => $navigation,
            'pageTitle' => '신청서 상세보기 - ' . ($application->personal_info['name'] ?? 'Unknown')
        ]);
    }

    /**
     * 신청서 평가 분석
     */
    private function evaluateApplication($application)
    {
        $evaluation = [
            'strengths' => [],
            'concerns' => [],
            'recommendations' => [],
            'overall_score' => 0
        ];

        $score = 0;

        // 경력 평가
        $totalYears = $application->experience_info['total_years'] ?? 0;
        if ($totalYears >= 5) {
            $evaluation['strengths'][] = '풍부한 경력 (' . $totalYears . '년)';
            $score += 25;
        } elseif ($totalYears >= 2) {
            $evaluation['strengths'][] = '적절한 경력 (' . $totalYears . '년)';
            $score += 15;
        } else {
            $evaluation['concerns'][] = '경력 부족 (' . $totalYears . '년)';
            $score += 5;
        }

        // 기술 스택 평가
        $skills = $application->skills_info['skills'] ?? [];
        $skillCount = count($skills);
        if ($skillCount >= 8) {
            $evaluation['strengths'][] = '다양한 기술 스택 (' . $skillCount . '개)';
            $score += 20;
        } elseif ($skillCount >= 5) {
            $evaluation['strengths'][] = '적절한 기술 스택 (' . $skillCount . '개)';
            $score += 15;
        } else {
            $evaluation['concerns'][] = '제한적인 기술 스택 (' . $skillCount . '개)';
            $score += 8;
        }

        // 자격증 평가
        $certifications = $application->skills_info['certifications'] ?? [];
        if (count($certifications) >= 2) {
            $evaluation['strengths'][] = '관련 자격증 보유';
            $score += 15;
        } elseif (count($certifications) >= 1) {
            $evaluation['strengths'][] = '자격증 보유';
            $score += 10;
        }

        // 포트폴리오 평가
        $hasPortfolio = isset($application->documents['portfolio']);
        $hasPortfolioUrl = !empty($application->experience_info['portfolio_url'] ?? '');

        if ($hasPortfolio || $hasPortfolioUrl) {
            $evaluation['strengths'][] = '포트폴리오 제출';
            $score += 15;
        } else {
            $evaluation['concerns'][] = '포트폴리오 미제출';
        }

        // 희망 시급 평가
        $hourlyRate = $application->expected_hourly_rate ?? 0;
        if ($hourlyRate > 50000) {
            $evaluation['concerns'][] = '높은 희망 시급 (' . number_format($hourlyRate) . '원)';
        } elseif ($hourlyRate < 20000) {
            $evaluation['concerns'][] = '낮은 희망 시급 (' . number_format($hourlyRate) . '원)';
        } else {
            $evaluation['strengths'][] = '적정 희망 시급 (' . number_format($hourlyRate) . '원)';
            $score += 10;
        }

        // 자기소개 평가
        $bio = $application->experience_info['bio'] ?? '';
        if (strlen($bio) >= 200) {
            $evaluation['strengths'][] = '상세한 자기소개';
            $score += 10;
        } elseif (strlen($bio) >= 100) {
            $evaluation['strengths'][] = '적절한 자기소개';
            $score += 5;
        } else {
            $evaluation['concerns'][] = '간략한 자기소개';
        }

        // 추천사항 생성
        if ($score >= 80) {
            $evaluation['recommendations'][] = '승인 권장 - 우수한 후보자';
        } elseif ($score >= 60) {
            $evaluation['recommendations'][] = '면접 후 판단 권장';
        } else {
            $evaluation['recommendations'][] = '추가 검토 필요';
        }

        // 개선 필요 사항
        if (count($evaluation['concerns']) > 0) {
            $evaluation['recommendations'][] = '부족한 부분에 대한 보완 요청 고려';
        }

        $evaluation['overall_score'] = min(100, $score);

        return $evaluation;
    }

    /**
     * 이전/다음 신청서 네비게이션
     */
    private function getNavigation($currentApplication)
    {
        $prevApplication = PartnerApplication::where('id', '<', $currentApplication->id)
            ->where('application_status', '!=', 'draft')
            ->orderBy('id', 'desc')
            ->select('id', 'personal_info', 'application_status')
            ->first();

        $nextApplication = PartnerApplication::where('id', '>', $currentApplication->id)
            ->where('application_status', '!=', 'draft')
            ->orderBy('id', 'asc')
            ->select('id', 'personal_info', 'application_status')
            ->first();

        return [
            'prev' => $prevApplication,
            'next' => $nextApplication
        ];
    }
}