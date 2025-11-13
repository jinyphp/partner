<?php

namespace Jiny\Partner\Http\Controllers\Home\PartnerRegist;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerTier;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Jiny\Partner\Http\Controllers\PartnerController;

/**
 * 파트너 코드를 통한 추천 가입 컨트롤러
 *
 * /home/partner/regist/{partner_code} 형태로 접근 시
 * 해당 파트너의 하위 파트너로 가입할 수 있도록 처리
 */
class ReferralController extends PartnerController
{
    /**
     * 파트너 코드를 통한 추천 가입 페이지
     *
     * @param Request $request
     * @param string $partnerCode 20자리 파트너 코드
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, string $partnerCode)
    {
        try {
            Log::info('Partner referral registration accessed', [
                'partner_code' => $partnerCode,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            // Step1. JWT 인증여부 처리
            $user = $this->auth($request);
            if (!$user) {
                // 파트너 코드를 세션에 저장하여 로그인 후 복원
                Session::put('referral_partner_code', $partnerCode);

                return redirect()->route('login')
                    ->with('error', 'JWT 인증이 필요합니다. 로그인해 주세요.')
                    ->with('info', '파트너 서비스는 로그인 후 이용하실 수 있습니다.');
            }

            // Step2. 파트너 코드로 추천인 찾기
            $referrer = PartnerUser::where('partner_code', $partnerCode)
                ->where('status', 'active')
                ->first();

            if (!$referrer) {
                Log::warning('Invalid partner code used for registration', [
                    'partner_code' => $partnerCode,
                    'user_uuid' => $user->uuid
                ]);

                return redirect()->route('home.partner.regist.index')
                    ->with('error', '유효하지 않은 파트너 코드입니다.')
                    ->with('info', '일반 파트너 가입을 진행해주세요.');
            }

            // Step3. 추천인이 하위 파트너 모집 가능한지 확인
            if (!$referrer->can_recruit) {
                return redirect()->route('home.partner.regist.index')
                    ->with('error', '해당 파트너는 현재 하위 파트너를 모집하지 않습니다.')
                    ->with('info', '일반 파트너 가입을 진행해주세요.');
            }

            // Step4. 추천인의 모집 한도 확인
            if ($referrer->max_children && $referrer->children_count >= $referrer->max_children) {
                return redirect()->route('home.partner.regist.index')
                    ->with('error', '해당 파트너의 하위 파트너 모집 한도가 초과되었습니다.')
                    ->with('info', '일반 파트너 가입을 진행해주세요.');
            }

            // Step5. 현재 사용자가 이미 파트너인지 확인
            $isPartnerUser = PartnerUser::where('user_uuid', $user->uuid)
                ->exists();

            if ($isPartnerUser) {
                return redirect()->route('home.partner.regist.index')
                    ->with('info', '이미 파트너로 등록되어 있습니다.');
            }

            // Step6. 진행 중인 신청서가 있는지 확인
            $existingApplication = PartnerApplication::where('user_uuid', $user->uuid)
                ->whereIn('application_status', ['submitted', 'reviewing', 'interview', 'approved'])
                ->latest()
                ->first();

            if ($existingApplication) {
                return redirect()->route('home.partner.regist.status', $existingApplication->id)
                    ->with('info', '이미 진행 중인 신청이 있습니다.');
            }

            // Step7. 파트너 등급 정보 가져오기
            $tiers = PartnerTier::where('is_active', true)
                ->orderBy('priority_level')
                ->get();

            // 기본 등급 (가장 낮은 우선순위) 설정
            $defaultTier = $tiers->last();

            // Step8. 추천인 정보를 세션에 저장
            Session::put('referrer_partner_id', $referrer->id);
            Session::put('referrer_partner_code', $partnerCode);
            Session::put('referrer_info', [
                'id' => $referrer->id,
                'name' => $referrer->name,
                'email' => $referrer->email,
                'tier' => $referrer->partnerTier->tier_name ?? 'Unknown'
            ]);

            Log::info('Partner referral setup completed', [
                'partner_code' => $partnerCode,
                'referrer_id' => $referrer->id,
                'referrer_name' => $referrer->name,
                'user_uuid' => $user->uuid,
                'user_email' => $user->email
            ]);

            // Step9. 추천 가입 폼으로 리디렉션
            return redirect()->route('home.partner.regist.create')
                ->with('success', "'{$referrer->name}' 파트너의 추천으로 가입을 진행합니다.")
                ->with('referrer_info', [
                    'name' => $referrer->name,
                    'tier' => $referrer->partnerTier->tier_name ?? 'Unknown',
                    'partner_code' => $partnerCode
                ]);

        } catch (\Exception $e) {
            Log::error('Partner referral registration failed', [
                'partner_code' => $partnerCode,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('home.partner.regist.index')
                ->with('error', '추천 가입 처리 중 오류가 발생했습니다.')
                ->with('info', '일반 파트너 가입을 진행해주세요.');
        }
    }
}