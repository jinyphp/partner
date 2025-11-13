@extends('jiny-partner::layouts.admin.sidebar')

@section('title', $title)

@section('content')
<div class="container-fluid">
    <!-- 헤더 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">{{ $title }}</h2>
                    <p class="text-muted mb-0">새로운 파트너 신청서를 등록합니다</p>
                </div>
                <div>
                    <a href="{{ route('admin.partner.applications.index') }}" class="btn btn-outline-secondary">
                        <i class="fe fe-arrow-left me-2"></i>목록으로
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 등록 폼 -->
    <form action="{{ route('admin.partner.applications.store') }}" method="POST" enctype="multipart/form-data" id="applicationForm">
        @csrf

        <div class="row">
            <div class="col-lg-8">
                {{-- 사용자 검색 --}}
                @includeIf("jiny-partner::admin.partner-applications.partials.search_user")

                <!-- 추가 개인 정보 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fe fe-user-plus me-2"></i>추가 개인 정보
                        </h5>
                    </div>
                    <div class="card-body">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="personal_phone" class="form-label">연락처</label>
                                <input type="tel"
                                       class="form-control @error('personal_info.phone') is-invalid @enderror"
                                       name="personal_info[phone]"
                                       id="personal_phone"
                                       value="{{ old('personal_info.phone') }}">
                                @error('personal_info.phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="personal_birth_date" class="form-label">생년월일</label>
                                <input type="date"
                                       class="form-control @error('personal_info.birth_date') is-invalid @enderror"
                                       name="personal_info[birth_date]"
                                       id="personal_birth_date"
                                       value="{{ old('personal_info.birth_date') }}">
                                @error('personal_info.birth_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="personal_address" class="form-label">주소</label>
                                <input type="text"
                                       class="form-control @error('personal_info.address') is-invalid @enderror"
                                       name="personal_info[address]"
                                       id="personal_address"
                                       value="{{ old('personal_info.address') }}">
                                @error('personal_info.address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="emergency_contact" class="form-label">긴급연락인</label>
                                <input type="text"
                                       class="form-control @error('personal_info.emergency_contact') is-invalid @enderror"
                                       name="personal_info[emergency_contact]"
                                       id="emergency_contact"
                                       value="{{ old('personal_info.emergency_contact') }}">
                                @error('personal_info.emergency_contact')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="emergency_phone" class="form-label">긴급연락처</label>
                                <input type="tel"
                                       class="form-control @error('personal_info.emergency_phone') is-invalid @enderror"
                                       name="personal_info[emergency_phone]"
                                       id="emergency_phone"
                                       value="{{ old('personal_info.emergency_phone') }}">
                                @error('personal_info.emergency_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 경력 정보 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fe fe-briefcase me-2"></i>경력 정보
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="total_years" class="form-label">총 경력 (년)</label>
                                <input type="number"
                                       class="form-control @error('experience_info.total_years') is-invalid @enderror"
                                       name="experience_info[total_years]"
                                       id="total_years"
                                       value="{{ old('experience_info.total_years', 0) }}"
                                       min="0" max="50">
                                @error('experience_info.total_years')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="education" class="form-label">학력</label>
                            <textarea class="form-control @error('experience_info.education') is-invalid @enderror"
                                      name="experience_info[education]"
                                      id="education"
                                      rows="3"
                                      placeholder="학력 정보를 입력하세요...">{{ old('experience_info.education') }}</textarea>
                            @error('experience_info.education')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="achievements" class="form-label">주요 성과</label>
                            <textarea class="form-control @error('experience_info.achievements') is-invalid @enderror"
                                      name="experience_info[achievements]"
                                      id="achievements"
                                      rows="4"
                                      placeholder="주요 성과나 업적을 입력하세요...">{{ old('experience_info.achievements') }}</textarea>
                            @error('experience_info.achievements')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 동적 필드: 이전 회사 -->
                        <div id="previous_companies_section">
                            <label class="form-label">이전 근무 회사</label>
                            <div id="previous_companies_list">
                                <!-- 동적으로 추가될 필드들 -->
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addPreviousCompany()">
                                <i class="fe fe-plus me-1"></i>회사 추가
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 기술 정보 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fe fe-code me-2"></i>기술 정보
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- 주요 기술 -->
                        <div class="mb-3">
                            <label class="form-label">주요 기술</label>
                            <div id="primary_skills_list">
                                <!-- 동적으로 추가될 태그들 -->
                            </div>
                            <div class="input-group">
                                <input type="text" class="form-control" id="primary_skill_input"
                                       placeholder="기술을 입력하고 Enter를 누르세요">
                                <button type="button" class="btn btn-outline-secondary" onclick="addSkill('primary')">
                                    추가
                                </button>
                            </div>
                        </div>

                        <!-- 보조 기술 -->
                        <div class="mb-3">
                            <label class="form-label">보조 기술</label>
                            <div id="secondary_skills_list">
                                <!-- 동적으로 추가될 태그들 -->
                            </div>
                            <div class="input-group">
                                <input type="text" class="form-control" id="secondary_skill_input"
                                       placeholder="기술을 입력하고 Enter를 누르세요">
                                <button type="button" class="btn btn-outline-secondary" onclick="addSkill('secondary')">
                                    추가
                                </button>
                            </div>
                        </div>

                        <!-- 프로그래밍 언어 -->
                        <div class="mb-3">
                            <label class="form-label">프로그래밍 언어</label>
                            <div id="programming_languages_list">
                                <!-- 동적으로 추가될 태그들 -->
                            </div>
                            <div class="input-group">
                                <input type="text" class="form-control" id="programming_language_input"
                                       placeholder="언어를 입력하고 Enter를 누르세요">
                                <button type="button" class="btn btn-outline-secondary" onclick="addSkill('programming')">
                                    추가
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 근무 조건 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fe fe-clock me-2"></i>근무 조건
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="expected_hourly_rate" class="form-label">희망 시급 (원)</label>
                                <input type="number"
                                       class="form-control @error('expected_hourly_rate') is-invalid @enderror"
                                       name="expected_hourly_rate"
                                       id="expected_hourly_rate"
                                       value="{{ old('expected_hourly_rate') }}"
                                       min="0" step="1000">
                                @error('expected_hourly_rate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- 선호 근무 지역 -->
                        <div class="mb-3">
                            <label class="form-label">선호 근무 지역</label>
                            <div id="preferred_areas_list">
                                <!-- 동적으로 추가될 태그들 -->
                            </div>
                            <div class="input-group">
                                <input type="text" class="form-control" id="preferred_area_input"
                                       placeholder="지역을 입력하고 Enter를 누르세요">
                                <button type="button" class="btn btn-outline-secondary" onclick="addPreferredArea()">
                                    추가
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 추가 정보 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fe fe-info me-2"></i>추가 정보
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="motivation" class="form-label">지원 동기</label>
                            <textarea class="form-control @error('motivation') is-invalid @enderror"
                                      name="motivation"
                                      id="motivation"
                                      rows="4"
                                      placeholder="파트너 지원 동기를 입력하세요...">{{ old('motivation') }}</textarea>
                            @error('motivation')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="goals" class="form-label">목표</label>
                            <textarea class="form-control @error('goals') is-invalid @enderror"
                                      name="goals"
                                      id="goals"
                                      rows="4"
                                      placeholder="향후 목표를 입력하세요...">{{ old('goals') }}</textarea>
                            @error('goals')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="admin_notes" class="form-label">관리자 메모</label>
                            <textarea class="form-control @error('admin_notes') is-invalid @enderror"
                                      name="admin_notes"
                                      id="admin_notes"
                                      rows="3"
                                      placeholder="관리자 메모를 입력하세요...">{{ old('admin_notes') }}</textarea>
                            @error('admin_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- 파일 업로드 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fe fe-paperclip me-2"></i>첨부 파일
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="documents" class="form-label">서류 첨부</label>
                            <input type="file"
                                   class="form-control @error('documents.*') is-invalid @enderror"
                                   name="documents[]"
                                   id="documents"
                                   multiple
                                   accept=".pdf,.doc,.docx,.jpg,.png">
                            <small class="text-muted">PDF, Word, 이미지 파일을 업로드할 수 있습니다 (각 파일 최대 5MB)</small>
                            @error('documents.*')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- 오른쪽 사이드바 -->
            <div class="col-lg-4">
                <!-- 신청서 상태 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">신청서 상태</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="application_status" class="form-label">상태 <span class="text-danger">*</span></label>
                            <select name="application_status"
                                    id="application_status"
                                    class="form-control @error('application_status') is-invalid @enderror"
                                    required>
                                @foreach($statusOptions as $value => $label)
                                    <option value="{{ $value }}" {{ old('application_status', 'submitted') == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('application_status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- 추천자 정보 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">추천자 정보</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="referrer_name" class="form-label">추천자 이름</label>
                            <input type="text"
                                   class="form-control @error('referrer_name') is-invalid @enderror"
                                   name="referrer_name"
                                   id="referrer_name"
                                   value="{{ old('referrer_name') }}">
                            @error('referrer_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="referrer_contact" class="form-label">추천자 연락처</label>
                            <input type="text"
                                   class="form-control @error('referrer_contact') is-invalid @enderror"
                                   name="referrer_contact"
                                   id="referrer_contact"
                                   value="{{ old('referrer_contact') }}">
                            @error('referrer_contact')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="referrer_relationship" class="form-label">관계</label>
                            <input type="text"
                                   class="form-control @error('referrer_relationship') is-invalid @enderror"
                                   name="referrer_relationship"
                                   id="referrer_relationship"
                                   value="{{ old('referrer_relationship') }}"
                                   placeholder="예: 동료, 친구, 상사 등">
                            @error('referrer_relationship')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="referral_code" class="form-label">추천 코드</label>
                            <input type="text"
                                   class="form-control @error('referral_code') is-invalid @enderror"
                                   name="referral_code"
                                   id="referral_code"
                                   value="{{ old('referral_code') }}">
                            @error('referral_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- 제출 -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fe fe-save me-1"></i>신청서 등록
                            </button>
                            <a href="{{ route('admin.partner.applications.index') }}" class="btn btn-secondary">
                                <i class="fe fe-x me-1"></i>취소
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
// 동적 필드 관리를 위한 전역 변수들
let primarySkills = [];
let secondarySkills = [];
let programmingLanguages = [];
let preferredAreas = [];
let previousCompanies = [];

// 이전 회사 추가
function addPreviousCompany() {
    const list = document.getElementById('previous_companies_list');
    const index = previousCompanies.length;

    const companyDiv = document.createElement('div');
    companyDiv.className = 'mb-2 company-item';
    companyDiv.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <input type="text" class="form-control"
                       name="experience_info[previous_companies][${index}][name]"
                       placeholder="회사명" required>
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control"
                       name="experience_info[previous_companies][${index}][period]"
                       placeholder="근무기간">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="removeCompany(this)">
                    <i class="fe fe-trash-2"></i>
                </button>
            </div>
        </div>
    `;

    list.appendChild(companyDiv);
    previousCompanies.push({});
}

// 이전 회사 제거
function removeCompany(button) {
    const companyItem = button.closest('.company-item');
    companyItem.remove();
}

// 기술 추가
function addSkill(type) {
    const inputId = type + '_skill_input';
    const listId = type + '_skills_list';

    if (type === 'programming') {
        inputId = 'programming_language_input';
        listId = 'programming_languages_list';
    }

    const input = document.getElementById(inputId);
    const list = document.getElementById(listId);
    const skill = input.value.trim();

    if (skill && !getSkillArray(type).includes(skill)) {
        getSkillArray(type).push(skill);

        const badge = document.createElement('span');
        badge.className = 'badge bg-primary me-1 mb-1';
        badge.innerHTML = `
            ${skill}
            <button type="button" class="btn-close btn-close-white ms-1" onclick="removeSkill('${skill}', '${type}')"></button>
            <input type="hidden" name="${getSkillFieldName(type)}[]" value="${skill}">
        `;

        list.appendChild(badge);
        input.value = '';
    }
}

// 기술 제거
function removeSkill(skill, type) {
    const array = getSkillArray(type);
    const index = array.indexOf(skill);
    if (index > -1) {
        array.splice(index, 1);
    }

    // DOM에서 제거
    const list = document.getElementById(getSkillListId(type));
    const badges = list.querySelectorAll('.badge');
    badges.forEach(badge => {
        if (badge.textContent.trim().startsWith(skill)) {
            badge.remove();
        }
    });
}

// 선호 지역 추가
function addPreferredArea() {
    const input = document.getElementById('preferred_area_input');
    const list = document.getElementById('preferred_areas_list');
    const area = input.value.trim();

    if (area && !preferredAreas.includes(area)) {
        preferredAreas.push(area);

        const badge = document.createElement('span');
        badge.className = 'badge bg-secondary me-1 mb-1';
        badge.innerHTML = `
            ${area}
            <button type="button" class="btn-close btn-close-white ms-1" onclick="removePreferredArea('${area}')"></button>
            <input type="hidden" name="preferred_work_areas[]" value="${area}">
        `;

        list.appendChild(badge);
        input.value = '';
    }
}

// 선호 지역 제거
function removePreferredArea(area) {
    const index = preferredAreas.indexOf(area);
    if (index > -1) {
        preferredAreas.splice(index, 1);
    }

    const list = document.getElementById('preferred_areas_list');
    const badges = list.querySelectorAll('.badge');
    badges.forEach(badge => {
        if (badge.textContent.trim().startsWith(area)) {
            badge.remove();
        }
    });
}

// 헬퍼 함수들
function getSkillArray(type) {
    switch(type) {
        case 'primary': return primarySkills;
        case 'secondary': return secondarySkills;
        case 'programming': return programmingLanguages;
        default: return [];
    }
}

function getSkillFieldName(type) {
    switch(type) {
        case 'primary': return 'skills_info[primary_skills]';
        case 'secondary': return 'skills_info[secondary_skills]';
        case 'programming': return 'skills_info[programming_languages]';
        default: return '';
    }
}

function getSkillListId(type) {
    switch(type) {
        case 'primary': return 'primary_skills_list';
        case 'secondary': return 'secondary_skills_list';
        case 'programming': return 'programming_languages_list';
        default: return '';
    }
}

// Enter 키 이벤트 리스너
document.addEventListener('DOMContentLoaded', function() {
    // 기술 입력 필드들에 Enter 키 이벤트 추가
    ['primary_skill_input', 'secondary_skill_input', 'programming_language_input', 'preferred_area_input'].forEach(id => {
        const input = document.getElementById(id);
        if (input) {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();

                    if (id.includes('preferred_area')) {
                        addPreferredArea();
                    } else if (id.includes('programming')) {
                        addSkill('programming');
                    } else if (id.includes('secondary')) {
                        addSkill('secondary');
                    } else if (id.includes('primary')) {
                        addSkill('primary');
                    }
                }
            });
        }
    });

    // AJAX 폼 제출 처리
    document.getElementById('applicationForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const userId = document.getElementById('user_id').value;
        const name = document.getElementById('name').value.trim();
        const email = document.getElementById('email').value.trim();

        // 유효성 검사
        if (!userId) {
            showAlert('사용자를 먼저 검색하고 선택해주세요.', 'error');
            return false;
        }

        if (!name || !email) {
            showAlert('이름과 이메일은 필수 입력 항목입니다.', 'error');
            return false;
        }

        // 제출 버튼 비활성화
        const submitButton = document.querySelector('button[type="submit"]');
        const originalText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fe fe-loader me-1"></i>등록 중...';

        // FormData 생성
        const formData = new FormData(this);

        // AJAX 요청
        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message || '파트너 신청서가 성공적으로 등록되었습니다.', 'success');

                // 2초 후 목록으로 이동
                setTimeout(() => {
                    window.location.href = data.redirect || '{{ route("admin.partner.applications.index") }}';
                }, 2000);
            } else {
                // 검증 오류 처리
                if (data.errors) {
                    displayValidationErrors(data.errors);
                } else {
                    showAlert(data.message || '신청서 등록 중 오류가 발생했습니다.', 'error');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('네트워크 오류가 발생했습니다. 다시 시도해주세요.', 'error');
        })
        .finally(() => {
            // 제출 버튼 복원
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        });
    });
});

// 알림 표시 함수
function showAlert(message, type = 'info') {
    // 기존 알림 제거
    const existingAlerts = document.querySelectorAll('.alert-ajax');
    existingAlerts.forEach(alert => alert.remove());

    const alertClass = type === 'success' ? 'alert-success' : type === 'error' ? 'alert-danger' : 'alert-info';
    const iconClass = type === 'success' ? 'fe-check-circle' : type === 'error' ? 'fe-x-circle' : 'fe-info';

    const alertDiv = document.createElement('div');
    alertDiv.className = `alert ${alertClass} alert-dismissible fade show alert-ajax`;
    alertDiv.innerHTML = `
        <i class="fe ${iconClass} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    // 폼 위에 삽입
    const form = document.getElementById('applicationForm');
    form.parentNode.insertBefore(alertDiv, form);

    // 3초 후 자동 제거 (성공 메시지가 아닌 경우)
    if (type !== 'success') {
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
}

// 검증 오류 표시 함수
function displayValidationErrors(errors) {
    // 기존 오류 메시지 제거
    const existingErrors = document.querySelectorAll('.invalid-feedback');
    existingErrors.forEach(error => error.remove());

    const existingInvalidInputs = document.querySelectorAll('.is-invalid');
    existingInvalidInputs.forEach(input => input.classList.remove('is-invalid'));

    let firstErrorField = null;

    // 각 오류에 대해 처리
    Object.keys(errors).forEach(fieldName => {
        const errorMessages = errors[fieldName];

        // 필드명 변환 (점 표기법을 name 속성으로)
        let inputName = fieldName;
        if (fieldName.includes('.')) {
            // personal_info.phone -> personal_info[phone] 형태로 변환
            const parts = fieldName.split('.');
            if (parts.length === 2) {
                inputName = `${parts[0]}[${parts[1]}]`;
            }
        }

        // 해당 입력 필드 찾기
        const input = document.querySelector(`[name="${inputName}"]`);

        if (input) {
            // 첫 번째 오류 필드 기억
            if (!firstErrorField) {
                firstErrorField = input;
            }

            // is-invalid 클래스 추가
            input.classList.add('is-invalid');

            // 오류 메시지 생성
            const errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            errorDiv.textContent = errorMessages[0]; // 첫 번째 오류 메시지만 표시

            // 입력 필드 다음에 오류 메시지 삽입
            input.parentNode.insertBefore(errorDiv, input.nextSibling);
        }
    });

    // 첫 번째 오류 필드로 스크롤
    if (firstErrorField) {
        firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        firstErrorField.focus();
    }

    // 오류 요약 메시지 표시
    const errorCount = Object.keys(errors).length;
    showAlert(`입력 정보를 확인해주세요. ${errorCount}개의 오류가 있습니다.`, 'error');
}
</script>
@endpush

@push('styles')
<style>
.badge .btn-close {
    font-size: 0.7rem;
    opacity: 0.8;
}

.badge .btn-close:hover {
    opacity: 1;
}

.company-item {
    padding: 10px;
    border: 1px solid #e9ecef;
    border-radius: 5px;
    margin-bottom: 10px;
}

.skill-tag {
    display: inline-block;
    margin: 2px;
}

#previous_companies_list:empty + button {
    margin-top: 10px;
}

.form-control:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

/* AJAX 알림 스타일 */
.alert-ajax {
    position: relative;
    z-index: 1050;
    margin-bottom: 1rem;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* 로딩 스피너 */
.fe-loader {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

/* 폼 비활성화 상태 */
form.submitting {
    pointer-events: none;
    opacity: 0.7;
}

/* 검증 오류 필드 강조 */
.form-control.is-invalid {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.invalid-feedback {
    color: #dc3545;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}
</style>
@endpush