<?php

namespace Jiny\Partner\Http\Controllers\Home\PartnerApproval;

use Jiny\Partner\Http\Controllers\Home\HomeController;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Services\PartnerActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ReviewController extends HomeController
{
    protected $activityLogService;

    public function __construct(PartnerActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * 상위 파트너의 신청서 세부 검토
     * 권한 범위 내 신청서만 검토 가능
     */
    public function __invoke(Request $request, $id)
    {
        // JWT 인증 확인
        $user = $this->auth($request);
        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'JWT 인증이 필요합니다.');
        }

        // 파트너 정보 확인
        $partner = PartnerUser::where('user_uuid', $user->uuid)->first();
        if (!$partner) {
            return redirect()->route('home.partner.regist.index')
                ->with('error', '파트너 등록이 필요합니다.');
        }

        // 승인 권한 확인
        $approvalPermissions = $this->getApprovalPermissions($partner);
        if (!$approvalPermissions['can_approve']) {
            return redirect()->route('home.partner.approval.index')
                ->with('error', '승인 권한이 없습니다.')
                ->with('info', $approvalPermissions['reason']);
        }

        // 신청서 조회
        $application = PartnerApplication::findOrFail($id);

        // 검토 권한 확인
        if (!$this->canReviewApplication($application, $partner, $approvalPermissions)) {
            return redirect()->route('home.partner.approval.pending')
                ->with('error', '이 신청서를 검토할 권한이 없습니다.')
                ->with('info', '권한 범위를 확인해 주세요.');
        }

        // 검토 활동 로그 기록
        $this->activityLogService->logActivity(
            $partner->user_uuid,
            'application_reviewed',
            "신청서 검토: {$application->id}",
            [
                'application_id' => $application->id,
                'applicant_uuid' => $application->user_uuid,
                'review_permissions' => $approvalPermissions
            ]
        );

        // 추가 검토 정보 수집
        $reviewData = $this->collectReviewData($application, $partner);

        return view('jiny-partner::home.partner-approval.review', [
            'application' => $application,
            'partner' => $partner,
            'permissions' => $approvalPermissions,
            'reviewData' => $reviewData,
            'pageTitle' => '신청서 검토'
        ]);
    }

    /**
     * 신청서 검토 권한 확인
     */
    private function canReviewApplication(PartnerApplication $application, PartnerUser $partner, array $permissions): bool
    {
        // 처리 완료된 신청서는 검토 불가
        if (in_array($application->application_status, ['approved', 'rejected'])) {
            return false;
        }

        // 직접 추천한 신청자인지 확인
        if ($application->referral_details['referrer_uuid'] ?? null === $partner->user_uuid) {
            return true;
        }

        // Gold 이상은 하위 파트너가 추천한 신청자도 검토 가능
        if (in_array($partner->tier_name, ['Gold', 'Platinum'])) {
            $referrerUuid = $application->referral_details['referrer_uuid'] ?? null;
            if ($referrerUuid) {
                $referrer = PartnerUser::where('user_uuid', $referrerUuid)->first();
                if ($referrer && $referrer->referrer_uuid === $partner->user_uuid) {
                    return true;
                }
            }
        }

        // Platinum은 타입 기반 추가 권한
        if ($partner->tier_name === 'Platinum' && $partner->type_name) {
            $targetType = $application->application_preferences['target_type'] ?? null;
            if ($targetType === $partner->type_name) {
                return true;
            }
        }

        // 등급 권한 확인
        $targetTier = $application->application_preferences['target_tier'] ?? 'Bronze';
        return in_array($targetTier, $permissions['approvable_tiers']);
    }

    /**
     * 검토용 데이터 수집
     */
    private function collectReviewData(PartnerApplication $application, PartnerUser $partner): array
    {
        $data = [
            'application_summary' => $this->generateApplicationSummary($application),
            'referrer_info' => $this->getReferrerInfo($application),
            'document_status' => $this->checkDocumentStatus($application),
            'approval_recommendation' => $this->generateApprovalRecommendation($application),
            'approval_actions' => $this->getAvailableActions($application, $partner),
            'similar_applications' => $this->findSimilarApplications($application),
            'tier_analysis' => $this->analyzeTierFitness($application)
        ];

        return $data;
    }

    /**
     * 신청서 요약 정보 생성
     */
    private function generateApplicationSummary(PartnerApplication $application): array
    {
        $personalInfo = $application->personal_info ?? [];
        $experienceInfo = $application->experience_info ?? [];
        $skillsInfo = $application->skills_info ?? [];

        return [
            'applicant_name' => $personalInfo['name'] ?? 'Unknown',
            'contact_info' => [
                'email' => $personalInfo['email'] ?? '',
                'phone' => $personalInfo['phone'] ?? ''
            ],
            'experience_years' => $experienceInfo['total_years'] ?? 0,
            'primary_skills' => $skillsInfo['primary_skills'] ?? [],
            'target_tier' => $application->application_preferences['target_tier'] ?? 'Bronze',
            'target_type' => $application->application_preferences['target_type'] ?? '',
            'application_date' => $application->created_at,
            'last_updated' => $application->updated_at,
            'current_status' => $application->application_status
        ];
    }

    /**
     * 추천인 정보 조회
     */
    private function getReferrerInfo(PartnerApplication $application): ?array
    {
        $referrerUuid = $application->referral_details['referrer_uuid'] ?? null;
        if (!$referrerUuid) {
            return null;
        }

        $referrer = PartnerUser::where('user_uuid', $referrerUuid)->first();
        if (!$referrer) {
            return null;
        }

        return [
            'uuid' => $referrer->user_uuid,
            'name' => $referrer->name,
            'tier' => $referrer->tier_name,
            'type' => $referrer->type_name,
            'total_referrals' => PartnerUser::where('referrer_uuid', $referrer->user_uuid)->count(),
            'active_partners' => PartnerUser::where('referrer_uuid', $referrer->user_uuid)
                ->where('status', 'active')
                ->count()
        ];
    }

    /**
     * 문서 상태 확인
     */
    private function checkDocumentStatus(PartnerApplication $application): array
    {
        $documents = $application->documents ?? [];
        $required_docs = ['resume', 'portfolio', 'certificates'];

        $status = [];
        foreach ($required_docs as $doc) {
            $status[$doc] = [
                'submitted' => isset($documents[$doc]) && !empty($documents[$doc]),
                'file_path' => $documents[$doc]['file_path'] ?? null,
                'file_size' => $documents[$doc]['file_size'] ?? 0,
                'uploaded_at' => $documents[$doc]['uploaded_at'] ?? null
            ];
        }

        return $status;
    }

    /**
     * 승인 권한 정보 조회 (IndexController와 동일한 로직)
     */
    private function getApprovalPermissions(PartnerUser $partner): array
    {
        $tierName = $partner->tier_name ?? 'Bronze';
        $currentApprovals = PartnerApplication::where('approved_by_uuid', $partner->user_uuid)
            ->where('application_status', 'approved')
            ->whereMonth('approval_date', now()->month)
            ->whereYear('approval_date', now()->year)
            ->count();

        $totalManaging = PartnerUser::where('referrer_uuid', $partner->user_uuid)
            ->where('status', 'active')
            ->count();

        $permissions = [
            'Bronze' => [
                'can_approve' => false,
                'reason' => 'Bronze 파트너는 승인 권한이 없습니다.',
                'approvable_tiers' => [],
                'monthly_limit' => 0,
                'max_managing' => 0
            ],
            'Silver' => [
                'can_approve' => true,
                'reason' => '',
                'approvable_tiers' => ['Bronze'],
                'monthly_limit' => 2,
                'max_managing' => 5
            ],
            'Gold' => [
                'can_approve' => true,
                'reason' => '',
                'approvable_tiers' => ['Bronze', 'Silver'],
                'monthly_limit' => 5,
                'max_managing' => 15
            ],
            'Platinum' => [
                'can_approve' => true,
                'reason' => '',
                'approvable_tiers' => ['Bronze', 'Silver', 'Gold'],
                'monthly_limit' => 15,
                'max_managing' => 50
            ]
        ];

        $basePermission = $permissions[$tierName] ?? $permissions['Bronze'];

        return array_merge($basePermission, [
            'current_month_approvals' => $currentApprovals,
            'remaining_monthly' => max(0, $basePermission['monthly_limit'] - $currentApprovals),
            'total_managing' => $totalManaging,
            'remaining_capacity' => max(0, $basePermission['max_managing'] - $totalManaging)
        ]);
    }

    /**
     * 승인 추천 생성
     */
    private function generateApprovalRecommendation(PartnerApplication $application): array
    {
        $experienceYears = $application->experience_info['total_years'] ?? 0;
        $skillsCount = count($application->skills_info['primary_skills'] ?? []);
        $hasPortfolio = !empty($application->documents['portfolio']);
        $targetTier = $application->application_preferences['target_tier'] ?? 'Bronze';

        $score = 0;
        $reasons = [];

        // 경험 점수
        if ($experienceYears >= 5) {
            $score += 40;
            $reasons[] = '풍부한 경험 (' . $experienceYears . '년)';
        } elseif ($experienceYears >= 3) {
            $score += 25;
            $reasons[] = '적절한 경험 (' . $experienceYears . '년)';
        } elseif ($experienceYears >= 1) {
            $score += 10;
            $reasons[] = '최소 경험 (' . $experienceYears . '년)';
        }

        // 기술 점수
        if ($skillsCount >= 5) {
            $score += 30;
            $reasons[] = '다양한 기술 보유 (' . $skillsCount . '개)';
        } elseif ($skillsCount >= 3) {
            $score += 20;
            $reasons[] = '기본 기술 보유 (' . $skillsCount . '개)';
        }

        // 포트폴리오 점수
        if ($hasPortfolio) {
            $score += 20;
            $reasons[] = '포트폴리오 제출';
        }

        // 자격증 점수
        if (!empty($application->documents['certificates'])) {
            $score += 10;
            $reasons[] = '자격증 보유';
        }

        $recommendation = 'reject'; // 기본값
        if ($score >= 70) {
            $recommendation = 'approve';
        } elseif ($score >= 50) {
            $recommendation = 'interview';
        }

        return [
            'score' => $score,
            'recommendation' => $recommendation,
            'reasons' => $reasons,
            'tier_match' => $this->checkTierMatch($targetTier, $score)
        ];
    }

    /**
     * 등급 매치 확인
     */
    private function checkTierMatch(string $targetTier, int $score): array
    {
        $tierRequirements = [
            'Bronze' => 30,
            'Silver' => 60,
            'Gold' => 80,
            'Platinum' => 90
        ];

        $requiredScore = $tierRequirements[$targetTier] ?? 30;
        $matches = $score >= $requiredScore;

        return [
            'matches' => $matches,
            'required_score' => $requiredScore,
            'actual_score' => $score,
            'suggested_tier' => $this->suggestTier($score)
        ];
    }

    /**
     * 점수 기반 등급 제안
     */
    private function suggestTier(int $score): string
    {
        if ($score >= 90) return 'Platinum';
        if ($score >= 80) return 'Gold';
        if ($score >= 60) return 'Silver';
        return 'Bronze';
    }

    /**
     * 사용 가능한 액션
     */
    private function getAvailableActions(PartnerApplication $application, PartnerUser $partner): array
    {
        $actions = [];

        // 현재 상태에 따른 액션
        switch ($application->application_status) {
            case 'submitted':
            case 'reviewing':
                $actions[] = 'approve';
                $actions[] = 'reject';
                $actions[] = 'request_interview';
                break;
            case 'interview':
                $actions[] = 'approve';
                $actions[] = 'reject';
                break;
        }

        return $actions;
    }

    /**
     * 유사한 신청서 검색
     */
    private function findSimilarApplications(PartnerApplication $application): array
    {
        $targetTier = $application->application_preferences['target_tier'] ?? 'Bronze';
        $targetType = $application->application_preferences['target_type'] ?? '';

        return PartnerApplication::where('id', '!=', $application->id)
            ->whereJsonContains('application_preferences->target_tier', $targetTier)
            ->when($targetType, function ($query, $targetType) {
                return $query->whereJsonContains('application_preferences->target_type', $targetType);
            })
            ->whereIn('application_status', ['approved', 'rejected'])
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get(['id', 'application_status', 'updated_at', 'personal_info', 'application_preferences'])
            ->toArray();
    }

    /**
     * 등급 적합성 분석
     */
    private function analyzeTierFitness(PartnerApplication $application): array
    {
        $targetTier = $application->application_preferences['target_tier'] ?? 'Bronze';
        $experienceYears = $application->experience_info['total_years'] ?? 0;
        $skillsCount = count($application->skills_info['primary_skills'] ?? []);

        $fitness = [
            'Bronze' => $this->calculateTierFitness('Bronze', $experienceYears, $skillsCount),
            'Silver' => $this->calculateTierFitness('Silver', $experienceYears, $skillsCount),
            'Gold' => $this->calculateTierFitness('Gold', $experienceYears, $skillsCount),
            'Platinum' => $this->calculateTierFitness('Platinum', $experienceYears, $skillsCount)
        ];

        return [
            'target_tier' => $targetTier,
            'fitness_scores' => $fitness,
            'best_match' => array_keys($fitness, max($fitness))[0]
        ];
    }

    /**
     * 등급별 적합성 점수 계산
     */
    private function calculateTierFitness(string $tier, int $experienceYears, int $skillsCount): int
    {
        $requirements = [
            'Bronze' => ['experience' => 0, 'skills' => 1],
            'Silver' => ['experience' => 2, 'skills' => 3],
            'Gold' => ['experience' => 4, 'skills' => 5],
            'Platinum' => ['experience' => 6, 'skills' => 7]
        ];

        $req = $requirements[$tier];
        $experienceScore = min(100, ($experienceYears / max($req['experience'], 1)) * 100);
        $skillsScore = min(100, ($skillsCount / $req['skills']) * 100);

        return (int) (($experienceScore + $skillsScore) / 2);
    }
}