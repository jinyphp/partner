@extends('jiny-partner::layouts.admin.sidebar')

@section('title', $title)

@section('content')
<div class="container-fluid">
    <!-- 헤더 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">{{ $title }}</h2>
                    <p class="text-muted mb-0">파트너 매출을 등록하고 커미션 분배를 관리합니다</p>
                </div>
                <div>
                    <a href="{{ route('admin.partner.dashboard') }}" class="btn btn-outline-secondary me-2">
                        <i class="fe fe-arrow-left me-2"></i>파트너 관리
                    </a>
                    <a href="{{ route('admin.' . $routePrefix . '.create') }}" class="btn btn-primary">
                        <i class="fe fe-plus me-2"></i>매출 등록
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 통계 카드 -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-gradient rounded-circle p-3 stat-circle">
                                <i class="fe fe-trending-up text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 text-muted">총 매출</h6>
                            <h3 class="mb-0 fw-bold">{{ number_format($statistics['total_sales']) }}원</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-gradient rounded-circle p-3 stat-circle">
                                <i class="fe fe-check-circle text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 text-muted">확정 매출</h6>
                            <h3 class="mb-0 fw-bold">{{ number_format($statistics['confirmed_sales']) }}원</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-gradient rounded-circle p-3 stat-circle">
                                <i class="fe fe-dollar-sign text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 text-muted">총 커미션</h6>
                            <h3 class="mb-0 fw-bold">{{ number_format($statistics['total_commission']) }}원</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-gradient rounded-circle p-3 stat-circle">
                                <i class="fe fe-users text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 text-muted">참여 파트너</h6>
                            <h3 class="mb-0 fw-bold">{{ number_format($statistics['unique_partners']) }}명</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 필터 -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.' . $routePrefix . '.index') }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search">검색</label>
                            <input type="text"
                                   id="search"
                                   name="search"
                                   class="form-control"
                                   placeholder="제목, 주문번호, 파트너명으로 검색..."
                                   value="{{ $searchValue }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="status">상태</label>
                            <select id="status" name="status" class="form-control">
                                <option value="">전체</option>
                                @foreach($filterOptions['statuses'] as $value => $label)
                                    <option value="{{ $value }}" {{ $selectedStatus == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="partner_id">파트너</label>
                            <select id="partner_id" name="partner_id" class="form-control">
                                <option value="">전체</option>
                                @foreach($filterOptions['partners'] as $id => $name)
                                    <option value="{{ $id }}" {{ $selectedPartnerId == $id ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="start_date">시작일</label>
                            <input type="date" id="start_date" name="start_date" class="form-control" value="{{ $startDate }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="end_date">종료일</label>
                            <input type="date" id="end_date" name="end_date" class="form-control" value="{{ $endDate }}">
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-outline-primary me-2">
                            <i class="fe fe-search me-1"></i>검색
                        </button>
                        <a href="{{ route('admin.' . $routePrefix . '.index') }}" class="btn btn-outline-secondary">
                            <i class="fe fe-refresh-cw me-1"></i>초기화
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 매출 목록 -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">파트너 매출 목록</h5>
        </div>
        <div class="card-body p-0">
            @if($items->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="40">
                                    <input type="checkbox" id="checkAll">
                                </th>
                                <th>매출 정보</th>
                                <th>파트너</th>
                                <th width="120">금액</th>
                                <th width="80">상태</th>
                                <th width="100">잔액 영향</th>
                                <th width="100">커미션</th>
                                <th width="100">매출일</th>
                                <th width="100">관리</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $item)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="item-checkbox" value="{{ $item->id }}">
                                    </td>
                                    <td>
                                        <div class="font-weight-bold">{{ $item->title }}</div>
                                        <small class="text-muted">{{ $item->order_number }}</small>
                                        @if($item->category)
                                            <span class="badge badge-light">{{ $item->category }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $item->partner_name }}</div>
                                        <small class="text-muted">{{ $item->partner_email }}</small>
                                        @if($item->partner && $item->partner->tier)
                                            <span class="badge badge-info">{{ $item->partner->tier->tier_name }}</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <div class="font-weight-bold">{{ number_format($item->amount) }}원</div>
                                        <small class="text-muted">{{ $item->currency }}</small>
                                    </td>
                                    <td>
                                        @switch($item->status)
                                            @case('pending')
                                                <span class="badge badge-warning">대기중</span>
                                                @break
                                            @case('confirmed')
                                                <span class="badge badge-success">확정</span>
                                                @break
                                            @case('cancelled')
                                                <span class="badge badge-danger">취소</span>
                                                @break
                                            @case('refunded')
                                                <span class="badge badge-secondary">환불</span>
                                                @break
                                        @endswitch

                                        @if($item->requires_approval && !$item->is_approved)
                                            <br><small class="text-warning">승인 대기</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @switch($item->status)
                                            @case('confirmed')
                                                <div class="text-success font-weight-bold">
                                                    +{{ number_format($item->amount) }}원
                                                </div>
                                                <small class="text-muted">잔액 반영</small>
                                                @break
                                            @case('pending')
                                                <div class="text-warning">
                                                    0원
                                                </div>
                                                <small class="text-muted">대기중</small>
                                                @break
                                            @case('cancelled')
                                                <div class="text-danger">
                                                    0원
                                                </div>
                                                <small class="text-muted">취소됨</small>
                                                @break
                                            @case('refunded')
                                                <div class="text-secondary">
                                                    -{{ number_format($item->amount) }}원
                                                </div>
                                                <small class="text-muted">환불됨</small>
                                                @break
                                            @default
                                                <div class="text-muted">
                                                    0원
                                                </div>
                                                <small class="text-muted">미반영</small>
                                        @endswitch
                                    </td>
                                    <td class="text-right">
                                        @if($item->commission_calculated)
                                            <div class="text-success font-weight-bold">
                                                {{ number_format($item->total_commission_amount) }}원
                                            </div>
                                            <small class="text-muted">{{ $item->commission_recipients_count }}명</small>
                                        @else
                                            <span class="text-muted">미계산</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $item->sales_date ? $item->sales_date->format('Y-m-d') : '' }}</div>
                                        <small class="text-muted">{{ $item->created_at->format('H:i') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.' . $routePrefix . '.show', $item->id) }}"
                                               class="btn btn-outline-primary"
                                               title="상세보기">
                                                <i class="fe fe-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.' . $routePrefix . '.edit', $item->id) }}"
                                               class="btn btn-outline-warning"
                                               title="수정">
                                                <i class="fe fe-edit"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- 페이지네이션 -->
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            전체 {{ $items->total() }}개 중
                            {{ $items->firstItem() }}~{{ $items->lastItem() }}개 표시
                        </div>
                        <div>
                            {{ $items->appends(request()->query())->links('pagination::bootstrap-4') }}
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fe fe-trending-up fe-3x text-muted mb-3"></i>
                    <h5 class="text-muted">등록된 매출이 없습니다</h5>
                    <p class="text-muted">새로운 파트너 매출을 등록해보세요.</p>
                    <a href="{{ route('admin.' . $routePrefix . '.create') }}" class="btn btn-primary">
                        <i class="fe fe-plus me-2"></i>첫 번째 매출 등록
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- 대량 작업 버튼 -->
    @if($items->count() > 0)
        <div class="card">
            <div class="card-body">
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-danger" id="bulkDeleteBtn" disabled>
                        <i class="fe fe-trash-2 me-1"></i>선택 삭제
                    </button>
                    <button type="button" class="btn btn-outline-info" id="bulkCommissionBtn" disabled>
                        <i class="fe fe-calculator me-1"></i>커미션 계산
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

@push('styles')
<style>
/* 통계 카드 원형 아이콘 스타일 */
.stat-circle {
    width: 48px !important;
    height: 48px !important;
    min-width: 48px;
    min-height: 48px;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-shrink: 0 !important;
}

.stat-circle i {
    font-size: 20px;
}

/* 카드 그림자 효과 */
.shadow-sm {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // 전체 선택/해제
    $('#checkAll').change(function() {
        $('.item-checkbox').prop('checked', $(this).prop('checked'));
        toggleBulkButtons();
    });

    // 개별 체크박스 변경
    $('.item-checkbox').change(function() {
        const totalCheckboxes = $('.item-checkbox').length;
        const checkedCheckboxes = $('.item-checkbox:checked').length;

        $('#checkAll').prop('checked', totalCheckboxes === checkedCheckboxes);
        toggleBulkButtons();
    });

    // 대량 작업 버튼 활성화/비활성화
    function toggleBulkButtons() {
        const checkedCount = $('.item-checkbox:checked').length;
        $('#bulkDeleteBtn, #bulkCommissionBtn').prop('disabled', checkedCount === 0);
    }

    // 대량 삭제
    $('#bulkDeleteBtn').click(function() {
        const checkedIds = $('.item-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (checkedIds.length === 0) {
            alert('삭제할 항목을 선택해주세요.');
            return;
        }

        if (confirm(`선택한 ${checkedIds.length}개의 매출을 삭제하시겠습니까?`)) {
            // AJAX 대량 삭제 요청
            $.post('{{ route("admin." . $routePrefix . ".bulk-destroy") }}', {
                ids: checkedIds,
                _token: '{{ csrf_token() }}'
            })
            .done(function(response) {
                if (response.success) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert('삭제 중 오류가 발생했습니다: ' + response.message);
                }
            })
            .fail(function() {
                alert('삭제 요청 중 오류가 발생했습니다.');
            });
        }
    });

    // 대량 커미션 계산
    $('#bulkCommissionBtn').click(function() {
        const checkedIds = $('.item-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (checkedIds.length === 0) {
            alert('커미션을 계산할 항목을 선택해주세요.');
            return;
        }

        if (confirm(`선택한 ${checkedIds.length}개의 매출에 대해 커미션을 계산하시겠습니까?`)) {
            // 커미션 계산 요청
            alert('커미션 계산 기능은 개발 중입니다.');
        }
    });
});
</script>
@endpush
@endsection
