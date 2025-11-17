@extends('jiny-partner::layouts.admin.sidebar')

@section('title', '면접 평가 수정')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">📝 면접 평가 수정</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="/admin">관리자</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.partner.index') }}">파트너</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.partner.interview.evaluations.index') }}">면접 평가</a></li>
                        <li class="breadcrumb-item active">수정</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.partner.interview.evaluations.update', $evaluation->id) }}" method="POST" id="evaluationForm">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-8">
                <!-- 지원자 정보 표시 -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">👤 지원자 정보</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <strong>지원자:</strong> {{ $evaluation->applicant_display_name ?: $evaluation->applicant_name ?: $evaluation->interview_name ?: '이름 없음' }}
                            </div>
                            <div class="col-md-4">
                                <strong>이메일:</strong> {{ $evaluation->applicant_display_email ?: $evaluation->applicant_email ?: $evaluation->interview_email ?: '이메일 없음' }}
                            </div>
                            <div class="col-md-4">
                                <strong>포지션:</strong> {{ $evaluation->position_applied ?: '포지션 미정' }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 기본 정보 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">📋 면접 정보</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="interview_type" class="form-label">면접 방식 <span class="text-danger">*</span></label>
                                    <select name="interview_type" id="interview_type" class="form-select" required>
                                        <option value="">선택</option>
                                        <option value="video" {{ old('interview_type', $evaluation->interview_type) == 'video' ? 'selected' : '' }}>화상면접</option>
                                        <option value="phone" {{ old('interview_type', $evaluation->interview_type) == 'phone' ? 'selected' : '' }}>전화면접</option>
                                        <option value="in_person" {{ old('interview_type', $evaluation->interview_type) == 'in_person' ? 'selected' : '' }}>대면면접</option>
                                        <option value="online_test" {{ old('interview_type', $evaluation->interview_type) == 'online_test' ? 'selected' : '' }}>온라인테스트</option>
                                    </select>
                                    @error('interview_type')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="duration_minutes" class="form-label">소요 시간 (분)</label>
                                    <input type="number" name="duration_minutes" id="duration_minutes"
                                           class="form-control" min="1" max="480"
                                           value="{{ old('duration_minutes', $evaluation->duration_minutes) }}"
                                           placeholder="예: 60">
                                    @error('duration_minutes')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="interview_date" class="form-label">면접 일시 <span class="text-danger">*</span></label>
                                    <input type="datetime-local" name="interview_date" id="interview_date"
                                           class="form-control"
                                           value="{{ old('interview_date', date('Y-m-d\TH:i', strtotime($evaluation->interview_date))) }}"
                                           required>
                                    @error('interview_date')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 평가 점수 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">⭐ 평가 점수 (1-100점)</h5>
                        <small class="text-muted">각 영역별로 1점부터 100점까지 평가해주세요.</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            @php
                                $scores = [
                                    'technical_skills' => ['기술 역량', '전문 지식과 기술적 이해도', 'bg-primary', 25],
                                    'communication' => ['의사소통', '명확한 표현력과 이해력', 'bg-info', 20],
                                    'motivation' => ['동기 및 열정', '업무에 대한 열정과 의지', 'bg-success', 15],
                                    'experience_relevance' => ['경력 연관성', '관련 업무 경험과 활용도', 'bg-warning', 15],
                                    'cultural_fit' => ['조직 적합성', '회사 문화와의 조화', 'bg-secondary', 10],
                                    'problem_solving' => ['문제 해결', '창의적 사고와 해결 능력', 'bg-danger', 10],
                                    'leadership_potential' => ['리더십 잠재력', '팀을 이끄는 능력과 가능성', 'bg-dark', 5]
                                ];
                            @endphp

                            @foreach($scores as $key => $info)
                                <div class="col-md-6">
                                    <div class="card border-{{ str_replace('bg-', '', $info[2]) }}">
                                        <div class="card-header {{ $info[2] }} text-white py-2">
                                            <div class="d-flex justify-content-between">
                                                <strong>{{ $info[0] }}</strong>
                                                <span class="badge bg-light text-dark">가중치 {{ $info[3] }}%</span>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <p class="small text-muted mb-2">{{ $info[1] }}</p>
                                            <div class="row align-items-center">
                                                <div class="col-8">
                                                    <input type="range" name="{{ $key }}" id="{{ $key }}"
                                                           class="form-range score-slider" min="0" max="100"
                                                           value="{{ old($key, $evaluation->$key ?? 0) }}"
                                                           data-target="{{ $key }}_display">
                                                </div>
                                                <div class="col-4">
                                                    <input type="number" class="form-control score-input"
                                                           id="{{ $key }}_display" min="0" max="100"
                                                           value="{{ old($key, $evaluation->$key ?? 0) }}"
                                                           data-target="{{ $key }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @error($key)
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endforeach
                        </div>

                        <hr class="my-4">

                        <!-- 종합 점수 표시 -->
                        <div class="text-center">
                            <h5>예상 종합 점수</h5>
                            <h2 class="text-primary" id="overallScore">{{ $evaluation->overall_rating ?? 0 }}</h2>
                            <div class="progress mx-auto" style="width: 300px; height: 20px;">
                                <div class="progress-bar" id="overallProgressBar"
                                     style="width: {{ $evaluation->overall_rating ?? 0 }}%"></div>
                            </div>
                            <small class="text-muted mt-2 d-block">가중 평균으로 자동 계산됩니다</small>
                        </div>
                    </div>
                </div>

                <!-- 피드백 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">💬 상세 피드백</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <label for="detailed_feedback" class="form-label">종합 의견</label>
                            <textarea name="detailed_feedback" id="detailed_feedback" class="form-control" rows="4"
                                      placeholder="면접에 대한 전반적인 의견을 작성해주세요...">{{ old('detailed_feedback', $evaluation->detailed_feedback) }}</textarea>
                            @error('detailed_feedback')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-success">💪 강점</label>
                                    <div id="strengths-container">
                                        @if(old('strengths') || count($evaluation->strengths) > 0)
                                            @foreach(old('strengths', $evaluation->strengths) as $index => $strength)
                                                <div class="input-group mb-2">
                                                    <input type="text" name="strengths[]" class="form-control"
                                                           value="{{ $strength }}" placeholder="지원자의 강점을 입력하세요">
                                                    <button type="button" class="btn btn-outline-danger" onclick="removeItem(this)">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="input-group mb-2">
                                                <input type="text" name="strengths[]" class="form-control"
                                                       placeholder="지원자의 강점을 입력하세요">
                                                <button type="button" class="btn btn-outline-danger" onclick="removeItem(this)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                    <button type="button" class="btn btn-outline-success btn-sm" onclick="addStrength()">
                                        <i class="fas fa-plus"></i> 강점 추가
                                    </button>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-warning">⚠️ 약점</label>
                                    <div id="weaknesses-container">
                                        @if(old('weaknesses') || count($evaluation->weaknesses) > 0)
                                            @foreach(old('weaknesses', $evaluation->weaknesses) as $index => $weakness)
                                                <div class="input-group mb-2">
                                                    <input type="text" name="weaknesses[]" class="form-control"
                                                           value="{{ $weakness }}" placeholder="개선이 필요한 부분을 입력하세요">
                                                    <button type="button" class="btn btn-outline-danger" onclick="removeItem(this)">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="input-group mb-2">
                                                <input type="text" name="weaknesses[]" class="form-control"
                                                       placeholder="개선이 필요한 부분을 입력하세요">
                                                <button type="button" class="btn btn-outline-danger" onclick="removeItem(this)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                    <button type="button" class="btn btn-outline-warning btn-sm" onclick="addWeakness()">
                                        <i class="fas fa-plus"></i> 약점 추가
                                    </button>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-danger">🚨 우려사항</label>
                                    <div id="concerns-container">
                                        @if(old('concerns') || count($evaluation->concerns) > 0)
                                            @foreach(old('concerns', $evaluation->concerns) as $index => $concern)
                                                <div class="input-group mb-2">
                                                    <input type="text" name="concerns[]" class="form-control"
                                                           value="{{ $concern }}" placeholder="우려되는 사항을 입력하세요">
                                                    <button type="button" class="btn btn-outline-danger" onclick="removeItem(this)">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="input-group mb-2">
                                                <input type="text" name="concerns[]" class="form-control"
                                                       placeholder="우려되는 사항을 입력하세요">
                                                <button type="button" class="btn btn-outline-danger" onclick="removeItem(this)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="addConcern()">
                                        <i class="fas fa-plus"></i> 우려사항 추가
                                    </button>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-info">🎯 개선 액션 아이템</label>
                                    <div id="action-items-container">
                                        @if(old('action_items') || count($evaluation->action_items) > 0)
                                            @foreach(old('action_items', $evaluation->action_items) as $index => $action)
                                                <div class="input-group mb-2">
                                                    <input type="text" name="action_items[]" class="form-control"
                                                           value="{{ $action }}" placeholder="개선을 위한 행동 계획을 입력하세요">
                                                    <button type="button" class="btn btn-outline-danger" onclick="removeItem(this)">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="input-group mb-2">
                                                <input type="text" name="action_items[]" class="form-control"
                                                       placeholder="개선을 위한 행동 계획을 입력하세요">
                                                <button type="button" class="btn btn-outline-danger" onclick="removeItem(this)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                    <button type="button" class="btn btn-outline-info btn-sm" onclick="addActionItem()">
                                        <i class="fas fa-plus"></i> 액션 아이템 추가
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- 최종 추천 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">🏆 최종 추천 <span class="text-danger">*</span></h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            @php
                                $recommendations = [
                                    'strongly_approve' => ['강력 추천', 'success', 'star'],
                                    'approve' => ['추천', 'primary', 'thumbs-up'],
                                    'conditional' => ['조건부', 'warning', 'clock'],
                                    'reject' => ['불합격', 'danger', 'thumbs-down'],
                                    'strongly_reject' => ['강력 불합격', 'dark', 'times']
                                ];
                            @endphp

                            @foreach($recommendations as $key => $info)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="recommendation"
                                           id="recommendation_{{ $key }}" value="{{ $key }}"
                                           {{ old('recommendation', $evaluation->recommendation) == $key ? 'checked' : '' }} required>
                                    <label class="form-check-label d-flex align-items-center" for="recommendation_{{ $key }}">
                                        <i class="fas fa-{{ $info[2] }} text-{{ $info[1] }} me-2"></i>
                                        <span class="fw-bold text-{{ $info[1] }}">{{ $info[0] }}</span>
                                    </label>
                                </div>
                            @endforeach

                            @error('recommendation')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- 액션 버튼 -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> 평가 수정
                            </button>
                            <a href="{{ route('admin.partner.interview.evaluations.show', $evaluation->id) }}" class="btn btn-info">
                                <i class="fas fa-eye"></i> 상세 보기
                            </a>
                            <a href="{{ route('admin.partner.interview.evaluations.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> 취소
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 점수 슬라이더와 입력 필드 동기화
    const scoreSliders = document.querySelectorAll('.score-slider');
    const scoreInputs = document.querySelectorAll('.score-input');

    scoreSliders.forEach(slider => {
        slider.addEventListener('input', function() {
            const target = document.getElementById(this.dataset.target);
            target.value = this.value;
            calculateOverallScore();
        });
    });

    scoreInputs.forEach(input => {
        input.addEventListener('input', function() {
            const target = document.getElementById(this.dataset.target);
            target.value = this.value;
            calculateOverallScore();
        });
    });

    // 종합 점수 계산
    function calculateOverallScore() {
        const weights = {
            'technical_skills': 0.25,
            'communication': 0.20,
            'motivation': 0.15,
            'experience_relevance': 0.15,
            'cultural_fit': 0.10,
            'problem_solving': 0.10,
            'leadership_potential': 0.05
        };

        let totalScore = 0;
        let totalWeight = 0;

        for (const [skill, weight] of Object.entries(weights)) {
            const input = document.getElementById(skill);
            const score = parseInt(input.value) || 0;
            if (score > 0) {
                totalScore += score * weight;
                totalWeight += weight;
            }
        }

        const overallScore = totalWeight > 0 ? Math.round(totalScore / totalWeight) : 0;

        document.getElementById('overallScore').textContent = overallScore;
        const progressBar = document.getElementById('overallProgressBar');
        progressBar.style.width = overallScore + '%';

        // 점수에 따른 색상 변경
        progressBar.className = 'progress-bar';
        if (overallScore >= 70) {
            progressBar.classList.add('bg-success');
        } else if (overallScore >= 50) {
            progressBar.classList.add('bg-warning');
        } else {
            progressBar.classList.add('bg-danger');
        }
    }

    // 초기 계산
    calculateOverallScore();

    // AJAX 폼 제출 처리
    const form = document.getElementById('evaluationForm');
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton.innerHTML;

        // 로딩 상태 표시
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 저장 중...';

        // 폼 데이터 수집
        const formData = new FormData(form);

        // AJAX 요청
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(async response => {
            const data = await response.json();

            if (response.ok && data.success) {
                // 성공 메시지 표시
                showAlert('success', data.message);

                // 1초 후 목록 페이지로 이동
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1000);
            } else {
                // 오류 처리
                if (data.errors) {
                    // 유효성 검사 오류 표시
                    displayValidationErrors(data.errors);
                } else {
                    showAlert('danger', data.message || '평가 수정 중 오류가 발생했습니다.');
                }

                // 버튼 상태 복원
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', '네트워크 오류가 발생했습니다.');

            // 버튼 상태 복원
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        });
    });
});

