<!-- 상태 변경 모달 -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">상태 변경</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="new_status">

                <!-- 승인 타입 선택 -->
                <div class="mb-3" id="approval_type_group" style="display: none;">
                    <label for="approval_type" class="form-label">승인 타입</label>
                    <select class="form-control" id="approval_type">
                        <option value="">승인 타입을 선택하세요</option>
                        <option value="approved">승인</option>
                        <option value="pending">대기</option>
                        <option value="suspended">정지</option>
                    </select>
                    <small class="text-muted">승인 처리 방식을 선택하세요.</small>
                </div>

                <!-- 변경 사유 -->
                <div class="mb-3">
                    <label for="status_reason" class="form-label">변경 사유</label>
                    <textarea class="form-control" id="status_reason" rows="3" required></textarea>
                </div>

                <!-- 승인 메모 (승인시에만 표시) -->
                <div class="mb-3" id="approval_notes_group" style="display: none;">
                    <label for="approval_notes" class="form-label">승인 메모</label>
                    <textarea class="form-control" id="approval_notes" rows="2" placeholder="승인과 관련된 추가 메모를 입력하세요..."></textarea>
                    <small class="text-muted">승인 조건이나 특이사항이 있다면 기록해주세요.</small>
                </div>

                <!-- 로딩 상태 표시 -->
                <div id="statusLoading" class="d-none text-center py-3">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    <span>상태 변경 중...</span>
                </div>

                <!-- 알림 메시지 영역 -->
                <div id="statusAlert" class="alert d-none" role="alert"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" id="confirmStatusChange" class="btn btn-primary">
                    <span id="confirmButtonText">변경</span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// 상태 변경 함수
function changeStatus(newStatus) {
    const modal = new bootstrap.Modal(document.getElementById('statusModal'));
    const newStatusInput = document.getElementById('new_status');

    // 상태 설정
    newStatusInput.value = newStatus;

    // 모달 제목 변경 (간단한 워크플로우: 대기->승인->정지)
    const modalTitle = document.querySelector('#statusModal .modal-title');
    switch(newStatus) {
        case 'active':
            modalTitle.textContent = '승인';
            break;
        case 'suspended':
            modalTitle.textContent = '정지';
            break;
        case 'pending':
            modalTitle.textContent = '대기';
            break;
        default:
            modalTitle.textContent = '상태 변경';
            break;
    }

    // 상태에 따라 추가 필드 표시/숨김
    toggleApprovalFields(newStatus);

    // 버튼 텍스트 업데이트
    updateConfirmButtonText(newStatus);

    // 폼 초기화
    document.getElementById('status_reason').value = '';
    document.getElementById('approval_type').value = '';
    document.getElementById('approval_notes').value = '';
    hideStatusAlert();
    hideStatusLoading();

    modal.show();
}

// 간단한 워크플로우 상태 필드 처리 (대기->승인->정지)
function toggleApprovalFields(status) {
    const approvalTypeGroup = document.getElementById('approval_type_group');
    const approvalNotesGroup = document.getElementById('approval_notes_group');
    const approvalTypeSelect = document.getElementById('approval_type');
    const statusReasonGroup = document.getElementById('status_reason').parentNode;

    if (status === 'active') {
        // 승인 상태로 변경시 승인 타입, 메모, 사유 모두 표시
        approvalTypeGroup.style.display = 'block';
        approvalNotesGroup.style.display = 'block';
        approvalTypeSelect.required = true;
        statusReasonGroup.style.display = 'block';

        // 기본값 설정
        if (!approvalTypeSelect.value) {
            approvalTypeSelect.value = 'approved';
        }
    } else if (status === 'suspended') {
        // 정지 상태로 변경시 사유 필드만 표시
        approvalTypeGroup.style.display = 'none';
        approvalNotesGroup.style.display = 'none';
        approvalTypeSelect.required = false;
        statusReasonGroup.style.display = 'block';
    } else {
        // 대기 상태로 변경시 모든 필드 숨김
        approvalTypeGroup.style.display = 'none';
        approvalNotesGroup.style.display = 'none';
        approvalTypeSelect.required = false;
        statusReasonGroup.style.display = 'none';
    }
}

// 확인 버튼 텍스트 업데이트 (간단한 워크플로우)
function updateConfirmButtonText(status) {
    const confirmButtonText = document.getElementById('confirmButtonText');

    switch(status) {
        case 'active':
            confirmButtonText.textContent = '승인';
            break;
        case 'suspended':
            confirmButtonText.textContent = '정지';
            break;
        case 'pending':
            confirmButtonText.textContent = '대기';
            break;
        default:
            confirmButtonText.textContent = '변경';
            break;
    }
}

// 상태 변경 확인 버튼 클릭 이벤트
document.addEventListener('DOMContentLoaded', function() {
    const confirmButton = document.getElementById('confirmStatusChange');
    if (confirmButton) {
        confirmButton.addEventListener('click', function() {
            confirmStatusChange();
        });
    }
});

