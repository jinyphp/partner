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
                            <li class="breadcrumb-item active">매출 등록</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('admin.' . $routePrefix . '.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> 목록으로
                    </a>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.' . $routePrefix . '.store') }}" method="POST" id="salesForm">
        @csrf

        <div class="row">
            <!-- 메인 정보 -->
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">매출 정보</h6>
                    </div>
                    <div class="card-body">
                        <!-- 파트너 선택 (검색 방식) -->
                        @include('jiny-partner::admin.partner-sales.partials.search_partner', [
                            'selectedPartnerId' => old('partner_id', $selectedPartnerId ?? null),
                            'selectedPartner' => $selectedPartner ?? null
                        ])

                        <!-- 매출 제목 -->
                        <div class="form-group">
                            <label for="title" class="required">매출 제목 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror"
                                   id="title" name="title" value="{{ old('title') }}" required maxlength="200"
                                   placeholder="매출 제목을 입력하세요">
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 매출 설명 -->
                        <div class="form-group">
                            <label for="description">매출 설명</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="3" maxlength="1000"
                                      placeholder="매출에 대한 상세 설명을 입력하세요">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 금액 및 통화 -->
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="amount" class="required">매출 금액 <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('amount') is-invalid @enderror"
                                           id="amount" name="amount" value="{{ old('amount') }}" required
                                           min="0" max="999999999999.99" step="0.01"
                                           placeholder="0.00">
                                    @error('amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="currency" class="required">통화 <span class="text-danger">*</span></label>
                                    <select class="form-control @error('currency') is-invalid @enderror"
                                            id="currency" name="currency" required>
                                        @foreach($currencies as $code => $name)
                                            <option value="{{ $code }}" {{ old('currency', 'KRW') == $code ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('currency')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- 매출일 -->
                        <div class="form-group">
                            <label for="sales_date" class="required">매출일 <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('sales_date') is-invalid @enderror"
                                   id="sales_date" name="sales_date" value="{{ old('sales_date', date('Y-m-d')) }}"
                                   required max="{{ date('Y-m-d') }}">
                            @error('sales_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 주문번호 -->
                        <div class="form-group">
                            <label for="order_number">주문번호</label>
                            <input type="text" class="form-control @error('order_number') is-invalid @enderror"
                                   id="order_number" name="order_number" value="{{ old('order_number') }}"
                                   maxlength="100" placeholder="자동 생성됩니다 (선택사항)">
                            @error('order_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">비워두면 자동으로 생성됩니다.</small>
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
                                    <select class="form-control @error('category') is-invalid @enderror"
                                            id="category" name="category">
                                        <option value="">선택하세요</option>
                                        @foreach($categories as $value => $label)
                                            <option value="{{ $value }}" {{ old('category') == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="product_type">제품 타입</label>
                                    <select class="form-control @error('product_type') is-invalid @enderror"
                                            id="product_type" name="product_type">
                                        <option value="">선택하세요</option>
                                        @foreach($productTypes as $value => $label)
                                            <option value="{{ $value }}" {{ old('product_type') == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('product_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="sales_channel">판매 채널</label>
                                    <select class="form-control @error('sales_channel') is-invalid @enderror"
                                            id="sales_channel" name="sales_channel">
                                        <option value="">선택하세요</option>
                                        @foreach($salesChannels as $value => $label)
                                            <option value="{{ $value }}" {{ old('sales_channel') == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('sales_channel')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
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
                            <textarea class="form-control @error('admin_notes') is-invalid @enderror"
                                      id="admin_notes" name="admin_notes" rows="3" maxlength="1000"
                                      placeholder="관리자용 메모를 입력하세요">{{ old('admin_notes') }}</textarea>
                            @error('admin_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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
                            <select class="form-control @error('status') is-invalid @enderror"
                                    id="status" name="status" required>
                                <option value="pending" {{ old('status', 'pending') == 'pending' ? 'selected' : '' }}>
                                    대기중
                                </option>
                                <option value="confirmed" {{ old('status') == 'confirmed' ? 'selected' : '' }}>
                                    확정
                                </option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="requires_approval"
                                   name="requires_approval" value="1" {{ old('requires_approval') ? 'checked' : '' }}>
                            <label class="form-check-label" for="requires_approval">
                                승인이 필요한 매출
                            </label>
                        </div>
                    </div>
                </div>

                <!-- 커미션 설정 -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">커미션 설정</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="auto_calculate_commission"
                                   name="auto_calculate_commission" value="1" {{ old('auto_calculate_commission', '1') ? 'checked' : '' }}>
                            <label class="form-check-label" for="auto_calculate_commission">
                                자동 커미션 계산
                            </label>
                        </div>
                        <small class="form-text text-muted">
                            확정 상태일 때 자동으로 커미션을 계산합니다.
                        </small>

                        <div id="commissionPreview" class="mt-3" style="display: none;">
                            <div class="alert alert-info">
                                <small>
                                    <strong>커미션 계산 미리보기</strong><br>
                                    파트너와 금액을 선택하면 예상 커미션이 표시됩니다.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 파트너 정보 -->
                <div class="card shadow mb-4" id="partnerInfo" style="display: none;">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">파트너 정보</h6>
                    </div>
                    <div class="card-body">
                        <div id="partnerDetails">
                            <!-- 파트너 선택 시 JavaScript로 채워질 영역 -->
                        </div>
                    </div>
                </div>

                <!-- 작업 버튼 -->
                <div class="card shadow">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-save"></i> 매출 등록
                        </button>
                        <a href="{{ route('admin.' . $routePrefix . '.index') }}"
                           class="btn btn-secondary btn-block mt-2">
                            <i class="fas fa-times"></i> 취소
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // 파트너 선택 변화 감지 (검색 기능과 연동)
    function onPartnerSelected(partnerData) {
        if (partnerData) {
            const tier = partnerData.tier_name;
            const type = partnerData.type_name;

            $('#partnerDetails').html(`
                <div class="mb-2">
                    <small class="text-muted">등급:</small><br>
                    <span class="badge badge-info">${tier}</span>
                </div>
                <div class="mb-2">
                    <small class="text-muted">타입:</small><br>
                    <span class="badge badge-secondary">${type}</span>
                </div>
            `);

            $('#partnerInfo').show();
            updateCommissionPreview();
        } else {
            $('#partnerInfo').hide();
        }
    }

    // 파트너 ID 필드 변화 감지
    $(document).on('change', '#partner_id', function() {
        const partnerId = $(this).val();

        if (partnerId && partnerSearchState.selectedPartner) {
            onPartnerSelected(partnerSearchState.selectedPartner);
        } else {
            $('#partnerInfo').hide();
        }
    });

    // 금액 입력 시 커미션 미리보기 업데이트
    $('#amount').on('input', function() {
        updateCommissionPreview();
    });

    // 커미션 미리보기 업데이트
    function updateCommissionPreview() {
        const partnerId = $('#partner_id').val();
        const amount = parseFloat($('#amount').val()) || 0;

        if (partnerId && amount > 0 && partnerSearchState.selectedPartner) {
            const commissionRate = partnerSearchState.selectedPartner.total_commission_rate;
            const estimatedCommission = (amount * commissionRate / 100).toLocaleString('ko-KR');

            $('#commissionPreview').html(`
                <div class="alert alert-success">
                    <small>
                        <strong>커미션 계산 미리보기</strong><br>
                        매출 금액: ${amount.toLocaleString('ko-KR')}원<br>
                        커미션율: ${commissionRate}%<br>
                        <strong class="text-success">예상 커미션: ${estimatedCommission}원</strong>
                    </small>
                </div>
            `);
            $('#commissionPreview').show();
        } else {
            $('#commissionPreview').hide();
        }
    }

    // 자동 커미션 계산 체크박스 상태에 따른 UI 업데이트
    $('#auto_calculate_commission').change(function() {
        if ($(this).is(':checked')) {
            $('#commissionPreview').show();
        } else {
            $('#commissionPreview').hide();
        }
    });

    // 매출 상태 변경 시 처리
    $('#status').change(function() {
        const status = $(this).val();
        const autoCalcCheckbox = $('#auto_calculate_commission');

        if (status === 'confirmed') {
            autoCalcCheckbox.prop('disabled', false);
        } else {
            autoCalcCheckbox.prop('disabled', true);
            autoCalcCheckbox.prop('checked', false);
            $('#commissionPreview').hide();
        }
    });

    // 폼 제출 전 검증
    $('#salesForm').submit(function(e) {
        const amount = parseFloat($('#amount').val()) || 0;

        if (amount <= 0) {
            e.preventDefault();
            alert('매출 금액을 올바르게 입력해주세요.');
            $('#amount').focus();
            return false;
        }

        // 확정 상태이고 자동 커미션 계산이 선택된 경우 확인
        const status = $('#status').val();
        const autoCalc = $('#auto_calculate_commission').is(':checked');

        if (status === 'confirmed' && autoCalc) {
            if (!confirm('매출을 확정하고 커미션을 자동으로 계산하시겠습니까?')) {
                e.preventDefault();
                return false;
            }
        }
    });

    // 초기 파트너 정보 표시 (편집 모드에서)
    const initialPartnerId = $('#partner_id').val();
    if (initialPartnerId) {
        // 기존에 선택된 파트너가 있는 경우 파트너 정보를 표시
        // 이미 search_partner.blade.php에서 처리됨
        setTimeout(() => {
            if (partnerSearchState.selectedPartner) {
                onPartnerSelected(partnerSearchState.selectedPartner);
            }
        }, 100);
    }

    // 파트너 검색 결과에서 파트너 선택 시 호출되는 함수 재정의
    window.showPartnerInfo = function(partnerData) {
        onPartnerSelected(partnerData);
    };
});
</script>
@endpush
@endsection
