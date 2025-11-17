<div class="modal fade" id="revokeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">파트너 승인 취소</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="revokeForm" action="{{ route('admin.partner.approval.revoke', $application->id) }}"
                method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fe fe-alert-triangle me-2"></i>
                        <strong>주의!</strong><br>
                        이 작업은 파트너 회원 등록을 해제하고 파트너 계정을 삭제합니다. 이 작업은 되돌릴 수 없습니다.
                    </div>

                    <div class="bg-light p-3 rounded mb-3">
                        <h6 class="mb-2">승인 취소 시 처리 사항:</h6>
                        <ul class="mb-0 small text-muted">
                            <li>파트너 회원 계정 완전 삭제</li>
                            <li>신청 상태를 "검토 중"으로 변경</li>
                            <li>파트너 권한 및 관련 데이터 초기화</li>
                            <li>하위 파트너 계층 구조 재조정</li>
                        </ul>
                    </div>

                    <div class="form-group mb-3">
                        <label for="revoke_reason">취소 사유 <span class="text-danger">*</span></label>
                        <textarea name="revoke_reason" id="revoke_reason" class="form-control" rows="4"
                            placeholder="승인 취소 사유를 상세히 입력해주세요..." required></textarea>
                        <small class="form-text text-muted">향후 참고를 위해 구체적인 취소 사유를 입력해주세요.</small>
                    </div>

                    <div class="form-group mb-3">
                        <label for="admin_notes_revoke">관리자 메모 (선택사항)</label>
                        <textarea name="admin_notes" id="admin_notes_revoke" class="form-control" rows="2"
                            placeholder="내부 참고용 메모를 입력하세요..."></textarea>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="notify_user" id="notify_user_revoke"
                            value="1" checked>
                        <label class="form-check-label" for="notify_user_revoke">
                            지원자에게 승인 취소 알림 전송
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fe fe-rotate-ccw me-2"></i>승인 취소 확인
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
/**
 * 승인 취소 모달 관리 스크립트
 */

// DOM이 로드된 후 이벤트 리스너 등록
document.addEventListener('DOMContentLoaded', function() {
    // 승인 취소 폼 AJAX 처리
    const revokeForm = document.getElementById('revokeForm');
    if (revokeForm) {
        revokeForm.addEventListener('submit', function(e) {
            e.preventDefault(); // 기본 폼 제출 방지

            const form = this;
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;

            // 취소 사유 검증
            const reason = formData.get('revoke_reason');
            if (!reason || reason.trim().length < 10) {
                alert('취소 사유를 최소 10글자 이상 입력해주세요.');
                return;
            }

            // 버튼 상태 변경
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="spinner-border spinner-border-sm me-2"></i>처리 중...';

            // notify_user 값을 명시적으로 설정
            const notifyCheckbox = form.querySelector('input[name="notify_user"]');
            if (!notifyCheckbox.checked) {
                formData.set('notify_user', '0');
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
                        bootstrap.Modal.getInstance(document.getElementById('revokeModal')).hide();

                        // 페이지 새로고침 (데이터 업데이트 반영)
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        // 오류 메시지 표시
                        if (typeof showToast === 'function') {
                            showToast('error', data.message || '승인 취소 처리 중 오류가 발생했습니다.');
                        } else {
                            alert(data.message || '승인 취소 처리 중 오류가 발생했습니다.');
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

    // 취소 사유 입력 카운터 추가
    const revokeReasonTextarea = document.getElementById('revoke_reason');
    if (revokeReasonTextarea) {
        // 글자 수 카운터 추가
        const counterDiv = document.createElement('div');
        counterDiv.className = 'text-end text-muted small mt-1';
        counterDiv.innerHTML = '<span id="revoke_reason_counter">0</span>/1000자';
        revokeReasonTextarea.parentElement.appendChild(counterDiv);

        // 최대 길이 설정
        revokeReasonTextarea.setAttribute('maxlength', '1000');

        // 실시간 글자 수 업데이트
        revokeReasonTextarea.addEventListener('input', function() {
            const counter = document.getElementById('revoke_reason_counter');
            if (counter) {
                counter.textContent = this.value.length;

                // 글자 수에 따라 색상 변경
                if (this.value.length > 900) {
                    counter.parentElement.className = 'text-end text-danger small mt-1';
                } else if (this.value.length > 800) {
                    counter.parentElement.className = 'text-end text-warning small mt-1';
                } else {
                    counter.parentElement.className = 'text-end text-muted small mt-1';
                }
            }
        });

        // 유용한 취소 사유 템플릿 제공
        revokeReasonTextarea.addEventListener('focus', function() {
            if (this.value.trim() === '') {
                showRevokeReasonSuggestions(this);
            }
        });
    }
});

// 취소 사유 템플릿 제안 함수
function showRevokeReasonSuggestions(textarea) {
    const suggestions = [
        '파트너 계약 조건 위반으로 인한 승인 취소',
        '부정확한 정보 제공이 확인되어 승인 철회',
        '업무 수행 능력 부족으로 파트너십 해지',
        '규정 위반 행위로 인한 자격 박탈',
        '기타 사유로 인한 파트너 승인 취소'
    ];

    // 제안 목록 생성
    const suggestionContainer = document.createElement('div');
    suggestionContainer.className = 'mt-2 p-2 border rounded bg-light';
    suggestionContainer.innerHTML = `
        <small class="text-muted">자주 사용되는 취소 사유:</small>
        <div class="mt-1">
            ${suggestions.map(suggestion =>
                `<button type="button" class="btn btn-sm btn-outline-warning me-1 mb-1 revoke-suggestion-btn"
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
    suggestionContainer.querySelectorAll('.revoke-suggestion-btn').forEach(btn => {
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