<?php

namespace Jiny\Partner\Http\Controllers\Home\PartnerRegist;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerTier;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;

use Jiny\Partner\Http\Controllers\PartnerController;
//use Jiny\Auth\Http\Controllers\HomeController;
class StatusController extends PartnerController
{

    /**
     * 파트너 신청 상태 페이지
     * - 현재 신청 상태 확인
     * - 상태별 안내 메시지 및 액션 표시
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

        // Step2. 파트너 등록 여부 확인 (UUID 기반)
        $isPartnerUser = PartnerUser::where('user_uuid', $user->uuid)
            ->exists();

        // 이미 파트너인 경우 대시보드로 리다이렉트
        if($isPartnerUser) {
            return redirect()->route('home.partner.index')
                ->with('info', '이미 파트너로 등록되어 있습니다.')
                ->with('success', '파트너 대시보드에서 활동을 확인하세요.');
        }

        // Step3. 신청서 조회 및 권한 확인
        $currentApplication = PartnerApplication::where('id', $id)
            ->where('user_uuid', $user->uuid)
            ->first();

        if (!$currentApplication) {
            return redirect()->route('home.partner.regist.index')
                ->with('error', '신청서를 찾을 수 없습니다.')
                ->with('info', '새로운 신청을 시작해주세요.');
        }

        // 상태별 안내 메시지 및 액션 결정
        $statusInfo = $this->getStatusInfo($currentApplication, false);

        // 진행 로그 생성
        $progressLogs = $this->generateProgressLogs($currentApplication);

        // 추천 파트너 정보 가져오기
        $referrerInfo = null;
        if ($currentApplication->referrer_partner_id) {
            $referrerPartner = PartnerUser::with('partnerTier')->find($currentApplication->referrer_partner_id);
            if ($referrerPartner) {
                $referrerInfo = [
                    'id' => $referrerPartner->id,
                    'name' => $referrerPartner->name,
                    'email' => $referrerPartner->email,
                    'tier' => $referrerPartner->partnerTier->tier_name ?? 'Unknown',
                    'tier_color' => $referrerPartner->partnerTier->tier_color ?? '#6c757d',
                    'partner_code' => $referrerPartner->partner_code,
                    'referral_code_used' => $currentApplication->referral_code,
                    'referral_source' => $currentApplication->referral_source
                ];
            }
        }

        return view('jiny-partner::home.partner-regist.status', [
            'user' => $user,
            'currentApplication' => $currentApplication,
            'statusInfo' => $statusInfo,
            'progressLogs' => $progressLogs,
            'referrerInfo' => $referrerInfo,
            'hasReferrer' => $referrerInfo !== null,
            'pageTitle' => '파트너 신청 상태'
        ]);
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
                        ],
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
                            'url' => "/home/partner/regist/{$application->id}/edit",
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
                $actions = [];

                // 면접 상태일 때는 항상 면접 정보 확인 버튼 표시
                $actions[] = [
                    'label' => '면접 정보 확인',
                    'url' => '#',
                    'class' => 'btn-outline-primary',
                    'data-bs-toggle' => 'modal',
                    'data-bs-target' => '#interviewInfoModal'
                ];

                return [
                    'status' => 'interview',
                    'title' => '면접 예정',
                    'message' => '면접이 예정되어 있습니다.',
                    'description' => $application->interview_date
                        ? '면접 일정: ' . $application->interview_date->format('Y년 m월 d일 H:i')
                        : '곧 면접 일정을 안내드리겠습니다.',
                    'actions' => $actions,
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

    /**
     * 진행 로그 생성 (사용자용) - 실제 업무 흐름 시간 반영
     */
    private function generateProgressLogs($application)
    {
        $progressLogs = [];

        // 1. 신청서 작성 시작 (실제 created_at 시간)
        $progressLogs[] = [
            'status' => 'draft_created',
            'date' => $application->created_at,
            'title' => '신청서 작성 시작',
            'description' => '파트너 신청서 작성을 시작했습니다.',
            'user' => $application->personal_info['name'] ?? '지원자',
            'icon' => 'edit',
            'color' => 'secondary',
            'is_completed' => true
        ];

        // 2. 신청서 제출 완료 (실제 submitted_at 시간)
        if ($application->submitted_at) {
            $progressLogs[] = [
                'status' => 'submitted',
                'date' => $application->submitted_at,
                'title' => '신청서 제출 완료',
                'description' => '파트너 신청서가 성공적으로 제출되었습니다.',
                'user' => $application->personal_info['name'] ?? '지원자',
                'icon' => 'send',
                'color' => 'primary',
                'is_completed' => true,
                'details' => [
                    '진행률' => '+25%',
                    '상태' => '제출완료'
                ]
            ];
        }

        // 3. 검토 시작 (제출 이후 몇 시간 후 - 업무 흐름상 자연스러운 시간)
        if (in_array($application->application_status, ['reviewing', 'interview', 'approved', 'rejected', 'reapplied'])) {
            $reviewStartTime = $application->submitted_at
                ? $application->submitted_at->copy()->addHours(2) // 제출 2시간 후 검토 시작
                : $application->created_at->copy()->addDay(); // submitted_at이 없으면 다음날

            $progressLogs[] = [
                'status' => 'review_started',
                'date' => $reviewStartTime,
                'title' => '검토 시작',
                'description' => '관리자가 신청서 검토를 시작했습니다.',
                'user' => '관리자',
                'icon' => 'eye',
                'color' => 'warning',
                'is_completed' => true,
                'details' => [
                    '검토항목' => '개인정보, 경력, 기술스택, 근무조건',
                    '우선도' => '일반'
                ]
            ];
        }

        // 4. 면접 일정 설정 (면접 날짜 1-2일 전에 설정되었을 것으로 가정)
        if ($application->interview_date) {
            $interviewScheduledTime = $application->interview_date->copy()->subDays(2); // 면접 2일 전 설정

            $progressLogs[] = [
                'status' => 'interview_scheduled',
                'date' => $interviewScheduledTime,
                'title' => '면접 일정 설정',
                'description' => '면접 일정이 설정되었습니다.',
                'user' => '시스템관리자',
                'icon' => 'calendar',
                'color' => 'info',
                'is_completed' => true,
                'details' => [
                    '면접일시' => $application->interview_date->format('Y-m-d H:i'),
                    '면접장소' => $application->interview_feedback['location'] ?? '온라인 면접'
                ]
            ];
        }

        // 5. 면접 완료 (실제 면접 날짜 + 1시간 후)
        if ($application->interview_date && in_array($application->application_status, ['approved', 'rejected'])) {
            $interviewCompletedTime = $application->interview_date->copy()->addHour(); // 면접 1시간 후 완료

            $progressLogs[] = [
                'status' => 'interview_completed',
                'date' => $interviewCompletedTime,
                'title' => '면접 완료',
                'description' => '면접이 완료되었습니다.',
                'user' => $application->personal_info['name'] ?? '지원자',
                'icon' => 'check-circle',
                'color' => 'success',
                'is_completed' => true,
                'details' => [
                    '소요시간' => '약 1시간',
                    '면접형태' => '화상면접'
                ]
            ];
        }

        // 6. 최종 승인 (면접 후 2-3일 후 또는 실제 승인일)
        if ($application->application_status === 'approved') {
            $approvalTime = $application->approval_date
                ?? ($application->interview_date
                    ? $application->interview_date->copy()->addDays(3) // 면접 3일 후
                    : $application->updated_at); // 또는 updated_at

            $progressLogs[] = [
                'status' => 'approved',
                'date' => $approvalTime,
                'title' => '최종 승인',
                'description' => '파트너 신청이 최종 승인되었습니다! 🎉',
                'user' => '승인관리자',
                'icon' => 'check-circle',
                'color' => 'success',
                'is_completed' => true,
                'details' => [
                    '승인일' => $approvalTime->format('Y-m-d'),
                    '파트너등급' => 'BRONZE'
                ]
            ];
        }

        // 7. 신청 반려 (면접 후 2-3일 후 또는 검토 후)
        if ($application->application_status === 'rejected') {
            $rejectionTime = $application->interview_date
                ? $application->interview_date->copy()->addDays(2) // 면접 2일 후 반려
                : $application->updated_at; // 또는 updated_at

            $progressLogs[] = [
                'status' => 'rejected',
                'date' => $rejectionTime,
                'title' => '신청 반려',
                'description' => $application->rejection_reason ?: '신청이 반려되었습니다.',
                'user' => '검토관리자',
                'icon' => 'x-circle',
                'color' => 'danger',
                'is_completed' => true,
                'details' => [
                    '반려일' => $rejectionTime->format('Y-m-d'),
                    '재신청' => '가능'
                ]
            ];
        }

        // 8. 재신청 (반려 후 재신청한 시점)
        if ($application->application_status === 'reapplied') {
            $progressLogs[] = [
                'status' => 'reapplied',
                'date' => $application->updated_at, // 실제 재신청 시간
                'title' => '재신청',
                'description' => '신청서가 재제출되었습니다.',
                'user' => $application->personal_info['name'] ?? '지원자',
                'icon' => 'arrow-repeat',
                'color' => 'info',
                'is_completed' => true,
                'details' => [
                    '재신청일' => $application->updated_at->format('Y-m-d'),
                    '진행률' => '+25%'
                ]
            ];
        }

        // 날짜순으로 정렬 (자연스러운 시간 흐름)
        usort($progressLogs, function ($a, $b) {
            return $a['date']->timestamp - $b['date']->timestamp;
        });

        return $progressLogs;
    }
}
