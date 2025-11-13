<?php

namespace Jiny\Partner\Http\Controllers\Home\Intro;

use Jiny\Partner\Http\Controllers\PartnerController;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerTier;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;

/**
 * 파트너 프로그램 소개 페이지 컨트롤러
 *
 * 기능:
 * - 파트너 프로그램 소개 및 가입 안내 페이지 제공
 * - 사용자 인증 상태 및 파트너 등록 여부 확인
 * - 신청 진행 상태별 맞춤형 UI 및 안내 메시지 제공
 * - 파트너 등급 정보 및 프로그램 혜택 소개
 *
 * 상속구조:
 * HomeController -> PartnerController -> IndexController
 *
 * 인증 요구사항:
 * 1. 사용자 JWT/세션 인증 필수 (HomeController.auth() 사용)
 * 2. 파트너 등록 여부 확인 (선택적, 상태 분기용)
 * 3. 신청서 존재 여부 및 진행 상태 확인
 *
 * 상태별 분기 처리:
 * - 미인증 사용자: 로그인 페이지로 리다이렉션
 * - 이미 파트너인 사용자: 대시보드로 리다이렉션
 * - 신청서 있는 사용자: 상태별 맞춤 안내 제공
 * - 신규 사용자: 신청서 작성 안내
 *
 * 라우팅: GET /home/partner/intro
 * 뷰파일: jiny-partner::home.intro.index
 *
 * @package Jiny\Partner\Http\Controllers\Home\Intro
 * @author JinyPHP Partner System
 * @since 1.0.0
 */
