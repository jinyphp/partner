@extends('jiny-partner::layouts.admin.sidebar')

@section('title', $title . ' 등록')

@section('content')
<div class="container-fluid">

    <!-- 헤더 -->
    <section class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">{{ $title }} 등록</h2>
                    <p class="text-muted mb-0">새로운 파트너 회원을 시스템에 등록합니다</p>
                </div>
                <div>
                    <a href="{{ route('admin.' . $routePrefix . '.index') }}" class="btn btn-outline-secondary">
                        <i class="fe fe-arrow-left me-2"></i>목록으로
                    </a>
                </div>
            </div>
        </div>
    </section>


    <!-- 등록 폼 -->
    <div class="row">
        <div class="col-lg-8">
            <form action="{{ route('admin.' . $routePrefix . '.store') }}" method="POST" id="partnerUserForm">
                @csrf

                {{-- 샤딩 회원 검색 --}}
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
                                                {{ old('partner_type_id') == $type->id ? 'selected' : '' }}
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
                                        class="form-control @error('partner_tier_id') is-invalid @enderror">
                                    <option value="">등급을 선택하세요</option>
                                    @foreach($partnerTiers as $tier)
                                        <option value="{{ $tier->id }}"
                                                {{ old('partner_tier_id') == $tier->id ? 'selected' : '' }}
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
                                        class="form-control @error('status') is-invalid @enderror">
                                    <option value="pending" {{ old('status', 'pending') == 'pending' ? 'selected' : '' }}>대기</option>
                                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>승인</option>
                                    <option value="suspended" {{ old('status') == 'suspended' ? 'selected' : '' }}>정지</option>
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
                                    <option value="percentage" {{ old('individual_commission_type', 'percentage') == 'percentage' ? 'selected' : '' }}>퍼센트</option>
                                    <option value="fixed_amount" {{ old('individual_commission_type') == 'fixed_amount' ? 'selected' : '' }}>고정금액</option>
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
                                       value="{{ old('individual_commission_rate', 0) }}"
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
                                       value="{{ old('individual_commission_amount', 0) }}"
                                       min="0">
                                @error('individual_commission_amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label for="commission_calculation_preview" class="form-label">총 수수료율</label>
                                <div id="commission_calculation_preview" class="form-control bg-light border-2 border-primary text-center fw-bold text-primary">
                                    0.0%
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
                                      placeholder="개별 수수료 설정 사유나 특이사항을 입력하세요...">{{ old('commission_notes') }}</textarea>
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
                                       value="{{ old('partner_joined_at', date('Y-m-d')) }}">
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
                                       value="{{ old('tier_assigned_at', date('Y-m-d')) }}">
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
                                       value="{{ old('last_performance_review_at') }}">
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
                                       value="{{ old('total_completed_jobs', 0) }}"
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
                                       value="{{ old('average_rating', 0) }}"
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
                                       value="{{ old('punctuality_rate', 0) }}"
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
                                       value="{{ old('satisfaction_rate', 0) }}"
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
                                      placeholder='{"specializations": ["웹개발", "모바일앱"], "certifications": ["정보처리기사"], "experience_years": 5}'>{{ old('profile_data') }}</textarea>
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
                                      placeholder="파트너에 대한 추가 정보나 특이사항을 입력하세요...">{{ old('admin_notes') }}</textarea>
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
                                      placeholder="상태 변경 사유를 입력하세요...">{{ old('status_reason') }}</textarea>
                            @error('status_reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 버튼 그룹 -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.' . $routePrefix . '.index') }}" class="btn btn-secondary">
                                <i class="fe fe-x me-1"></i>취소
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fe fe-save me-1"></i>등록
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            <!-- 등록 가이드 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fe fe-help-circle me-2"></i>등록 가이드
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-primary">1. 사용자 검색</h6>
                        <p class="small text-muted">이메일로 샤딩된 사용자 테이블에서 등록할 사용자를 검색하세요.</p>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-primary">2. 파트너 등급 선택</h6>
                        <p class="small text-muted">사용자의 경력과 역량에 맞는 등급을 선택하세요.</p>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-primary">3. 초기 상태 설정</h6>
                        <p class="small text-muted">
                            • <strong>대기:</strong> 검토 중<br>
                            • <strong>활성:</strong> 즉시 활동 가능<br>
                            • <strong>비활성:</strong> 임시 비활성화
                        </p>
                    </div>
                    <div>
                        <h6 class="text-primary">4. 성과 정보</h6>
                        <p class="small text-muted">기존 활동 이력이 있다면 성과 데이터를 입력하세요.</p>
                    </div>
                </div>
            </div>

            <!-- 파트너 등급 정보 -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fe fe-award me-2"></i>파트너 등급 정보
                    </h6>
                </div>
                <div class="card-body">
                    @foreach($partnerTiers as $tier)
                    <div class="mb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-info">{{ $tier->tier_name }}</span>
                            <small class="text-muted">{{ $tier->commission_rate }}% 수수료</small>
                        </div>
                        @if($tier->description)
                        <small class="text-muted d-block">{{ $tier->description }}</small>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// 상태 변경시 사유 입력 필드 표시/숨김 (간단한 워크플로우: 대기->승인->정지)
