@extends('jiny-partner::layouts.admin.sidebar')

@section('title', '파트너 커미션 상세')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<style>
/* 기본 글자색을 검은색으로 설정 */
.container-fluid {
    color: #333333;
}

.card-body {
    color: #333333;
}

.card-body h5, .card-body h6, .card-body p, .card-body div {
    color: #333333 !important;
}

/* 라벨과 제목 글자색 강제 설정 */
label, .font-weight-bold, .form-label {
    color: #333333 !important;
}

/* 브레드크럼과 제목 */
.breadcrumb, .h3 {
    color: #333333;
}

/* text-muted 클래스를 가독성 있는 색상으로 변경 */
.text-muted {
    color: #666666 !important;
}

/* small 텍스트 색상 */
.small {
    color: #666666 !important;
}

/* 테이블 텍스트 색상 강제 설정 */
.table td, .table th {
    color: #333333 !important;
}

.table tbody td {
    color: #333333 !important;
}

/* 테이블 내 배지 텍스트 색상은 유지 */
.table .badge {
    color: white !important;
}

/* 커미션 통계 카드 */
.commission-stat-card {
    border: 1px solid #e3e6f0;
    border-radius: 0.5rem;
    padding: 1.5rem;
    text-align: center;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fc 100%);
}

.commission-stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.commission-stat-label {
    color: #666666;
    font-size: 0.875rem;
}

/* 커미션 상세 정보 카드 */
.commission-detail-card {
    background: #f8f9fc;
    border: 1px solid #e3e6f0;
    border-radius: 0.5rem;
    padding: 1rem;
}

