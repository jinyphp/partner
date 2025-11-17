@extends('jiny-partner::layouts.admin.sidebar')

@section('title', '지급 정보 수정')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">💳 지급 정보 수정</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="/admin">관리자</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.partner.index') }}">파트너</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.partner.payments.index') }}">지급 관리</a></li>
                        <li class="breadcrumb-item active">수정</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.partner.payments.update', $payment->id) }}">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-8">
                <!-- 기본 정보 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">📋 지급 기본 정보</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">지급 코드</label>
                                    <div class="form-control-plaintext h5 text-primary">{{ $payment->payment_code }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">현재 상태</label>
                                    <div>
                                        @switch($payment->status)
                                            @case('requested')
                                                <span class="badge bg-warning fs-6">📋 신청됨</span>
                                                @break
                                            @case('approved')
                                                <span class="badge bg-info fs-6">✅ 승인됨</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary fs-6">{{ $payment->status }}</span>
                                        @endswitch
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">파트너 (신청 당시)</label>
                                    <div class="form-control-plaintext">
                                        {{ $payment->partner_name }}<br>
                                        <small class="text-muted">{{ $payment->partner_email }}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">파트너 (현재)</label>
                                    <div class="form-control-plaintext">
                                        {{ $payment->partner_name_current }}<br>
                                        <small class="text-muted">{{ $payment->partner_email_current }}</small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="requested_amount" class="form-label">신청 금액 (만원) <span class="text-danger">*</span></label>
                                    <input type="number" name="requested_amount" id="requested_amount" class="form-control"
                                           value="{{ old('requested_amount', $payment->requested_amount) }}" min="1" step="0.01" required
                                           placeholder="0" onchange="calculateFinalAmount()">
                                    @error('requested_amount')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">신청일시</label>
                                    <div class="form-control-plaintext">{{ date('Y년 m월 d일 H:i', strtotime($payment->requested_at)) }}</div>
                                </div>
                            </div>
                        </div>

                        @if($payment->status !== 'requested')
                        <div class="alert alert-warning">
                            <i class="fe fe-alert-triangle me-2"></i>
                            <strong>주의:</strong> 이미 승인된 지급입니다. 신중하게 수정해주세요.
                        </div>
                        @endif
                    </div>
                </div>

                <!-- 지급 방법 및 계좌 정보 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">🏦 지급 방법 및 계좌 정보</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="payment_method" class="form-label">지급 방법 <span class="text-danger">*</span></label>
                                    <select name="payment_method" id="payment_method" class="form-select" required onchange="toggleAccountFields()">
                                        <option value="bank_transfer" {{ old('payment_method', $payment->payment_method) == 'bank_transfer' ? 'selected' : '' }}>
                                            🏦 은행 이체
                                        </option>
                                        <option value="cash" {{ old('payment_method', $payment->payment_method) == 'cash' ? 'selected' : '' }}>
                                            💵 현금 지급
                                        </option>
                                        <option value="check" {{ old('payment_method', $payment->payment_method) == 'check' ? 'selected' : '' }}>
                                            📄 수표 발행
                                        </option>
                                        <option value="digital_wallet" {{ old('payment_method', $payment->payment_method) == 'digital_wallet' ? 'selected' : '' }}>
                                            📱 디지털지갑
                                        </option>
                                    </select>
                                    @error('payment_method')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div id="bank-account-fields">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="bank_name" class="form-label">은행명</label>
                                        <input type="text" name="bank_name" id="bank_name" class="form-control"
                                               value="{{ old('bank_name', $payment->bank_name) }}" placeholder="예: 국민은행">
                                        @error('bank_name')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="account_number" class="form-label">계좌번호</label>
                                        <input type="text" name="account_number" id="account_number" class="form-control"
                                               value="{{ old('account_number', $payment->account_number) }}" placeholder="123-456-789012">
                                        @error('account_number')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="account_holder" class="form-label">예금주명</label>
                                        <input type="text" name="account_holder" id="account_holder" class="form-control"
                                               value="{{ old('account_holder', $payment->account_holder) }}" placeholder="홍길동">
                                        @error('account_holder')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 수수료 및 세금 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">💰 수수료 및 세금</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="fee_amount" class="form-label">지급 수수료 (만원)</label>
                                    <input type="number" name="fee_amount" id="fee_amount" class="form-control"
                                           value="{{ old('fee_amount', $payment->fee_amount) }}" min="0" step="0.01"
                                           placeholder="0" onchange="calculateFinalAmount()">
                                    <small class="text-muted">은행 이체 수수료 등</small>
                                    @error('fee_amount')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tax_amount" class="form-label">세금 (만원)</label>
                                    <input type="number" name="tax_amount" id="tax_amount" class="form-control"
                                           value="{{ old('tax_amount', $payment->tax_amount) }}" min="0" step="0.01"
                                           placeholder="0" onchange="calculateFinalAmount()">
                                    <small class="text-muted">원천징수세 등</small>
                                    @error('tax_amount')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- 세금 자동 계산 버튼들 -->
                        <div class="mb-3">
                            <label class="form-label">세금 자동 계산</label>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="calculateTax(3.3)">
                                    3.3% (사업소득세)
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="calculateTax(8.8)">
                                    8.8% (근로소득세)
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="calculateTax(10)">
                                    10% (기타소득세)
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm" onclick="clearTax()">
                                    초기화
                                </button>
                            </div>
                        </div>

                        <!-- 기존 vs 새로운 금액 비교 -->
                        <div class="alert alert-info">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>기존 실 지급액:</strong><br>
                                    <span class="text-muted">{{ number_format($payment->final_amount, 0) }}만원</span>
                                </div>
                                <div class="col-md-6">
                                    <strong>새로운 실 지급액:</strong><br>
                                    <span id="new-final-amount" class="text-primary fw-bold">{{ number_format($payment->final_amount, 0) }}만원</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 메모 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">📝 메모</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="notes" class="form-label">관리자 메모</label>
                            <textarea name="notes" id="notes" class="form-control" rows="4"
                                      placeholder="지급에 대한 추가 메모를 입력하세요...">{{ old('notes', $payment->notes) }}</textarea>
                            @error('notes')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- 금액 계산 미리보기 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">💰 금액 계산 미리보기</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>신청 금액:</span>
                                <span id="preview-requested" class="fw-bold">{{ number_format($payment->requested_amount, 0) }}만원</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between text-warning">
                                <span>수수료:</span>
                                <span id="preview-fee">{{ number_format($payment->fee_amount, 0) }}만원</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between text-danger">
                                <span>세금:</span>
                                <span id="preview-tax">{{ number_format($payment->tax_amount, 0) }}만원</span>
                            </div>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold">실 지급액:</span>
                                <span id="preview-final" class="fw-bold text-primary h5">{{ number_format($payment->final_amount, 0) }}만원</span>
                            </div>
                        </div>

                        <div class="small text-muted">
                            <div>공제율: <span id="preview-deduction-rate">0%</span></div>
                        </div>

                        @php
                            $originalDeductionRate = ($payment->fee_amount + $payment->tax_amount) / $payment->requested_amount * 100;
                        @endphp
                        <div class="small text-info mt-2">
                            <div>기존 공제율: {{ number_format($originalDeductionRate, 1) }}%</div>
                        </div>
                    </div>
                </div>

                <!-- 수정 경고 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">⚠️ 수정 주의사항</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled small">
                            @if($payment->status === 'approved')
                                <li class="mb-2 text-warning">• 이미 승인된 지급입니다.</li>
                                <li class="mb-2">• 금액 변경 시 재승인이 필요할 수 있습니다.</li>
                            @endif
                            <li class="mb-2">• 계좌 정보는 신중하게 확인해주세요.</li>
                            <li class="mb-2">• 세금 계산은 관련 법규를 확인하세요.</li>
                            <li class="mb-2">• 수수료는 지급 방법에 따라 달라질 수 있습니다.</li>
                        </ul>
                    </div>
                </div>

                <!-- 진행 이력 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">📅 진행 이력</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <!-- 신청 -->
                            <div class="timeline-item">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">지급 신청</h6>
                                    <div class="text-muted small">{{ date('Y-m-d H:i', strtotime($payment->requested_at)) }}</div>
                                </div>
                            </div>

                            <!-- 승인 -->
                            @if($payment->approved_at)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-info"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">승인 처리</h6>
                                    <div class="text-muted small">{{ date('Y-m-d H:i', strtotime($payment->approved_at)) }}</div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- 제출 버튼 -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fe fe-save"></i> 지급 정보 수정
                            </button>
                            <a href="{{ route('admin.partner.payments.show', $payment->id) }}" class="btn btn-secondary">
                                <i class="fe fe-eye"></i> 상세보기
                            </a>
                            <a href="{{ route('admin.partner.payments.index') }}" class="btn btn-outline-secondary">
                                <i class="fe fe-x"></i> 취소
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    height: 100%;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 4px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 2px solid #fff;
    z-index: 1;
}

