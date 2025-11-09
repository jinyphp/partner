@extends('jiny-site::layouts.home')

@section('title', '커미션 이력')

@section('content')
<div class="container-fluid p-6">
    <div class="row">
        <div class="col-lg-12">
            <div class="border-bottom pb-3 mb-3">
                <h1 class="mb-1 h2 fw-bold">커미션 이력</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home.partner.index') }}">파트너 홈</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('home.partner.commission.index') }}">커미션 관리</a></li>
                        <li class="breadcrumb-item active">커미션 이력</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">기간</label>
                            <select name="period" class="form-select">
                                <option value="all" {{ $currentPeriod === 'all' ? 'selected' : '' }}>전체</option>
                                <option value="this_month" {{ $currentPeriod === 'this_month' ? 'selected' : '' }}>이번 달</option>
                                <option value="last_month" {{ $currentPeriod === 'last_month' ? 'selected' : '' }}>지난 달</option>
                                <option value="this_year" {{ $currentPeriod === 'this_year' ? 'selected' : '' }}>올해</option>
                                <option value="custom" {{ $currentPeriod === 'custom' ? 'selected' : '' }}>직접 설정</option>
                            </select>
                        </div>
                        <div class="col-md-2" id="startDateField" style="display: {{ $currentPeriod === 'custom' ? 'block' : 'none' }};">
                            <label class="form-label">시작일</label>
                            <input type="date" name="start_date" class="form-control" value="{{ $startDate ?? '' }}">
                        </div>
                        <div class="col-md-2" id="endDateField" style="display: {{ $currentPeriod === 'custom' ? 'block' : 'none' }};">
                            <label class="form-label">종료일</label>
                            <input type="date" name="end_date" class="form-control" value="{{ $endDate ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">상태</label>
                            <select name="status" class="form-select">
                                <option value="all" {{ $currentStatus === 'all' ? 'selected' : '' }}>전체</option>
                                <option value="pending" {{ $currentStatus === 'pending' ? 'selected' : '' }}>대기</option>
                                <option value="paid" {{ $currentStatus === 'paid' ? 'selected' : '' }}>지급완료</option>
                                <option value="cancelled" {{ $currentStatus === 'cancelled' ? 'selected' : '' }}>취소</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">검색</button>
                            <a href="{{ route('home.partner.commission.history') }}" class="btn btn-outline-secondary ms-2">초기화</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ number_format($filteredStats['total_count']) }}</h4>
                    <p class="mb-0">총 건수</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ number_format($filteredStats['total_amount']) }}원</h4>
                    <p class="mb-0">총 금액</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ number_format($filteredStats['paid_amount']) }}원</h4>
                    <p class="mb-0">지급 완료</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ number_format($filteredStats['pending_amount']) }}원</h4>
                    <p class="mb-0">대기 중</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Commission History Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">커미션 내역</h4>
                    <div>
                        <button class="btn btn-outline-success btn-sm" onclick="exportToExcel()">
                            <i class="fe fe-download me-1"></i>엑셀 다운로드
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>날짜</th>
                                    <th>유형</th>
                                    <th>판매자/추천인</th>
                                    <th>상품명</th>
                                    <th>판매금액</th>
                                    <th>커미션</th>
                                    <th>상태</th>
                                    <th>지급일</th>
                                    <th>세부정보</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($commissionHistory as $commission)
                                <tr>
                                    <td>{{ $commission->created_at->format('Y-m-d') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $commission->commission_type === 'direct' ? 'primary' : 'info' }}">
                                            {{ $commission->commission_type === 'direct' ? '직접판매' : '추천보너스' }}
                                        </span>
                                    </td>
                                    <td>{{ $commission->source_name ?? '직접판매' }}</td>
                                    <td>{{ $commission->product_name ?? '상품명' }}</td>
                                    <td>{{ number_format($commission->sale_amount) }}원</td>
                                    <td class="fw-bold">{{ number_format($commission->amount) }}원</td>
                                    <td>
                                        <span class="badge bg-{{
                                            $commission->status === 'paid' ? 'success' :
                                            ($commission->status === 'pending' ? 'warning' : 'danger')
                                        }}">
                                            {{
                                                $commission->status === 'paid' ? '지급완료' :
                                                ($commission->status === 'pending' ? '대기중' : '취소')
                                            }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $commission->paid_at ? $commission->paid_at->format('Y-m-d') : '-' }}
                                    </td>
                                    <td>
                                        <button class="btn btn-outline-info btn-sm" onclick="showCommissionDetail({{ $commission->id }})">
                                            <i class="fe fe-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <div class="mb-3">
                                            <i class="fe fe-inbox display-4 text-muted"></i>
                                        </div>
                                        <h6 class="mb-1">커미션 내역이 없습니다</h6>
                                        <p class="text-muted">조건을 변경하여 다시 검색해보세요.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($commissionHistory && method_exists($commissionHistory, 'links'))
                        {{ $commissionHistory->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Commission Detail Modal -->
    <div class="modal fade" id="commissionDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">커미션 상세 정보</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="commissionDetailContent">
                    <!-- 동적으로 로드될 내용 -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// 기간 선택 변경 시 날짜 필드 표시/숨김
document.querySelector('select[name="period"]').addEventListener('change', function() {
    const startDateField = document.getElementById('startDateField');
    const endDateField = document.getElementById('endDateField');

    if (this.value === 'custom') {
        startDateField.style.display = 'block';
        endDateField.style.display = 'block';
    } else {
        startDateField.style.display = 'none';
        endDateField.style.display = 'none';
    }
});

// 커미션 상세 정보 모달
function showCommissionDetail(commissionId) {
    fetch(`/home/partner/commission/${commissionId}/detail`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('commissionDetailContent').innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>기본 정보</h6>
                            <table class="table table-sm">
                                <tr><td>커미션 ID:</td><td>${data.commission.id}</td></tr>
                                <tr><td>생성일:</td><td>${data.commission.created_at}</td></tr>
                                <tr><td>커미션 유형:</td><td>${data.commission.commission_type}</td></tr>
                                <tr><td>상태:</td><td>${data.commission.status}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>금액 정보</h6>
                            <table class="table table-sm">
                                <tr><td>판매 금액:</td><td>${data.commission.sale_amount}원</td></tr>
                                <tr><td>커미션 비율:</td><td>${data.commission.commission_rate}%</td></tr>
                                <tr><td>커미션 금액:</td><td>${data.commission.amount}원</td></tr>
                                <tr><td>지급일:</td><td>${data.commission.paid_at || '미지급'}</td></tr>
                            </table>
                        </div>
                    </div>
                    ${data.commission.notes ? `
                        <div class="mt-3">
                            <h6>참고사항</h6>
                            <p class="text-muted">${data.commission.notes}</p>
                        </div>
                    ` : ''}
                `;

                const modal = new bootstrap.Modal(document.getElementById('commissionDetailModal'));
                modal.show();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('상세 정보를 불러오는데 실패했습니다.');
        });
}

// 엑셀 다운로드
function exportToExcel() {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('export', 'excel');
    window.location.href = currentUrl.toString();
}
</script>
@endpush