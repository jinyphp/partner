<?php

namespace Jiny\Partner\Http\Controllers\Home\PartnerApproval;

use Jiny\Auth\Http\Controllers\HomeController;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DetailController extends HomeController
{
    /**
     * 파트너 신청 상세 정보 표시
     */
    public function __invoke(Request $request, $id)
    {
        // Step1. JWT 인증 확인 (HomeController의 auth 메서드 사용)
        $user = $this->auth($request);
        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'JWT 인증이 필요합니다. 로그인해 주세요.')
                ->with('info', '파트너 서비스는 로그인 후 이용하실 수 있습니다.');
        }

        Log::info('Partner application detail access', [
            'user_id' => $user->id,
            'user_uuid' => $user->uuid,
            'application_id' => $id,
            'user_email' => $user->email
        ]);

        // Step2. 나의 파트너 정보 확인
        $myPartner = PartnerUser::with(['partnerTier', 'partnerType'])
            ->where('user_uuid', $user->uuid)
            ->first();

        // 파트너 미등록시 intro로 리다이렉션
        if (!$myPartner) {
            return redirect()->route('home.partner.intro')
                ->with('info', '파트너 프로그램에 가입하시면 파트너 관리 기능을 이용하실 수 있습니다.')
                ->with('userInfo', [
                    'name' => $user->name ?? '',
                    'email' => $user->email ?? '',
                    'phone' => $user->profile->phone ?? '',
                    'uuid' => $user->uuid
                ]);
        }

        // Step3. 신청서 정보 조회
        $application = PartnerApplication::find($id);

        if (!$application) {
            return redirect()->route('home.partner.approval.index')
                ->with('error', '요청하신 신청서를 찾을 수 없습니다.');
        }

        // Step4. 검토 권한 확인 (간소화된 버전)
        $hasPermission = $this->checkViewPermission($application, $myPartner);

        if (!$hasPermission) {
            Log::warning('Unauthorized application detail access attempt', [
                'user_uuid' => $user->uuid,
                'application_id' => $id,
                'application_referrer' => $application->referrer_partner_id ?? null
            ]);

            return redirect()->route('home.partner.approval.index')
                ->with('error', '해당 신청서에 대한 조회 권한이 없습니다.');
        }

        // Step5. 신청서 상세 정보 가공
        $applicationDetails = $this->formatApplicationDetails($application);

        // Step6. 승인 권한 정보
        $permissions = $this->getApprovalPermissions($myPartner);

        // Step7. 추가 데이터 수집
        $reviewData = [
            'application_summary' => $this->generateApplicationSummary($application),
            'referrer_info' => $this->getReferrerInfo($application),
            'document_status' => $this->checkDocumentStatus($application),
            'approval_recommendation' => $this->generateApprovalRecommendation($application),
            'approval_actions' => $this->getAvailableActions($application, $myPartner),
            'tier_analysis' => $this->analyzeTierFitness($application)
        ];

        return view('jiny-partner::home.partner-approval.detail', [
            'user' => $user,
            'myPartner' => $myPartner,
            'application' => $application,
            'applicationDetails' => $applicationDetails,
            'permissions' => $permissions,
            'reviewData' => $reviewData,
            'pageTitle' => '파트너 신청 상세보기'
        ]);
    }

    /**
     * 신청서 조회 권한 확인 (더 관대한 권한)
     */
    private function checkViewPermission(PartnerApplication $application, PartnerUser $myPartner): bool
    {
        // 방법 1: referrer_partner_id로 확인
        if ($application->referrer_partner_id === $myPartner->id) {
            return true;
        }

        // 방법 2: referral_code로 확인
        if ($application->referral_code === $myPartner->partner_code) {
            return true;
        }

        // 방법 3: referral_details에서 확인
        $referralDetails = $application->referral_details ?? [];
        if (isset($referralDetails['referrer_code']) && $referralDetails['referrer_code'] === $myPartner->partner_code) {
            return true;
        }

        // 방법 4: 상위 파트너 권한 확인 (Gold 이상)
        $tierName = $myPartner->partnerTier->tier_name ?? 'Bronze';
        if (in_array($tierName, ['Gold', 'Platinum'])) {
            return true; // Gold 이상은 모든 신청서 조회 가능
        }

        return false;
    }

    /**
     * 신청서 상세 정보 포맷팅
     */
    private function formatApplicationDetails(PartnerApplication $application): array
    {
        $personalInfo = $application->personal_info ?? [];
        $businessInfo = $application->business_info ?? [];
        $experienceInfo = $application->experience_info ?? [];
        $skillsInfo = $application->skills_info ?? [];
        $referralDetails = $application->referral_details ?? [];

        return [
            'id' => $application->id,
            'application_status' => $application->application_status,
            'expected_tier_level' => $application->expected_tier_level ?? 'Bronze',
            'submitted_at' => $application->submitted_at ?? $application->created_at,
            'completeness_score' => $this->calculateCompletenessScore($application),

            'personal' => [
                'name' => $personalInfo['name'] ?? '',
                'email' => $personalInfo['email'] ?? '',
                'phone' => $personalInfo['phone'] ?? '',
                'birth_date' => $personalInfo['birth_date'] ?? '',
                'address' => $personalInfo['address'] ?? ''
            ],

            'business' => [
                'company_name' => $businessInfo['company_name'] ?? '',
                'business_type' => $businessInfo['business_type'] ?? '',
                'business_number' => $businessInfo['business_number'] ?? '',
                'address' => $businessInfo['address'] ?? ''
            ],

            'experience' => [
                'total_years' => $experienceInfo['total_years'] ?? 0,
                'current_position' => $experienceInfo['current_position'] ?? '',
                'achievements' => $experienceInfo['achievements'] ?? [],
                'certifications' => $experienceInfo['certifications'] ?? []
            ],

            'skills' => [
                'technical_skills' => $skillsInfo['technical_skills'] ?? [],
                'primary_skills' => $skillsInfo['primary_skills'] ?? [],
                'languages' => $skillsInfo['languages'] ?? []
            ],

            'referral' => [
                'source' => $application->referral_source ?? '',
                'referrer_code' => $referralDetails['referrer_code'] ?? '',
                'referrer_name' => $referralDetails['referrer_name'] ?? ''
            ],

            'motivation' => $application->motivation ?? '',
            'documents' => $application->documents ?? []
        ];
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
            'target_tier' => $application->expected_tier_level ?? 'Bronze',
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
        if ($application->referrer_partner_id) {
            $referrer = PartnerUser::find($application->referrer_partner_id);
            if ($referrer) {
                return [
                    'uuid' => $referrer->user_uuid,
                    'name' => $referrer->name,
                    'tier' => $referrer->partnerTier->tier_name ?? 'Bronze',
                    'type' => $referrer->partnerType->type_name ?? 'General',
                    'total_referrals' => PartnerUser::where('referrer_uuid', $referrer->user_uuid)->count(),
                    'active_partners' => PartnerUser::where('referrer_uuid', $referrer->user_uuid)
                        ->where('status', 'active')
                        ->count()
                ];
            }
        }

        return null;
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
     * 승인 추천 생성
     */
    private function generateApprovalRecommendation(PartnerApplication $application): array
    {
        $experienceYears = $application->experience_info['total_years'] ?? 0;
        $skillsCount = count($application->skills_info['primary_skills'] ?? []);
        $hasPortfolio = !empty($application->documents['portfolio']);

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
            'reasons' => $reasons
        ];
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
     * 등급 적합성 분석
     */
    private function analyzeTierFitness(PartnerApplication $application): array
    {
        $targetTier = $application->expected_tier_level ?? 'Bronze';
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

    /**
     * 완성도 점수 계산
     */
    private function calculateCompletenessScore(PartnerApplication $application): int
    {
        $score = 0;
        $totalFields = 10;

        // 기본 정보 확인
        $personalInfo = $application->personal_info ?? [];
        if (!empty($personalInfo['name'])) $score++;
        if (!empty($personalInfo['email'])) $score++;
        if (!empty($personalInfo['phone'])) $score++;

        // 경력 정보
        $experienceInfo = $application->experience_info ?? [];
        if (!empty($experienceInfo['total_years']) && $experienceInfo['total_years'] > 0) $score++;
        if (!empty($experienceInfo['current_position'])) $score++;

        // 기술 정보
        $skillsInfo = $application->skills_info ?? [];
        if (!empty($skillsInfo['primary_skills'])) $score++;

        // 동기
        if (!empty($application->motivation)) $score++;

        // 문서 첨부
        $documents = $application->documents ?? [];
        if (!empty($documents['resume'])) $score++;
        if (!empty($documents['portfolio'])) $score++;
        if (!empty($documents['certificates'])) $score++;

        return (int)(($score / $totalFields) * 100);
    }

    /**
     * 승인 권한 정보 조회
     */
    private function getApprovalPermissions(PartnerUser $partner): array
    {
        $tierName = $partner->partnerTier->tier_name ?? 'Bronze';
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
                'monthly_limit' => 0,
                'max_managing' => 0
            ],
            'Silver' => [
                'can_approve' => true,
                'monthly_limit' => 2,
                'max_managing' => 5
            ],
            'Gold' => [
                'can_approve' => true,
                'monthly_limit' => 5,
                'max_managing' => 15
            ],
            'Platinum' => [
                'can_approve' => true,
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
}