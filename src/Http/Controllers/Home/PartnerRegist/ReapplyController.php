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
class ReapplyController extends PartnerController
{

    /**
     * 재신청 페이지 표시
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

        // Step2. 기존 신청서 조회 (본인의 신청서만, UUID 기반)
        $rejectedApplication = PartnerApplication::where('id', $id)
            ->where('user_uuid', $user->uuid)
            ->where('application_status', 'rejected')
            ->firstOrFail();

        // Step3. 이미 재신청을 했는지 확인 (UUID 기반)
        $existingReapplication = PartnerApplication::where('user_uuid', $user->uuid)
            ->where('previous_application_id', $rejectedApplication->id)
            ->first();

        if ($existingReapplication) {
            return redirect()->route('home.partner.regist.status', $existingReapplication->id)
                ->with('info', '이미 재신청을 진행하셨습니다.');
        }

        // Step4. 진행 중인 다른 신청서가 있는지 확인 (UUID 기반)
        $activeApplication = PartnerApplication::where('user_uuid', $user->uuid)
            ->whereIn('application_status', ['submitted', 'reviewing', 'interview', 'approved'])
            ->exists();

        if ($activeApplication) {
            return redirect()->route('home.partner.regist.index')
                ->with('error', '현재 진행 중인 신청이 있어 재신청할 수 없습니다.');
        }

        // Step5. 파트너 등급 정보 (참고용)
        $partnerTiers = PartnerTier::active()->orderBy('priority_level')->get();

        // 기술 스택 옵션
        $skillOptions = $this->getSkillOptions();

        // 지역 옵션
        $regionOptions = $this->getRegionOptions();

        // 반려 사유 분석
        $rejectionAnalysis = $this->analyzeRejection($rejectedApplication);

        return view('jiny-partner::home.partner-regist.reapply', [
            'user' => $user,
            'currentUser' => $user, // 현재 로그인 사용자 정보 명시적 전달
            'rejectedApplication' => $rejectedApplication,
            'application' => $rejectedApplication, // 뷰에서 사용하는 변수명으로 추가
            'partnerTiers' => $partnerTiers,
            'skillOptions' => $skillOptions,
            'regionOptions' => $regionOptions,
            'rejectionAnalysis' => $rejectionAnalysis,
            'pageTitle' => '파트너 재신청',
            'userInfo' => [
                'name' => $user->name ?? '',
                'email' => $user->email ?? '',
                'phone' => $user->profile->phone ?? '',
                'uuid' => $user->uuid
            ]
        ]);
    }

    /**
     * 반려 사유 분석 및 개선 가이드 제공
     */
    private function analyzeRejection($application)
    {
        $rejectionReason = $application->rejection_reason ?: '';
        $analysis = [
            'reason' => $rejectionReason,
            'suggestions' => [],
            'focus_areas' => []
        ];

        // 반려 사유에 따른 개선 제안 (키워드 기반)
        $keywords = [
            '경력' => [
                'suggestion' => '경력 사항을 더 자세히 기술하고, 구체적인 프로젝트 경험을 추가해주세요.',
                'focus' => 'experience_info'
            ],
            '기술' => [
                'suggestion' => '기술 스택을 보완하거나 관련 자격증/교육 이수 내역을 추가해주세요.',
                'focus' => 'skills_info'
            ],
            '포트폴리오' => [
                'suggestion' => '더 다양하고 완성도 높은 포트폴리오를 준비해주세요.',
                'focus' => 'documents'
            ],
            '서류' => [
                'suggestion' => '제출 서류의 완성도를 높이고 누락된 정보를 보완해주세요.',
                'focus' => 'documents'
            ],
            '면접' => [
                'suggestion' => '면접에서 부족했던 부분을 보완하고 준비를 더 철저히 해주세요.',
                'focus' => 'interview_preparation'
            ]
        ];

        foreach ($keywords as $keyword => $info) {
            if (strpos($rejectionReason, $keyword) !== false) {
                $analysis['suggestions'][] = $info['suggestion'];
                $analysis['focus_areas'][] = $info['focus'];
            }
        }

        // 기본 개선 제안 (특정 키워드가 없는 경우)
        if (empty($analysis['suggestions'])) {
            $analysis['suggestions'] = [
                '신청서의 모든 항목을 더 자세히 작성해주세요.',
                '부족했던 부분을 보완하여 재신청해주세요.',
                '추가 교육이나 프로젝트 경험을 쌓아보세요.'
            ];
            $analysis['focus_areas'] = ['personal_info', 'experience_info', 'skills_info'];
        }

        // 개선 가이드
        $analysis['improvement_guide'] = [
            'personal_info' => '개인정보를 더 상세하고 정확하게 작성해주세요.',
            'experience_info' => '경력 사항을 구체적으로 기술하고 성과를 수치로 표현해주세요.',
            'skills_info' => '보유 기술을 더 자세히 나열하고 숙련도를 정확히 표기해주세요.',
            'documents' => '이력서와 포트폴리오의 품질을 높이고 최신 내용으로 업데이트해주세요.',
            'interview_preparation' => '면접 준비를 더 철저히 하고 예상 질문에 대한 답변을 준비해주세요.'
        ];

        return $analysis;
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
            // ... 기타 지역 (CreateController와 동일)
        ];
    }
}
