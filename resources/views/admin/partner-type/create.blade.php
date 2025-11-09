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
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">관리자</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.partner.dashboard') }}">파트너</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.' . $routePrefix . '.index') }}">타입 관리</a></li>
                            <li class="breadcrumb-item active">새 타입 생성</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('admin.' . $routePrefix . '.index') }}" class="btn btn-outline-secondary">
                        <i class="fe fe-arrow-left me-2"></i>목록으로
                    </a>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.' . $routePrefix . '.store') }}" method="POST">
        @csrf
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
                                           value="{{ old('type_code') }}"
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
                                           value="{{ old('type_name') }}"
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
                                      placeholder="이 타입의 역할과 특징을 설명해주세요">{{ old('description') }}</textarea>
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
                                           value="{{ old('icon', 'fe-users') }}"
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
                                           value="{{ old('color', '#007bff') }}">
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
                                           value="{{ old('sort_order', 0) }}"
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
                                                   {{ in_array($key, old('specialties', [])) ? 'checked' : '' }}>
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
                                                   {{ in_array($key, old('required_skills', [])) ? 'checked' : '' }}>
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
                                      placeholder="쉼표로 구분하여 입력 (예: 정보처리기사, AWS 자격증)">{{ old('certifications') }}</textarea>
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
                                    <label for="target_sales_amount" class="form-label">목표 매출액 (원)</label>
                                    <input type="number"
                                           class="form-control @error('target_sales_amount') is-invalid @enderror"
                                           id="target_sales_amount"
                                           name="target_sales_amount"
                                           value="{{ old('target_sales_amount', 0) }}"
                                           min="0"
                                           step="10000">
                                    @error('target_sales_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="target_support_cases" class="form-label">목표 지원 건수</label>
                                    <input type="number"
                                           class="form-control @error('target_support_cases') is-invalid @enderror"
                                           id="target_support_cases"
                                           name="target_support_cases"
                                           value="{{ old('target_support_cases', 0) }}"
                                           min="0">
                                    @error('target_support_cases')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="commission_bonus_rate" class="form-label">추가 수수료율 (%)</label>
                            <input type="number"
                                   class="form-control @error('commission_bonus_rate') is-invalid @enderror"
                                   id="commission_bonus_rate"
                                   name="commission_bonus_rate"
                                   value="{{ old('commission_bonus_rate', 0) }}"
                                   min="0"
                                   max="100"
                                   step="0.1">
                            @error('commission_bonus_rate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">이 타입 파트너가 받는 추가 수수료율</small>
                        </div>
                    </div>
                </div>

                <!-- 권한 설정 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fe fe-shield me-2"></i>권한 설정
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">타입별 권한</label>
                            <div class="row">
                                @foreach($defaultPermissions as $key => $label)
                                    <div class="col-md-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   name="permissions[]"
                                                   value="{{ $key }}"
                                                   id="permission_{{ $key }}"
                                                   {{ in_array($key, old('permissions', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="permission_{{ $key }}">
                                                {{ $label }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="access_levels" class="form-label">접근 레벨</label>
                            <textarea class="form-control @error('access_levels') is-invalid @enderror"
                                      id="access_levels"
                                      name="access_levels"
                                      rows="2"
                                      placeholder="쉼표로 구분하여 입력 (예: level_1, level_2)">{{ old('access_levels') }}</textarea>
                            @error('access_levels')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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
                                   {{ old('is_active', true) ? 'checked' : '' }}>
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
                                   value="{{ old('training_hours_required', 0) }}"
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
                                   value="{{ old('certification_valid_until') }}">
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
                                      placeholder="쉼표로 구분하여 입력">{{ old('training_requirements') }}</textarea>
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
                                      placeholder="관리자용 메모를 입력하세요">{{ old('admin_notes') }}</textarea>
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
                                <i class="fe fe-save me-2"></i>타입 생성
                            </button>
                            <a href="{{ route('admin.' . $routePrefix . '.index') }}" class="btn btn-outline-secondary">
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
