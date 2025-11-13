{{--
순수 JavaScript 사용자 검색 입력 커스텀 컴포넌트

사용법:
@include('jiny-partner::components.user-search-input', [
    'name' => 'customer_name',
    'label' => '고객명',
    'placeholder' => '고객 이름을 직접 입력하거나 회원 검색을 이용하세요',
    'searchType' => 'email',
    'verifiedOnly' => false,
    'limit' => 10,
    'required' => false,
    'buttonText' => '회원 검색',
    'showEmailBadge' => true,
    'helpText' => '기존 회원을 검색하면 이메일 정보도 자동으로 설정됩니다.',
    'value' => ''
])

파라미터:
- name: input 필드의 name 속성 (필수)
- label: 라벨 텍스트 (기본값: '사용자 검색')
- placeholder: 입력 필드 플레이스홀더
- searchType: 검색 타입 ('email', 'name', 'both') 기본값: 'email'
- verifiedOnly: 인증된 회원만 검색 여부 (기본값: false)
- limit: 검색 결과 제한 (기본값: 10)
- required: 필수 입력 여부 (기본값: false)
- buttonText: 검색 버튼 텍스트 (기본값: '사용자 검색')
- showEmailBadge: 이메일 인증 뱃지 표시 여부 (기본값: true)
- helpText: 도움말 텍스트
- value: 초기값
- onSelect: 사용자 선택 시 호출할 JavaScript 함수명 (선택사항)
- onClear: 선택 해제 시 호출할 JavaScript 함수명 (선택사항)
--}}

@props([
    'name' => 'user_search',
    'label' => '사용자 검색',
    'placeholder' => '사용자 이름을 직접 입력하거나 검색을 이용하세요',
    'searchType' => 'email',
    'verifiedOnly' => false,
    'limit' => 10,
    'required' => false,
    'buttonText' => '사용자 검색',
    'showEmailBadge' => true,
    'helpText' => '기존 회원을 검색하면 이메일 정보도 자동으로 설정됩니다.',
    'value' => '',
    'onSelect' => null,
    'onClear' => null
])

@php
    $uniqueId = 'user_search_' . uniqid();
    $componentId = str_replace(['-', '.'], '_', $uniqueId);
    $inputId = $uniqueId . '_input';
    $modalId = $uniqueId . '_modal';
    $searchInputId = $uniqueId . '_search';
    $resultsId = $uniqueId . '_results';
    $alertId = $uniqueId . '_alert';
    $selectedInfoId = $uniqueId . '_selected_info';
@endphp

<div class="user-search-component" id="{{ $componentId }}">
    <label for="{{ $inputId }}" class="form-label">
        {{ $label }}
        @if($required)<span class="text-danger">*</span>@endif
    </label>

    <div class="input-group">
        <input
            type="text"
            class="form-control"
            id="{{ $inputId }}"
            name="{{ $name }}"
            placeholder="{{ $placeholder }}"
            value="{{ $value }}"
            @if($required) required @endif
        >
        <button
            type="button"
            class="btn btn-outline-primary"
            id="{{ $componentId }}_search_btn"
            data-bs-toggle="modal"
            data-bs-target="#{{ $modalId }}"
        >
            <i class="fe fe-search me-1"></i>{{ $buttonText }}
        </button>
    </div>

    <div class="invalid-feedback"></div>

    @if($helpText)
    <div class="form-text">
        <i class="fe fe-info me-1"></i>{{ $helpText }}
    </div>
    @endif

    {{-- 선택된 사용자 정보 표시 영역 --}}
    <div id="{{ $selectedInfoId }}" class="mt-2" style="display: none;">
        <div class="alert alert-info d-flex align-items-center">
            <i class="fe fe-user me-2"></i>
            <div class="flex-grow-1">
                <strong id="{{ $componentId }}_selected_name"></strong>
                <small class="d-block text-muted" id="{{ $componentId }}_selected_email"></small>
            </div>
            <button type="button" class="btn-close btn-sm" onclick="userSearchComponents['{{ $componentId }}'].clearSelection()"></button>
        </div>
    </div>

    {{-- 검색 모달 --}}
    <div class="modal fade" id="{{ $modalId }}" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fe fe-users me-2"></i>{{ $buttonText }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                            <input
                                type="{{ $searchType === 'name' ? 'text' : 'email' }}"
                                class="form-control"
                                id="{{ $searchInputId }}"
                                placeholder="{{ $searchType === 'email' ? '이메일을 입력하세요' : '이름을 입력하세요' }}"
                                autocomplete="off"
                            >
                            <button
                                type="button"
                                class="btn btn-primary"
                                id="{{ $componentId }}_perform_search_btn"
                            >
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

                    {{-- 알림 영역 --}}
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
</div>

