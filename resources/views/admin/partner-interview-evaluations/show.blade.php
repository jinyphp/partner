@extends('jiny-partner::layouts.admin.sidebar')

@section('title', '면접 평가 상세')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">📝 면접 평가 상세</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="/admin">관리자</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.partner.index') }}">파트너</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.partner.interview.evaluations.index') }}">면접 평가</a></li>
                        <li class="breadcrumb-item active">상세</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- 기본 정보 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">📋 면접 기본 정보</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">지원자</label>
                                <div>{{ $evaluation->applicant_name }}</div>
                                <small class="text-muted">{{ $evaluation->applicant_email }}</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">면접관</label>
                                <div>{{ $evaluation->interviewer_name }}</div>
                                <small class="text-muted">{{ $evaluation->interviewer_email }}</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">지원 포지션</label>
                                <div>{{ $evaluation->position_applied }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">면접 방식</label>
                                <div>
                                    @switch($evaluation->interview_type)
                                        @case('video')
                                            <span class="badge bg-primary fs-6">🎥 화상면접</span>
                                            @break
                                        @case('phone')
                                            <span class="badge bg-info fs-6">📞 전화면접</span>
                                            @break
                                        @case('in_person')
                                            <span class="badge bg-success fs-6">🤝 대면면접</span>
                                            @break
                                        @case('online_test')
                                            <span class="badge bg-warning fs-6">💻 온라인테스트</span>
                                            @break
                                    @endswitch
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">면접 일시</label>
                                <div>{{ date('Y년 m월 d일 H:i', strtotime($evaluation->interview_date)) }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">소요 시간</label>
                                <div>{{ $evaluation->duration_minutes ? $evaluation->duration_minutes . '분' : '미기록' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 평가 점수 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">⭐ 평가 점수</h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        @php
                            $scores = [
                                'technical_skills' => ['기술 역량', 'bg-primary', 25],
                                'communication' => ['의사소통', 'bg-info', 20],
                                'motivation' => ['동기 및 열정', 'bg-success', 15],
                                'experience_relevance' => ['경력 연관성', 'bg-warning', 15],
                                'cultural_fit' => ['조직 적합성', 'bg-secondary', 10],
                                'problem_solving' => ['문제 해결', 'bg-danger', 10],
                                'leadership_potential' => ['리더십 잠재력', 'bg-dark', 5]
                            ];
                        @endphp

                        @foreach($scores as $key => $info)
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold">{{ $info[0] }}</span>
                                    <span class="badge {{ $info[1] }}">{{ $evaluation->$key ?? 0 }}점 (가중치 {{ $info[2] }}%)</span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar {{ $info[1] }}"
                                         style="width: {{ ($evaluation->$key ?? 0) }}%"></div>
                                </div>
                            </div>
                        @endforeach

                        <div class="col-12">
                            <hr>
                            <div class="text-center">
                                <h4 class="mb-2">종합 점수</h4>
                                <h2 class="text-primary">{{ $evaluation->overall_rating ?? 0 }}점</h2>
                                <div class="progress mx-auto" style="width: 300px; height: 20px;">
                                    <div class="progress-bar {{ $evaluation->overall_rating >= 70 ? 'bg-success' : ($evaluation->overall_rating >= 50 ? 'bg-warning' : 'bg-danger') }}"
                                         style="width: {{ $evaluation->overall_rating ?? 0 }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 상세 피드백 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">💬 상세 피드백</h5>
                </div>
                <div class="card-body">
                    @if($evaluation->detailed_feedback)
                        <div class="mb-4">
                            <label class="form-label fw-bold">종합 의견</label>
                            <div class="p-3 bg-light rounded">
                                {!! nl2br(e($evaluation->detailed_feedback)) !!}
                            </div>
                        </div>
                    @endif

                    <div class="row">
                        <!-- 강점 -->
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label fw-bold text-success">💪 강점</label>
                                @if(count($evaluation->strengths) > 0)
                                    <ul class="list-group">
                                        @foreach($evaluation->strengths as $strength)
                                            <li class="list-group-item border-success">
                                                <i class="fas fa-check-circle text-success me-2"></i>{{ $strength }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-muted">기록된 강점이 없습니다.</p>
                                @endif
                            </div>
                        </div>

                        <!-- 약점 -->
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label fw-bold text-warning">⚠️ 약점</label>
                                @if(count($evaluation->weaknesses) > 0)
                                    <ul class="list-group">
                                        @foreach($evaluation->weaknesses as $weakness)
                                            <li class="list-group-item border-warning">
                                                <i class="fas fa-exclamation-triangle text-warning me-2"></i>{{ $weakness }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-muted">기록된 약점이 없습니다.</p>
                                @endif
                            </div>
                        </div>

                        <!-- 우려사항 -->
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label fw-bold text-danger">🚨 우려사항</label>
                                @if(count($evaluation->concerns) > 0)
                                    <ul class="list-group">
                                        @foreach($evaluation->concerns as $concern)
                                            <li class="list-group-item border-danger">
                                                <i class="fas fa-times-circle text-danger me-2"></i>{{ $concern }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-muted">기록된 우려사항이 없습니다.</p>
                                @endif
                            </div>
                        </div>

                        <!-- 개선 액션 아이템 -->
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label fw-bold text-info">🎯 개선 액션 아이템</label>
                                @if(count($evaluation->action_items) > 0)
                                    <ul class="list-group">
                                        @foreach($evaluation->action_items as $action)
                                            <li class="list-group-item border-info">
                                                <i class="fas fa-tasks text-info me-2"></i>{{ $action }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-muted">기록된 액션 아이템이 없습니다.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 면접 노트 -->
            @if(count($evaluation->interview_notes) > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">📝 면접 노트</h5>
                </div>
                <div class="card-body">
                    @foreach($evaluation->interview_notes as $note)
                        <div class="alert alert-light">
                            {{ $note }}
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            <!-- 최종 추천 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">🏆 최종 추천</h5>
                </div>
                <div class="card-body text-center">
                    @switch($evaluation->recommendation)
                        @case('strongly_approve')
                            <div class="mb-3">
                                <i class="fas fa-star fa-3x text-success"></i>
                            </div>
                            <h4 class="text-success">강력 추천</h4>
                            <p class="text-muted">매우 우수한 후보자로 즉시 채용을 권장합니다.</p>
                            @break
                        @case('approve')
                            <div class="mb-3">
                                <i class="fas fa-thumbs-up fa-3x text-primary"></i>
                            </div>
                            <h4 class="text-primary">추천</h4>
                            <p class="text-muted">적합한 후보자로 채용을 권장합니다.</p>
                            @break
                        @case('conditional')
                            <div class="mb-3">
                                <i class="fas fa-clock fa-3x text-warning"></i>
                            </div>
                            <h4 class="text-warning">조건부 승인</h4>
                            <p class="text-muted">추가 검토나 조건부로 채용을 고려할 수 있습니다.</p>
                            @break
                        @case('reject')
                            <div class="mb-3">
                                <i class="fas fa-thumbs-down fa-3x text-danger"></i>
                            </div>
                            <h4 class="text-danger">불합격</h4>
                            <p class="text-muted">현 시점에서는 채용을 권장하지 않습니다.</p>
                            @break
                        @case('strongly_reject')
                            <div class="mb-3">
                                <i class="fas fa-times fa-3x text-dark"></i>
                            </div>
                            <h4 class="text-dark">강력 불합격</h4>
                            <p class="text-muted">채용에 적합하지 않은 후보자입니다.</p>
                            @break
                    @endswitch
                </div>
            </div>

            <!-- 액션 버튼 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">⚡ 액션</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.partner.interview.evaluations.edit', $evaluation->id) }}"
                           class="btn btn-warning">
                            <i class="fas fa-edit"></i> 평가 수정
                        </a>
                        <a href="{{ route('admin.partner.interview.evaluations.index') }}"
                           class="btn btn-secondary">
                            <i class="fas fa-list"></i> 목록으로
                        </a>
                        <button type="button" class="btn btn-danger"
                                onclick="deleteEvaluation({{ $evaluation->id }})">
                            <i class="fas fa-trash"></i> 평가 삭제
                        </button>
                    </div>
                </div>
            </div>

            <!-- 첨부 파일 -->
            @if(count($evaluation->attachments) > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">📎 첨부 파일</h5>
                </div>
                <div class="card-body">
                    @foreach($evaluation->attachments as $attachment)
                        <div class="d-flex justify-content-between align-items-center p-2 border rounded mb-2">
                            <span>{{ $attachment['name'] ?? '첨부파일' }}</span>
                            <a href="{{ $attachment['url'] ?? '#' }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                <i class="fas fa-download"></i>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- 평가 정보 -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">ℹ️ 평가 정보</h5>
                </div>
                <div class="card-body">
                    <div class="small">
                        <div class="mb-2">
                            <strong>평가 ID:</strong> {{ $evaluation->id }}
                        </div>
                        <div class="mb-2">
                            <strong>지원서 ID:</strong> {{ $evaluation->application_id }}
                        </div>
                        <div class="mb-2">
                            <strong>등록일:</strong> {{ date('Y-m-d H:i', strtotime($evaluation->created_at)) }}
                        </div>
                        <div class="mb-2">
                            <strong>수정일:</strong> {{ date('Y-m-d H:i', strtotime($evaluation->updated_at)) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 삭제 확인 모달 -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">평가 삭제 확인</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                정말로 이 평가를 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">삭제</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function deleteEvaluation(id) {
    const deleteForm = document.getElementById('deleteForm');
    deleteForm.action = `/admin/partner/interview/evaluations/${id}`;

    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}
</script>
@endsection