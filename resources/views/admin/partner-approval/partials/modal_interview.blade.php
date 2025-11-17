<div class="modal fade" id="interviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    @if ($application->application_status === 'interview')
                        면접 일정 수정
                    @else
                        면접 일정 설정
                    @endif
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="interviewForm"
                action="{{ $application->application_status === 'interview' ? route('admin.partner.approval.interview.update', $application->id) : route('admin.partner.approval.interview.schedule', $application->id) }}"
                method="POST">
                @csrf
                @if ($application->application_status === 'interview')
                    @method('PUT')
                @endif
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fe fe-calendar me-2"></i>
                        <strong>
                            @if ($application->application_status === 'interview')
                                면접 일정 수정
                            @else
                                면접 일정 설정
                            @endif
                        </strong><br>
                        @if ($application->application_status === 'interview')
                            기존 면접 일정을 수정합니다. 지원자에게 변경된 일정이 통지됩니다.
                        @else
                            지원자에게 면접 일정이 통지됩니다.
                        @endif
                    </div>

                    <div class="form-group mb-3">
                        <label for="interview_date">면접 일시 <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="interview_date" id="interview_date" class="form-control"
                            required
                            @if ($application->interview_date) value="{{ $application->interview_date->format('Y-m-d\TH:i') }}" @endif>
                    </div>

                    <div class="form-group mb-3">
                        <label for="interview_location">면접 장소</label>
                        <input type="text" name="interview_location" id="interview_location" class="form-control"
                            placeholder="면접 장소를 입력하세요 (예: 본사 회의실 A, 온라인 화상면접 등)"
                            @if ($application->interview_feedback && isset($application->interview_feedback['location'])) value="{{ $application->interview_feedback['location'] }}" @endif>
                    </div>

                    <div class="form-group mb-3">
                        <label for="interview_type">면접 유형</label>
                        <select name="interview_type" id="interview_type" class="form-control">
                            <option value="video" @if(isset($application->interview_feedback['type']) && $application->interview_feedback['type'] === 'video') selected @endif>화상 면접</option>
                            <option value="phone" @if(isset($application->interview_feedback['type']) && $application->interview_feedback['type'] === 'phone') selected @endif>전화 면접</option>
                            <option value="in_person" @if(isset($application->interview_feedback['type']) && $application->interview_feedback['type'] === 'in_person') selected @endif>대면 면접</option>
                            <option value="written" @if(isset($application->interview_feedback['type']) && $application->interview_feedback['type'] === 'written') selected @endif>서면 면접</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label for="interview_notes">면접 안내사항</label>
                        <textarea name="interview_notes" id="interview_notes" class="form-control" rows="3"
                            placeholder="면접에 대한 추가 안내사항을 입력하세요...">{{ $application->interview_notes ?? '' }}</textarea>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="notify_user"
                            id="notify_user_interview" value="1" checked>
                        <label class="form-check-label" for="notify_user_interview">
                            지원자에게 면접 일정 알림 전송
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fe fe-calendar me-2"></i>면접 설정
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
/**
 * 면접 설정 모달 관리 스크립트
 */

// 면접 모달 표시 함수
function showInterviewModal() {
    new bootstrap.Modal(document.getElementById('interviewModal')).show();
}