// 알림 메시지 표시 함수
function showAlert(type, message) {
    // 기존 알림 제거
    const existingAlert = document.querySelector('.alert-ajax');
    if (existingAlert) {
        existingAlert.remove();
    }

    // 새 알림 생성
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-ajax alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    // 폼 상단에 알림 추가
    const form = document.getElementById('evaluationForm');
    form.insertBefore(alert, form.firstChild);
}

// 유효성 검사 오류 표시 함수
function displayValidationErrors(errors) {
    // 기존 오류 메시지 제거
    document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
    document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

    // 각 필드별 오류 표시
    for (const [field, messages] of Object.entries(errors)) {
        const input = document.querySelector(`[name="${field}"]`);
        if (input) {
            input.classList.add('is-invalid');

            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            feedback.textContent = Array.isArray(messages) ? messages[0] : messages;

            input.parentNode.appendChild(feedback);
        }
    }

    showAlert('danger', '입력한 정보를 확인해주세요.');
}

// 동적 입력 필드 추가/제거 함수들
function addStrength() {
    const container = document.getElementById('strengths-container');
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `
        <input type="text" name="strengths[]" class="form-control" placeholder="지원자의 강점을 입력하세요">
        <button type="button" class="btn btn-outline-danger" onclick="removeItem(this)">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(div);
}

function addWeakness() {
    const container = document.getElementById('weaknesses-container');
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `
        <input type="text" name="weaknesses[]" class="form-control" placeholder="개선이 필요한 부분을 입력하세요">
        <button type="button" class="btn btn-outline-danger" onclick="removeItem(this)">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(div);
}

function addConcern() {
    const container = document.getElementById('concerns-container');
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `
        <input type="text" name="concerns[]" class="form-control" placeholder="우려되는 사항을 입력하세요">
        <button type="button" class="btn btn-outline-danger" onclick="removeItem(this)">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(div);
}

function addActionItem() {
    const container = document.getElementById('action-items-container');
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `
        <input type="text" name="action_items[]" class="form-control" placeholder="개선을 위한 행동 계획을 입력하세요">
        <button type="button" class="btn btn-outline-danger" onclick="removeItem(this)">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(div);
}

function removeItem(button) {
    button.parentElement.remove();
}
</script>
@endsection