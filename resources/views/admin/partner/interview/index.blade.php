@extends('jiny-partner::layouts.admin.sidebar')

@section('title', '파트너 면접 관리')

@section('content')
    <div class="container-fluid">

        <!-- 헤더 -->
        <section class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">파트너 면접 관리</h1>
                <p class="text-muted mb-0">파트너 지원자 면접 일정 및 결과를 관리합니다</p>
            </div>
            <div>
                <a href="{{ route('admin.partner.interview.create') }}" class="btn btn-primary">
                    <i class="fe fe-plus me-1"></i>새 면접 예약
                </a>
            </div>
        </section>

        <!-- 통계 카드 -->
        <section class="row mb-4">
            <div class="col-md-2 col-6">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="h2 mb-0 text-primary">{{ number_format($statistics['counts']['total']) }}</div>
                        <div class="text-muted small">전체 면접</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="h2 mb-0 text-warning">{{ number_format($statistics['counts']['scheduled']) }}</div>
                        <div class="text-muted small">예정</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="h2 mb-0 text-info">{{ number_format($statistics['counts']['in_progress']) }}</div>
                        <div class="text-muted small">진행중</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="h2 mb-0 text-success">{{ number_format($statistics['counts']['completed']) }}</div>
                        <div class="text-muted small">완료</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="h2 mb-0 text-success">{{ $statistics['pass_rate'] }}%</div>
                        <div class="text-muted small">통과율</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="h2 mb-0 text-secondary">{{ $statistics['avg_score'] }}</div>
                        <div class="text-muted small">평균평점</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 필터 -->
        <section class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">검색 및 필터</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.partner.interview.index') }}">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">상태</label>
                            <select name="status" class="form-select">
                                @foreach ($filterOptions['statuses'] as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ $currentFilters['status'] === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">결과</label>
                            <select name="result" class="form-select">
                                @foreach ($filterOptions['results'] as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ $currentFilters['result'] === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">면접 유형</label>
                            <select name="type" class="form-select">
                                @foreach ($filterOptions['types'] as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ $currentFilters['type'] === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">날짜 범위</label>
                            <select name="date_range" class="form-select">
                                @foreach ($filterOptions['date_ranges'] as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ $currentFilters['date_range'] === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">면접관</label>
                            <select name="interviewer" class="form-select">
                                <option value="all">전체</option>
                                @foreach ($interviewers as $interviewer)
                                    <option value="{{ $interviewer->id }}"
                                        {{ $currentFilters['interviewer'] == $interviewer->id ? 'selected' : '' }}>
                                        {{ $interviewer->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">검색</label>
                            <input type="text" name="search" class="form-control" placeholder="이름, 이메일, 추천 코드 검색..."
                                value="{{ $currentFilters['search'] }}">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">검색</button>
                            <a href="{{ route('admin.partner.interview.index') }}" class="btn btn-secondary">초기화</a>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <div class="row">
            <!-- 사이드바 -->
            <div class="col-md-4">
                <!-- 오늘의 면접 일정 -->
                @if ($todayInterviews->count() > 0)
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fe fe-calendar me-2"></i>오늘의 면접 일정 ({{ $todayInterviews->count() }}건)
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            @foreach ($todayInterviews as $interview)
                                <div class="d-flex align-items-center p-3 border-bottom">
                                    <div class="flex-grow-1">
                                        <div class="fw-medium">{{ $interview->name }}</div>
                                        <div class="text-muted small">
                                            {{ $interview->scheduled_at->format('H:i') }} -
                                            {{ $interview->type_label }}
                                        </div>
                                        @if ($interview->interviewer)
                                            <div class="text-muted small">면접관: {{ $interview->interviewer->name }}</div>
                                        @endif
                                    </div>
                                    <div>
                                        <a href="{{ route('admin.partner.interview.show', $interview->id) }}"
                                            class="btn btn-sm btn-outline-primary">보기</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- 월간 통계 -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fe fe-bar-chart-2 me-2"></i>이번 달 통계
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="h5 mb-0 text-primary">{{ $statistics['this_month']['scheduled'] }}</div>
                                <div class="text-muted small">예정</div>
                            </div>
                            <div class="col-4">
                                <div class="h5 mb-0 text-success">{{ $statistics['this_month']['completed'] }}</div>
                                <div class="text-muted small">완료</div>
                            </div>
                            <div class="col-4">
                                <div class="h5 mb-0 text-success">{{ $statistics['this_month']['passed'] }}</div>
                                <div class="text-muted small">통과</div>
                            </div>
                        </div>
                        <hr>
                        <div class="small text-muted">
                            <div class="d-flex justify-content-between mb-1">
                                <span>평균 면접시간</span>
                                <span>{{ $statistics['avg_duration_minutes'] }}분</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>평균 평점</span>
                                <span>{{ $statistics['avg_score'] }}/5.0</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 메인 콘텐츠 -->
            <div class="col-md-8">
                <!-- 면접 목록 -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">면접 목록 ({{ number_format($interviews->total()) }}건)</h5>
                        <div class="d-flex align-items-center">
                            <select name="per_page"
                                onchange="location.href='{{ request()->fullUrlWithQuery(['per_page' => '']) }}'+(this.value)"
                                class="form-select form-select-sm me-2" style="width: auto;">
                                @foreach ($filterOptions['per_page_options'] as $option)
                                    <option value="{{ $option }}"
                                        {{ $currentFilters['per_page'] == $option ? 'selected' : '' }}>
                                        {{ $option }}개씩
                                    </option>
                                @endforeach
                            </select>
                            <select name="sort_by"
                                onchange="location.href='{{ request()->fullUrlWithQuery(['sort_by' => '', 'sort_direction' => $currentFilters['sort_direction']]) }}'+(this.value)"
                                class="form-select form-select-sm" style="width: auto;">
                                @foreach ($filterOptions['sort_options'] as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ $currentFilters['sort_by'] === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>지원자</th>
                                    <th>면접 정보</th>
                                    <th>상태 및 평가</th>
                                    <th>작업</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($interviews as $interview)
                                    <tr>
                                        <!-- 지원자 정보 -->
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="fw-medium">{{ $interview->name }}</div>
                                                    <div class="text-muted small">{{ $interview->email }}</div>



                                                    <!-- 일정 -->
                                                    <div class="small">
                                                        <i class="fe fe-calendar me-1 text-muted"></i>
                                                        @if ($interview->scheduled_at)
                                                            <span>{{ $interview->scheduled_at->format('Y-m-d') }}</span>
                                                            <span
                                                                class="text-muted">{{ $interview->scheduled_at->format('H:i') }}</span>
                                                        @else
                                                            <span class="text-muted">미정</span>
                                                        @endif
                                                    </div>


                                                </div>
                                            </div>
                                        </td>

                                        <!-- 면접 정보 통합 (면접관 + 정보 + 일정) -->
                                        <td>
                                            <!-- 면접 정보 -->
                                            <div class="small">
                                                <i class="fe fe-video me-1 text-muted"></i>
                                                <span>{{ $interview->type_label }}</span>
                                                <span class="text-muted">{{ $interview->round_label }}</span>
                                            </div>

                                            <!-- 면접관 -->
                                            <div class="">
                                                <i class="fe fe-user me-1 text-muted"></i>
                                                @if ($interview->interviewer)
                                                    <span class="fw-medium">{{ $interview->interviewer->name }}</span>
                                                @else
                                                    <span class="text-muted">미배정</span>
                                                @endif
                                            </div>

                                            @if ($interview->referrer_code)
                                                <div class="small">
                                                    <span class="badge bg-primary">{{ $interview->referrer_code }}</span>
                                                    {{ $interview->referrer_name }}
                                                </div>
                                            @endif
                                        </td>

                                        <!-- 상태 및 평가 통합 (상태/결과 + 평점) -->
                                        <td>
                                            <!-- 상태 -->
                                            <div class="mb-2">
                                                @php
                                                    $statusColor = match ($interview->interview_status) {
                                                        'scheduled' => 'warning',
                                                        'in_progress' => 'info',
                                                        'completed' => 'success',
                                                        'cancelled' => 'danger',
                                                        'no_show' => 'dark',
                                                        default => 'secondary',
                                                    };
                                                @endphp
                                                <span
                                                    class="badge bg-{{ $statusColor }}">{{ $interview->status_label }}</span>

                                                @if ($interview->interview_result)
                                                    @php
                                                        $resultColor = match ($interview->interview_result) {
                                                            'pass' => 'success',
                                                            'fail' => 'danger',
                                                            'pending' => 'warning',
                                                            'hold' => 'secondary',
                                                            default => 'secondary',
                                                        };
                                                    @endphp
                                                    <span
                                                        class="badge bg-{{ $resultColor }} ms-1">{{ $interview->result_label }}</span>
                                                @endif
                                            </div>

                                            <!-- 평점 -->
                                            <div class="d-flex align-items-center">
                                                <i class="fe fe-star me-1 text-muted"></i>
                                                @if ($interview->overall_score)
                                                    <span class="fw-medium">{{ $interview->overall_score }}</span>
                                                    <span class="text-muted small">/5.0</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </div>
                                        </td>

                                        <!-- 작업 -->
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('admin.partner.interview.show', $interview->id) }}"
                                                    class="btn btn-outline-primary">보기</a>
                                                @if (!in_array($interview->interview_status, ['completed']))
                                                    <a href="{{ route('admin.partner.interview.edit', $interview->id) }}"
                                                        class="btn btn-outline-secondary">수정</a>
                                                @endif

                                                <!-- 평가 관련 버튼들 -->
                                                @if ($interview->interview_status === 'completed')
                                                    <!-- 면접 완료 후 평가 보기/수정 -->
                                                    <a href="{{ route('admin.partner.interview.evaluations.show', $interview->id) }}"
                                                        class="btn btn-outline-success">
                                                        <i class="fe fe-star me-1"></i>평가
                                                    </a>
                                                    <a href="{{ route('admin.partner.interview.evaluations.edit', $interview->id) }}"
                                                        class="btn btn-outline-secondary">
                                                        <i class="fe fe-edit-2 me-1"></i>평가수정
                                                    </a>
                                                @else
                                                    <!-- 모든 상태에서 평가 작성 가능 -->
                                                    <a href="{{ route('admin.partner.interview.evaluations.create', ['interview_id' => $interview->id]) }}"
                                                        class="btn btn-outline-warning">
                                                        <i class="fe fe-edit me-1"></i>평가작성
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fe fe-calendar fe-2x mb-2 d-block"></i>
                                                검색 결과가 없습니다.
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($interviews->hasPages())
                        <div class="card-footer">
                            {{ $interviews->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
@endsection
