@extends('jiny-partner::layouts.admin.sidebar')

@section('title', $title . ' 수정')

@section('content')
<div class="container-fluid">
    <!-- 헤더 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">{{ $item->tier_name }} 수정</h2>
                    <p class="text-muted mb-0">파트너 등급 정보를 수정합니다</p>
                </div>
                <div>
                    <a href="{{ route('admin.' . $routePrefix . '.show', $item->id) }}" class="btn btn-outline-secondary">
                        <i class="fe fe-arrow-left me-2"></i>상세보기
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 등급 수정 폼 -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">기본 정보</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.' . $routePrefix . '.update', $item->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group mb-3">
                                    <label for="tier_name" class="form-label">등급명 <span class="text-danger">*</span></label>
                                    <input type="text"
                                           id="tier_name"
                                           name="tier_name"
                                           class="form-control @error('tier_name') is-invalid @enderror"
                                           placeholder="파트너 등급명을 입력하세요"
                                           value="{{ old('tier_name', $item->tier_name) }}"
                                           required>
                                    @error('tier_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="tier_code" class="form-label">등급 코드 <span class="text-danger">*</span></label>
                                    <input type="text"
                                           id="tier_code"
                                           name="tier_code"
                                           class="form-control @error('tier_code') is-invalid @enderror"
                                           placeholder="등급 코드"
                                           value="{{ old('tier_code', $item->tier_code) }}"
                                           required>
                                    @error('tier_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">고유한 등급 식별 코드입니다.</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="description" class="form-label">설명</label>
                            <textarea id="description"
                                      name="description"
                                      class="form-control @error('description') is-invalid @enderror"
                                      rows="4"
                                      placeholder="파트너 등급에 대한 설명을 입력하세요">{{ old('description', $item->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="commission_rate" class="form-label">수수료율 (%) <span class="text-danger">*</span></label>
                                    <input type="number"
                                           id="commission_rate"
                                           name="commission_rate"
                                           class="form-control @error('commission_rate') is-invalid @enderror"
                                           placeholder="65.00"
                                           value="{{ old('commission_rate', $item->commission_rate) }}"
                                           min="0"
                                           max="100"
                                           step="0.01"
                                           required>
                                    @error('commission_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">파트너가 받게 되는 수수료 비율입니다.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="priority_level" class="form-label">우선순위 <span class="text-danger">*</span></label>
                                    <input type="number"
                                           id="priority_level"
                                           name="priority_level"
                                           class="form-control @error('priority_level') is-invalid @enderror"
                                           placeholder="1"
                                           value="{{ old('priority_level', $item->priority_level) }}"
                                           min="1"
                                           required>
                                    @error('priority_level')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">낮은 숫자일수록 높은 우선순위입니다.</small>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h6 class="mb-3">성과 기준</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="min_completed_jobs" class="form-label">최소 완료 작업 수</label>
                                    <input type="number"
                                           id="min_completed_jobs"
                                           name="min_completed_jobs"
                                           class="form-control @error('min_completed_jobs') is-invalid @enderror"
                                           placeholder="0"
                                           value="{{ old('min_completed_jobs', $item->min_completed_jobs) }}"
                                           min="0">
                                    @error('min_completed_jobs')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="min_rating" class="form-label">최소 평점</label>
                                    <input type="number"
                                           id="min_rating"
                                           name="min_rating"
                                           class="form-control @error('min_rating') is-invalid @enderror"
                                           placeholder="0"
                                           value="{{ old('min_rating', $item->min_rating) }}"
                                           min="0"
                                           max="5"
                                           step="0.01">
                                    @error('min_rating')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="min_punctuality_rate" class="form-label">최소 시간 준수율 (%)</label>
                                    <input type="number"
                                           id="min_punctuality_rate"
                                           name="min_punctuality_rate"
                                           class="form-control @error('min_punctuality_rate') is-invalid @enderror"
                                           placeholder="0"
                                           value="{{ old('min_punctuality_rate', $item->min_punctuality_rate) }}"
                                           min="0"
                                           max="100"
                                           step="0.01">
                                    @error('min_punctuality_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="min_satisfaction_rate" class="form-label">최소 고객 만족도 (%)</label>
                                    <input type="number"
                                           id="min_satisfaction_rate"
                                           name="min_satisfaction_rate"
                                           class="form-control @error('min_satisfaction_rate') is-invalid @enderror"
                                           placeholder="0"
                                           value="{{ old('min_satisfaction_rate', $item->min_satisfaction_rate) }}"
                                           min="0"
                                           max="100"
                                           step="0.01">
                                    @error('min_satisfaction_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="sort_order" class="form-label">정렬 순서</label>
                                    <input type="number"
                                           id="sort_order"
                                           name="sort_order"
                                           class="form-control @error('sort_order') is-invalid @enderror"
                                           placeholder="0"
                                           value="{{ old('sort_order', $item->sort_order) }}"
                                           min="0">
                                    @error('sort_order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">숫자가 작을수록 먼저 표시됩니다.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-4">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <label for="is_active" class="form-label mb-1">등급 활성화</label>
                                            <small class="text-muted d-block">비활성화하면 파트너에게 할당되지 않습니다.</small>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   role="switch"
                                                   id="is_active"
                                                   name="is_active"
                                                   value="1"
                                                   {{ old('is_active', $item->is_active) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_active"></label>
                                        </div>
                                    </div>
                                    @error('is_active')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h6 class="mb-3">상세 설정</h6>

                        <div class="form-group mb-3">
                            <label for="requirements" class="form-label">요구사항 (JSON)</label>
                            <textarea id="requirements"
                                      name="requirements"
                                      class="form-control @error('requirements') is-invalid @enderror"
                                      rows="4"
                                      placeholder='{"min_experience_months": 6, "required_certifications": ["기본 자격증"]}'>{{ old('requirements', $item->requirements ? json_encode($item->requirements, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '') }}</textarea>
                            @error('requirements')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">JSON 형식으로 등급 요구사항을 입력하세요.</small>
                        </div>

                        <div class="form-group mb-4">
                            <label for="benefits" class="form-label">혜택 (JSON)</label>
                            <textarea id="benefits"
                                      name="benefits"
                                      class="form-control @error('benefits') is-invalid @enderror"
                                      rows="4"
                                      placeholder='{"maximum_concurrent_jobs": 4, "support_response_time": "12시간"}'>{{ old('benefits', $item->benefits ? json_encode($item->benefits, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '') }}</textarea>
                            @error('benefits')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">JSON 형식으로 등급별 혜택을 입력하세요.</small>
                        </div>

                        <hr>

                        <h6 class="mb-3">계층 관리 설정</h6>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <label for="can_recruit" class="form-label mb-1">모집 권한</label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   role="switch"
                                                   id="can_recruit"
                                                   name="can_recruit"
                                                   value="1"
                                                   {{ old('can_recruit', $item->can_recruit) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="can_recruit"></label>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">하위 파트너 모집 가능 여부</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="max_children" class="form-label">최대 하위 파트너</label>
                                    <input type="number"
                                           id="max_children"
                                           name="max_children"
                                           class="form-control @error('max_children') is-invalid @enderror"
                                           placeholder="무제한은 999"
                                           value="{{ old('max_children', $item->max_children ?? 10) }}"
                                           min="0"
                                           max="999">
                                    @error('max_children')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="max_depth" class="form-label">최대 계층 깊이</label>
                                    <input type="number"
                                           id="max_depth"
                                           name="max_depth"
                                           class="form-control @error('max_depth') is-invalid @enderror"
                                           placeholder="5"
                                           value="{{ old('max_depth', $item->max_depth ?? 5) }}"
                                           min="1"
                                           max="20">
                                    @error('max_depth')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h6 class="mb-3">비용 관리 설정</h6>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <label for="cost_management_enabled" class="form-label mb-1">비용 관리 활성화</label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   role="switch"
                                                   id="cost_management_enabled"
                                                   name="cost_management_enabled"
                                                   value="1"
                                                   {{ old('cost_management_enabled', $item->cost_management_enabled) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="cost_management_enabled"></label>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">이 등급에 대한 비용 관리를 활성화합니다</small>
                                </div>
                            </div>
                        </div>

                        <div id="cost-management-fields" style="display: {{ old('cost_management_enabled', $item->cost_management_enabled) ? 'block' : 'none' }};">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="registration_fee" class="form-label">가입비용</label>
                                        <div class="input-group">
                                            <input type="number"
                                                   id="registration_fee"
                                                   name="registration_fee"
                                                   class="form-control @error('registration_fee') is-invalid @enderror"
                                                   placeholder="0"
                                                   value="{{ old('registration_fee', $item->registration_fee ?? 0) }}"
                                                   min="0"
                                                   step="100">
                                            <span class="input-group-text">원</span>
                                        </div>
                                        @error('registration_fee')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="activation_fee" class="form-label">활성화 비용</label>
                                        <div class="input-group">
                                            <input type="number"
                                                   id="activation_fee"
                                                   name="activation_fee"
                                                   class="form-control @error('activation_fee') is-invalid @enderror"
                                                   placeholder="0"
                                                   value="{{ old('activation_fee', $item->activation_fee ?? 0) }}"
                                                   min="0"
                                                   step="100">
                                            <span class="input-group-text">원</span>
                                        </div>
                                        @error('activation_fee')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="monthly_maintenance_fee" class="form-label">월 유지비용</label>
                                        <div class="input-group">
                                            <input type="number"
                                                   id="monthly_maintenance_fee"
                                                   name="monthly_maintenance_fee"
                                                   class="form-control @error('monthly_maintenance_fee') is-invalid @enderror"
                                                   placeholder="0"
                                                   value="{{ old('monthly_maintenance_fee', $item->monthly_maintenance_fee ?? 0) }}"
                                                   min="0"
                                                   step="100">
                                            <span class="input-group-text">원/월</span>
                                        </div>
                                        @error('monthly_maintenance_fee')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="annual_maintenance_fee" class="form-label">연 유지비용</label>
                                        <div class="input-group">
                                            <input type="number"
                                                   id="annual_maintenance_fee"
                                                   name="annual_maintenance_fee"
                                                   class="form-control @error('annual_maintenance_fee') is-invalid @enderror"
                                                   placeholder="0"
                                                   value="{{ old('annual_maintenance_fee', $item->annual_maintenance_fee ?? 0) }}"
                                                   min="0"
                                                   step="1000">
                                            <span class="input-group-text">원/년</span>
                                        </div>
                                        @error('annual_maintenance_fee')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label for="service_fee_rate" class="form-label">서비스 이용료율</label>
                                        <div class="input-group">
                                            <input type="number"
                                                   id="service_fee_rate"
                                                   name="service_fee_rate"
                                                   class="form-control @error('service_fee_rate') is-invalid @enderror"
                                                   placeholder="0.00"
                                                   value="{{ old('service_fee_rate', $item->service_fee_rate ?? 0) }}"
                                                   min="0"
                                                   max="100"
                                                   step="0.01">
                                            <span class="input-group-text">%</span>
                                        </div>
                                        @error('service_fee_rate')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label for="platform_fee_rate" class="form-label">플랫폼 이용료율</label>
                                        <div class="input-group">
                                            <input type="number"
                                                   id="platform_fee_rate"
                                                   name="platform_fee_rate"
                                                   class="form-control @error('platform_fee_rate') is-invalid @enderror"
                                                   placeholder="0.00"
                                                   value="{{ old('platform_fee_rate', $item->platform_fee_rate ?? 0) }}"
                                                   min="0"
                                                   max="100"
                                                   step="0.01">
                                            <span class="input-group-text">%</span>
                                        </div>
                                        @error('platform_fee_rate')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <label for="transaction_fee_rate" class="form-label">거래 수수료율</label>
                                        <div class="input-group">
                                            <input type="number"
                                                   id="transaction_fee_rate"
                                                   name="transaction_fee_rate"
                                                   class="form-control @error('transaction_fee_rate') is-invalid @enderror"
                                                   placeholder="0.00"
                                                   value="{{ old('transaction_fee_rate', $item->transaction_fee_rate ?? 0) }}"
                                                   min="0"
                                                   max="100"
                                                   step="0.01">
                                            <span class="input-group-text">%</span>
                                        </div>
                                        @error('transaction_fee_rate')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.' . $routePrefix . '.show', $item->id) }}" class="btn btn-secondary">
                                <i class="fe fe-x me-2"></i>취소
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fe fe-save me-2"></i>수정 저장
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">현재 정보</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small">등급 코드</label>
                        <div><code class="bg-light px-2 py-1 rounded">{{ $item->tier_code }}</code></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">등급명</label>
                        <div><strong>{{ $item->tier_name }}</strong></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">수수료율</label>
                        <div><span class="badge bg-success fs-6">{{ $item->commission_rate }}%</span></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">우선순위</label>
                        <div><span class="badge bg-info fs-6">{{ $item->priority_level }}</span></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">상태</label>
                        <div>
                            @if($item->is_active)
                                <span class="badge bg-success">활성</span>
                            @else
                                <span class="badge bg-secondary">비활성</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">시스템 정보</h5>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <small class="text-muted">생성일시</small>
                        <div>{{ $item->created_at->format('Y-m-d H:i:s') }}</div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">수정일시</small>
                        <div>{{ $item->updated_at->format('Y-m-d H:i:s') }}</div>
                    </div>
                    @if($item->deleted_at)
                        <div class="mb-2">
                            <small class="text-muted">삭제일시</small>
                            <div class="text-danger">{{ $item->deleted_at->format('Y-m-d H:i:s') }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">안내</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning mb-0">
                        <h6><i class="fe fe-alert-triangle me-2"></i>수정 주의사항</h6>
                        <ul class="mb-0 small">
                            <li>수수료율 변경은 기존 파트너들에게 영향을 줍니다.</li>
                            <li>우선순위 변경은 작업 배정 순서에 영향을 줍니다.</li>
                            <li>성과 기준 변경은 자동 승급에 영향을 줍니다.</li>
                            <li>비활성화하면 신규 파트너 배정이 중단됩니다.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 등급명 변경시 코드 자동 업데이트 제안
    const tierNameInput = document.getElementById('tier_name');
    const tierCodeInput = document.getElementById('tier_code');

    tierNameInput.addEventListener('input', function() {
        // 사용자가 직접 코드를 수정했는지 확인
        if (!tierCodeInput.dataset.manuallyChanged) {
            const suggestedCode = this.value
                .toLowerCase()
                .replace(/[^a-z0-9가-힣\s-]/g, '')
                .replace(/\s+/g, '_')
                .trim();

            // 기존 코드와 다르면 제안
            if (suggestedCode !== tierCodeInput.value && suggestedCode !== '') {
                tierCodeInput.style.borderColor = '#28a745';
                tierCodeInput.title = `제안: ${suggestedCode}`;
            }
        }
    });

    tierCodeInput.addEventListener('input', function() {
        this.dataset.manuallyChanged = 'true';
        this.style.borderColor = '';
        this.title = '';
    });

    // 비용 관리 섹션 토글
    const costManagementToggle = document.getElementById('cost_management_enabled');
    const costManagementFields = document.getElementById('cost-management-fields');

    function toggleCostManagementFields() {
        if (costManagementToggle.checked) {
            costManagementFields.style.display = 'block';
        } else {
            costManagementFields.style.display = 'none';
        }
    }

    costManagementToggle.addEventListener('change', toggleCostManagementFields);
    toggleCostManagementFields(); // 초기 상태 설정
});
</script>
@endpush