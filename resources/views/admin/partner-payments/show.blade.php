@extends('jiny-partner::layouts.admin.sidebar')

@section('title', '지급 내역 상세')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">💳 지급 내역 상세</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="/admin">관리자</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.partner.index') }}">파트너</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.partner.payments.index') }}">지급 관리</a></li>
                        <li class="breadcrumb-item active">상세</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- 지급 기본 정보 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">📋 지급 기본 정보</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">지급 코드</label>
                                <div class="h5 text-primary">{{ $payment->payment_code }}</div>
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
                                        @case('processing')
                                            <span class="badge bg-primary fs-6">🔄 처리중</span>
                                            @break
                                        @case('completed')
                                            <span class="badge bg-success fs-6">✅ 완료됨</span>
                                            @break
                                        @case('failed')
                                            <span class="badge bg-danger fs-6">❌ 실패</span>
                                            @break
                                        @case('cancelled')
                                            <span class="badge bg-secondary fs-6">🚫 취소됨</span>
                                            @break
                                    @endswitch
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">파트너 (신청 당시)</label>
                                <div>{{ $payment->partner_name }}</div>
                                <small class="text-muted">{{ $payment->partner_email }}</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">파트너 (현재)</label>
                                <div>{{ $payment->partner_name_current }}</div>
                                <small class="text-muted">{{ $payment->partner_email_current }}</small>
                                @if($payment->tier_level)
                                    <span class="badge bg-primary ms-2">{{ $payment->tier_level }}급</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 금액 정보 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">💰 금액 정보</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h4 class="text-primary mb-2">{{ number_format($payment->requested_amount, 0) }}만원</h4>
                                <p class="text-muted mb-0">신청 금액</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h4 class="text-warning mb-2">{{ number_format($payment->fee_amount, 0) }}만원</h4>
                                <p class="text-muted mb-0">수수료</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h4 class="text-danger mb-2">{{ number_format($payment->tax_amount, 0) }}만원</h4>
                                <p class="text-muted mb-0">세금</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-success bg-opacity-10 border border-success rounded">
                                <h4 class="text-success mb-2">{{ number_format($payment->final_amount, 0) }}만원</h4>
                                <p class="text-muted mb-0">실 지급액</p>
                            </div>
                        </div>
                    </div>

                    @if($payment->fee_amount > 0 || $payment->tax_amount > 0)
                    <div class="mt-3">
                        <div class="alert alert-info">
                            <div class="row">
                                <div class="col-md-8">
                                    <strong>계산 공식:</strong><br>
                                    실 지급액 = 신청 금액 - 수수료 - 세금<br>
                                    {{ number_format($payment->final_amount, 0) }}만원 = {{ number_format($payment->requested_amount, 0) }}만원 - {{ number_format($payment->fee_amount, 0) }}만원 - {{ number_format($payment->tax_amount, 0) }}만원
                                </div>
                                <div class="col-md-4 text-end">
                                    @php
                                        $deductionRate = ($payment->fee_amount + $payment->tax_amount) / $payment->requested_amount * 100;
                                    @endphp
                                    <div class="fs-14 text-muted">공제율: {{ number_format($deductionRate, 1) }}%</div>
                                </div>
                            </div>
                        </div>
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
                                <label class="form-label fw-bold">지급 방법</label>
                                <div>
                                    @switch($payment->payment_method)
                                        @case('bank_transfer')
                                            <span class="badge bg-primary fs-6">🏦 은행이체</span>
                                            @break
                                        @case('cash')
                                            <span class="badge bg-success fs-6">💵 현금 지급</span>
                                            @break
                                        @case('check')
                                            <span class="badge bg-warning fs-6">📄 수표 발행</span>
                                            @break
                                        @case('digital_wallet')
                                            <span class="badge bg-info fs-6">📱 디지털지갑</span>
                                            @break
                                    @endswitch
                                </div>
                            </div>
                        </div>

                        @if($payment->payment_method === 'bank_transfer' && ($payment->bank_name || $payment->account_number))
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">은행 정보</label>
                                <div>
                                    @if($payment->bank_name)
                                        <div><strong>은행:</strong> {{ $payment->bank_name }}</div>
                                    @endif
                                    @if($payment->account_number)
                                        <div><strong>계좌:</strong> {{ $payment->account_number }}</div>
                                    @endif
                                    @if($payment->account_holder)
                                        <div><strong>예금주:</strong> {{ $payment->account_holder }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- 진행 상황 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">📅 진행 상황</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <!-- 신청 -->
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">지급 신청</h6>
                                <div class="text-muted">{{ date('Y년 m월 d일 H:i', strtotime($payment->requested_at)) }}</div>
                            </div>
                        </div>

                        <!-- 승인 -->
                        @if($payment->approved_at)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-info"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">승인 처리</h6>
                                <div class="text-muted">{{ date('Y년 m월 d일 H:i', strtotime($payment->approved_at)) }}</div>
                                @if($payment->approver_name)
                                    <small class="text-muted">담당자: {{ $payment->approver_name }}</small>
                                @endif
                                @if($payment->approval_notes)
                                    <div class="mt-1"><small class="text-info">메모: {{ $payment->approval_notes }}</small></div>
                                @endif
                            </div>
                        </div>
                        @endif

                        <!-- 처리 시작 -->
                        @if($payment->processed_at)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-warning"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">처리 시작</h6>
                                <div class="text-muted">{{ date('Y년 m월 d일 H:i', strtotime($payment->processed_at)) }}</div>
                                @if($payment->processor_name)
                                    <small class="text-muted">담당자: {{ $payment->processor_name }}</small>
                                @endif
                                @if($payment->processing_notes)
                                    <div class="mt-1"><small class="text-warning">메모: {{ $payment->processing_notes }}</small></div>
                                @endif
                            </div>
                        </div>
                        @endif

                        <!-- 완료/실패/취소 -->
                        @if($payment->completed_at)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">지급 완료</h6>
                                <div class="text-muted">{{ date('Y년 m월 d일 H:i', strtotime($payment->completed_at)) }}</div>
                                @if($payment->external_transaction_id)
                                    <div class="mt-1"><small class="text-success">거래 ID: {{ $payment->external_transaction_id }}</small></div>
                                @endif
                            </div>
                        </div>
                        @elseif($payment->cancelled_at)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-danger"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">지급 취소</h6>
                                <div class="text-muted">{{ date('Y년 m월 d일 H:i', strtotime($payment->cancelled_at)) }}</div>
                                @if($payment->failure_reason)
                                    <div class="mt-1"><small class="text-danger">사유: {{ $payment->failure_reason }}</small></div>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- 포함된 커미션 내역 -->
            @if(count($commissionItems) > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">💵 포함된 커미션 내역</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>커미션 유형</th>
                                    <th>참조 정보</th>
                                    <th>발생일</th>
                                    <th class="text-end">원래 금액</th>
                                    <th class="text-end">포함 금액</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($commissionItems as $item)
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark">{{ $item->commission_type }}</span>
                                    </td>
                                    <td>
                                        <div class="small">
                                            @if($item->reference_type)
                                                <div>유형: {{ $item->reference_type }}</div>
                                            @endif
                                            @if($item->reference_id)
                                                <div>ID: {{ $item->reference_id }}</div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ date('Y-m-d', strtotime($item->earned_date)) }}</td>
                                    <td class="text-end">{{ number_format($item->commission_amount, 0) }}만원</td>
                                    <td class="text-end text-primary fw-bold">{{ number_format($item->included_amount, 0) }}만원</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4" class="text-end">총 포함 금액:</th>
                                    <th class="text-end text-primary">{{ number_format($commissionItems->sum('included_amount'), 0) }}만원</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- 배치 정보 (해당시) -->
            @if($payment->batch_id && $batchPayments && count($batchPayments) > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">📦 배치 지급 정보</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong>배치 ID:</strong> {{ $payment->batch_id }}<br>
                        <strong>동시 처리 지급:</strong> {{ count($batchPayments) + 1 }}건 (현재 지급 포함)
                    </div>

                    <h6>동일 배치의 다른 지급들:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>지급코드</th>
                                    <th>파트너</th>
                                    <th class="text-end">금액</th>
                                    <th>상태</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($batchPayments as $batchPayment)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.partner.payments.show', $batchPayment->id) }}">
                                            {{ $batchPayment->payment_code }}
                                        </a>
                                    </td>
                                    <td>{{ $batchPayment->partner_name }}</td>
                                    <td class="text-end">{{ number_format($batchPayment->final_amount, 0) }}만원</td>
                                    <td>
                                        @switch($batchPayment->status)
                                            @case('requested')
                                                <span class="badge bg-warning">신청</span>
                                                @break
                                            @case('approved')
                                                <span class="badge bg-info">승인</span>
                                                @break
                                            @case('processing')
                                                <span class="badge bg-primary">처리중</span>
                                                @break
                                            @case('completed')
                                                <span class="badge bg-success">완료</span>
                                                @break
                                            @case('failed')
                                                <span class="badge bg-danger">실패</span>
                                                @break
                                            @case('cancelled')
                                                <span class="badge bg-secondary">취소</span>
                                                @break
                                        @endswitch
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- 메타데이터 -->
            @if(count($payment->metadata) > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">📊 추가 정보</h5>
                </div>
                <div class="card-body">
                    <pre class="bg-light p-3 rounded small"><code>{{ json_encode($payment->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                </div>
            </div>
            @endif

            <!-- 외부 시스템 응답 -->
            @if(count($payment->external_response) > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">🔗 외부 시스템 응답</h5>
                </div>
                <div class="card-body">
                    <pre class="bg-light p-3 rounded small"><code>{{ json_encode($payment->external_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            <!-- 액션 버튼 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">⚡ 액션</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <!-- 상태별 액션 버튼 -->
                        @if($payment->status === 'requested')
                            <button type="button" class="btn btn-success" onclick="approvePayment({{ $payment->id }})">
                                <i class="fe fe-check"></i> 지급 승인
                            </button>
                        @elseif($payment->status === 'approved')
                            <button type="button" class="btn btn-info" onclick="processPayment({{ $payment->id }})">
                                <i class="fe fe-play"></i> 처리 시작
                            </button>
                        @elseif($payment->status === 'processing')
                            <button type="button" class="btn btn-success" onclick="completePayment({{ $payment->id }})">
                                <i class="fe fe-check-circle"></i> 완료 처리
                            </button>
                        @endif

                        @if(in_array($payment->status, ['requested', 'approved']))
                            <a href="{{ route('admin.partner.payments.edit', $payment->id) }}" class="btn btn-warning">
                                <i class="fe fe-edit"></i> 지급 정보 수정
                            </a>
                        @endif

                        @if(!in_array($payment->status, ['completed', 'cancelled']))
                            <button type="button" class="btn btn-danger" onclick="cancelPayment({{ $payment->id }})">
                                <i class="fe fe-x"></i> 지급 취소
                            </button>
                        @endif

                        <a href="{{ route('admin.partner.payments.index') }}" class="btn btn-secondary">
                            <i class="fe fe-list"></i> 목록으로
                        </a>

                        @if($payment->status === 'requested')
                            <form method="POST" action="{{ route('admin.partner.payments.destroy', $payment->id) }}"
                                  onsubmit="return confirm('정말로 삭제하시겠습니까?')" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger w-100">
                                    <i class="fe fe-trash"></i> 지급 삭제
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <!-- 상태 정보 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">ℹ️ 상태 정보</h5>
                </div>
                <div class="card-body">
                    <div class="small">
                        <div class="mb-2">
                            <strong>지급 ID:</strong> {{ $payment->id }}
                        </div>
                        <div class="mb-2">
                            <strong>등록일:</strong> {{ date('Y-m-d H:i', strtotime($payment->created_at)) }}
                        </div>
                        <div class="mb-2">
                            <strong>수정일:</strong> {{ date('Y-m-d H:i', strtotime($payment->updated_at)) }}
                        </div>
                        @if($payment->is_bulk_payment)
                            <div class="mb-2">
                                <span class="badge bg-info">배치 지급</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- 관리자 메모 -->
            @if($payment->notes)
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">📝 관리자 메모</h5>
                </div>
                <div class="card-body">
                    <div class="p-3 bg-light rounded">
                        {!! nl2br(e($payment->notes)) !!}
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- 모달들 (index.blade.php에서와 동일) -->
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