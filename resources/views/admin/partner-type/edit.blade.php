@extends('jiny-partner::layouts.admin.sidebar')

@section('title', $title . ' 수정')

@section('content')
<div class="container-fluid">
    <!-- 헤더 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">{{ $item->type_name }} 수정</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">관리자</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.partner.dashboard') }}">파트너</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.' . $routePrefix . '.index') }}">타입 관리</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.' . $routePrefix . '.show', $item->id) }}">{{ $item->type_name }}</a></li>
                            <li class="breadcrumb-item active">수정</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('admin.' . $routePrefix . '.show', $item->id) }}" class="btn btn-outline-secondary">
                        <i class="fe fe-arrow-left me-2"></i>돌아가기
                    </a>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.' . $routePrefix . '.update', $item->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-8">
                <!-- 기본 정보 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fe fe-info me-2"></i>기본 정보
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="type_code" class="form-label">타입 코드 <span class="text-danger">*</span></label>
                                    <input type="text"
                                           class="form-control @error('type_code') is-invalid @enderror"
                                           id="type_code"
                                           name="type_code"
                                           value="{{ old('type_code', $item->type_code) }}"
                                           placeholder="예: SALES, TECH_SUPPORT"
                                           required>
                                    @error('type_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="type_name" class="form-label">타입 이름 <span class="text-danger">*</span></label>
                                    <input type="text"
                                           class="form-control @error('type_name') is-invalid @enderror"
                                           id="type_name"
                                           name="type_name"
                                           value="{{ old('type_name', $item->type_name) }}"
                                           placeholder="예: 영업 전문 파트너"
                                           required>
                                    @error('type_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">설명</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description"
                                      name="description"
                                      rows="3"
                                      placeholder="이 타입의 역할과 특징을 설명해주세요">{{ old('description', $item->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="icon" class="form-label">아이콘</label>
                                    <input type="text"
                                           class="form-control @error('icon') is-invalid @enderror"
                                           id="icon"
                                           name="icon"
                                           value="{{ old('icon', $item->icon) }}"
                                           placeholder="fe-users">
                                    @error('icon')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Feather Icons 클래스명 (예: fe-users)</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="color" class="form-label">색상</label>
                                    <input type="color"
                                           class="form-control @error('color') is-invalid @enderror"
                                           id="color"
                                           name="color"
                                           value="{{ old('color', $item->color) }}">
                                    @error('color')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="sort_order" class="form-label">정렬 순서</label>
                                    <input type="number"
                                           class="form-control @error('sort_order') is-invalid @enderror"
                                           id="sort_order"
                                           name="sort_order"
                                           value="{{ old('sort_order', $item->sort_order) }}"
                                           min="0">
                                    @error('sort_order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 전문성 설정 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fe fe-star me-2"></i>전문성 설정
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="specialties" class="form-label">전문 분야</label>
                            <div class="row">
                                @foreach($defaultSpecialties as $key => $label)
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   name="specialties[]"
                                                   value="{{ $key }}"
                                                   id="specialty_{{ $key }}"
                                                   {{ in_array($key, old('specialties', $item->specialties ?? [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="specialty_{{ $key }}">
                                                {{ $label }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <small class="form-text text-muted">이 타입 파트너의 전문 분야를 선택하세요</small>
                        </div>

                        <div class="mb-3">
                            <label for="required_skills" class="form-label">필수 스킬</label>
                            <div class="row">
                                @foreach($defaultSkills as $key => $label)
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   name="required_skills[]"
                                                   value="{{ $key }}"
                                                   id="skill_{{ $key }}"
                                                   {{ in_array($key, old('required_skills', $item->required_skills ?? [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="skill_{{ $key }}">
                                                {{ $label }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="certifications" class="form-label">관련 자격증</label>
                            <textarea class="form-control @error('certifications') is-invalid @enderror"
                                      id="certifications"
                                      name="certifications"
                                      rows="2"
                                      placeholder="쉼표로 구분하여 입력 (예: 정보처리기사, AWS 자격증)">{{ old('certifications', is_array($item->certifications) ? implode(', ', $item->certifications) : $item->certifications) }}</textarea>
                            @error('certifications')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- 성과 목표 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fe fe-target me-2"></i>성과 목표
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="min_baseline_sales" class="form-label">목표 매출액 (원)</label>
                                    <input type="number"
                                           class="form-control @error('min_baseline_sales') is-invalid @enderror"
                                           id="min_baseline_sales"
                                           name="min_baseline_sales"
                                           value="{{ old('min_baseline_sales', $item->min_baseline_sales) }}"
                                           min="0"
                                           step="10000">
                                    @error('min_baseline_sales')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="min_baseline_cases" class="form-label">목표 지원 건수</label>
                                    <input type="number"
                                           class="form-control @error('min_baseline_cases') is-invalid @enderror"
                                           id="min_baseline_cases"
                                           name="min_baseline_cases"
                                           value="{{ old('min_baseline_cases', $item->min_baseline_cases) }}"
                                           min="0">
                                    @error('min_baseline_cases')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- 성과 기준 섹션 -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="min_baseline_clients" class="form-label">최소 고객 수</label>
                                    <input type="number"
                                           class="form-control @error('min_baseline_clients') is-invalid @enderror"
                                           id="min_baseline_clients"
                                           name="min_baseline_clients"
                                           value="{{ old('min_baseline_clients', $item->min_baseline_clients ?? 0) }}"
                                           min="0">
                                    @error('min_baseline_clients')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">타입별 최소 관리 고객 수</small>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="baseline_quality_score" class="form-label">최소 품질 점수</label>
                            <input type="number"
                                   class="form-control @error('baseline_quality_score') is-invalid @enderror"
                                   id="baseline_quality_score"
                                   name="baseline_quality_score"
                                   value="{{ old('baseline_quality_score', $item->baseline_quality_score ?? 80) }}"
                                   min="0"
                                   max="100"
                                   step="0.1">
                            @error('baseline_quality_score')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">타입별 최소 품질 점수 기준 (0-100점)</small>
                        </div>


                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="onboarding_period_months" class="form-label">신규 파트너 적응 기간 (월)</label>
                                    <input type="number"
                                           class="form-control @error('onboarding_period_months') is-invalid @enderror"
                                           id="onboarding_period_months"
                                           name="onboarding_period_months"
                                           value="{{ old('onboarding_period_months', $item->onboarding_period_months ?? 3) }}"
                                           min="1"
                                           max="12">
                                    @error('onboarding_period_months')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">신규 파트너의 적응 기간 (1-12개월)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="onboarding_target_ratio" class="form-label">신규 파트너 목표 비율</label>
                                    <input type="number"
                                           class="form-control @error('onboarding_target_ratio') is-invalid @enderror"
                                           id="onboarding_target_ratio"
                                           name="onboarding_target_ratio"
                                           value="{{ old('onboarding_target_ratio', $item->onboarding_target_ratio ?? 0.7) }}"
                                           min="0.1"
                                           max="1.0"
                                           step="0.1">
                                    @error('onboarding_target_ratio')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">신규 파트너의 목표 비율 (0.7 = 70%)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 수수료 및 비용 설정 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fe fe-dollar-sign me-2"></i>수수료 및 비용 설정
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- 수수료 설정 -->
                        <div class="mb-4">
                            <h6 class="mb-3">수수료 설정</h6>

                            <div class="mb-3">
                                <label for="default_commission_type" class="form-label">수수료 타입 <span class="text-danger">*</span></label>
                                <select class="form-control @error('default_commission_type') is-invalid @enderror"
                                        id="default_commission_type"
                                        name="default_commission_type"
                                        onchange="toggleCommissionFields()" required>
                                    <option value="percentage" {{ old('default_commission_type', $item->default_commission_type) === 'percentage' ? 'selected' : '' }}>퍼센트 기반 (%)</option>
                                    <option value="fixed_amount" {{ old('default_commission_type', $item->default_commission_type) === 'fixed_amount' ? 'selected' : '' }}>고정 금액 (원)</option>
                                </select>
                                @error('default_commission_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6" id="percentage_fields">
                                    <div class="mb-3">
                                        <label for="default_commission_rate" class="form-label">기본 수수료율 (%)</label>
                                        <input type="number"
                                               class="form-control @error('default_commission_rate') is-invalid @enderror"
                                               id="default_commission_rate"
                                               name="default_commission_rate"
                                               value="{{ old('default_commission_rate', $item->default_commission_rate) }}"
                                               min="0" max="100" step="0.1">
                                        @error('default_commission_rate')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6" id="amount_fields" style="display: none;">
                                    <div class="mb-3">
                                        <label for="default_commission_amount" class="form-label">기본 수수료 금액 (원)</label>
                                        <input type="number"
                                               class="form-control @error('default_commission_amount') is-invalid @enderror"
                                               id="default_commission_amount"
                                               name="default_commission_amount"
                                               value="{{ old('default_commission_amount', $item->default_commission_amount) }}"
                                               min="0" step="1000">
                                        @error('default_commission_amount')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="commission_notes" class="form-label">수수료 관련 참고사항</label>
                                <textarea class="form-control @error('commission_notes') is-invalid @enderror"
                                          id="commission_notes"
                                          name="commission_notes"
                                          rows="2"
                                          placeholder="수수료 관련 특별 조건이나 참고사항을 입력하세요">{{ old('commission_notes', $item->commission_notes) }}</textarea>
                                @error('commission_notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- 비용 설정 -->
                        <div class="mb-4">
                            <h6 class="mb-3">비용 설정</h6>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="registration_fee" class="form-label">등록비 (원)</label>
                                        <input type="number"
                                               class="form-control @error('registration_fee') is-invalid @enderror"
                                               id="registration_fee"
                                               name="registration_fee"
                                               value="{{ old('registration_fee', $item->registration_fee) }}"
                                               min="0" step="10000">
                                        @error('registration_fee')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="monthly_maintenance_fee" class="form-label">월 유지비 (원)</label>
                                        <input type="number"
                                               class="form-control @error('monthly_maintenance_fee') is-invalid @enderror"
                                               id="monthly_maintenance_fee"
                                               name="monthly_maintenance_fee"
                                               value="{{ old('monthly_maintenance_fee', $item->monthly_maintenance_fee) }}"
                                               min="0" step="1000">
                                        @error('monthly_maintenance_fee')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="annual_maintenance_fee" class="form-label">연 유지비 (원)</label>
                                        <input type="number"
                                               class="form-control @error('annual_maintenance_fee') is-invalid @enderror"
                                               id="annual_maintenance_fee"
                                               name="annual_maintenance_fee"
                                               value="{{ old('annual_maintenance_fee', $item->annual_maintenance_fee) }}"
                                               min="0" step="10000">
                                        @error('annual_maintenance_fee')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="fee_waiver_available"
                                           name="fee_waiver_available"
                                           {{ old('fee_waiver_available', $item->fee_waiver_available) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="fee_waiver_available">
                                        비용 면제 가능
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="fee_structure_notes" class="form-label">비용 구조 관련 참고사항</label>
                                <textarea class="form-control @error('fee_structure_notes') is-invalid @enderror"
                                          id="fee_structure_notes"
                                          name="fee_structure_notes"
                                          rows="2"
                                          placeholder="비용 구조나 면제 조건 등에 대한 참고사항을 입력하세요">{{ old('fee_structure_notes', $item->fee_structure_notes) }}</textarea>
                                @error('fee_structure_notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="col-lg-4">
                <!-- 설정 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fe fe-settings me-2"></i>설정
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="is_active"
                                   name="is_active"
                                   {{ old('is_active', $item->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                활성 상태
                            </label>
                        </div>
                    </div>
                </div>

                <!-- 교육 및 인증 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fe fe-book me-2"></i>교육 및 인증
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="training_hours_required" class="form-label">필수 교육 시간</label>
                            <input type="number"
                                   class="form-control @error('training_hours_required') is-invalid @enderror"
                                   id="training_hours_required"
                                   name="training_hours_required"
                                   value="{{ old('training_hours_required', $item->training_hours_required) }}"
                                   min="0">
                            @error('training_hours_required')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="certification_valid_until" class="form-label">인증 유효기간</label>
                            <input type="date"
                                   class="form-control @error('certification_valid_until') is-invalid @enderror"
                                   id="certification_valid_until"
                                   name="certification_valid_until"
                                   value="{{ old('certification_valid_until', $item->certification_valid_until ? $item->certification_valid_until->format('Y-m-d') : '') }}">
                            @error('certification_valid_until')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="training_requirements" class="form-label">교육 요구사항</label>
                            <textarea class="form-control @error('training_requirements') is-invalid @enderror"
                                      id="training_requirements"
                                      name="training_requirements"
                                      rows="3"
                                      placeholder="쉼표로 구분하여 입력">{{ old('training_requirements', is_array($item->training_requirements) ? implode(', ', $item->training_requirements) : $item->training_requirements) }}</textarea>
                            @error('training_requirements')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- 관리자 메모 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fe fe-file-text me-2"></i>관리자 메모
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <textarea class="form-control @error('admin_notes') is-invalid @enderror"
                                      id="admin_notes"
                                      name="admin_notes"
                                      rows="4"
                                      placeholder="관리자용 메모를 입력하세요">{{ old('admin_notes', $item->admin_notes) }}</textarea>
                            @error('admin_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- 액션 버튼 -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fe fe-save me-2"></i>수정 저장
                            </button>
                            <a href="{{ route('admin.' . $routePrefix . '.show', $item->id) }}" class="btn btn-outline-secondary">
                                <i class="fe fe-x me-2"></i>취소
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
// 수수료 타입에 따른 필드 표시/숨김
function toggleCommissionFields() {
    const commissionType = document.getElementById('default_commission_type').value;
    const percentageFields = document.getElementById('percentage_fields');
    const amountFields = document.getElementById('amount_fields');

    if (commissionType === 'percentage') {
        percentageFields.style.display = 'block';
        amountFields.style.display = 'none';

        // 퍼센트 필드를 필수로 설정
        document.getElementById('default_commission_rate').required = true;
        document.getElementById('default_commission_amount').required = false;
    } else {
        percentageFields.style.display = 'none';
        amountFields.style.display = 'block';

        // 금액 필드를 필수로 설정
        document.getElementById('default_commission_rate').required = false;
        document.getElementById('default_commission_amount').required = true;
    }
}

// 페이지 로드시 초기 설정
document.addEventListener('DOMContentLoaded', function() {
    toggleCommissionFields();
});
</script>
@endpush