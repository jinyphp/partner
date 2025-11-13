<?php

namespace Jiny\Partner\Http\Controllers\Home\PartnerApproval;

use Jiny\Auth\Http\Controllers\HomeController;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IndexController extends HomeController
{
    /**
     * 파트너 승인 관리 대시보드
     * 나의 파트너 코드로 신청된 파트너 목록 표시
     */
    public function __invoke(Request $request)
    {
        // Step1. JWT 인증 확인 (HomeController의 auth 메서드 사용)
        $user = $this->auth($request);
        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'JWT 인증이 필요합니다. 로그인해 주세요.')
                ->with('info', '파트너 서비스는 로그인 후 이용하실 수 있습니다.');
        }

        Log::info('Partner approval access', [
            'user_id' => $user->id,
            'user_uuid' => $user->uuid,
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

        // Step3. 나의 파트너 코드로 신청된 파트너들 조회
        $partnersAppliedWithMyCode = $this->getPartnersAppliedWithMyCode($myPartner);

        // Step4. 승인 대기 중인 신청서들 조회
        $pendingApplications = $this->getApplicationsWithMyCode($myPartner);

        return view('jiny-partner::home.partner-approval.index', [
            'user' => $user,
            'myPartner' => $myPartner,
            'partnersAppliedWithMyCode' => $partnersAppliedWithMyCode,
            'pendingApplications' => $pendingApplications,
            'pageTitle' => '파트너 승인 관리'
        ]);
    }

    /**
     * 나의 파트너 코드로 신청된 파트너들 조회
     */
    private function getPartnersAppliedWithMyCode(PartnerUser $myPartner)
    {
        // 방법 1: profile_data에서 referrer_info로 검색
        $partnersFromProfile = PartnerUser::with(['partnerTier', 'partnerType'])
            ->whereJsonContains('profile_data->referrer_info->referrer_code', $myPartner->partner_code)
            ->get();

        // 방법 2: 신청서를 통해 간접적으로 검색
        $partnerIdsFromApplications = PartnerApplication::where('referrer_partner_id', $myPartner->id)
            ->where('application_status', 'approved')
            ->pluck('user_uuid');

        $partnersFromApplications = PartnerUser::with(['partnerTier', 'partnerType'])
            ->whereIn('user_uuid', $partnerIdsFromApplications)
            ->get();

        // 두 결과 합치고 중복 제거
        $allPartners = $partnersFromProfile->merge($partnersFromApplications)->unique('id');

        return $allPartners->sortByDesc('partner_joined_at')
            ->map(function($partner) {
                return [
                    'id' => $partner->id,
                    'name' => $partner->name,
                    'email' => $partner->email,
                    'partner_code' => $partner->partner_code,
                    'tier_name' => $partner->partnerTier->tier_name ?? 'Bronze',
                    'type_name' => $partner->partnerType->type_name ?? 'General',
                    'status' => $partner->status,
                    'joined_at' => $partner->partner_joined_at,
                    'total_sales' => $partner->total_sales ?? 0,
                    'monthly_sales' => $partner->monthly_sales ?? 0,
                    'earned_commissions' => $partner->earned_commissions ?? 0,
                    'children_count' => $partner->children_count ?? 0,
                    'last_activity_at' => $partner->last_activity_at
                ];
            });
    }

    /**
     * 나의 파트너 코드로 신청된 신청서들 조회 (승인 대기 및 승인 완료 포함)
     */
    private function getApplicationsWithMyCode(PartnerUser $myPartner)
    {
        return PartnerApplication::whereIn('application_status', ['submitted', 'reviewing', 'interview', 'approved'])
            ->where(function ($query) use ($myPartner) {
                // referrer_partner_id 또는 referral_code 검색
                $query->where('referrer_partner_id', $myPartner->id)
                      ->orWhere('referral_code', $myPartner->partner_code)
                      ->orWhereJsonContains('referral_details->referrer_code', $myPartner->partner_code);
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($application) {
                return [
                    'id' => $application->id,
                    'applicant_name' => $application->personal_info['name'] ?? 'Unknown',
                    'email' => $application->personal_info['email'] ?? '',
                    'phone' => $application->personal_info['phone'] ?? '',
                    'application_status' => $application->application_status,
                    'target_tier' => $application->expected_tier_level ?? 'Bronze',
                    'submitted_at' => $application->submitted_at ?? $application->created_at,
                    'completeness_score' => $application->getCompletenessScore(),
                    'experience_years' => $application->experience_info['total_years'] ?? 0,
                    'skills_count' => count($application->skills_info['skills'] ?? []),
                    'documents_count' => count($application->documents ?? []),
                    'referral_source' => $application->referral_source ?? '',
                    'meeting_date' => $application->meeting_date ?? null,
                    'motivation' => $application->motivation ?? '',
                ];
            });
    }
}
