@extends('jiny-partner::layouts.admin.sidebar')

@section('title', $pageTitle)

@push('styles')
<style>
/* 통계 카드 호버 효과 */
.stats-card {
    transition: all 0.2s ease;
    cursor: pointer;
}

.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.25rem 1rem rgba(0, 0, 0, 0.08) !important;
    background-color: #ffffff !important;
}

/* 카드 내 아이콘 애니메이션 */
.stats-card:hover .stats-icon {
    transform: scale(1.05);
}

.stats-icon {
    transition: transform 0.2s ease;
    flex-shrink: 0;
}

/* 숫자 카운터 애니메이션 */
.counter {
    display: inline-block;
    font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, sans-serif;
}

/* 카드 기본 스타일 */
.stats-card .card-body {
    min-height: 5rem;
}

/* 아이콘 색상 테마 */
.icon-purple { background-color: #6366f1 !important; }
.icon-amber { background-color: #f59e0b !important; }
.icon-cyan { background-color: #06b6d4 !important; }
.icon-emerald { background-color: #10b981 !important; }

/* 반응형 개선 */
@media (max-width: 768px) {
    .stats-card .card-body {
        padding: 1rem !important;
        min-height: auto;
    }

    .stats-icon {
        width: 2.5rem !important;
        height: 2.5rem !important;
    }

    .counter {
        font-size: 1.5rem !important;
    }
}

@media (max-width: 576px) {
    .stats-card .card-body {
        flex-direction: column;
        text-align: center;
    }

    .stats-icon {
        margin-bottom: 0.75rem !important;
        margin-right: 0 !important;
    }
}

/* 부드러운 페이드인 애니메이션 */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.stats-card-animate {
    animation: fadeInUp 0.6s ease forwards;
}

/* 텍스트 스타일 개선 */
.stats-card h4 {
    font-weight: 700;
    letter-spacing: -0.025em;
}

.stats-card p {
    color: #6b7280;
}

.stats-card .text-muted {
    color: #9ca3af !important;
}
</style>
@endpush

@section('content')
<div class="container-fluid">

    <!-- 헤더 -->
    <section class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-1">{{ $pageTitle }}</h3>
                    <p class="text-muted mb-0">파트너 추천 코드를 통합 관리하세요</p>
                </div>
                <div>
                    <button class="btn btn-outline-success me-2" data-bs-toggle="modal" data-bs-target="#bulkGenerateModal">
                        <i class="fe fe-plus me-1"></i>대량 생성
                    </button>
                    <button class="btn btn-outline-danger me-2" onclick="bulkDelete()">
                        <i class="fe fe-trash-2 me-1"></i>대량 삭제
                    </button>
                    <button class="btn btn-primary" onclick="generateAllCodes()">
                        <i class="fe fe-hash me-1"></i>모든 파트너 코드 생성
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- 통계 카드 -->
    <section class="row g-3 mb-4">
        <!-- 전체 파트너 카드 -->
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 stats-card h-100 position-relative">
                <div class="card-body d-flex align-items-center py-3 px-4">
                    <!-- 아이콘 -->
                    <div class="d-flex align-items-center justify-content-center rounded-circle me-3 stats-icon"
                         style="width: 3rem; height: 3rem; background-color: #6366f1; color: white;">
                        <i class="bi bi-person-lines-fill fs-5"></i>
                    </div>
                    <!-- 내용 -->
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-baseline">
                            <h4 class="fw-bold text-dark mb-0 me-1 counter" data-target="{{ $statistics['total_partners'] }}">{{ number_format($statistics['total_partners']) }}</h4>
                            <span class="text-muted fw-medium" style="font-size: 0.875rem;">/</span>
                            <span class="text-muted fw-medium ms-1" style="font-size: 0.875rem;">전체</span>
                        </div>
                        <p class="text-muted mb-0 fw-medium" style="font-size: 0.875rem;">파트너 현황</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 총 커미션 카드 -->
        <div class="col-lg-3 col-md-6">
            <div class="card border-0  stats-card h-100 position-relative">
                <div class="card-body d-flex align-items-center py-3 px-4">
                    <!-- 아이콘 -->
                    <div class="d-flex align-items-center justify-content-center rounded-circle me-3 stats-icon"
                         style="width: 3rem; height: 3rem; background-color: #f59e0b; color: white;">
                        <i class="bi bi-currency-dollar fs-5"></i>
                    </div>
                    <!-- 내용 -->
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-baseline">
                            <h4 class="fw-bold text-dark mb-0 counter" data-target="0">0</h4>
                            <span class="text-muted fw-medium ms-1" style="font-size: 0.875rem;">원</span>
                        </div>
                        <p class="text-muted mb-0 fw-medium" style="font-size: 0.875rem;">총 커미션</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 총 매출 카드 -->
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 stats-card h-100 position-relative">
                <div class="card-body d-flex align-items-center py-3 px-4">
                    <!-- 아이콘 -->
                    <div class="d-flex align-items-center justify-content-center rounded-circle me-3 stats-icon"
                         style="width: 3rem; height: 3rem; background-color: #06b6d4; color: white;">
                        <i class="bi bi-graph-up-arrow fs-5"></i>
                    </div>
                    <!-- 내용 -->
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-baseline">
                            <h4 class="fw-bold text-dark mb-0 counter" data-target="0">0</h4>
                            <span class="text-muted fw-medium ms-1" style="font-size: 0.875rem;">원</span>
                        </div>
                        <p class="text-muted mb-0 fw-medium" style="font-size: 0.875rem;">총 매출</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 평균 점수 카드 -->
        <div class="col-lg-3 col-md-6">
            <div class="card border-0  stats-card h-100 position-relative">
                <div class="card-body d-flex align-items-center py-3 px-4">
                    <!-- 아이콘 -->
                    <div class="d-flex align-items-center justify-content-center rounded-circle me-3 stats-icon"
                         style="width: 3rem; height: 3rem; background-color: #10b981; color: white;">
                        <i class="bi bi-star-fill fs-5"></i>
                    </div>
                    <!-- 내용 -->
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-baseline">
                            <h4 class="fw-bold text-dark mb-0 counter" data-target="{{ $statistics['code_usage_rate'] }}">{{ $statistics['code_usage_rate'] }}</h4>
                            <span class="text-muted fw-medium ms-1" style="font-size: 0.875rem;">%</span>
                        </div>
                        <p class="text-muted mb-0 fw-medium" style="font-size: 0.875rem;">평균 점수</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 필터 및 검색 -->
    <section class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control"
                                   placeholder="이름, 이메일, 코드로 검색"
                                   value="{{ $currentFilters['search'] }}">
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select">
                                <option value="all" {{ $currentFilters['status'] === 'all' ? 'selected' : '' }}>전체 상태</option>
                                <option value="active" {{ $currentFilters['status'] === 'active' ? 'selected' : '' }}>코드 보유</option>
                                <option value="inactive" {{ $currentFilters['status'] === 'inactive' ? 'selected' : '' }}>코드 없음</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="per_page" class="form-select">
                                <option value="20" {{ $currentFilters['per_page'] == 20 ? 'selected' : '' }}>20개씩</option>
                                <option value="50" {{ $currentFilters['per_page'] == 50 ? 'selected' : '' }}>50개씩</option>
                                <option value="100" {{ $currentFilters['per_page'] == 100 ? 'selected' : '' }}>100개씩</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary me-2">검색</button>
                            <a href="{{ route('admin.partner.codes.index') }}" class="btn btn-outline-secondary">초기화</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- 파트너 코드 목록 -->
    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        파트너 코드 목록
                        <span class="badge bg-secondary ms-2">{{ $partners->total() }}명</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    @if($partners->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">
                                        <input type="checkbox" class="form-check-input" id="selectAll">
                                    </th>
                                    <th>파트너 정보</th>
                                    <th width="150">등급</th>
                                    <th width="200">추천 코드</th>
                                    <th width="100">상태</th>
                                    <th width="200">작업</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($partners as $partner)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input partner-checkbox"
                                               value="{{ $partner->id }}">
                                    </td>
                                    <td>
                                        <div>
                                            <div class="fw-medium">{{ $partner->name }}</div>
                                            <small class="text-muted">{{ $partner->email }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $partner->partnerTier->tier_name === 'Diamond' ? 'primary' : ($partner->partnerTier->tier_name === 'Gold' ? 'warning' : 'secondary') }}">
                                            {{ $partner->partnerTier->tier_name ?? 'Bronze' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($partner->partner_code)
                                            <div class="input-group input-group-sm">
                                                <input type="text" class="form-control"
                                                       value="{{ $partner->partner_code }}" readonly>
                                                <button class="btn btn-outline-secondary"
                                                        onclick="copyToClipboard('{{ $partner->partner_code }}')">
                                                    <i class="fe fe-copy"></i>
                                                </button>
                                            </div>
                                        @else
                                            <span class="text-muted">코드 없음</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($partner->partner_code)
                                            <span class="badge bg-success">활성</span>
                                        @else
                                            <span class="badge bg-secondary">비활성</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            @if($partner->partner_code)
                                                <button class="btn btn-outline-warning"
                                                        onclick="regenerateCode({{ $partner->id }})">
                                                    <i class="fe fe-refresh-cw"></i>
                                                </button>
                                                <button class="btn btn-outline-danger"
                                                        onclick="deleteCode({{ $partner->id }})">
                                                    <i class="fe fe-trash-2"></i>
                                                </button>
                                            @else
                                                <button class="btn btn-outline-success"
                                                        onclick="generateCode({{ $partner->id }})">
                                                    <i class="fe fe-plus"></i> 생성
                                                </button>
                                            @endif
                                            <a href="{{ route('admin.partner.users.show', $partner->id) }}"
                                               class="btn btn-outline-secondary">
                                                <i class="fe fe-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- 페이지네이션 -->
                    <div class="d-flex justify-content-between align-items-center p-3">
                        <div>
                            {{ $partners->appends(request()->query())->links() }}
                        </div>
                        <div class="text-muted">
                            {{ $partners->firstItem() }}-{{ $partners->lastItem() }} / {{ $partners->total() }}개
                        </div>
                    </div>
                    @else
                    <div class="text-center py-5">
                        <div class="text-muted">
                            <i class="fe fe-hash" style="font-size: 3rem;"></i>
                            <div class="mt-3">
                                <h5>파트너 코드가 없습니다</h5>
                                <p class="mb-0">검색 조건을 확인하거나 새로운 코드를 생성해보세요.</p>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</div>

<!-- 대량 생성 모달 -->
<div class="modal fade" id="bulkGenerateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">코드 대량 생성</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="bulkGenerateForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">생성 대상</label>
                        <select name="target" class="form-select" required>
                            <option value="">선택해주세요</option>
                            <option value="without_codes">코드가 없는 파트너만</option>
                            <option value="all">모든 파트너 (기존 코드 교체)</option>
                            <option value="active_only">활성 파트너만</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">코드 접두어 (선택사항)</label>
                        <input type="text" name="prefix" class="form-control"
                               placeholder="예: PARTNER_" maxlength="10">
                        <small class="text-muted">접두어 + 랜덤 문자열로 생성됩니다</small>
                    </div>
                    <div class="alert alert-info">
                        <i class="fe fe-info me-2"></i>
                        대량 생성은 시간이 소요될 수 있습니다. 생성 중에는 페이지를 닫지 마세요.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">생성 시작</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
console.log('Partner Codes Script Loaded - Version: {{ date("Y-m-d H:i:s") }}');
// 전체 선택/해제
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.partner-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// 클립보드 복사
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('코드가 클립보드에 복사되었습니다.');
    });
}

// 개별 코드 생성
function generateCode(partnerId) {
    if (confirm('이 파트너의 추천 코드를 생성하시겠습니까?')) {
        // 파트너 이메일을 찾아서 전달
        const partnerRow = document.querySelector(`input[value="${partnerId}"]`).closest('tr');
        const emailElement = partnerRow.querySelector('small.text-muted');
        const email = emailElement ? emailElement.textContent.trim() : '';

        console.log('Generating code for partner:', partnerId, 'email:', email);

        if (!email) {
            alert('파트너 이메일을 찾을 수 없습니다.');
            return;
        }

        // 버튼 비활성화
        const generateBtn = partnerRow.querySelector('.btn-outline-success');
        if (generateBtn) {
            generateBtn.disabled = true;
            generateBtn.innerHTML = '<i class="fe fe-loader rotate"></i> 생성중...';
        }

        // AJAX 요청 구현
        fetch(`/api/partner/code/generate/${partnerId}`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                email: email
            })
        }).then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Response:', data);
            if (data.success) {
                const partnerCode = data.data ? data.data.partner_code : data.partner_code;
                alert('코드가 생성되었습니다: ' + partnerCode);
                // 동적으로 UI 업데이트
                updatePartnerRowUI(partnerId, partnerCode);

                // 1초 후 페이지 리로드 (최신 상태 반영)
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                alert('오류가 발생했습니다: ' + data.message);
                // 버튼 원상복구
                if (generateBtn) {
                    generateBtn.disabled = false;
                    generateBtn.innerHTML = '<i class="fe fe-plus"></i> 생성';
                }
            }
        }).catch(error => {
            console.error('Error:', error);
            alert('네트워크 오류가 발생했습니다: ' + error.message);
            // 버튼 원상복구
            if (generateBtn) {
                generateBtn.disabled = false;
                generateBtn.innerHTML = '<i class="fe fe-plus"></i> 생성';
            }
        });
    }
}

