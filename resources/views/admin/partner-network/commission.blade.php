@extends('jiny-partner::layouts.admin.sidebar')

@section('content')
<div class="container-fluid px-6 py-4">
    <!-- Page Header -->
    <div class="row">
        <div class="col-lg-12 col-md-12 col-12">
            <div class="border-bottom pb-3 mb-3 d-lg-flex align-items-center justify-content-between">
                <div class="mb-2 mb-lg-0">
                    <h1 class="mb-0 h2 fw-bold">{{ $pageTitle }}</h1>
                    <p class="mb-0 text-muted">파트너 네트워크의 커미션 분배를 관리하고 추적합니다.</p>
                </div>
                <div>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#calculateCommissionModal">
                        <i class="fe fe-calculator me-2"></i>커미션 계산
                    </button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
                        <i class="fe fe-filter me-2"></i>필터
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-lg-6 col-md-12 col-12">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ number_format($statistics['total_commission']) }}원</h4>
                            <p class="mb-0 text-muted">총 커미션</p>
                        </div>
                        <div class="icon-shape icon-md bg-primary text-white rounded-3">
                            <i class="fe fe-dollar-sign"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-12 col-12">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ number_format($statistics['calculated_commission'] ?? 0) }}원</h4>
                            <p class="mb-0 text-muted">계산 완료</p>
                        </div>
                        <div class="icon-shape icon-md bg-success text-white rounded-3">
                            <i class="fe fe-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-12 col-12">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ number_format($statistics['active_commission'] ?? 0) }}원</h4>
                            <p class="mb-0 text-muted">활성 커미션</p>
                        </div>
                        <div class="icon-shape icon-md bg-info text-white rounded-3">
                            <i class="fe fe-activity"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-12 col-12">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ number_format($statistics['average_commission']) }}원</h4>
                            <p class="mb-0 text-muted">평균 커미션</p>
                        </div>
                        <div class="icon-shape icon-md bg-info text-white rounded-3">
                            <i class="fe fe-trending-up"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Commission Type Statistics -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">커미션 타입별 통계</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($typeStatistics as $type => $stats)
                            <div class="col-md-2 text-center mb-3">
                                <h6 class="mb-1">{{ $commissionTypes[$type] ?? $type }}</h6>
                                <div class="text-primary fw-bold">{{ number_format($stats->total_amount) }}원</div>
                                <small class="text-muted">{{ $stats->count }}건</small>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Commission List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">커미션 내역</h5>
                    <div>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="selectAll()">
                            전체 선택
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="bulkAction('cancel')">
                            선택 취소
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>
                                        <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll()">
                                    </th>
                                    <th>파트너</th>
                                    <th>소스 파트너</th>
                                    <th>커미션 타입</th>
                                    <th>금액</th>
                                    <th>수수료율</th>
                                    <th>순금액</th>
                                    <th>상태</th>
                                    <th>발생일</th>
                                    <th>액션</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($commissions as $commission)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="commission-checkbox" value="{{ $commission->id }}">
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-xs me-2">
                                                    <span class="avatar-initials rounded-circle bg-primary">
                                                        {{ strtoupper(substr($commission->partner->name, 0, 1)) }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">{{ $commission->partner->name }}</h6>
                                                    <small class="text-muted">{{ $commission->partner->email }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($commission->sourcePartner)
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-xs me-2">
                                                        <span class="avatar-initials rounded-circle bg-info">
                                                            {{ strtoupper(substr($commission->sourcePartner->name, 0, 1)) }}
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0">{{ $commission->sourcePartner->name }}</h6>
                                                        <small class="text-muted">{{ $commission->sourcePartner->email }}</small>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                {{ $commissionTypes[$commission->commission_type] ?? $commission->commission_type }}
                                            </span>
                                        </td>
                                        <td class="fw-bold">{{ number_format($commission->commission_amount) }}원</td>
                                        <td>{{ $commission->commission_rate }}%</td>
                                        <td class="fw-bold text-success">{{ number_format($commission->net_amount) }}원</td>
                                        <td>
                                            @php
                                                $statusColors = [
                                                    'pending' => 'warning',
                                                    'calculated' => 'info',
                                                    'cancelled' => 'danger'
                                                ];
                                                $statusLabels = [
                                                    'pending' => '대기',
                                                    'calculated' => '계산완료',
                                                    'cancelled' => '취소'
                                                ];
                                            @endphp
                                            <span class="badge bg-{{ $statusColors[$commission->status] ?? 'secondary' }}">
                                                {{ $statusLabels[$commission->status] ?? $commission->status }}
                                            </span>
                                        </td>
                                        <td>{{ $commission->earned_at->format('Y-m-d H:i:s') }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('admin.partner.network.commission.show', $commission->id) }}"
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="fe fe-eye"></i>
                                                </a>
                                                @if(in_array($commission->status, ['pending', 'calculated']))
                                                    <button type="button" class="btn btn-outline-danger btn-sm"
                                                            onclick="cancelCommission({{ $commission->id }})">
                                                        <i class="fe fe-x"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-4">
                                            <i class="fe fe-dollar-sign display-4 text-muted"></i>
                                            <h5 class="mt-3">커미션 데이터가 없습니다</h5>
                                            <p class="text-muted">커미션을 계산하여 분배해보세요.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($commissions->hasPages())
                    <div class="card-footer">
                        {{ $commissions->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Calculate Commission Modal -->
<div class="modal fade" id="calculateCommissionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">커미션 계산</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="calculateCommissionForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">소스 파트너</label>
                        <select name="source_partner_id" class="form-select" required>
                            <option value="">파트너를 선택하세요</option>
                            @foreach($availablePartners as $partner)
                                <option value="{{ $partner->id }}">{{ $partner->name }} ({{ $partner->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">매출 금액</label>
                        <input type="number" name="sale_amount" class="form-control" required min="0" step="1000">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">주문 정보 (JSON, 선택)</label>
                        <textarea name="order_data" class="form-control" rows="3"
                                  placeholder='{"order_id": "ORD-001", "product": "상품명"}'></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-success">계산하기</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">필터 설정</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="GET" action="{{ route('admin.partner.network.commission.index') }}">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">상태</label>
                                <select name="status" class="form-select">
                                    <option value="all" {{ $currentFilters['status'] === 'all' ? 'selected' : '' }}>전체</option>
                                    <option value="pending" {{ $currentFilters['status'] === 'pending' ? 'selected' : '' }}>대기</option>
                                    <option value="calculated" {{ $currentFilters['status'] === 'calculated' ? 'selected' : '' }}>계산완료</option>
                                    <option value="cancelled" {{ $currentFilters['status'] === 'cancelled' ? 'selected' : '' }}>취소</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">커미션 타입</label>
                                <select name="type" class="form-select">
                                    <option value="all" {{ $currentFilters['type'] === 'all' ? 'selected' : '' }}>전체</option>
                                    @foreach($commissionTypes as $type => $label)
                                        <option value="{{ $type }}" {{ $currentFilters['type'] === $type ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">파트너</label>
                        <select name="partner_id" class="form-select">
                            <option value="">전체 파트너</option>
                            @foreach($availablePartners as $partner)
                                <option value="{{ $partner->id }}" {{ $currentFilters['partner_id'] == $partner->id ? 'selected' : '' }}>
                                    {{ $partner->name }} ({{ $partner->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">시작일</label>
                                <input type="date" name="start_date" class="form-control" value="{{ $currentFilters['start_date'] }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">종료일</label>
                                <input type="date" name="end_date" class="form-control" value="{{ $currentFilters['end_date'] }}">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">적용</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Calculate Commission
$('#calculateCommissionForm').on('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    // Parse order_data if provided
    const orderDataText = formData.get('order_data');
    if (orderDataText) {
        try {
            const orderData = JSON.parse(orderDataText);
            formData.delete('order_data');
            formData.append('order_data', JSON.stringify(orderData));
        } catch (e) {
            alert('주문 정보 JSON 형식이 올바르지 않습니다.');
            return;
        }
    }

    $.ajax({
        url: '{{ route("admin.partner.network.commission.calculate") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                alert('커미션이 성공적으로 계산되었습니다.');
                location.reload();
            } else {
                alert('오류: ' + response.message);
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            alert('오류: ' + (response?.message || '알 수 없는 오류가 발생했습니다.'));
        }
    });
});

// Selection functions
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAllCheckbox');
    const checkboxes = document.querySelectorAll('.commission-checkbox');

    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

function selectAll() {
    const checkboxes = document.querySelectorAll('.commission-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
    });
    document.getElementById('selectAllCheckbox').checked = true;
}

// Bulk actions
function bulkAction(action) {
    const selectedIds = [];
    document.querySelectorAll('.commission-checkbox:checked').forEach(checkbox => {
        selectedIds.push(checkbox.value);
    });

    if (selectedIds.length === 0) {
        alert('선택된 커미션이 없습니다.');
        return;
    }

    if (!confirm(`선택된 ${selectedIds.length}개의 커미션을 취소하시겠습니까?`)) {
        return;
    }

    $.ajax({
        url: '{{ route("admin.partner.network.commission.bulk-process") }}',
        method: 'POST',
        data: {
            commission_ids: selectedIds,
            action: action,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            alert(response.message);
            if (response.success) {
                location.reload();
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            alert('오류: ' + (response?.message || '알 수 없는 오류가 발생했습니다.'));
        }
    });
}

// Individual actions
// payCommission 함수 제거됨 - 지급 기능은 별도의 지급 테이블로 관리

function cancelCommission(id) {
    const reason = prompt('취소 사유를 입력하세요:');
    if (!reason) return;

    $.ajax({
        url: '{{ route("admin.partner.network.commission.bulk-process") }}',
        method: 'POST',
        data: {
            commission_ids: [id],
            action: 'cancel',
            reason: reason,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            alert(response.message);
            if (response.success) {
                location.reload();
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            alert('오류: ' + (response?.message || '알 수 없는 오류가 발생했습니다.'));
        }
    });
}
</script>
@endsection