<div class="modal fade" id="quickApproveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">파트너 신청 승인</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="quickApproveForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-success d-flex align-items-center">
                        <i class="fe fe-check-circle me-2"></i>
                        <div>
                            <strong>승인 확인</strong><br>
                            이 지원자를 파트너로 승인하면 자동으로 파트너 회원으로 등록됩니다.
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="quick_admin_notes">관리자 메모 (선택사항)</label>
                        <textarea name="admin_notes" id="quick_admin_notes" class="form-control" rows="3"
                            placeholder="승인과 관련된 메모를 입력하세요..."></textarea>
                    </div>

                    <div class="form-group mb-3">
                        <label for="quick_welcome_message">환영 메시지 (선택사항)</label>
                        <textarea name="welcome_message" id="quick_welcome_message" class="form-control" rows="2"
                            placeholder="승인 알림과 함께 전송할 환영 메시지를 입력하세요..."></textarea>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="notify_user" id="quick_notify_user" value="1" checked>
                        <label class="form-check-label" for="quick_notify_user">
                            지원자에게 승인 알림 전송
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fe fe-check me-2"></i>승인 확인
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
/**
 * 공통 승인 모달 관리 스크립트 (modal_approve.blade.php와 동일한 방식)
 */

// 승인 모달 표시 함수
function quickApprove(applicationId) {
    currentApplicationId = applicationId;

    // 폼 action 설정
    const form = document.getElementById('quickApproveForm');
    form.action = `{{ url('admin/partner/approval') }}/${applicationId}/approve`;

    // 모달 표시
    new bootstrap.Modal(document.getElementById('quickApproveModal')).show();
}

// DOM 로드 후 이벤트 리스너 등록
document.addEventListener('DOMContentLoaded', function() {
    // 승인 폼 AJAX 처리
    const quickApproveForm = document.getElementById('quickApproveForm');
    if (quickApproveForm) {
        quickApproveForm.addEventListener('submit', function(e) {
            e.preventDefault(); // 기본 폼 제출 방지

            const form = this;
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;

            // 버튼 상태 변경
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="spinner-border spinner-border-sm me-2"></i>처리 중...';

            // notify_user 값을 명시적으로 설정 (체크되지 않은 경우 0으로)
            const notifyCheckbox = form.querySelector('input[name="notify_user"]');
            if (!notifyCheckbox.checked) {
                formData.set('notify_user', '0');
            }

            // 승인 요청 전송

            // AJAX 요청
            fetch(form.action, {
                    method: form.method,
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // 성공 메시지 표시 (showToast 함수 사용)
                        if (typeof showToast === 'function') {
                            showToast('success', data.message);
                        } else {
                            alert(data.message || '파트너 신청이 승인되었습니다.');
                        }

                        // 모달 닫기
                        bootstrap.Modal.getInstance(document.getElementById('quickApproveModal')).hide();

                        // 페이지 새로고침 (데이터 업데이트 반영)
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        // 오류 메시지 표시
                        if (typeof showToast === 'function') {
                            showToast('error', data.message || '승인 처리 중 오류가 발생했습니다.');
                        } else {
                            alert(data.message || '승인 처리 중 오류가 발생했습니다.');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (typeof showToast === 'function') {
                        showToast('error', '네트워크 오류가 발생했습니다.');
                    } else {
                        alert('네트워크 오류가 발생했습니다.');
                    }
                })
                .finally(() => {
                    // 버튼 상태 복원
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                });
        });
    }

    // 관리자 메모 글자 수 제한 (동적 카운터 생성)
    const adminNotesTextarea = document.getElementById('quick_admin_notes');
    if (adminNotesTextarea) {
        adminNotesTextarea.setAttribute('maxlength', '300');

        // 글자 수 카운터 추가
        const counterDiv = document.createElement('div');
        counterDiv.className = 'text-end text-muted small mt-1';
        counterDiv.innerHTML = '<span id="quick_admin_notes_counter">0</span>/300자';
        adminNotesTextarea.parentElement.appendChild(counterDiv);

        adminNotesTextarea.addEventListener('input', function() {
            const counter = document.getElementById('quick_admin_notes_counter');
            if (counter) {
                counter.textContent = this.value.length;

                // 글자 수에 따라 색상 변경
                if (this.value.length > 250) {
                    counter.parentElement.className = 'text-end text-warning small mt-1';
                } else {
                    counter.parentElement.className = 'text-end text-muted small mt-1';
                }
            }
        });
    }

    // 환영 메시지 글자 수 제한 (동적 카운터 생성)
    const welcomeMessageTextarea = document.getElementById('quick_welcome_message');
    if (welcomeMessageTextarea) {
        welcomeMessageTextarea.setAttribute('maxlength', '200');

        // 글자 수 카운터 추가
        const counterDiv = document.createElement('div');
        counterDiv.className = 'text-end text-muted small mt-1';
        counterDiv.innerHTML = '<span id="quick_welcome_message_counter">0</span>/200자';
        welcomeMessageTextarea.parentElement.appendChild(counterDiv);

        welcomeMessageTextarea.addEventListener('input', function() {
            const counter = document.getElementById('quick_welcome_message_counter');
            if (counter) {
                counter.textContent = this.value.length;
            }
        });

        // 환영 메시지 템플릿 제공 (modal_approve.blade.php와 동일)
        welcomeMessageTextarea.addEventListener('focus', function() {
            if (this.value.trim() === '') {
                showWelcomeMessageSuggestions(this);
            }
        });
    }
});

// 환영 메시지 템플릿 제안 함수 (modal_approve.blade.php와 동일)
function showWelcomeMessageSuggestions(textarea) {
    const suggestions = [
        '파트너로 합류해 주셔서 감사합니다. 함께 성장해 나가요!',
        '환영합니다! 파트너십을 통해 좋은 성과를 기대합니다.',
        '파트너 승인을 축하드립니다. 앞으로 잘 부탁드립니다.',
        '저희 파트너가 되어주셔서 감사합니다. 성공적인 협력을 기대합니다.',
        '파트너 등록이 완료되었습니다. 곧 상세 안내를 전달드리겠습니다.'
    ];

    // 제안 목록 생성
    const suggestionContainer = document.createElement('div');
    suggestionContainer.className = 'mt-2 p-2 border rounded bg-light';
    suggestionContainer.innerHTML = `
        <small class="text-muted">환영 메시지 템플릿:</small>
        <div class="mt-1">
            ${suggestions.map(suggestion =>
                `<button type="button" class="btn btn-sm btn-outline-success me-1 mb-1 welcome-suggestion-btn"
                         data-suggestion="${suggestion}">${suggestion}</button>`
            ).join('')}
        </div>
    `;

    // 기존 제안 제거
    const existingSuggestion = textarea.parentElement.querySelector('.mt-2.p-2.border');
    if (existingSuggestion) {
        existingSuggestion.remove();
    }

    // 제안 목록 추가
    textarea.parentElement.appendChild(suggestionContainer);

    // 제안 클릭 이벤트
    suggestionContainer.querySelectorAll('.welcome-suggestion-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            textarea.value = this.dataset.suggestion;
            textarea.focus();
            suggestionContainer.remove();
            // 글자 수 업데이트
            textarea.dispatchEvent(new Event('input'));
        });
    });

    // 외부 클릭 시 제안 숨기기
    document.addEventListener('click', function hideSuggestions(e) {
        if (!textarea.contains(e.target) && !suggestionContainer.contains(e.target)) {
            suggestionContainer.remove();
            document.removeEventListener('click', hideSuggestions);
        }
    });
}
</script>
