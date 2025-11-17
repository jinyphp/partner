@extends('jiny-partner::layouts.admin.sidebar')

@section('title', $title . ' 수정')

@section('content')
<div class="container-fluid">

    <!-- 헤더 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">{{ $title }} 수정</h2>
                    <p class="text-muted mb-0">{{ $item->name }}님의 파트너 정보를 수정합니다</p>
                </div>
                <div>
                    <a href="{{ route('admin.' . $routePrefix . '.show', $item->id) }}" class="btn btn-outline-secondary me-2">
                        <i class="fe fe-arrow-left me-2"></i>상세보기
                    </a>
                    <a href="{{ route('admin.' . $routePrefix . '.index') }}" class="btn btn-outline-secondary">
                        <i class="fe fe-list me-2"></i>목록으로
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 수정 폼 -->
    <div class="row">
        <div class="col-lg-8">

            <form action="{{ route('admin.' . $routePrefix . '.update', $item->id) }}" method="POST" id="partnerUserForm">
                @csrf
                @method('PUT')

                {{-- 샤딩 회원 검색 (수정 모드) --}}
                @includeIf("jiny-partner::admin.partner-users.partials.search_user")

                <!-- 파트너 정보 카드 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fe fe-award me-2"></i>파트너 정보
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- 파트너 타입 및 등급 -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="partner_type_id" class="form-label">파트너 타입 <span class="text-danger">*</span></label>
                                <select name="partner_type_id"
                                        id="partner_type_id"
                                        class="form-control @error('partner_type_id') is-invalid @enderror">
                                    <option value="">타입을 선택하세요</option>
                                    @foreach($partnerTypes as $type)
                                        <option value="{{ $type->id }}"
                                                {{ old('partner_type_id', $item->partner_type_id) == $type->id ? 'selected' : '' }}
                                                data-commission-rate="{{ $type->default_commission_rate }}"
                                                data-commission-amount="{{ $type->default_commission_amount }}">
                                            {{ $type->type_name }}
                                            ({{ number_format($type->default_commission_rate ?? 0, 1) }}%)
                                            @if(isset($type->default_commission_amount) && $type->default_commission_amount > 0)
                                                (+{{ number_format($type->default_commission_amount) }}원)
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('partner_type_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="partner_tier_id" class="form-label">파트너 등급 <span class="text-danger">*</span></label>
                                <select name="partner_tier_id"
                                        id="partner_tier_id"
                                        class="form-control @error('partner_tier_id') is-invalid @enderror"
                                        required>
                                    <option value="">등급을 선택하세요</option>
                                    @foreach($partnerTiers as $tier)
                                        <option value="{{ $tier->id }}"
                                                {{ old('partner_tier_id', $item->partner_tier_id) == $tier->id ? 'selected' : '' }}
                                                data-commission-rate="{{ $tier->commission_rate }}"
                                                data-commission-amount="{{ $tier->commission_amount }}">
                                            {{ $tier->tier_name }} ({{ $tier->tier_code }})
                                            - {{ number_format($tier->commission_rate ?? 0, 1) }}%
                                            @if(isset($tier->commission_amount) && $tier->commission_amount > 0)
                                                - +{{ number_format($tier->commission_amount) }}원
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('partner_tier_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="status" class="form-label">상태 <span class="text-danger">*</span></label>
                                <select name="status"
                                        id="status"
                                        class="form-control @error('status') is-invalid @enderror"
                                        required>
                                    <option value="pending" {{ old('status', $item->status) == 'pending' ? 'selected' : '' }}>대기</option>
                                    <option value="active" {{ old('status', $item->status) == 'active' ? 'selected' : '' }}>승인</option>
                                    <option value="suspended" {{ old('status', $item->status) == 'suspended' ? 'selected' : '' }}>정지</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- 개별 수수료 설정 -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="individual_commission_type" class="form-label">개별 수수료 타입</label>
                                <select name="individual_commission_type"
                                        id="individual_commission_type"
                                        class="form-control @error('individual_commission_type') is-invalid @enderror">
                                    <option value="percentage" {{ old('individual_commission_type', $item->individual_commission_type) == 'percentage' ? 'selected' : '' }}>퍼센트</option>
                                    <option value="fixed_amount" {{ old('individual_commission_type', $item->individual_commission_type) == 'fixed_amount' ? 'selected' : '' }}>고정금액</option>
                                </select>
                                @error('individual_commission_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label for="individual_commission_rate" class="form-label">개별 수수료율 (%)</label>
                                <input type="number"
                                       class="form-control @error('individual_commission_rate') is-invalid @enderror"
                                       name="individual_commission_rate"
                                       id="individual_commission_rate"
                                       value="{{ old('individual_commission_rate', $item->individual_commission_rate) }}"
                                       step="0.01"
                                       min="0"
                                       max="100">
                                @error('individual_commission_rate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label for="individual_commission_amount" class="form-label">개별 수수료액 (원)</label>
                                <input type="number"
                                       class="form-control @error('individual_commission_amount') is-invalid @enderror"
                                       name="individual_commission_amount"
                                       id="individual_commission_amount"
                                       value="{{ old('individual_commission_amount', $item->individual_commission_amount) }}"
                                       min="0">
                                @error('individual_commission_amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label for="commission_calculation_preview" class="form-label">총 수수료율</label>
                                <div id="commission_calculation_preview" class="form-control bg-light border-2 border-primary text-center fw-bold text-primary">
                                    {{ number_format($item->getTotalCommissionRate(), 1) }}%
                                </div>
                            </div>
                        </div>

                        <!-- 수수료 설정 메모 -->
                        <div class="mb-3">
                            <label for="commission_notes" class="form-label">수수료 설정 메모</label>
                            <textarea class="form-control @error('commission_notes') is-invalid @enderror"
                                      name="commission_notes"
                                      id="commission_notes"
                                      rows="2"
                                      placeholder="개별 수수료 설정 사유나 특이사항을 입력하세요...">{{ old('commission_notes', $item->commission_notes) }}</textarea>
                            @error('commission_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 날짜 정보 -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="partner_joined_at" class="form-label">파트너 가입일</label>
                                <input type="date"
                                       class="form-control @error('partner_joined_at') is-invalid @enderror"
                                       name="partner_joined_at"
                                       id="partner_joined_at"
                                       value="{{ old('partner_joined_at', $item->partner_joined_at->format('Y-m-d')) }}">
                                @error('partner_joined_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="tier_assigned_at" class="form-label">등급 할당일</label>
                                <input type="date"
                                       class="form-control @error('tier_assigned_at') is-invalid @enderror"
                                       name="tier_assigned_at"
                                       id="tier_assigned_at"
                                       value="{{ old('tier_assigned_at', $item->tier_assigned_at->format('Y-m-d')) }}">
                                @error('tier_assigned_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="last_performance_review_at" class="form-label">마지막 성과 평가일</label>
                                <input type="date"
                                       class="form-control @error('last_performance_review_at') is-invalid @enderror"
                                       name="last_performance_review_at"
                                       id="last_performance_review_at"
                                       value="{{ old('last_performance_review_at', $item->last_performance_review_at?->format('Y-m-d')) }}">
                                @error('last_performance_review_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- 성과 정보 -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="total_completed_jobs" class="form-label">완료 작업 수</label>
                                <input type="number"
                                       class="form-control @error('total_completed_jobs') is-invalid @enderror"
                                       name="total_completed_jobs"
                                       id="total_completed_jobs"
                                       value="{{ old('total_completed_jobs', $item->total_completed_jobs) }}"
                                       min="0">
                                @error('total_completed_jobs')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label for="average_rating" class="form-label">평균 평점</label>
                                <input type="number"
                                       class="form-control @error('average_rating') is-invalid @enderror"
                                       name="average_rating"
                                       id="average_rating"
                                       value="{{ old('average_rating', $item->average_rating) }}"
                                       step="0.01"
                                       min="0"
                                       max="5">
                                @error('average_rating')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label for="punctuality_rate" class="form-label">시간 준수율 (%)</label>
                                <input type="number"
                                       class="form-control @error('punctuality_rate') is-invalid @enderror"
                                       name="punctuality_rate"
                                       id="punctuality_rate"
                                       value="{{ old('punctuality_rate', $item->punctuality_rate) }}"
                                       step="0.01"
                                       min="0"
                                       max="100">
                                @error('punctuality_rate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label for="satisfaction_rate" class="form-label">만족도 (%)</label>
                                <input type="number"
                                       class="form-control @error('satisfaction_rate') is-invalid @enderror"
                                       name="satisfaction_rate"
                                       id="satisfaction_rate"
                                       value="{{ old('satisfaction_rate', $item->satisfaction_rate) }}"
                                       step="0.01"
                                       min="0"
                                       max="100">
                                @error('satisfaction_rate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- 프로필 데이터 (JSON) -->
                        <div class="mb-3">
                            <label for="profile_data" class="form-label">프로필 데이터 (JSON)</label>
                            <textarea class="form-control @error('profile_data') is-invalid @enderror"
                                      name="profile_data"
                                      id="profile_data"
                                      rows="6"
                                      placeholder='{"specializations": ["웹개발", "모바일앱"], "certifications": ["정보처리기사"], "experience_years": 5}'>{{ old('profile_data', json_encode($item->profile_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) }}</textarea>
                            @error('profile_data')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">JSON 형태로 입력하세요. 빈 값으로 두면 null이 저장됩니다.</small>
                        </div>

                        <!-- 관리자 메모 -->
                        <div class="mb-3">
                            <label for="admin_notes" class="form-label">관리자 메모</label>
                            <textarea class="form-control @error('admin_notes') is-invalid @enderror"
                                      name="admin_notes"
                                      id="admin_notes"
                                      rows="3"
                                      placeholder="파트너에 대한 추가 정보나 특이사항을 입력하세요...">{{ old('admin_notes', $item->admin_notes) }}</textarea>
                            @error('admin_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 상태 변경 사유 -->
                        <div class="mb-4" id="status_reason_group" style="display: none;">
                            <label for="status_reason" class="form-label">상태 변경 사유</label>
                            <textarea class="form-control @error('status_reason') is-invalid @enderror"
                                      name="status_reason"
                                      id="status_reason"
                                      rows="2"
                                      placeholder="상태 변경 사유를 입력하세요...">{{ old('status_reason', $item->status_reason) }}</textarea>
                            @error('status_reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 버튼 그룹 -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.' . $routePrefix . '.show', $item->id) }}" class="btn btn-secondary">
                                <i class="fe fe-x me-1"></i>취소
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fe fe-save me-1"></i>수정
                            </button>
                        </div>
                    </div>
                </div>
            </form>

        </div>

        <div class="col-lg-4">

            <!-- 현재 정보 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">현재 정보</h6>
                </div>
                <div class="card-body">

                    <table class="table table-borderless table-sm">
                        <tr>
                            <td class="text-muted">현재 등급:</td>
                            <td><span class="badge bg-info">{{ $item->partnerTier->tier_name ?? 'N/A' }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">현재 상태:</td>
                            <td>
                                @if($item->status === 'active')
                                    <span class="badge bg-success">활성</span>
                                @elseif($item->status === 'pending')
                                    <span class="badge bg-warning">대기</span>
                                @elseif($item->status === 'suspended')
                                    <span class="badge bg-danger">정지</span>
                                @else
                                    <span class="badge bg-secondary">비활성</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">가입일:</td>
                            <td>{{ $item->partner_joined_at->format('Y-m-d') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">등급 할당일:</td>
                            <td>{{ $item->tier_assigned_at->format('Y-m-d') }}</td>
                        </tr>
                    </table>

                </div>
            </div>

            <!-- 수정 가이드 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">수정 가이드</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-primary">등급 변경</h6>
                        <p class="small text-muted">등급을 변경하면 등급 할당일이 자동으로 업데이트됩니다.</p>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-primary">상태 변경</h6>
                        <p class="small text-muted">정지나 비활성 상태로 변경시 반드시 사유를 입력하세요.</p>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-primary">성과 데이터</h6>
                        <p class="small text-muted">평점은 0-5점, 비율은 0-100% 사이 값으로 입력하세요.</p>
                    </div>
                    <div>
                        <h6 class="text-primary">프로필 데이터</h6>
                        <p class="small text-muted">JSON 형태로 입력하세요. 문법 오류시 저장되지 않습니다.</p>
                    </div>
                </div>
            </div>

            <!-- 변경 이력 -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">수정 이력</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td class="text-muted">등록일:</td>
                            <td>{{ $item->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">마지막 수정:</td>
                            <td>{{ $item->updated_at->format('Y-m-d H:i') }}</td>
                        </tr>
                        @if($item->creator)
                        <tr>
                            <td class="text-muted">등록자:</td>
                            <td>{{ $item->creator->name }}</td>
                        </tr>
                        @endif
                        @if($item->updater)
                        <tr>
                            <td class="text-muted">마지막 수정자:</td>
                            <td>{{ $item->updater->name }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// 수정 페이지 전용 JavaScript

// 등급 변경시 등급 할당일 자동 업데이트 (수정 모드 전용)
document.addEventListener('DOMContentLoaded', function() {
    const partnerTierSelect = document.getElementById('partner_tier_id');
    const tierAssignedAtInput = document.getElementById('tier_assigned_at');

    if (partnerTierSelect && tierAssignedAtInput) {
        partnerTierSelect.addEventListener('change', function() {
            const originalTierId = '{{ $item->partner_tier_id }}';
            const selectedTierId = this.value;

            if (selectedTierId !== originalTierId && selectedTierId) {
                const today = new Date().toISOString().split('T')[0];
                tierAssignedAtInput.value = today;

                // 변경 알림
                const alert = document.createElement('div');
                alert.className = 'alert alert-info alert-dismissible fade show mt-2';
                alert.innerHTML = `
                    <i class="fe fe-info-circle me-2"></i>
                    등급이 변경되어 할당일이 오늘 날짜로 자동 설정되었습니다.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;

                partnerTierSelect.parentNode.appendChild(alert);

                // 3초 후 자동 제거
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 3000);
            }
        });
    }

    // 상태 변경 확장 (수정 모드용)
    const statusSelect = document.getElementById('status');
    const statusReasonGroup = document.getElementById('status_reason_group');

    if (statusSelect && statusReasonGroup) {
        statusSelect.addEventListener('change', function() {
            const selectedStatus = this.value;
            const originalStatus = '{{ $item->status }}';

            // 상태가 변경되고, 정지나 비활성으로 변경되는 경우
            if (selectedStatus !== originalStatus && (selectedStatus === 'suspended' || selectedStatus === 'inactive')) {
                statusReasonGroup.style.display = 'block';
                document.getElementById('status_reason').required = true;

                // 상태 변경 안내
                const statusReasonTextarea = document.getElementById('status_reason');
                if (statusReasonTextarea && !statusReasonTextarea.value.trim()) {
                    statusReasonTextarea.placeholder = `${originalStatus}에서 ${selectedStatus}로 상태 변경 사유를 입력하세요...`;
                }
            } else {
                statusReasonGroup.style.display = 'none';
                const statusReasonInput = document.getElementById('status_reason');
                if (statusReasonInput) {
                    statusReasonInput.required = false;
                }
            }
        });

        // 페이지 로드시 상태에 따른 사유 필드 표시
        statusSelect.dispatchEvent(new Event('change'));
    }

    // 개별 수수료 타입 변경시 필드 활성화/비활성화
    const commissionTypeSelect = document.getElementById('individual_commission_type');
    const commissionRateField = document.getElementById('individual_commission_rate');
    const commissionAmountField = document.getElementById('individual_commission_amount');

    if (commissionTypeSelect && commissionRateField && commissionAmountField) {
        commissionTypeSelect.addEventListener('change', function() {
            const type = this.value;

            if (type === 'percentage') {
                commissionRateField.disabled = false;
                commissionAmountField.disabled = true;
                commissionAmountField.value = 0;
            } else {
                commissionRateField.disabled = true;
                commissionRateField.value = 0;
                commissionAmountField.disabled = false;
            }

            updateCommissionPreview();
        });

        // 초기 상태 설정
        commissionTypeSelect.dispatchEvent(new Event('change'));
    }

    // 수수료 계산 미리보기 업데이트
    function updateCommissionPreview() {
        const typeSelect = document.getElementById('partner_type_id');
        const tierSelect = document.getElementById('partner_tier_id');
        const individualType = document.getElementById('individual_commission_type').value;
        const individualRate = parseFloat(document.getElementById('individual_commission_rate').value) || 0;
        const preview = document.getElementById('commission_calculation_preview');

        let totalRate = 0;

        // 파트너 타입 수수료
        if (typeSelect && typeSelect.selectedOptions.length > 0) {
            const selectedType = typeSelect.selectedOptions[0];
            totalRate += parseFloat(selectedType.dataset.commissionRate) || 0;
        }

        // 파트너 등급 수수료
        if (tierSelect && tierSelect.selectedOptions.length > 0) {
            const selectedTier = tierSelect.selectedOptions[0];
            totalRate += parseFloat(selectedTier.dataset.commissionRate) || 0;
        }

        // 개별 수수료 (퍼센트인 경우만)
        if (individualType === 'percentage') {
            totalRate += individualRate;
        }

        if (preview) {
            preview.textContent = totalRate.toFixed(1) + '%';

            // 색상 업데이트
            if (totalRate > 0) {
                preview.classList.remove('text-primary');
                preview.classList.add('text-success');
            } else {
                preview.classList.remove('text-success');
                preview.classList.add('text-primary');
            }
        }
    }

    // 수수료 관련 필드 변경시 미리보기 업데이트
    if (document.getElementById('partner_type_id')) {
        document.getElementById('partner_type_id').addEventListener('change', updateCommissionPreview);
    }
    if (document.getElementById('partner_tier_id')) {
        document.getElementById('partner_tier_id').addEventListener('change', updateCommissionPreview);
    }
    if (document.getElementById('individual_commission_rate')) {
        document.getElementById('individual_commission_rate').addEventListener('input', updateCommissionPreview);
    }

    // 페이지 로드시 수수료 미리보기 업데이트
    updateCommissionPreview();
});
</script>
@endpush

@push('styles')
<style>
.table-borderless td {
    border: none !important;
    padding: 0.25rem 0.5rem;
}

.badge {
    font-size: 0.875rem;
}

.is-valid {
    border-color: #198754;
}

.is-invalid {
    border-color: #dc3545;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}
</style>
@endpush
