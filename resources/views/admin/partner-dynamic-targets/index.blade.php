@extends('jiny-partner::layouts.admin.sidebar')

@section('title', '동적 목표 관리')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
.achievement-gradient {
    background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%);
}

.hover-lift {
    transition: transform 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.hover-lift:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.progress-circle {
    width: 4rem;
    height: 4rem;
    border: 0.25rem solid;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.75rem;
}

.badge-status {
    font-size: 0.75rem;
    padding: 0.375em 0.75em;
}

.metric-card {
    border-radius: 0.5rem;
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.075);
}

.btn-sm-icon {
    width: 2rem;
    height: 2rem;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
</style>
@endpush

@section('content')
<div class="container-fluid py-3">
    {{-- 헤더 섹션 --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center">
                <div class="mb-3 mb-lg-0">
                    <h1 class="display-6 fw-bold mb-1">동적 목표 관리</h1>
                    @if($partner)
                        <div class="d-flex flex-wrap align-items-center">
                            <span class="me-3">
                                <i class="bi bi-person-circle me-1"></i>
                                {{ $partner->name }} ({{ $partner->email }})
                            </span>
                            <span class="badge bg-{{ $partner->partnerType ? 'primary' : 'secondary' }} me-2">
                                {{ $partner->partnerType?->type_name ?? '타입 미설정' }}
                            </span>
                            <span class="badge bg-{{ $partner->partnerTier ? 'success' : 'secondary' }}">
                                {{ $partner->partnerTier?->tier_name ?? '등급 미설정' }}
                            </span>
                        </div>
                    @endif
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.partner.users.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>파트너 목록
                    </a>
                    @if($partner)
                        <a href="{{ route('admin.partner.targets.create', ['partner_id' => $partner->id]) }}"
                           class="btn btn-primary">
                            <i class="bi bi-plus-lg me-1"></i>새 목표 생성
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- 성과 통계 카드 --}}
    @if($partner && $performanceStats)
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm metric-card achievement-gradient text-white h-100">
                <div class="card-body text-center py-4">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-20 mb-3" style="width: 3rem; height: 3rem;">
                        <i class="bi bi-bullseye fs-5"></i>
                    </div>
                    <h3 class="fw-bold mb-1">{{ $performanceStats['active_targets'] }}</h3>
                    <p class="mb-0 opacity-75">활성 목표</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm metric-card h-100">
                <div class="card-body text-center py-4">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 text-primary mb-3" style="width: 3rem; height: 3rem;">
                        <i class="bi bi-graph-up-arrow fs-5"></i>
                    </div>
                    <h3 class="fw-bold text-primary mb-1">{{ $performanceStats['avg_achievement'] }}%</h3>
                    <p class="text-muted mb-0">평균 달성률</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm metric-card h-100">
                <div class="card-body text-center py-4">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 text-success mb-3" style="width: 3rem; height: 3rem;">
                        <i class="bi bi-currency-dollar fs-5"></i>
                    </div>
                    <h3 class="fw-bold text-success mb-1">{{ number_format($performanceStats['total_bonus']) }}원</h3>
                    <p class="text-muted mb-0">누적 보너스</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm metric-card h-100">
                <div class="card-body text-center py-4">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-info bg-opacity-10 text-info mb-3" style="width: 3rem; height: 3rem;">
                        <i class="bi bi-activity fs-5"></i>
                    </div>
                    <h3 class="fw-bold text-info mb-1">{{ $activeTargets->count() }}</h3>
                    <p class="text-muted mb-0">진행중인 목표</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- 현재 활성 목표 --}}
    @if($activeTargets->count() > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pb-0">
                    <div class="d-flex align-items-center">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning bg-opacity-10 text-warning me-3" style="width: 2.5rem; height: 2.5rem;">
                            <i class="bi bi-lightning-charge-fill"></i>
                        </div>
                        <h5 class="mb-0 fw-bold">현재 활성 목표</h5>
                    </div>
                </div>
                <div class="card-body pt-3">
                    <div class="row g-3">
                        @foreach($activeTargets as $target)
                        <div class="col-lg-6 col-xl-4">
                            <div class="card border-success hover-lift h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h6 class="fw-bold mb-1">{{ $target->period_display }}</h6>
                                            <span class="badge bg-success badge-status">
                                                <i class="bi bi-check-circle-fill me-1"></i>{{ $target->status_display }}
                                            </span>
                                        </div>
                                        <div class="progress-circle border-success text-success">
                                            {{ number_format($target->overall_achievement_rate, 1) }}%
                                        </div>
                                    </div>

                                    <div class="row text-center mb-3">
                                        <div class="col-6">
                                            <div class="border-end">
                                                <small class="text-muted d-block">매출 목표</small>
                                                <div class="fw-semibold text-primary">{{ number_format($target->final_sales_target) }}원</div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div>
                                                <small class="text-muted d-block">건수 목표</small>
                                                <div class="fw-semibold text-info">{{ number_format($target->final_cases_target) }}건</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="progress mb-3" style="height: 0.5rem;">
                                        <div class="progress-bar bg-gradient"
                                             style="width: {{ min($target->overall_achievement_rate, 100) }}%;
                                                    background: linear-gradient(90deg, #198754, #20c997);">
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="bi bi-calendar-event me-1"></i>
                                            {{ $target->next_review_date ? \Carbon\Carbon::parse($target->next_review_date)->format('m/d') : 'N/A' }}
                                        </small>
                                        <a href="{{ route('admin.partner.targets.show', $target) }}"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye me-1"></i>상세보기
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- 전체 목표 목록 --}}
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pb-0">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center">
                        <div class="d-flex align-items-center mb-3 mb-lg-0">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-secondary bg-opacity-10 text-secondary me-3" style="width: 2.5rem; height: 2.5rem;">
                                <i class="bi bi-list-ul"></i>
                            </div>
                            <h5 class="mb-0 fw-bold">전체 목표 목록</h5>
                        </div>
                        <div class="input-group" style="max-width: 300px;">
                            <span class="input-group-text">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" class="form-control" placeholder="목표 검색..." id="searchInput">
                        </div>
                    </div>
                </div>
                <div class="card-body pt-3">
                    @if($targets->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="fw-semibold">기간</th>
                                        <th class="fw-semibold">목표 유형</th>
                                        <th class="fw-semibold">매출 목표</th>
                                        <th class="fw-semibold">건수 목표</th>
                                        <th class="fw-semibold">달성률</th>
                                        <th class="fw-semibold">상태</th>
                                        <th class="fw-semibold">생성일</th>
                                        <th class="fw-semibold text-center">작업</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($targets as $target)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $target->period_display }}</div>
                                        </td>
                                        <td>
                                            <span class="badge
                                                @if($target->target_period_type === 'monthly') bg-primary
                                                @elseif($target->target_period_type === 'quarterly') bg-info
                                                @else bg-secondary @endif">
                                                {{ $target->target_period_type === 'monthly' ? '월별' :
                                                   ($target->target_period_type === 'quarterly' ? '분기별' : '연별') }}
                                            </span>
                                        </td>
                                        <td>
                                            <div>
                                                <div class="fw-semibold text-primary">{{ number_format($target->final_sales_target) }}원</div>
                                                <small class="text-success">
                                                    <i class="bi bi-check-circle-fill me-1"></i>
                                                    {{ number_format($target->current_sales_achievement) }}원 달성
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <div class="fw-semibold text-info">{{ number_format($target->final_cases_target) }}건</div>
                                                <small class="text-success">
                                                    <i class="bi bi-check-circle-fill me-1"></i>
                                                    {{ number_format($target->current_cases_achievement) }}건 달성
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress me-2" style="width: 4rem; height: 0.375rem;">
                                                    <div class="progress-bar
                                                        @if($target->overall_achievement_rate >= 100) bg-success
                                                        @elseif($target->overall_achievement_rate >= 80) bg-warning
                                                        @else bg-danger @endif"
                                                         style="width: {{ min($target->overall_achievement_rate, 100) }}%">
                                                    </div>
                                                </div>
                                                <span class="fw-semibold small">{{ number_format($target->overall_achievement_rate, 1) }}%</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge
                                                @if($target->status === 'draft') bg-secondary
                                                @elseif($target->status === 'pending_approval') bg-warning text-dark
                                                @elseif($target->status === 'approved') bg-info
                                                @elseif($target->status === 'active') bg-success
                                                @elseif($target->status === 'completed') bg-primary
                                                @else bg-danger @endif">
                                                {{ $target->status_display }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="small text-muted">
                                                <div>{{ $target->created_at->format('Y-m-d') }}</div>
                                                <div>{{ $target->createdBy?->name ?? 'N/A' }}</div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('admin.partner.targets.show', $target) }}"
                                                   class="btn btn-outline-primary btn-sm-icon"
                                                   data-bs-toggle="tooltip"
                                                   title="상세보기">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                @if(!in_array($target->status, ['completed', 'cancelled']))
                                                    <a href="{{ route('admin.partner.targets.edit', $target) }}"
                                                       class="btn btn-outline-secondary btn-sm-icon"
                                                       data-bs-toggle="tooltip"
                                                       title="수정">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                @endif
                                                @if($target->status === 'pending_approval')
                                                    <form method="POST"
                                                          action="{{ route('admin.partner.targets.approve', $target) }}"
                                                          class="d-inline">
                                                        @csrf
                                                        <button type="submit"
                                                                class="btn btn-outline-success btn-sm-icon"
                                                                data-bs-toggle="tooltip"
                                                                title="승인">
                                                            <i class="bi bi-check-lg"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                @if($target->status === 'approved')
                                                    <form method="POST"
                                                          action="{{ route('admin.partner.targets.activate', $target) }}"
                                                          class="d-inline">
                                                        @csrf
                                                        <button type="submit"
                                                                class="btn btn-outline-warning btn-sm-icon"
                                                                data-bs-toggle="tooltip"
                                                                title="활성화">
                                                            <i class="bi bi-play-fill"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- 페이지네이션 --}}
                        <div class="d-flex justify-content-center mt-4">
                            <nav aria-label="목표 목록 페이지네이션">
                                {{ $targets->appends(request()->query())->links('pagination::bootstrap-4') }}
                            </nav>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light text-muted mb-4" style="width: 5rem; height: 5rem;">
                                <i class="bi bi-bullseye" style="font-size: 2.5rem;"></i>
                            </div>
                            <h4 class="text-muted fw-bold mb-3">설정된 목표가 없습니다</h4>
                            <p class="text-muted mb-4">새로운 목표를 생성하여 성과 관리를 시작하세요.</p>
                            @if($partner)
                                <a href="{{ route('admin.partner.targets.create', ['partner_id' => $partner->id]) }}"
                                   class="btn btn-primary btn-lg">
                                    <i class="bi bi-plus-lg me-2"></i>첫 번째 목표 생성
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Bootstrap 툴팁 초기화
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // 검색 기능
    const searchInput = document.getElementById('searchInput');
    const tableRows = document.querySelectorAll('tbody tr');

    if (searchInput && tableRows.length > 0) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();

            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }

    // 상태 변경 확인
    const statusForms = document.querySelectorAll('form[action*="approve"], form[action*="activate"]');
    statusForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!confirm('이 작업을 수행하시겠습니까?')) {
                e.preventDefault();
            }
        });
    });

    // 카드 호버 효과 최적화
    const hoverCards = document.querySelectorAll('.hover-lift');
    hoverCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.classList.add('shadow');
        });

        card.addEventListener('mouseleave', function() {
            this.classList.remove('shadow');
        });
    });
});
</script>
@endpush
