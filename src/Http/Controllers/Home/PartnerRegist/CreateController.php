<?php

namespace Jiny\Partner\Http\Controllers\Home\PartnerRegist;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerTier;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
//use Illuminate\Support\Facades\Auth;
//use Jiny\Auth\Http\Controllers\Traits\JWTAuthTrait;

//use Jiny\Auth\Http\Controllers\HomeController;
use Jiny\Partner\Http\Controllers\PartnerController;
class CreateController extends PartnerController
{

    /**
     * 파트너 신청서 작성 폼 표시 (파트너 코드 없이)
     */
    public function __invoke(Request $request)
    {
        // 파트너 코드 없이 접근하는 경우 코드 입력 페이지로 리다이렉트
        return redirect()->route('home.partner.regist.index')
            ->with('info', '추천 파트너 코드를 먼저 입력해주세요.');
    }

    /**
     * 파트너 코드와 함께 파트너 신청서 작성 폼 표시
     */
    public function createWithCode(Request $request, $partnerCode)
    {
        // Step0. 파트너 코드 검증
        $referrerPartner = PartnerUser::where('partner_code', $partnerCode)
            ->where('status', 'active')
            ->where('can_recruit', true)
            ->first();

        if (!$referrerPartner) {
            Log::warning('CreateController: Invalid partner code provided', [
                'partner_code' => $partnerCode,
                'ip' => $request->ip()
            ]);

            return redirect()->route('home.partner.regist.index')
                ->with('error', '유효하지 않은 파트너 코드입니다.')
                ->with('info', '올바른 파트너 코드를 입력해주세요.');
        }

        Log::info('CreateController: Valid partner code accessed', [
            'partner_code' => $partnerCode,
            'referrer_id' => $referrerPartner->id,
            'referrer_name' => $referrerPartner->name
        ]);

        // Step1. JWT 인증여부 처리
        $user = $this->auth($request);
        if(!$user) {
            return redirect()->route('login')
                ->with('error', 'JWT 인증이 필요합니다. 로그인해 주세요.')
                ->with('info', '파트너 서비스는 로그인 후 이용하실 수 있습니다.');
        }

        // Step2. 파트너 등록 여부 확인 (UUID 기반)
        $isPartnerUser = PartnerUser::where('user_uuid', $user->uuid)
            ->exists();

        if ($isPartnerUser) {
            return redirect()->route('home.partner.regist.index')
                ->with('info', '이미 파트너로 등록되어 있습니다.');
        }

        // Step3. 진행 중인 신청서가 있는지 확인 (UUID 기반)
        $existingApplication = PartnerApplication::where('user_uuid', $user->uuid)
            ->whereIn('application_status', ['submitted', 'reviewing', 'interview', 'approved'])
            ->latest()
            ->first();

        if ($existingApplication) {
            return redirect()->route('home.partner.regist.status', $existingApplication->id)
                ->with('info', '이미 진행 중인 신청이 있습니다.');
        }

        // Step4. 기존 draft 신청서 확인 (UUID 기반)
        $draftApplication = PartnerApplication::where('user_uuid', $user->uuid)
            ->where('application_status', 'draft')
            ->latest()
            ->first();

        // 파트너 등급 정보 (참고용)
        $partnerTiers = PartnerTier::active()->orderBy('priority_level')->get();

        // 기술 스택 옵션
        $skillOptions = $this->getSkillOptions();

        // 지역 옵션
        $regionOptions = $this->getRegionOptions();

        // 추천 파트너 목록 (활성 상태인 파트너들)
        $availablePartners = PartnerUser::where('status', 'active')
            ->where('can_recruit', true)
            ->select(['id', 'name', 'email'])
            ->orderBy('name')
            ->get();

        // 추천 파트너 정보 설정 및 세션에 저장 (StoreController와 동일한 방식)
        $referralPartner = $referrerPartner;
        $referralInfo = [
            'id' => $referrerPartner->id,
            'name' => $referrerPartner->name,
            'email' => $referrerPartner->email,
            'tier' => $referrerPartner->partnerTier->tier_name ?? 'Unknown'
        ];

        // StoreController에서 세션을 우선으로 하므로 세션에 추천인 정보 저장
        Session::put('referrer_partner_id', $referrerPartner->id);
        Session::put('referrer_partner_code', $partnerCode);
        Session::put('referrer_info', [
            'id' => $referrerPartner->id,
            'name' => $referrerPartner->name,
            'email' => $referrerPartner->email,
            'tier' => $referrerPartner->partnerTier->tier_name ?? 'Unknown'
        ]);

        Log::info('CreateController: Referral info set from URL parameter and stored in session', [
            'partner_code' => $partnerCode,
            'referrer_id' => $referrerPartner->id,
            'referrer_name' => $referrerPartner->name,
            'referrer_tier' => $referralInfo['tier']
        ]);

        // Step5. 사용자 정보 구성 및 디버깅
        $userInfo = [
            'name' => $user->name ?? '',
            'email' => $user->email ?? '',
            'phone' => optional($user->profile)->phone ?? '',
            'uuid' => $user->uuid
        ];

        \Log::info('CreateController: User info prepared', [
            'user_id' => $user->id,
            'user_info' => $userInfo,
            'has_profile' => $user->profile !== null,
            'profile_phone' => optional($user->profile)->phone,
            'partner_code' => $partnerCode
        ]);

        return view('jiny-partner::home.partner-regist.create', [
            'user' => $user,
            'currentUser' => $user, // 현재 로그인 사용자 정보 명시적 전달
            'draftApplication' => $draftApplication,
            'partnerTiers' => $partnerTiers,
            'skillOptions' => $skillOptions,
            'regionOptions' => $regionOptions,
            'availablePartners' => $availablePartners,
            'referralPartner' => $referralPartner,
            'referralInfo' => $referralInfo,
            'hasReferrer' => true, // 항상 true (파트너 코드가 있기 때문)
            'pageTitle' => "파트너 신청서 작성 ('{$referralInfo['name']}' 파트너의 추천)",
            'userInfo' => $userInfo,
            'partnerCode' => $partnerCode // 추가: 파트너 코드를 뷰에 전달
        ]);
    }

