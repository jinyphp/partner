@extends('jiny-partner::layouts.admin.sidebar')

@section('title', '파트너 성과 지표 관리')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">📊 파트너 성과 지표 관리</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="/admin">관리자</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.partner.index') }}">파트너</a></li>
                        <li class="breadcrumb-item active">성과 지표</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- 통계 대시보드 -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar avatar-md bg-primary-subtle rounded">
                                <i class="fe fe-bar-chart-2 fs-20 text-primary"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1">{{ number_format($stats['total_records']) }}</h5>
                            <p class="text-muted mb-0">총 성과 기록</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar avatar-md bg-success-subtle rounded">
                                <i class="fe fe-users fs-20 text-success"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1">{{ number_format($stats['total_partners']) }}</h5>
                            <p class="text-muted mb-0">활성 파트너</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar avatar-md bg-warning-subtle rounded">
                                <i class="fe fe-dollar-sign fs-20 text-warning"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1">{{ number_format($stats['avg_total_sales'], 0) }}만원</h5>
                            <p class="text-muted mb-0">평균 매출</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar avatar-md bg-info-subtle rounded">
                                <i class="fe fe-award fs-20 text-info"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            @if($stats['top_performer'])
                                <h5 class="mb-1">{{ $stats['top_performer']->name }}</h5>
                                <p class="text-muted mb-0">최고 성과자 ({{ number_format($stats['top_performer']->total_sales, 0) }}만원)</p>
                            @else
                                <h5 class="mb-1">-</h5>
                                <p class="text-muted mb-0">데이터 없음</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 필터 및 검색 -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.partner.performance.metrics.index') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">파트너</label>
                        <select name="partner_id" class="form-select">
                            <option value="">전체 파트너</option>
                            @foreach($partners as $partner)
                                <option value="{{ $partner->id }}" {{ request('partner_id') == $partner->id ? 'selected' : '' }}>
                                    {{ $partner->name }} ({{ $partner->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">기간 유형</label>
                        <select name="period_type" class="form-select">
                            <option value="">전체</option>
                            <option value="weekly" {{ request('period_type') == 'weekly' ? 'selected' : '' }}>주간</option>
                            <option value="monthly" {{ request('period_type') == 'monthly' ? 'selected' : '' }}>월간</option>
                            <option value="quarterly" {{ request('period_type') == 'quarterly' ? 'selected' : '' }}>분기</option>
                            <option value="yearly" {{ request('period_type') == 'yearly' ? 'selected' : '' }}>연간</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">연도</label>
                        <select name="period_year" class="form-select">
                            <option value="">전체</option>
                            @for($year = date('Y'); $year >= 2020; $year--)
                                <option value="{{ $year }}" {{ request('period_year') == $year ? 'selected' : '' }}>{{ $year }}년</option>
                            @endfor
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">월</label>
                        <select name="period_month" class="form-select">
                            <option value="">전체</option>
                            @for($month = 1; $month <= 12; $month++)
                                <option value="{{ $month }}" {{ request('period_month') == $month ? 'selected' : '' }}>{{ $month }}월</option>
                            @endfor
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">검색</label>
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="파트너명 또는 이메일" value="{{ request('search') }}">
                            <button type="submit" class="btn btn-primary">
                                <i class="fe fe-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 성과 지표 목록 -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">성과 지표 목록</h5>
            <a href="{{ route('admin.partner.performance.metrics.create') }}" class="btn btn-primary">
                <i class="fe fe-plus"></i> 새 성과 지표 등록
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>파트너</th>
                            <th>기간</th>
                            <th>유형</th>
                            <th class="text-end">총 매출</th>
                            <th class="text-end">수수료</th>
                            <th class="text-end">거래 건수</th>
                            <th class="text-center">만족도</th>
                            <th class="text-center">효율성</th>
                            <th class="text-center">순위</th>
                            <th>액션</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($metrics as $metric)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm bg-light rounded-circle me-2">
                                            <span class="text-dark fs-14">{{ substr($metric->partner_name, 0, 1) }}</span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ $metric->partner_name }}</h6>
                                            <small class="text-muted">{{ $metric->partner_email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-medium">{{ date('Y-m-d', strtotime($metric->period_start)) }}</div>
                                    <small class="text-muted">~ {{ date('Y-m-d', strtotime($metric->period_end)) }}</small>
                                </td>
                                <td>
                                    @switch($metric->period_type)
                                        @case('weekly')
                                            <span class="badge bg-info">주간</span>
                                            @break
                                        @case('monthly')
                                            <span class="badge bg-primary">월간</span>
                                            @break
                                        @case('quarterly')
                                            <span class="badge bg-warning">분기</span>
                                            @break
                                        @case('yearly')
                                            <span class="badge bg-success">연간</span>
                                            @break
                                    @endswitch
                                </td>
                                <td class="text-end">
                                    <div class="fw-bold text-dark">{{ number_format($metric->total_sales, 0) }}만원</div>
                                    @if($metric->growth_rate > 0)
                                        <small class="text-success"><i class="fe fe-trending-up me-1"></i>{{ $metric->growth_rate }}%</small>
                                    @elseif($metric->growth_rate < 0)
                                        <small class="text-danger"><i class="fe fe-trending-down me-1"></i>{{ abs($metric->growth_rate) }}%</small>
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format($metric->commission_earned, 0) }}만원</td>
                                <td class="text-end">{{ number_format($metric->deals_closed) }}건</td>
                                <td class="text-center">
                                    @if($metric->customer_satisfaction_score)
                                        <div class="d-flex align-items-center justify-content-center">
                                            <span class="me-1">{{ $metric->customer_satisfaction_score }}</span>
                                            <div class="rating-stars">
                                                @for($i = 1; $i <= 5; $i++)
                                                    @if($i <= $metric->customer_satisfaction_score)
                                                        <i class="fe fe-star text-warning fs-12"></i>
                                                    @else
                                                        <i class="fe fe-star text-muted fs-12"></i>
                                                    @endif
                                                @endfor
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($metric->efficiency_score)
                                        <span class="badge {{ $metric->efficiency_score > 100 ? 'bg-success' : ($metric->efficiency_score > 50 ? 'bg-warning' : 'bg-danger') }}">
                                            {{ $metric->efficiency_score }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($metric->rank_in_tier)
                                        <span class="badge bg-primary">{{ $metric->rank_in_tier }}위</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.partner.performance.metrics.show', $metric->id) }}"
                                           class="btn btn-outline-primary">
                                            <i class="fe fe-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.partner.performance.metrics.edit', $metric->id) }}"
                                           class="btn btn-outline-warning">
                                            <i class="fe fe-edit"></i>
                                        </a>
                                        <button type="button"
                                                class="btn btn-outline-danger"
                                                onclick="deleteMetric({{ $metric->id }})">
                                            <i class="fe fe-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fe fe-inbox fs-48 d-block mb-2"></i>
                                        등록된 성과 지표가 없습니다.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($metrics->hasPages())
        <div class="card-footer">
            {{ $metrics->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- 삭제 확인 모달 -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">성과 지표 삭제 확인</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                정말로 이 성과 지표를 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.
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
function deleteMetric(id) {
    const deleteForm = document.getElementById('deleteForm');
    deleteForm.action = `/admin/partner/performance/metrics/${id}`;

    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}
</script>
@endsection