document.getElementById('status').addEventListener('change', function() {
    const statusReasonGroup = document.getElementById('status_reason_group');
    const selectedStatus = this.value;

    // 승인 또는 정지 상태로 변경시 사유 필드 표시
    if (selectedStatus === 'active' || selectedStatus === 'suspended') {
        statusReasonGroup.style.display = 'block';
    } else {
        statusReasonGroup.style.display = 'none';
    }
});

// 중복된 JavaScript 제거됨 - search_user.blade.php partial에서 처리

// JSON 유효성 검사
document.getElementById('profile_data').addEventListener('blur', function() {
    const value = this.value.trim();

    if (value && value !== '') {
        try {
            JSON.parse(value);
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        } catch (e) {
            this.classList.remove('is-valid');
            this.classList.add('is-invalid');

            // 피드백 메시지 추가
            let feedback = this.parentNode.querySelector('.invalid-feedback');
            if (!feedback) {
                feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                this.parentNode.appendChild(feedback);
            }
            feedback.textContent = 'JSON 형식이 올바르지 않습니다.';
        }
    } else {
        this.classList.remove('is-invalid', 'is-valid');
    }
});

// 폼 제출 전 유효성 검사
document.getElementById('partnerUserForm').addEventListener('submit', function(e) {
    const userId = document.getElementById('user_id').value;

    if (!userId) {
        e.preventDefault();
        alert('사용자를 먼저 검색하고 선택해주세요.');
        return false;
    }

    // JSON 유효성 검사
    const profileData = document.getElementById('profile_data').value.trim();

    if (profileData && profileData !== '') {
        try {
            JSON.parse(profileData);
        } catch (error) {
            e.preventDefault();
            alert('프로필 데이터의 JSON 형식이 올바르지 않습니다.');
            document.getElementById('profile_data').focus();
            return false;
        }
    }
});

// 개별 수수료 타입 변경시 필드 활성화/비활성화
document.getElementById('individual_commission_type').addEventListener('change', function() {
    const type = this.value;
    const rateField = document.getElementById('individual_commission_rate');
    const amountField = document.getElementById('individual_commission_amount');

    if (type === 'percentage') {
        rateField.disabled = false;
        amountField.disabled = true;
        amountField.value = 0;
    } else {
        rateField.disabled = true;
        rateField.value = 0;
        amountField.disabled = false;
    }

    updateCommissionPreview();
});

// 수수료 계산 미리보기 업데이트
function updateCommissionPreview() {
    const typeSelect = document.getElementById('partner_type_id');
    const tierSelect = document.getElementById('partner_tier_id');
    const individualType = document.getElementById('individual_commission_type').value;
    const individualRate = parseFloat(document.getElementById('individual_commission_rate').value) || 0;
    const preview = document.getElementById('commission_calculation_preview');

    let totalRate = 0;

    // 파트너 타입 수수료
    if (typeSelect.selectedOptions.length > 0) {
        const selectedType = typeSelect.selectedOptions[0];
        totalRate += parseFloat(selectedType.dataset.commissionRate) || 0;
    }

    // 파트너 등급 수수료
    if (tierSelect.selectedOptions.length > 0) {
        const selectedTier = tierSelect.selectedOptions[0];
        totalRate += parseFloat(selectedTier.dataset.commissionRate) || 0;
    }

    // 개별 수수료 (퍼센트인 경우만)
    if (individualType === 'percentage') {
        totalRate += individualRate;
    }

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

// 수수료 관련 필드 변경시 미리보기 업데이트
document.getElementById('partner_type_id').addEventListener('change', updateCommissionPreview);
document.getElementById('partner_tier_id').addEventListener('change', updateCommissionPreview);
document.getElementById('individual_commission_rate').addEventListener('input', updateCommissionPreview);

// 페이지 로드시 상태에 따른 사유 필드 표시
document.addEventListener('DOMContentLoaded', function() {
    const statusSelect = document.getElementById('status');
    statusSelect.dispatchEvent(new Event('change'));

    // 개별 수수료 타입 초기 설정
    const commissionTypeSelect = document.getElementById('individual_commission_type');
    commissionTypeSelect.dispatchEvent(new Event('change'));

    // 초기 수수료 미리보기
    updateCommissionPreview();
});
</script>
@endpush

@push('styles')
<style>
.cursor-pointer {
    cursor: pointer;
}

.list-group-item:hover {
    background-color: #f8f9fa;
}

.alert {
    margin-bottom: 1rem;
}

#search_results {
    max-height: 300px;
    overflow-y: auto;
}

.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.is-valid {
    border-color: #198754;
}

.is-invalid {
    border-color: #dc3545;
}
</style>
@endpush
