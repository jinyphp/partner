<?php

namespace Jiny\Partner\Http\Controllers\Home\PartnerRegist;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerTier;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;
//use Illuminate\Support\Facades\Auth;
//use Jiny\Auth\Http\Controllers\Traits\JWTAuthTrait;

use Jiny\Auth\Http\Controllers\HomeController;
class IndexController extends HomeController
{


    // use JWTAuthTrait;

    /**
     * 파트너 신청 메인 페이지
     * - 현재 신청 상태 확인
     * - 신규 신청 또는 기존 신청 관리
     */
    public function __invoke(Request $request)
    {
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

        // 이미 파트너인 경우 대시보드로 리다이렉트
        if($isPartnerUser) {
            return redirect()->route('home.partner.index')
                ->with('info', '이미 파트너로 등록되어 있습니다.')
                ->with('success', '파트너 대시보드에서 활동을 확인하세요.');
        }

        // Step3. 파트너 신청 여부 확인 (UUID 기반)
        $currentApplication = PartnerApplication::where('user_uuid', $user->uuid)
            ->whereNotIn('application_status', ['rejected'])
            ->latest()
            ->first();

        // 파트너 등급 정보 조회 (참고용)
        $partnerTiers = PartnerTier::active()->orderBy('priority_level')->get();

        // 신청서 존재 여부에 따른 분기 처리
        if ($currentApplication) {
            // 신청서가 있는 경우: 상태 페이지로 리다이렉트
            return redirect()->route('home.partner.regist.status', ['id' => $currentApplication->id])
                ->with('info', '기존 신청서가 있습니다. 상태를 확인해주세요.');
        } else {
            // 신청서가 없는 경우: 신청 양식 표시
            return view('jiny-partner::home.partner-regist.index', [
                'user' => $user,
                'partnerTiers' => $partnerTiers,
                'pageTitle' => '파트너 신청'
            ]);
        }
    }

    /**
     * 현재 상태에 따른 안내 정보 생성
     */
    private function getStatusInfo($application, $isPartnerUser)
    {
        // 이미 파트너인 경우
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
                'color' => 'success'
            ];
        }

        // 신청서가 없는 경우
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
                'color' => 'primary'
            ];
        }

        // 상태별 정보 반환
        switch ($application->application_status) {
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
                            'class' => 'btn-primary'
                        ],
                        [
                            'label' => '새로 작성하기',
                            'url' => '/home/partner/regist/create',
                            'class' => 'btn-outline-secondary'
                        ]
                    ],
                    'color' => 'warning'
                ];

            case 'submitted':
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
                    'color' => 'info'
                ];

            case 'reviewing':
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
                    'color' => 'warning'
                ];

            case 'interview':
                return [
                    'status' => 'interview',
                    'title' => '면접 예정',
                    'message' => '면접이 예정되어 있습니다.',
                    'description' => $application->interview_date
                        ? '면접 일정: ' . $application->interview_date->format('Y년 m월 d일 H:i')
                        : '곧 면접 일정을 안내드리겠습니다.',
                    'actions' => [
                        [
                            'label' => '면접 정보 확인',
                            'url' => "/home/partner/regist/{$application->id}/status",
                            'class' => 'btn-primary'
                        ]
                    ],
                    'color' => 'success'
                ];

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
                            'class' => 'btn-success'
                        ]
                    ],
                    'color' => 'success'
                ];

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
                            'class' => 'btn-primary'
                        ],
                        [
                            'label' => '반려 상세 확인',
                            'url' => "/home/partner/regist/{$application->id}/status",
                            'class' => 'btn-outline-secondary'
                        ]
                    ],
                    'color' => 'danger'
                ];

            case 'reapplied':
                return [
                    'status' => 'reapplied',
                    'title' => '재신청 제출',
                    'message' => '재신청이 제출되었습니다.',
                    'description' => '관리자가 재검토를 진행하겠습니다.',
                    'actions' => [
                        [
                            'label' => '재신청 상태 확인',
                            'url' => "/home/partner/regist/{$application->id}/status",
                            'class' => 'btn-outline-primary'
                        ]
                    ],
                    'color' => 'info'
                ];

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
                    'color' => 'secondary'
                ];
        }
    }
}
