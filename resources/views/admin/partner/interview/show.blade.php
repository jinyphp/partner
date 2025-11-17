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
            <!-- 평가 관리 버튼들 -->
            @if ($interview->interview_status === 'completed')
                <!-- 면접 완료 후 평가 보기/수정 -->
                <a href="{{ route('admin.partner.interview.evaluations.show', $interview->id) }}" class="btn btn-success">
                    <i class="fe fe-star me-1"></i>평가 보기
                </a>
                <a href="{{ route('admin.partner.interview.evaluations.edit', $interview->id) }}" class="btn btn-outline-success">
                    <i class="fe fe-edit-2 me-1"></i>평가 수정
                </a>
            @endif

            <!-- 모든 상태에서 평가 작성 가능 -->
            <a href="{{ route('admin.partner.interview.evaluations.create', ['interview_id' => $interview->id]) }}" class="btn btn-warning">
                <i class="fe fe-edit me-1"></i>평가 작성
            </a>

            @if(!in_array($interview->interview_status, ['completed']))
                <a href="{{ route('admin.partner.interview.edit', $interview->id) }}" class="btn btn-primary">
                    <i class="fe fe-edit me-1"></i>수정
                </a>
            @endif

            <button type="button" class="btn btn-danger me-2" onclick="deleteInterview({{ $interview->id }})">
                <i class="fe fe-trash-2 me-1"></i>삭제
            </button>

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
                            <a href="{{ route('admin.partner.approval.show', $interview->application->id) }}" class="btn btn-outline-primary btn-sm">
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

<!-- 삭제 확인 모달 -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">면접 삭제 확인</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning d-flex align-items-center" role="alert">
                    <i class="fe fe-alert-triangle me-2"></i>
                    <div>
                        <strong>주의!</strong> 이 작업은 되돌릴 수 없습니다.
                    </div>
                </div>
                <p>정말로 이 면접을 삭제하시겠습니까?</p>
                <div class="bg-light p-3 rounded">
                    <div class="small">
                        <strong>면접 정보:</strong><br>
                        • 지원자: {{ $interview->name }}<br>
                        • 면접일시: {{ $interview->scheduled_at ? $interview->scheduled_at->format('Y-m-d H:i') : '미정' }}<br>
                        • 상태: {{ $interview->status_label }}
                    </div>
                </div>
                <p class="text-muted small mt-2">
                    삭제된 면접 정보는 복구할 수 없으며, 관련된 평가 데이터에 영향을 줄 수 있습니다.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fe fe-x me-1"></i>취소
                </button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fe fe-trash-2 me-1"></i>삭제 확인
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// 면접 삭제 확인
function deleteInterview(id) {
    console.log('Delete interview button clicked for ID:', id);

    try {
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        const form = document.getElementById('deleteForm');

        // 삭제 URL 설정
        form.action = `{{ route('admin.partner.interview.index') }}/${id}`;

        console.log('Form action set to:', form.action);
        modal.show();
    } catch (error) {
        console.error('Error in deleteInterview:', error);
        alert('삭제 기능에서 오류가 발생했습니다: ' + error.message);
    }
}

// 폼 제출 시 AJAX 처리
document.addEventListener('DOMContentLoaded', function() {
    const deleteForm = document.getElementById('deleteForm');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;

            // 버튼 비활성화 및 로딩 표시
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fe fe-loader me-1"></i>삭제 중...';

            // AJAX 요청
            fetch(this.action, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 성공 알림 표시
                    showToast('success', data.message || '면접이 성공적으로 삭제되었습니다.');

                    // 모달 닫기
                    bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();

                    // 1.5초 후 목록 페이지로 이동
                    setTimeout(() => {
                        window.location.href = data.redirect || '{{ route("admin.partner.interview.index") }}';
                    }, 1500);
                } else {
                    showToast('error', data.message || '삭제 처리 중 오류가 발생했습니다.');

                    // 버튼 상태 복원
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', '네트워크 오류가 발생했습니다.');

                // 버튼 상태 복원
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            });
        });
    }
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
</script>
@endpush
