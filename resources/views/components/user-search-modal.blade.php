{{--
회원 검색 모달 컴포넌트 (재사용 가능)

사용법:
@include('jiny-partner::components.user-search-modal', [
    'modalId' => 'customerSearchModal',
    'title' => '고객 검색',
    'placeholder' => '고객 이메일을 입력하세요',
    'onSelectCallback' => 'selectCustomer', // JavaScript 함수명
    'searchType' => 'email', // 'email', 'name', 'both'
    'verifiedOnly' => false,
    'limit' => 10
])

파라미터:
- modalId: 모달 ID (필수)
- title: 모달 제목 (기본값: '회원 검색')
- placeholder: 입력 필드 플레이스홀더
- onSelectCallback: 회원 선택 시 호출될 JavaScript 함수명 (필수)
- searchType: 검색 타입 ('email', 'name', 'both') 기본값: 'email'
- verifiedOnly: 인증된 회원만 검색 여부 (기본값: false)
- limit: 검색 결과 제한 (기본값: 10)
- showEmailBadge: 이메일 인증 뱃지 표시 여부 (기본값: true)
--}}

@props([
    'modalId' => 'userSearchModal',
    'title' => '회원 검색',
    'placeholder' => '회원 이메일을 입력하세요 (부분 검색 가능)',
    'onSelectCallback' => null,
    'searchType' => 'email',
    'verifiedOnly' => false,
    'limit' => 10,
    'showEmailBadge' => true
])

{{-- JavaScript 함수명 검증 --}}
@if(is_null($onSelectCallback))
    @php
        throw new InvalidArgumentException('onSelectCallback parameter is required for user-search-modal component');
    @endphp
@endif

{{-- 고유 ID 생성 (같은 페이지에 여러 모달이 있을 경우 충돌 방지) --}}
@php
    $uniqueId = $modalId . '_' . uniqid();
    $searchInputId = $uniqueId . '_search_input';
    $searchBtnId = $uniqueId . '_search_btn';
    $resultsId = $uniqueId . '_results';
    $alertId = $uniqueId . '_alerts';
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $modalId }}Label">
                    <i class="fe fe-users me-2"></i>{{ $title }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- 검색 폼 --}}
                <div class="mb-4">
                    <label for="{{ $searchInputId }}" class="form-label">
                        @if($searchType === 'email')
                            이메일로 검색
                        @elseif($searchType === 'name')
                            이름으로 검색
                        @else
                            이메일 또는 이름으로 검색
                        @endif
                    </label>
                    <div class="input-group">
                        <input type="{{ $searchType === 'name' ? 'text' : 'email' }}"
                               class="form-control"
                               id="{{ $searchInputId }}"
                               placeholder="{{ $placeholder }}"
                               autocomplete="off">
                        <button type="button" class="btn btn-primary" id="{{ $searchBtnId }}">
                            <i class="fe fe-search me-1"></i>검색
                        </button>
                    </div>
                    <div class="form-text">
                        <i class="fe fe-info me-1"></i>
                        @if($searchType === 'email')
                            이메일의 일부만 입력해도 검색 가능합니다. (예: "test", "jinyphp.com")
                        @elseif($searchType === 'name')
                            이름의 일부만 입력해도 검색 가능합니다. (예: "홍길", "김")
                        @else
                            이메일 또는 이름의 일부만 입력해도 검색 가능합니다.
                        @endif
                        @if($verifiedOnly)
                            <br><strong>인증된 회원만 검색됩니다.</strong>
                        @endif
                    </div>
                </div>

                {{-- 검색 알림 영역 --}}
                <div id="{{ $alertId }}"></div>

                {{-- 검색 결과 영역 --}}
                <div class="mb-3">
                    <h6 class="mb-2">검색 결과</h6>
                    <div class="border rounded" style="min-height: 200px; max-height: 400px; overflow-y: auto;">
                        <div id="{{ $resultsId }}" class="p-3 text-center text-muted">
                            <i class="fe fe-search me-2"></i>
                            검색어를 입력하고 검색 버튼을 클릭하세요.
                        </div>
                    </div>
                </div>

                {{-- 사용법 안내 --}}
                <div class="alert alert-light">
                    <h6><i class="fe fe-help-circle me-2"></i>사용법</h6>
                    <ul class="mb-0">
                        <li>
                            @if($searchType === 'email')
                                이메일 주소의 일부 또는 전체를 입력하여 회원을 검색할 수 있습니다.
                            @elseif($searchType === 'name')
                                회원 이름의 일부 또는 전체를 입력하여 검색할 수 있습니다.
                            @else
                                이메일 주소나 회원 이름으로 검색할 수 있습니다.
                            @endif
                        </li>
                        <li>검색 결과에서 원하는 회원을 클릭하면 자동으로 선택됩니다.</li>
                        <li>검색된 회원의 상세 정보도 함께 확인할 수 있습니다.</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>

