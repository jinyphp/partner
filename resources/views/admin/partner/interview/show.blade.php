@extends('jiny-partner::layouts.admin.sidebar')

@section('title', '면접 상세보기')

@section('content')
<div class="container-fluid">
    <!-- 헤더 -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">면접 상세보기</h1>
            <p class="text-muted mb-0">{{ $interview->name }}님의 면접 정보</p>
        </div>
        <div>
            @if(!in_array($interview->interview_status, ['completed']))
                <a href="{{ route('admin.partner.interview.edit', $interview->id) }}" class="btn btn-primary">
                    <i class="fe fe-edit me-1"></i>수정
                </a>
            @endif
            <a href="{{ route('admin.partner.interview.index') }}" class="btn btn-secondary">
                <i class="fe fe-arrow-left me-1"></i>목록
            </a>
        </div>
    </div>

    <!-- 네비게이션 -->
    @if($navigation['prev'] || $navigation['next'])
        <div class="card mb-3">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        @if($navigation['prev'])
                            <a href="{{ route('admin.partner.interview.show', $navigation['prev']->id) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="fe fe-chevron-left"></i> {{ $navigation['prev']->name }}
                            </a>
                        @endif
                    </div>
                    <div>
                        @if($navigation['next'])
                            <a href="{{ route('admin.partner.interview.show', $navigation['next']->id) }}" class="btn btn-sm btn-outline-secondary">
                                {{ $navigation['next']->name }} <i class="fe fe-chevron-right"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <!-- 메인 콘텐츠 -->
        <div class="col-md-8">
            <!-- 면접 기본 정보 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fe fe-calendar me-2"></i>면접 기본 정보
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong>면접 상태:</strong>
                            @php
                                $statusColor = match($interview->interview_status) {
                                    'scheduled' => 'warning',
                                    'in_progress' => 'info',
                                    'completed' => 'success',
                                    'cancelled' => 'danger',
                                    'no_show' => 'dark',
                                    default => 'secondary'
                                };
                            @endphp
                            <span class="badge bg-{{ $statusColor }} ms-2">{{ $interview->status_label }}</span>
                        </div>

                        <div class="col-md-6 mb-3">
                            <strong>면접 결과:</strong>
                            @if($interview->interview_result)
                                @php
                                    $resultColor = match($interview->interview_result) {
                                        'pass' => 'success',
                                        'fail' => 'danger',
                                        'pending' => 'warning',
                                        'hold' => 'secondary',
                                        default => 'secondary'
                                    };
                                @endphp
                                <span class="badge bg-{{ $resultColor }} ms-2">{{ $interview->result_label }}</span>
                            @else
                                <span class="text-muted ms-2">미정</span>
                            @endif
                        </div>

                        <div class="col-md-6 mb-3">
                            <strong>면접 유형:</strong>
                            <span class="ms-2">{{ $interview->type_label }}</span>
                        </div>

                        <div class="col-md-6 mb-3">
                            <strong>면접 차수:</strong>
                            <span class="ms-2">{{ $interview->round_label }}</span>
                        </div>

                        <div class="col-md-6 mb-3">
                            <strong>예정 일시:</strong>
                            @if($interview->scheduled_at)
                                <span class="ms-2">{{ $interview->scheduled_at->format('Y-m-d H:i') }}</span>
                            @else
                                <span class="text-muted ms-2">미정</span>
                            @endif
                        </div>

                        <div class="col-md-6 mb-3">
                            <strong>면접 시간:</strong>
                            <span class="ms-2">{{ $interview->duration_minutes ?? 60 }}분</span>
                        </div>

                        <div class="col-md-6 mb-3">
                            <strong>면접관:</strong>
                            @if($interview->interviewer)
                                <span class="ms-2">{{ $interview->interviewer->name }}</span>
                            @else
                                <span class="text-muted ms-2">미배정</span>
                            @endif
                        </div>

                        @if($interview->started_at)
                            <div class="col-md-6 mb-3">
                                <strong>시작 시간:</strong>
                                <span class="ms-2">{{ $interview->started_at->format('Y-m-d H:i') }}</span>
                            </div>
                        @endif

                        @if($interview->completed_at)
                            <div class="col-md-6 mb-3">
                                <strong>완료 시간:</strong>
                                <span class="ms-2">{{ $interview->completed_at->format('Y-m-d H:i') }}</span>
                            </div>
                        @endif

                        @if($interview->meeting_location)
                            <div class="col-12 mb-3">
                                <strong>면접 장소:</strong>
                                <span class="ms-2">{{ $interview->meeting_location }}</span>
                            </div>
                        @endif

                        @if($interview->meeting_url)
                            <div class="col-12 mb-3">
                                <strong>회의 URL:</strong>
                                <a href="{{ $interview->meeting_url }}" target="_blank" class="ms-2">{{ $interview->meeting_url }}</a>
                                @if($interview->meeting_password)
                                    <small class="text-muted">(비밀번호: {{ $interview->meeting_password }})</small>
                                @endif
                            </div>
                        @endif

                        @if($interview->preparation_notes)
                            <div class="col-12">
                                <strong>준비 사항:</strong>
                                <div class="mt-2 p-2 bg-light rounded">
                                    {!! nl2br(e($interview->preparation_notes)) !!}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- 지원자 정보 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fe fe-user me-2"></i>지원자 정보
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong>이름:</strong>
                            <span class="ms-2">{{ $interview->name }}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>이메일:</strong>
                            <span class="ms-2">{{ $interview->email }}</span>
                        </div>
                        @if($interview->referrer_code)
                            <div class="col-md-6 mb-3">
                                <strong>추천 코드:</strong>
                                <span class="badge bg-primary ms-2">{{ $interview->referrer_code }}</span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>추천인:</strong>
                                <span class="ms-2">{{ $interview->referrer_name }}</span>
                            </div>
                        @endif
                    </div>
                    @if($interview->application)
                        <div class="mt-3">
                            <a href="{{ route('admin.partner-approval.show', $interview->application->id) }}" class="btn btn-outline-primary btn-sm">
                                <i class="fe fe-file-text me-1"></i>신청서 보기
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- 면접 평가 -->
            @if($interview->interview_status === 'completed' && $evaluationStats['score_breakdown'])
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fe fe-star me-2"></i>면접 평가
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="text-center">
                                    <div class="h2 mb-0 text-{{ $evaluationStats['overall_assessment']['color'] }}">
                                        {{ $evaluationStats['total_score'] }}
                                    </div>
                                    <div class="text-muted">종합 평점 / 5.0</div>
                                </div>
                                <div class="mt-3 text-center">
                                    <span class="badge bg-{{ $evaluationStats['overall_assessment']['color'] }} p-2">
                                        {{ $evaluationStats['overall_assessment']['message'] }}
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row text-center">
                                    @foreach($evaluationStats['score_breakdown'] as $category => $score)
                                        <div class="col-6 mb-2">
                                            <div class="h5 mb-0">{{ $score }}</div>
                                            <div class="text-muted small">{{ $category }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        @if($evaluationStats['strengths'] || $evaluationStats['areas_for_improvement'])
                            <hr>
                            <div class="row">
                                @if($evaluationStats['strengths'])
                                    <div class="col-md-6">
                                        <h6 class="text-success">강점</h6>
                                        <ul class="list-unstyled">
                                            @foreach($evaluationStats['strengths'] as $strength)
                                                <li><i class="fe fe-check text-success me-1"></i>{{ $strength }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if($evaluationStats['areas_for_improvement'])
                                    <div class="col-md-6">
                                        <h6 class="text-warning">개선점</h6>
                                        <ul class="list-unstyled">
                                            @foreach($evaluationStats['areas_for_improvement'] as $area)
                                                <li><i class="fe fe-alert-triangle text-warning me-1"></i>{{ $area }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- 면접 피드백 -->
            @if($interview->interview_feedback)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fe fe-message-square me-2"></i>면접 피드백
                        </h5>
                    </div>
                    <div class="card-body">
                        @foreach($interview->interview_feedback as $key => $value)
                            @if($value)
                                <div class="mb-3">
                                    <strong>{{ ucfirst($key) }}:</strong>
                                    <div class="mt-1 p-2 bg-light rounded">
                                        @if(is_array($value))
                                            <ul class="mb-0">
                                                @foreach($value as $item)
                                                    <li>{{ $item }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            {!! nl2br(e($value)) !!}
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- 면접관 메모 -->
            @if($interview->interviewer_notes)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fe fe-edit-3 me-2"></i>면접관 메모
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="p-2 bg-light rounded">
                            {!! nl2br(e($interview->interviewer_notes)) !!}
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- 사이드바 -->
        <div class="col-md-4">
            <!-- 면접 히스토리 -->
            @if($interviewHistory->count() > 0)
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fe fe-clock me-2"></i>면접 히스토리
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        @foreach($interviewHistory as $history)
                            <div class="p-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-medium">{{ $history->round_label }}</div>
                                        <div class="text-muted small">{{ $history->type_label }}</div>
                                        @if($history->scheduled_at)
                                            <div class="text-muted small">{{ $history->scheduled_at->format('Y-m-d H:i') }}</div>
                                        @endif
                                        @if($history->interviewer)
                                            <div class="text-muted small">면접관: {{ $history->interviewer->name }}</div>
                                        @endif
                                    </div>
                                    <div>
                                        @php
                                            $statusColor = match($history->interview_status) {
                                                'scheduled' => 'warning',
                                                'in_progress' => 'info',
                                                'completed' => 'success',
                                                'cancelled' => 'danger',
                                                'no_show' => 'dark',
                                                default => 'secondary'
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $statusColor }}">{{ $history->status_label }}</span>
                                    </div>
                                </div>
                                @if($history->overall_score)
                                    <div class="mt-2">
                                        <small class="text-muted">평점: {{ $history->overall_score }}/5.0</small>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- 면접 로그 -->
            @if($logs)
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fe fe-activity me-2"></i>면접 로그
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        @foreach($logs as $log)
                            <div class="p-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="fw-medium">{{ $log['action'] }}</div>
                                        @if($log['message'])
                                            <div class="text-muted small">{{ $log['message'] }}</div>
                                        @endif
                                        @if($log['user_name'])
                                            <div class="text-muted small">by {{ $log['user_name'] }}</div>
                                        @endif
                                    </div>
                                    <div class="text-muted small">
                                        {{ \Carbon\Carbon::parse($log['timestamp'])->format('m-d H:i') }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
