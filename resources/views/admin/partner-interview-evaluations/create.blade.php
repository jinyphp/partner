@extends('jiny-partner::layouts.admin.sidebar')

@section('title', '면접 평가 등록')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                @if(isset($interview) && $interview)
                    <h4 class="page-title">🎤 면접 평가 등록 - {{ $interview->name }}</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="/admin">관리자</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.partner.index') }}">파트너</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.partner.interview.index') }}">면접 관리</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.partner.interview.show', $interview->id) }}">면접 상세</a></li>
                            <li class="breadcrumb-item active">평가 등록</li>
                        </ol>
                    </div>
                @else
                    <h4 class="page-title">📝 면접 평가 등록</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="/admin">관리자</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.partner.index') }}">파트너</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.partner.interview.evaluations.index') }}">면접 평가</a></li>
                            <li class="breadcrumb-item active">등록</li>
                        </ol>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <form action="{{ route('admin.partner.interview.evaluations.store') }}" method="POST" id="evaluationForm">
        @csrf

        {{-- interview_id가 있는 경우 hidden field로 전달 --}}
        @if(isset($interview) && $interview)
            <input type="hidden" name="interview_id" value="{{ $interview->id }}">
        @endif

        <div class="row">
            <div class="col-lg-8">
                {{-- 면접 정보 섹션 (interview가 있는 경우) --}}
                @if(isset($interview) && $interview)
                    <div class="card mb-4 border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">🎤 면접 정보</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>지원자:</strong><br>
                                    <span class="text-primary">{{ $interview->name }}</span><br>
                                    <small class="text-muted">{{ $interview->email }}</small>
                                </div>
                                <div class="col-md-4">
                                    <strong>면접 일시:</strong><br>
                                    {{ $interview->scheduled_at ? \Carbon\Carbon::parse($interview->scheduled_at)->format('Y-m-d H:i') : '미정' }}
                                </div>
                                <div class="col-md-4">
                                    <strong>면접 유형:</strong><br>
                                    @switch($interview->interview_type)
                                        @case('video') 화상면접 @break
                                        @case('phone') 전화면접 @break
                                        @case('in_person') 대면면접 @break
                                        @case('online_test') 온라인테스트 @break
                                        @default {{ $interview->interview_type }}
                                    @endswitch
                                </div>
                            </div>
                            @if($interview->interviewer_name)
                                <div class="row mt-2">
                                    <div class="col-12">
                                        <strong>면접관:</strong> {{ $interview->interviewer_name }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- 기본 정보 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">📋 기본 정보</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="application_id" class="form-label">지원서 <span class="text-danger">*</span></label>
                                    @if(isset($interview) && $interview)
                                        {{-- 면접에서 온 경우 지원서 고정 --}}
                                        <input type="hidden" name="application_id" value="{{ $interview->application_id }}">
                                        <input type="text" class="form-control" readonly
                                               value="{{ $interview->name }} - 파트너 신청 ({{ $interview->email }})">
                                    @else
                                        {{-- 일반 평가 등록 --}}
                                        <select name="application_id" id="application_id" class="form-select" required>
                                            <option value="">지원서를 선택하세요</option>
                                            @foreach($applications as $app)
                                                <option value="{{ $app->id }}"
                                                        {{ (request('application_id') == $app->id || (isset($application) && $application->id == $app->id)) ? 'selected' : '' }}>
                                                    {{ $app->applicant_name ?? '이름 없음' }} - {{ $app->position_applied }} ({{ $app->email }})
                                                </option>
                                            @endforeach
                                        </select>
                                    @endif
                                    @error('application_id')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="interview_type" class="form-label">면접 방식 <span class="text-danger">*</span></label>
                                    @php
                                        $selectedType = old('interview_type', isset($interview) ? $interview->interview_type : '');
                                    @endphp
                                    <select name="interview_type" id="interview_type" class="form-select" required>
                                        <option value="">선택</option>
                                        <option value="video" {{ $selectedType == 'video' ? 'selected' : '' }}>화상면접</option>
                                        <option value="phone" {{ $selectedType == 'phone' ? 'selected' : '' }}>전화면접</option>
                                        <option value="in_person" {{ $selectedType == 'in_person' ? 'selected' : '' }}>대면면접</option>
                                        <option value="online_test" {{ $selectedType == 'online_test' ? 'selected' : '' }}>온라인테스트</option>
                                    </select>
                                    @error('interview_type')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="interview_date" class="form-label">면접 일시 <span class="text-danger">*</span></label>
                                    @php
                                        $selectedDate = old('interview_date');
                                        if (!$selectedDate && isset($interview) && $interview->scheduled_at) {
                                            $selectedDate = \Carbon\Carbon::parse($interview->scheduled_at)->format('Y-m-d\TH:i');
                                        }
                                    @endphp
                                    <input type="datetime-local" name="interview_date" id="interview_date"
                                           class="form-control" value="{{ $selectedDate }}" required>
                                    @error('interview_date')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="duration_minutes" class="form-label">소요 시간 (분)</label>
                                    @php
                                        $selectedDuration = old('duration_minutes');
                                        if (!$selectedDuration && isset($interview)) {
                                            $selectedDuration = $interview->duration_minutes ?? $interview->interview_duration ?? '';
                                        }
                                    @endphp
                                    <input type="number" name="duration_minutes" id="duration_minutes"
                                           class="form-control" min="1" max="480" value="{{ $selectedDuration }}"
                                           placeholder="예: 60">
                                    @error('duration_minutes')
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
                                                           value="{{ old($key, 0) }}" data-target="{{ $key }}_display">
                                                </div>
                                                <div class="col-4">
                                                    <input type="number" class="form-control score-input"
                                                           id="{{ $key }}_display" min="0" max="100"
                                                           value="{{ old($key, 0) }}" data-target="{{ $key }}">
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
                            <h2 class="text-primary" id="overallScore">0</h2>
                            <div class="progress mx-auto" style="width: 300px; height: 20px;">
                                <div class="progress-bar" id="overallProgressBar" style="width: 0%"></div>
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
                                      placeholder="면접에 대한 전반적인 의견을 작성해주세요...">{{ old('detailed_feedback') }}</textarea>
                            @error('detailed_feedback')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-success">💪 강점</label>
                                    <div id="strengths-container">
                                        @if(old('strengths'))
                                            @foreach(old('strengths') as $index => $strength)
                                                <div class="input-group mb-2">
                                                    <input type="text" name="strengths[]" class="form-control" value="{{ $strength }}"
                                                           placeholder="지원자의 강점을 입력하세요">
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
                                        @if(old('weaknesses'))
                                            @foreach(old('weaknesses') as $index => $weakness)
                                                <div class="input-group mb-2">
                                                    <input type="text" name="weaknesses[]" class="form-control" value="{{ $weakness }}"
                                                           placeholder="개선이 필요한 부분을 입력하세요">
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
                                        @if(old('concerns'))
                                            @foreach(old('concerns') as $index => $concern)
                                                <div class="input-group mb-2">
                                                    <input type="text" name="concerns[]" class="form-control" value="{{ $concern }}"
                                                           placeholder="우려되는 사항을 입력하세요">
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
                                        @if(old('action_items'))
                                            @foreach(old('action_items') as $index => $action)
                                                <div class="input-group mb-2">
                                                    <input type="text" name="action_items[]" class="form-control" value="{{ $action }}"
                                                           placeholder="개선을 위한 행동 계획을 입력하세요">
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
                                           {{ old('recommendation') == $key ? 'checked' : '' }} required>
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
                                <i class="fas fa-save"></i> 평가 저장
                            </button>
                            @if(isset($interview) && $interview)
                                <a href="{{ route('admin.partner.interview.show', $interview->id) }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> 면접 상세로 돌아가기
                                </a>
                            @else
                                <a href="{{ route('admin.partner.interview.evaluations.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> 취소
                                </a>
                            @endif
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
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // 기본 폼 제출 방지

            // 필수 필드 체크
            const requiredFields = {
                'application_id': document.querySelector('input[name="application_id"]')?.value,
                'interview_date': document.querySelector('input[name="interview_date"]')?.value,
                'interview_type': document.querySelector('select[name="interview_type"]')?.value,
                'recommendation': document.querySelector('input[name="recommendation"]:checked')?.value
            };

            // 필수 필드 누락 체크
            const missingFields = [];
            Object.keys(requiredFields).forEach(field => {
                if (!requiredFields[field]) {
                    missingFields.push(field);
                }
            });

            if (missingFields.length > 0) {
                showToast('error', '다음 필드를 채워주세요: ' + missingFields.map(field => {
                    const labels = {
                        'application_id': '지원서',
                        'interview_date': '면접 일시',
                        'interview_type': '면접 방식',
                        'recommendation': '최종 추천'
                    };
                    return labels[field] || field;
                }).join(', '));
                return false;
            }

            // 제출 버튼 상태 변경
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>저장 중...';

            // FormData 생성
            const formData = new FormData(form);

            // CSRF 토큰 자동 포함됨

            // 배열 필드들이 비어있는 경우 처리
            const arrayFields = ['strengths', 'weaknesses', 'concerns', 'action_items'];
            arrayFields.forEach(field => {
                const inputs = form.querySelectorAll(`input[name="${field}[]"]`);
                let hasValue = false;
                inputs.forEach(input => {
                    if (input.value.trim()) {
                        hasValue = true;
                    }
                });
                if (!hasValue) {
                    // 빈 배열인 경우 필드 제거
                    formData.delete(`${field}[]`);
                }
            });


            // AJAX 요청
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    if (response.status === 422) {
                        // Validation 오류 처리
                        return response.json();
                    }
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // 성공 메시지 표시
                    showToast('success', data.message || '면접 평가가 성공적으로 저장되었습니다.');

                    // 잠시 후 페이지 이동
                    setTimeout(() => {
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        } else {
                            // 기본 리다이렉트 - 평가 목록 페이지
                            window.location.href = '{{ route("admin.partner.interview.evaluations.index") }}';
                        }
                    }, 1000);
                } else {
                    // 오류 메시지 표시
                    let errorMessage = data.message || '면접 평가 저장 중 오류가 발생했습니다.';

                    // Validation 오류가 있는 경우 상세 메시지 추가
                    if (data.errors) {
                        const errorList = Object.values(data.errors).flat();
                        if (errorList.length > 0) {
                            errorMessage = errorList.join('\n');
                        }
                    }

                    showToast('error', errorMessage);

                    // 버튼 상태 복원
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            })
            .catch(error => {
                console.error('AJAX Error:', error);
                showToast('error', '서버 오류가 발생했습니다. 잠시 후 다시 시도해주세요.');

                // 버튼 상태 복원
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
        });
    }
});

// Toast 메시지 표시 함수
function showToast(type, message) {
    // Bootstrap toast가 없는 경우 alert으로 대체
    if (typeof bootstrap === 'undefined' || !bootstrap.Toast) {
        alert(message);
        return;
    }

    // Toast 컨테이너가 없으면 생성
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }

    // Toast 엘리먼트 생성
    const toastId = 'toast-' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast" role="alert">
            <div class="toast-header">
                <i class="fas fa-${type === 'success' ? 'check-circle text-success' : 'exclamation-circle text-danger'} me-2"></i>
                <strong class="me-auto">${type === 'success' ? '성공' : '오류'}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;

    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement);
    toast.show();

    // 토스트가 사라진 후 엘리먼트 제거
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}</script>

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