// DOM이 로드된 후 이벤트 리스너 등록
document.addEventListener('DOMContentLoaded', function() {
    // 면접 설정 폼 AJAX 처리
    const interviewForm = document.getElementById('interviewForm');
    if (interviewForm) {
        interviewForm.addEventListener('submit', function(e) {
            e.preventDefault(); // 기본 폼 제출 방지

            const form = this;
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;

            // 버튼 상태 변경
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="spinner-border spinner-border-sm me-2"></i>처리 중...';

            // AJAX 요청
            fetch(form.action, {
                    method: form.method,
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    // 응답이 JSON이 아닌 경우 처리
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const contentType = response.headers.get("content-type");
                    if (!contentType || !contentType.includes("application/json")) {
                        throw new Error("응답이 JSON 형식이 아닙니다.");
                    }

                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // 성공 메시지 표시 (showToast 함수 사용)
                        if (typeof showToast === 'function') {
                            showToast('success', data.message);
                        } else {
                            alert(data.message);
                        }

                        // 모달 닫기
                        bootstrap.Modal.getInstance(document.getElementById('interviewModal')).hide();

                        // 면접 관리 페이지로 리다이렉트
                        // Redirect to interview management page
                        setTimeout(() => {
                            if (data.redirect) {
                                window.location.href = data.redirect;
                            } else {
                                // 백업 URL - 면접 관리 페이지
                                window.location.href = '/admin/partner/interview';
                            }
                        }, 500); // 더 빠른 리다이렉트를 위해 시간 단축
                    } else {
                        // 오류 메시지 표시
                        const errorMessage = data.message || data.error || '면접 설정 중 오류가 발생했습니다.';
                        if (typeof showToast === 'function') {
                            showToast('error', errorMessage);
                        } else {
                            alert(errorMessage);
                        }
                    }
                })
                .catch(error => {
                    console.error('면접 설정 오류:', error);

                    let errorMessage = '면접 설정 중 오류가 발생했습니다.';

                    if (error.message.includes('HTTP error')) {
                        errorMessage = '서버 오류가 발생했습니다. 관리자에게 문의하세요.';
                    } else if (error.message.includes('JSON')) {
                        errorMessage = '서버 응답 형식 오류가 발생했습니다.';
                    } else if (error.name === 'TypeError') {
                        errorMessage = '네트워크 연결을 확인하세요.';
                    }

                    if (typeof showToast === 'function') {
                        showToast('error', errorMessage);
                    } else {
                        alert(errorMessage);
                    }
                })
                .finally(() => {
                    // 버튼 상태 복원
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                });
        });
    }

    // 날짜 유효성 검사
    const interviewDateInput = document.getElementById('interview_date');
    if (interviewDateInput) {
        interviewDateInput.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const now = new Date();

            // 과거 날짜 선택 방지
            if (selectedDate < now) {
                this.classList.add('is-invalid');
                this.setCustomValidity('과거 날짜는 선택할 수 없습니다.');
            } else {
                this.classList.remove('is-invalid');
                this.setCustomValidity('');
            }
        });
    }

    // 면접 타입별 장소 제안 관리
    const interviewLocationInput = document.getElementById('interview_location');
    const interviewTypeSelect = document.getElementById('interview_type');

    // 면접 타입별 장소 제안 데이터
    const locationSuggestions = {
        video: [
            '온라인 화상면접 (Zoom)',
            '온라인 화상면접 (Google Meet)',
            '온라인 화상면접 (Microsoft Teams)',
            '온라인 화상면접 (Webex)',
            '온라인 화상면접 (Skype)'
        ],
        phone: [
            '전화 면접',
            '휴대폰 면접',
            '유선 전화 면접'
        ],
        in_person: [
            '본사 회의실 A',
            '본사 회의실 B',
            '지점 사무실',
            '카페 미팅룸',
            '호텔 라운지',
            '공용 회의실'
        ],
        written: [
            '이메일 제출',
            '온라인 플랫폼',
            '서면 과제 제출',
            '구글 폼 제출'
        ]
    };

    if (interviewLocationInput && interviewTypeSelect) {
        // 면접 타입 변경시 장소 초기화 및 placeholder 변경
        interviewTypeSelect.addEventListener('change', function() {
            const selectedType = this.value;
            interviewLocationInput.value = '';

            // 타입별 placeholder 변경
            const placeholders = {
                video: '화상면접 링크나 플랫폼을 입력하세요',
                phone: '연락받을 전화번호를 입력하세요',
                in_person: '면접 장소를 입력하세요',
                written: '서면 제출 방법을 입력하세요'
            };

            interviewLocationInput.placeholder = placeholders[selectedType] || '면접 장소를 입력하세요';
        });

        // 포커스 시 현재 면접 타입에 맞는 제안 목록 표시
        interviewLocationInput.addEventListener('focus', function() {
            const currentType = interviewTypeSelect.value || 'video';
            const suggestions = locationSuggestions[currentType] || locationSuggestions.video;
            showLocationSuggestions(this, suggestions);
        });
    }
});

// 면접 장소 자동완성 제안 함수
function showLocationSuggestions(input, suggestions) {
    // 기존 제안 목록 제거
    const existingDropdown = document.querySelector('.location-suggestions');
    if (existingDropdown) {
        existingDropdown.remove();
    }

    // 제안 목록 생성
    const dropdown = document.createElement('div');
    dropdown.className = 'location-suggestions position-absolute bg-white border rounded shadow-sm';
    dropdown.style.cssText = 'top: 100%; left: 0; right: 0; z-index: 1000; max-height: 200px; overflow-y: auto;';

    suggestions.forEach(suggestion => {
        const item = document.createElement('div');
        item.className = 'px-3 py-2 cursor-pointer hover-bg-light';
        item.textContent = suggestion;
        item.style.cursor = 'pointer';

        item.addEventListener('click', function() {
            input.value = suggestion;
            dropdown.remove();
        });

        item.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#f8f9fa';
        });

        item.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });

        dropdown.appendChild(item);
    });

    // 입력 필드 위치에 상대적으로 배치
    input.parentElement.style.position = 'relative';
    input.parentElement.appendChild(dropdown);

    // 외부 클릭 시 제안 목록 숨기기
    document.addEventListener('click', function hideDropdown(e) {
        if (!input.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.remove();
            document.removeEventListener('click', hideDropdown);
        }
    });
}
</script>