{{-- 순수 JavaScript 컴포넌트 스크립트 --}}
<script>
// 전역 컴포넌트 저장소
window.userSearchComponents = window.userSearchComponents || {};

// 컴포넌트 초기화
document.addEventListener('DOMContentLoaded', function() {
    const componentId = '{{ $componentId }}';

    // 컴포넌트 인스턴스 생성
    window.userSearchComponents[componentId] = new UserSearchComponent({
        componentId: componentId,
        inputId: '{{ $inputId }}',
        modalId: '{{ $modalId }}',
        searchInputId: '{{ $searchInputId }}',
        resultsId: '{{ $resultsId }}',
        alertId: '{{ $alertId }}',
        selectedInfoId: '{{ $selectedInfoId }}',
        searchType: @json($searchType),
        verifiedOnly: @json($verifiedOnly),
        limit: @json($limit),
        showEmailBadge: @json($showEmailBadge),
        apiUrl: @json(route('home.partner.users.search')),
        onSelect: @json($onSelect),
        onClear: @json($onClear),
        initialValue: @json($value)
    });
});

/**
 * 사용자 검색 컴포넌트 클래스
 */
class UserSearchComponent {
    constructor(config) {
        this.config = config;
        this.selectedUser = {
            uuid: '',
            name: config.initialValue || '',
            email: ''
        };
        this.modal = null;
        this.searching = false;

        this.init();
    }

    init() {
        // DOM 요소 참조
        this.elements = {
            input: document.getElementById(this.config.inputId),
            searchInput: document.getElementById(this.config.searchInputId),
            performSearchBtn: document.getElementById(this.config.componentId + '_perform_search_btn'),
            results: document.getElementById(this.config.resultsId),
            alert: document.getElementById(this.config.alertId),
            selectedInfo: document.getElementById(this.config.selectedInfoId),
            selectedName: document.getElementById(this.config.componentId + '_selected_name'),
            selectedEmail: document.getElementById(this.config.componentId + '_selected_email')
        };

        // Bootstrap 모달 초기화
        const modalElement = document.getElementById(this.config.modalId);
        this.modal = new bootstrap.Modal(modalElement);

        // 이벤트 리스너 등록
        this.bindEvents();

        // 초기값 설정
        if (this.config.initialValue) {
            this.elements.input.value = this.config.initialValue;
        }
    }

