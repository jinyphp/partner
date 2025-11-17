@extends('jiny-partner::layouts.admin.sidebar')

@section('title', '새 알림 생성')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">📤 새 알림 생성</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="/admin">관리자</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.partner.index') }}">파트너</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.partner.notifications.index') }}">알림</a></li>
                        <li class="breadcrumb-item active">새 알림</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.partner.notifications.store') }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-lg-8">
                <!-- 기본 정보 -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">📋 기본 정보</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="user_id" class="form-label">수신자 <span class="text-danger">*</span></label>
                                <select name="user_id" id="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                                    <option value="">수신자를 선택하세요</option>
                                    <!-- 실제 구현 시 AJAX로 사용자 목록 로드 -->
                                </select>
                                @error('user_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">알림을 받을 사용자를 선택하세요</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="type" class="form-label">알림 유형 <span class="text-danger">*</span></label>
                                <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                                    <option value="">알림 유형을 선택하세요</option>
                                    @foreach($notificationTypes as $key => $label)
                                        <option value="{{ $key }}" {{ old('type') == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="priority" class="form-label">우선순위 <span class="text-danger">*</span></label>
                                <select name="priority" id="priority" class="form-select @error('priority') is-invalid @enderror" required>
                                    @foreach($priorities as $key => $label)
                                        <option value="{{ $key }}" {{ old('priority', 'normal') == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('priority')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="expires_at" class="form-label">만료일시</label>
                                <input type="datetime-local" name="expires_at" id="expires_at"
                                       class="form-control @error('expires_at') is-invalid @enderror"
                                       value="{{ old('expires_at') }}">
                                @error('expires_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">비워두면 만료되지 않습니다</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label">제목 <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="title"
                                   class="form-control @error('title') is-invalid @enderror"
                                   value="{{ old('title') }}" maxlength="200" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">최대 200자</small>
                        </div>

                        <div class="mb-3">
                            <label for="message" class="form-label">메시지 <span class="text-danger">*</span></label>
                            <textarea name="message" id="message"
                                      class="form-control @error('message') is-invalid @enderror"
                                      rows="5" required>{{ old('message') }}</textarea>
                            @error('message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="action_url" class="form-label">액션 URL</label>
                            <input type="url" name="action_url" id="action_url"
                                   class="form-control @error('action_url') is-invalid @enderror"
                                   value="{{ old('action_url') }}" maxlength="500">
                            @error('action_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">사용자가 클릭할 때 이동할 URL</small>
                        </div>
                    </div>
                </div>

                <!-- 관련 데이터 -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">📊 관련 데이터</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="data" class="form-label">JSON 데이터</label>
                            <textarea name="data" id="data"
                                      class="form-control @error('data') is-invalid @enderror"
                                      rows="4" placeholder='{"key": "value"}'
                                      style="font-family: monospace;">{{ old('data') }}</textarea>
                            @error('data')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">JSON 형태로 추가 데이터를 입력하세요 (선택사항)</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- 전송 채널 -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">📱 전송 채널</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">전송할 채널 선택 <span class="text-danger">*</span></label>
                            @foreach($channels as $key => $label)
                                <div class="form-check">
                                    <input type="checkbox" name="channels[]" value="{{ $key }}"
                                           id="channel_{{ $key }}" class="form-check-input"
                                           {{ (is_array(old('channels')) && in_array($key, old('channels'))) || (!old('channels') && $key === 'web') ? 'checked' : '' }}>
                                    <label for="channel_{{ $key }}" class="form-check-label">
                                        @switch($key)
                                            @case('web')
                                                🌐 {{ $label }}
                                                @break
                                            @case('email')
                                                📧 {{ $label }}
                                                @break
                                            @case('sms')
                                                📱 {{ $label }}
                                                @break
                                            @case('push')
                                                📲 {{ $label }}
                                                @break
                                            @default
                                                📤 {{ $label }}
                                        @endswitch
                                    </label>
                                </div>
                            @endforeach
                            @error('channels')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- 미리보기 -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">👁️ 미리보기</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info" id="preview">
                            <h6 id="preview-title">알림 제목</h6>
                            <p id="preview-message" class="mb-0">알림 내용을 입력하면 여기에 미리보기가 표시됩니다.</p>
                            <hr>
                            <small class="text-muted">
                                <span id="preview-type">-</span> |
                                <span id="preview-priority">보통</span> 우선순위
                            </small>
                        </div>
                    </div>
                </div>

                <!-- 액션 버튼 -->
                <div class="card">
                    <div class="card-body text-center">
                        <button type="submit" class="btn btn-primary btn-lg w-100 mb-2">
                            📤 알림 생성 및 전송
                        </button>
                        <a href="{{ route('admin.partner.notifications.index') }}" class="btn btn-outline-secondary w-100">
                            ❌ 취소
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
.form-label {
    font-weight: 600;
}
.required {
    color: #dc3545;
}
#preview {
    min-height: 100px;
}
#data {
    font-size: 0.9em;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // 사용자 검색 및 선택
    $('#user_id').select2({
        placeholder: '사용자 이름 또는 이메일로 검색...',
        allowClear: true,
        ajax: {
            url: '/admin/partner/users/search',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term
                };
            },
            processResults: function (data) {
                return {
                    results: data.map(user => ({
                        id: user.id,
                        text: `${user.name} (${user.email})`
                    }))
                };
            },
            cache: true
        },
        minimumInputLength: 2
    });

    // 실시간 미리보기
    function updatePreview() {
        const title = $('#title').val() || '알림 제목';
        const message = $('#message').val() || '알림 내용을 입력하면 여기에 미리보기가 표시됩니다.';
        const type = $('#type').val();
        const priority = $('#priority').val();

        $('#preview-title').text(title);
        $('#preview-message').text(message);

        const typeLabels = {
            'status_update': '상태 변경',
            'interview_scheduled': '면접 일정',
            'approved': '승인 완료',
            'rejected': '신청 거부',
            'reapply_available': '재신청 가능',
            'tier_upgraded': '등급 승급',
            'performance_alert': '성과 알림'
        };

        const priorityLabels = {
            'low': '낮음',
            'normal': '보통',
            'high': '높음',
            'urgent': '긴급'
        };

        $('#preview-type').text(typeLabels[type] || '-');
        $('#preview-priority').text(priorityLabels[priority] || '보통');

        // 우선순위에 따른 스타일 변경
        const alertClass = priority === 'urgent' ? 'alert-danger' :
                          priority === 'high' ? 'alert-warning' :
                          priority === 'normal' ? 'alert-info' : 'alert-secondary';

        $('#preview').removeClass('alert-info alert-warning alert-danger alert-secondary').addClass(alertClass);
    }

    // 입력값 변경 시 미리보기 업데이트
    $('#title, #message, #type, #priority').on('input change', updatePreview);

    // JSON 데이터 검증
    $('#data').on('blur', function() {
        const jsonData = $(this).val().trim();
        if (jsonData && jsonData !== '') {
            try {
                JSON.parse(jsonData);
                $(this).removeClass('is-invalid').addClass('is-valid');
            } catch (e) {
                $(this).removeClass('is-valid').addClass('is-invalid');
                // 에러 피드백 추가
                if (!$(this).next('.invalid-feedback').length) {
                    $(this).after('<div class="invalid-feedback">올바른 JSON 형식이 아닙니다.</div>');
                }
            }
        } else {
            $(this).removeClass('is-invalid is-valid');
        }
    });

    // 폼 제출 전 유효성 검사
    $('form').on('submit', function(e) {
        const channels = $('input[name="channels[]"]:checked').length;
        if (channels === 0) {
            e.preventDefault();
            alert('최소 하나의 전송 채널을 선택해야 합니다.');
            return false;
        }

        const jsonData = $('#data').val().trim();
        if (jsonData && jsonData !== '') {
            try {
                JSON.parse(jsonData);
            } catch (e) {
                e.preventDefault();
                alert('관련 데이터의 JSON 형식이 올바르지 않습니다.');
                return false;
            }
        }

        return true;
    });

    // 초기 미리보기 업데이트
    updatePreview();
});

// 템플릿 적용 함수
function applyTemplate(type) {
    const templates = {
        'status_update': {
            title: '파트너 상태가 변경되었습니다',
            message: '안녕하세요!\n\n귀하의 파트너 상태가 변경되었습니다. 자세한 내용은 대시보드에서 확인하실 수 있습니다.\n\n감사합니다.'
        },
        'interview_scheduled': {
            title: '면접 일정이 확정되었습니다',
            message: '안녕하세요!\n\n면접 일정이 확정되었습니다.\n\n일시: [면접 일시]\n장소: [면접 장소]\n\n준비사항을 확인하시고 시간에 맞춰 참석해 주세요.\n\n감사합니다.'
        },
        'approved': {
            title: '파트너 승인이 완료되었습니다! 🎉',
            message: '축하합니다!\n\n파트너 승인이 완료되었습니다. 이제 파트너 활동을 시작하실 수 있습니다.\n\n대시보드에서 자세한 정보를 확인해 보세요.\n\n환영합니다!'
        },
        'rejected': {
            title: '파트너 신청 검토 결과',
            message: '안녕하세요!\n\n파트너 신청을 신중히 검토한 결과, 현재는 승인이 어려운 상황입니다.\n\n재신청 가능 시기와 개선사항에 대해서는 별도로 안내드리겠습니다.\n\n감사합니다.'
        }
    };

    if (templates[type]) {
        $('#title').val(templates[type].title);
        $('#message').val(templates[type].message);
        updatePreview();
    }
}

// 유형 선택 시 템플릿 적용 제안
$('#type').on('change', function() {
    const type = $(this).val();
    if (type && !$('#title').val() && !$('#message').val()) {
        if (confirm('선택한 유형에 맞는 템플릿을 적용하시겠습니까?')) {
            applyTemplate(type);
        }
    }
});
</script>
@endpush