<!-- 사용자 검색 -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fe fe-search me-2"></i>사용자 검색 및 선택
        </h5>
    </div>
    <div class="card-body">
        <!-- 사용자 검색 -->
        <div class="row mb-3">
            <div class="col-md-8">
                <div class="input-group">
                    <input type="text" id="user_search_query" class="form-control"
                           placeholder="이메일 또는 이름으로 검색하세요..."
                           autocomplete="off">
                    <button type="button" id="search_user_btn" class="btn btn-primary">
                        <i class="fe fe-search me-1"></i>검색
                    </button>
                </div>
            </div>
            <div class="col-md-4">
                <select id="search_limit" class="form-control">
                    <option value="10">10개 결과</option>
                    <option value="20" selected>20개 결과</option>
                    <option value="50">50개 결과</option>
                </select>
            </div>
        </div>

        <!-- 검색 결과 -->
        <div id="search_results" class="mb-3" style="display: none;">
            <h6 class="text-primary mb-2">검색 결과:</h6>
            <div id="search_results_list" class="list-group">
                <!-- 검색 결과가 여기에 표시됩니다 -->
            </div>
        </div>

        <!-- 로딩 상태 -->
        <div id="search_loading" class="text-center py-3" style="display: none;">
            <div class="spinner-border spinner-border-sm me-2" role="status"></div>
            <span>검색 중...</span>
        </div>

        <!-- 선택된 사용자 정보 표시 -->
        <div id="selected_user_info" class="alert alert-success" style="display: none;">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <strong>선택된 사용자:</strong>
                    <span id="selected_user_display"></span>
                    <br>
                    <small class="text-muted">
                        테이블: <span id="selected_user_table"></span> |
                        샤드: <span id="selected_user_shard"></span> |
                        UUID: <span id="selected_user_uuid"></span>
                    </small>
                </div>
                <div class="col-md-4 text-end">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearSelectedUser()">
                        <i class="fe fe-x me-1"></i>선택 해제
                    </button>
                </div>
            </div>
        </div>

        <!-- 숨겨진 필드들 -->
        <input type="hidden" id="user_id" name="user_id" value="{{ old('user_id') }}">
        <input type="hidden" id="user_uuid" name="user_uuid" value="{{ old('user_uuid') }}">
        <input type="hidden" id="shard_number" name="shard_number" value="{{ old('shard_number') }}">

        <!-- 오류 메시지 표시 -->
        <div id="search_error" class="alert alert-danger" style="display: none;">
            <strong>오류:</strong> <span id="search_error_message"></span>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('user_search_query');
    const searchButton = document.getElementById('search_user_btn');
    const limitSelect = document.getElementById('search_limit');

    // 검색 버튼 클릭
    searchButton.addEventListener('click', performSearch);

    // Enter 키로 검색
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            performSearch();
        }
    });

    // 기존 선택된 사용자 정보가 있다면 표시
    const existingUserId = document.getElementById('user_id').value;
    if (existingUserId) {
        showSelectedUserFromOldInput();
    }
});

// 사용자 검색 수행
async function performSearch() {
    const query = document.getElementById('user_search_query').value.trim();
    const limit = document.getElementById('search_limit').value;

    if (query.length < 2) {
        showSearchError('검색어를 2글자 이상 입력해주세요.');
        return;
    }

    showSearchLoading();
    hideSearchError();

    try {
        const response = await fetch(`/api/admin/partner/users/search?query=${encodeURIComponent(query)}&limit=${limit}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            credentials: 'same-origin'
        });

        const data = await response.json();

        if (response.ok && data.success) {
            displaySearchResults(data.users || []);
        } else {
            showSearchError(data.message || '검색 중 오류가 발생했습니다.');
        }

    } catch (error) {
        console.error('Search error:', error);
        showSearchError('네트워크 오류가 발생했습니다. 다시 시도해주세요.');
    } finally {
        hideSearchLoading();
    }
}

// 검색 결과 표시
function displaySearchResults(users) {
    const resultsDiv = document.getElementById('search_results');
    const resultsList = document.getElementById('search_results_list');

    resultsList.innerHTML = '';

    if (users.length === 0) {
        resultsDiv.style.display = 'block';
        resultsList.innerHTML = '<div class="list-group-item text-muted text-center">검색 결과가 없습니다.</div>';
        return;
    }

    users.forEach(user => {
        const resultItem = document.createElement('div');
        resultItem.className = 'list-group-item list-group-item-action cursor-pointer';
        resultItem.onclick = () => selectUser(user.user_id, user.table_name, user.email, user.name, user.uuid, user.shard_number);

        resultItem.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1">${escapeHtml(user.name || 'N/A')}</h6>
                    <p class="mb-1">${escapeHtml(user.email)}</p>
                    <small class="text-muted">
                        ID: ${user.user_id} | 테이블: ${user.table_name} | 샤드: ${user.shard_number}
                    </small>
                </div>
                <div>
                    <i class="fe fe-chevron-right text-muted"></i>
                </div>
            </div>
        `;

        resultsList.appendChild(resultItem);
    });

    resultsDiv.style.display = 'block';
}

