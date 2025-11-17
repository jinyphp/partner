<?php

namespace Jiny\Partner\Http\Controllers\Home\PartnerRegist;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerTier;
use Illuminate\Http\Request;
//use Illuminate\Support\Facades\Auth;
//use Jiny\Auth\Http\Controllers\Traits\JWTAuthTrait;

use Jiny\Partner\Http\Controllers\PartnerController;
//use Jiny\Auth\Http\Controllers\HomeController;
class EditController extends PartnerController
{

    /**
     * 파트너 신청서 수정 폼 표시
     */
    public function __invoke(Request $request, $id)
    {
        // Step1. JWT 인증여부 처리
        $user = $this->auth($request);
        if(!$user) {
            return redirect()->route('login')
                ->with('error', 'JWT 인증이 필요합니다. 로그인해 주세요.')
                ->with('info', '파트너 서비스는 로그인 후 이용하실 수 있습니다.');
        }

        // Step2. 신청서 조회 (본인의 신청서만, UUID 기반)
        $application = PartnerApplication::where('id', $id)
            ->where('user_uuid', $user->uuid)
            ->firstOrFail();

        // Step3. 수정 가능한 상태인지 확인
        if (!in_array($application->application_status, ['draft', 'submitted', 'rejected'])) {
            return redirect()->route('home.partner.regist.status')
                ->with('error', '현재 상태에서는 신청서를 수정할 수 없습니다.');
        }

        // Step4. 파트너 등급 정보 (참고용)
        $partnerTiers = PartnerTier::active()->orderBy('priority_level')->get();

        // 기술 스택 옵션
        $skillOptions = $this->getSkillOptions();

        // 지역 옵션
        $regionOptions = $this->getRegionOptions();

        return view('jiny-partner::home.partner-regist.edit', [
            'user' => $user,
            'currentUser' => $user, // 현재 로그인 사용자 정보 명시적 전달
            'application' => $application,
            'partnerTiers' => $partnerTiers,
            'skillOptions' => $skillOptions,
            'regionOptions' => $regionOptions,
            'pageTitle' => '파트너 신청서 수정',
            'userInfo' => [
                'name' => $user->name ?? '',
                'email' => $user->email ?? '',
                'phone' => $user->profile->phone ?? '',
                'uuid' => $user->uuid
            ]
        ]);
    }

    /**
     * 기술 스택 옵션 반환 (CreateController와 동일)
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
     * 지역 옵션 반환 (CreateController와 동일)
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
            // ... 기타 지역 (CreateController와 동일)
        ];
    }
}
