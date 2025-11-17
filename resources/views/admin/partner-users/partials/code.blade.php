{{-- 파트너코드 ajax 요청 --}}
<div id="partner-code-section">
    @if ($item->partner_code)
        <code id="partner-code-display" class="bg-light px-2 py-1 rounded">{{ $item->partner_code }}</code>
        <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="deletePartnerCode()">
            <i class="fe fe-trash-2"></i>
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary ms-1" onclick="copyPartnerCode()">
            <i class="fe fe-copy"></i>
        </button>
    @else
        <span id="no-code-message" class="text-muted">미생성</span>
        <button type="button" class="btn btn-sm btn-primary ms-2" onclick="generatePartnerCode()">
            <i class="fe fe-plus me-1"></i>코드 생성
        </button>
    @endif
</div>

<script>
// 파트너 코드 생성
async function generatePartnerCode() {
    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;

    try {
        // 버튼 비활성화 및 로딩 표시
        btn.disabled = true;
        btn.innerHTML = '<i class="spinner-border spinner-border-sm me-1"></i>생성중...';

        const response = await fetch(`/api/partner/code/generate/{{ $item->id }}`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                email: '{{ $item->email }}'
            })
        });

        const data = await response.json();

        if (response.ok && data.success) {
            // 성공 시 UI 업데이트
            const partnerCode = data.data ? data.data.partner_code : data.partner_code;
            updatePartnerCodeSection(partnerCode);
            showAlert('success', '파트너 코드가 성공적으로 생성되었습니다.');

            // 1초 후 페이지 리로드
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            throw new Error(data.message || '파트너 코드 생성에 실패했습니다.');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('danger', error.message);

        // 버튼 복원
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// 파트너 코드 삭제
async function deletePartnerCode() {
    if (!confirm('파트너 코드를 삭제하시겠습니까?\n삭제된 코드는 복구할 수 없습니다.')) {
        return;
    }

    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;

    try {
        // 버튼 비활성화 및 로딩 표시
        btn.disabled = true;
        btn.innerHTML = '<i class="spinner-border spinner-border-sm"></i>';

        const response = await fetch(`/api/partner/code/delete/{{ $item->id }}`, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (response.ok && data.success) {
            // 성공 시 UI 업데이트
            updatePartnerCodeSection(null);
            showAlert('success', '파트너 코드가 성공적으로 삭제되었습니다.');

            // 1초 후 페이지 리로드
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            throw new Error(data.message || '파트너 코드 삭제에 실패했습니다.');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('danger', error.message);

        // 버튼 복원
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// 파트너 코드 복사
function copyPartnerCode() {
    const codeElement = document.getElementById('partner-code-display');
    const code = codeElement.textContent;

    navigator.clipboard.writeText(code).then(function() {
        showAlert('info', '파트너 코드가 클립보드에 복사되었습니다.');
    }).catch(function(err) {
        console.error('복사 실패:', err);
        showAlert('warning', '클립보드 복사에 실패했습니다.');
    });
}

// 파트너 코드 섹션 업데이트
function updatePartnerCodeSection(partnerCode) {
    const section = document.getElementById('partner-code-section');

    if (partnerCode) {
        // 코드가 있는 경우
        section.innerHTML = `
            <code id="partner-code-display" class="bg-light px-2 py-1 rounded">${partnerCode}</code>
            <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="deletePartnerCode()">
                <i class="fe fe-trash-2"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary ms-1" onclick="copyPartnerCode()">
                <i class="fe fe-copy"></i>
            </button>
        `;
    } else {
        // 코드가 없는 경우
        section.innerHTML = `
            <span id="no-code-message" class="text-muted">미생성</span>
            <button type="button" class="btn btn-sm btn-primary ms-2" onclick="generatePartnerCode()">
                <i class="fe fe-plus me-1"></i>코드 생성
            </button>
        `;
    }
}

// 알림 메시지 표시
function showAlert(type, message) {
    // 기존 알림 제거
    const existingAlert = document.querySelector('.alert-dynamic');
    if (existingAlert) {
        existingAlert.remove();
    }

    // 새 알림 생성
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show alert-dynamic`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    // 페이지 상단에 추가
    const container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);

    // 5초 후 자동 제거
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}
</script>