    /**
     * 기술 스택 옵션 반환
     */
    private function getSkillOptions()
    {
        return [
            'languages' => [
                'PHP', 'JavaScript', 'Python', 'Java', 'C#', 'C++',
                'Ruby', 'Go', 'Swift', 'Kotlin', 'TypeScript'
            ],
            'frameworks' => [
                'Laravel', 'Vue.js', 'React', 'Angular', 'Express.js',
                'Django', 'Spring Boot', 'ASP.NET', 'Ruby on Rails'
            ],
            'databases' => [
                'MySQL', 'PostgreSQL', 'MongoDB', 'Redis', 'SQLite',
                'Oracle', 'SQL Server', 'Elasticsearch'
            ],
            'tools' => [
                'Git', 'Docker', 'AWS', 'Azure', 'GCP', 'Jenkins',
                'Nginx', 'Apache', 'Linux', 'Windows Server'
            ],
            'skills' => [
                'Frontend Development', 'Backend Development', 'Full Stack Development',
                'Mobile App Development', 'DevOps', 'Database Administration',
                'System Administration', 'UI/UX Design', 'Project Management',
                'Quality Assurance', 'Data Analysis', 'Machine Learning'
            ]
        ];
    }

    /**
     * 지역 옵션 반환
     */
    private function getRegionOptions()
    {
        return [
            '서울' => [
                '강남구', '강동구', '강북구', '강서구', '관악구', '광진구', '구로구', '금천구',
                '노원구', '도봉구', '동대문구', '동작구', '마포구', '서대문구', '서초구', '성동구',
                '성북구', '송파구', '양천구', '영등포구', '용산구', '은평구', '종로구', '중구', '중랑구'
            ],
            '경기' => [
                '고양시', '과천시', '광명시', '광주시', '구리시', '군포시', '김포시', '남양주시',
                '동두천시', '부천시', '성남시', '수원시', '시흥시', '안산시', '안성시', '안양시',
                '양주시', '오산시', '용인시', '의왕시', '의정부시', '이천시', '파주시', '평택시',
                '포천시', '하남시', '화성시'
            ],
            '인천' => [
                '계양구', '미추홀구', '남동구', '동구', '부평구', '서구', '연수구', '중구', '강화군', '옹진군'
            ],
            '부산' => [
                '강서구', '금정구', '기장군', '남구', '동구', '동래구', '부산진구', '북구',
                '사상구', '사하구', '서구', '수영구', '연제구', '영도구', '중구', '해운대구'
            ],
            '대구' => [
                '남구', '달서구', '동구', '북구', '서구', '수성구', '중구', '달성군'
            ],
            '대전' => [
                '대덕구', '동구', '서구', '유성구', '중구'
            ],
            '광주' => [
                '광산구', '남구', '동구', '북구', '서구'
            ],
            '울산' => [
                '남구', '동구', '북구', '중구', '울주군'
            ],
            '세종' => ['세종시'],
            '강원' => [
                '강릉시', '고성군', '동해시', '삼척시', '속초시', '양구군', '양양군', '영월군',
                '원주시', '인제군', '정선군', '철원군', '춘천시', '태백시', '평창군', '홍천군', '화천군', '횡성군'
            ]
        ];
    }
}
