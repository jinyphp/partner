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

                        <!-- 기본 정보 -->
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
                                    <label for="tier_code" class="form-label">등급 코드</label>
                                    <input type="text"
                                           id="tier_code"
                                           name="tier_code"
                                           class="form-control @error('tier_code') is-invalid @enderror"
                                           placeholder="등급 코드"
                                           value="{{ old('tier_code', $item->tier_code) }}">
                                    @error('tier_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">비어있으면 등급명에서 자동 생성됩니다.</small>
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
                                           value="{{ old('priority_level', $item->priority_level) }}"
                                           min="1"
                                           max="99"
                                           required>
                                    @error('priority_level')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">낮은 숫자일수록 높은 우선순위입니다.</small>
                                </div>
                            </div>
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
                                    <small class="form-text text-muted">화면 표시 순서입니다.</small>
                                </div>
                            </div>
                        </div>

                        <!-- 수수료 설정 -->
                        <h6 class="mb-3">수수료 설정</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="commission_type" class="form-label">수수료 타입 <span class="text-danger">*</span></label>
                                    <select id="commission_type"
                                            name="commission_type"
                                            class="form-select @error('commission_type') is-invalid @enderror"
                                            required>
                                        <option value="percentage" {{ old('commission_type', $item->commission_type) === 'percentage' ? 'selected' : '' }}>퍼센트</option>
                                        <option value="fixed_amount" {{ old('commission_type', $item->commission_type) === 'fixed_amount' ? 'selected' : '' }}>고정금액</option>
                                    </select>
                                    @error('commission_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="commission_rate" class="form-label">수수료율 (%)</label>
                                    <input type="number"
                                           id="commission_rate"
                                           name="commission_rate"
                                           class="form-control @error('commission_rate') is-invalid @enderror"
                                           placeholder="5.00"
                                           value="{{ old('commission_rate', $item->commission_rate) }}"
                                           min="0"
                                           max="100"
                                           step="0.01">
                                    @error('commission_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">퍼센트 타입일 때 사용</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="commission_amount" class="form-label">고정 수수료 (원)</label>
                                    <input type="number"
                                           id="commission_amount"
                                           name="commission_amount"
                                           class="form-control @error('commission_amount') is-invalid @enderror"
                                           placeholder="1000"
                                           value="{{ old('commission_amount', $item->commission_amount) }}"
                                           min="0"
                                           step="100">
                                    @error('commission_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">고정금액 타입일 때 사용</small>
                                </div>
                            </div>
                        </div>

                        <!-- 비용 관리 -->
                        <h6 class="mb-3">비용 관리</h6>
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
                                               value="{{ old('registration_fee', $item->registration_fee ?? 0) }}"
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
                                               value="{{ old('monthly_fee', $item->monthly_fee ?? 0) }}"
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
                                               value="{{ old('annual_fee', $item->annual_fee ?? 0) }}"
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
                                            <small class="text-muted d-block">특별한 경우 비용 면제가 가능한지 설정합니다.</small>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   role="switch"
                                                   id="fee_waiver_available"
                                                   name="fee_waiver_available"
                                                   value="1"
                                                   {{ old('fee_waiver_available', $item->fee_waiver_available) ? 'checked' : '' }}>
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
                                    <label for="fee_structure_notes" class="form-label">비용 구조 메모</label>
                                    <textarea id="fee_structure_notes"
                                              name="fee_structure_notes"
                                              class="form-control @error('fee_structure_notes') is-invalid @enderror"
                                              rows="2"
                                              placeholder="특별 조건이나 할인 정책 등">{{ old('fee_structure_notes', $item->fee_structure_notes) }}</textarea>
                                    @error('fee_structure_notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- JSON 필드 -->
                        <h6 class="mb-3">요구사항 및 혜택</h6>
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

                        <!-- 시스템 설정 -->
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
                        <label class="form-label text-muted small">수수료</label>
                        <div>
                            @if($item->commission_type === 'percentage')
                                <span class="badge bg-success fs-6">{{ $item->commission_rate }}%</span>
                            @else
                                <span class="badge bg-info fs-6">{{ number_format($item->commission_amount) }}원</span>
                            @endif
                        </div>
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
                            <li>수수료율 변경은 기존 파트너들에게 영향을 줄 수 있습니다.</li>
                            <li>우선순위 변경은 작업 배정 순서에 영향을 줍니다.</li>
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
    // 수수료 타입 변경에 따른 필드 토글
    const commissionType = document.getElementById('commission_type');
    const commissionRate = document.getElementById('commission_rate');
    const commissionAmount = document.getElementById('commission_amount');

    function toggleCommissionFields() {
        if (commissionType.value === 'percentage') {
            commissionRate.required = true;
            commissionAmount.required = false;
            commissionRate.parentElement.style.opacity = '1';
            commissionAmount.parentElement.style.opacity = '0.6';
        } else {
            commissionRate.required = false;
            commissionAmount.required = true;
            commissionRate.parentElement.style.opacity = '0.6';
            commissionAmount.parentElement.style.opacity = '1';
        }
    }

    commissionType.addEventListener('change', toggleCommissionFields);
    toggleCommissionFields(); // 초기 상태 설정
});
</script>
@endpush