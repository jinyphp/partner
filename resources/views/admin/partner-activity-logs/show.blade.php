@extends('jiny-partner::layouts.admin.sidebar')

@section('title', '활동 로그 상세')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">📋 활동 로그 상세</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="/admin">관리자</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.partner.index') }}">파트너</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.partner.activity.logs.index') }}">활동 로그</a></li>
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
                            활동 로그 #{{ $log->id }}
                            @php
                                $typeClass = match($log->activity_type) {
                                    'approved' => 'badge bg-success',
                                    'rejected' => 'badge bg-danger',
                                    'status_changed' => 'badge bg-warning',
                                    'tier_changed' => 'badge bg-info',
                                    'application_submitted' => 'badge bg-primary',
                                    default => 'badge bg-secondary'
                                };
                                $typeLabels = [
                                    'application_submitted' => '신청서 제출',
                                    'status_changed' => '상태 변경',
                                    'interview_scheduled' => '면접 일정 설정',
                                    'approved' => '승인 완료',
                                    'rejected' => '신청 거부',
                                    'reapplied' => '재신청',
                                    'tier_changed' => '등급 변경',
                                    'performance_updated' => '성과 업데이트'
                                ];
                            @endphp
                            <span class="{{ $typeClass }} ms-2">
                                {{ $typeLabels[$log->activity_type] ?? $log->activity_type }}
                            </span>
                        </h5>
                        <div class="btn-group">
                            <a href="{{ route('admin.partner.activity.logs.index') }}" class="btn btn-outline-secondary">
                                ⬅️ 목록으로
                            </a>
                            @if($log->partner_id)
                                <a href="{{ route('admin.partner.users.show', $log->partner_id) }}" class="btn btn-outline-primary">
                                    👤 파트너 보기
                                </a>
                            @endif
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
                                        <th width="120" class="text-muted">로그 ID:</th>
                                        <td>{{ $log->id }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">활동 시간:</th>
                                        <td>{{ date('Y-m-d H:i:s', strtotime($log->created_at)) }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">활동 유형:</th>
                                        <td>
                                            <span class="{{ $typeClass }}">
                                                {{ $typeLabels[$log->activity_type] ?? $log->activity_type }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">작업 수행자:</th>
                                        <td>
                                            @if($log->user_name)
                                                <div class="fw-bold">{{ $log->user_name }}</div>
                                                <small class="text-muted">{{ $log->user_email }}</small>
                                                @if($log->user_uuid)
                                                    <br><small class="font-monospace text-muted">{{ $log->user_uuid }}</small>
                                                @endif
                                            @else
                                                <span class="text-muted">알 수 없음</span>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- 파트너 정보 -->
                        <div class="col-md-6">
                            <h6 class="mb-3">👤 파트너 정보</h6>
                            <table class="table table-sm table-borderless">
                                <tbody>
                                    @if($log->partner_id)
                                        <tr>
                                            <th width="120" class="text-muted">파트너 ID:</th>
                                            <td>{{ $log->partner_id }}</td>
                                        </tr>
                                        <tr>
                                            <th class="text-muted">파트너명:</th>
                                            <td class="fw-bold">{{ $log->partner_name }}</td>
                                        </tr>
                                        <tr>
                                            <th class="text-muted">이메일:</th>
                                            <td>{{ $log->partner_email }}</td>
                                        </tr>
                                        @if($log->partner_code)
                                            <tr>
                                                <th class="text-muted">파트너 코드:</th>
                                                <td class="font-monospace">{{ $log->partner_code }}</td>
                                            </tr>
                                        @endif
                                    @else
                                        <tr>
                                            <td colspan="2" class="text-muted">파트너 정보가 없습니다</td>
                                        </tr>
                                    @endif

                                    @if($log->application_id)
                                        <tr>
                                            <th class="text-muted">신청서 ID:</th>
                                            <td>
                                                {{ $log->application_id }}
                                                @if($log->application_status)
                                                    <span class="badge bg-info ms-2">{{ $log->application_status }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- 변경 내역 -->
                    <div class="row">
                        <div class="col-12">
                            <h6 class="mb-3">🔄 변경 내역</h6>
                            @if($log->old_value || $log->new_value)
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card border-danger">
                                            <div class="card-header bg-danger-lighten text-danger">
                                                <h6 class="mb-0">이전 값</h6>
                                            </div>
                                            <div class="card-body">
                                                @if($log->old_value)
                                                    <pre class="mb-0" style="white-space: pre-wrap; font-size: 0.9em;">{{ $log->old_value }}</pre>
                                                @else
                                                    <span class="text-muted">없음</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-success">
                                            <div class="card-header bg-success-lighten text-success">
                                                <h6 class="mb-0">새로운 값</h6>
                                            </div>
                                            <div class="card-body">
                                                @if($log->new_value)
                                                    <pre class="mb-0" style="white-space: pre-wrap; font-size: 0.9em;">{{ $log->new_value }}</pre>
                                                @else
                                                    <span class="text-muted">없음</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    이 활동에는 값 변경 내역이 없습니다.
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($metadata)
                        <hr class="my-4">

                        <!-- 메타데이터 -->
                        <div class="row">
                            <div class="col-12">
                                <h6 class="mb-3">📊 메타데이터</h6>
                                <div class="card border-info">
                                    <div class="card-body">
                                        <pre class="mb-0" style="background: #f8f9fa; padding: 15px; border-radius: 5px; font-size: 0.9em;">{{ json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($log->notes)
                        <hr class="my-4">

                        <!-- 메모 -->
                        <div class="row">
                            <div class="col-12">
                                <h6 class="mb-3">📝 메모</h6>
                                <div class="alert alert-light">
                                    <div style="white-space: pre-wrap;">{{ $log->notes }}</div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <hr class="my-4">

                    <!-- 접속 정보 -->
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-3">🌐 접속 정보</h6>
                            <table class="table table-sm table-borderless">
                                <tbody>
                                    <tr>
                                        <th width="120" class="text-muted">IP 주소:</th>
                                        <td class="font-monospace">{{ $log->ip_address ?: '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted">User Agent:</th>
                                        <td style="word-break: break-all; font-size: 0.85em;">
                                            {{ $log->user_agent ?: '-' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- 관련 로그 -->
                        <div class="col-md-6">
                            <h6 class="mb-3">🔗 관련 로그</h6>
                            @if($log->partner_id)
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="loadRelatedLogs({{ $log->partner_id }}, 'partner')">
                                    이 파트너의 다른 로그 보기
                                </button>
                            @endif
                            @if($log->application_id)
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="loadRelatedLogs({{ $log->application_id }}, 'application')">
                                    이 신청서의 다른 로그 보기
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- 관련 로그 표시 영역 -->
                    <div id="relatedLogsContainer" class="d-none">
                        <hr class="my-4">
                        <h6 class="mb-3">📋 관련 로그</h6>
                        <div id="relatedLogsContent">
                            <!-- 동적으로 로드됨 -->
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
.bg-danger-lighten {
    background-color: rgba(220, 53, 69, 0.1) !important;
}
.bg-success-lighten {
    background-color: rgba(25, 135, 84, 0.1) !important;
}
</style>
@endpush

@push('scripts')
<script>
function loadRelatedLogs(id, type) {
    const container = $('#relatedLogsContainer');
    const content = $('#relatedLogsContent');

    container.removeClass('d-none');
    content.html('<div class="text-center py-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">로딩중...</span></div></div>');

    const url = type === 'partner'
        ? "{{ route('admin.partner.activity.logs.partner', ':id') }}".replace(':id', id)
        : "{{ route('admin.partner.activity.logs.application', ':id') }}".replace(':id', id);

    $.ajax({
        url: url,
        method: 'GET',
        success: function(response) {
            renderRelatedLogs(response.logs, type);
        },
        error: function() {
            content.html('<div class="alert alert-danger">관련 로그를 불러오지 못했습니다.</div>');
        }
    });
}

function renderRelatedLogs(logs, type) {
    const content = $('#relatedLogsContent');

    if (logs.length === 0) {
        content.html('<div class="alert alert-info">관련 로그가 없습니다.</div>');
        return;
    }

    let html = '<div class="table-responsive"><table class="table table-sm table-striped"><thead><tr>';
    html += '<th>날짜</th><th>활동 유형</th><th>이전 값</th><th>새로운 값</th><th>작업자</th><th>액션</th>';
    html += '</tr></thead><tbody>';

    logs.forEach(log => {
        const currentId = parseInt("{{ $log->id }}");
        const rowClass = log.id === currentId ? 'table-warning' : '';

        html += `<tr class="${rowClass}">
            <td><small>${new Date(log.created_at).toLocaleString()}</small></td>
            <td><span class="badge bg-secondary">${log.activity_type}</span></td>
            <td><small>${log.old_value || '-'}</small></td>
            <td><small>${log.new_value || '-'}</small></td>
            <td><small>${log.user_name || '-'}</small></td>
            <td>`;

        if (log.id !== currentId) {
            html += `<a href="{{ route('admin.partner.activity.logs.show', ':id') }}".replace(':id', log.id) class="btn btn-xs btn-outline-primary">보기</a>`;
        } else {
            html += '<span class="badge bg-warning">현재</span>';
        }

        html += '</td></tr>';
    });

    html += '</tbody></table></div>';
    content.html(html);
}
</script>
@endpush