/* 계산 분석 정보 */
.calculation-breakdown {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 0.5rem;
    padding: 1rem;
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- 페이지 헤더 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-dark">파트너 커미션 상세</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">관리자</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.partner.dashboard') }}">파트너 관리</a></li>
                            <li class="breadcrumb-item"><a href="#">네트워크 관리</a></li>
                            <li class="breadcrumb-item active">커미션 상세</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="javascript:history.back()" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> 돌아가기
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(isset($commission))
    <!-- 커미션 기본 정보 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">커미션 기본 정보</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="commission-detail-card mb-3">
                                <h6 class="text-dark">수령 파트너</h6>
                                <div class="mb-2">
                                    <strong class="text-dark">{{ $commission->partner->name ?? 'Unknown' }}</strong>
                                </div>
                                <small class="text-muted">{{ $commission->partner->email ?? 'No email' }}</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="commission-detail-card mb-3">
                                <h6 class="text-dark">매출 파트너</h6>
                                <div class="mb-2">
                                    <strong class="text-dark">{{ $commission->sourcePartner->name ?? 'Unknown' }}</strong>
                                </div>
                                <small class="text-muted">{{ $commission->sourcePartner->email ?? 'No email' }}</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="commission-stat-card">
                                <div class="commission-stat-value text-success">
                                    {{ number_format($commission->commission_amount) }}원
                                </div>
                                <div class="commission-stat-label">커미션 금액</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="commission-stat-card">
                                <div class="commission-stat-value text-primary">
                                    {{ number_format($commission->commission_rate, 2) }}%
                                </div>
                                <div class="commission-stat-label">커미션율</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="commission-stat-card">
                                <div class="commission-stat-value text-warning">
                                    {{ number_format($commission->tax_amount) }}원
                                </div>
                                <div class="commission-stat-label">세금</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="commission-stat-card">
                                <div class="commission-stat-value text-info">
                                    {{ number_format($commission->net_amount) }}원
                                </div>
                                <div class="commission-stat-label">세후 금액</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 커미션 상세 정보 -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <!-- 매출 정보 -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">관련 매출 정보</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="font-weight-bold text-dark">주문 ID:</label>
                                <span class="text-dark">{{ $commission->order_id }}</span>
                            </div>
                            <div class="mb-3">
                                <label class="font-weight-bold text-dark">원본 금액:</label>
                                <span class="text-success">{{ number_format($commission->original_amount) }}원</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="font-weight-bold text-dark">커미션 타입:</label>
                                @if($commission->commission_type === 'direct_sales')
                                    <span class="badge badge-primary">직접 매출</span>
                                @else
                                    <span class="badge badge-secondary">간접 추천</span>
                                @endif
                            </div>
                            <div class="mb-3">
                                <label class="font-weight-bold text-dark">레벨 차이:</label>
                                @if($commission->level_difference > 0)
                                    <span class="text-dark">{{ $commission->level_difference }}단계 상위</span>
                                @else
                                    <span class="text-dark">직접 매출</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 커미션 계산 분석 -->
            @if($commission->calculation_details)
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">커미션 계산 분석</h6>
                </div>
                <div class="card-body">
                    <div class="calculation-breakdown">
                        @php
                            $details = json_decode($commission->calculation_details, true);
                        @endphp

                        @if(is_array($details))
                            <div class="row">
                                @foreach($details as $key => $value)
                                    <div class="col-md-6 mb-2">
                                        <strong class="text-dark">{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong>
                                        <span class="text-dark">
                                            @if(is_array($value))
                                                @if(count($value) > 0)
                                                    <ul class="mb-0 pl-3">
                                                        @foreach($value as $subKey => $subValue)
                                                            <li>
                                                                @if(is_string($subKey))
                                                                    <strong>{{ ucfirst(str_replace('_', ' ', $subKey)) }}:</strong>
                                                                @endif
                                                                @if(is_array($subValue))
                                                                    {{ json_encode($subValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
                                                                @elseif(is_numeric($subValue))
                                                                    {{ is_float($subValue) ? number_format($subValue, 2) : $subValue }}
                                                                    @if(str_contains($subKey, 'rate') || str_contains($subKey, 'percentage'))%@endif
                                                                @else
                                                                    {{ $subValue }}
                                                                @endif
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <em class="text-muted">빈 배열</em>
                                                @endif
                                            @elseif(is_numeric($value))
                                                {{ is_float($value) ? number_format($value, 2) : $value }}
                                                @if(str_contains($key, 'rate') || str_contains($key, 'percentage'))%@endif
                                            @elseif(is_bool($value))
                                                {{ $value ? '예' : '아니오' }}
                                            @elseif(is_null($value))
                                                <em class="text-muted">없음</em>
                                            @else
                                                {{ $value }}
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-dark">{{ $commission->calculation_details }}</p>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            <!-- 상태 정보 -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">상태 정보</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="font-weight-bold text-dark">현재 상태:</label>
                        @switch($commission->status)
                            @case('calculated')
                                <span class="badge badge-info badge-lg">계산완료</span>
                                @break
                            @case('paid')
                                <span class="badge badge-success badge-lg">지급완료</span>
                                @break
                            @case('cancelled')
                                <span class="badge badge-danger badge-lg">취소됨</span>
                                @break
                            @default
                                <span class="badge badge-secondary badge-lg">{{ $commission->status }}</span>
                        @endswitch
                    </div>

                    <div class="mb-3">
                        <label class="font-weight-bold text-dark">발생일:</label>
                        <span class="text-dark">{{ $commission->earned_at ? $commission->earned_at->format('Y-m-d H:i') : '미설정' }}</span>
                    </div>

                    @if($commission->paid_at)
                        <div class="mb-3">
                            <label class="font-weight-bold text-dark">지급일:</label>
                            <span class="text-success">{{ $commission->paid_at->format('Y-m-d H:i') }}</span>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="font-weight-bold text-dark">등록일:</label>
                        <span class="text-dark">{{ $commission->created_at->format('Y-m-d H:i') }}</span>
                    </div>
                </div>
            </div>

            <!-- 메모 -->
            @if($commission->notes)
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">메모</h6>
                </div>
                <div class="card-body">
                    <p class="text-dark mb-0">{{ $commission->notes }}</p>
                </div>
            </div>
            @endif

            <!-- 빠른 작업 -->
            @if($commission->status === 'calculated')
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">빠른 작업</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>자동 반영:</strong> 이 커미션은 파트너의 잔액에 자동으로 누적됩니다.
                    </div>
                    <button type="button" class="btn btn-warning btn-sm btn-block mb-2" onclick="editCommission()">
                        <i class="fas fa-edit"></i> 수정
                    </button>
                    <button type="button" class="btn btn-danger btn-sm btn-block" onclick="cancelCommission()">
                        <i class="fas fa-ban"></i> 취소
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>

    @else
    <!-- 커미션 정보가 없는 경우 -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-body text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h5 class="text-dark">커미션 정보를 찾을 수 없습니다</h5>
                    <p class="text-muted">요청하신 커미션 정보가 존재하지 않습니다.</p>
                    <a href="javascript:history.back()" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> 돌아가기
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>

@push('scripts')
<script>

function editCommission() {
    // 모달이나 인라인 편집 폼을 여는 것이 좋지만, 간단히 prompt 사용
    const currentAmount = {{ $commission->commission_amount ?? 0 }};
    const currentTax = {{ $commission->tax_amount ?? 0 }};
    const currentNotes = `{{ $commission->notes ?? '' }}`;

    const newAmount = prompt('커미션 금액을 입력하세요:', currentAmount);
    if (newAmount === null) return;

    const newTax = prompt('세금 금액을 입력하세요:', currentTax);
    if (newTax === null) return;

    const newNotes = prompt('메모를 입력하세요:', currentNotes);
    if (newNotes === null) return;

    fetch(`{{ route('admin.partner.network.commission.update', $commission->id ?? 0) }}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            commission_amount: parseFloat(newAmount),
            tax_amount: parseFloat(newTax),
            notes: newNotes
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('오류: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('처리 중 오류가 발생했습니다.');
    });
}

function cancelCommission() {
    if (confirm('이 커미션을 취소하시겠습니까? 이 작업은 되돌릴 수 없습니다.')) {
        const reason = prompt('취소 사유를 입력하세요:');
        if (reason === null || reason.trim() === '') {
            alert('취소 사유를 입력해주세요.');
            return;
        }

        fetch(`{{ route('admin.partner.network.commission.cancel', $commission->id ?? 0) }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                reason: reason
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('오류: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('처리 중 오류가 발생했습니다.');
        });
    }
}
</script>
@endpush

@endsection