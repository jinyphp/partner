<?php

namespace Jiny\Partner\Http\Controllers\Home\PartnerRegist;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Jiny\Partner\Http\Controllers\PartnerController;

/**
 * 파트너 코드 입력 요청 컨트롤러
 *
 * /home/partner/regist 접근 시 파트너 코드 입력을 요청하고
 * 입력된 코드로 /home/partner/regist/{partnerCode}로 리다이렉션
 */
class PartnerCodeInputController extends PartnerController
{
    /**
     * 파트너 코드 입력 페이지 표시
     */
    public function __invoke(Request $request)
    {
        // Step1. JWT 인증여부 처리
        $user = $this->auth($request);
        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'JWT 인증이 필요합니다. 로그인해 주세요.')
                ->with('info', '파트너 서비스는 로그인 후 이용하실 수 있습니다.');
        }

        Log::info('PartnerCodeInputController: User accessing partner code input', [
            'user_id' => $user->id,
            'user_uuid' => $user->uuid,
            'user_email' => $user->email
        ]);

        // Step2. 파트너 등록 여부 확인 (UUID 기반)
        $isPartnerUser = PartnerUser::where('user_uuid', $user->uuid)
            ->exists();

        if ($isPartnerUser) {
            // 이미 파트너인 경우 파트너 대시보드로 이동
            return redirect()->route('home.partner.index')
                ->with('info', '이미 파트너로 등록되어 있습니다.');
        }

        // Step3. 진행 중인 신청서가 있는지 확인
        $existingApplication = \Jiny\Partner\Models\PartnerApplication::where('user_uuid', $user->uuid)
            ->whereIn('application_status', ['submitted', 'reviewing', 'interview', 'approved'])
            ->latest()
            ->first();

        if ($existingApplication) {
            return redirect()->route('home.partner.regist.status')
                ->with('info', '이미 진행 중인 신청이 있습니다.');
        }

        return view('jiny-partner::home.partner-regist.index', [
            'user' => $user,
            'pageTitle' => '파트너 가입 - 추천 코드 입력',
            'userInfo' => [
                'name' => $user->name ?? '',
                'email' => $user->email ?? '',
                'phone' => optional($user->profile)->phone ?? '',
                'uuid' => $user->uuid
            ]
        ]);
    }

    /**
     * 파트너 코드 검증 및 리다이렉션 (AJAX 처리)
     */
    public function validateAndRedirect(Request $request)
    {
        // Step1. JWT 인증여부 처리
        $user = $this->auth($request);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'JWT 인증이 필요합니다.',
                'redirect_url' => route('login')
            ], 401);
        }

        // 입력 검증
        $request->validate([
            'partner_code' => 'required|string|size:20|regex:/^[A-Z0-9]{20}$/'
        ], [
            'partner_code.required' => '파트너 코드를 입력해주세요.',
            'partner_code.size' => '파트너 코드는 20자리여야 합니다.',
            'partner_code.regex' => '파트너 코드는 영문 대문자와 숫자만 입력 가능합니다.'
        ]);

        $partnerCode = strtoupper(trim($request->input('partner_code')));

        Log::info('PartnerCodeInputController: Partner code validation request', [
            'partner_code' => $partnerCode,
            'user_uuid' => $user->uuid,
            'ip' => $request->ip()
        ]);

        // 파트너 코드 존재 여부 확인 (먼저 코드가 존재하는지 확인)
        $partnerWithCode = PartnerUser::where('partner_code', $partnerCode)->first();

        if (!$partnerWithCode) {
            Log::warning('PartnerCodeInputController: Partner code does not exist', [
                'partner_code' => $partnerCode,
                'user_uuid' => $user->uuid
            ]);

            return response()->json([
                'success' => false,
                'message' => '유효하지 않은 파트너 코드입니다. 파트너 코드를 다시 확인해주세요.',
                'errors' => [
                    'partner_code' => ['유효하지 않은 파트너 코드입니다.']
                ]
            ], 422);
        }

        // 파트너 상태 확인 (active 상태만 허용)
        if ($partnerWithCode->status !== 'active') {
            Log::warning('PartnerCodeInputController: Partner code exists but not active', [
                'partner_code' => $partnerCode,
                'partner_status' => $partnerWithCode->status,
                'user_uuid' => $user->uuid
            ]);

            $statusMessages = [
                'pending' => '해당 파트너 코드는 아직 승인 대기 중입니다. 관리자에게 문의해주세요.',
                'inactive' => '해당 파트너 코드는 현재 비활성 상태입니다.',
                'suspended' => '해당 파트너 코드는 일시 정지된 상태입니다.'
            ];

            $message = $statusMessages[$partnerWithCode->status] ?? '해당 파트너 코드는 현재 사용할 수 없는 상태입니다.';

            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => [
                    'partner_code' => [$message]
                ]
            ], 422);
        }

        $referrerPartner = $partnerWithCode;

        // 추천인이 하위 파트너 모집 가능한지 확인
        if (!$referrerPartner->can_recruit) {
            return response()->json([
                'success' => false,
                'message' => '해당 파트너는 현재 하위 파트너를 모집하지 않습니다.',
            ], 422);
        }

        // 추천인의 모집 한도 확인
        if ($referrerPartner->max_children && $referrerPartner->children_count >= $referrerPartner->max_children) {
            return response()->json([
                'success' => false,
                'message' => '해당 파트너의 하위 파트너 모집 한도가 초과되었습니다.',
            ], 422);
        }

        // 세션에 추천인 정보 저장 (ReferralController와 동일한 방식)
        Session::put('referrer_partner_id', $referrerPartner->id);
        Session::put('referrer_partner_code', $partnerCode);
        Session::put('referrer_info', [
            'id' => $referrerPartner->id,
            'name' => $referrerPartner->name,
            'email' => $referrerPartner->email,
            'tier' => $referrerPartner->partnerTier->tier_name ?? 'Unknown'
        ]);

        Log::info('PartnerCodeInputController: Valid partner code, redirecting to create page', [
            'partner_code' => $partnerCode,
            'referrer_id' => $referrerPartner->id,
            'referrer_name' => $referrerPartner->name,
            'user_uuid' => $user->uuid
        ]);

        // 유효한 파트너 코드인 경우 바로 create 페이지로 리다이렉션
        return response()->json([
            'success' => true,
            'message' => "'{$referrerPartner->name}' 파트너의 추천으로 가입을 진행합니다.",
            'redirect_url' => route('home.partner.regist.create'),
            'referrer_info' => [
                'name' => $referrerPartner->name,
                'tier' => $referrerPartner->partnerTier->tier_name ?? 'Unknown',
                'partner_code' => $partnerCode
            ]
        ]);
    }
}