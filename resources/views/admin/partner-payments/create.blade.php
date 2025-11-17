@extends('jiny-partner::layouts.admin.sidebar')

@section('title', '지급 신청 등록')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">💳 지급 신청 등록</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="/admin">관리자</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.partner.index') }}">파트너</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.partner.payments.index') }}">지급 관리</a></li>
                        <li class="breadcrumb-item active">등록</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.partner.payments.store') }}">
        @csrf
        <div class="row">
            <div class="col-lg-8">
                <!-- 기본 정보 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">📋 기본 정보</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="partner_id" class="form-label">파트너 선택 <span class="text-danger">*</span></label>
                                    <select name="partner_id" id="partner_id" class="form-select" required onchange="loadPartnerInfo()">
                                        <option value="">파트너를 선택하세요</option>
                                        @foreach($partners as $p)
                                            <option value="{{ $p->id }}"
                                                    {{ (request('partner_id') == $p->id || (isset($partner) && $partner->id == $p->id)) ? 'selected' : '' }}>
                                                {{ $p->name }} ({{ $p->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('partner_id')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="requested_amount" class="form-label">신청 금액 (만원) <span class="text-danger">*</span></label>
                                    <input type="number" name="requested_amount" id="requested_amount" class="form-control"
                                           value="{{ old('requested_amount') }}" min="1" step="0.01" required
                                           placeholder="0" onchange="calculateFinalAmount()">
                                    @error('requested_amount')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- 파트너 정보 미리보기 -->
                        <div id="partner-info" style="display: none;">
                            <div class="alert alert-info">
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong>파트너:</strong> <span id="preview-partner-name">-</span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>이메일:</strong> <span id="preview-partner-email">-</span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>지급 가능 커미션:</strong> <span id="preview-commission-count">0</span>건
                                    </div>
                                </div>
                            </div>
                        </div>
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
                                        <option value="bank_transfer" {{ old('payment_method', 'bank_transfer') == 'bank_transfer' ? 'selected' : '' }}>
                                            🏦 은행 이체
                                        </option>
                                        <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>
                                            💵 현금 지급
                                        </option>
                                        <option value="check" {{ old('payment_method') == 'check' ? 'selected' : '' }}>
                                            📄 수표 발행
                                        </option>
                                        <option value="digital_wallet" {{ old('payment_method') == 'digital_wallet' ? 'selected' : '' }}>
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
                                               value="{{ old('bank_name') }}" placeholder="예: 국민은행">
                                        @error('bank_name')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="account_number" class="form-label">계좌번호</label>
                                        <input type="text" name="account_number" id="account_number" class="form-control"
                                               value="{{ old('account_number') }}" placeholder="123-456-789012">
                                        @error('account_number')
                                            <div class="text-danger">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="account_holder" class="form-label">예금주명</label>
                                        <input type="text" name="account_holder" id="account_holder" class="form-control"
                                               value="{{ old('account_holder') }}" placeholder="홍길동">
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
                                           value="{{ old('fee_amount', 0) }}" min="0" step="0.01"
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
                                           value="{{ old('tax_amount', 0) }}" min="0" step="0.01"
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
                    </div>
                </div>

                <!-- 지급할 커미션 선택 -->
                <div class="card mb-4" id="commission-selection" style="display: none;">
                    <div class="card-header">
                        <h5 class="card-title mb-0">💵 지급할 커미션 선택</h5>
                    </div>
                    <div class="card-body">
                        <div id="available-commissions">
                            <!-- AJAX로 로드되는 커미션 목록 -->
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
                            <label for="notes" class="form-label">관리자 메모 (선택사항)</label>
                            <textarea name="notes" id="notes" class="form-control" rows="4"
                                      placeholder="지급에 대한 추가 메모를 입력하세요...">{{ old('notes') }}</textarea>
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
                                <span id="preview-requested" class="fw-bold">0만원</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between text-warning">
                                <span>수수료:</span>
                                <span id="preview-fee">0만원</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between text-danger">
                                <span>세금:</span>
                                <span id="preview-tax">0만원</span>
                            </div>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold">실 지급액:</span>
                                <span id="preview-final" class="fw-bold text-primary h5">0만원</span>
                            </div>
                        </div>

                        <div class="small text-muted">
                            <div>공제율: <span id="preview-deduction-rate">0%</span></div>
                        </div>
                    </div>
                </div>

                <!-- 지급 방법별 안내 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">ℹ️ 지급 방법별 안내</h5>
                    </div>
                    <div class="card-body">
                        <div id="payment-method-guide">
                            <div class="payment-guide" data-method="bank_transfer">
                                <h6>🏦 은행 이체</h6>
                                <ul class="small text-muted mb-0">
                                    <li>일반적인 계좌 이체 방식</li>
                                    <li>1~2 영업일 소요</li>
                                    <li>은행 수수료 발생 가능</li>
                                </ul>
                            </div>
                            <div class="payment-guide" data-method="cash" style="display: none;">
                                <h6>💵 현금 지급</h6>
                                <ul class="small text-muted mb-0">
                                    <li>직접 현금 전달</li>
                                    <li>소액 지급에 적합</li>
                                    <li>영수증 발급 필요</li>
                                </ul>
                            </div>
                            <div class="payment-guide" data-method="check" style="display: none;">
                                <h6>📄 수표 발행</h6>
                                <ul class="small text-muted mb-0">
                                    <li>고액 지급에 적합</li>
                                    <li>수표 발행비용 발생</li>
                                    <li>현금화까지 시간 소요</li>
                                </ul>
                            </div>
                            <div class="payment-guide" data-method="digital_wallet" style="display: none;">
                                <h6>📱 디지털지갑</h6>
                                <ul class="small text-muted mb-0">
                                    <li>빠른 송금 가능</li>
                                    <li>디지털지갑 수수료 적용</li>
                                    <li>계좌 연동 필요</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 도움말 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">💡 도움말</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled small">
                            <li class="mb-2">• 파트너를 선택하면 지급 가능한 커미션이 표시됩니다.</li>
                            <li class="mb-2">• 세금은 자동 계산 버튼을 사용하거나 직접 입력할 수 있습니다.</li>
                            <li class="mb-2">• 지급 방법에 따라 수수료가 다를 수 있습니다.</li>
                            <li class="mb-2">• 등록 후 승인 → 처리 → 완료 단계를 거칩니다.</li>
                        </ul>
                    </div>
                </div>

                <!-- 제출 버튼 -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fe fe-save"></i> 지급 신청 등록
                            </button>
                            <a href="{{ route('admin.partner.payments.index') }}" class="btn btn-secondary">
                                <i class="fe fe-x"></i> 취소
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// 파트너 정보 로드
function loadPartnerInfo() {
    const partnerId = document.getElementById('partner_id').value;
    const partnerInfo = document.getElementById('partner-info');
    const commissionSelection = document.getElementById('commission-selection');

    if (!partnerId) {
        partnerInfo.style.display = 'none';
        commissionSelection.style.display = 'none';
        return;
    }

    // 파트너 정보 표시
    const selectedOption = document.querySelector('#partner_id option:checked');
    const partnerText = selectedOption.text;
    const [name, email] = partnerText.split(' (');

    document.getElementById('preview-partner-name').textContent = name;
    document.getElementById('preview-partner-email').textContent = email.replace(')', '');
    partnerInfo.style.display = 'block';

    // 사용 가능한 커미션 로드 (AJAX)
    // 실제 구현에서는 서버에서 데이터를 가져와야 함
    document.getElementById('preview-commission-count').textContent = '로딩 중...';
    commissionSelection.style.display = 'block';

    // 예시: 임시 데이터
    setTimeout(() => {
        document.getElementById('preview-commission-count').textContent = '5';
        document.getElementById('available-commissions').innerHTML = `
            <div class="alert alert-info">
                <p class="mb-2"><strong>지급 가능한 커미션:</strong></p>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="commission_ids[]" value="1" id="commission1">
                    <label class="form-check-label" for="commission1">
                        추천 커미션 - 50만원 (2024-11-01)
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="commission_ids[]" value="2" id="commission2">
                    <label class="form-check-label" for="commission2">
                        매출 커미션 - 100만원 (2024-11-05)
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="commission_ids[]" value="3" id="commission3">
                    <label class="form-check-label" for="commission3">
                        팀 보너스 - 30만원 (2024-11-10)
                    </label>
                </div>
                <hr>
                <div class="d-flex justify-content-between">
                    <span>선택된 커미션 총액:</span>
                    <span class="fw-bold" id="selected-commission-total">0만원</span>
                </div>
            </div>
        `;

        // 커미션 선택 시 금액 자동 업데이트
        document.querySelectorAll('input[name="commission_ids[]"]').forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectedCommissionTotal);
        });
    }, 1000);
}