class IndexController extends PartnerController
{
    /**
     * 파트너 프로그램 소개 및 가입 안내 페이지 표시
     *
     * 처리 과정:
     * 1. 사용자 JWT/세션 인증 확인 및 로그인 강제
     * 2. 파트너 등록 여부 확인 (이미 등록된 경우 대시보드 리다이렉션)
     * 3. 기존 신청서 존재 여부 및 상태 확인
     * 4. 파트너 등급 정보 조회 (소개 페이지 표시용)
     * 5. 세션 데이터 병합 (리다이렉션으로 전달된 사용자 정보)
     * 6. 상태별 맞춤 안내 정보 구성 및 뷰 렌더링
     *
     * 사용자 상태별 분기:
     * - 미인증: 로그인 페이지로 리다이렉션 + 안내 메시지
     * - 이미 파트너: 대시보드로 리다이렉션 + 환영 메시지
     * - 신청서 있음: 진행 상태별 맞춤 UI 제공
     * - 신규 사용자: 신청서 작성 유도 및 프로그램 소개
     *
     * 신청서 상태 확인:
     * - UUID 기반으로 현재 사용자의 신청서 조회
     * - rejected 제외한 모든 상태의 최신 신청서 확인
     * - 상태별 적절한 안내 메시지 및 액션 버튼 제공
     *
     * 뷰 데이터 구성:
     * - user: 현재 로그인한 사용자 객체
     * - partnerTiers: 파트너 등급별 혜택 정보
     * - currentApplication: 진행 중인 신청서 정보
     * - statusInfo: 현재 상태별 맞춤 안내 데이터
     * - userInfo: 사용자 기본 정보 (폼 자동 입력용)
     *
     * 실패 시 리다이렉션:
     * - 미인증: /login (로그인 페이지)
     * - 이미 파트너: /home/partner (대시보드)
     *
     * @param Request $request HTTP 요청 객체
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     *
     * @throws \Exception 사용자 정보 조회 실패 시
     *
     * @since 1.0.0
     */
    public function __invoke(Request $request)
    {
        // ========================================
        // Step 1: 사용자 JWT/세션 인증 확인
        // ========================================
        // HomeController의 auth() 메소드를 통해 JWT 또는 세션 기반 인증 수행
        // 샤딩된 사용자 테이블에서 UUID 기반으로 사용자 정보 조회
        $user = $this->auth($request);
        if(!$user) {
            // 인증 실패 시: 로그인 페이지로 리다이렉션
            // - 파트너 프로그램 접근을 위한 로그인 필요성 안내
            // - 서비스 이용 안내 메시지 추가 제공
            return redirect()->route('login')
                ->with('error', 'JWT 인증이 필요합니다. 로그인해 주세요.')
                ->with('info', '파트너 서비스는 로그인 후 이용하실 수 있습니다.');
        }

        // ========================================
        // Step 2: 파트너 등록 여부 확인 (중복 방지)
        // ========================================
        // UUID 기반으로 파트너 테이블에서 등록 여부 확인
        // 이미 등록된 사용자는 소개 페이지 대신 대시보드로 안내
        $isPartnerUser = PartnerUser::where('user_uuid', $user->uuid)
            ->exists();

        if($isPartnerUser) {
            // 이미 파트너인 경우: 대시보드로 리다이렉션
            // - 중복 신청 방지 및 기존 활동 안내
            // - 성공 메시지로 긍정적 사용자 경험 제공
            return redirect()->route('home.partner.index')
                ->with('info', '이미 파트너로 등록되어 있습니다.')
                ->with('success', '파트너 대시보드에서 활동을 확인하세요.');
        }

        // ========================================
        // Step 3: 기존 신청서 존재 여부 및 상태 확인
        // ========================================
        // UUID 기반으로 현재 진행 중인 신청서 조회
        // - 'rejected' 상태 제외 (재신청 허용)
        // - 최신 신청서 우선 조회 (latest())
        $currentApplication = PartnerApplication::where('user_uuid', $user->uuid)
            ->whereNotIn('application_status', ['rejected'])
            ->latest()
            ->first();

        // ========================================
        // Step 4: 파트너 등급 정보 조회 (프로그램 소개용)
        // ========================================
        // 활성화된 파트너 등급 목록 조회 (priority_level 순)
        // 소개 페이지에서 등급별 혜택 및 조건 표시용
        $partnerTiers = PartnerTier::active()->orderBy('priority_level')->get();

        // ========================================
        // Step 5: 세션 데이터 병합 (리다이렉션 처리)
        // ========================================
        // 다른 페이지에서 리다이렉션으로 전달된 사용자 정보
        // 예: 대시보드에서 미등록 사용자 안내 시 사용자 데이터 전달
        $sessionUserInfo = session('userInfo', []);

        // ========================================
        // Step 6: 상태별 맞춤 안내 정보 구성
        // ========================================
        // 현재 사용자의 파트너 등록/신청 상태에 따른 맞춤형 UI 데이터 생성
        // - 신청서 진행 상태별 안내 메시지 및 액션 버튼 구성
        // - 사용자 경험 최적화를 위한 상황별 가이드라인 제공
        $statusInfo = $this->getStatusInfo($currentApplication, $isPartnerUser);

        // ========================================
        // Step 7: 뷰 데이터 구성 및 렌더링
        // ========================================
        return view('jiny-partner::home.intro.index', [
            // 사용자 정보
            'user' => $user,                           // 현재 로그인한 사용자 객체
            'currentUser' => $user,                    // 현재 사용자 명시적 전달 (템플릿 일관성)

            // 페이지 메타 정보
            'title' => '파트너 프로그램 소개',           // 페이지 제목
            'description' => 'JinyPHP 파트너 프로그램에 오신 것을 환영합니다.', // 페이지 설명
            'pageTitle' => '파트너 신청',               // 브레드크럼 제목

            // 파트너 프로그램 정보
            'partnerTiers' => $partnerTiers,           // 파트너 등급별 혜택 및 조건
            'currentApplication' => $currentApplication, // 진행 중인 신청서 정보
            'statusInfo' => $statusInfo,               // 상태별 맞춤 안내 데이터

            // 사용자 기본 정보 (폼 자동 입력 및 표시용)
            'userInfo' => array_merge([
                'name' => $user->name ?? '',           // 사용자 이름
                'email' => $user->email ?? '',         // 이메일 주소
                'phone' => $user->profile->phone ?? '', // 연락처 (프로필 관계에서 조회)
                'uuid' => $user->uuid                   // 고유 식별자 (샤딩 키)
            ], $sessionUserInfo)                       // 세션 데이터 병합 (리다이렉션 전달 정보)
        ]);
    }

