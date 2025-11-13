<!-- 삭제 버튼 -->
<button type="button" class="btn btn-danger" data-bs-toggle="modal"
    data-bs-target="#confirmDeleteModal" id="directDeleteBtn"
    data-delete-url="{{ route('admin.partner.approval.destroy', $application->id) }}">
    <i class="fe fe-trash-2 me-2"></i>신청서 삭제
</button>

<!-- 삭제 확인 모달 -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteModalLabel">
                    <i class="fe fe-alert-triangle me-2 text-danger"></i>삭제 확인
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <p>정말로 삭제하시겠습니까?</p>

                <div class="card bg-light">
                    <div class="card-body">

                        <i class="fe fe-shield me-2"></i>
                        <strong>안전한 삭제를 위해 아래 코드를 정확히 입력해주세요:</strong>
                        <div class="mt-2 p-2 bg-danger-light border border-danger rounded text-center position-relative">
                            <span class="font-monospace fw-bold fs-4 text-danger" id="deleteCode"></span>
                            <button type="button"
                                    class="btn btn-sm btn-outline-danger ms-2"
                                    id="copyCodeBtn"
                                    onclick="copyDeleteCode()"
                                    title="코드 복사">
                                <i class="fe fe-copy"></i>
                            </button>
                        </div>


                    </div>
                </div>

                <div class="mt-3">
                    <div class="form-group mt-3">
                        <label for="deleteCodeInput">확인 코드 입력</label>
                        <input type="text"
                               class="form-control font-monospace text-center"
                               id="deleteCodeInput"
                               placeholder="위의 코드를 입력하세요"
                               maxlength="10"
                               autocomplete="off">
                        <div class="invalid-feedback" id="deleteCodeError">
                            코드가 일치하지 않습니다.
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fe fe-x me-2"></i>취소
                </button>
                <button type="button" class="btn btn-danger" onclick="executeDelete()" id="confirmDeleteButton" disabled>
                    <i class="fe fe-trash-2 me-2"></i>삭제 확인
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* 삭제 모달 스타일 */
    #confirmDeleteModal .modal-header {
        border-bottom: 1px solid #dee2e6;
        background-color: #f8f9fa;
    }

    #confirmDeleteModal .card {
        transition: all 0.2s ease;
    }

    #confirmDeleteModal .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    #confirmDeleteModal .form-group label {
        font-weight: 600;
        color: #495057;
    }

    #confirmDeleteModal .btn-danger {
        transition: all 0.3s ease;
    }

    #confirmDeleteModal .btn-danger:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
    }

    #confirmDeleteModal .modal-footer {
        border-top: 1px solid #dee2e6;
        background-color: #f8f9fa;
    }

    /* 확인 코드 관련 스타일 */
    #confirmDeleteModal #deleteCode {
        font-family: 'Courier New', 'Monaco', monospace;
        letter-spacing: 2px;
        user-select: all;
        transition: all 0.2s ease;
    }

    #confirmDeleteModal #deleteCode:hover {
        background-color: #e3f2fd;
        padding: 4px 8px;
        border-radius: 4px;
    }

    #confirmDeleteModal #deleteCodeInput {
        font-family: 'Courier New', 'Monaco', monospace;
        letter-spacing: 2px;
        font-size: 1.1em;
        transition: all 0.3s ease;
    }

    #confirmDeleteModal #deleteCodeInput.is-valid {
        border-color: #28a745;
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    }

    #confirmDeleteModal #deleteCodeInput.is-invalid {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }

    #confirmDeleteModal .bg-danger-light {
        background-color: #ffe6e8 !important;
    }

    /* 복사 버튼 스타일 */
    #confirmDeleteModal #copyCodeBtn {
        position: relative;
        transition: all 0.3s ease;
        border-radius: 6px;
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }

    #confirmDeleteModal #copyCodeBtn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(220, 53, 69, 0.2);
    }

    #confirmDeleteModal #copyCodeBtn:active {
        transform: translateY(0);
    }

    #confirmDeleteModal #copyCodeBtn.btn-success {
        border-color: #28a745;
        background-color: rgba(40, 167, 69, 0.1);
    }
