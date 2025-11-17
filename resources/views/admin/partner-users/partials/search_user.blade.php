{{-- 선택된 사용자 정보 (숨김 필드) --}}
<input type="hidden" name="user_id" id="user_id" value="{{ old('user_id', isset($item) ? $item->user_id : '') }}">
<input type="hidden" name="user_table" id="user_table" value="{{ old('user_table', isset($item) ? $item->user_table : '') }}">
<input type="hidden" name="user_uuid" id="user_uuid" value="{{ old('user_uuid', isset($item) ? $item->user_uuid : '') }}">
<input type="hidden" name="shard_number" id="shard_number" value="{{ old('shard_number', isset($item) ? $item->shard_number : '') }}">

{{-- 회원정보 카드 --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fe fe-user me-2"></i>회원정보
        </h5>
    </div>
    <div class="card-body">

        {{-- 사용자 검색 안내 --}}
        {{-- <div class="alert alert-warning border-warning">
            <div class="d-flex align-items-center">
                <i class="fe fe-search text-warning me-2" style="font-size: 1.2rem;"></i>
                <div class="flex-grow-1">
                    @if(isset($item))
                        <strong class="text-warning">사용자 변경 필수:</strong> 다른 사용자로 변경하려면 이메일을 검색하여 새 사용자를 선택하세요.
                    @else
                        <strong class="text-warning">사용자 검색 필수:</strong> 이메일을 입력하여 샤딩된 사용자 테이블에서 등록할 사용자를 검색하세요.
                    @endif
                    <br>
                    <small class="text-muted">
                        <i class="fe fe-alert-triangle me-1"></i>
                        이메일과 이름은 직접 입력할 수 없습니다. 반드시 검색 기능을 사용하여 사용자를 선택해야 합니다.
                    </small>
                </div>
            </div>
        </div> --}}

        {{-- 사용자 이메일 검색 --}}
        <div class="mb-3">
            <label for="search_email" class="form-label fw-bold">
                <i class="fe fe-search me-1 text-primary"></i>
                사용자 이메일 검색 <span class="text-danger">*</span>
                <span class="badge bg-primary ms-2">필수</span>
            </label>
            <div class="input-group">
                <span class="input-group-text bg-primary text-white">
                    <i class="fe fe-search"></i>
                </span>
                <input type="text"
                       class="form-control border-primary"
                       id="search_email"
                       placeholder="이메일 주소로 사용자 검색... (예: user@example.com)"
                       value="{{ old('search_email', isset($item) ? $item->email : '') }}"
                       onkeypress="if(event.key==='Enter') searchUsers()"
                       required>
                <button type="button" class="btn btn-primary" onclick="searchUsers()">
                    <i class="fe fe-search me-1"></i>검색
                </button>
            </div>
            <div class="d-flex align-items-center mt-1">
                <i class="fe fe-help-circle text-muted me-1"></i>
                <small class="text-muted">
                    부분 이메일로도 검색 가능합니다 (예: hojin, @jinyphp.com, test029)
                </small>
            </div>
        </div>

        {{-- 검색 결과 표시 영역 --}}
        <div id="search_results" class="mb-4" style="display: none;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label mb-0">검색 결과</label>
                <div id="search_pagination_info" class="small text-muted"></div>
            </div>
            <div class="border rounded">
                <div id="search_results_content" class="p-3">
                    {{-- 검색 결과가 여기에 표시됩니다 --}}
                </div>
                <div id="search_pagination" class="border-top p-3 bg-light" style="display: none;">
                    {{-- 페이지네이션이 여기에 표시됩니다 --}}
                </div>
            </div>
        </div>

        {{-- 선택된 사용자 기본 정보 (검색을 통해서만 입력 가능) --}}
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="email" class="form-label">
                    이메일 <span class="text-danger">*</span>
                    <small class="text-muted">(검색을 통해 자동 입력됨)</small>
                </label>
                <div class="input-group">
                    <input type="email"
                           class="form-control @error('email') is-invalid @enderror bg-light"
                           name="email"
                           id="email"
                           value="{{ old('email', isset($item) ? $item->email : '') }}"
                           placeholder="위의 검색 기능을 사용하여 사용자를 선택하세요"
                           readonly>
                    <span class="input-group-text">
                        <i class="fe fe-lock text-muted" title="검색을 통해서만 입력 가능"></i>
                    </span>
                </div>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="text-info">
                    <i class="fe fe-info-circle me-1"></i>
                    이메일은 위의 검색 기능을 통해서만 입력할 수 있습니다
                </small>
            </div>
            <div class="col-md-6">
                <label for="name" class="form-label">
                    이름 <span class="text-danger">*</span>
                    <small class="text-muted">(검색을 통해 자동 입력됨)</small>
                </label>
                <div class="input-group">
                    <input type="text"
                           class="form-control @error('name') is-invalid @enderror bg-light"
                           name="name"
                           id="name"
                           value="{{ old('name', isset($item) ? $item->name : '') }}"
                           placeholder="위의 검색 기능을 사용하여 사용자를 선택하세요"
                           readonly>
                    <span class="input-group-text">
                        <i class="fe fe-lock text-muted" title="검색을 통해서만 입력 가능"></i>
                    </span>
                </div>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="text-info">
                    <i class="fe fe-info-circle me-1"></i>
                    이름은 위의 검색 기능을 통해서만 입력할 수 있습니다
                </small>
            </div>
        </div>

        @if(isset($item))
        {{-- 수정 모드: 사용자 시스템 정보 표시 (읽기 전용) --}}
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="display_user_id" class="form-label">사용자 ID</label>
                <input type="number"
                       class="form-control"
                       id="display_user_id"
                       value="{{ $item->user_id }}"
                       readonly>
                <small class="text-muted">시스템 내부 사용자 고유번호입니다.</small>
            </div>
            <div class="col-md-6">
                <label for="display_user_table" class="form-label">사용자 테이블</label>
                <input type="text"
                       class="form-control"
                       id="display_user_table"
                       value="{{ $item->user_table }}"
                       readonly>
                <small class="text-muted">사용자가 저장된 샤딩 테이블명입니다.</small>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label for="display_user_uuid" class="form-label">사용자 UUID</label>
                <input type="text"
                       class="form-control"
                       id="display_user_uuid"
                       value="{{ $item->user_uuid ?? 'N/A' }}"
                       readonly>
                <small class="text-muted">사용자의 고유 식별자입니다.</small>
            </div>
            <div class="col-md-6">
                <label for="display_shard_number" class="form-label">샤드 번호</label>
                <input type="number"
                       class="form-control"
                       id="display_shard_number"
                       value="{{ $item->shard_number ?? 0 }}"
                       readonly>
                <small class="text-muted">데이터베이스 샤딩 번호입니다.</small>
            </div>
        </div>

        <div class="alert alert-warning">
            <i class="fe fe-alert-triangle me-2"></i>
            <strong>주의:</strong> 사용자 ID와 테이블 정보는 변경할 수 없습니다. 다른 사용자로 변경하려면 위의 검색 기능을 사용하세요.
        </div>
        @endif
    </div>
</div>

{{-- 사용자 검색 JavaScript --}}
@push('scripts')
<script>
// 검색 상태 관리
let searchState = {
    currentQuery: '',
    currentPage: 1,
    totalPages: 1,
    totalFound: 0,
    perPage: 10
};

// 사용자 검색 함수 (페이지네이션 지원)
async function searchUsers(page = 1) {
    const email = document.getElementById('search_email').value.trim();

    if (!email) {
        alert('검색할 이메일을 입력하세요.');
        return;
    }

    // 새로운 검색인지 페이지네이션인지 확인
    if (email !== searchState.currentQuery) {
        page = 1; // 새로운 검색이면 첫 페이지로
        searchState.currentQuery = email;
    }

    const resultsDiv = document.getElementById('search_results');
    const contentDiv = document.getElementById('search_results_content');
    const paginationDiv = document.getElementById('search_pagination');
    const infoDiv = document.getElementById('search_pagination_info');

    // 로딩 표시
    contentDiv.innerHTML = '<div class="text-center py-3"><i class="fe fe-loader spin"></i> 검색 중...</div>';
    resultsDiv.style.display = 'block';
    paginationDiv.style.display = 'none';

    try {
        const params = new URLSearchParams({
            query: email,
            page: page,
            limit: searchState.perPage
        });

        const response = await fetch(`/api/partner/v1/users/search?${params}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            }
        });

        if (!response.ok) {
            throw new Error('네트워크 응답이 올바르지 않습니다.');
        }

        const data = await response.json();

        if (data.success && data.users && data.users.length > 0) {
            // 검색 상태 업데이트
            searchState.currentPage = page;
            searchState.totalFound = data.total_found || 0;
            searchState.totalPages = Math.ceil(searchState.totalFound / searchState.perPage);

            // 결과 표시
            displaySearchResults(data.users);

            // 페이지 정보 업데이트
            updatePaginationInfo();

            // 페이지네이션 표시
            if (searchState.totalPages > 1) {
                displayPagination();
                paginationDiv.style.display = 'block';
            } else {
                paginationDiv.style.display = 'none';
            }
        } else {
            contentDiv.innerHTML = `
                <div class="text-center py-3">
                    <i class="fe fe-search text-muted mb-2" style="font-size: 2rem;"></i>
                    <div class="text-muted">${data.message || '검색 결과가 없습니다.'}</div>
                </div>
            `;
            paginationDiv.style.display = 'none';
            infoDiv.innerHTML = '';
        }
    } catch (error) {
        contentDiv.innerHTML = `
            <div class="text-center py-3">
                <i class="fe fe-alert-triangle text-danger mb-2" style="font-size: 2rem;"></i>
                <div class="text-danger">검색 중 오류가 발생했습니다.</div>
                <small class="text-muted">${error.message}</small>
            </div>
        `;
        paginationDiv.style.display = 'none';
        infoDiv.innerHTML = '';
        console.error('Search error:', error);
    }
}

// 검색 결과 표시 함수
function displaySearchResults(users) {
    const contentDiv = document.getElementById('search_results_content');

    let html = '<div class="list-group">';

    users.forEach(user => {
        html += `
            <div class="list-group-item list-group-item-action cursor-pointer border-success"
                 onclick="selectUser(${user.id}, '${user.table_name}', '${user.email}', '${user.name}', '${user.uuid || ''}', ${user.shard_number || 0})">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-1">
                            <strong class="me-2">${user.name}</strong>
                            ${user.email_verified ? '<span class="badge bg-success">인증됨</span>' : '<span class="badge bg-secondary">미인증</span>'}
                        </div>
                        <div class="text-muted mb-1">
                            <i class="fe fe-mail me-1"></i>${user.email}
                        </div>
                        ${user.uuid ? `<div class="text-muted small mb-1"><i class="fe fe-hash me-1"></i>UUID: ${user.uuid}</div>` : ''}
                        <div class="text-muted small">
                            <i class="fe fe-calendar me-1"></i>가입일: ${new Date(user.created_at).toLocaleDateString('ko-KR')}
                            <span class="ms-2"><i class="fe fe-database me-1"></i>${user.table_name}</span>
                            ${user.shard_number > 0 ? `<span class="ms-2 badge bg-secondary">샤드 ${user.shard_number}</span>` : ''}
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="mb-1"><span class="badge bg-success">등록 가능</span></div>
                        <i class="fe fe-arrow-right text-primary"></i>
                    </div>
                </div>
            </div>
        `;
    });

    html += '</div>';
    contentDiv.innerHTML = html;
}

// 페이지 정보 업데이트
function updatePaginationInfo() {
    const infoDiv = document.getElementById('search_pagination_info');
    const startIndex = (searchState.currentPage - 1) * searchState.perPage + 1;
    const endIndex = Math.min(searchState.currentPage * searchState.perPage, searchState.totalFound);

    infoDiv.innerHTML = `${startIndex}-${endIndex} / 총 ${searchState.totalFound}명`;
}

// 페이지네이션 표시
function displayPagination() {
    const paginationDiv = document.getElementById('search_pagination');

    let html = '<div class="d-flex justify-content-between align-items-center">';

    // 이전 페이지 버튼들
    html += '<div>';

    if (searchState.currentPage > 1) {
        html += `<button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="searchUsers(1)">
                    <i class="fe fe-chevrons-left me-1"></i>첫 페이지
                </button>`;
        html += `<button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="searchUsers(${searchState.currentPage - 1})">
                    <i class="fe fe-chevron-left me-1"></i>이전
                </button>`;
    }

    html += '</div>';

    // 페이지 번호들
    html += '<div class="d-flex align-items-center">';

    const maxVisiblePages = 5;
    let startPage = Math.max(1, searchState.currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(searchState.totalPages, startPage + maxVisiblePages - 1);

    // 시작 페이지 조정
    if (endPage - startPage + 1 < maxVisiblePages) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }

    for (let i = startPage; i <= endPage; i++) {
        const isActive = i === searchState.currentPage;
        html += `<button type="button"
                        class="btn btn-sm ${isActive ? 'btn-primary' : 'btn-outline-secondary'} me-1"
                        onclick="searchUsers(${i})">${i}</button>`;
    }

    html += '</div>';

    // 다음 페이지 버튼들
    html += '<div>';

    if (searchState.currentPage < searchState.totalPages) {
        html += `<button type="button" class="btn btn-sm btn-outline-primary ms-2" onclick="searchUsers(${searchState.currentPage + 1})">
                    다음<i class="fe fe-chevron-right ms-1"></i>
                </button>`;
        html += `<button type="button" class="btn btn-sm btn-outline-primary ms-2" onclick="searchUsers(${searchState.totalPages})">
                    마지막 페이지<i class="fe fe-chevrons-right ms-1"></i>
                </button>`;
    }

    html += '</div>';
    html += '</div>';

    // 더보기 버튼 (모바일 친화적)
    if (searchState.currentPage < searchState.totalPages) {
        html += `<div class="text-center mt-3 d-md-none">
                    <button type="button" class="btn btn-primary" onclick="searchUsers(${searchState.currentPage + 1})">
                        <i class="fe fe-plus me-1"></i>더보기 (${searchState.totalFound - (searchState.currentPage * searchState.perPage)}명 더 있음)
                    </button>
                </div>`;
    }

    paginationDiv.innerHTML = html;
}

// 사용자 선택 함수
function selectUser(userId, userTable, email, name, uuid, shardNumber) {
    document.getElementById('user_id').value = userId;
    document.getElementById('user_table').value = userTable;
    document.getElementById('user_uuid').value = uuid || '';
    document.getElementById('shard_number').value = shardNumber || 0;
    document.getElementById('email').value = email;
    document.getElementById('name').value = name;

    // 수정 모드에서 display 필드들도 업데이트
    updateDisplayFields(userId, userTable, uuid, shardNumber);

    // 검색 결과 숨김
    document.getElementById('search_results').style.display = 'none';

    // 선택된 사용자 정보 표시
    document.getElementById('search_email').value = email;

    // 기존 edit-mode-notice 제거
    const existingNotice = document.querySelector('.edit-mode-notice');
    if (existingNotice) {
        existingNotice.remove();
    }

    // 성공 메시지
    const alert = document.createElement('div');
    alert.className = 'alert alert-success alert-dismissible fade show';
    alert.innerHTML = `
        <i class="fe fe-check-circle me-2"></i>
        <strong>${name}</strong> (${email}) 사용자가 선택되었습니다.
        <br><small class="text-muted">테이블: ${userTable} | UUID: ${uuid || 'N/A'} | 샤드: ${shardNumber || 0}</small>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    // 폼의 첫 번째 요소 앞에 알림 삽입
    const form = document.getElementById('partnerUserForm');
    if (form) {
        form.insertBefore(alert, form.firstChild);
    }

    // 3초 후 자동 제거
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 3000);
}

// 수정 모드에서 display 필드 업데이트 함수
function updateDisplayFields(userId, userTable, uuid, shardNumber) {
    const displayUserIdField = document.getElementById('display_user_id');
    const displayUserTableField = document.getElementById('display_user_table');
    const displayUserUuidField = document.getElementById('display_user_uuid');
    const displayShardNumberField = document.getElementById('display_shard_number');

    if (displayUserIdField) displayUserIdField.value = userId;
    if (displayUserTableField) displayUserTableField.value = userTable;
    if (displayUserUuidField) displayUserUuidField.value = uuid || 'N/A';
    if (displayShardNumberField) displayShardNumberField.value = shardNumber || 0;
}

// 페이지 로드시 기본 이벤트 바인딩 및 수정 모드 초기화
document.addEventListener('DOMContentLoaded', function() {
    // 수정 모드에서 기존 데이터로 초기화
    initializeEditMode();

    function initializeEditMode() {
        const userId = document.getElementById('user_id').value;
        const userEmail = document.getElementById('email').value;
        const userName = document.getElementById('name').value;

        // 수정 모드이고 사용자 데이터가 있다면
        if (userId && userEmail && userName) {
            // 검색 결과 숨김 (이미 사용자가 선택된 상태)
            const searchResults = document.getElementById('search_results');
            if (searchResults) {
                searchResults.style.display = 'none';
            }

            // 현재 사용자 정보로 성공 알림 표시 (선택사항)
            showEditModeNotice(userName, userEmail);
        }
    }

    // 수정 모드 안내 메시지 표시 (선택사항)
    function showEditModeNotice(name, email) {
        const existingAlert = document.querySelector('.edit-mode-notice');
        if (existingAlert) {
            existingAlert.remove();
        }

        const alert = document.createElement('div');
        alert.className = 'alert alert-success alert-dismissible fade show edit-mode-notice';
        alert.innerHTML = `
            <i class="fe fe-check-circle me-2"></i>
            <strong>현재 사용자:</strong> ${name} (${email})
            <br><small class="text-muted">다른 사용자로 변경하려면 위의 검색 기능을 사용하세요.</small>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        // 폼의 첫 번째 요소 앞에 알림 삽입
        const form = document.getElementById('partnerUserForm');
        if (form) {
            form.insertBefore(alert, form.firstChild);
        }
    }
    // 상태 변경시 사유 입력 필드 표시/숨김
    const statusSelect = document.getElementById('status');
    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            const statusReasonGroup = document.getElementById('status_reason_group');
            const selectedStatus = this.value;

            if (statusReasonGroup) {
                if (selectedStatus === 'suspended' || selectedStatus === 'inactive') {
                    statusReasonGroup.style.display = 'block';
                } else {
                    statusReasonGroup.style.display = 'none';
                }
            }
        });

        // 페이지 로드시 상태에 따른 사유 필드 표시
        statusSelect.dispatchEvent(new Event('change'));
    }

    // JSON 유효성 검사
    const profileDataField = document.getElementById('profile_data');
    if (profileDataField) {
        profileDataField.addEventListener('blur', function() {
            const value = this.value.trim();

            if (value && value !== '') {
                try {
                    JSON.parse(value);
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } catch (e) {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');

                    // 피드백 메시지 추가
                    let feedback = this.parentNode.querySelector('.invalid-feedback');
                    if (!feedback) {
                        feedback = document.createElement('div');
                        feedback.className = 'invalid-feedback';
                        this.parentNode.appendChild(feedback);
                    }
                    feedback.textContent = 'JSON 형식이 올바르지 않습니다.';
                }
            } else {
                this.classList.remove('is-invalid', 'is-valid');
            }
        });
    }

    // 폼 제출 전 유효성 검사 (강화됨)
    const form = document.getElementById('partnerUserForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const userId = document.getElementById('user_id').value;
            const userEmail = document.getElementById('email').value;
            const userName = document.getElementById('name').value;
            const searchEmail = document.getElementById('search_email').value;

            // 사용자가 선택되지 않은 경우
            if (!userId || !userEmail || !userName) {
                e.preventDefault();

                // 더 명확한 오류 메시지와 시각적 피드백
                const errorAlert = document.createElement('div');
                errorAlert.className = 'alert alert-danger alert-dismissible fade show';
                errorAlert.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="fe fe-alert-triangle me-2" style="font-size: 1.5rem;"></i>
                        <div>
                            <strong>사용자 선택 필요!</strong><br>
                            <small>이메일과 이름은 직접 입력할 수 없습니다. 반드시 위의 검색 기능을 사용하여 사용자를 선택해주세요.</small>
                            <br>
                            <small class="text-muted">
                                1. 위의 검색란에 이메일을 입력하세요<br>
                                2. 검색 버튼을 클릭하세요<br>
                                3. 검색 결과에서 원하는 사용자를 선택하세요
                            </small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;

                // 검색 필드 위에 오류 메시지 삽입
                const searchContainer = document.getElementById('search_email').closest('.mb-3');
                searchContainer.parentNode.insertBefore(errorAlert, searchContainer);

                // 검색 필드에 포커스
                document.getElementById('search_email').focus();
                document.getElementById('search_email').classList.add('border-danger');

                // 5초 후 자동 제거
                setTimeout(() => {
                    if (errorAlert.parentNode) {
                        errorAlert.remove();
                    }
                    document.getElementById('search_email').classList.remove('border-danger');
                }, 5000);

                return false;
            }

            // JSON 유효성 검사
            const profileDataField = document.getElementById('profile_data');
            if (profileDataField) {
                const profileData = profileDataField.value.trim();

                if (profileData && profileData !== '') {
                    try {
                        JSON.parse(profileData);
                    } catch (error) {
                        e.preventDefault();
                        alert('프로필 데이터의 JSON 형식이 올바르지 않습니다.');
                        profileDataField.focus();
                        return false;
                    }
                }
            }
        });
    }
});
</script>
@endpush

{{-- 검색 관련 스타일 --}}
@push('styles')
<style>
.cursor-pointer {
    cursor: pointer;
}

.list-group-item:hover {
    background-color: #f8f9fa;
}

.alert {
    margin-bottom: 1rem;
}

#search_results_content {
    max-height: 400px;
    overflow-y: auto;
}

/* 페이지네이션 스타일 */
#search_pagination .btn-sm {
    min-width: 40px;
}

#search_pagination .btn[disabled] {
    opacity: 0.5;
    cursor: not-allowed;
}

/* 페이지네이션 정보 스타일 */
#search_pagination_info {
    font-weight: 500;
    color: #6c757d;
}

/* 검색 결과 카운터 애니메이션 */
.search-result-counter {
    transition: all 0.3s ease;
}

/* 모바일 반응형 페이지네이션 */
@media (max-width: 768px) {
    #search_pagination .d-flex {
        flex-direction: column;
        gap: 0.5rem;
    }

    #search_pagination .btn-sm {
        font-size: 0.75rem;
    }

    #search_pagination_info {
        text-align: center;
        margin-bottom: 0.5rem;
    }
}

.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.is-valid {
    border-color: #198754;
}

.is-invalid {
    border-color: #dc3545;
}

/* 읽기 전용 필드 스타일 강화 */
input[readonly].bg-light {
    background-color: #f8f9fa !important;
    border-color: #dee2e6;
    cursor: not-allowed;
    position: relative;
}

input[readonly]:focus {
    box-shadow: none !important;
    border-color: #dee2e6 !important;
}

/* 검색 필드 강조 */
.border-primary {
    border-width: 2px !important;
}

/* 잠금 아이콘 스타일 */
.input-group-text .fe-lock {
    opacity: 0.6;
}
</style>
@endpush
