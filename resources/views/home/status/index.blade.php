@extends('jiny-partner::layouts.home')

@section('title', $pageTitle ?? '파트너 신청 상태')

@section('styles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
    <div class="container-fluid py-4">

        <!-- 헤더 -->
        <section class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-0">{{ $pageTitle ?? '파트너 신청 상태' }}</h2>
                        <p class="text-muted mb-0">현재 파트너 신청 진행 상황을 확인하세요.</p>
                    </div>
                    <div>
                        @if(isset($currentApplication) && in_array($currentApplication->application_status, ['draft', 'submitted', 'reviewing', 'rejected', 'reapplied']))
                            <button type="button" class="btn btn-danger me-2"
                                id="cancelApplicationBtn"
                                data-id="{{ $currentApplication->id }}">
                                <i class="bi bi-trash me-1"></i>신청 취소
                            </button>
                        @endif
                        <a href="/home/partner/regist" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left me-1"></i>신청 메인으로
                        </a>
                    </div>
                </div>
            </div>
        </section>


        <section class="mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <div
                                    style="width: 40px; height: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-person-fill text-white"></i>
                                </div>
                            </div>
                            <div>
                                <h6 class="mb-1 fw-bold">{{ $user->name ?? '사용자' }}님</h6>
                                <small class="text-muted">로그인된 사용자</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 메인 컨텐츠 영역을 col-8과 col-4로 분할 -->
        <div class="row">
            <!-- 좌측 메인 컨텐츠 (col-8) -->
            <div class="col-8">
                <!-- 신청 상태 카드 -->
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
                                <span
                                    class="badge fs-6 px-3 py-2
                                    @if ($currentApplication->application_status === 'approved') bg-success
                                    @elseif($currentApplication->application_status === 'rejected') bg-danger
                                    @elseif($currentApplication->application_status === 'submitted') bg-info
                                    @elseif($currentApplication->application_status === 'reviewing') bg-warning
                                    @elseif($currentApplication->application_status === 'interview') bg-info
                                    @else bg-secondary @endif">
                                    @switch($currentApplication->application_status)
                                        @case('draft')
                                            작성중
                                        @break

                                        @case('submitted')
                                            제출완료
                                        @break

                                        @case('reviewing')
                                            검토중
                                        @break

                                        @case('interview')
                                            면접예정
                                        @break

                                        @case('approved')
                                            승인됨
                                        @break

                                        @case('rejected')
                                            반려됨
                                        @break

                                        @case('reapplied')
                                            재신청
                                        @break

                                        @default
                                            알 수 없음
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
                                    <div class="fw-semibold">{{ $currentApplication->created_at->format('Y-m-d H:i') }}
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                @if ($currentApplication->submitted_at)
                                    <div class="mb-3">
                                        <small class="text-muted d-block">제출일시</small>
                                        <div class="fw-semibold">
                                            {{ $currentApplication->submitted_at->format('Y-m-d H:i') }}</div>
                                    </div>
                                @endif

                                @if ($currentApplication->interview_date)
                                    <div class="mb-3">
                                        <small class="text-muted d-block">면접일정</small>
                                        <div class="fw-semibold">
                                            {{ $currentApplication->interview_date->format('Y-m-d H:i') }}</div>
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
                                       @elseif($action['class'] === 'btn-danger') btn-danger
                                       @elseif($action['class'] === 'btn-outline-primary') btn-outline-primary
                                       @elseif($action['class'] === 'btn-outline-secondary') btn-outline-secondary
                                       @elseif($action['class'] === 'btn-outline-danger') btn-outline-danger
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
                                                            <i
                                                                class="bi bi-clock me-1"></i>{{ $log['date']->format('Y-m-d H:i') }}
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
                    </div>
                @endif
            </div> <!-- col-8 끝 -->

            <!-- 우측 사이드바 (col-4) -->
            <div class="col-4">
                <!-- 추천 파트너 정보 (추천인이 있는 경우에만 표시) -->
                @if ($hasReferrer && isset($referrerInfo))
                    <div class="card mb-4 border-0 shadow-lg"
                        style="border-left: 4px solid #28a745 !important; background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                        <div class="card-body p-4 text-white">
                            <!-- 아이콘과 제목 -->
                            <div class="text-center mb-4">
                                <div
                                    style="width: 80px; height: 80px; background: rgba(255, 255, 255, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                                    <i class="bi bi-person-check fs-1 text-white"></i>
                                </div>
                                <h5 class="card-title mb-1 fw-bold text-white">추천인을 통한 신청</h5>
                                <p class="card-text mb-0 small" style="color: rgba(255, 255, 255, 0.8);">
                                    {{ $referrerInfo['name'] ?? '알 수 없음' }}님의 추천</p>
                            </div>

                            <!-- 상위 추천파트너 정보 -->
                            <div class="mb-4">
                                <div class="text-center">
                                    <div class="fw-bold text-white mb-2">상위 추천파트너</div>
                                    <div class="text-white h5 mb-1">{{ $referrerInfo['name'] ?? '알 수 없음' }}</div>
                                    <div class="mb-2">
                                        <span class="badge"
                                            style="background-color: {{ $referrerInfo['tier_color'] ?? '#6c757d' }}">
                                            {{ $referrerInfo['tier'] ?? 'Standard' }}
                                        </span>
                                    </div>
                                    <div class="text-white font-monospace small">
                                        코드: {{ $referrerInfo['referral_code_used'] ?? 'N/A' }}
                                    </div>
                                </div>
                            </div>


                            <!-- 검증 배지 -->
                            <div class="text-center">
                                <div
                                    style="background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 0.5rem; padding: 1rem;">
                                    <i class="bi bi-check-circle fs-3 mb-2 d-block text-white"></i>
                                    <div class="fw-bold text-white small">검증된 추천</div>
                                    <div class="small" style="color: rgba(255, 255, 255, 0.8);">신뢰 네트워크</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- 진행률 정보 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0 fw-bold">
                            <i class="bi bi-graph-up me-2"></i>신청 진행률
                        </h6>
                    </div>
                    <div class="card-body">
                        @php
                            $progressPercentage = 0;
                            $statusText = '시작됨';

                            switch ($currentApplication->application_status) {
                                case 'draft':
                                    $progressPercentage = 10;
                                    $statusText = '작성 중';
                                    break;
                                case 'submitted':
                                    $progressPercentage = 25;
                                    $statusText = '제출 완료';
                                    break;
                                case 'reviewing':
                                    $progressPercentage = 50;
                                    $statusText = '검토 중';
                                    break;
                                case 'interview':
                                    $progressPercentage = 75;
                                    $statusText = '면접 예정';
                                    break;
                                case 'approved':
                                    $progressPercentage = 100;
                                    $statusText = '승인 완료';
                                    break;
                                case 'rejected':
                                    $progressPercentage = 0;
                                    $statusText = '재검토 필요';
                                    break;
                            }
                        @endphp

                        <div class="text-center mb-3">
                            <div class="h4 mb-1">{{ $progressPercentage }}%</div>
                            <div class="small text-muted">{{ $statusText }}</div>
                        </div>

                        <div class="progress mb-3" style="height: 10px;">
                            <div class="progress-bar
                                @if ($currentApplication->application_status === 'approved') bg-success
                                @elseif($currentApplication->application_status === 'rejected') bg-danger
                                @else bg-primary @endif"
                                role="progressbar" style="width: {{ $progressPercentage }}%">
                            </div>
                        </div>

                        <div class="small text-muted text-center">
                            신청일: {{ $currentApplication->created_at->format('Y-m-d') }}
                        </div>
                    </div>
                </div>

                <!-- 도움말 카드 -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0 fw-bold">
                            <i class="bi bi-question-circle me-2"></i>도움말
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="small text-muted">
                            <div class="mb-3">
                                <div class="fw-semibold mb-1">문의 사항</div>
                                <div>파트너 신청에 관한 문의사항이 있으시면 고객센터로 연락해주세요.</div>
                            </div>

                            <div class="mb-3">
                                <div class="fw-semibold mb-1">처리 시간</div>
                                <div>일반적으로 신청서 검토는 3-5 영업일이 소요됩니다.</div>
                            </div>

                            @if ($hasReferrer)
                                <div class="mb-3">
                                    <div class="fw-semibold mb-1">추천 혜택</div>
                                    <div>추천인을 통한 신청으로 우선 검토 대상입니다.</div>
                                </div>
                            @endif
                        </div>

                        <div class="text-center mt-3">
                            <a href="mailto:partner@example.com" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-envelope me-1"></i>문의하기
                            </a>
                        </div>
                    </div>
                </div>
            </div> <!-- col-4 끝 -->

        </div> <!-- row 끝 -->
    </div> <!-- container-fluid 끝 -->

    <!-- 면접 정보 모달 (Layout 구조 외부) -->
    <div class="modal fade" id="interviewInfoModal" tabindex="-1" aria-labelledby="interviewInfoModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="interviewInfoModalLabel">
                        <i class="bi bi-calendar-event me-2"></i>면접 상세 정보
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($currentApplication->interview_date)
                        <!-- 면접 기본 정보 -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card border-primary border-2">
                                    <div class="card-body text-center">
                                        <i class="bi bi-calendar-check fs-1 text-primary mb-2"></i>
                                        <h6 class="fw-bold">면접 일정</h6>
                                        <div class="fs-5 fw-bold text-primary">
                                            {{ $currentApplication->interview_date->format('Y년 m월 d일') }}</div>
                                        <div class="text-muted">{{ $currentApplication->interview_date->format('H:i') }}
                                        </div>
                                        <small class="text-muted">
                                            @if ($currentApplication->interview_date->isFuture())
                                                <span
                                                    class="badge bg-info">{{ $currentApplication->interview_date->diffForHumans() }}</span>
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
                                        <div class="fw-bold">
                                            {{ $currentApplication->interview_feedback['location'] ?? '온라인 면접' }}</div>
                                        @if (isset($currentApplication->interview_feedback['location']) &&
                                                $currentApplication->interview_feedback['location'] !== '온라인 면접')
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
                    @if (isset($currentApplication->interview_feedback['notes']) && $currentApplication->interview_feedback['notes'])
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
                                @if (($currentApplication->interview_feedback['location'] ?? '온라인 면접') === '온라인 면접')
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
                                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center"
                                    style="width: 40px; height: 40px;">1</div>
                                <small class="d-block mt-1 fw-semibold">신분 확인</small>
                            </div>
                            <div class="col-md-3 text-center mb-3">
                                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center"
                                    style="width: 40px; height: 40px;">2</div>
                                <small class="d-block mt-1 fw-semibold">자기 소개</small>
                            </div>
                            <div class="col-md-3 text-center mb-3">
                                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center"
                                    style="width: 40px; height: 40px;">3</div>
                                <small class="d-block mt-1 fw-semibold">기술 질문</small>
                            </div>
                            <div class="col-md-3 text-center mb-3">
                                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center"
                                    style="width: 40px; height: 40px;">4</div>
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
                    @if ($currentApplication->interview_date && $currentApplication->interview_date->isFuture())
                        <button type="button" class="btn btn-primary" onclick="window.print()">
                            <i class="bi bi-printer me-1"></i>면접 안내 인쇄
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 즉시 실행하는 테스트
        console.log('JavaScript 파일이 로드되었습니다');

        // 페이지 로드 후 실행
        window.addEventListener('load', function() {
            console.log('Window loaded');
        });

        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM Content Loaded - 실행됨!');

            // Cancel button event listener
            const cancelBtn = document.getElementById('cancelApplicationBtn');
            console.log('Button element:', cancelBtn);

            if (cancelBtn) {
                console.log('Cancel button found');

                cancelBtn.addEventListener('click', function(event) {
                    console.log('Button clicked!');
                    event.preventDefault();

                    // 먼저 alert로 버튼 클릭 확인
                    //alert('신청 취소 버튼이 클릭되었습니다!');

                    const applicationId = this.getAttribute('data-id');
                    console.log('Cancel button clicked for application ID:', applicationId);

                    const confirmMessage = '⚠️ 신청 취소 확인\n\n정말로 신청을 취소하시겠습니까?\n\n' +
                                           '• 신청서가 완전히 삭제됩니다\n' +
                                           '• 업로드한 모든 파일이 삭제됩니다\n' +
                                           '• 이 작업은 되돌릴 수 없습니다\n\n' +
                                           '계속하시려면 "확인"을 클릭하세요.';

                    if (confirm(confirmMessage)) {
                        console.log('User confirmed cancellation, proceeding...');
                        cancelApplication(applicationId);
                    } else {
                        console.log('User cancelled the operation');
                    }
                });
            } else {
                console.log('Cancel button not found');
            }


            // CSRF 토큰 확인
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            console.log('CSRF token meta tag found:', !!csrfToken);
            if (csrfToken) {
                console.log('CSRF token value:', csrfToken.getAttribute('content'));
            }
        });

        // Cancel application function
        function cancelApplication(applicationId) {
            console.log('cancelApplication called with ID:', applicationId);

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
            console.log('CSRF Token:', csrfToken);

            // Using Laravel route helper for proper URL generation
            const url = '{{ route("home.partner.regist.cancel", ":id") }}'.replace(':id', applicationId);
            console.log('Request URL:', url);

            fetch(url, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin'
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);

                // Check for non-200 responses
                if (!response.ok) {
                    // Try to get error message from response
                    return response.text().then(text => {
                        console.log('Error response text:', text);
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    });
                }

                // Check if response is actually JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        console.log('Non-JSON response:', text);
                        throw new Error('Server returned non-JSON response');
                    });
                }

                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);

                if (data.success) {
                    alert(data.message || '신청이 성공적으로 취소되었습니다.');
                    console.log('Redirecting to:', data.redirect || '/home/partner/regist');
                    window.location.href = data.redirect || '/home/partner/regist';
                } else {
                    alert(data.message || '신청 취소 중 오류가 발생했습니다.');
                }
            })
            .catch(error => {
                console.error('AJAX Error details:', error);
                alert('신청 취소 중 오류가 발생했습니다: ' + error.message);
            });
        }
    </script>
@endpush