{{-- 컴포넌트 전용 JavaScript --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 컴포넌트 설정
    const modalConfig_{{ str_replace('-', '_', $modalId) }} = {
        modalId: '{{ $modalId }}',
        searchInputId: '{{ $searchInputId }}',
        searchBtnId: '{{ $searchBtnId }}',
        resultsId: '{{ $resultsId }}',
        alertId: '{{ $alertId }}',
        onSelectCallback: '{{ $onSelectCallback }}',
        searchType: '{{ $searchType }}',
        verifiedOnly: {{ $verifiedOnly ? 'true' : 'false' }},
        limit: {{ $limit }},
        showEmailBadge: {{ $showEmailBadge ? 'true' : 'false' }},
        apiUrl: '{{ route('home.partner.users.search') }}'
    };

    // 검색 입력 필드와 버튼 참조
    const searchInput = document.getElementById(modalConfig_{{ str_replace('-', '_', $modalId) }}.searchInputId);
    const searchBtn = document.getElementById(modalConfig_{{ str_replace('-', '_', $modalId) }}.searchBtnId);
    const resultsContainer = document.getElementById(modalConfig_{{ str_replace('-', '_', $modalId) }}.resultsId);
    const alertContainer = document.getElementById(modalConfig_{{ str_replace('-', '_', $modalId) }}.alertId);

    // Enter 키 처리
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            performUserSearch_{{ str_replace('-', '_', $modalId) }}();
        }
    });

    // 검색 버튼 클릭
    searchBtn.addEventListener('click', function() {
        performUserSearch_{{ str_replace('-', '_', $modalId) }}();
    });

    /**
     * 회원 검색 수행
     */
    window.performUserSearch_{{ str_replace('-', '_', $modalId) }} = function() {
        const searchValue = searchInput.value.trim();

        if (searchValue.length < 2) {
            showModalAlert_{{ str_replace('-', '_', $modalId) }}('error', '최소 2자 이상 입력해주세요.');
            return;
        }

        // 버튼 로딩 상태
        searchBtn.disabled = true;
        searchBtn.innerHTML = '<i class="fe fe-loader rotating me-1"></i>검색 중...';

        // 검색 결과 초기화
        resultsContainer.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>';

        // API 요청 파라미터 구성
        const params = new URLSearchParams({
            limit: modalConfig_{{ str_replace('-', '_', $modalId) }}.limit,
            verified_only: modalConfig_{{ str_replace('-', '_', $modalId) }}.verifiedOnly
        });

        // 검색 타입에 따른 파라미터 추가
        if (modalConfig_{{ str_replace('-', '_', $modalId) }}.searchType === 'email') {
            params.append('email', searchValue);
        } else if (modalConfig_{{ str_replace('-', '_', $modalId) }}.searchType === 'name') {
            params.append('name', searchValue);
        } else {
            // 'both' - 이메일 형식이면 이메일로, 아니면 이름으로 검색
            if (searchValue.includes('@')) {
                params.append('email', searchValue);
            } else {
                params.append('name', searchValue);
            }
        }

        // AJAX 검색 요청
        fetch(`${modalConfig_{{ str_replace('-', '_', $modalId) }}.apiUrl}?${params.toString()}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            console.log('User search response:', data);

            if (data.success && data.data.users.length > 0) {
                displayUserResults_{{ str_replace('-', '_', $modalId) }}(data.data.users);
                showModalAlert_{{ str_replace('-', '_', $modalId) }}('success', `${data.data.result_count}명의 회원을 찾았습니다.`);
            } else {
                resultsContainer.innerHTML = '<div class="text-center py-4 text-muted"><i class="fe fe-search me-2"></i>검색 결과가 없습니다.</div>';
                showModalAlert_{{ str_replace('-', '_', $modalId) }}('info', '일치하는 회원이 없습니다.');
            }
        })
        .catch(error => {
            console.error('User search error:', error);
            resultsContainer.innerHTML = '<div class="text-center py-4 text-danger"><i class="fe fe-alert-circle me-2"></i>검색 중 오류가 발생했습니다.</div>';
            showModalAlert_{{ str_replace('-', '_', $modalId) }}('error', '검색 중 오류가 발생했습니다.');
        })
        .finally(() => {
            // 버튼 상태 복원
            searchBtn.disabled = false;
            searchBtn.innerHTML = '<i class="fe fe-search me-1"></i>검색';
        });
    };

    /**
     * 검색 결과 표시
     */
    function displayUserResults_{{ str_replace('-', '_', $modalId) }}(users) {
        let html = '<div class="list-group list-group-flush">';

        users.forEach(user => {
            const emailBadge = modalConfig_{{ str_replace('-', '_', $modalId) }}.showEmailBadge ?
                (user.email_verified ?
                    '<span class="badge bg-success ms-2">인증됨</span>' :
                    '<span class="badge bg-secondary ms-2">미인증</span>') : '';

            html += `
                <div class="list-group-item list-group-item-action border-0" style="cursor: pointer;"
                     onclick="window.${modalConfig_{{ str_replace('-', '_', $modalId) }}.onSelectCallback}('${user.uuid}', '${user.name}', '${user.email}', ${JSON.stringify(user).replace(/"/g, '&quot;')})">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-3">
                            <span class="avatar-initial bg-primary text-white rounded-circle">
                                ${user.name ? user.name.charAt(0).toUpperCase() : 'U'}
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">${user.name || '이름 없음'}</h6>
                            <small class="text-muted">${user.email}</small>
                            ${emailBadge}
                        </div>
                        <div class="text-end">
                            <small class="text-muted">가입일</small><br>
                            <small class="text-muted">${new Date(user.created_at).toLocaleDateString('ko-KR')}</small>
                        </div>
                    </div>
                </div>
            `;
        });

        html += '</div>';
        resultsContainer.innerHTML = html;
    }

    /**
     * 모달 내 알림 표시
     */
    function showModalAlert_{{ str_replace('-', '_', $modalId) }}(type, message) {
        const alertClass = type === 'success' ? 'alert-success' :
                         type === 'error' ? 'alert-danger' : 'alert-info';

        alertContainer.innerHTML = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="fe fe-${type === 'success' ? 'check-circle' : type === 'error' ? 'alert-circle' : 'info'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        // 3초 후 자동 제거
        setTimeout(() => {
            const alert = alertContainer.querySelector('.alert');
            if (alert) alert.remove();
        }, 3000);
    }
});
</script>