    bindEvents() {
        // 검색 버튼 클릭
        this.elements.performSearchBtn.addEventListener('click', () => {
            this.performSearch();
        });

        // 검색 입력 필드에서 Enter 키
        this.elements.searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.performSearch();
            }
        });

        // 모달이 열릴 때 초기화
        document.getElementById(this.config.modalId).addEventListener('shown.bs.modal', () => {
            this.resetSearch();
            this.elements.searchInput.focus();
        });
    }

    async performSearch() {
        const query = this.elements.searchInput.value.trim();

        if (query.length < 2) {
            this.showAlert('error', '최소 2자 이상 입력해주세요.');
            return;
        }

        this.setLoading(true);
        this.clearAlert();

        try {
            const params = new URLSearchParams({
                limit: this.config.limit,
                verified_only: this.config.verifiedOnly
            });

            // 검색 타입에 따른 파라미터 추가
            if (this.config.searchType === 'email') {
                params.append('email', query);
            } else if (this.config.searchType === 'name') {
                params.append('name', query);
            } else {
                // 'both' - 이메일 형식이면 이메일로, 아니면 이름으로 검색
                if (this.isEmail(query)) {
                    params.append('email', query);
                } else {
                    params.append('name', query);
                }
            }

            const response = await fetch(`${this.config.apiUrl}?${params.toString()}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (!response.ok) {
                console.error('HTTP Error:', response.status, data);
                this.displayError();
                this.showAlert('error', data.message || `HTTP ${response.status} 오류가 발생했습니다.`);
                return;
            }

            if (data.success && data.data && data.data.users && data.data.users.length > 0) {
                this.displayResults(data.data.users);
                this.showAlert('success', `${data.data.result_count}명의 회원을 찾았습니다.`);
            } else {
                this.displayNoResults();
                const message = data.success ? '일치하는 회원이 없습니다.' : (data.message || '검색에 실패했습니다.');
                this.showAlert('info', message);
            }

        } catch (error) {
            console.error('User search error:', error);
            this.displayError();
            this.showAlert('error', '검색 중 네트워크 오류가 발생했습니다.');
        } finally {
            this.setLoading(false);
        }
    }

    displayResults(users) {
        let html = '<div class="list-group list-group-flush">';

        users.forEach(user => {
            const emailBadge = this.config.showEmailBadge ?
                (user.email_verified ?
                    '<span class="badge bg-success ms-2">인증됨</span>' :
                    '<span class="badge bg-secondary ms-2">미인증</span>') : '';

            html += `
                <div class="list-group-item list-group-item-action border-0 cursor-pointer"
                     onclick="userSearchComponents['${this.config.componentId}'].selectUser(${this.escapeJson(user)})">
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
                            <small class="text-muted">${this.formatDate(user.created_at)}</small>
                        </div>
                    </div>
                </div>
            `;
        });

        html += '</div>';
        this.elements.results.innerHTML = html;
    }

    displayNoResults() {
        this.elements.results.innerHTML = `
            <div class="text-center py-4 text-muted">
                <i class="fe fe-search me-2"></i>검색 결과가 없습니다.
            </div>
        `;
    }

    displayError() {
        this.elements.results.innerHTML = `
            <div class="text-center py-4 text-danger">
                <i class="fe fe-alert-circle me-2"></i>검색 중 오류가 발생했습니다.
            </div>
        `;
    }

    selectUser(user) {
        this.selectedUser = {
            uuid: user.uuid,
            name: user.name,
            email: user.email,
            ...user
        };

        // 입력 필드 업데이트
        this.elements.input.value = user.name;

        // 선택된 정보 표시
        this.elements.selectedName.textContent = user.name;
        this.elements.selectedEmail.textContent = user.email;
        this.elements.selectedInfo.style.display = 'block';

        // 모달 닫기
        this.modal.hide();

        // 사용자 정의 콜백 호출
        if (this.config.onSelect && typeof window[this.config.onSelect] === 'function') {
            window[this.config.onSelect](user.uuid, user.name, user.email, user);
        }

        // 커스텀 이벤트 발생
        this.dispatchEvent('user-selected', { user: this.selectedUser });

        this.showGlobalAlert('success', `${user.name} 사용자를 선택했습니다.`);
    }

    clearSelection() {
        this.selectedUser = { uuid: '', name: '', email: '' };
        this.elements.input.value = '';
        this.elements.selectedInfo.style.display = 'none';

        // 사용자 정의 콜백 호출
        if (this.config.onClear && typeof window[this.config.onClear] === 'function') {
            window[this.config.onClear]();
        }

        // 커스텀 이벤트 발생
        this.dispatchEvent('user-cleared');
    }

    resetSearch() {
        this.elements.searchInput.value = '';
        this.elements.results.innerHTML = `
            <div class="p-3 text-center text-muted">
                <i class="fe fe-search me-2"></i>
                검색어를 입력하고 검색 버튼을 클릭하세요.
            </div>
        `;
        this.clearAlert();
    }

    setLoading(loading) {
        this.searching = loading;
        this.elements.performSearchBtn.disabled = loading;

        if (loading) {
            this.elements.performSearchBtn.innerHTML = '<i class="fe fe-loader rotating me-1"></i>검색 중...';
            this.elements.results.innerHTML = `
                <div class="text-center py-3">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            `;
        } else {
            this.elements.performSearchBtn.innerHTML = '<i class="fe fe-search me-1"></i>검색';
        }
    }

    showAlert(type, message) {
        const alertClass = type === 'success' ? 'alert-success' :
                         type === 'error' ? 'alert-danger' : 'alert-info';

        const iconClass = type === 'success' ? 'check-circle' :
                         type === 'error' ? 'alert-circle' : 'info';

        this.elements.alert.innerHTML = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="fe fe-${iconClass} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        // 3초 후 자동 제거
        setTimeout(() => {
            this.clearAlert();
        }, 3000);
    }

    clearAlert() {
        this.elements.alert.innerHTML = '';
    }

    showGlobalAlert(type, message) {
        // 페이지에 showAlert 함수가 있으면 사용
        if (typeof window.showAlert === 'function') {
            window.showAlert(type, message);
        } else {
            // 기본 alert 사용
            alert(message);
        }
    }

    isEmail(value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(value);
    }

    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('ko-KR');
    }

    escapeJson(obj) {
        return JSON.stringify(obj).replace(/"/g, '&quot;');
    }

    dispatchEvent(eventName, detail = {}) {
        const event = new CustomEvent('user-search-' + eventName, {
            detail: detail,
            bubbles: true
        });
        document.getElementById(this.config.componentId).dispatchEvent(event);
    }

    // 공용 메서드들
    getSelectedUser() {
        return this.selectedUser;
    }

    setSelectedUser(user) {
        this.selectUser(user);
    }
}
</script>

{{-- 스타일 --}}
<style>
.user-search-component .cursor-pointer {
    cursor: pointer;
}

.user-search-component .rotating {
    animation: user-search-rotation 1s infinite linear;
}

@keyframes user-search-rotation {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(359deg);
    }
}

.user-search-component .list-group-item:hover {
    background-color: #f8f9fa;
}
</style>