// 선택된 커미션 총액 계산
function updateSelectedCommissionTotal() {
    const checkboxes = document.querySelectorAll('input[name="commission_ids[]"]:checked');
    let total = 0;

    checkboxes.forEach(checkbox => {
        const label = document.querySelector(`label[for="${checkbox.id}"]`);
        const text = label.textContent;
        const amount = text.match(/(\d+)만원/);
        if (amount) {
            total += parseInt(amount[1]);
        }
    });

    document.getElementById('selected-commission-total').textContent = total.toLocaleString() + '만원';

    // 신청 금액 자동 설정
    if (total > 0) {
        document.getElementById('requested_amount').value = total;
        calculateFinalAmount();
    }
}

// 지급 방법 변경 시 계좌 필드 토글
function toggleAccountFields() {
    const paymentMethod = document.getElementById('payment_method').value;
    const bankFields = document.getElementById('bank-account-fields');
    const guides = document.querySelectorAll('.payment-guide');

    // 계좌 필드 표시/숨김
    if (paymentMethod === 'bank_transfer') {
        bankFields.style.display = 'block';
    } else {
        bankFields.style.display = 'none';
    }

    // 안내 메시지 변경
    guides.forEach(guide => guide.style.display = 'none');
    const currentGuide = document.querySelector(`.payment-guide[data-method="${paymentMethod}"]`);
    if (currentGuide) {
        currentGuide.style.display = 'block';
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

    // 공제율 계산
    const deductionRate = requestedAmount > 0 ? ((feeAmount + taxAmount) / requestedAmount * 100) : 0;
    document.getElementById('preview-deduction-rate').textContent = deductionRate.toFixed(1) + '%';
}

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    toggleAccountFields();
    calculateFinalAmount();

    // 이미 선택된 파트너가 있으면 정보 로드
    if (document.getElementById('partner_id').value) {
        loadPartnerInfo();
    }
});
</script>
@endsection