// 사용자 선택
function selectUser(userId, userTable, email, name, uuid, shardNumber) {
    // 숨겨진 필드에 값 설정
    document.getElementById('user_id').value = userId;
    document.getElementById('user_uuid').value = uuid || '';
    document.getElementById('shard_number').value = shardNumber || 0;

    // 개인정보 필드에 자동 입력
    if (name) document.getElementById('personal_name').value = name;
    if (email) document.getElementById('personal_email').value = email;

    // 선택된 사용자 정보 표시
    updateSelectedUserDisplay(userId, userTable, uuid, shardNumber, email, name);

    // 검색 결과 숨기기
    document.getElementById('search_results').style.display = 'none';
    document.getElementById('user_search_query').value = '';
}

// 선택된 사용자 정보 업데이트
function updateSelectedUserDisplay(userId, userTable, uuid, shardNumber, email, name) {
    const displaySpan = document.getElementById('selected_user_display');
    const tableSpan = document.getElementById('selected_user_table');
    const shardSpan = document.getElementById('selected_user_shard');
    const uuidSpan = document.getElementById('selected_user_uuid');

    displaySpan.textContent = `${name || 'N/A'} (${email})`;
    tableSpan.textContent = userTable || 'N/A';
    shardSpan.textContent = shardNumber || '0';
    uuidSpan.textContent = uuid || 'N/A';

    document.getElementById('selected_user_info').style.display = 'block';
}

// 선택된 사용자 해제
function clearSelectedUser() {
    document.getElementById('user_id').value = '';
    document.getElementById('user_uuid').value = '';
    document.getElementById('shard_number').value = '';
    document.getElementById('personal_name').value = '';
    document.getElementById('personal_email').value = '';
    document.getElementById('selected_user_info').style.display = 'none';
}

// 기존 입력값으로부터 선택된 사용자 표시
function showSelectedUserFromOldInput() {
    const userId = document.getElementById('user_id').value;
    const userUuid = document.getElementById('user_uuid').value;
    const shardNumber = document.getElementById('shard_number').value;
    const name = document.getElementById('personal_name').value;
    const email = document.getElementById('personal_email').value;

    if (userId) {
        updateSelectedUserDisplay(
            userId,
            `users_${String(shardNumber || 0).padStart(3, '0')}`,
            userUuid,
            shardNumber,
            email,
            name
        );
    }
}

// 로딩 상태 표시/숨김
function showSearchLoading() {
    document.getElementById('search_loading').style.display = 'block';
    document.getElementById('search_user_btn').disabled = true;
}

function hideSearchLoading() {
    document.getElementById('search_loading').style.display = 'none';
    document.getElementById('search_user_btn').disabled = false;
}

// 오류 메시지 표시/숨김
function showSearchError(message) {
    const errorDiv = document.getElementById('search_error');
    const errorMessage = document.getElementById('search_error_message');
    errorMessage.textContent = message;
    errorDiv.style.display = 'block';
}

function hideSearchError() {
    document.getElementById('search_error').style.display = 'none';
}

// HTML 이스케이프 함수
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
}
</script>
@endpush

@push('styles')
<style>
.cursor-pointer {
    cursor: pointer;
}

.list-group-item:hover {
    background-color: #f8f9fa;
}

#search_results {
    max-height: 400px;
    overflow-y: auto;
}

.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

.alert {
    margin-bottom: 1rem;
}

#selected_user_info {
    border: 1px solid #d1ecf1;
    background-color: #d1ecf1;
}

#search_error {
    border: 1px solid #f5c6cb;
    background-color: #f8d7da;
}
</style>
@endpush