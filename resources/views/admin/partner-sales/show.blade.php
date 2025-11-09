@extends('jiny-partner::layouts.admin.sidebar')

@section('content')
<div class="container-fluid">
    <!-- 페이지 헤더 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">{{ $title }}</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">관리자</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.partner.dashboard') }}">파트너 관리</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.' . $routePrefix . '.index') }}">매출 관리</a></li>
                            <li class="breadcrumb-item active">{{ $sales->title }}</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('admin.' . $routePrefix . '.edit', $sales->id) }}" class="btn btn-primary">
                        <i class="fas fa-edit"></i> 수정
                    </a>
                    <a href="{{ route('admin.' . $routePrefix . '.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> 목록으로
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- 메인 정보 -->
        <div class="col-lg-8">
            <!-- 처리 상태 정보 -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-tasks"></i> 처리 상태
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <!-- 처리 단계 프로그레스 -->
                            <div class="processing-steps mb-4">
                                <div class="step-item {{ $sales->created_at ? 'completed' : 'pending' }}">
                                    <div class="step-marker">
                                        <i class="fas fa-plus-circle"></i>
                                    </div>
                                    <div class="step-content">
                                        <div class="step-title">등록</div>
                                        <div class="step-time">{{ $sales->created_at ? $sales->created_at->format('Y-m-d H:i') : '' }}</div>
                                    </div>
                                </div>

                                <div class="step-item {{ $sales->is_approved ? 'completed' : ($sales->status === 'pending' ? 'current' : 'pending') }}">
                                    <div class="step-marker">
                                        <i class="fas fa-thumbs-up"></i>
                                    </div>
                                    <div class="step-content">
                                        <div class="step-title">승인</div>
                                        <div class="step-time">{{ $sales->approved_at ? $sales->approved_at->format('Y-m-d H:i') : '대기중' }}</div>
                                    </div>
                                </div>

                                <div class="step-item {{ $sales->status === 'confirmed' ? 'completed' : ($sales->is_approved ? 'current' : 'pending') }}">
                                    <div class="step-marker">
                                        <i class="fas fa-handshake"></i>
                                    </div>
                                    <div class="step-content">
                                        <div class="step-title">확정</div>
                                        <div class="step-time">{{ $sales->confirmed_at ? $sales->confirmed_at->format('Y-m-d H:i') : '대기중' }}</div>
                                    </div>
                                </div>

                                <div class="step-item {{ $sales->commission_calculated ? 'completed' : ($sales->status === 'confirmed' ? 'current' : 'pending') }}">
                                    <div class="step-marker">
                                        <i class="fas fa-calculator"></i>
                                    </div>
                                    <div class="step-content">
                                        <div class="step-title">커미션 계산</div>
                                        <div class="step-time">{{ $sales->commission_calculated_at ? $sales->commission_calculated_at->format('Y-m-d H:i') : '대기중' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <!-- 현재 상태 -->
                            <div class="current-status text-center p-4" style="background: #f8f9fc; border: 1px solid #e3e6f0; border-radius: 10px; min-height: 200px;">
                                <div class="status-icon mb-3">
                                    @switch($sales->status)
                                        @case('pending')
                                            <i class="fas fa-clock fa-4x text-warning"></i>
                                            @break
                                        @case('confirmed')
                                            <i class="fas fa-check-circle fa-4x text-success"></i>
                                            @break
                                        @case('cancelled')
                                            <i class="fas fa-times-circle fa-4x text-danger"></i>
                                            @break
                                        @case('refunded')
                                            <i class="fas fa-undo fa-4x text-secondary"></i>
                                            @break
                                        @default
                                            <i class="fas fa-question-circle fa-4x text-muted"></i>
                                    @endswitch
                                </div>
                                <div class="status-text">
                                    <h5 class="mb-3">현재 상태</h5>
                                    @switch($sales->status)
                                        @case('pending')
                                            <div class="badge badge-warning badge-lg mb-2">
                                                {{ $sales->is_approved ? '승인완료 (확정대기)' : '승인대기' }}
                                            </div>
                                            <div class="small text-muted">
                                                {{ $sales->is_approved ? '관리자 확정을 기다리고 있습니다' : '관리자 승인을 기다리고 있습니다' }}
                                            </div>
                                            @break
                                        @case('confirmed')
                                            <div class="badge badge-success badge-lg mb-2">확정완료</div>
                                            <div class="small text-success mb-2">매출이 확정되었습니다</div>
                                            @if($sales->commission_calculated)
                                                <div class="mt-2">
                                                    <i class="fas fa-check text-success"></i>
                                                    <small class="text-success">커미션 계산완료</small>
                                                </div>
                                            @else
                                                <div class="mt-2">
                                                    <i class="fas fa-hourglass-half text-warning"></i>
                                                    <small class="text-warning">커미션 계산 대기중</small>
                                                </div>
                                            @endif
                                            @break
                                        @case('cancelled')
                                            <div class="badge badge-danger badge-lg mb-2">취소됨</div>
                                            <div class="small text-muted">
                                                매출이 취소되었습니다
                                                @if($sales->status_reason)
                                                    <br><small>"{{ $sales->status_reason }}"</small>
                                                @endif
                                            </div>
                                            @break
                                        @case('refunded')
                                            <div class="badge badge-secondary badge-lg mb-2">환불됨</div>
                                            <div class="small text-muted">매출이 환불되었습니다</div>
                                            @break
                                        @default
                                            <div class="badge badge-secondary badge-lg mb-2">알 수 없음</div>
                                            <div class="small text-muted">상태: {{ $sales->status }}</div>
                                    @endswitch
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 매출 기본 정보 -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">매출 정보</h6>
                    <div>
                        @switch($sales->status)
                            @case('pending')
                                <span class="badge badge-warning badge-lg">대기중</span>
                                @break
                            @case('confirmed')
                                <span class="badge badge-success badge-lg">확정</span>
                                @break
                            @case('cancelled')
                                <span class="badge badge-danger badge-lg">취소</span>
                                @break
                            @case('refunded')
                                <span class="badge badge-secondary badge-lg">환불</span>
                                @break
                        @endswitch
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="font-weight-bold mb-3">{{ $sales->title }}</h5>

                            <div class="mb-3">
                                <label class="font-weight-bold">주문번호:</label>
                                <span class="text-primary">{{ $sales->order_number }}</span>
                            </div>

                            <div class="mb-3">
                                <label class="font-weight-bold">매출 금액:</label>
                                <span class="h4 text-success">{{ number_format($sales->amount) }}원</span>
                                <small class="text-muted">({{ $sales->currency }})</small>
                            </div>

                            <div class="mb-3">
                                <label class="font-weight-bold">매출일:</label>
                                {{ $sales->sales_date ? $sales->sales_date->format('Y년 m월 d일') : '미설정' }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            @if($sales->category)
                                <div class="mb-3">
                                    <label class="font-weight-bold">카테고리:</label>
                                    <span class="badge badge-info">{{ $sales->category }}</span>
                                </div>
                            @endif

                            @if($sales->product_type)
                                <div class="mb-3">
                                    <label class="font-weight-bold">제품 타입:</label>
                                    <span class="badge badge-secondary">{{ $sales->product_type }}</span>
                                </div>
                            @endif

                            @if($sales->sales_channel)
                                <div class="mb-3">
                                    <label class="font-weight-bold">판매 채널:</label>
                                    <span class="badge badge-light">{{ $sales->sales_channel }}</span>
                                </div>
                            @endif

                            <div class="mb-3">
                                <label class="font-weight-bold">등록일:</label>
                                {{ $sales->created_at->format('Y-m-d H:i') }}
                            </div>
                        </div>
                    </div>

                    @if($sales->description)
                        <div class="mt-3">
                            <label class="font-weight-bold">매출 설명:</label>
                            <p class="text-muted">{{ $sales->description }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- 파트너 정보 -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">파트너 정보</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="font-weight-bold">파트너명:</label>
                                <a href="{{ route('admin.partner.users.show', $sales->partner_id) }}" class="text-primary">
                                    {{ $sales->partner_name }}
                                </a>
                            </div>
                            <div class="mb-3">
                                <label class="font-weight-bold">이메일:</label>
                                {{ $sales->partner_email }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            @if($sales->partner && $sales->partner->tier)
                                <div class="mb-3">
                                    <label class="font-weight-bold">등급:</label>
                                    <span class="badge badge-info">{{ $sales->partner->tier->tier_name }}</span>
                                </div>
                            @endif
                            @if($sales->partner && $sales->partner->type)
                                <div class="mb-3">
                                    <label class="font-weight-bold">타입:</label>
                                    <span class="badge badge-secondary">{{ $sales->partner->type->type_name }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- 커미션 분배 정보 -->
            @if($sales->commission_calculated && $commissions->count() > 0)
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">커미션 분배 내역</h6>
                    </div>
                    <div class="card-body">
                        <!-- 커미션 통계 -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h4 text-success">{{ number_format($commissionStats['total_commission']) }}원</div>
                                    <small class="text-muted">총 커미션</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h4 text-info">{{ $commissionStats['recipients_count'] }}명</div>
                                    <small class="text-muted">수령자</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h4 text-primary">{{ number_format($commissionStats['direct_commission']) }}원</div>
                                    <small class="text-muted">직접 커미션</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h4 text-warning">{{ number_format($commissionStats['indirect_commission']) }}원</div>
                                    <small class="text-muted">간접 커미션</small>
                                </div>
                            </div>
                        </div>

                        <!-- 계층 구조 시각화 -->
                        <div class="mb-4">
                            <h6 class="font-weight-bold">계층 구조 및 커미션 분배</h6>
                            <div class="commission-hierarchy">
                                @foreach($hierarchyData as $item)
                                    <div class="hierarchy-item d-flex align-items-center mb-3 p-3
                                         {{ $item['is_source'] ? 'bg-light border-left border-primary' : 'bg-white border-left border-secondary' }}">

                                        <!-- 레벨 표시 -->
                                        <div class="level-indicator mr-3">
                                            @if($item['level'] > 0)
                                                <span class="badge badge-secondary">{{ $item['level'] }}단계 상위</span>
                                            @else
                                                <span class="badge badge-primary">매출 파트너</span>
                                            @endif
                                        </div>

                                        <!-- 파트너 정보 -->
                                        <div class="partner-info flex-grow-1">
                                            <div class="font-weight-bold">{{ $item['partner']->name }}</div>
                                            <small class="text-muted">{{ $item['partner']->email }}</small>
                                            @if($item['partner']->tier)
                                                <span class="badge badge-info badge-sm ml-2">{{ $item['partner']->tier->tier_name }}</span>
                                            @endif
                                        </div>

                                        <!-- 커미션 정보 -->
                                        @if($item['commission'])
                                            <div class="commission-info text-right">
                                                <div class="font-weight-bold text-success">
                                                    {{ number_format($item['commission']->commission_amount) }}원
                                                </div>
                                                <small class="text-muted">
                                                    {{ number_format($item['commission']->commission_rate, 2) }}%
                                                    @if($item['commission']->children_count > 1)
                                                        ({{ $item['commission']->children_count }}명 분배)
                                                    @endif
                                                </small>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- 상세 커미션 테이블 -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="thead-light">
                                    <tr>
                                        <th>파트너</th>
                                        <th>유형</th>
                                        <th>레벨</th>
                                        <th>커미션율</th>
                                        <th>커미션 금액</th>
                                        <th>세후 금액</th>
                                        <th>상태</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($commissions as $commission)
                                        <tr>
                                            <td>
                                                <div>{{ $commission->partner->name }}</div>
                                                <small class="text-muted">{{ $commission->partner->email }}</small>
                                            </td>
                                            <td>
                                                @if($commission->commission_type === 'direct_sales')
                                                    <span class="badge badge-primary">직접</span>
                                                @else
                                                    <span class="badge badge-secondary">간접</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($commission->level_difference > 0)
                                                    {{ $commission->level_difference }}단계 상위
                                                @else
                                                    매출 파트너
                                                @endif
                                            </td>
                                            <td>{{ number_format($commission->commission_rate, 2) }}%</td>
                                            <td class="text-right font-weight-bold">{{ number_format($commission->commission_amount) }}원</td>
                                            <td class="text-right">{{ number_format($commission->net_amount) }}원</td>
                                            <td>
                                                @switch($commission->status)
                                                    @case('calculated')
                                                        <span class="badge badge-info">계산완료</span>
                                                        @break
                                                    @case('paid')
                                                        <span class="badge badge-success">지급완료</span>
                                                        @break
                                                    @case('cancelled')
                                                        <span class="badge badge-danger">취소</span>
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
            @elseif($sales->status === 'confirmed')
                <div class="card shadow mb-4">
                    <div class="card-body text-center">
                        <i class="fas fa-calculator fa-3x text-gray-300 mb-3"></i>
                        <p class="text-muted mb-3">아직 커미션이 계산되지 않았습니다.</p>
                        <button type="button" class="btn btn-primary" onclick="calculateCommission()">
                            <i class="fas fa-play"></i> 커미션 계산하기
                        </button>
                    </div>
                </div>
            @endif

            <!-- 상태 변경 이력 -->
            @if($statusHistory && $statusHistory->count() > 0)
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">상태 변경 이력</h6>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            @foreach($statusHistory as $history)
                                <div class="timeline-item d-flex mb-3">
                                    <div class="timeline-marker">
                                        @switch($history['status'])
                                            @case('created')
                                                <i class="fas fa-plus-circle text-info"></i>
                                                @break
                                            @case('approved')
                                                <i class="fas fa-check-circle text-success"></i>
                                                @break
                                            @case('confirmed')
                                                <i class="fas fa-handshake text-primary"></i>
                                                @break
                                            @case('commission_calculated')
                                                <i class="fas fa-calculator text-warning"></i>
                                                @break
                                            @case('cancelled')
                                                <i class="fas fa-times-circle text-danger"></i>
                                                @break
                                            @default
                                                <i class="fas fa-circle text-secondary"></i>
                                        @endswitch
                                    </div>
                                    <div class="timeline-content ml-3">
                                        <div class="font-weight-bold">{{ $history['status_korean'] }}</div>
                                        <div class="text-muted">{{ $history['notes'] }}</div>
                                        <small class="text-muted">
                                            {{ $history['date']->format('Y-m-d H:i') }}
                                            @if($history['user'])
                                                by {{ $history['user']->name }}
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- 사이드바 -->
        <div class="col-lg-4">
            <!-- 빠른 작업 -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">빠른 작업</h6>
                </div>
                <div class="card-body">
                    @if($sales->status === 'pending' && !$sales->is_approved)
                        <button type="button" class="btn btn-primary btn-sm btn-block mb-2" onclick="approveSales()">
                            <i class="fas fa-thumbs-up"></i> 매출 승인
                        </button>
                    @endif

                    @if($sales->status === 'pending' && $sales->is_approved)
                        <button type="button" class="btn btn-success btn-sm btn-block mb-2" onclick="confirmSales()">
                            <i class="fas fa-check"></i> 매출 확정
                        </button>
                    @endif

                    @if($sales->status === 'confirmed' && !$sales->commission_calculated)
                        <button type="button" class="btn btn-primary btn-sm btn-block mb-2" onclick="calculateCommission()">
                            <i class="fas fa-calculator"></i> 커미션 계산
                        </button>
                    @endif

                    @if($sales->status !== 'cancelled')
                        <button type="button" class="btn btn-warning btn-sm btn-block mb-2" onclick="cancelSales()">
                            <i class="fas fa-ban"></i> 매출 취소
                        </button>
                    @endif

                    <a href="{{ route('admin.' . $routePrefix . '.edit', $sales->id) }}" class="btn btn-outline-primary btn-sm btn-block mb-2">
                        <i class="fas fa-edit"></i> 수정
                    </a>

                    <button type="button" class="btn btn-outline-danger btn-sm btn-block" onclick="deleteSales()">
                        <i class="fas fa-trash"></i> 삭제
                    </button>
                </div>
            </div>

            <!-- 관련 매출 -->
            @if($relatedSales && $relatedSales->count() > 0)
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">관련 매출</h6>
                    </div>
                    <div class="card-body">
                        @foreach($relatedSales as $related)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <a href="{{ route('admin.' . $routePrefix . '.show', $related->id) }}" class="text-primary">
                                        {{ \Str::limit($related->title, 25) }}
                                    </a>
                                    <br>
                                    <small class="text-muted">{{ $related->sales_date ? $related->sales_date->format('Y-m-d') : '' }}</small>
                                </div>
                                <div class="text-right">
                                    <div class="font-weight-bold">{{ number_format($related->amount) }}원</div>
                                    @switch($related->status)
                                        @case('confirmed')
                                            <span class="badge badge-success badge-sm">확정</span>
                                            @break
                                        @case('pending')
                                            <span class="badge badge-warning badge-sm">대기</span>
                                            @break
                                        @case('cancelled')
                                            <span class="badge badge-danger badge-sm">취소</span>
                                            @break
                                    @endswitch
                                </div>
                            </div>
                        @endforeach
                        <a href="{{ route('admin.' . $routePrefix . '.index', ['partner_id' => $sales->partner_id]) }}" class="btn btn-outline-primary btn-sm btn-block mt-2">
                            전체 보기
                        </a>
                    </div>
                </div>
            @endif

            <!-- 메모 -->
            @if($sales->admin_notes)
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">관리자 메모</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-0">{{ $sales->admin_notes }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
// CSRF token for AJAX requests
const csrfToken = '{{ csrf_token() }}';

// Show loading state for buttons
function setButtonLoading(button, loading = true) {
    if (loading) {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 처리중...';
    } else {
        button.disabled = false;
        // Restore original content - you may want to store this differently
        button.innerHTML = button.getAttribute('data-original-text') || button.innerHTML;
    }
}

// Show toast notification
function showToast(message, type = 'success') {
    // Simple alert for now - you can implement a proper toast system
    if (type === 'success') {
        alert('✓ ' + message);
    } else {
        alert('✗ ' + message);
    }
}

function approveSales() {
    const notes = prompt('승인 사유를 입력하세요 (선택사항):');
    if (notes !== null) { // null means cancelled, empty string is ok
        if (confirm('매출을 승인하시겠습니까?')) {
            const formData = new FormData();
            formData.append('_token', csrfToken);
            if (notes) formData.append('notes', notes);

            fetch('{{ route("admin." . $routePrefix . ".status.approve", $sales->id) }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    location.reload(); // Refresh to show updated status
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('승인 처리 중 오류가 발생했습니다.', 'error');
            });
        }
    }
}

function confirmSales() {
    const reason = prompt('확정 사유를 입력하세요 (선택사항):');
    if (reason !== null) { // null means cancelled
        if (confirm('매출을 확정하시겠습니까? 확정 후에는 제한적인 수정만 가능합니다.')) {
            const formData = new FormData();
            formData.append('_token', csrfToken);
            if (reason) formData.append('reason', reason);

            fetch('{{ route("admin." . $routePrefix . ".status.confirm", $sales->id) }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    location.reload(); // Refresh to show updated status
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('확정 처리 중 오류가 발생했습니다.', 'error');
            });
        }
    }
}

function calculateCommission() {
    if (confirm('커미션을 계산하시겠습니까? 계산 후에는 되돌릴 수 없습니다.')) {
        const button = event.target;
        const originalText = button.innerHTML;
        button.setAttribute('data-original-text', originalText);
        setButtonLoading(button, true);

        fetch('{{ route("admin." . $routePrefix . ".commission.calculate", $sales->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            setButtonLoading(button, false);
            if (data.success) {
                showToast(data.message, 'success');
                location.reload(); // Refresh to show commission data
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            setButtonLoading(button, false);
            console.error('Error:', error);
            showToast('커미션 계산 중 오류가 발생했습니다.', 'error');
        });
    }
}

function cancelSales() {
    const reason = prompt('매출 취소 사유를 입력하세요:');
    if (reason) {
        if (confirm('매출을 취소하시겠습니까? 이미 계산된 커미션은 회수됩니다.')) {
            const formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('reason', reason);

            fetch('{{ route("admin." . $routePrefix . ".status.cancel", $sales->id) }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    location.reload(); // Refresh to show updated status
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('취소 처리 중 오류가 발생했습니다.', 'error');
            });
        }
    }
}

function deleteSales() {
    if (confirm('매출을 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("admin." . $routePrefix . ".destroy", $sales->id) }}';

        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'DELETE';

        const tokenField = document.createElement('input');
        tokenField.type = 'hidden';
        tokenField.name = '_token';
        tokenField.value = '{{ csrf_token() }}';

        form.appendChild(methodField);
        form.appendChild(tokenField);
        document.body.appendChild(form);
        form.submit();
    }
}

// Load status history dynamically (optional enhancement)
function loadStatusHistory() {
    fetch('{{ route("admin." . $routePrefix . ".status.history", $sales->id) }}', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update status history section if needed
            console.log('Status history loaded:', data.data);
        }
    })
    .catch(error => {
        console.error('Error loading status history:', error);
    });
}

// Auto-refresh status every 30 seconds (optional)
// setInterval(loadStatusHistory, 30000);
</script>
@endpush

@push('styles')
<style>
.timeline-item {
    position: relative;
}

.timeline-marker {
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.hierarchy-item {
    border-radius: 5px;
    border-left-width: 4px !important;
}

.commission-hierarchy {
    max-height: 400px;
    overflow-y: auto;
}

.badge-lg {
    font-size: 0.9em;
    padding: 0.5em 0.75em;
}

/* Processing Steps Styles */
.processing-steps {
    position: relative;
}

.step-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 20px;
    position: relative;
}

.step-item:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 15px;
    top: 30px;
    bottom: -20px;
    width: 2px;
    background-color: #e3e6f0;
    z-index: 1;
}

.step-item.completed:not(:last-child)::after {
    background-color: #28a745;
}

.step-item.current:not(:last-child)::after {
    background-color: #ffc107;
}

.step-marker {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    z-index: 2;
    margin-right: 15px;
    flex-shrink: 0;
}

.step-item.pending .step-marker {
    background-color: #e3e6f0;
    color: #858796;
    border: 2px solid #e3e6f0;
}

.step-item.current .step-marker {
    background-color: #fff3cd;
    color: #856404;
    border: 2px solid #ffc107;
    animation: pulse 2s infinite;
}

.step-item.completed .step-marker {
    background-color: #d4edda;
    color: #155724;
    border: 2px solid #28a745;
}

.step-content {
    flex: 1;
    padding-top: 2px;
}

.step-title {
    font-weight: 600;
    margin-bottom: 2px;
}

.step-item.pending .step-title {
    color: #858796;
}

.step-item.current .step-title {
    color: #856404;
}

.step-item.completed .step-title {
    color: #155724;
}

.step-time {
    font-size: 0.875rem;
    color: #6e707e;
}

.step-item.current .step-time {
    color: #856404;
    font-weight: 500;
}

.step-item.completed .step-time {
    color: #155724;
}

/* Current Status Section */
.current-status {
    padding: 20px;
    background: linear-gradient(135deg, #f8f9fc 0%, #eaecf4 100%);
    border-radius: 10px;
    border: 1px solid #e3e6f0;
}

.status-icon {
    margin-bottom: 15px;
}

/* Pulse animation for current step */
@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(255, 193, 7, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(255, 193, 7, 0);
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .processing-steps {
        margin-bottom: 20px;
    }

    .step-item {
        margin-bottom: 15px;
    }

    .current-status {
        margin-top: 20px;
    }
}
</style>
@endpush
@endsection
