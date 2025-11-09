@extends('jiny-partner::layouts.admin.sidebar')

@section('title', $title . ' 등록')

@section('content')
<div class="container-fluid">
    <!-- 헤더 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">{{ $title }} 등록</h2>
                    <p class="text-muted mb-0">새로운 파트너 회원을 시스템에 등록합니다</p>
                </div>
                <div>
                    <a href="{{ route('admin.' . $routePrefix . '.index') }}" class="btn btn-outline-secondary">
                        <i class="fe fe-arrow-left me-2"></i>목록으로
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 등록 폼 -->
    <div class="row">
        <div class="col-lg-8">
            <form action="{{ route('admin.' . $routePrefix . '.store') }}" method="POST" id="partnerUserForm">
                @csrf

                <!-- 선택된 사용자 정보 (숨김 필드) -->
                <input type="hidden" name="user_id" id="user_id" value="{{ old('user_id') }}">
                <input type="hidden" name="user_table" id="user_table" value="{{ old('user_table') }}">
                <input type="hidden" name="user_uuid" id="user_uuid" value="{{ old('user_uuid') }}">
                <input type="hidden" name="shard_number" id="shard_number" value="{{ old('shard_number') }}">

                <!-- 회원정보 카드 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fe fe-user me-2"></i>회원정보
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- 사용자 검색 안내 -->
                        <div class="alert alert-info">
                            <i class="fe fe-info me-2"></i>
                            <strong>사용자 검색:</strong> 이메일을 입력하여 샤딩된 사용자 테이블에서 등록할 사용자를 검색하세요.
                        </div>

                        <!-- 사용자 이메일 검색 -->
                        <div class="mb-3">
                            <label for="search_email" class="form-label">사용자 이메일 검색 <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text"
                                       class="form-control"
                                       id="search_email"
                                       placeholder="이메일 주소로 사용자 검색..."
                                       value="{{ old('search_email') }}"
                                       onkeypress="if(event.key==='Enter') searchUsers()">
                                <button type="button" class="btn btn-outline-primary" onclick="searchUsers()">
                                    <i class="fe fe-search me-1"></i>검색
                                </button>
                            </div>
                            <small class="text-muted">부분 이메일로도 검색 가능합니다 (예: hojin1@jinyphp.com)</small>
                        </div>

                        <!-- 검색 결과 표시 영역 -->
                        <div id="search_results" class="mb-4" style="display: none;">
                            <label class="form-label">검색 결과</label>
                            <div class="border rounded p-3">
                                <div id="search_results_content">
                                    <!-- 검색 결과가 여기에 표시됩니다 -->
                                </div>
                            </div>
                        </div>

                        <!-- 선택된 사용자 기본 정보 -->
                        <div class="row">
                            <div class="col-md-6">
                                <label for="email" class="form-label">이메일 <span class="text-danger">*</span></label>
                                <input type="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       name="email"
                                       id="email"
                                       value="{{ old('email') }}"
                                       readonly>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="name" class="form-label">이름 <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       name="name"
                                       id="name"
                                       value="{{ old('name') }}"
                                       readonly>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 파트너 정보 카드 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fe fe-award me-2"></i>파트너 정보
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- 파트너 등급 및 상태 -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="partner_tier_id" class="form-label">파트너 등급 <span class="text-danger">*</span></label>
                                <select name="partner_tier_id"
                                        id="partner_tier_id"
                                        class="form-control @error('partner_tier_id') is-invalid @enderror">
                                    <option value="">등급을 선택하세요</option>
                                    @foreach($partnerTiers as $tier)
                                        <option value="{{ $tier->id }}" {{ old('partner_tier_id') == $tier->id ? 'selected' : '' }}>
                                            {{ $tier->tier_name }} ({{ $tier->tier_code }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('partner_tier_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">상태 <span class="text-danger">*</span></label>
                                <select name="status"
                                        id="status"
                                        class="form-control @error('status') is-invalid @enderror">
                                    @foreach($statusOptions as $value => $label)
                                        <option value="{{ $value }}" {{ old('status', 'pending') == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- 날짜 정보 -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="partner_joined_at" class="form-label">파트너 가입일</label>
                                <input type="date"
                                       class="form-control @error('partner_joined_at') is-invalid @enderror"
                                       name="partner_joined_at"
                                       id="partner_joined_at"
                                       value="{{ old('partner_joined_at', date('Y-m-d')) }}">
                                @error('partner_joined_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="tier_assigned_at" class="form-label">등급 할당일</label>
                                <input type="date"
                                       class="form-control @error('tier_assigned_at') is-invalid @enderror"
                                       name="tier_assigned_at"
                                       id="tier_assigned_at"
                                       value="{{ old('tier_assigned_at', date('Y-m-d')) }}">
                                @error('tier_assigned_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="last_performance_review_at" class="form-label">마지막 성과 평가일</label>
                                <input type="date"
                                       class="form-control @error('last_performance_review_at') is-invalid @enderror"
                                       name="last_performance_review_at"
                                       id="last_performance_review_at"
                                       value="{{ old('last_performance_review_at') }}">
                                @error('last_performance_review_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- 성과 정보 -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="total_completed_jobs" class="form-label">완료 작업 수</label>
                                <input type="number"
                                       class="form-control @error('total_completed_jobs') is-invalid @enderror"
                                       name="total_completed_jobs"
                                       id="total_completed_jobs"
                                       value="{{ old('total_completed_jobs', 0) }}"
                                       min="0">
                                @error('total_completed_jobs')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label for="average_rating" class="form-label">평균 평점</label>
                                <input type="number"
                                       class="form-control @error('average_rating') is-invalid @enderror"
                                       name="average_rating"
                                       id="average_rating"
                                       value="{{ old('average_rating', 0) }}"
                                       step="0.01"
                                       min="0"
                                       max="5">
                                @error('average_rating')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label for="punctuality_rate" class="form-label">시간 준수율 (%)</label>
                                <input type="number"
                                       class="form-control @error('punctuality_rate') is-invalid @enderror"
                                       name="punctuality_rate"
                                       id="punctuality_rate"
                                       value="{{ old('punctuality_rate', 0) }}"
                                       step="0.01"
                                       min="0"
                                       max="100">
                                @error('punctuality_rate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label for="satisfaction_rate" class="form-label">만족도 (%)</label>
                                <input type="number"
                                       class="form-control @error('satisfaction_rate') is-invalid @enderror"
                                       name="satisfaction_rate"
                                       id="satisfaction_rate"
                                       value="{{ old('satisfaction_rate', 0) }}"
                                       step="0.01"
                                       min="0"
                                       max="100">
                                @error('satisfaction_rate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- 프로필 데이터 (JSON) -->
                        <div class="mb-3">
                            <label for="profile_data" class="form-label">프로필 데이터 (JSON)</label>
                            <textarea class="form-control @error('profile_data') is-invalid @enderror"
                                      name="profile_data"
                                      id="profile_data"
                                      rows="6"
                                      placeholder='{"specializations": ["웹개발", "모바일앱"], "certifications": ["정보처리기사"], "experience_years": 5}'>{{ old('profile_data') }}</textarea>
                            @error('profile_data')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">JSON 형태로 입력하세요. 빈 값으로 두면 null이 저장됩니다.</small>
                        </div>

                        <!-- 관리자 메모 -->
                        <div class="mb-3">
                            <label for="admin_notes" class="form-label">관리자 메모</label>
                            <textarea class="form-control @error('admin_notes') is-invalid @enderror"
                                      name="admin_notes"
                                      id="admin_notes"
                                      rows="3"
                                      placeholder="파트너에 대한 추가 정보나 특이사항을 입력하세요...">{{ old('admin_notes') }}</textarea>
                            @error('admin_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 상태 변경 사유 -->
                        <div class="mb-4" id="status_reason_group" style="display: none;">
                            <label for="status_reason" class="form-label">상태 변경 사유</label>
                            <textarea class="form-control @error('status_reason') is-invalid @enderror"
                                      name="status_reason"
                                      id="status_reason"
                                      rows="2"
                                      placeholder="상태 변경 사유를 입력하세요...">{{ old('status_reason') }}</textarea>
                            @error('status_reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 버튼 그룹 -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.' . $routePrefix . '.index') }}" class="btn btn-secondary">
                                <i class="fe fe-x me-1"></i>취소
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fe fe-save me-1"></i>등록
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            <!-- 등록 가이드 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fe fe-help-circle me-2"></i>등록 가이드
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-primary">1. 사용자 검색</h6>
                        <p class="small text-muted">이메일로 샤딩된 사용자 테이블에서 등록할 사용자를 검색하세요.</p>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-primary">2. 파트너 등급 선택</h6>
                        <p class="small text-muted">사용자의 경력과 역량에 맞는 등급을 선택하세요.</p>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-primary">3. 초기 상태 설정</h6>
                        <p class="small text-muted">
                            • <strong>대기:</strong> 검토 중<br>
                            • <strong>활성:</strong> 즉시 활동 가능<br>
                            • <strong>비활성:</strong> 임시 비활성화
                        </p>
                    </div>
                    <div>
                        <h6 class="text-primary">4. 성과 정보</h6>
                        <p class="small text-muted">기존 활동 이력이 있다면 성과 데이터를 입력하세요.</p>
                    </div>
                </div>
            </div>

            <!-- 파트너 등급 정보 -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fe fe-award me-2"></i>파트너 등급 정보
                    </h6>
                </div>
                <div class="card-body">
                    @foreach($partnerTiers as $tier)
                    <div class="mb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-info">{{ $tier->tier_name }}</span>
                            <small class="text-muted">{{ $tier->commission_rate }}% 수수료</small>
                        </div>
                        @if($tier->description)
                        <small class="text-muted d-block">{{ $tier->description }}</small>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// 상태 변경시 사유 입력 필드 표시/숨김
document.getElementById('status').addEventListener('change', function() {
    const statusReasonGroup = document.getElementById('status_reason_group');
    const selectedStatus = this.value;

    if (selectedStatus === 'suspended' || selectedStatus === 'inactive') {
        statusReasonGroup.style.display = 'block';
    } else {
        statusReasonGroup.style.display = 'none';
    }
});

// 사용자 검색 함수
async function searchUsers() {
    const email = document.getElementById('search_email').value.trim();

    if (!email) {
        alert('검색할 이메일을 입력하세요.');
        return;
    }

    const resultsDiv = document.getElementById('search_results');
    const contentDiv = document.getElementById('search_results_content');

    // 로딩 표시
    contentDiv.innerHTML = '<div class="text-center py-3"><i class="fe fe-loader spin"></i> 검색 중...</div>';
    resultsDiv.style.display = 'block';

    try {
        const params = new URLSearchParams({
            email: email
        });

        const response = await fetch(`{{ route('admin.partner.users.search') }}?${params}`, {
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
            let html = '<div class="list-group">';

            data.users.forEach(user => {
                html += `
                    <div class="list-group-item list-group-item-action cursor-pointer"
                         onclick="selectUser(${user.id}, '${user.user_table}', '${user.email}', '${user.name}', '${user.uuid || ''}', ${user.shard_number || 0})">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${user.name}</strong><br>
                                <small class="text-muted">${user.email}</small>
                                ${user.uuid ? `<br><small class="text-muted">UUID: ${user.uuid}</small>` : ''}
                            </div>
                            <div>
                                <span class="badge bg-secondary">${user.user_table}</span>
                                ${user.shard_number > 0 ? `<br><span class="badge bg-info">샤드 ${user.shard_number}</span>` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            html += `<div class="mt-2 text-muted small">
                        <div>총 ${data.total_found || 0}명 중 ${data.available_count || 0}명 등록 가능</div>
                        ${data.already_registered > 0 ? `<div class="text-warning">• ${data.already_registered}명은 이미 활성 상태로 등록됨</div>` : ''}
                        ${data.deleted_registered > 0 ? `<div class="text-info">• ${data.deleted_registered}명은 이전에 등록되었다가 삭제됨</div>` : ''}
                     </div>`;
            contentDiv.innerHTML = html;
        } else {
            contentDiv.innerHTML = `
                <div class="text-center py-3">
                    <i class="fe fe-search text-muted mb-2" style="font-size: 2rem;"></i>
                    <div class="text-muted">${data.message || '검색 결과가 없습니다.'}</div>
                </div>
            `;
        }
    } catch (error) {
        contentDiv.innerHTML = `
            <div class="text-center py-3">
                <i class="fe fe-alert-triangle text-danger mb-2" style="font-size: 2rem;"></i>
                <div class="text-danger">검색 중 오류가 발생했습니다.</div>
                <small class="text-muted">${error.message}</small>
            </div>
        `;
        console.error('Search error:', error);
    }
}

// 사용자 선택 함수
function selectUser(userId, userTable, email, name, uuid, shardNumber) {
    document.getElementById('user_id').value = userId;
    document.getElementById('user_table').value = userTable;
    document.getElementById('user_uuid').value = uuid || '';
    document.getElementById('shard_number').value = shardNumber || 0;
    document.getElementById('email').value = email;
    document.getElementById('name').value = name;

    // 검색 결과 숨김
    document.getElementById('search_results').style.display = 'none';

    // 선택된 사용자 정보 표시
    document.getElementById('search_email').value = email;

    // 성공 메시지
    const alert = document.createElement('div');
    alert.className = 'alert alert-success alert-dismissible fade show';
    alert.innerHTML = `
        <i class="fe fe-check-circle me-2"></i>
        <strong>${name}</strong> (${email}) 사용자가 선택되었습니다.
        <br><small class="text-muted">테이블: ${userTable} | UUID: ${uuid || 'N/A'} | 샤드: ${shardNumber || 0}</small>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.getElementById('partnerUserForm').insertBefore(alert, document.getElementById('partnerUserForm').firstChild);

    // 3초 후 자동 제거
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 3000);
}

// JSON 유효성 검사
document.getElementById('profile_data').addEventListener('blur', function() {
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

// 폼 제출 전 유효성 검사
document.getElementById('partnerUserForm').addEventListener('submit', function(e) {
    const userId = document.getElementById('user_id').value;

    if (!userId) {
        e.preventDefault();
        alert('사용자를 먼저 검색하고 선택해주세요.');
        return false;
    }

    // JSON 유효성 검사
    const profileData = document.getElementById('profile_data').value.trim();

    if (profileData && profileData !== '') {
        try {
            JSON.parse(profileData);
        } catch (error) {
            e.preventDefault();
            alert('프로필 데이터의 JSON 형식이 올바르지 않습니다.');
            document.getElementById('profile_data').focus();
            return false;
        }
    }
});

// 페이지 로드시 상태에 따른 사유 필드 표시
document.addEventListener('DOMContentLoaded', function() {
    const statusSelect = document.getElementById('status');
    statusSelect.dispatchEvent(new Event('change'));
});
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

.alert {
    margin-bottom: 1rem;
}

#search_results {
    max-height: 300px;
    overflow-y: auto;
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
</style>
@endpush