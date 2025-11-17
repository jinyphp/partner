<div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">파트너 신청 거부</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="rejectForm" action="{{ route('admin.partner.approval.reject', $application->id) }}"
                    method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fe fe-alert-triangle me-2"></i>
                            <strong>거부 확인</strong><br>
                            이 작업은 되돌릴 수 없습니다. 신중하게 검토해주세요.
                        </div>

                        <div class="form-group mb-3">
                            <label for="rejection_reason">거부 사유 <span class="text-danger">*</span></label>
                            <textarea name="rejection_reason" id="rejection_reason" class="form-control" rows="4"
                                placeholder="거부 사유를 상세히 입력해주세요..." required></textarea>
                            <small class="form-text text-muted">지원자가 개선할 수 있도록 구체적인 피드백을 제공해주세요.</small>
                        </div>

                        <div class="form-group mb-3">
                            <label for="admin_notes_reject">관리자 메모 (선택사항)</label>
                            <textarea name="admin_notes" id="admin_notes_reject" class="form-control" rows="2"
                                placeholder="내부 참고용 메모를 입력하세요..."></textarea>
                        </div>

                        <div class="form-group mb-3">
                            <label for="feedback_message">지원자 피드백 메시지 (선택사항)</label>
                            <textarea name="feedback_message" id="feedback_message" class="form-control" rows="3"
                                placeholder="지원자에게 전달할 추가 피드백 메시지를 입력하세요..."></textarea>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="notify_user" id="notify_user_reject"
                                value="1" checked>
                            <label class="form-check-label" for="notify_user_reject">
                                지원자에게 거부 알림 전송
                            </label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="allow_reapply" id="allow_reapply"
                                value="1" checked>
                            <label class="form-check-label" for="allow_reapply">
                                재신청 허용
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fe fe-x me-2"></i>거부 확인
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
/**
 * 거부 모달 관리 스크립트
 */

// 거부 모달 표시 함수
function showRejectModal() {
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

// DOM이 로드된 후 이벤트 리스너 등록
document.addEventListener('DOMContentLoaded', function() {
    // 거부 폼 AJAX 처리
    const rejectForm = document.getElementById('rejectForm');
    if (rejectForm) {
        rejectForm.addEventListener('submit', function(e) {
            e.preventDefault(); // 기본 폼 제출 방지

            const form = this;
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;

            // 버튼 상태 변경
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="spinner-border spinner-border-sm me-2"></i>처리 중...';

            // notify_user와 allow_reapply 값을 명시적으로 설정
            const notifyCheckbox = form.querySelector('input[name="notify_user"]');
            const allowReapplyCheckbox = form.querySelector('input[name="allow_reapply"]');

            if (!notifyCheckbox.checked) {
                formData.set('notify_user', '0');
            }
            if (!allowReapplyCheckbox.checked) {
                formData.set('allow_reapply', '0');
            }

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
                        }

                        // 모달 닫기
                        bootstrap.Modal.getInstance(document.getElementById('rejectModal')).hide();

                        // 페이지 새로고침 (데이터 업데이트 반영)
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        // 오류 메시지 표시
                        if (typeof showToast === 'function') {
                            showToast('error', data.message || '거부 처리 중 오류가 발생했습니다.');
                        } else {
                            alert(data.message || '거부 처리 중 오류가 발생했습니다.');
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

    // 거부 사유 입력 카운터 추가
    const rejectionReasonTextarea = document.getElementById('rejection_reason');
    if (rejectionReasonTextarea) {
        // 글자 수 카운터 추가
        const counterDiv = document.createElement('div');
        counterDiv.className = 'text-end text-muted small mt-1';
        counterDiv.innerHTML = '<span id="rejection_reason_counter">0</span>/500자';
        rejectionReasonTextarea.parentElement.appendChild(counterDiv);

        // 최대 길이 설정
        rejectionReasonTextarea.setAttribute('maxlength', '500');

        // 실시간 글자 수 업데이트
        rejectionReasonTextarea.addEventListener('input', function() {
            const counter = document.getElementById('rejection_reason_counter');
            if (counter) {
                counter.textContent = this.value.length;

                // 글자 수에 따라 색상 변경
                if (this.value.length > 450) {
                    counter.parentElement.className = 'text-end text-danger small mt-1';
                } else if (this.value.length > 400) {
                    counter.parentElement.className = 'text-end text-warning small mt-1';
                } else {
                    counter.parentElement.className = 'text-end text-muted small mt-1';
                }
            }
        });

        // 유용한 거부 사유 템플릿 제공
        rejectionReasonTextarea.addEventListener('focus', function() {
            if (this.value.trim() === '') {
                showRejectionReasonSuggestions(this);
            }
        });
    }

    // 피드백 메시지 글자 수 제한
    const feedbackMessageTextarea = document.getElementById('feedback_message');
    if (feedbackMessageTextarea) {
        feedbackMessageTextarea.setAttribute('maxlength', '300');

        // 글자 수 카운터 추가
        const counterDiv = document.createElement('div');
        counterDiv.className = 'text-end text-muted small mt-1';
        counterDiv.innerHTML = '<span id="feedback_counter">0</span>/300자';
        feedbackMessageTextarea.parentElement.appendChild(counterDiv);

        feedbackMessageTextarea.addEventListener('input', function() {
            const counter = document.getElementById('feedback_counter');
            if (counter) {
                counter.textContent = this.value.length;
            }
        });
    }
});

// 거부 사유 템플릿 제안 함수
function showRejectionReasonSuggestions(textarea) {
    const suggestions = [
        '경력 요구사항이 충족되지 않습니다.',
        '제출된 포트폴리오가 업무 요구사항에 부합하지 않습니다.',
        '면접 과정에서 업무 적합성이 확인되지 않았습니다.',
        '현재 채용 계획이 변경되어 진행이 어렵습니다.',
        '기술 스택이 현재 프로젝트 요구사항과 맞지 않습니다.'
    ];

    // 제안 목록 생성
    const suggestionContainer = document.createElement('div');
    suggestionContainer.className = 'mt-2 p-2 border rounded bg-light';
    suggestionContainer.innerHTML = `
        <small class="text-muted">자주 사용되는 거부 사유:</small>
        <div class="mt-1">
            ${suggestions.map(suggestion =>
                `<button type="button" class="btn btn-sm btn-outline-secondary me-1 mb-1 suggestion-btn"
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
    suggestionContainer.querySelectorAll('.suggestion-btn').forEach(btn => {
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
