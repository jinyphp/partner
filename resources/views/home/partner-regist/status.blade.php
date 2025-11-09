@extends($layout ?? 'jiny-auth::layouts.home')

@section('title', $pageTitle ?? '파트너 신청 상태')

@section('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .process-step {
            position: relative;
        }

        .process-step::after {
            content: '';
            position: absolute;
            top: 50%;
            right: -25px;
            width: 50px;
            height: 2px;
            background: #e9ecef;
            z-index: 1;
        }

        .process-step:last-child::after {
            display: none;
        }

        .step-circle {
            width: 60px;
            height: 60px;
            background: white;
            border: 3px solid #e9ecef;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-weight: bold;
            color: #6c757d;
            position: relative;
            z-index: 2;
        }

        .step-circle.active {
            border-color: #667eea;
            color: white;
            background: #667eea;
        }

        .step-circle.completed {
            border-color: #28a745;
            color: white;
            background: #28a745;
        }

        .step-circle.current {
            border-color: #ffc107;
            color: #333;
            background: #ffc107;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <!-- 헤더 -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-0">{{ $pageTitle ?? '파트너 신청 상태' }}</h2>
                        <p class="text-muted mb-0">현재 파트너 신청 진행 상황을 확인하세요.</p>
                    </div>
                    <div>
                        <span class="text-muted small d-block mb-2">{{ $user->name ?? '사용자' }}님</span>
                        <a href="/home/partner/regist" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-arrow-left me-1"></i>신청 메인으로
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        @if ($statusInfo['color'] === 'success')
                            <div class="bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3"
                                style="width: 40px; height: 40px;">
                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                            </div>
                        @elseif($statusInfo['color'] === 'warning')
                            <div class="bg-warning bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3"
                                style="width: 40px; height: 40px;">
                                <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
                            </div>
                        @elseif($statusInfo['color'] === 'danger')
                            <div class="bg-danger bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3"
                                style="width: 40px; height: 40px;">
                                <i class="bi bi-x-circle-fill text-danger fs-5"></i>
                            </div>
                        @elseif($statusInfo['color'] === 'info')
                            <div class="bg-info bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3"
                                style="width: 40px; height: 40px;">
                                <i class="bi bi-info-circle-fill text-info fs-5"></i>
                            </div>
                        @else
                            <div class="bg-secondary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3"
                                style="width: 40px; height: 40px;">
                                <i class="bi bi-question-circle-fill text-secondary fs-5"></i>
                            </div>
                        @endif

                        <div>
                            <h6 class="mb-1 fw-bold">{{ $statusInfo['title'] }}</h6>
                            <small class="text-muted">{{ $statusInfo['message'] }}</small>
                        </div>
                    </div>

                    <div class="text-end">
                        <span class="badge fs-6 px-3 py-2
                            @if ($currentApplication->application_status === 'approved') bg-success
                            @elseif($currentApplication->application_status === 'rejected') bg-danger
                            @elseif($currentApplication->application_status === 'submitted') bg-info
                            @elseif($currentApplication->application_status === 'reviewing') bg-warning
                            @elseif($currentApplication->application_status === 'interview') bg-info
                            @else bg-secondary @endif">
                            @switch($currentApplication->application_status)
                                @case('draft') 작성중 @break
                                @case('submitted') 제출완료 @break
                                @case('reviewing') 검토중 @break
                                @case('interview') 면접예정 @break
                                @case('approved') 승인됨 @break
                                @case('rejected') 반려됨 @break
                                @case('reapplied') 재신청 @break
                                @default 알 수 없음
                            @endswitch
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <small class="text-muted d-block">신청번호</small>
                            <div class="fw-semibold">#{{ $currentApplication->id }}</div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block">신청일시</small>
                            <div class="fw-semibold">{{ $currentApplication->created_at->format('Y-m-d H:i') }}</div>
                        </div>

                    </div>

                    <div class="col-md-6">
                        @if ($currentApplication->submitted_at)
                            <div class="mb-3">
                                <small class="text-muted d-block">제출일시</small>
                                <div class="fw-semibold">{{ $currentApplication->submitted_at->format('Y-m-d H:i') }}</div>
                            </div>
                        @endif

                        @if ($currentApplication->interview_date)
                            <div class="mb-3">
                                <small class="text-muted d-block">면접일정</small>
                                <div class="fw-semibold">{{ $currentApplication->interview_date->format('Y-m-d H:i') }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                @if ($currentApplication->rejection_reason)
                    <div class="alert alert-danger p-3 mt-3">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-exclamation-triangle-fill me-2 mt-1"></i>
                            <div>
                                <small class="fw-semibold d-block">반려사유</small>
                                <div class="small">{{ $currentApplication->rejection_reason }}</div>
                            </div>
                        </div>
                    </div>
                @endif

                @if (isset($statusInfo['actions']) && count($statusInfo['actions']) > 0)
                    <div class="mt-3 text-end">
                        @foreach ($statusInfo['actions'] as $action)
                            <a href="{{ $action['url'] }}"
                                class="btn btn-sm
                               @if ($action['class'] === 'btn-primary') btn-primary
                               @elseif($action['class'] === 'btn-success') btn-success
                               @elseif($action['class'] === 'btn-outline-primary') btn-outline-primary
                               @elseif($action['class'] === 'btn-outline-secondary') btn-outline-secondary
                               @else btn-secondary @endif"
                               @if (isset($action['data-bs-toggle'])) data-bs-toggle="{{ $action['data-bs-toggle'] }}" @endif
                               @if (isset($action['data-bs-target'])) data-bs-target="{{ $action['data-bs-target'] }}" @endif
                               @if (isset($action['onclick'])) onclick="{{ $action['onclick'] }}" @endif>
                                {{ $action['label'] }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>


        <!-- 진행 로그 -->
        @if (isset($progressLogs) && count($progressLogs) > 0)
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-clock-history text-primary fs-5 me-2"></i>
                        <h6 class="card-title mb-0 fw-bold">진행 로그</h6>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="timeline">
                        @foreach ($progressLogs as $index => $log)
                            <div class="timeline-item d-flex align-items-start mb-4">
                                <div class="timeline-marker me-3">
                                    <div class="bg-{{ $log['color'] }} rounded-circle d-flex align-items-center justify-content-center text-white"
                                        style="width: 40px; height: 40px;">
                                        @switch($log['icon'])
                                            @case('edit')
                                                <i class="bi bi-pencil"></i>
                                            @break

                                            @case('send')
                                                <i class="bi bi-send"></i>
                                            @break

                                            @case('eye')
                                                <i class="bi bi-eye"></i>
                                            @break

                                            @case('calendar')
                                                <i class="bi bi-calendar-event"></i>
                                            @break

                                            @case('check-circle')
                                                <i class="bi bi-check-circle"></i>
                                            @break

                                            @case('x-circle')
                                                <i class="bi bi-x-circle"></i>
                                            @break

                                            @case('arrow-repeat')
                                                <i class="bi bi-arrow-repeat"></i>
                                            @break

                                            @default
                                                <i class="bi bi-circle"></i>
                                        @endswitch
                                    </div>
                                </div>
                                <div class="timeline-content flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 fw-bold text-{{ $log['color'] }}">
                                                {{ $log['title'] }}
                                                @if ($log['status'] === 'interview_scheduled' && $currentApplication->interview_date)
                                                    <small class="text-muted ms-2">
                                                        @if ($currentApplication->interview_date->isFuture())
                                                            <span class="badge bg-info">예정</span>
                                                        @elseif($currentApplication->interview_date->isToday())
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
                                                    <i class="bi bi-person me-1"></i>{{ $log['user'] }}
                                                    •
                                                    <i class="bi bi-clock me-1"></i>{{ $log['date']->format('Y-m-d H:i') }}
                                                    <span class="ms-2">({{ $log['date']->diffForHumans() }})</span>
                                                    @if ($log['date']->isToday())
                                                        <span class="badge bg-primary ms-2">TODAY</span>
                                                    @elseif($log['date']->isYesterday())
                                                        <span class="badge bg-secondary ms-2">YESTERDAY</span>
                                                    @endif
                                                </small>
                                            </div>
                                        </div>
                                        <div class="text-end ms-3">
                                            @if (isset($log['is_completed']) && $log['is_completed'])
                                                <span class="badge bg-success">완료</span>
                                            @else
                                                <span class="badge bg-secondary">진행중</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
        @endif

        <!-- 면접 정보 모달 -->
        <div class="modal fade" id="interviewInfoModal" tabindex="-1" aria-labelledby="interviewInfoModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="interviewInfoModalLabel">
                            <i class="bi bi-calendar-event me-2"></i>면접 상세 정보
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if($currentApplication->interview_date)
                        <!-- 면접 기본 정보 -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card border-primary border-2">
                                    <div class="card-body text-center">
                                        <i class="bi bi-calendar-check fs-1 text-primary mb-2"></i>
                                        <h6 class="fw-bold">면접 일정</h6>
                                        <div class="fs-5 fw-bold text-primary">{{ $currentApplication->interview_date->format('Y년 m월 d일') }}</div>
                                        <div class="text-muted">{{ $currentApplication->interview_date->format('H:i') }}</div>
                                        <small class="text-muted">
                                            @if($currentApplication->interview_date->isFuture())
                                                <span class="badge bg-info">{{ $currentApplication->interview_date->diffForHumans() }}</span>
                                            @elseif($currentApplication->interview_date->isToday())
                                                <span class="badge bg-warning">오늘</span>
                                            @else
                                                <span class="badge bg-secondary">완료</span>
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-info border-2">
                                    <div class="card-body text-center">
                                        <i class="bi bi-geo-alt fs-1 text-info mb-2"></i>
                                        <h6 class="fw-bold">면접 장소</h6>
                                        <div class="fw-bold">{{ $currentApplication->interview_feedback['location'] ?? '온라인 면접' }}</div>
                                        @if(isset($currentApplication->interview_feedback['location']) && $currentApplication->interview_feedback['location'] !== '온라인 면접')
                                            <small class="text-muted d-block mt-1">
                                                <i class="bi bi-clock me-1"></i>10분 전 도착 권장
                                            </small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @else
                        <!-- 면접 일정이 없는 경우 -->
                        <div class="alert alert-warning text-center">
                            <i class="bi bi-calendar-x fs-1 text-warning mb-3"></i>
                            <h5 class="alert-heading">면접 일정 미정</h5>
                            <p class="mb-0">아직 면접 일정이 확정되지 않았습니다.<br>곧 연락드리겠습니다.</p>
                        </div>
                        @endif

                        <!-- 면접 안내사항 -->
                        @if(isset($currentApplication->interview_feedback['notes']) && $currentApplication->interview_feedback['notes'])
                        <div class="alert alert-info">
                            <h6 class="alert-heading">
                                <i class="bi bi-info-circle me-1"></i>면접 안내사항
                            </h6>
                            <p class="mb-0">{{ $currentApplication->interview_feedback['notes'] }}</p>
                        </div>
                        @endif

                        <!-- 준비사항 및 주의사항 -->
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="fw-bold text-success">
                                    <i class="bi bi-check-circle me-1"></i>준비사항
                                </h6>
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="bi bi-person-badge text-primary me-2"></i>
                                        <strong>신분증 지참</strong>
                                        <small class="text-muted d-block ms-4">주민등록증, 운전면허증, 여권 중 1개</small>
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-folder text-primary me-2"></i>
                                        <strong>포트폴리오 준비</strong>
                                        <small class="text-muted d-block ms-4">기술 스택 및 프로젝트 경험 자료</small>
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-file-text text-primary me-2"></i>
                                        <strong>이력서 인쇄본</strong>
                                        <small class="text-muted d-block ms-4">신청서와 동일한 내용</small>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold text-warning">
                                    <i class="bi bi-exclamation-triangle me-1"></i>주의사항
                                </h6>
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="bi bi-clock text-warning me-2"></i>
                                        <strong>정시 도착</strong>
                                        <small class="text-muted d-block ms-4">10분 전 도착을 권장합니다</small>
                                    </li>
                                    @if(($currentApplication->interview_feedback['location'] ?? '온라인 면접') === '온라인 면접')
                                    <li class="mb-2">
                                        <i class="bi bi-camera-video text-warning me-2"></i>
                                        <strong>온라인 환경 체크</strong>
                                        <small class="text-muted d-block ms-4">카메라, 마이크, 인터넷 연결 상태</small>
                                    </li>
                                    @endif
                                    <li class="mb-2">
                                        <i class="bi bi-person-check text-warning me-2"></i>
                                        <strong>복장</strong>
                                        <small class="text-muted d-block ms-4">단정한 비즈니스 캐주얼</small>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- 면접 진행 절차 -->
                        <div class="bg-light p-3 rounded mt-4">
                            <h6 class="fw-bold">
                                <i class="bi bi-list-check text-primary me-1"></i>면접 진행 절차
                            </h6>
                            <div class="row">
                                <div class="col-md-3 text-center mb-3">
                                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">1</div>
                                    <small class="d-block mt-1 fw-semibold">신분 확인</small>
                                </div>
                                <div class="col-md-3 text-center mb-3">
                                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">2</div>
                                    <small class="d-block mt-1 fw-semibold">자기 소개</small>
                                </div>
                                <div class="col-md-3 text-center mb-3">
                                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">3</div>
                                    <small class="d-block mt-1 fw-semibold">기술 질문</small>
                                </div>
                                <div class="col-md-3 text-center mb-3">
                                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">4</div>
                                    <small class="d-block mt-1 fw-semibold">질의 응답</small>
                                </div>
                            </div>
                        </div>

                        <!-- 연락처 정보 -->
                        <div class="alert alert-secondary mt-4">
                            <h6 class="alert-heading">
                                <i class="bi bi-telephone me-1"></i>문의사항
                            </h6>
                            <p class="mb-1">면접 관련 문의사항이 있으시면 아래로 연락주세요.</p>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-envelope me-2"></i>
                                <span>partner@example.com</span>
                                <i class="bi bi-telephone ms-4 me-2"></i>
                                <span>02-1234-5678</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
                        @if($currentApplication->interview_date && $currentApplication->interview_date->isFuture())
                        <button type="button" class="btn btn-primary" onclick="window.print()">
                            <i class="bi bi-printer me-1"></i>면접 안내 인쇄
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>


@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Bootstrap Modal은 data-bs-toggle과 data-bs-target으로 자동 실행됩니다
        // 페이지 로드 후 Modal 요소 확인
        document.addEventListener('DOMContentLoaded', function() {
            const modalElement = document.getElementById('interviewInfoModal');
            if (modalElement) {
                console.log('Interview modal found and ready');
            } else {
                console.error('Interview modal not found');
            }
        });
    </script>
@endsection
