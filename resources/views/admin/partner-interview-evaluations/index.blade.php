@extends('jiny-partner::layouts.admin.sidebar')

@section('title', '면접 평가 관리')

@section('content')
<div class="container-fluid">
    <!-- 헤더 -->
    <section class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">📝 면접 평가 관리</h1>
            <p class="text-muted mb-0">파트너 면접 평가 결과를 관리합니다</p>
        </div>
        <div>
            <a href="{{ route('admin.partner.interview.evaluations.create') }}" class="btn btn-primary">
                <i class="fe fe-plus me-1"></i>새 평가 등록
            </a>
        </div>
    </section>

    <!-- 통계 카드 -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">전체 평가</h5>
                            <h3 class="text-primary">{{ number_format($stats['total']) }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clipboard-list fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">평균 점수</h5>
                            <h3 class="text-success">{{ number_format($stats['average_rating'], 1) }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-star fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">최근 1주</h5>
                            <h3 class="text-info">{{ $stats['recent_count'] }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-calendar-week fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">합격률</h5>
                            @php
                                $passCount = ($stats['by_recommendation']['strongly_approve'] ?? 0) + ($stats['by_recommendation']['approve'] ?? 0);
                                $passRate = $stats['total'] > 0 ? round(($passCount / $stats['total']) * 100, 1) : 0;
                            @endphp
                            <h3 class="text-warning">{{ $passRate }}%</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-trophy fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 필터 및 검색 -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.partner.interview.evaluations.index') }}" class="row g-3">
                        <div class="col-md-2">
                            <label for="recommendation" class="form-label">추천 등급</label>
                            <select name="recommendation" id="recommendation" class="form-select">
                                <option value="">전체</option>
                                <option value="strongly_approve" {{ request('recommendation') == 'strongly_approve' ? 'selected' : '' }}>강력 추천</option>
                                <option value="approve" {{ request('recommendation') == 'approve' ? 'selected' : '' }}>추천</option>
                                <option value="conditional" {{ request('recommendation') == 'conditional' ? 'selected' : '' }}>조건부</option>
                                <option value="reject" {{ request('recommendation') == 'reject' ? 'selected' : '' }}>불합격</option>
                                <option value="strongly_reject" {{ request('recommendation') == 'strongly_reject' ? 'selected' : '' }}>강력 불합격</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="interview_type" class="form-label">면접 방식</label>
                            <select name="interview_type" id="interview_type" class="form-select">
                                <option value="">전체</option>
                                <option value="video" {{ request('interview_type') == 'video' ? 'selected' : '' }}>화상면접</option>
                                <option value="phone" {{ request('interview_type') == 'phone' ? 'selected' : '' }}>전화면접</option>
                                <option value="in_person" {{ request('interview_type') == 'in_person' ? 'selected' : '' }}>대면면접</option>
                                <option value="online_test" {{ request('interview_type') == 'online_test' ? 'selected' : '' }}>온라인테스트</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="overall_rating_min" class="form-label">최소 점수</label>
                            <input type="number" name="overall_rating_min" id="overall_rating_min"
                                   class="form-control" min="0" max="100"
                                   value="{{ request('overall_rating_min') }}" placeholder="0">
                        </div>
                        <div class="col-md-2">
                            <label for="overall_rating_max" class="form-label">최대 점수</label>
                            <input type="number" name="overall_rating_max" id="overall_rating_max"
                                   class="form-control" min="0" max="100"
                                   value="{{ request('overall_rating_max') }}" placeholder="100">
                        </div>
                        <div class="col-md-2">
                            <label for="date_from" class="form-label">시작일</label>
                            <input type="date" name="date_from" id="date_from"
                                   class="form-control" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="date_to" class="form-label">종료일</label>
                            <input type="date" name="date_to" id="date_to"
                                   class="form-control" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-8">
                            <label for="search" class="form-label">검색</label>
                            <input type="text" name="search" id="search"
                                   class="form-control" value="{{ request('search') }}"
                                   placeholder="지원자명, 이메일, 포지션, 면접관명...">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search"></i> 검색
                            </button>
                            <a href="{{ route('admin.partner.interview.evaluations.index') }}" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-refresh"></i> 초기화
                            </a>
                            <a href="{{ route('admin.partner.interview.evaluations.create') }}" class="btn btn-success">
                                <i class="fas fa-plus"></i> 평가 등록
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 평가 목록 -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">면접 평가 목록 ({{ number_format($evaluations->total()) }}건)</h5>
                    <div class="d-flex align-items-center">
                        <div class="dropdown">
                            <button class="btn btn-outline-primary dropdown-toggle btn-sm" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                정렬: {{ request('sort_by') == 'overall_rating' ? '점수순' : '최신순' }}
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['sort_by' => 'interview_date', 'sort_order' => 'desc']) }}">최신순</a></li>
                                <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['sort_by' => 'overall_rating', 'sort_order' => 'desc']) }}">높은 점수순</a></li>
                                <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['sort_by' => 'overall_rating', 'sort_order' => 'asc']) }}">낮은 점수순</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    @if($evaluations->count() > 0)
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>지원자</th>
                                    <th>면접 정보</th>
                                    <th>평가 결과</th>
                                    <th>작업</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($evaluations as $evaluation)
                                    <tr>
                                        <!-- 지원자 정보 -->
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="fw-medium">{{ $evaluation->applicant_display_name ?: $evaluation->applicant_name ?: $evaluation->interview_name ?: '이름 없음' }}</div>
                                                    <div class="text-muted small">{{ $evaluation->applicant_display_email ?: $evaluation->applicant_email ?: $evaluation->interview_email ?: '이메일 없음' }}</div>

                                                    <!-- 면접 일시 -->
                                                    <div class="small">
                                                        <i class="fe fe-calendar me-1 text-muted"></i>
                                                        @php
                                                            $interviewDateTime = $evaluation->scheduled_at ?: $evaluation->interview_date;
                                                        @endphp
                                                        @if($interviewDateTime)
                                                            <span>{{ date('Y-m-d', strtotime($interviewDateTime)) }}</span>
                                                            <span class="text-muted">{{ date('H:i', strtotime($interviewDateTime)) }}</span>
                                                        @else
                                                            <span class="text-muted">일시 미정</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- 면접 정보 통합 -->
                                        <td>
                                            <!-- 포지션 -->
                                            <div class="small">
                                                <i class="fe fe-briefcase me-1 text-muted"></i>
                                                <span>{{ $evaluation->position_applied ?: '포지션 미정' }}</span>
                                            </div>

                                            <!-- 면접관 -->
                                            <div class="small">
                                                <i class="fe fe-user me-1 text-muted"></i>
                                                <span class="fw-medium">{{ $evaluation->interviewer_name ?: '미배정' }}</span>
                                            </div>

                                            <!-- 면접 방식 -->
                                            <div class="small">
                                                <i class="fe fe-video me-1 text-muted"></i>
                                                @switch($evaluation->interview_type)
                                                    @case('video')
                                                        <span class="badge bg-primary">화상면접</span>
                                                        @break
                                                    @case('phone')
                                                        <span class="badge bg-info">전화면접</span>
                                                        @break
                                                    @case('in_person')
                                                        <span class="badge bg-success">대면면접</span>
                                                        @break
                                                    @case('online_test')
                                                        <span class="badge bg-warning">온라인테스트</span>
                                                        @break
                                                    @default
                                                        <span class="text-muted">방식 미정</span>
                                                @endswitch
                                            </div>

                                            @if($evaluation->duration_minutes)
                                                <div class="small text-muted">
                                                    <i class="fe fe-clock me-1"></i>{{ $evaluation->duration_minutes }}분
                                                </div>
                                            @endif
                                        </td>

                                        <!-- 평가 결과 통합 -->
                                        <td>
                                            <!-- 종합 점수 -->
                                            <div class="mb-2">
                                                <div class="d-flex align-items-center">
                                                    <i class="fe fe-star me-1 text-muted"></i>
                                                    @if($evaluation->overall_rating)
                                                        <span class="fw-medium fs-6">{{ $evaluation->overall_rating }}</span>
                                                        <span class="text-muted small">/100</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </div>
                                                @if($evaluation->overall_rating)
                                                    <div class="progress mt-1" style="height: 4px;">
                                                        <div class="progress-bar {{ $evaluation->overall_rating >= 70 ? 'bg-success' : ($evaluation->overall_rating >= 50 ? 'bg-warning' : 'bg-danger') }}"
                                                             style="width: {{ $evaluation->overall_rating }}%"></div>
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- 추천 등급 -->
                                            <div>
                                                @switch($evaluation->recommendation)
                                                    @case('strongly_approve')
                                                        <span class="badge bg-success">강력 추천</span>
                                                        @break
                                                    @case('approve')
                                                        <span class="badge bg-primary">추천</span>
                                                        @break
                                                    @case('conditional')
                                                        <span class="badge bg-warning">조건부</span>
                                                        @break
                                                    @case('reject')
                                                        <span class="badge bg-danger">불합격</span>
                                                        @break
                                                    @case('strongly_reject')
                                                        <span class="badge bg-dark">강력 불합격</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-secondary">미정</span>
                                                @endswitch
                                            </div>
                                        </td>

                                        <!-- 작업 -->
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('admin.partner.interview.evaluations.show', $evaluation->id) }}"
                                                   class="btn btn-outline-info" title="상세보기">
                                                    <i class="fe fe-eye me-1"></i>보기
                                                </a>
                                                <a href="{{ route('admin.partner.interview.evaluations.edit', $evaluation->id) }}"
                                                   class="btn btn-outline-warning" title="수정">
                                                    <i class="fe fe-edit me-1"></i>수정
                                                </a>
                                                <button type="button" class="btn btn-outline-danger"
                                                        onclick="deleteEvaluation({{ $evaluation->id }})" title="삭제">
                                                    <i class="fe fe-trash me-1"></i>삭제
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="text-center py-5">
                            <i class="fe fe-clipboard text-muted mb-3 d-block" style="font-size: 3rem;"></i>
                            <p class="text-muted">등록된 면접 평가가 없습니다.</p>
                            <a href="{{ route('admin.partner.interview.evaluations.create') }}" class="btn btn-primary">
                                <i class="fe fe-plus me-1"></i>첫 번째 평가 등록
                            </a>
                        </div>
                    @endif
                </div>
                @if($evaluations->hasPages())
                    <div class="card-footer">
                        {{ $evaluations->appends(request()->query())->links() }}
                    </div>
                @endif
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