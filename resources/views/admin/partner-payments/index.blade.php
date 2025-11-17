@extends('jiny-partner::layouts.admin.sidebar')

@section('title', '파트너 지급 관리')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">💳 파트너 지급 관리</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="/admin">관리자</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.partner.index') }}">파트너</a></li>
                        <li class="breadcrumb-item active">지급 관리</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- 통계 대시보드 -->
    <div class="row mb-4">
        <div class="col-xl-2 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar avatar-md bg-primary-subtle rounded">
                                <i class="fe fe-credit-card fs-20 text-primary"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1">{{ number_format($stats['total_payments']) }}</h5>
                            <p class="text-muted mb-0">전체 지급</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar avatar-md bg-warning-subtle rounded">
                                <i class="fe fe-clock fs-20 text-warning"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1">{{ number_format($stats['pending_approval']) }}</h5>
                            <p class="text-muted mb-0">승인 대기</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar avatar-md bg-info-subtle rounded">
                                <i class="fe fe-refresh-cw fs-20 text-info"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1">{{ number_format($stats['processing_payments']) }}</h5>
                            <p class="text-muted mb-0">처리 중</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar avatar-md bg-success-subtle rounded">
                                <i class="fe fe-check-circle fs-20 text-success"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1">{{ number_format($stats['completed_this_month']) }}</h5>
                            <p class="text-muted mb-0">이번 달 완료</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar avatar-md bg-primary-subtle rounded">
                                <i class="fe fe-dollar-sign fs-20 text-primary"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1">{{ number_format($stats['total_amount_this_month'], 0) }}만원</h5>
                            <p class="text-muted mb-0">이번 달 총액</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar avatar-md bg-secondary-subtle rounded">
                                <i class="fe fe-trending-up fs-20 text-secondary"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1">{{ number_format($stats['avg_payment_amount'], 0) }}만원</h5>
                            <p class="text-muted mb-0">평균 지급액</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 필터 및 검색 -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.partner.payments.index') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">파트너</label>
                        <select name="partner_id" class="form-select">
                            <option value="">전체 파트너</option>
                            @foreach($partners as $partner)
                                <option value="{{ $partner->id }}" {{ request('partner_id') == $partner->id ? 'selected' : '' }}>
                                    {{ $partner->name }} ({{ $partner->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">상태</label>
                        <select name="status" class="form-select">
                            <option value="">전체 상태</option>
                            <option value="requested" {{ request('status') == 'requested' ? 'selected' : '' }}>신청</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>승인</option>
                            <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>처리중</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>완료</option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>실패</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>취소</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">지급 방법</label>
                        <select name="payment_method" class="form-select">
                            <option value="">전체</option>
                            <option value="bank_transfer" {{ request('payment_method') == 'bank_transfer' ? 'selected' : '' }}>은행이체</option>
                            <option value="cash" {{ request('payment_method') == 'cash' ? 'selected' : '' }}>현금</option>
                            <option value="check" {{ request('payment_method') == 'check' ? 'selected' : '' }}>수표</option>
                            <option value="digital_wallet" {{ request('payment_method') == 'digital_wallet' ? 'selected' : '' }}>디지털지갑</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">시작일</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">종료일</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>

                    <div class="col-md-1">
                        <label class="form-label">최소금액</label>
                        <input type="number" name="min_amount" class="form-control" placeholder="만원" value="{{ request('min_amount') }}">
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-4">
                        <label class="form-label">검색</label>
                        <div class="input-group">
                            <input type="text" name="search" class="form-control"
                                   placeholder="지급코드, 파트너명, 이메일" value="{{ request('search') }}">
                            <button type="submit" class="btn btn-primary">
                                <i class="fe fe-search"></i>
                            </button>
                            <a href="{{ route('admin.partner.payments.index') }}" class="btn btn-secondary">
                                <i class="fe fe-refresh-cw"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 지급 목록 -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">지급 내역 목록</h5>
            <a href="{{ route('admin.partner.payments.create') }}" class="btn btn-primary">
                <i class="fe fe-plus"></i> 새 지급 신청
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>지급코드</th>
                            <th>파트너</th>
                            <th>지급방법</th>
                            <th class="text-end">신청액</th>
                            <th class="text-end">수수료/세금</th>
                            <th class="text-end">실지급액</th>
                            <th class="text-center">상태</th>
                            <th class="text-center">신청일</th>
                            <th>액션</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $payment->payment_code }}</div>
                                    @if($payment->batch_id)
                                        <small class="text-info">배치: {{ $payment->batch_id }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm bg-light rounded-circle me-2">
                                            <span class="text-dark fs-14">{{ substr($payment->partner_name, 0, 1) }}</span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ $payment->partner_name }}</h6>
                                            <small class="text-muted">{{ $payment->partner_email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @switch($payment->payment_method)
                                        @case('bank_transfer')
                                            <span class="badge bg-primary fs-6">🏦 은행이체</span>
                                            @break
                                        @case('cash')
                                            <span class="badge bg-success fs-6">💵 현금</span>
                                            @break
                                        @case('check')
                                            <span class="badge bg-warning fs-6">📄 수표</span>
                                            @break
                                        @case('digital_wallet')
                                            <span class="badge bg-info fs-6">📱 디지털지갑</span>
                                            @break
                                    @endswitch
                                </td>
                                <td class="text-end">
                                    <div class="fw-medium">{{ number_format($payment->requested_amount, 0) }}만원</div>
                                </td>
                                <td class="text-end">
                                    <div class="text-danger">
                                        {{ number_format($payment->fee_amount + $payment->tax_amount, 0) }}만원
                                    </div>
                                    @if($payment->fee_amount > 0 || $payment->tax_amount > 0)
                                        <small class="text-muted">
                                            수수료 {{ number_format($payment->fee_amount, 0) }} + 세금 {{ number_format($payment->tax_amount, 0) }}
                                        </small>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="fw-bold text-primary">{{ number_format($payment->final_amount, 0) }}만원</div>
                                </td>
                                <td class="text-center">
                                    @switch($payment->status)
                                        @case('requested')
                                            <span class="badge bg-warning">📋 신청</span>
                                            @break
                                        @case('approved')
                                            <span class="badge bg-info">✅ 승인</span>
                                            @break
                                        @case('processing')
                                            <span class="badge bg-primary">🔄 처리중</span>
                                            @break
                                        @case('completed')
                                            <span class="badge bg-success">✅ 완료</span>
                                            @break
                                        @case('failed')
                                            <span class="badge bg-danger">❌ 실패</span>
                                            @break
                                        @case('cancelled')
                                            <span class="badge bg-secondary">🚫 취소</span>
                                            @break
                                    @endswitch
                                </td>
                                <td class="text-center">
                                    <div>{{ date('Y-m-d', strtotime($payment->requested_at)) }}</div>
                                    <small class="text-muted">{{ date('H:i', strtotime($payment->requested_at)) }}</small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.partner.payments.show', $payment->id) }}"
                                           class="btn btn-outline-primary" title="상세보기">
                                            <i class="fe fe-eye"></i>
                                        </a>
                                        @if(in_array($payment->status, ['requested', 'approved']))
                                            <a href="{{ route('admin.partner.payments.edit', $payment->id) }}"
                                               class="btn btn-outline-warning" title="수정">
                                                <i class="fe fe-edit"></i>
                                            </a>
                                        @endif

                                        <!-- 상태별 액션 버튼 -->
                                        @if($payment->status === 'requested')
                                            <button type="button" class="btn btn-outline-success"
                                                    onclick="approvePayment({{ $payment->id }})" title="승인">
                                                <i class="fe fe-check"></i>
                                            </button>
                                        @elseif($payment->status === 'approved')
                                            <button type="button" class="btn btn-outline-info"
                                                    onclick="processPayment({{ $payment->id }})" title="처리">
                                                <i class="fe fe-play"></i>
                                            </button>
                                        @elseif($payment->status === 'processing')
                                            <button type="button" class="btn btn-outline-success"
                                                    onclick="completePayment({{ $payment->id }})" title="완료">
                                                <i class="fe fe-check-circle"></i>
                                            </button>
                                        @endif

                                        @if(!in_array($payment->status, ['completed', 'cancelled']))
                                            <button type="button" class="btn btn-outline-danger"
                                                    onclick="cancelPayment({{ $payment->id }})" title="취소">
                                                <i class="fe fe-x"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fe fe-credit-card fs-48 d-block mb-2"></i>
                                        등록된 지급 내역이 없습니다.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($payments->hasPages())
        <div class="card-footer">
            {{ $payments->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

<!-- 승인 모달 -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approveModalLabel">지급 승인</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="approveForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="approval_notes" class="form-label">승인 메모 (선택사항)</label>
                        <textarea name="approval_notes" id="approval_notes" class="form-control" rows="3"
                                  placeholder="승인에 대한 메모를 입력하세요..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-success">승인</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 처리 모달 -->
<div class="modal fade" id="processModal" tabindex="-1" aria-labelledby="processModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="processModalLabel">지급 처리</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="processForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="processing_notes" class="form-label">처리 메모 (선택사항)</label>
                        <textarea name="processing_notes" id="processing_notes" class="form-control" rows="3"
                                  placeholder="처리에 대한 메모를 입력하세요..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-info">처리 시작</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 완료 모달 -->
<div class="modal fade" id="completeModal" tabindex="-1" aria-labelledby="completeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="completeModalLabel">지급 완료</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="completeForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="external_transaction_id" class="form-label">외부 거래 ID</label>
                        <input type="text" name="external_transaction_id" id="external_transaction_id" class="form-control"
                               placeholder="은행 거래번호 등...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-success">완료 처리</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 취소 모달 -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelModalLabel">지급 취소</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="cancelForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="failure_reason" class="form-label">취소 사유 <span class="text-danger">*</span></label>
                        <textarea name="failure_reason" id="failure_reason" class="form-control" rows="3" required
                                  placeholder="취소 사유를 입력하세요..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-danger">지급 취소</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function approvePayment(id) {
    const form = document.getElementById('approveForm');
    form.action = `/admin/partner/payments/${id}/approve`;
    document.getElementById('approval_notes').value = '';

    const modal = new bootstrap.Modal(document.getElementById('approveModal'));
    modal.show();
}

function processPayment(id) {
    const form = document.getElementById('processForm');
    form.action = `/admin/partner/payments/${id}/process`;
    document.getElementById('processing_notes').value = '';

    const modal = new bootstrap.Modal(document.getElementById('processModal'));
    modal.show();
}

function completePayment(id) {
    const form = document.getElementById('completeForm');
    form.action = `/admin/partner/payments/${id}/complete`;
    document.getElementById('external_transaction_id').value = '';

    const modal = new bootstrap.Modal(document.getElementById('completeModal'));
    modal.show();
}

function cancelPayment(id) {
    const form = document.getElementById('cancelForm');
    form.action = `/admin/partner/payments/${id}/cancel`;
    document.getElementById('failure_reason').value = '';

    const modal = new bootstrap.Modal(document.getElementById('cancelModal'));
    modal.show();
}
</script>
@endsection