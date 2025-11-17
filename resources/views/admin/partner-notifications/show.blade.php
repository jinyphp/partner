@extends('jiny-partner::layouts.admin.sidebar')

@section('title', '알림 상세')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">🔔 알림 상세</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="/admin">관리자</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.partner.index') }}">파트너</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.partner.notifications.index') }}">알림</a></li>
                        <li class="breadcrumb-item active">상세</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            알림 #{{ $notification->id }}
                            @php
                                $priorityClass = match($notification->priority) {
                                    'urgent' => 'badge bg-danger',
                                    'high' => 'badge bg-warning',
                                    'normal' => 'badge bg-info',
                                    'low' => 'badge bg-secondary',
                                    default => 'badge bg-secondary'
                                };
                                $typeLabels = [
                                    'status_update' => '상태 변경',
                                    'interview_scheduled' => '면접 일정',
                                    'approved' => '승인 완료',
                                    'rejected' => '신청 거부',
                                    'reapply_available' => '재신청 가능',
                                    'tier_upgraded' => '등급 승급',
                                    'performance_alert' => '성과 알림'
                                ];
                            @endphp
                            <span class="badge bg-secondary ms-2">
                                {{ $typeLabels[$notification->type] ?? $notification->type }}
                            </span>
                            <span class="{{ $priorityClass }} ms-1">
                                {{ ucfirst($notification->priority) }}
                            </span>
                        </h5>
                        <div class="btn-group">
                            <a href="{{ route('admin.partner.notifications.index') }}" class="btn btn-outline-secondary">
                                ⬅️ 목록으로
                            </a>
                            @if(!$notification->is_read)
                                <button type="button" class="btn btn-outline-success" onclick="markAsRead()">
                                    ✅ 읽음처리
                                </button>
                            @endif
                            <button type="button" class="btn btn-outline-danger" onclick="deleteNotification()">
                                🗑️ 삭제
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- 기본 정보 -->
                        <div class="col-md-6">
                            <h6 class="mb-3">📋 기본 정보</h6>
                            <table class="table table-sm table-borderless">
                                <tbody>
                                    <tr>
                                        <th width="120" class="text-muted">알림 ID:</th>
                                        <td>{{ $notification->id }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">생성일시:</th>
                                        <td>{{ date('Y-m-d H:i:s', strtotime($notification->created_at)) }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">알림 유형:</th>
                                        <td>
                                            <span class="badge bg-secondary">
                                                {{ $typeLabels[$notification->type] ?? $notification->type }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">우선순위:</th>
                                        <td>
                                            <span class="{{ $priorityClass }}">
                                                {{ ucfirst($notification->priority) }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">만료일시:</th>
                                        <td>
                                            @if($notification->expires_at)
                                                {{ date('Y-m-d H:i:s', strtotime($notification->expires_at)) }}
                                                @if(strtotime($notification->expires_at) < time())
                                                    <span class="badge bg-danger ms-1">만료됨</span>
                                                @endif
                                            @else
                                                <span class="text-muted">만료없음</span>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- 수신자 정보 -->
                        <div class="col-md-6">
                            <h6 class="mb-3">👤 수신자 정보</h6>
                            <table class="table table-sm table-borderless">
                                <tbody>
                                    <tr>
                                        <th width="120" class="text-muted">사용자 ID:</th>
                                        <td>{{ $notification->user_id }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">이름:</th>
                                        <td class="fw-bold">{{ $notification->user_name }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">이메일:</th>
                                        <td>{{ $notification->user_email }}</td>
                                    </tr>
                                    @if($notification->user_uuid)
                                        <tr>
                                            <th class="text-muted">UUID:</th>
                                            <td class="font-monospace">{{ $notification->user_uuid }}</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <th class="text-muted">읽음 상태:</th>
                                        <td>
                                            @if($notification->is_read)
                                                <span class="badge bg-success">읽음</span>
                                                @if($notification->read_at)
                                                    <br><small class="text-muted">{{ date('Y-m-d H:i:s', strtotime($notification->read_at)) }}</small>
                                                @endif
                                            @else
                                                <span class="badge bg-warning">읽지않음</span>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- 알림 내용 -->
                    <div class="row">
                        <div class="col-12">
                            <h6 class="mb-3">📝 알림 내용</h6>
                            <div class="card border-info">
                                <div class="card-header bg-info-lighten">
                                    <h6 class="mb-0 text-info">{{ $notification->title }}</h6>
                                </div>
                                <div class="card-body">
                                    <div style="white-space: pre-wrap; line-height: 1.6;">{{ $notification->message }}</div>
                                    @if($notification->action_url)
                                        <hr>
                                        <p class="mb-0">
                                            <strong>액션 URL:</strong>
                                            <a href="{{ $notification->action_url }}" target="_blank" class="btn btn-sm btn-outline-primary ms-2">
                                                🔗 링크로 이동
                                            </a>
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- 전송 채널 및 상태 -->
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-3">📱 전송 채널</h6>
                            <div class="list-group">
                                @foreach($channels as $channel)
                                    @php
                                        $channelLabels = [
                                            'web' => '웹 알림',
                                            'email' => '이메일',
                                            'sms' => 'SMS',
                                            'push' => '푸시 알림'
                                        ];
                                        $channelIcons = [
                                            'web' => '🌐',
                                            'email' => '📧',
                                            'sms' => '📱',
                                            'push' => '📲'
                                        ];
                                    @endphp
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>
                                            {{ $channelIcons[$channel] ?? '📤' }}
                                            {{ $channelLabels[$channel] ?? $channel }}
                                        </span>
                                        @if($deliveryStatus && isset($deliveryStatus[$channel]))
                                            @php $status = $deliveryStatus[$channel]; @endphp
                                            <span class="badge bg-success">{{ $status['status'] }}</span>
                                        @else
                                            <span class="badge bg-secondary">대기</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- 전송 상태 상세 -->
                        <div class="col-md-6">
                            <h6 class="mb-3">📊 전송 상태 상세</h6>
                            @if($deliveryStatus)
                                <div class="card border-success">
                                    <div class="card-body">
                                        <pre class="mb-0" style="background: #f8f9fa; padding: 15px; border-radius: 5px; font-size: 0.9em;">{{ json_encode($deliveryStatus, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    아직 전송 상태 정보가 없습니다.
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($data)
                        <hr class="my-4">

                        <!-- 관련 데이터 -->
                        <div class="row">
                            <div class="col-12">
                                <h6 class="mb-3">📊 관련 데이터</h6>
                                <div class="card border-info">
                                    <div class="card-body">
                                        <pre class="mb-0" style="background: #f8f9fa; padding: 15px; border-radius: 5px; font-size: 0.9em;">{{ json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 관련 알림 -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">🔗 동일 사용자의 최근 알림</h6>
                </div>
                <div class="card-body">
                    <div id="relatedNotifications">
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
</div>
@endsection

@push('styles')
<style>
.table th {
    font-weight: 600;
}
.card-header h6 {
    font-weight: 600;
}
pre {
    font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
    line-height: 1.4;
}
.bg-info-lighten {
    background-color: rgba(13, 202, 240, 0.1) !important;
}
</style>
@endpush

@push('scripts')
<script>
// 읽음 처리
function markAsRead() {
    if (!confirm('이 알림을 읽음 처리하시겠습니까?')) return;

    $.ajax({
        url: `/admin/partner/notifications/{{ $notification->id }}/read`,
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

// 삭제
function deleteNotification() {
    if (!confirm('이 알림을 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.')) return;

    $.ajax({
        url: `/admin/partner/notifications/{{ $notification->id }}`,
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function() {
            window.location.href = '{{ route("admin.partner.notifications.index") }}';
        },
        error: function() {
            alert('삭제에 실패했습니다.');
        }
    });
}

// 관련 알림 로드
function loadRelatedNotifications() {
    $.ajax({
        url: `/admin/partner/notifications/user/{{ $notification->user_id }}`,
        method: 'GET',
        success: function(response) {
            renderRelatedNotifications(response.notifications);
        },
        error: function() {
            $('#relatedNotifications').html('<div class="alert alert-danger">관련 알림을 불러오지 못했습니다.</div>');
        }
    });
}

// 관련 알림 렌더링
function renderRelatedNotifications(notifications) {
    const container = $('#relatedNotifications');

    if (notifications.length === 0) {
        container.html('<div class="alert alert-info">관련 알림이 없습니다.</div>');
        return;
    }

    let html = '<div class="table-responsive"><table class="table table-sm table-striped"><thead><tr>';
    html += '<th>날짜</th><th>제목</th><th>유형</th><th>우선순위</th><th>상태</th><th>액션</th>';
    html += '</tr></thead><tbody>';

    notifications.slice(0, 10).forEach(notification => {
        const currentId = parseInt("{{ $notification->id }}");
        const rowClass = notification.id === currentId ? 'table-warning' : '';

        html += `<tr class="${rowClass}">
            <td><small>${new Date(notification.created_at).toLocaleString()}</small></td>
            <td><small>${notification.title}</small></td>
            <td><span class="badge bg-secondary">${notification.type}</span></td>
            <td><span class="badge bg-info">${notification.priority}</span></td>
            <td>${notification.is_read ? '<span class="badge bg-success">읽음</span>' : '<span class="badge bg-warning">읽지않음</span>'}</td>
            <td>`;

        if (notification.id !== currentId) {
            html += `<a href="/admin/partner/notifications/${notification.id}" class="btn btn-xs btn-outline-primary">보기</a>`;
        } else {
            html += '<span class="badge bg-warning">현재</span>';
        }

        html += '</td></tr>';
    });

    html += '</tbody></table></div>';
    container.html(html);
}

// 페이지 로드시 관련 알림 로드
$(document).ready(function() {
    loadRelatedNotifications();
});
</script>
@endpush