    /**
     * 파트너 신청 상태별 맞춤형 안내 정보 생성
     *
     * 기능:
     * - 사용자의 현재 파트너 등록/신청 상태에 따른 맞춤 UI 구성
     * - 상태별 적절한 안내 메시지, 액션 버튼, 색상 테마 제공
     * - 사용자 경험 최적화를 위한 단계별 가이드라인 생성
     * - 프런트엔드에서 활용할 구조화된 상태 데이터 반환
     *
     * 지원 상태:
     * 1. partner: 이미 파트너로 등록됨 (대시보드 안내)
     * 2. new: 신규 사용자 (신청서 작성 유도)
     * 3. draft: 작성 중인 신청서 (이어서 작성 유도)
     * 4. submitted/pending: 제출 완료 (검토 대기 안내)
     * 5. reviewing/under_review: 검토 중 (진행 상황 안내)
     * 6. interview_scheduled: 면접 예정 (일정 안내)
     * 7. interview_completed: 면접 완료 (결과 대기 안내)
     * 8. approved: 승인됨 (파트너 시작 유도)
     * 9. rejected: 반려됨 (재신청 유도 및 사유 안내)
     * 10. cancelled: 취소됨 (새 신청 유도)
     * 11. unknown: 알 수 없는 상태 (확인 요청)
     *
     * 반환 데이터 구조:
     * - status: 현재 상태 식별자 (string)
     * - title: 상태 제목 (string)
     * - message: 주요 안내 메시지 (string)
     * - description: 상세 설명 (string)
     * - actions: 액션 버튼 배열 (array)
     *   - label: 버튼 텍스트
     *   - url: 이동할 URL
     *   - class: CSS 클래스 (부트스트랩 스타일)
     * - color: 테마 색상 (success, primary, warning, danger, info, secondary)
     *
     * UI 활용:
     * - 상태별 알림 배너 표시
     * - 적절한 액션 버튼 제공
     * - 진행 상황 시각화
     * - 사용자 안내 메시지 표시
     *
     * @param PartnerApplication|null $application 현재 신청서 객체 (없으면 null)
     * @param bool $isPartnerUser 파트너 등록 여부
     * @return array 상태별 안내 정보 배열
     *               - status: 상태 식별자
     *               - title: 상태 제목
     *               - message: 주요 메시지
     *               - description: 상세 설명
     *               - actions: 액션 버튼 배열
     *               - color: 테마 색상
     *
     * @since 1.0.0
     */
    private function getStatusInfo($application, $isPartnerUser)
    {
        // ========================================
        // 이미 파트너로 등록된 사용자 처리
        // ========================================
        // 일반적으로 이 페이지에 도달하지 않아야 하지만 안전장치로 제공
        if ($isPartnerUser) {
            return [
                'status' => 'partner',
                'title' => '파트너 회원',
                'message' => '현재 파트너로 활동 중입니다.',
                'description' => '파트너 대시보드에서 활동 내역을 확인하실 수 있습니다.',
                'actions' => [
                    [
                        'label' => '파트너 대시보드',
                        'url' => '/home/partner',
                        'class' => 'btn-primary'
                    ]
                ],
                'color' => 'success'  // 긍정적 상태를 나타내는 녹색 테마
            ];
        }

        // ========================================
        // 신청서가 없는 신규 사용자 처리
        // ========================================
        // 파트너 프로그램에 처음 관심을 보이는 사용자 대상
        if (!$application) {
            return [
                'status' => 'new',
                'title' => '파트너 신청',
                'message' => '파트너 신청을 시작해보세요.',
                'description' => '전문 기술을 가진 분들과 함께 다양한 프로젝트에 참여할 수 있습니다.',
                'actions' => [
                    [
                        'label' => '신청서 작성하기',
                        'url' => '/home/partner/regist/create',
                        'class' => 'btn-primary'
                    ]
                ],
                'color' => 'primary'  // 신청 유도를 위한 주요 테마
            ];
        }

        // ========================================
        // 신청서 상태별 맞춤 안내 처리
        // ========================================
        // 각 신청 단계별로 적절한 메시지와 다음 액션 제공
        switch ($application->application_status) {
            // ========================================
            // 'draft': 작성 중인 신청서
            // ========================================
            // 사용자가 신청서를 저장했지만 아직 제출하지 않은 상태
            case 'draft':
                return [
                    'status' => 'draft',
                    'title' => '작성 중인 신청서',
                    'message' => '신청서 작성을 완료해주세요.',
                    'description' => '저장된 신청서를 이어서 작성하거나 제출할 수 있습니다.',
                    'actions' => [
                        [
                            'label' => '이어서 작성하기',
                            'url' => "/home/partner/regist/{$application->id}/edit",
                            'class' => 'btn-primary'    // 주요 액션
                        ],
                        [
                            'label' => '새로 작성하기',
                            'url' => '/home/partner/regist/create',
                            'class' => 'btn-outline-secondary'  // 대안 액션
                        ]
                    ],
                    'color' => 'warning'  // 주의 필요 상태
                ];

            // ========================================
            // 'submitted'/'pending': 제출 완료, 검토 대기
            // ========================================
            // 사용자가 신청서를 제출했지만 아직 검토가 시작되지 않은 상태
            case 'submitted':
            case 'pending':
                return [
                    'status' => 'submitted',
                    'title' => '제출 완료',
                    'message' => '신청서가 제출되었습니다.',
                    'description' => '관리자 검토 후 연락드리겠습니다. 평균 검토 기간은 3-5일입니다.',
                    'actions' => [
                        [
                            'label' => '신청서 확인',
                            'url' => "/home/partner/regist/{$application->id}/status",
                            'class' => 'btn-outline-primary'
                        ]
                    ],
                    'color' => 'info'  // 정보 제공 상태
                ];

            // ========================================
            // 'reviewing'/'under_review': 검토 진행 중
            // ========================================
            // 관리자가 신청서를 적극적으로 검토하고 있는 상태
            case 'reviewing':
            case 'under_review':
                return [
                    'status' => 'reviewing',
                    'title' => '검토 중',
                    'message' => '신청서를 검토하고 있습니다.',
                    'description' => '추가 서류나 정보가 필요한 경우 별도 연락드리겠습니다.',
                    'actions' => [
                        [
                            'label' => '진행 상황 확인',
                            'url' => "/home/partner/regist/{$application->id}/status",
                            'class' => 'btn-outline-primary'
                        ]
                    ],
                    'color' => 'warning'  // 진행 중 상태
                ];

            // ========================================
            // 'interview_scheduled': 면접 예정
            // ========================================
            // 서류 검토 통과 후 면접이 예정된 상태
            case 'interview_scheduled':
                return [
                    'status' => 'interview',
                    'title' => '면접 예정',
                    'message' => '면접이 예정되어 있습니다.',
                    'description' => $application->interview_scheduled_at
                        ? '면접 일정: ' . $application->interview_scheduled_at->format('Y년 m월 d일 H:i')
                        : '곧 면접 일정을 안내드리겠습니다.',
                    'actions' => [
                        [
                            'label' => '면접 정보 확인',
                            'url' => "/home/partner/regist/{$application->id}/status",
                            'class' => 'btn-primary'
                        ]
                    ],
                    'color' => 'success'  // 긍정적 진전 상태
                ];

            // ========================================
            // 'interview_completed': 면접 완료
            // ========================================
            // 면접을 마치고 최종 결과를 기다리는 상태
            case 'interview_completed':
                return [
                    'status' => 'interview_completed',
                    'title' => '면접 완료',
                    'message' => '면접이 완료되었습니다.',
                    'description' => '결과를 기다려주세요. 곧 안내드리겠습니다.',
                    'actions' => [
                        [
                            'label' => '상태 확인',
                            'url' => "/home/partner/regist/{$application->id}/status",
                            'class' => 'btn-outline-primary'
                        ]
                    ],
                    'color' => 'info'  // 대기 상태
                ];

            // ========================================
            // 'approved': 승인 완료
            // ========================================
            // 파트너 신청이 최종 승인되어 파트너 활동 가능한 상태
            case 'approved':
                return [
                    'status' => 'approved',
                    'title' => '승인됨',
                    'message' => '파트너 신청이 승인되었습니다!',
                    'description' => '파트너 계정이 생성되었습니다. 이제 파트너로 활동하실 수 있습니다.',
                    'actions' => [
                        [
                            'label' => '파트너 시작하기',
                            'url' => '/home/partner',
                            'class' => 'btn-success'  // 성공 강조
                        ]
                    ],
                    'color' => 'success'  // 성공 상태
                ];

            // ========================================
            // 'rejected': 반려됨
            // ========================================
            // 신청이 반려되었지만 재신청 기회가 있는 상태
            case 'rejected':
                return [
                    'status' => 'rejected',
                    'title' => '반려됨',
                    'message' => '죄송합니다. 신청이 반려되었습니다.',
                    'description' => $application->rejection_reason ?: '자세한 사유는 개별 연락드렸습니다.',
                    'actions' => [
                        [
                            'label' => '재신청하기',
                            'url' => "/home/partner/regist/{$application->id}/reapply",
                            'class' => 'btn-primary'    // 재신청 유도
                        ],
                        [
                            'label' => '반려 상세 확인',
                            'url' => "/home/partner/regist/{$application->id}/status",
                            'class' => 'btn-outline-secondary'  // 상세 확인
                        ]
                    ],
                    'color' => 'danger'  // 부정적 상태이지만 재기회 제공
                ];

            // ========================================
            // 'cancelled': 신청 취소
            // ========================================
            // 사용자 또는 관리자에 의해 신청이 취소된 상태
            case 'cancelled':
                return [
                    'status' => 'cancelled',
                    'title' => '취소됨',
                    'message' => '신청이 취소되었습니다.',
                    'description' => '언제든지 다시 신청하실 수 있습니다.',
                    'actions' => [
                        [
                            'label' => '새로 신청하기',
                            'url' => '/home/partner/regist/create',
                            'class' => 'btn-primary'
                        ]
                    ],
                    'color' => 'secondary'  // 중립적 상태
                ];

            // ========================================
            // 기본값: 알 수 없는 상태
            // ========================================
            // 예상하지 못한 상태값에 대한 안전장치
            default:
                return [
                    'status' => 'unknown',
                    'title' => '상태 확인 필요',
                    'message' => '신청 상태를 확인해주세요.',
                    'description' => '문제가 지속되면 고객센터로 문의해주세요.',
                    'actions' => [
                        [
                            'label' => '상태 확인',
                            'url' => "/home/partner/regist/{$application->id}/status",
                            'class' => 'btn-outline-primary'
                        ]
                    ],
                    'color' => 'secondary'  // 중립적 상태
                ];
        }
    }
}