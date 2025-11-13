@extends('jiny-partner::layouts.admin.sidebar')

@section('title', $pageTitle)

@section('content')
    <div class="container-fluid">

        <!-- 헤더 -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1">{{ $pageTitle }}</h2>
                        <p class="text-muted mb-0">
                            지원자: {{ $application->personal_info['name'] ?? ($application->user->name ?? 'Unknown') }}
                        </p>
                    </div>
                    <div>
                        @if ($navigation['prev'])
                            <a href="{{ route('admin.partner.approval.show', $navigation['prev']->id) }}"
                                class="btn btn-outline-secondary me-2" title="이전 지원서">
                                <i class="fe fe-chevron-left"></i>
                            </a>
                        @endif
                        @if ($navigation['next'])
                            <a href="{{ route('admin.partner.approval.show', $navigation['next']->id) }}"
                                class="btn btn-outline-secondary me-2" title="다음 지원서">
                                <i class="fe fe-chevron-right"></i>
                            </a>
                        @endif
                        <a href="{{ route('admin.partner.approval.index') }}" class="btn btn-secondary">
                            <i class="fe fe-arrow-left me-2"></i>목록으로
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 진행 단계 표시 -->
        {{-- <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">신청 진행 단계</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <!-- 진행 단계 스텝 -->
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    @php
                                        $steps = [
                                            'submitted' => ['label' => '제출', 'icon' => 'send', 'color' => 'primary'],
                                            'reviewing' => ['label' => '검토', 'icon' => 'eye', 'color' => 'warning'],
                                            'interview' => ['label' => '면접', 'icon' => 'video', 'color' => 'info'],
                                            'approved' => ['label' => '승인', 'icon' => 'check', 'color' => 'success'],
                                            'rejected' => ['label' => '반려', 'icon' => 'x', 'color' => 'danger']
                                        ];

                                        $currentStepIndex = array_search($application->application_status, array_keys($steps));
                                        if ($application->application_status === 'rejected') {
                                            $currentStepIndex = 3; // 면접 단계까지 표시
                                        }
                                    @endphp

                                    @foreach ($steps as $stepKey => $step)
                                        @if ($stepKey === 'rejected' && $application->application_status !== 'rejected')
                                            @continue
                                        @endif
                                        @if ($stepKey === 'approved' && $application->application_status === 'rejected')
                                            @continue
                                        @endif

                                        @php
                                            $stepIndex = array_search($stepKey, array_keys($steps));
                                            $isCompleted = $stepIndex <= $currentStepIndex;
                                            $isCurrent = $stepKey === $application->application_status;
                                        @endphp

                                        <div class="text-center position-relative">
                                            <!-- Step Circle -->
                                            <div class="step-circle
                                                @if ($isCurrent) bg-{{ $step['color'] }} text-white
                                                @elseif($isCompleted) bg-success text-white
                                                @else bg-light text-muted @endif
                                                rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2"
                                                style="width: 50px; height: 50px;">
                                                <i class="fe fe-{{ $step['icon'] }} fs-5"></i>
                                            </div>

                                            <!-- Step Label -->
                                            <div class="small fw-bold
                                                @if ($isCurrent) text-{{ $step['color'] }}
                                                @elseif($isCompleted) text-success
                                                @else text-muted @endif">
                                                {{ $step['label'] }}
                                            </div>

                                            @if ($isCurrent)
                                                <div class="badge bg-{{ $step['color'] }} mt-1">진행중</div>
                                            @elseif($isCompleted && $stepKey !== 'approved' && $stepKey !== 'rejected')
                                                <div class="badge bg-success mt-1">완료</div>
                                            @endif

                                            <!-- Connection Line -->
                                            @if (!$loop->last && $stepKey !== 'interview')
                                                <div class="position-absolute top-50 start-100 translate-middle-y"
                                                    style="width: calc(100vw / {{ count($steps) - 1 }} - 50px); height: 2px; margin-left: 25px;">
                                                    <div class="h-100 @if ($isCompleted && $stepIndex < $currentStepIndex) bg-success @else bg-light @endif"></div>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> --}}

        <!-- 지원서 상태 및 평가 -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2">
                                <h6 class="text-muted mb-1">현재 상태</h6>
                                @if ($application->application_status === 'submitted')
                                    <span class="badge bg-primary fs-6">제출됨</span>
                                @elseif($application->application_status === 'reviewing')
                                    <span class="badge bg-warning fs-6">검토 중</span>
                                @elseif($application->application_status === 'interview')
                                    <span class="badge bg-info fs-6">면접 예정</span>
                                @elseif($application->application_status === 'approved')
                                    <span class="badge bg-success fs-6">승인됨</span>
                                @elseif($application->application_status === 'rejected')
                                    <span class="badge bg-danger fs-6">반려됨</span>
                                @elseif($application->application_status === 'reapplied')
                                    <span class="badge bg-secondary fs-6">재신청</span>
                                @endif
                            </div>
                            <div class="col-md-2">
                                <h6 class="text-muted mb-1">완성도</h6>
                                <div class="d-flex align-items-center">
                                    <div class="progress me-2" style="width: 60px; height: 8px;">
                                        <div class="progress-bar
                                        @if ($completenessScore >= 80) bg-success
                                        @elseif($completenessScore >= 60) bg-warning
                                        @else bg-danger @endif"
                                            style="width: {{ $completenessScore }}%"></div>
                                    </div>
                                    <span class="fw-bold">{{ $completenessScore }}%</span>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <h6 class="text-muted mb-1">전체 평가</h6>
                                <div class="d-flex align-items-center">
                                    <span class="fw-bold fs-5 me-2">{{ $evaluation['overall_score'] }}</span>
                                    <div class="text-muted">/ 100점</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <h6 class="text-muted mb-1">희망 시급</h6>
                                <span
                                    class="fw-bold fs-5">{{ number_format($application->expected_hourly_rate ?? 0) }}원</span>
                            </div>
                            <div class="col-md-3">
                                <h6 class="text-muted mb-1">지원일</h6>
                                <span>{{ $application->created_at->format('Y년 m월 d일') }}</span>
                                @if ($application->created_at < now()->subDays(7))
                                    <span class="badge bg-danger ms-2">긴급</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 추천 파트너 정보 (추천인이 있는 경우만 표시) -->
        @if ($application->referrerPartner)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header ">
                    <h6 class="mb-0 text-dark">
                        <i class="fe fe-users me-2"></i>추천 파트너 정보
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong>파트너 코드:</strong>
                            <span class="badge bg-primary ms-2">{{ $application->referrerPartner->partner_code }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>추천인 이름:</strong> {{ $application->referrerPartner->name }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>추천인 이메일:</strong> {{ $application->referrerPartner->email ?? 'N/A' }}
                        </div>
                        @if ($application->referrerPartner->partnerTier)
                            <div class="col-md-6 mb-3">
                                <strong>추천인 등급:</strong>
                                <span
                                    class="badge bg-success">{{ $application->referrerPartner->partnerTier->tier_name }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif






        <div class="row">
            <div class="col-lg-4">
                <!-- 진행 로그 -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fe fe-clock text-primary me-2"></i>진행 로그
                        </h6>
                    </div>
                    <div class="card-body">
                        @php
                            $progressLogs = [];

                            // 1. 신청서 초기 작성 (created_at)
                            $progressLogs[] = [
                                'status' => 'draft_created',
                                'date' => $application->created_at,
                                'title' => '신청서 작성 시작',
                                'description' => '파트너 신청서 작성을 시작했습니다.',
                                'user' => $application->personal_info['name'] ?? '지원자',
                                'icon' => 'edit',
                                'color' => 'secondary',
                                'details' => [
                                    'ip_address' => request()->ip(),
                                    'user_agent' => 'Web Application',
                                ],
                            ];

                            // 2. 신청서 제출 완료 (submitted_at 또는 status가 submitted인 시점)
                            if ($application->submitted_at) {
                                $progressLogs[] = [
                                    'status' => 'submitted',
                                    'date' => $application->submitted_at,
                                    'title' => '신청서 제출 완료',
                                    'description' => '파트너 신청서가 정식으로 제출되었습니다.',
                                    'user' => $application->personal_info['name'] ?? '지원자',
                                    'icon' => 'send',
                                    'color' => 'primary',
                                    'details' => [
                                        '완성도' => ($completenessScore ?? 0) . '%',
                                        '제출방식' => 'AJAX 온라인 제출',
                                    ],
                                ];
                            } elseif ($application->application_status !== 'draft') {
                                // submitted_at이 없지만 상태가 draft가 아닌 경우
                                $progressLogs[] = [
                                    'status' => 'submitted',
                                    'date' => $application->created_at->addMinutes(5), // 추정 제출 시간
                                    'title' => '신청서 제출',
                                    'description' => '파트너 신청서가 제출되었습니다.',
                                    'user' => $application->personal_info['name'] ?? '지원자',
                                    'icon' => 'send',
                                    'color' => 'primary',
                                ];
                            }

                            // 3. 관리자 검토 시작 (상태가 reviewing으로 변경된 시점 추정)
                            if (
                                in_array($application->application_status, [
                                    'reviewing',
                                    'interview',
                                    'approved',
                                    'rejected',
                                ])
                            ) {
                                $reviewStartDate = $application->submitted_at
                                    ? $application->submitted_at->addHours(1)
                                    : $application->created_at->addHours(2);

                                $progressLogs[] = [
                                    'status' => 'review_started',
                                    'date' => $reviewStartDate,
                                    'title' => '검토 시작',
                                    'description' => '관리자가 신청서 검토를 시작했습니다.',
                                    'user' => '관리자',
                                    'icon' => 'eye',
                                    'color' => 'warning',
                                    'details' => [
                                        '검토항목' => '개인정보, 경력, 기술스택, 근무조건',
                                        '우선도' => $application->created_at->diffInDays(now()) >= 7 ? '긴급' : '일반',
                                    ],
                                ];
                            }

                            // 4. 면접 일정 설정
                            if ($application->interview_date) {
                                $interviewSetDate =
                                    $application->interview_feedback &&
                                    isset($application->interview_feedback['scheduled_at'])
                                        ? \Carbon\Carbon::parse($application->interview_feedback['scheduled_at'])
                                        : $application->updated_at;

                                $progressLogs[] = [
                                    'status' => 'interview_scheduled',
                                    'date' => $interviewSetDate,
                                    'title' => '면접 일정 설정',
                                    'description' => '면접 일정이 설정되었습니다.',
                                    'user' => '관리자',
                                    'icon' => 'calendar',
                                    'color' => 'info',
                                    'details' => [
                                        '면접일시' => $application->interview_date->format('Y-m-d H:i'),
                                        '면접장소' => $application->interview_feedback['location'] ?? '미지정',
                                        '면접형태' =>
                                            strpos($application->interview_feedback['location'] ?? '', '온라인') !==
                                            false
                                                ? '화상면접'
                                                : '대면면접',
                                    ],
                                ];
                            }

                            // 5. 면접 완료 (면접일이 지난 경우)
                            if ($application->interview_date && $application->interview_date->isPast()) {
                                $progressLogs[] = [
                                    'status' => 'interview_completed',
                                    'date' => $application->interview_date->addHours(1), // 면접 후 1시간으로 추정
                                    'title' => '면접 완료',
                                    'description' => '면접이 완료되었습니다.',
                                    'user' => '면접관',
                                    'icon' => 'video',
                                    'color' => 'info',
                                    'details' => [
                                        '면접결과' => '검토중',
                                        '참석여부' => '참석',
                                    ],
                                ];
                            }

                            // 6. 최종 승인
                            if ($application->approval_date) {
                                $progressLogs[] = [
                                    'status' => 'approved',
                                    'date' => $application->approval_date,
                                    'title' => '최종 승인',
                                    'description' => '파트너 신청이 최종 승인되었습니다.',
                                    'user' => '승인관리자',
                                    'icon' => 'check-circle',
                                    'color' => 'success',
                                    'details' => [
                                        '처리시간' =>
                                            $application->created_at->diffInDays($application->approval_date) . '일',
                                        '파트너등급' => '신규파트너',
                                        '알림발송' => '승인 알림 발송됨',
                                    ],
                                ];
                            }

                            // 7. 반려/거부
                            if ($application->rejection_date) {
                                $progressLogs[] = [
                                    'status' => 'rejected',
                                    'date' => $application->rejection_date,
                                    'title' => '신청 반려',
                                    'description' => '파트너 신청이 반려되었습니다.',
                                    'user' => '검토관리자',
                                    'icon' => 'x-circle',
                                    'color' => 'danger',
                                    'details' => [
                                        '처리시간' =>
                                            $application->created_at->diffInDays($application->rejection_date) . '일',
                                        '반려유형' => '서류 미비',
                                        '재신청가능' => '가능',
                                    ],
                                ];
                            }

                            // 8. 재신청 (이전 신청서가 있는 경우)
                            if ($application->previous_application_id) {
                                $progressLogs[] = [
                                    'status' => 'reapplied',
                                    'date' => $application->created_at,
                                    'title' => '재신청',
                                    'description' => '이전 신청을 개선하여 재신청했습니다.',
                                    'user' => $application->personal_info['name'] ?? '지원자',
                                    'icon' => 'refresh-cw',
                                    'color' => 'warning',
                                    'details' => [
                                        '이전신청ID' => $application->previous_application_id,
                                        '개선사항' => $application->reapplication_reason ?? '정보 업데이트',
                                    ],
                                ];
                            }

                            // 9. 시스템 로그 (admin_notes에서 추출)
                            if ($application->admin_notes) {
                                $noteLines = explode("\n", $application->admin_notes);
                                foreach ($noteLines as $line) {
                                    if (strpos($line, '[') !== false && strpos($line, ']') !== false) {
                                        // [액션] 날짜 - 내용 형태의 로그 파싱
                                        preg_match(
                                            '/\[(.*?)\]\s*(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2})?\s*-\s*(.*)/',
                                            $line,
                                            $matches,
                                        );
                                        if (count($matches) >= 4) {
                                            $action = trim($matches[1]);
                                            $dateStr = trim($matches[2]);
                                            $description = trim($matches[3]);

                                            if ($dateStr) {
                                                try {
                                                    $logDate = \Carbon\Carbon::parse($dateStr);
                                                    $progressLogs[] = [
                                                        'status' => 'admin_action',
                                                        'date' => $logDate,
                                                        'title' => $action,
                                                        'description' => $description,
                                                        'user' => '시스템관리자',
                                                        'icon' => 'settings',
                                                        'color' => 'secondary',
                                                    ];
                                                } catch (\Exception $e) {
                                                    // 날짜 파싱 실패 시 무시
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            // 10. 추가 시스템 이벤트들
                            if ($application->referrer_partner_id) {
                                $progressLogs[] = [
                                    'status' => 'referral_confirmed',
                                    'date' => $application->created_at->addMinutes(10),
                                    'title' => '추천 확인',
                                    'description' => '추천 파트너 정보가 확인되었습니다.',
                                    'user' => '시스템',
                                    'icon' => 'users',
                                    'color' => 'info',
                                    'details' => [
                                        '추천경로' => $application->referral_source ?? 'direct',
                                        '추천코드' => $application->referral_code ?? '없음',
                                    ],
                                ];
                            }

                            // 날짜순 정렬
                            usort($progressLogs, function ($a, $b) {
                                return $a['date']->timestamp - $b['date']->timestamp;
                            });
                        @endphp

                        @if (count($progressLogs) > 0)
                            <div class="timeline">
                                @foreach ($progressLogs as $index => $log)
                                    <div class="timeline-item d-flex align-items-start mb-4">
                                        <div class="timeline-marker me-3">
                                            <div class="bg-{{ $log['color'] }} rounded-circle d-flex align-items-center justify-content-center text-white"
                                                style="width: 40px; height: 40px;">
                                                <i class="fe fe-{{ $log['icon'] }}"></i>
                                            </div>
                                        </div>
                                        <div class="timeline-content flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1 fw-bold text-{{ $log['color'] }}">
                                                        {{ $log['title'] }}
                                                        @if ($log['status'] === 'interview_scheduled' && $application->interview_date)
                                                            <small class="text-muted ms-2">
                                                                @if ($application->interview_date->isFuture())
                                                                    <span class="badge bg-info">예정</span>
                                                                @elseif($application->interview_date->isToday())
                                                                    <span class="badge bg-warning">오늘</span>
                                                                @else
                                                                    <span class="badge bg-secondary">완료</span>
                                                                @endif
                                                            </small>
                                                        @endif
                                                    </h6>
                                                    <p class="mb-2">{{ $log['description'] }}</p>

                                                    <!-- 상세 정보 표시 -->
                                                    @if (isset($log['details']) && is_array($log['details']) && count($log['details']) > 0)
                                                        <div class="mt-2 p-3 border-start border-{{ $log['color'] }} border-2"
                                                            style="background-color:
                                                            @if ($log['color'] === 'primary') #e7f1ff
                                                            @elseif($log['color'] === 'secondary') #f8f9fa
                                                            @elseif($log['color'] === 'success') #e8f5e8
                                                            @elseif($log['color'] === 'danger') #fdeaea
                                                            @elseif($log['color'] === 'warning') #fff8e1
                                                            @elseif($log['color'] === 'info') #e1f5fe
                                                            @elseif($log['color'] === 'dark') #f5f5f5
                                                            @else #f8f9fa @endif; border-radius: 0.25rem;">
                                                            <div class="row g-2">
                                                                @foreach ($log['details'] as $key => $value)
                                                                    <div class="col-md-6">
                                                                        <small
                                                                            class="text-muted fw-semibold">{{ $key }}:</small>
                                                                        <small
                                                                            class="d-block text-dark">{{ $value }}</small>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif

                                                    <div class="mt-2">
                                                        <small class="text-muted">
                                                            <i class="fe fe-user me-1"></i>{{ $log['user'] }} •
                                                            <i
                                                                class="fe fe-clock me-1"></i>{{ $log['date']->format('Y-m-d H:i') }}
                                                            <span
                                                                class="ms-2">({{ $log['date']->diffForHumans() }})</span>
                                                            @if ($log['date']->isToday())
                                                                <span class="badge bg-primary ms-2">TODAY</span>
                                                            @elseif($log['date']->isYesterday())
                                                                <span class="badge bg-secondary ms-2">YESTERDAY</span>
                                                            @endif
                                                        </small>
                                                    </div>
                                                </div>
                                                <div class="text-end ms-3">
                                                    <span class="badge bg-{{ $log['color'] }} mb-2">
                                                        {{ [
                                                            'draft_created' => '작성시작',
                                                            'submitted' => '제출완료',
                                                            'review_started' => '검토시작',
                                                            'interview_scheduled' => '면접설정',
                                                            'interview_completed' => '면접완료',
                                                            'approved' => '승인완료',
                                                            'rejected' => '반려',
                                                            'reapplied' => '재신청',
                                                            'admin_action' => '관리자액션',
                                                            'referral_confirmed' => '추천확인',
                                                        ][$log['status']] ?? $log['status'] }}
                                                    </span>

                                                    @if (in_array($log['status'], ['submitted', 'approved', 'rejected']))
                                                        <div class="text-muted small">
                                                            @switch($log['status'])
                                                                @case('submitted')
                                                                    <i class="fe fe-trending-up text-primary"></i> 진행률 +25%
                                                                @break

                                                                @case('approved')
                                                                    <i class="fe fe-check-circle text-success"></i> 완료
                                                                @break

                                                                @case('rejected')
                                                                    <i class="fe fe-x-circle text-danger"></i> 종료
                                                                @break
                                                            @endswitch
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- 특별 정보 섹션들 -->
                                            @if ($log['status'] === 'rejected' && $application->rejection_reason)
                                                <div
                                                    class="mt-3 p-3 bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded">
                                                    <h6 class="text-danger mb-2">
                                                        <i class="fe fe-alert-triangle me-1"></i>반려 상세사유
                                                    </h6>
                                                    <div class="text-dark">{{ $application->rejection_reason }}</div>
                                                    @if ($application->reapplication_reason)
                                                        <div class="mt-2 p-2 bg-warning bg-opacity-10 rounded">
                                                            <small class="text-warning fw-semibold">개선방안:</small>
                                                            <small
                                                                class="d-block">{{ $application->reapplication_reason }}</small>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif

                                            @if ($log['status'] === 'interview_scheduled' && $application->interview_notes)
                                                <div
                                                    class="mt-3 p-3 bg-info bg-opacity-10 border border-info border-opacity-25 rounded">
                                                    <h6 class="text-info mb-2">
                                                        <i class="fe fe-message-square me-1"></i>면접 안내사항
                                                    </h6>
                                                    <div class="text-dark">{{ $application->interview_notes }}</div>
                                                </div>
                                            @endif

                                            @if ($log['status'] === 'reapplied' && $application->reapplication_reason)
                                                <div
                                                    class="mt-3 p-3 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded">
                                                    <h6 class="text-warning mb-2">
                                                        <i class="fe fe-refresh-cw me-1"></i>재신청 개선사항
                                                    </h6>
                                                    <div class="text-dark">{{ $application->reapplication_reason }}</div>
                                                </div>
                                            @endif

                                            <!-- 타임라인 연결선 -->
                                            @if (!$loop->last)
                                                <div class="timeline-connector position-absolute"
                                                    style="left: 19px; top: 40px; width: 2px; height: {{ $loop->index < 3 ? '80' : '60' }}px; background: linear-gradient(to bottom, rgba({{ $log['color'] === 'success' ? '40, 167, 69' : ($log['color'] === 'danger' ? '220, 53, 69' : '108, 117, 125') }}, 0.3), rgba(233, 236, 239, 0.5));">
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center text-muted py-3">
                                <i class="fe fe-clock fe-2x mb-2"></i>
                                <p>진행 로그가 없습니다.</p>
                            </div>
                        @endif

                        @if ($application->admin_notes)
                            <div class="mt-4 p-3 bg-warning bg-opacity-10 border-start border-warning border-4">
                                <h6 class="text-warning mb-2">
                                    <i class="fe fe-message-circle me-1"></i>관리자 메모
                                </h6>
                                <div class="small">{{ $application->admin_notes }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- 파트너 등급 정보 -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">파트너 등급별 혜택</h6>
                    </div>
                    <div class="card-body">
                        @foreach ($partnerTiers as $tier)
                            <div class="mb-3 p-2 border rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">{{ $tier->tier_name }}</span>
                                    <span class="badge bg-info">{{ $tier->commission_rate }}%</span>
                                </div>
                                @if ($tier->description)
                                    <small class="text-muted">{{ $tier->description }}</small>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>
            <div class="col-lg-8">

                <!-- 평가 결과 -->
                <div class="row mb-4">
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm d-flex" style="min-height: 200px;">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="fe fe-check-circle me-2"></i>강점</h6>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-center">
                                @if (count($evaluation['strengths']) > 0)
                                    <ul class="list-unstyled mb-0">
                                        @foreach ($evaluation['strengths'] as $strength)
                                            <li class="mb-2">
                                                <i class="fe fe-plus-circle text-success me-2"></i>{{ $strength }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-muted mb-0">특별한 강점이 발견되지 않았습니다.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm d-flex" style="min-height: 200px;">
                            <div class="card-header bg-warning text-white">
                                <h6 class="mb-0"><i class="fe fe-alert-triangle me-2"></i>우려사항</h6>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-center">
                                @if (count($evaluation['concerns']) > 0)
                                    <ul class="list-unstyled mb-0">
                                        @foreach ($evaluation['concerns'] as $concern)
                                            <li class="mb-2">
                                                <i class="fe fe-minus-circle text-warning me-2"></i>{{ $concern }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-muted mb-0">특별한 우려사항이 없습니다.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>



                <!-- 개인정보 -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fe fe-user me-2"></i>개인정보</h5>
                    </div>
                    <div class="card-body">
                        @if ($application->personal_info)
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <strong>이름:</strong> {{ $application->personal_info['name'] ?? 'N/A' }}
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>연락처:</strong> {{ $application->personal_info['phone'] ?? 'N/A' }}
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>이메일:</strong>
                                    {{ $application->personal_info['email'] ?? ($application->user->email ?? 'N/A') }}
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>출생년도:</strong> {{ $application->personal_info['birth_year'] ?? 'N/A' }}
                                </div>
                                <div class="col-12 mb-3">
                                    <strong>주소:</strong> {{ $application->personal_info['address'] ?? 'N/A' }}
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>학력:</strong> {{ $application->personal_info['education_level'] ?? 'N/A' }}
                                </div>
                                @if (isset($application->personal_info['emergency_contact']))
                                    <div class="col-12">
                                        <strong>비상연락처:</strong>
                                        {{ $application->personal_info['emergency_contact']['name'] ?? 'N/A' }}
                                        ({{ $application->personal_info['emergency_contact']['phone'] ?? 'N/A' }})
                                        - {{ $application->personal_info['emergency_contact']['relationship'] ?? 'N/A' }}
                                    </div>
                                @endif
                            </div>
                        @else
                            <p class="text-muted">개인정보가 입력되지 않았습니다.</p>
                        @endif
                    </div>
                </div>



                <!-- 경력정보 -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fe fe-briefcase me-2"></i>경력정보</h5>
                    </div>
                    <div class="card-body">
                        @if ($application->experience_info)
                            <div class="mb-3">
                                <strong>총 경력:</strong>
                                <span
                                    class="badge bg-primary">{{ $application->experience_info['total_years'] ?? 0 }}년</span>
                            </div>

                            @if (isset($application->experience_info['career_summary']))
                                <div class="mb-3">
                                    <strong>경력 요약:</strong>
                                    <div class="mt-2 p-3 bg-light rounded">
                                        {{ $application->experience_info['career_summary'] }}
                                    </div>
                                </div>
                            @endif

                            @if (isset($application->experience_info['previous_companies']) &&
                                    count($application->experience_info['previous_companies']) > 0)
                                <div class="mb-3">
                                    <strong>이전 직장:</strong>
                                    <div class="mt-2">
                                        @foreach ($application->experience_info['previous_companies'] as $company)
                                            <div class="border rounded p-3 mb-2">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1">{{ $company['company'] ?? 'N/A' }}</h6>
                                                        <p class="mb-1">{{ $company['position'] ?? 'N/A' }}</p>
                                                        <small
                                                            class="text-muted">{{ $company['period'] ?? 'N/A' }}</small>
                                                    </div>
                                                </div>
                                                @if (isset($company['description']))
                                                    <div class="mt-2">
                                                        <small>{{ $company['description'] }}</small>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if (isset($application->experience_info['bio']))
                                <div class="mb-3">
                                    <strong>자기소개:</strong>
                                    <div class="mt-2 p-3 bg-light rounded">
                                        {{ $application->experience_info['bio'] }}
                                    </div>
                                </div>
                            @endif

                            @if (isset($application->experience_info['portfolio_url']))
                                <div class="mb-3">
                                    <strong>포트폴리오:</strong>
                                    <a href="{{ $application->experience_info['portfolio_url'] }}" target="_blank"
                                        class="ms-2">
                                        {{ $application->experience_info['portfolio_url'] }}
                                        <i class="fe fe-external-link ms-1"></i>
                                    </a>
                                </div>
                            @endif
                        @else
                            <p class="text-muted">경력정보가 입력되지 않았습니다.</p>
                        @endif
                    </div>
                </div>

                <!-- 기술정보 -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fe fe-code me-2"></i>기술정보</h5>
                    </div>
                    <div class="card-body">
                        @if ($application->skills_info)
                            @if (isset($application->skills_info['skills']) && count($application->skills_info['skills']) > 0)
                                <div class="mb-3">
                                    <strong>보유 기술:</strong>
                                    <div class="mt-2">
                                        @foreach ($application->skills_info['skills'] as $skill)
                                            <span class="badge bg-primary me-1 mb-1">{{ $skill }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if (isset($application->skills_info['skill_levels']) && count($application->skills_info['skill_levels']) > 0)
                                <div class="mb-3">
                                    <strong>기술 수준:</strong>
                                    <div class="mt-2">
                                        @foreach ($application->skills_info['skill_levels'] as $skill => $level)
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span>{{ $skill }}</span>
                                                <span
                                                    class="badge
                                                @if ($level === '상급') bg-success
                                                @elseif($level === '중급') bg-warning
                                                @else bg-secondary @endif">{{ $level }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if (isset($application->skills_info['certifications']) && count($application->skills_info['certifications']) > 0)
                                <div class="mb-3">
                                    <strong>자격증:</strong>
                                    <div class="mt-2">
                                        @foreach ($application->skills_info['certifications'] as $cert)
                                            <span class="badge bg-success me-1 mb-1">{{ $cert }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if (isset($application->skills_info['languages']) && count($application->skills_info['languages']) > 0)
                                <div class="mb-3">
                                    <strong>언어:</strong>
                                    <div class="mt-2">
                                        @foreach ($application->skills_info['languages'] as $lang)
                                            <span class="badge bg-info me-1 mb-1">{{ $lang }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @else
                            <p class="text-muted">기술정보가 입력되지 않았습니다.</p>
                        @endif
                    </div>
                </div>

                <!-- 근무 조건 -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fe fe-clock me-2"></i>근무 조건</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <strong>희망 시급:</strong>
                                <span
                                    class="fs-5 fw-bold text-primary">{{ number_format($application->expected_hourly_rate ?? 0) }}원</span>
                            </div>
                            @if (isset($application->preferred_work_areas))
                                <div class="col-md-6 mb-3">
                                    <strong>선호 지역:</strong>
                                    @if (isset($application->preferred_work_areas['regions']))
                                        @foreach ($application->preferred_work_areas['regions'] as $region)
                                            <span class="badge bg-secondary me-1">{{ $region }}</span>
                                        @endforeach
                                    @endif
                                </div>
                            @endif
                        </div>
                        @if (isset($application->availability_schedule))
                            <div class="mb-3">
                                <strong>근무 가능 시간:</strong>
                                <!-- 여기에 근무 시간 테이블 표시 -->
                            </div>
                        @endif
                    </div>
                </div>

                <!-- 추천사항 -->
                @if (count($evaluation['recommendations']) > 0)
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <h6 class="alert-heading"><i class="fe fe-lightbulb me-2"></i>추천사항</h6>
                                <ul class="mb-0">
                                    @foreach ($evaluation['recommendations'] as $recommendation)
                                        <li>{{ $recommendation }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif


                <!-- 관리자 메모 -->
                @if ($application->admin_notes)
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">관리자 메모</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-0">{{ $application->admin_notes }}</p>
                        </div>
                    </div>
                @endif


                <!-- 액션 버튼 -->
                @if (in_array($application->application_status, ['submitted', 'reviewing', 'interview', 'reapplied']))
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-center gap-3">
                                        @if (in_array($application->application_status, ['submitted', 'reviewing', 'interview', 'reapplied']))
                                            <button type="button" class="btn btn-success" onclick="showApproveModal()">
                                                <i class="fe fe-check me-2"></i>승인
                                            </button>
                                            <button type="button" class="btn btn-danger" onclick="showRejectModal()">
                                                <i class="fe fe-x me-2"></i>거부
                                            </button>
                                            <button type="button" class="btn btn-info" onclick="showInterviewModal()">
                                                <i class="fe fe-calendar me-2"></i>
                                                @if ($application->application_status === 'interview')
                                                    면접 수정
                                                @else
                                                    면접 설정
                                                @endif
                                            </button>
                                        @endif

                                        <!-- 삭제 버튼 - 특정 상태에서만 표시 -->
                                        @if (in_array($application->application_status, ['pending', 'submitted', 'reviewing', 'rejected', 'draft', 'cancelled']))
                                            <button type="button" class="btn btn-outline-secondary"
                                                onclick="showDeleteModal()">
                                                <i class="fe fe-trash-2 me-2"></i>삭제
                                            </button>
                                        @endif

                                        @if (
                                            $application->application_status === 'reviewing' &&
                                                !in_array($application->application_status, ['submitted', 'interview', 'reapplied']))
                                            <button type="button" class="btn btn-outline-warning"
                                                onclick="setReviewingStatus()">
                                                <i class="fe fe-eye me-2"></i>검토 시작
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="d-flex justify-content-center gap-3">
                    <x-jiny-auth::modal-delete :deleteUrl="route('admin.partner.approval.destroy', $application->id)">
                        <i class="fe fe-trash-2 me-2"></i>신청서 삭제
                    </x-jiny-auth::modal-delete>
                </div>



            </div>
        </div>



    </div>

    <!-- 승인 모달 -->
    <div class="modal fade" id="approveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">파트너 신청 승인</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="approveForm" action="{{ route('admin.partner.approval.approve', $application->id) }}"
                    method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-success">
                            <i class="fe fe-check-circle me-2"></i>
                            <strong>승인 확인</strong><br>
                            이 지원자를 파트너로 승인하면 자동으로 파트너 회원으로 등록됩니다.
                        </div>

                        <div class="form-group mb-3">
                            <label for="admin_notes">관리자 메모 (선택사항)</label>
                            <textarea name="admin_notes" id="admin_notes" class="form-control" rows="3"
                                placeholder="승인과 관련된 메모를 입력하세요..."></textarea>
                        </div>

                        <div class="form-group mb-3">
                            <label for="welcome_message">환영 메시지 (선택사항)</label>
                            <textarea name="welcome_message" id="welcome_message" class="form-control" rows="2"
                                placeholder="승인 알림과 함께 전송할 환영 메시지를 입력하세요..."></textarea>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="notify_user" id="notify_user"
                                value="1" checked>
                            <label class="form-check-label" for="notify_user">
                                지원자에게 승인 알림 전송
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fe fe-check me-2"></i>승인 확인
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 거부 모달 -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">파트너 신청 거부</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="rejectForm" action="{{ route('admin.partner.approval.reject', $application->id) }}"
                    method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fe fe-alert-triangle me-2"></i>
                            <strong>거부 확인</strong><br>
                            이 작업은 되돌릴 수 없습니다. 신중하게 검토해주세요.
                        </div>

                        <div class="form-group mb-3">
                            <label for="rejection_reason">거부 사유 <span class="text-danger">*</span></label>
                            <textarea name="rejection_reason" id="rejection_reason" class="form-control" rows="4"
                                placeholder="거부 사유를 상세히 입력해주세요..." required></textarea>
                            <small class="form-text text-muted">지원자가 개선할 수 있도록 구체적인 피드백을 제공해주세요.</small>
                        </div>

                        <div class="form-group mb-3">
                            <label for="admin_notes_reject">관리자 메모 (선택사항)</label>
                            <textarea name="admin_notes" id="admin_notes_reject" class="form-control" rows="2"
                                placeholder="내부 참고용 메모를 입력하세요..."></textarea>
                        </div>

                        <div class="form-group mb-3">
                            <label for="feedback_message">지원자 피드백 메시지 (선택사항)</label>
                            <textarea name="feedback_message" id="feedback_message" class="form-control" rows="3"
                                placeholder="지원자에게 전달할 추가 피드백 메시지를 입력하세요..."></textarea>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="notify_user" id="notify_user_reject"
                                value="1" checked>
                            <label class="form-check-label" for="notify_user_reject">
                                지원자에게 거부 알림 전송
                            </label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="allow_reapply" id="allow_reapply"
                                value="1" checked>
                            <label class="form-check-label" for="allow_reapply">
                                재신청 허용
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fe fe-x me-2"></i>거부 확인
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 면접 설정 모달 -->
    <div class="modal fade" id="interviewModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        @if ($application->application_status === 'interview')
                            면접 일정 수정
                        @else
                            면접 일정 설정
                        @endif
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="interviewForm"
                    action="{{ $application->application_status === 'interview' ? route('admin.partner.approval.interview.update', $application->id) : route('admin.partner.approval.interview.schedule', $application->id) }}"
                    method="POST">
                    @csrf
                    @if ($application->application_status === 'interview')
                        @method('PUT')
                    @endif
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fe fe-calendar me-2"></i>
                            <strong>
                                @if ($application->application_status === 'interview')
                                    면접 일정 수정
                                @else
                                    면접 일정 설정
                                @endif
                            </strong><br>
                            @if ($application->application_status === 'interview')
                                기존 면접 일정을 수정합니다. 지원자에게 변경된 일정이 통지됩니다.
                            @else
                                지원자에게 면접 일정이 통지됩니다.
                            @endif
                        </div>

                        <div class="form-group mb-3">
                            <label for="interview_date">면접 일시 <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="interview_date" id="interview_date" class="form-control"
                                required
                                @if ($application->interview_date) value="{{ $application->interview_date->format('Y-m-d\TH:i') }}" @endif>
                        </div>

                        <div class="form-group mb-3">
                            <label for="interview_location">면접 장소</label>
                            <input type="text" name="interview_location" id="interview_location" class="form-control"
                                placeholder="면접 장소를 입력하세요 (예: 본사 회의실 A, 온라인 화상면접 등)"
                                @if ($application->interview_feedback && isset($application->interview_feedback['location'])) value="{{ $application->interview_feedback['location'] }}" @endif>
                        </div>

                        <div class="form-group mb-3">
                            <label for="interview_notes">면접 안내사항</label>
                            <textarea name="interview_notes" id="interview_notes" class="form-control" rows="3"
                                placeholder="면접에 대한 추가 안내사항을 입력하세요...">{{ $application->interview_notes ?? '' }}</textarea>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="notify_user"
                                id="notify_user_interview" value="1" checked>
                            <label class="form-check-label" for="notify_user_interview">
                                지원자에게 면접 일정 알림 전송
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                        <button type="submit" class="btn btn-info">
                            <i class="fe fe-calendar me-2"></i>면접 설정
                        </button>
                    </div>
                </form>
            </div>


        </div>

    </div>




@endsection

@push('styles')
    <style>
        /* 진행 단계 스타일 */
        .step-circle {
            transition: all 0.3s ease;
        }

        .step-circle:hover {
            transform: scale(1.05);
        }

        /* 타임라인 스타일 */
        .timeline {
            position: relative;
        }

        .timeline-item {
            position: relative;
        }

        .timeline-marker {
            position: relative;
            z-index: 2;
        }

        .timeline-content {
            padding-left: 1rem;
        }

        .timeline-connector {
            position: absolute;
            left: 19px;
            top: 40px;
            width: 2px;
            height: 60px;
            background-color: #e9ecef;
            z-index: 1;
        }

        /* 호버 효과 */
        .hover-bg-light:hover {
            background-color: #f8f9fa !important;
        }

        /* 카드 그림자 효과 */
        .card {
            transition: box-shadow 0.2s ease;
        }

        .card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        }

        /* 배지 애니메이션 */
        .badge {
            transition: all 0.2s ease;
        }

        /* 진행 단계 연결선 */
        .progress-line {
            height: 2px;
            background: linear-gradient(to right, #28a745, #28a745);
            margin: 25px 0;
        }

        .progress-line.incomplete {
            background: #e9ecef;
        }

        /* 버튼 호버 효과 개선 */
        .btn {
            transition: all 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
        }
    </style>
@endpush

@push('scripts')
    <script>
        function showApproveModal() {
            new bootstrap.Modal(document.getElementById('approveModal')).show();
        }

        function showRejectModal() {
            new bootstrap.Modal(document.getElementById('rejectModal')).show();
        }

        function showInterviewModal() {
            new bootstrap.Modal(document.getElementById('interviewModal')).show();
        }

        // 승인 폼 AJAX 처리
        document.getElementById('approveForm').addEventListener('submit', function(e) {
            e.preventDefault(); // 기본 폼 제출 방지

            const form = this;
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;

            // 버튼 상태 변경
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="spinner-border spinner-border-sm me-2"></i>처리 중...';

            // AJAX 요청
            fetch(form.action, {
                    method: form.method,
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // 성공 메시지 표시
                        showToast('success', data.message);

                        // 모달 닫기
                        bootstrap.Modal.getInstance(document.getElementById('approveModal')).hide();

                        // 페이지 새로고침 (데이터 업데이트 반영)
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        // 오류 메시지 표시
                        showToast('error', data.message || '승인 처리 중 오류가 발생했습니다.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('error', '네트워크 오류가 발생했습니다.');
                })
                .finally(() => {
                    // 버튼 상태 복원
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                });
        });

        // 면접 설정 폼 AJAX 처리
        document.getElementById('interviewForm').addEventListener('submit', function(e) {
            e.preventDefault(); // 기본 폼 제출 방지

            const form = this;
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;

            // 버튼 상태 변경
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="spinner-border spinner-border-sm me-2"></i>처리 중...';

            // AJAX 요청
            fetch(form.action, {
                    method: form.method,
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // 성공 메시지 표시
                        showToast('success', data.message);

                        // 모달 닫기
                        bootstrap.Modal.getInstance(document.getElementById('interviewModal')).hide();

                        // 페이지 새로고침 (데이터 업데이트 반영)
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        // 오류 메시지 표시
                        showToast('error', data.message || '면접 설정 중 오류가 발생했습니다.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('error', '네트워크 오류가 발생했습니다.');
                })
                .finally(() => {
                    // 버튼 상태 복원
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                });
        });

        // 토스트 메시지 표시 함수
        function showToast(type, message) {
            // 기존 토스트 제거
            const existingToast = document.querySelector('.toast-container .toast');
            if (existingToast) {
                existingToast.remove();
            }

            // 토스트 컨테이너가 없으면 생성
            let toastContainer = document.querySelector('.toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
                document.body.appendChild(toastContainer);
            }

            // 토스트 HTML 생성
            const toastHtml = `
                <div class="toast align-items-center text-bg-${type === 'success' ? 'success' : 'danger'} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fe fe-${type === 'success' ? 'check' : 'x'} me-2"></i>
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;

            toastContainer.innerHTML = toastHtml;
            const toastElement = toastContainer.querySelector('.toast');
            const toast = new bootstrap.Toast(toastElement);
            toast.show();
        }

        // 삭제 모달 표시 함수
        function showDeleteModal() {
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        // 삭제 폼 AJAX 처리
        document.getElementById('deleteForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;

            // 버튼 비활성화
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fe fe-loader me-2"></i>삭제 중...';

            // FormData 생성
            const formData = new FormData(form);

            fetch(form.action, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('success', data.message);

                        // 모달 닫기
                        bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();

                        // 메인 콘텐츠 영역 삭제 효과
                        const mainContent = document.querySelector('.container-fluid');
                        if (mainContent) {
                            // 삭제된 항목 표시 오버레이 추가
                            const overlay = document.createElement('div');
                            overlay.style.cssText = `
                            position: fixed;
                            top: 0;
                            left: 0;
                            right: 0;
                            bottom: 0;
                            background: rgba(220, 53, 69, 0.1);
                            backdrop-filter: blur(2px);
                            z-index: 1040;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        `;

                            const deleteMessage = document.createElement('div');
                            deleteMessage.style.cssText = `
                            background: white;
                            padding: 30px;
                            border-radius: 10px;
                            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                            text-align: center;
                            max-width: 400px;
                            border-left: 5px solid #dc3545;
                        `;
                            deleteMessage.innerHTML = `
                            <div class="text-danger mb-3">
                                <i class="fe fe-check-circle" style="font-size: 48px;"></i>
                            </div>
                            <h5 class="text-danger mb-2">신청서가 삭제되었습니다</h5>
                            <p class="text-muted mb-0">잠시 후 목록으로 이동합니다...</p>
                        `;

                            overlay.appendChild(deleteMessage);
                            document.body.appendChild(overlay);

                            // 메인 콘텐츠 페이드 아웃
                            mainContent.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
                            mainContent.style.opacity = '0.3';
                            mainContent.style.transform = 'scale(0.95)';
                            mainContent.style.filter = 'grayscale(100%)';
                        }

                        // 2초 후 이전 페이지로 이동 (시각 효과 시간 고려)
                        setTimeout(() => {
                            if (data.redirect) {
                                window.location.href = data.redirect;
                            } else {
                                window.history.back();
                            }
                        }, 2000);
                    } else {
                        showToast('error', data.message || '삭제 처리 중 오류가 발생했습니다.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('error', '네트워크 오류가 발생했습니다.');
                })
                .finally(() => {
                    // 버튼 상태 복원
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                });
        });
    </script>
@endpush