</style>
@endpush

@push('scripts')
<script>
    let currentDeleteCode = '';

    // 10자리 난수 생성 함수
    function generateDeleteCode() {
        const chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        let result = '';
        for (let i = 0; i < 10; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return result;
    }

    // 모달이 열릴 때 새로운 코드 생성
    document.getElementById('confirmDeleteModal').addEventListener('show.bs.modal', function () {
        currentDeleteCode = generateDeleteCode();
        document.getElementById('deleteCode').textContent = currentDeleteCode;

        // 입력 필드와 버튼 초기화
        const inputField = document.getElementById('deleteCodeInput');
        const deleteButton = document.getElementById('confirmDeleteButton');

        inputField.value = '';
        inputField.classList.remove('is-invalid', 'is-valid');
        deleteButton.disabled = true;

        console.log('새로운 삭제 코드 생성:', currentDeleteCode);
    });

    // 입력 필드 검증
    document.getElementById('deleteCodeInput').addEventListener('input', function() {
        const inputValue = this.value.toUpperCase();
        const deleteButton = document.getElementById('confirmDeleteButton');
        const errorElement = document.getElementById('deleteCodeError');

        if (inputValue === currentDeleteCode) {
            // 코드 일치
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
            deleteButton.disabled = false;
            errorElement.style.display = 'none';
            console.log('코드 일치: 삭제 버튼 활성화');
        } else if (inputValue.length === 10) {
            // 10자리 입력했지만 불일치
            this.classList.remove('is-valid');
            this.classList.add('is-invalid');
            deleteButton.disabled = true;
            errorElement.style.display = 'block';
        } else {
            // 아직 입력 중
            this.classList.remove('is-invalid', 'is-valid');
            deleteButton.disabled = true;
            errorElement.style.display = 'none';
        }
    });

    // 삭제 코드 복사 함수
    function copyDeleteCode() {
        const deleteCodeElement = document.getElementById('deleteCode');
        const deleteCodeInput = document.getElementById('deleteCodeInput');
        const copyButton = document.getElementById('copyCodeBtn');

        if (!deleteCodeElement || !currentDeleteCode) {
            console.error('삭제 코드를 찾을 수 없습니다.');
            return;
        }

        // 클립보드에 복사
        navigator.clipboard.writeText(currentDeleteCode).then(function() {
            // 입력 필드에 자동 입력
            deleteCodeInput.value = currentDeleteCode;

            // 입력 이벤트 트리거하여 검증 실행
            deleteCodeInput.dispatchEvent(new Event('input'));

            // 복사 버튼 피드백
            const originalIcon = copyButton.innerHTML;
            copyButton.innerHTML = '<i class="fe fe-check text-success"></i>';
            copyButton.classList.add('btn-success');
            copyButton.classList.remove('btn-outline-danger');

            // 1.5초 후 원래 상태로 복원
            setTimeout(() => {
                copyButton.innerHTML = originalIcon;
                copyButton.classList.remove('btn-success');
                copyButton.classList.add('btn-outline-danger');
            }, 1500);

            console.log('코드가 클립보드에 복사되고 입력 필드에 자동 입력되었습니다.');
        }).catch(function(err) {
            console.error('클립보드 복사 실패:', err);

            // 클립보드 API 실패 시 fallback으로 직접 입력만 실행
            deleteCodeInput.value = currentDeleteCode;
            deleteCodeInput.dispatchEvent(new Event('input'));

            // 피드백 표시
            copyButton.innerHTML = '<i class="fe fe-alert-triangle text-warning"></i>';
            setTimeout(() => {
                copyButton.innerHTML = '<i class="fe fe-copy"></i>';
            }, 1500);
        });
    }

    // 삭제 실행 함수
    function executeDelete() {
        console.log('executeDelete 함수 호출됨');

        const confirmDeleteBtn = document.getElementById('confirmDeleteButton');
        if (!confirmDeleteBtn) {
            console.error('삭제 확인 버튼을 찾을 수 없음');
            return;
        }

        // 코드 검증
        const inputCode = document.getElementById('deleteCodeInput').value.toUpperCase();
        if (inputCode !== currentDeleteCode) {
            alert('코드가 일치하지 않습니다.');
            return;
        }

        // 버튼 상태 변경
        const originalBtnContent = confirmDeleteBtn.innerHTML;
        confirmDeleteBtn.disabled = true;
        confirmDeleteBtn.innerHTML = '<i class="fe fe-loader me-2"></i>삭제 중...';

        // CSRF 토큰 가져오기
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // 삭제 URL을 버튼의 데이터 속성에서 가져오기
        const deleteBtn = document.getElementById('directDeleteBtn');
        const deleteUrl = deleteBtn.dataset.deleteUrl;

        if (!deleteUrl) {
            console.error('삭제 URL을 찾을 수 없습니다.');
            confirmDeleteBtn.disabled = false;
            confirmDeleteBtn.innerHTML = originalBtnContent;
            return;
        }

        // AJAX 삭제 요청
        fetch(deleteUrl, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                delete_reason: '코드 확인 삭제: ' + currentDeleteCode,
                confirm_delete: true,
                verification_code: currentDeleteCode
            })
        })
        .then(response => {
            console.log('서버 응답 받음:', response);
            return response.json();
        })
        .then(data => {
            console.log('응답 데이터:', data);
            if (data.success) {
                // 모달 닫기
                const modal = bootstrap.Modal.getInstance(document.getElementById('confirmDeleteModal'));
                modal.hide();

                showToast('success', data.message);

                // 메인 콘텐츠 영역 삭제 효과
                const mainContent = document.querySelector('.container-fluid');
                if (mainContent) {
                    // 삭제된 항목 표시 오버레이 추가
                    const overlay = document.createElement('div');
                    overlay.style.cssText = `
                        position: fixed;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        background: rgba(220, 53, 69, 0.1);
                        backdrop-filter: blur(2px);
                        z-index: 1060;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    `;

                    const deleteMessage = document.createElement('div');
                    deleteMessage.style.cssText = `
                        background: white;
                        padding: 30px;
                        border-radius: 10px;
                        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                        text-align: center;
                        max-width: 400px;
                        border-left: 5px solid #dc3545;
                    `;
                    deleteMessage.innerHTML = `
                        <div class="text-danger mb-3">
                            <i class="fe fe-check-circle" style="font-size: 48px;"></i>
                        </div>
                        <h5 class="text-danger mb-2">삭제되었습니다</h5>
                        <p class="text-muted mb-0">잠시 후 목록으로 이동합니다...</p>
                    `;

                    overlay.appendChild(deleteMessage);
                    document.body.appendChild(overlay);

                    // 메인 콘텐츠 페이드 아웃
                    mainContent.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
                    mainContent.style.opacity = '0.3';
                    mainContent.style.transform = 'scale(0.95)';
                    mainContent.style.filter = 'grayscale(100%)';
                }

                // 2초 후 이전 페이지로 이동
                setTimeout(() => {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        window.history.back();
                    }
                }, 2000);
            } else {
                showToast('error', data.message || '삭제 처리 중 오류가 발생했습니다.');

                // 버튼 상태 복원
                confirmDeleteBtn.disabled = false;
                confirmDeleteBtn.innerHTML = originalBtnContent;
            }
        })
        .catch(error => {
            console.error('AJAX 오류:', error);
            showToast('error', '네트워크 오류가 발생했습니다.');

            // 버튼 상태 복원
            confirmDeleteBtn.disabled = false;
            confirmDeleteBtn.innerHTML = originalBtnContent;
        });
    }
</script>
@endpush
