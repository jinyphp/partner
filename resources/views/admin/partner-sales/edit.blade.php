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
                            <li class="breadcrumb-item"><a href="{{ route('admin.' . $routePrefix . '.show', $sales->id) }}">{{ $sales->title }}</a></li>
                            <li class="breadcrumb-item active">수정</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('admin.' . $routePrefix . '.show', $sales->id) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> 상세보기로
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 경고 메시지 -->
    @if($warnings && count($warnings) > 0)
        @foreach($warnings as $warning)
            <div class="alert alert-{{ $warning['type'] }} alert-dismissible fade show" role="alert">
                <strong>{{ $warning['message'] }}</strong>
                @if(isset($warning['detail']))
                    <br><small>{{ $warning['detail'] }}</small>
                @endif
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endforeach
    @endif

    <form action="{{ route('admin.' . $routePrefix . '.update', $sales->id) }}" method="POST" id="salesEditForm">
        @csrf
        @method('PUT')

        <div class="row">
            <!-- 메인 정보 -->
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">매출 정보</h6>
                    </div>
                    <div class="card-body">
                        <!-- 파트너 선택 -->
                        <div class="form-group">
                            <label for="partner_id" class="required">파트너 <span class="text-danger">*</span></label>
                            @if(in_array('partner_id', $editableFields))
                                <select class="form-control @error('partner_id') is-invalid @enderror"
                                        id="partner_id" name="partner_id" required>
                                    <option value="">파트너를 선택하세요</option>
                                    @foreach($partners as $partner)
                                        <option value="{{ $partner['id'] }}"
                                                {{ old('partner_id', $sales->partner_id) == $partner['id'] ? 'selected' : '' }}
                                                data-tier="{{ $partner['tier_name'] }}"
                                                data-type="{{ $partner['type_name'] }}">
                                            {{ $partner['display_name'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('partner_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            @else
                                <div class="form-control-plaintext">
                                    {{ $sales->partner_name }} ({{ $sales->partner_email }})
                                    @if($sales->partner && $sales->partner->tier)
                                        <span class="badge badge-info">{{ $sales->partner->tier->tier_name }}</span>
                                    @endif
                                </div>
                                <small class="text-muted">커미션이 계산된 매출은 파트너를 변경할 수 없습니다.</small>
                            @endif
                        </div>

                        <!-- 매출 제목 -->
                        <div class="form-group">
                            <label for="title" class="required">매출 제목 <span class="text-danger">*</span></label>
                            @if(in_array('title', $editableFields))
                                <input type="text" class="form-control @error('title') is-invalid @enderror"
                                       id="title" name="title" value="{{ old('title', $sales->title) }}" required maxlength="200"
                                       placeholder="매출 제목을 입력하세요">
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            @else
                                <div class="form-control-plaintext">{{ $sales->title }}</div>
                            @endif
                        </div>

                        <!-- 매출 설명 -->
                        <div class="form-group">
                            <label for="description">매출 설명</label>
                            @if(in_array('description', $editableFields))
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                          id="description" name="description" rows="3" maxlength="1000"
                                          placeholder="매출에 대한 상세 설명을 입력하세요">{{ old('description', $sales->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            @else
                                <div class="form-control-plaintext">{{ $sales->description ?: '설명 없음' }}</div>
                            @endif
                        </div>

                        <!-- 금액 및 통화 -->
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="amount" class="required">매출 금액 <span class="text-danger">*</span></label>
                                    @if(in_array('amount', $editableFields))
                                        <input type="number" class="form-control @error('amount') is-invalid @enderror"
                                               id="amount" name="amount" value="{{ old('amount', $sales->amount) }}" required
                                               min="0" max="999999999999.99" step="0.01"
                                               placeholder="0.00">
                                        @error('amount')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    @else
                                        <div class="form-control-plaintext h4 text-success">
                                            {{ number_format($sales->amount) }}원
                                        </div>
                                        <small class="text-muted">커미션이 계산된 매출은 금액을 변경할 수 없습니다.</small>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="currency" class="required">통화 <span class="text-danger">*</span></label>
                                    @if(in_array('currency', $editableFields))
                                        <select class="form-control @error('currency') is-invalid @enderror"
                                                id="currency" name="currency" required>
                                            @foreach($currencies as $code => $name)
                                                <option value="{{ $code }}" {{ old('currency', $sales->currency) == $code ? 'selected' : '' }}>
                                                    {{ $name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('currency')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    @else
                                        <div class="form-control-plaintext">{{ $sales->currency }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- 매출일 -->
                        <div class="form-group">
                            <label for="sales_date" class="required">매출일 <span class="text-danger">*</span></label>
                            @if(in_array('sales_date', $editableFields))
                                <input type="date" class="form-control @error('sales_date') is-invalid @enderror"
                                       id="sales_date" name="sales_date" value="{{ old('sales_date', $sales->sales_date ? $sales->sales_date->format('Y-m-d') : '') }}"
                                       required max="{{ date('Y-m-d') }}">
                                @error('sales_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            @else
                                <div class="form-control-plaintext">
                                    {{ $sales->sales_date ? $sales->sales_date->format('Y년 m월 d일') : '미설정' }}
                                </div>
                            @endif
                        </div>

                        <!-- 주문번호 -->
                        <div class="form-group">
                            <label for="order_number">주문번호</label>
                            @if(in_array('order_number', $editableFields))
                                <input type="text" class="form-control @error('order_number') is-invalid @enderror"
                                       id="order_number" name="order_number" value="{{ old('order_number', $sales->order_number) }}"
                                       maxlength="100" placeholder="주문번호">
                                @error('order_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            @else
                                <div class="form-control-plaintext">{{ $sales->order_number }}</div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- 분류 정보 -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">분류 정보</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="category">카테고리</label>
                                    @if(in_array('category', $editableFields))
                                        <select class="form-control @error('category') is-invalid @enderror"
                                                id="category" name="category">
                                            <option value="">선택하세요</option>
                                            @foreach($categories as $value => $label)
                                                <option value="{{ $value }}" {{ old('category', $sales->category) == $value ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('category')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    @else
                                        <div class="form-control-plaintext">
                                            {{ $sales->category ? $categories[$sales->category] ?? $sales->category : '미분류' }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="product_type">제품 타입</label>
                                    @if(in_array('product_type', $editableFields))
                                        <select class="form-control @error('product_type') is-invalid @enderror"
                                                id="product_type" name="product_type">
                                            <option value="">선택하세요</option>
                                            @foreach($productTypes as $value => $label)
                                                <option value="{{ $value }}" {{ old('product_type', $sales->product_type) == $value ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('product_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    @else
                                        <div class="form-control-plaintext">
                                            {{ $sales->product_type ? $productTypes[$sales->product_type] ?? $sales->product_type : '미분류' }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="sales_channel">판매 채널</label>
                                    @if(in_array('sales_channel', $editableFields))
                                        <select class="form-control @error('sales_channel') is-invalid @enderror"
                                                id="sales_channel" name="sales_channel">
                                            <option value="">선택하세요</option>
                                            @foreach($salesChannels as $value => $label)
                                                <option value="{{ $value }}" {{ old('sales_channel', $sales->sales_channel) == $value ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('sales_channel')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    @else
                                        <div class="form-control-plaintext">
                                            {{ $sales->sales_channel ? $salesChannels[$sales->sales_channel] ?? $sales->sales_channel : '미분류' }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 관리자 메모 -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">관리자 메모</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="admin_notes">관리자 메모</label>
                            @if(in_array('admin_notes', $editableFields))
                                <textarea class="form-control @error('admin_notes') is-invalid @enderror"
                                          id="admin_notes" name="admin_notes" rows="3" maxlength="1000"
                                          placeholder="관리자용 메모를 입력하세요">{{ old('admin_notes', $sales->admin_notes) }}</textarea>
                                @error('admin_notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            @else
                                <div class="form-control-plaintext">{{ $sales->admin_notes ?: '메모 없음' }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- 사이드바 -->
            <div class="col-lg-4">
                <!-- 상태 설정 -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">상태 설정</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="status" class="required">매출 상태 <span class="text-danger">*</span></label>
                            @if(in_array('status', $editableFields))
                                <select class="form-control @error('status') is-invalid @enderror"
                                        id="status" name="status" required>
                                    @foreach($statuses as $value => $label)
                                        <option value="{{ $value }}" {{ old('status', $sales->status) == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            @else
                                <div class="form-control-plaintext">
                                    <span class="badge badge-lg
                                        @switch($sales->status)
                                            @case('pending') badge-warning @break
                                            @case('confirmed') badge-success @break
                                            @case('cancelled') badge-danger @break
                                            @case('refunded') badge-secondary @break
                                        @endswitch">
                                        {{ $statuses[$sales->status] ?? $sales->status }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        @if($sales->requires_approval)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="requires_approval"
                                       name="requires_approval" value="1" {{ old('requires_approval', $sales->requires_approval) ? 'checked' : '' }}
                                       {{ $isCommissionCalculated ? 'disabled' : '' }}>
                                <label class="form-check-label" for="requires_approval">
                                    승인이 필요한 매출
                                </label>
                            </div>

                            @if(in_array('approval_notes', $editableFields))
                                <div class="form-group mt-3">
                                    <label for="approval_notes">승인 메모</label>
                                    <textarea class="form-control @error('approval_notes') is-invalid @enderror"
                                              id="approval_notes" name="approval_notes" rows="2" maxlength="1000"
                                              placeholder="승인 관련 메모">{{ old('approval_notes', $sales->approval_notes) }}</textarea>
                                    @error('approval_notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif
                        @endif
                    </div>
                </div>

                <!-- 커미션 정보 -->
                @if($sales->commission_calculated)
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">커미션 정보</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="font-weight-bold">총 커미션:</label>
                                <div class="h5 text-success">{{ number_format($sales->total_commission_amount) }}원</div>
                            </div>
                            <div class="mb-3">
                                <label class="font-weight-bold">수령자:</label>
                                {{ $sales->commission_recipients_count }}명
                            </div>
                            <div class="mb-3">
                                <label class="font-weight-bold">계산일:</label>
                                {{ $sales->commission_calculated_at ? $sales->commission_calculated_at->format('Y-m-d H:i') : '' }}
                            </div>
                            <div class="alert alert-info">
                                <small>이미 커미션이 계산된 매출입니다. 주요 정보 변경이 제한됩니다.</small>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- 작업 버튼 -->
                <div class="card shadow">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-save"></i> 변경사항 저장
                        </button>
                        <a href="{{ route('admin.' . $routePrefix . '.show', $sales->id) }}"
                           class="btn btn-secondary btn-block mt-2">
                            <i class="fas fa-times"></i> 취소
                        </a>

                        @if($sales->status === 'pending')
                            <hr>
                            <button type="button" class="btn btn-success btn-block" onclick="quickConfirm()">
                                <i class="fas fa-check"></i> 즉시 확정
                            </button>
                        @endif

                        @if($sales->status !== 'cancelled')
                            <button type="button" class="btn btn-warning btn-block mt-2" onclick="quickCancel()">
                                <i class="fas fa-ban"></i> 매출 취소
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // 파트너 선택 시 정보 표시
    $('#partner_id').change(function() {
        const selectedOption = $(this).find('option:selected');
        const partnerId = $(this).val();

        if (partnerId) {
            // 파트너 변경 경고
            if (confirm('파트너를 변경하면 기존 커미션 계산이 초기화될 수 있습니다. 계속하시겠습니까?')) {
                // 파트너 정보 업데이트
                updatePartnerInfo(selectedOption);
            }
        }
    });

    // 상태 변경 시 처리
    $('#status').change(function() {
        const newStatus = $(this).val();
        const oldStatus = '{{ $sales->status }}';

        if (newStatus !== oldStatus) {
            handleStatusChange(newStatus, oldStatus);
        }
    });

    // 폼 제출 전 검증
    $('#salesEditForm').submit(function(e) {
        const amount = parseFloat($('#amount').val()) || 0;

        if (amount <= 0 && $('#amount').is(':enabled')) {
            e.preventDefault();
            alert('매출 금액을 올바르게 입력해주세요.');
            $('#amount').focus();
            return false;
        }

        // 중요한 변경사항이 있는 경우 확인
        if (hasImportantChanges()) {
            if (!confirm('중요한 정보가 변경되었습니다. 저장하시겠습니까?')) {
                e.preventDefault();
                return false;
            }
        }
    });
});

function updatePartnerInfo(selectedOption) {
    const tier = selectedOption.data('tier');
    const type = selectedOption.data('type');
    // 파트너 정보 UI 업데이트 로직
}

function handleStatusChange(newStatus, oldStatus) {
    if (newStatus === 'confirmed' && oldStatus === 'pending') {
        if (!confirm('매출을 확정하시겠습니까? 확정 후에는 제한적인 수정만 가능합니다.')) {
            $('#status').val(oldStatus);
            return;
        }
    }

    if (newStatus === 'cancelled') {
        const reason = prompt('취소 사유를 입력하세요:');
        if (!reason) {
            $('#status').val(oldStatus);
            return;
        }
        $('#admin_notes').val($('#admin_notes').val() + '\n취소 사유: ' + reason);
    }
}

function hasImportantChanges() {
    // 중요한 필드들의 변경 여부 확인
    const importantFields = ['partner_id', 'amount', 'currency', 'sales_date', 'status'];

    for (let field of importantFields) {
        const element = $('#' + field);
        if (element.length && element.is(':enabled')) {
            const originalValue = element.data('original-value');
            if (originalValue && originalValue !== element.val()) {
                return true;
            }
        }
    }

    return false;
}

function quickConfirm() {
    if (confirm('매출을 즉시 확정하시겠습니까?')) {
        $('#status').val('confirmed');
        $('#salesEditForm').submit();
    }
}

function quickCancel() {
    const reason = prompt('취소 사유를 입력하세요:');
    if (reason) {
        $('#status').val('cancelled');
        $('#admin_notes').val($('#admin_notes').val() + '\n취소 사유: ' + reason);
        $('#salesEditForm').submit();
    }
}

// 원본 값 저장 (변경 감지를 위해)
$(document).ready(function() {
    $('input, select, textarea').each(function() {
        $(this).data('original-value', $(this).val());
    });
});
</script>
@endpush

@push('styles')
<style>
.badge-lg {
    font-size: 0.9em;
    padding: 0.5em 0.75em;
}

.form-control-plaintext {
    padding-top: 0.375rem;
    padding-bottom: 0.375rem;
    margin-bottom: 0;
    line-height: 1.5;
    background-color: transparent;
    border: solid transparent;
    border-width: 1px 0;
}
</style>
@endpush
@endsection