// AJAX로 상태 변경 처리
async function confirmStatusChange() {
    const newStatus = document.getElementById('new_status').value;
    const statusReason = document.getElementById('status_reason').value.trim();
    const approvalType = document.getElementById('approval_type').value;
    const approvalNotes = document.getElementById('approval_notes').value.trim();

    // 유효성 검사 (간단한 워크플로우: 대기->승인->정지)
    // 승인 또는 정지 상태 변경시 사유 필수
    if ((newStatus === 'active' || newStatus === 'suspended') && !statusReason) {
        showStatusAlert('변경 사유를 입력해주세요.', 'danger');
        document.getElementById('status_reason').focus();
        return;
    }

    // 승인 상태로 변경시 승인 타입 필수 검사
    if (newStatus === 'active' && !approvalType) {
        showStatusAlert('승인 타입을 선택해주세요.', 'danger');
        document.getElementById('approval_type').focus();
        return;
    }

    try {
        showStatusLoading();
        hideStatusAlert();

        // CSRF 토큰 가져오기
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        if (!token) {
            showStatusAlert('CSRF 토큰을 찾을 수 없습니다. 페이지를 새로고침하고 다시 시도해주세요.', 'danger');
            return;
        }

        // 요청 데이터 구성
        const requestData = {
            status: newStatus,
            status_reason: statusReason
        };

        // 활성 상태로 변경시 승인 관련 데이터 추가
        if (newStatus === 'active') {
            requestData.approval_type = approvalType;
            if (approvalNotes) {
                requestData.approval_notes = approvalNotes;
            }
        }

        const response = await fetch(`/api/admin/partner/users/{{ $item->id }}/status`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token
            },
            credentials: 'same-origin', // 세션 쿠키 포함
            body: JSON.stringify(requestData)
        });

        const data = await response.json();

        if (response.ok && data.success) {
            // 성공 처리
            showStatusAlert(data.message, 'success');

            // 페이지의 상태 배지 업데이트
            updateStatusBadgeOnPage(data.data.status_badge);

            // 상태 사유 업데이트 (있다면)
            updateStatusReasonOnPage(data.data.status_reason);

            // 2초 후 모달 닫기
            setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('statusModal'));
                modal.hide();

                // 성공 토스트 메시지 (선택사항)
                showToastMessage(data.message, 'success');
            }, 2000);

        } else {
            // 오류 처리
            let errorMessage = data.message || '상태 변경 중 오류가 발생했습니다.';

            // 유효성 검사 오류가 있다면 표시
            if (data.errors) {
                const errorMessages = Object.values(data.errors).flat();
                errorMessage = errorMessages.join('<br>');
            }

            showStatusAlert(errorMessage, 'danger');
        }

    } catch (error) {
        console.error('Status change error:', error);
        showStatusAlert('네트워크 오류가 발생했습니다. 다시 시도해주세요.', 'danger');
    } finally {
        hideStatusLoading();
    }
}

// 로딩 상태 표시/숨김
function showStatusLoading() {
    document.getElementById('statusLoading').classList.remove('d-none');
    document.getElementById('confirmStatusChange').disabled = true;
}

function hideStatusLoading() {
    document.getElementById('statusLoading').classList.add('d-none');
    document.getElementById('confirmStatusChange').disabled = false;
}

// 알림 메시지 표시/숨김
function showStatusAlert(message, type = 'info') {
    const alertElement = document.getElementById('statusAlert');
    alertElement.className = `alert alert-${type}`;
    alertElement.innerHTML = message;
    alertElement.classList.remove('d-none');
}

function hideStatusAlert() {
    document.getElementById('statusAlert').classList.add('d-none');
}

// 페이지의 상태 배지 업데이트
function updateStatusBadgeOnPage(newBadgeHtml) {
    // 상태 배지가 있는 요소들 찾기
    const statusBadges = document.querySelectorAll('.status-badge, [class*="badge"]');
    statusBadges.forEach(badge => {
        if (badge.closest('.card-body') && badge.textContent.includes('활성') ||
            badge.textContent.includes('대기') || badge.textContent.includes('정지') ||
            badge.textContent.includes('비활성')) {
            badge.outerHTML = newBadgeHtml;
        }
    });
}

// 페이지의 상태 사유 업데이트
function updateStatusReasonOnPage(newReason) {
    if (newReason) {
        // 상태 사유 표시 영역이 있다면 업데이트
        const reasonElement = document.querySelector('.status-reason, .alert-info');
        if (reasonElement && reasonElement.textContent.includes('상태 변경 사유')) {
            reasonElement.innerHTML = `<strong>상태 변경 사유:</strong> ${newReason}`;
            reasonElement.style.display = 'block';
        }
    }
}

// 토스트 메시지 표시 (선택사항)
function showToastMessage(message, type = 'info') {
    // 간단한 토스트 메시지 구현
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} position-fixed`;
    toast.style.top = '20px';
    toast.style.right = '20px';
    toast.style.zIndex = '9999';
    toast.style.minWidth = '300px';
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;

    document.body.appendChild(toast);

    // 5초 후 자동 제거
    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
    }, 5000);
}
</script>
@endpush
