@extends('jiny-partner::layouts.admin.sidebar')

@section('title', '파트너 알림')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">🔔 파트너 알림</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="/admin">관리자</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.partner.index') }}">파트너</a></li>
                        <li class="breadcrumb-item active">알림</li>
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
                    <form method="GET" action="{{ route('admin.partner.notifications.index') }}" class="row g-3">
                        <div class="col-md-2">
                            <label for="type" class="form-label">알림 유형</label>
                            <select name="type" id="type" class="form-select">
                                <option value="">전체</option>
                                @foreach($notificationTypes as $key => $label)
                                    <option value="{{ $key }}" {{ request('type') == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="priority" class="form-label">우선순위</label>
                            <select name="priority" id="priority" class="form-select">
                                <option value="">전체</option>
                                @foreach($priorities as $key => $label)
                                    <option value="{{ $key }}" {{ request('priority') == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="is_read" class="form-label">읽음 상태</label>
                            <select name="is_read" id="is_read" class="form-select">
                                <option value="">전체</option>
                                <option value="0" {{ request('is_read') === '0' ? 'selected' : '' }}>읽지않음</option>
                                <option value="1" {{ request('is_read') === '1' ? 'selected' : '' }}>읽음</option>
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
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">🔍 검색</button>
                        </div>
                    </form>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <input type="text" name="search" class="form-control" placeholder="제목, 내용, 수신자 검색..."
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-6 d-flex align-items-end gap-2">
                            <a href="{{ route('admin.partner.notifications.index') }}" class="btn btn-outline-secondary">초기화</a>
                            <a href="{{ route('admin.partner.notifications.create') }}" class="btn btn-success">📤 새 알림 생성</a>
                        </div>
                    </div>
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
                            <p class="text-muted fw-normal mb-0">총 알림</p>
                            <h3 class="my-1">{{ number_format($notifications->total()) }}</h3>
                            <p class="mb-0"><span class="text-muted">건</span></p>
                        </div>
                        <div class="col-4 text-center">
                            <div class="avatar-sm bg-light rounded">
                                <span class="avatar-title bg-primary-lighten text-primary rounded font-22">
                                    🔔
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
                            <p class="text-muted fw-normal mb-0">읽지않음</p>
                            <h3 class="my-1">{{ $notifications->where('is_read', 0)->count() }}</h3>
                            <p class="mb-0"><span class="text-muted">건</span></p>
                        </div>
                        <div class="col-4 text-center">
                            <div class="avatar-sm bg-light rounded">
                                <span class="avatar-title bg-warning-lighten text-warning rounded font-22">
                                    📬
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
                            <p class="text-muted fw-normal mb-0">긴급 알림</p>
                            <h3 class="my-1">{{ $notifications->where('priority', 'urgent')->count() }}</h3>
                            <p class="mb-0"><span class="text-muted">건</span></p>
                        </div>
                        <div class="col-4 text-center">
                            <div class="avatar-sm bg-light rounded">
                                <span class="avatar-title bg-danger-lighten text-danger rounded font-22">
                                    🚨
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
                            <p class="text-muted fw-normal mb-0">오늘 알림</p>
                            <h3 class="my-1">{{ $notifications->where('created_at', '>=', today())->count() }}</h3>
                            <p class="mb-0"><span class="text-muted">건</span></p>
                        </div>
                        <div class="col-4 text-center">
                            <div class="avatar-sm bg-light rounded">
                                <span class="avatar-title bg-success-lighten text-success rounded font-22">
                                    📅
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
                <h5 class="mb-0">알림 목록</h5>
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-success" onclick="markAllAsRead()">
                        ✅ 모두 읽음처리
                    </button>
                    <button type="button" class="btn btn-outline-primary" onclick="exportNotifications()">
                        📤 내보내기
                    </button>
                    <button type="button" class="btn btn-outline-info" onclick="showStatistics()">
                        📊 통계보기
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 알림 테이블 -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    @if($notifications->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="60">ID</th>
                                        <th width="120">생성일시</th>
                                        <th width="120">수신자</th>
                                        <th width="200">제목</th>
                                        <th width="100">유형</th>
                                        <th width="80">우선순위</th>
                                        <th width="80">상태</th>
                                        <th width="100">전송채널</th>
                                        <th width="80">액션</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($notifications as $notification)
                                        <tr class="{{ !$notification->is_read ? 'table-warning' : '' }}">
                                            <td>{{ $notification->id }}</td>
                                            <td>
                                                <small>
                                                    {{ date('m-d H:i', strtotime($notification->created_at)) }}<br>
                                                    <span class="text-muted">{{ date('Y', strtotime($notification->created_at)) }}</span>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="fw-bold">{{ $notification->user_name }}</div>
                                                <small class="text-muted">{{ $notification->user_email }}</small>
                                            </td>
                                            <td>
                                                <div class="fw-bold">{{ $notification->title }}</div>
                                                <small class="text-muted text-truncate d-block" style="max-width: 200px;">
                                                    {{ $notification->message }}
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    {{ $notificationTypes[$notification->type] ?? $notification->type }}
                                                </span>
                                            </td>
                                            <td>
                                                @php
                                                    $priorityClass = match($notification->priority) {
                                                        'urgent' => 'badge bg-danger',
                                                        'high' => 'badge bg-warning',
                                                        'normal' => 'badge bg-info',
                                                        'low' => 'badge bg-secondary',
                                                        default => 'badge bg-secondary'
                                                    };
                                                @endphp
                                                <span class="{{ $priorityClass }}">
                                                    {{ $priorities[$notification->priority] ?? $notification->priority }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($notification->is_read)
                                                    <span class="badge bg-success">읽음</span>
                                                @else
                                                    <span class="badge bg-warning">읽지않음</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $channels = json_decode($notification->channels, true) ?? [];
                                                @endphp
                                                <small>{{ implode(', ', $channels) }}</small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('admin.partner.notifications.show', $notification->id) }}"
                                                       class="btn btn-outline-primary btn-sm">
                                                        👁️
                                                    </a>
                                                    @if(!$notification->is_read)
                                                        <button type="button" class="btn btn-outline-success btn-sm"
                                                                onclick="markAsRead({{ $notification->id }})">
                                                            ✅
                                                        </button>
                                                    @endif
                                                    <button type="button" class="btn btn-outline-danger btn-sm"
                                                            onclick="deleteNotification({{ $notification->id }})">
                                                        🗑️
                                                    </button>
                                                </div>
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
                                    {{ $notifications->firstItem() }}-{{ $notifications->lastItem() }} / 총 {{ number_format($notifications->total()) }}건
                                </small>
                            </div>
                            <div>
                                {{ $notifications->appends(request()->query())->links() }}
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="avatar-xl mx-auto mb-3">
                                <span class="avatar-title bg-light text-muted rounded-circle font-24">
                                    🔔
                                </span>
                            </div>
                            <h5 class="text-muted">알림이 없습니다</h5>
                            <p class="text-muted">아직 전송된 알림이 없습니다.</p>
                            <a href="{{ route('admin.partner.notifications.create') }}" class="btn btn-success">
                                📤 첫 알림 생성하기
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 통계 모달 -->
<div class="modal fade" id="statisticsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">📊 알림 통계</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="statisticsContent">
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
.table-warning {
    --bs-table-accent-bg: rgba(255, 193, 7, 0.1);
}
</style>
@endpush

@push('scripts')
<script>
// 개별 읽음 처리
function markAsRead(notificationId) {
    if (!confirm('이 알림을 읽음 처리하시겠습니까?')) return;

    $.ajax({
        url: `/admin/partner/notifications/${notificationId}/read`,
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function() {
            location.reload();
        },
        error: function() {
            alert('읽음 처리에 실패했습니다.');
        }
    });
}

// 모든 알림 읽음 처리
function markAllAsRead() {
    if (!confirm('모든 알림을 읽음 처리하시겠습니까?')) return;

    $.ajax({
        url: '/admin/partner/notifications/mark-all-read',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            alert(response.message);
            location.reload();
        },
        error: function() {
            alert('일괄 읽음 처리에 실패했습니다.');
        }
    });
}

// 알림 삭제
function deleteNotification(notificationId) {
    if (!confirm('이 알림을 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.')) return;

    $.ajax({
        url: `/admin/partner/notifications/${notificationId}`,
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function() {
            location.reload();
        },
        error: function() {
            alert('삭제에 실패했습니다.');
        }
    });
}

// 내보내기
function exportNotifications() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', '1');
    window.location.href = "/admin/partner/notifications/export?" + params.toString();
}

// 통계 보기
function showStatistics() {
    $('#statisticsModal').modal('show');

    $.ajax({
        url: '/admin/partner/notifications/statistics',
        method: 'GET',
        data: {
            date_from: $('#date_from').val(),
            date_to: $('#date_to').val()
        },
        success: function(response) {
            renderStatistics(response);
        },
        error: function() {
            $('#statisticsContent').html('<div class="alert alert-danger">통계 데이터를 불러오지 못했습니다.</div>');
        }
    });
}

// 통계 렌더링
function renderStatistics(data) {
    let html = '<div class="row">';

    // 알림 유형별 통계
    html += '<div class="col-md-6"><h6>알림 유형별 통계</h6><ul class="list-group">';
    data.type_stats.forEach(stat => {
        html += `<li class="list-group-item d-flex justify-content-between">
            <span>${stat.type}</span>
            <span class="badge bg-primary">${stat.count}</span>
        </li>`;
    });
    html += '</ul></div>';

    // 우선순위별 통계
    html += '<div class="col-md-6"><h6>우선순위별 통계</h6><ul class="list-group">';
    data.priority_stats.forEach(stat => {
        html += `<li class="list-group-item d-flex justify-content-between">
            <span>${stat.priority}</span>
            <span class="badge bg-info">${stat.count}</span>
        </li>`;
    });
    html += '</ul></div>';

    html += '</div>';

    $('#statisticsContent').html(html);
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