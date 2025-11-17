@extends('jiny-partner::layouts.admin.sidebar')

@section('title', $title . ' 생성')

@section('content')
<div class="container-fluid">
    <!-- 헤더 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">{{ $title }} 생성</h2>
                    <p class="text-muted mb-0">새로운 파트너 등급을 생성합니다</p>
                </div>
                <div>
                    <a href="{{ route('admin.' . $routePrefix . '.index') }}" class="btn btn-outline-secondary">
                        <i class="fe fe-arrow-left me-2"></i>목록으로
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 등급 생성 폼 -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">기본 정보</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.' . $routePrefix . '.store') }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group mb-3">
                                    <label for="tier_name" class="form-label">등급명 <span class="text-danger">*</span></label>
                                    <input type="text"
                                           id="tier_name"
                                           name="tier_name"
                                           class="form-control @error('tier_name') is-invalid @enderror"
                                           placeholder="파트너 등급명을 입력하세요"
                                           value="{{ old('tier_name') }}"
                                           required>
                                    @error('tier_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="tier_code" class="form-label">등급 코드</label>
                                    <input type="text"
                                           id="tier_code"
                                           name="tier_code"
                                           class="form-control @error('tier_code') is-invalid @enderror"
                                           placeholder="자동 생성됩니다"
                                           value="{{ old('tier_code') }}">
                                    @error('tier_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">비워두면 등급명으로 자동 생성됩니다.</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="description" class="form-label">설명</label>
                            <textarea id="description"
                                      name="description"
                                      class="form-control @error('description') is-invalid @enderror"
                                      rows="4"
                                      placeholder="파트너 등급에 대한 설명을 입력하세요">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 우선순위 설정 -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="priority_level" class="form-label">우선순위 <span class="text-danger">*</span></label>
                                    <input type="number"
                                           id="priority_level"
                                           name="priority_level"
                                           class="form-control @error('priority_level') is-invalid @enderror"
                                           placeholder="10"
                                           value="{{ old('priority_level', 10) }}"
                                           min="1"
                                           max="99"
                                           required>
                                    @error('priority_level')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">낮을수록 높은 등급입니다.</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="sort_order" class="form-label">정렬 순서</label>
                                    <input type="number"
                                           id="sort_order"
                                           name="sort_order"
                                           class="form-control @error('sort_order') is-invalid @enderror"
                                           placeholder="자동 설정"
                                           value="{{ old('sort_order', 0) }}"
                                           min="0"
                                           max="999">
                                    @error('sort_order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">0이면 우선순위로 자동 정렬됩니다.</small>
                                </div>
                            </div>
                        </div>


                        <!-- 수수료 설정 -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="commission_type" class="form-label">수수료 타입 <span class="text-danger">*</span></label>
                                    <select id="commission_type"
                                            name="commission_type"
                                            class="form-select @error('commission_type') is-invalid @enderror"
                                            onchange="toggleCommissionFields()"
                                            required>
                                        <option value="percentage" {{ old('commission_type', 'percentage') === 'percentage' ? 'selected' : '' }}>
                                            퍼센트 (%)
                                        </option>
                                        <option value="fixed_amount" {{ old('commission_type') === 'fixed_amount' ? 'selected' : '' }}>
                                            고정 금액 (원)
                                        </option>
                                    </select>
                                    @error('commission_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">수수료 계산 방식을 선택하세요.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div id="commission_rate_field" class="form-group mb-3">
                                    <label for="commission_rate" class="form-label">수수료율 (%) <span class="text-danger">*</span></label>
                                    <input type="number"
                                           id="commission_rate"
                                           name="commission_rate"
                                           class="form-control @error('commission_rate') is-invalid @enderror"
                                           placeholder="65.00"
                                           value="{{ old('commission_rate') }}"
                                           min="0"
                                           max="100"
                                           step="0.01">
                                    @error('commission_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div id="commission_rate_help">
                                        <small class="form-text text-muted">파트너가 받게 되는 수수료 비율입니다.</small>
                                        <div id="max_rate_warning" class="text-warning small mt-1" style="display: none;"></div>
                                    </div>
                                </div>

                                <div id="commission_amount_field" class="form-group mb-3" style="display: none;">
                                    <label for="commission_amount" class="form-label">수수료 금액 (원) <span class="text-danger">*</span></label>
                                    <input type="number"
                                           id="commission_amount"
                                           name="commission_amount"
                                           class="form-control @error('commission_amount') is-invalid @enderror"
                                           placeholder="50000"
                                           value="{{ old('commission_amount') }}"
                                           min="0"
                                           step="1000">
                                    @error('commission_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">거래당 지급되는 고정 수수료 금액입니다.</small>
                                </div>
                            </div>
                        </div>


                        <hr>

                        <h6 class="mb-3">비용 관리 설정</h6>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="registration_fee" class="form-label">가입비</label>
                                    <div class="input-group">
                                        <input type="number"
                                               id="registration_fee"
                                               name="registration_fee"
                                               class="form-control @error('registration_fee') is-invalid @enderror"
                                               placeholder="0"
                                               value="{{ old('registration_fee', 0) }}"
                                               min="0"
                                               step="1000">
                                        <span class="input-group-text">원</span>
                                    </div>
                                    @error('registration_fee')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="monthly_fee" class="form-label">월 유지비</label>
                                    <div class="input-group">
                                        <input type="number"
                                               id="monthly_fee"
                                               name="monthly_fee"
                                               class="form-control @error('monthly_fee') is-invalid @enderror"
                                               placeholder="0"
                                               value="{{ old('monthly_fee', 0) }}"
                                               min="0"
                                               step="1000">
                                        <span class="input-group-text">원/월</span>
                                    </div>
                                    @error('monthly_fee')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="annual_fee" class="form-label">연 유지비</label>
                                    <div class="input-group">
                                        <input type="number"
                                               id="annual_fee"
                                               name="annual_fee"
                                               class="form-control @error('annual_fee') is-invalid @enderror"
                                               placeholder="0"
                                               value="{{ old('annual_fee', 0) }}"
                                               min="0"
                                               step="10000">
                                        <span class="input-group-text">원/년</span>
                                    </div>
                                    @error('annual_fee')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <label for="fee_waiver_available" class="form-label mb-1">비용 면제 가능</label>
                                            <small class="text-muted d-block">성과 우수자 등에게 비용 면제 가능 여부</small>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   role="switch"
                                                   id="fee_waiver_available"
                                                   name="fee_waiver_available"
                                                   value="1"
                                                   {{ old('fee_waiver_available') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="fee_waiver_available"></label>
                                        </div>
                                    </div>
                                    @error('fee_waiver_available')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
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
                                                   {{ old('is_active', true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_active"></label>
                                        </div>
                                    </div>
                                    @error('is_active')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label for="fee_structure_notes" class="form-label">비용 구조 특별 조건</label>
                                    <textarea id="fee_structure_notes"
                                              name="fee_structure_notes"
                                              class="form-control @error('fee_structure_notes') is-invalid @enderror"
                                              rows="3"
                                              placeholder="비용 면제 정책, 할인 조건 등 특별 사항을 입력하세요">{{ old('fee_structure_notes') }}</textarea>
                                    @error('fee_structure_notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
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
                                      placeholder='{"min_experience_months": 6, "required_certifications": ["기본 자격증"]}'>{{ old('requirements') }}</textarea>
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
                                      placeholder='{"maximum_concurrent_jobs": 4, "support_response_time": "12시간"}'>{{ old('benefits') }}</textarea>
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
                                                   {{ old('can_recruit') ? 'checked' : '' }}>
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
                                           value="{{ old('max_children', 10) }}"
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
                                           value="{{ old('max_depth', 5) }}"
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
                                                   {{ old('cost_management_enabled') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="cost_management_enabled"></label>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">이 등급에 대한 비용 관리를 활성화합니다</small>
                                </div>
                            </div>
                        </div>

                        <div id="cost-management-fields" style="display: none;">
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
                                                   value="{{ old('registration_fee', 0) }}"
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
                                                   value="{{ old('activation_fee', 0) }}"
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
                                                   value="{{ old('monthly_maintenance_fee', 0) }}"
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
                                                   value="{{ old('annual_maintenance_fee', 0) }}"
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
                                                   value="{{ old('service_fee_rate', 0) }}"
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
                                                   value="{{ old('platform_fee_rate', 0) }}"
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
                                                   value="{{ old('transaction_fee_rate', 0) }}"
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
                            <a href="{{ route('admin.' . $routePrefix . '.index') }}" class="btn btn-secondary">
                                <i class="fe fe-x me-2"></i>취소
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fe fe-save me-2"></i>저장
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">안내</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-0">
                        <h6><i class="fe fe-info me-2"></i>등급 생성 안내</h6>
                        <ul class="mb-0 small">
                            <li>등급명은 필수 입력 항목입니다.</li>
                            <li>등급 코드를 비워두면 등급명으로 자동 생성됩니다.</li>
                            <li>수수료율과 우선순위는 필수 입력 항목입니다.</li>
                            <li>성과 기준은 자동 승급 시 참고됩니다.</li>
                            <li>JSON 설정은 선택사항이며, 빈 값도 가능합니다.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">예시</h5>
                </div>
                <div class="card-body">
                    <h6>등급별 수수료율 예시</h6>
                    <ul class="small mb-3">
                        <li>브론즈: 60%</li>
                        <li>실버: 65%</li>
                        <li>골드: 70%</li>
                        <li>플래티넘: 75%</li>
                    </ul>

                    <h6>우선순위 예시</h6>
                    <ul class="small mb-0">
                        <li>플래티넘: 1 (최우선)</li>
                        <li>골드: 2</li>
                        <li>실버: 3</li>
                        <li>브론즈: 4</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 등급명 입력시 자동으로 코드 생성
    const titleInput = document.getElementById('tier_name');
    const codeInput = document.getElementById('tier_code');

    titleInput.addEventListener('input', function() {
        if (!codeInput.value || codeInput.dataset.autoGenerated === 'true') {
            const code = this.value
                .toLowerCase()
                .replace(/[^a-z0-9가-힣\s-]/g, '')
                .replace(/\s+/g, '_')
                .trim();
            codeInput.value = code;
            codeInput.dataset.autoGenerated = 'true';
        }
    });

    codeInput.addEventListener('input', function() {
        if (this.value) {
            this.dataset.autoGenerated = 'false';
        }
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

    // 수수료 타입 전환
    const commissionTypeSelect = document.getElementById('commission_type');
    const commissionRateField = document.getElementById('commission_rate_field');
    const commissionAmountField = document.getElementById('commission_amount_field');
    const commissionRateInput = document.getElementById('commission_rate');
    const commissionAmountInput = document.getElementById('commission_amount');

    // 초기 상태 설정
    toggleCommissionFields();

    // 상위 등급 선택 시 최대 수수료율 표시
    const parentTierSelect = document.getElementById('parent_tier_id');
    const maxRateWarning = document.getElementById('max_rate_warning');

    parentTierSelect.addEventListener('change', function() {
        updateMaxRateWarning();
    });

    commissionRateInput.addEventListener('input', function() {
        validateCommissionRate();
    });

    function updateMaxRateWarning() {
        const selectedOption = parentTierSelect.options[parentTierSelect.selectedIndex];
        if (selectedOption.value && selectedOption.text.includes('(')) {
            const parentRate = parseFloat(selectedOption.text.match(/\((\d+(?:\.\d+)?)\%\)/)[1]);
            const maxAllowed = Math.max(0, parentRate - 0.5);
            maxRateWarning.textContent = `상위 등급 수수료율: ${parentRate}%, 최대 허용: ${maxAllowed}%`;
            maxRateWarning.style.display = 'block';

            // 수수료율 최대값 설정
            commissionRateInput.max = maxAllowed;
        } else {
            maxRateWarning.style.display = 'none';
            commissionRateInput.max = 100;
        }
    }

    function validateCommissionRate() {
        const rate = parseFloat(commissionRateInput.value);
        const maxAllowed = parseFloat(commissionRateInput.max);

        if (rate > maxAllowed) {
            commissionRateInput.setCustomValidity(`수수료율은 ${maxAllowed}%를 초과할 수 없습니다.`);
            commissionRateInput.classList.add('is-invalid');
        } else {
            commissionRateInput.setCustomValidity('');
            commissionRateInput.classList.remove('is-invalid');
        }
    }
});

// 수수료 타입 전환 함수 (전역)
function toggleCommissionFields() {
    const commissionType = document.getElementById('commission_type').value;
    const commissionRateField = document.getElementById('commission_rate_field');
    const commissionAmountField = document.getElementById('commission_amount_field');
    const commissionRateInput = document.getElementById('commission_rate');
    const commissionAmountInput = document.getElementById('commission_amount');

    if (commissionType === 'fixed_amount') {
        commissionRateField.style.display = 'none';
        commissionAmountField.style.display = 'block';
        commissionRateInput.removeAttribute('required');
        commissionAmountInput.setAttribute('required', 'required');
    } else {
        commissionRateField.style.display = 'block';
        commissionAmountField.style.display = 'none';
        commissionRateInput.setAttribute('required', 'required');
        commissionAmountInput.removeAttribute('required');
    }
}
</script>
@endpush