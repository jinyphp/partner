@extends('jiny-partner::layouts.admin.sidebar')

@section('title', '파트너 활동 로그')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">📋 파트너 활동 로그</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="/admin">관리자</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.partner.index') }}">파트너</a></li>
                        <li class="breadcrumb-item active">활동 로그</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- 필터 및 검색 -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.partner.activity.logs.index') }}" class="row g-3">
                        <div class="col-md-3">
                            <label for="activity_type" class="form-label">활동 유형</label>
                            <select name="activity_type" id="activity_type" class="form-select">
                                <option value="">전체</option>
                                @foreach($activityTypes as $key => $label)
                                    <option value="{{ $key }}" {{ request('activity_type') == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="date_from" class="form-label">시작일</label>
                            <input type="date" name="date_from" id="date_from" class="form-control"
                                   value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="date_to" class="form-label">종료일</label>
                            <input type="date" name="date_to" id="date_to" class="form-control"
                                   value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="search" class="form-label">검색</label>
                            <input type="text" name="search" id="search" class="form-control"
                                   placeholder="파트너명, 이메일, 메모 검색..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary">🔍 검색</button>
                            <a href="{{ route('admin.partner.activity.logs.index') }}" class="btn btn-outline-secondary">초기화</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 통계 카드 -->
    <div class="row mb-3">
        <div class="col-xl-3 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <p class="text-muted fw-normal mb-0">총 활동 로그</p>
                            <h3 class="my-1">{{ number_format($logs->total()) }}</h3>
                            <p class="mb-0"><span class="text-muted">건</span></p>
                        </div>
                        <div class="col-4 text-center">
                            <div class="avatar-sm bg-light rounded">
                                <span class="avatar-title bg-primary-lighten text-primary rounded font-22">
                                    📊
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <p class="text-muted fw-normal mb-0">오늘 활동</p>
                            <h3 class="my-1">{{ $logs->where('created_at', '>=', today())->count() }}</h3>
                            <p class="mb-0"><span class="text-muted">건</span></p>
                        </div>
                        <div class="col-4 text-center">
                            <div class="avatar-sm bg-light rounded">
                                <span class="avatar-title bg-success-lighten text-success rounded font-22">
                                    📈
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <p class="text-muted fw-normal mb-0">승인 활동</p>
                            <h3 class="my-1">{{ $logs->where('activity_type', 'approved')->count() }}</h3>
                            <p class="mb-0"><span class="text-muted">건</span></p>
                        </div>
                        <div class="col-4 text-center">
                            <div class="avatar-sm bg-light rounded">
                                <span class="avatar-title bg-info-lighten text-info rounded font-22">
                                    ✅
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <p class="text-muted fw-normal mb-0">상태 변경</p>
                            <h3 class="my-1">{{ $logs->where('activity_type', 'status_changed')->count() }}</h3>
                            <p class="mb-0"><span class="text-muted">건</span></p>
                        </div>
                        <div class="col-4 text-center">
                            <div class="avatar-sm bg-light rounded">
                                <span class="avatar-title bg-warning-lighten text-warning rounded font-22">
                                    🔄
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 액션 버튼 -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">활동 로그 목록</h5>
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary" onclick="exportLogs()">
                        📤 내보내기
                    </button>
                    <button type="button" class="btn btn-outline-info" onclick="showStats()">
                        📊 통계보기
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 로그 테이블 -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    @if($logs->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="60">ID</th>
                                        <th width="120">날짜/시간</th>
                                        <th width="120">파트너</th>
                                        <th width="100">활동 유형</th>
                                        <th width="120">이전 값</th>
                                        <th width="120">새로운 값</th>
                                        <th width="100">작업자</th>
                                        <th width="100">IP 주소</th>
                                        <th width="60">액션</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($logs as $log)
                                        <tr>
                                            <td>{{ $log->id }}</td>
                                            <td>
                                                <small>
                                                    {{ date('m-d H:i', strtotime($log->created_at)) }}<br>
                                                    <span class="text-muted">{{ date('Y', strtotime($log->created_at)) }}</span>
                                                </small>
                                            </td>
                                            <td>
                                                @if($log->partner_name)
                                                    <div class="fw-bold">{{ $log->partner_name }}</div>
                                                    <small class="text-muted">{{ $log->partner_email }}</small>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $typeClass = match($log->activity_type) {
                                                        'approved' => 'badge bg-success',
                                                        'rejected' => 'badge bg-danger',
                                                        'status_changed' => 'badge bg-warning',
                                                        'tier_changed' => 'badge bg-info',
                                                        'application_submitted' => 'badge bg-primary',
                                                        default => 'badge bg-secondary'
                                                    };
                                                @endphp
                                                <span class="{{ $typeClass }}">
                                                    {{ $activityTypes[$log->activity_type] ?? $log->activity_type }}
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-truncate d-block" style="max-width: 120px;"
                                                       title="{{ $log->old_value }}">
                                                    {{ $log->old_value }}
                                                </small>
                                            </td>
                                            <td>
                                                <small class="text-truncate d-block" style="max-width: 120px;"
                                                       title="{{ $log->new_value }}">
                                                    {{ $log->new_value }}
                                                </small>
                                            </td>
                                            <td>
                                                <small>{{ $log->user_name ?: '-' }}</small>
                                            </td>
                                            <td>
                                                <small class="font-monospace">{{ $log->ip_address ?: '-' }}</small>
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.partner.activity.logs.show', $log->id) }}"
                                                   class="btn btn-sm btn-outline-primary">
                                                    👁️
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- 페이지네이션 -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                <small class="text-muted">
                                    {{ $logs->firstItem() }}-{{ $logs->lastItem() }} / 총 {{ number_format($logs->total()) }}건
                                </small>
                            </div>
                            <div>
                                {{ $logs->appends(request()->query())->links() }}
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="avatar-xl mx-auto mb-3">
                                <span class="avatar-title bg-light text-muted rounded-circle font-24">
                                    📋
                                </span>
                            </div>
                            <h5 class="text-muted">활동 로그가 없습니다</h5>
                            <p class="text-muted">아직 등록된 활동 로그가 없습니다.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 통계 모달 -->
<div class="modal fade" id="statsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">📊 활동 로그 통계</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="statsContent">
                    <div class="text-center py-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">로딩중...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.table th {
    font-size: 0.9em;
    font-weight: 600;
}
.table td {
    font-size: 0.85em;
    vertical-align: middle;
}
.badge {
    font-size: 0.75em;
}
</style>
@endpush

@push('scripts')
<script>
function exportLogs() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', '1');
    window.location.href = "{{ route('admin.partner.activity.logs.export') }}?" + params.toString();
}

function showStats() {
    $('#statsModal').modal('show');

    $.ajax({
        url: "{{ route('admin.partner.activity.logs.stats') }}",
        method: 'GET',
        data: {
            date_from: $('#date_from').val(),
            date_to: $('#date_to').val()
        },
        success: function(response) {
            renderStats(response);
        },
        error: function() {
            $('#statsContent').html('<div class="alert alert-danger">통계 데이터를 불러오지 못했습니다.</div>');
        }
    });
}

function renderStats(data) {
    let html = '<div class="row">';

    // 활동 유형별 통계
    html += '<div class="col-md-6"><h6>활동 유형별 통계</h6><ul class="list-group">';
    data.activity_stats.forEach(stat => {
        html += `<li class="list-group-item d-flex justify-content-between">
            <span>${stat.activity_type}</span>
            <span class="badge bg-primary">${stat.count}</span>
        </li>`;
    });
    html += '</ul></div>';

    // 사용자별 활동 통계
    html += '<div class="col-md-6"><h6>사용자별 활동 통계 (상위 10명)</h6><ul class="list-group">';
    data.user_stats.forEach(stat => {
        html += `<li class="list-group-item d-flex justify-content-between">
            <span>${stat.name || '알 수 없음'}</span>
            <span class="badge bg-success">${stat.count}</span>
        </li>`;
    });
    html += '</ul></div>';

    html += '</div>';

    $('#statsContent').html(html);
}

// 페이지 로드 시 날짜 기본값 설정
$(document).ready(function() {
    if (!$('#date_from').val()) {
        $('#date_from').val(new Date(Date.now() - 30*24*60*60*1000).toISOString().split('T')[0]);
    }
    if (!$('#date_to').val()) {
        $('#date_to').val(new Date().toISOString().split('T')[0]);
    }
});
</script>
@endpush