// 개별 코드 재생성
function regenerateCode(partnerId) {
    if (confirm('이 파트너의 추천 코드를 재생성하시겠습니까? 기존 코드는 삭제됩니다.')) {
        // 기존 코드 삭제 후 새로 생성
        deleteCode(partnerId, false);
        setTimeout(() => generateCode(partnerId), 500);
    }
}

// 개별 코드 삭제
function deleteCode(partnerId, showConfirm = true) {
    if (!showConfirm || confirm('이 파트너의 추천 코드를 삭제하시겠습니까?')) {
        fetch(`/api/partner/code/delete/${partnerId}`, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        }).then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                if (showConfirm) {
                    alert('코드가 삭제되었습니다.');
                    // 동적으로 UI 업데이트
                    updatePartnerRowUI(partnerId, null);

                    // 1초 후 페이지 리로드 (최신 상태 반영)
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            } else {
                alert('오류가 발생했습니다: ' + data.message);
            }
        }).catch(error => {
            console.error('Error:', error);
            alert('네트워크 오류가 발생했습니다: ' + error.message);
        });
    }
}

// 모든 파트너 코드 생성
async function generateAllCodes() {
    if (!confirm('모든 파트너의 추천 코드를 생성하시겠습니까? 이 작업은 시간이 소요될 수 있습니다.')) {
        return;
    }

    const btn = document.querySelector('.btn-primary');
    const originalText = btn.innerHTML;

    try {
        // 버튼 비활성화
        btn.disabled = true;
        btn.innerHTML = '<i class="spinner-border spinner-border-sm me-1"></i>생성 중...';

        // 코드가 없는 모든 파트너 ID 수집
        const partnerIds = [];
        document.querySelectorAll('.partner-checkbox').forEach(checkbox => {
            const row = checkbox.closest('tr');
            const codeCell = row.querySelector('td:nth-child(4)');
            // 코드가 없는 파트너만 추가 (미생성 상태)
            if (codeCell.textContent.includes('코드 없음')) {
                partnerIds.push(parseInt(checkbox.value));
            }
        });

        if (partnerIds.length === 0) {
            alert('생성할 파트너 코드가 없습니다. 모든 파트너가 이미 코드를 보유하고 있습니다.');
            return;
        }

        const response = await fetch('/admin/partner/codes/bulk-generate', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                partner_ids: partnerIds
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            alert(`모든 파트너 코드 생성 완료!\n성공: ${data.data.success_count}개\n실패: ${data.data.fail_count}개`);

            // 1초 후 페이지 리로드
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            throw new Error(data.message || '모든 파트너 코드 생성에 실패했습니다.');
        }

    } catch (error) {
        console.error('Error:', error);
        alert('오류가 발생했습니다: ' + error.message);
    } finally {
        // 버튼 복원
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// 선택된 파트너 대량 삭제
async function bulkDelete() {
    const selected = document.querySelectorAll('.partner-checkbox:checked');
    if (selected.length === 0) {
        alert('삭제할 파트너를 선택해주세요.');
        return;
    }

    // 선택된 파트너 중 코드가 있는 파트너만 필터링
    const partnerIds = [];
    const partnerNames = [];

    selected.forEach(checkbox => {
        const row = checkbox.closest('tr');
        const codeCell = row.querySelector('td:nth-child(4)');
        const nameCell = row.querySelector('td:nth-child(2) .fw-medium');

        // 코드가 있는 파트너만 추가
        if (!codeCell.textContent.includes('코드 없음')) {
            partnerIds.push(parseInt(checkbox.value));
            partnerNames.push(nameCell.textContent);
        }
    });

    if (partnerIds.length === 0) {
        alert('선택된 파트너 중 삭제할 코드가 없습니다.');
        return;
    }

    if (!confirm(`선택된 ${partnerIds.length}명의 파트너 코드를 삭제하시겠습니까?\n\n삭제 대상:\n${partnerNames.join('\n')}`)) {
        return;
    }

    const btn = document.querySelector('.btn-outline-danger');
    const originalText = btn.innerHTML;

    try {
        // 버튼 비활성화
        btn.disabled = true;
        btn.innerHTML = '<i class="spinner-border spinner-border-sm me-1"></i>삭제 중...';

        const response = await fetch('/admin/partner/codes/bulk-delete', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                partner_ids: partnerIds
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            alert(`선택된 파트너 코드 삭제 완료!\n성공: ${data.data.success_count}개\n실패: ${data.data.fail_count}개`);

            // 체크박스 초기화
            document.getElementById('selectAll').checked = false;
            selected.forEach(checkbox => checkbox.checked = false);

            // 1초 후 페이지 리로드
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            throw new Error(data.message || '대량 삭제에 실패했습니다.');
        }

    } catch (error) {
        console.error('Error:', error);
        alert('오류가 발생했습니다: ' + error.message);
    } finally {
        // 버튼 복원
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// 동적으로 파트너 행 UI 업데이트
function updatePartnerRowUI(partnerId, partnerCode) {
    const partnerRow = document.querySelector(`input[value="${partnerId}"]`).closest('tr');
    if (!partnerRow) return;

    const codeCell = partnerRow.querySelector('td:nth-child(4)'); // 추천 코드 열
    const statusCell = partnerRow.querySelector('td:nth-child(5)'); // 상태 열
    const actionCell = partnerRow.querySelector('td:nth-child(6)'); // 작업 열

    if (partnerCode) {
        // 코드가 생성된 경우
        codeCell.innerHTML = `
            <div class="input-group input-group-sm">
                <input type="text" class="form-control" value="${partnerCode}" readonly>
                <button class="btn btn-outline-secondary" onclick="copyToClipboard('${partnerCode}')">
                    <i class="fe fe-copy"></i>
                </button>
            </div>
        `;

        statusCell.innerHTML = '<span class="badge bg-success">활성</span>';

        actionCell.innerHTML = `
            <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-warning" onclick="regenerateCode(${partnerId})">
                    <i class="fe fe-refresh-cw"></i>
                </button>
                <button class="btn btn-outline-danger" onclick="deleteCode(${partnerId})">
                    <i class="fe fe-trash-2"></i>
                </button>
                <a href="/admin/partner/users/${partnerId}" class="btn btn-outline-secondary">
                    <i class="fe fe-eye"></i>
                </a>
            </div>
        `;
    } else {
        // 코드가 삭제된 경우
        codeCell.innerHTML = '<span class="text-muted">코드 없음</span>';
        statusCell.innerHTML = '<span class="badge bg-secondary">비활성</span>';

        actionCell.innerHTML = `
            <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-success" onclick="generateCode(${partnerId})">
                    <i class="fe fe-plus"></i> 생성
                </button>
                <a href="/admin/partner/users/${partnerId}" class="btn btn-outline-secondary">
                    <i class="fe fe-eye"></i>
                </a>
            </div>
        `;
    }
}

// 대량 생성 폼 제출
document.getElementById('bulkGenerateForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const target = formData.get('target');
    const prefix = formData.get('prefix');

    if (!target) {
        alert('생성 대상을 선택해주세요.');
        return;
    }

    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;

    try {
        // 버튼 비활성화
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="spinner-border spinner-border-sm me-1"></i>처리 중...';

        // 조건에 따라 파트너 ID 수집
        let partnerIds = [];

        if (target === 'without_codes') {
            // 코드가 없는 파트너만
            document.querySelectorAll('.partner-checkbox').forEach(checkbox => {
                const row = checkbox.closest('tr');
                const codeCell = row.querySelector('td:nth-child(4)');
                if (codeCell.textContent.includes('코드 없음')) {
                    partnerIds.push(parseInt(checkbox.value));
                }
            });
        } else if (target === 'all') {
            // 모든 파트너
            document.querySelectorAll('.partner-checkbox').forEach(checkbox => {
                partnerIds.push(parseInt(checkbox.value));
            });
        } else if (target === 'active_only') {
            // 활성 파트너만 (활성 상태이면서 코드가 없는 파트너)
            document.querySelectorAll('.partner-checkbox').forEach(checkbox => {
                const row = checkbox.closest('tr');
                const statusCell = row.querySelector('td:nth-child(5)');
                const codeCell = row.querySelector('td:nth-child(4)');
                if (statusCell.textContent.includes('활성') && codeCell.textContent.includes('코드 없음')) {
                    partnerIds.push(parseInt(checkbox.value));
                }
            });
        }

        if (partnerIds.length === 0) {
            alert('선택한 조건에 해당하는 파트너가 없습니다.');
            return;
        }

        if (!confirm(`${partnerIds.length}명의 파트너 코드를 생성하시겠습니까?`)) {
            return;
        }

        const response = await fetch('/admin/partner/codes/bulk-generate', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                partner_ids: partnerIds,
                prefix: prefix || null
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            alert(`대량 코드 생성 완료!\n성공: ${data.data.success_count}개\n실패: ${data.data.fail_count}개`);

            // 모달 닫기
            const modal = bootstrap.Modal.getInstance(document.getElementById('bulkGenerateModal'));
            modal.hide();

            // 폼 리셋
            this.reset();

            // 1초 후 페이지 리로드
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            throw new Error(data.message || '대량 생성에 실패했습니다.');
        }

    } catch (error) {
        console.error('Error:', error);
        alert('오류가 발생했습니다: ' + error.message);
    } finally {
        // 버튼 복원
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
});

// 통계 카드 애니메이션 초기화
document.addEventListener('DOMContentLoaded', function() {
    // 카운터 애니메이션
    function animateCounters() {
        const counters = document.querySelectorAll('.counter');

        counters.forEach(counter => {
            const target = parseInt(counter.dataset.target) || parseInt(counter.textContent.replace(/[^\d]/g, ''));
            const current = parseInt(counter.textContent.replace(/[^\d]/g, '')) || 0;

            if (target && target !== current) {
                const increment = target / 50; // 50 스텝으로 애니메이션
                let currentValue = 0;

                const timer = setInterval(() => {
                    currentValue += increment;

                    if (currentValue >= target) {
                        if (counter.textContent.includes('%')) {
                            counter.textContent = target + '%';
                        } else {
                            counter.textContent = target.toLocaleString();
                        }
                        clearInterval(timer);
                    } else {
                        if (counter.textContent.includes('%')) {
                            counter.textContent = Math.ceil(currentValue) + '%';
                        } else {
                            counter.textContent = Math.ceil(currentValue).toLocaleString();
                        }
                    }
                }, 30); // 30ms 간격
            }
        });
    }

    // Intersection Observer로 스크롤 애니메이션
    function setupScrollAnimations() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';

                    // 카운터 애니메이션 시작
                    if (entry.target.classList.contains('stats-card')) {
                        setTimeout(() => {
                            const counters = entry.target.querySelectorAll('.counter');
                            counters.forEach(counter => {
                                const target = parseInt(counter.dataset.target) || parseInt(counter.textContent.replace(/[^\d]/g, ''));
                                animateCounter(counter, target);
                            });
                        }, 200);
                    }
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });

        // 통계 카드들 초기 스타일 설정 및 관찰 시작
        document.querySelectorAll('.stats-card').forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = `all 0.6s ease ${index * 0.1}s`;
            observer.observe(card);
        });
    }

    // 개별 카운터 애니메이션
    function animateCounter(element, target) {
        const isPercentage = element.textContent.includes('%');
        const duration = 1500; // 1.5초
        const startTime = performance.now();

        function update(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);

            // easeOutCubic 이징 함수
            const easeProgress = 1 - Math.pow(1 - progress, 3);
            const currentValue = Math.floor(easeProgress * target);

            if (isPercentage) {
                element.textContent = currentValue + '%';
            } else {
                element.textContent = currentValue.toLocaleString();
            }

            if (progress < 1) {
                requestAnimationFrame(update);
            } else {
                if (isPercentage) {
                    element.textContent = target + '%';
                } else {
                    element.textContent = target.toLocaleString();
                }
            }
        }

        requestAnimationFrame(update);
    }

    // 프로그레스 바 애니메이션
    function animateProgressBars() {
        const progressBars = document.querySelectorAll('.progress-bar-animated');
        progressBars.forEach((bar, index) => {
            setTimeout(() => {
                bar.style.width = bar.getAttribute('aria-valuenow') + '%';
            }, index * 200);
        });
    }

    // 툴팁 초기화 (Bootstrap이 로드된 경우)
    function initializeTooltips() {
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    }

    // 모든 애니메이션 초기화
    setupScrollAnimations();
    animateProgressBars();
    initializeTooltips();

    // 카드 클릭 시 부드러운 효과
    document.querySelectorAll('.stats-card').forEach(card => {
        card.addEventListener('click', function() {
            this.style.transform = 'scale(0.98)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });
});
</script>
@endsection
