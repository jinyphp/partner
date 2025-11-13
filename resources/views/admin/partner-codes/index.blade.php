@extends('jiny-partner::layouts.admin.sidebar')

@section('title', $pageTitle)

@section('content')
<div class="container-fluid">
    <!-- 헤더 -->
    <div class="row mb-3">
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
    </div>

    <!-- 통계 카드 -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-white text-center">
                <div class="card-body">
                    <div class="h4 mb-0 text-primary">{{ number_format($statistics['total_partners']) }}</div>
                    <small class="text-muted">전체 파트너</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-white text-center">
                <div class="card-body">
                    <div class="h4 mb-0 text-success">{{ number_format($statistics['with_codes']) }}</div>
                    <small class="text-muted">코드 보유</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-white text-center">
                <div class="card-body">
                    <div class="h4 mb-0 text-warning">{{ number_format($statistics['without_codes']) }}</div>
                    <small class="text-muted">코드 없음</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-white text-center">
                <div class="card-body">
                    <div class="h4 mb-0 text-info">{{ $statistics['code_usage_rate'] }}%</div>
                    <small class="text-muted">코드 보유율</small>
                </div>
            </div>
        </div>
    </div>

    <!-- 필터 및 검색 -->
    <div class="row mb-3">
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
    </div>

    <!-- 파트너 코드 목록 -->
    <div class="row">
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
                                    <th width="150">작업</th>
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
    </div>
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
        fetch(`/admin/partner/users/${partnerId}/partner-code/generate`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                email: email
            })
        }).then(response => response.json())
        .then(data => {
            console.log('Response:', data);
            if (data.success) {
                alert('코드가 생성되었습니다: ' + data.data.partner_code);
                // 동적으로 UI 업데이트
                updatePartnerRowUI(partnerId, data.data.partner_code);
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
            alert('네트워크 오류가 발생했습니다.');
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
        fetch(`/admin/partner/users/${partnerId}/partner-code/delete`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        }).then(response => response.json())
        .then(data => {
            if (data.success) {
                if (showConfirm) {
                    alert('코드가 삭제되었습니다.');
                    // 동적으로 UI 업데이트
                    updatePartnerRowUI(partnerId, null);
                }
            } else {
                alert('오류가 발생했습니다: ' + data.message);
            }
        });
    }
}

// 모든 파트너 코드 생성
function generateAllCodes() {
    if (confirm('모든 파트너의 추천 코드를 생성하시겠습니까? 이 작업은 시간이 소요될 수 있습니다.')) {
        // 대량 생성 API 호출
        alert('기능 구현 중입니다.');
    }
}

// 선택된 파트너 대량 삭제
function bulkDelete() {
    const selected = document.querySelectorAll('.partner-checkbox:checked');
    if (selected.length === 0) {
        alert('삭제할 파트너를 선택해주세요.');
        return;
    }

    if (confirm(`선택된 ${selected.length}명의 파트너 코드를 삭제하시겠습니까?`)) {
        // 대량 삭제 API 호출
        alert('기능 구현 중입니다.');
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
document.getElementById('bulkGenerateForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    alert('대량 생성 기능을 구현 중입니다.');

    // AJAX 요청으로 대량 생성 처리
    // 실제 구현 시에는 progress bar나 로딩 상태 표시 필요
});
</script>
@endsection