.timeline-content {
    padding-left: 10px;
}
</style>

<script>
// 지급 방법 변경 시 계좌 필드 토글
function toggleAccountFields() {
    const paymentMethod = document.getElementById('payment_method').value;
    const bankFields = document.getElementById('bank-account-fields');

    if (paymentMethod === 'bank_transfer') {
        bankFields.style.display = 'block';
    } else {
        bankFields.style.display = 'none';
    }
}

// 세금 자동 계산
function calculateTax(rate) {
    const requestedAmount = parseFloat(document.getElementById('requested_amount').value) || 0;
    const taxAmount = requestedAmount * (rate / 100);
    document.getElementById('tax_amount').value = taxAmount.toFixed(2);
    calculateFinalAmount();
}

// 세금 초기화
function clearTax() {
    document.getElementById('tax_amount').value = 0;
    calculateFinalAmount();
}

// 최종 지급액 계산
function calculateFinalAmount() {
    const requestedAmount = parseFloat(document.getElementById('requested_amount').value) || 0;
    const feeAmount = parseFloat(document.getElementById('fee_amount').value) || 0;
    const taxAmount = parseFloat(document.getElementById('tax_amount').value) || 0;
    const finalAmount = requestedAmount - feeAmount - taxAmount;

    // 미리보기 업데이트
    document.getElementById('preview-requested').textContent = requestedAmount.toLocaleString() + '만원';
    document.getElementById('preview-fee').textContent = feeAmount.toLocaleString() + '만원';
    document.getElementById('preview-tax').textContent = taxAmount.toLocaleString() + '만원';
    document.getElementById('preview-final').textContent = finalAmount.toLocaleString() + '만원';

    // 새로운 실 지급액 표시
    document.getElementById('new-final-amount').textContent = finalAmount.toLocaleString() + '만원';

    // 공제율 계산
    const deductionRate = requestedAmount > 0 ? ((feeAmount + taxAmount) / requestedAmount * 100) : 0;
    document.getElementById('preview-deduction-rate').textContent = deductionRate.toFixed(1) + '%';
}

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    toggleAccountFields();
    calculateFinalAmount();
});
</script>
@endsection