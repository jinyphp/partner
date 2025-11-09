@extends('jiny-partner::layouts.admin.sidebar')

@section('title', $title . ' 상세보기')

@section('content')
<div class="container-fluid">
    <!-- 헤더 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">{{ $item->type_name }}</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">관리자</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.partner.dashboard') }}">파트너</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.' . $routePrefix . '.index') }}">타입 관리</a></li>
                            <li class="breadcrumb-item active">{{ $item->type_name }}</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('admin.' . $routePrefix . '.edit', $item->id) }}" class="btn btn-warning me-2">
                        <i class="fe fe-edit me-2"></i>수정
                    </a>
                    <a href="{{ route('admin.' . $routePrefix . '.index') }}" class="btn btn-outline-secondary">
                        <i class="fe fe-arrow-left me-2"></i>목록으로
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 타입 상태 요약 카드 -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle p-3 stat-circle text-white"
                                 style="background-color: {{ $item->color }};">
                                <i class="fe {{ $item->icon }}"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 text-muted">총 파트너</h6>
                            <h3 class="mb-0 fw-bold">{{ $item->partners_count }}명</h3>
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
                            <h6 class="mb-0 text-muted">활성 파트너</h6>
                            <h3 class="mb-0 fw-bold">{{ $item->active_partners_count }}명</h3>
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
                            <h6 class="mb-0 text-muted">총 매출</h6>
                            <h3 class="mb-0 fw-bold">{{ number_format($partnerStats['total_sales'] / 10000) }}만원</h3>
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
                            <div class="{{ $item->is_active ? 'bg-primary' : 'bg-secondary' }} bg-gradient rounded-circle p-3 stat-circle">
                                <i class="fe fe-{{ $item->is_active ? 'check' : 'x' }} text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 text-muted">타입 상태</h6>
                            <h3 class="mb-0 fw-bold">{{ $item->is_active ? '활성' : '비활성' }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fe fe-code text-muted me-2"></i>
                                    <label class="form-label text-muted mb-0">타입 코드</label>
                                </div>
                                <div><code class="bg-light px-2 py-1 rounded fs-6">{{ $item->type_code }}</code></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fe fe-tag text-muted me-2"></i>
                                    <label class="form-label text-muted mb-0">타입명</label>
                                </div>
                                <div><strong class="fs-5">{{ $item->type_name }}</strong></div>
                            </div>
                        </div>
                    </div>

                    @if($item->description)
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fe fe-file-text text-muted me-2"></i>
                                <label class="form-label text-muted mb-0">설명</label>
                            </div>
                            <div class="p-3 bg-light rounded">{{ $item->description }}</div>
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fe fe-eye text-muted me-2"></i>
                                    <label class="form-label text-muted mb-0">아이콘</label>
                                </div>
                                <div>
                                    <i class="fe {{ $item->icon }} me-2"></i>
                                    <code>{{ $item->icon }}</code>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fe fe-circle text-muted me-2"></i>
                                    <label class="form-label text-muted mb-0">색상</label>
                                </div>
                                <div>
                                    <span class="badge px-3 py-2" style="background-color: {{ $item->color }}; color: white;">{{ $item->color }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fe fe-list text-muted me-2"></i>
                                    <label class="form-label text-muted mb-0">정렬 순서</label>
                                </div>
                                <div class="fs-5 fw-bold">{{ $item->sort_order }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 전문성 정보 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fe fe-star me-2"></i>전문성 정보
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fe fe-layers text-muted me-2"></i>
                                    <label class="form-label text-muted mb-0">전문 분야</label>
                                </div>
                                <div>
                                    @if($item->specialties && count($item->specialties) > 0)
                                        @foreach($item->specialties as $specialty)
                                            <span class="badge bg-primary me-1 mb-1">{{ $specialty }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">미설정</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fe fe-settings text-muted me-2"></i>
                                    <label class="form-label text-muted mb-0">필수 스킬</label>
                                </div>
                                <div>
                                    @if($item->required_skills && count($item->required_skills) > 0)
                                        @foreach($item->required_skills as $skill)
                                            <span class="badge bg-success me-1 mb-1">{{ $skill }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">미설정</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($item->certifications && count($item->certifications) > 0)
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fe fe-award text-muted me-2"></i>
                                <label class="form-label text-muted mb-0">관련 자격증</label>
                            </div>
                            <div>
                                @foreach($item->certifications as $certification)
                                    <span class="badge bg-warning me-1 mb-1">{{ $certification }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
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
                        <div class="col-md-4">
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fe fe-dollar-sign text-muted me-2"></i>
                                    <label class="form-label text-muted mb-0">목표 매출액</label>
                                </div>
                                <div class="fs-4 fw-bold text-primary">{{ number_format($item->target_sales_amount / 10000) }}<small class="fs-6 text-muted">만원</small></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fe fe-help-circle text-muted me-2"></i>
                                    <label class="form-label text-muted mb-0">목표 지원 건수</label>
                                </div>
                                <div class="fs-4 fw-bold text-info">{{ number_format($item->target_support_cases) }}<small class="fs-6 text-muted">건</small></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fe fe-percent text-muted me-2"></i>
                                    <label class="form-label text-muted mb-0">추가 수수료율</label>
                                </div>
                                <div class="fs-4 fw-bold text-success">{{ $item->commission_bonus_rate }}<small class="fs-6 text-muted">%</small></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 권한 및 접근 -->
            @if($item->permissions || $item->access_levels)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fe fe-shield me-2"></i>권한 및 접근
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($item->permissions && count($item->permissions) > 0)
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fe fe-key text-muted me-2"></i>
                                    <label class="form-label text-muted mb-0">타입별 권한</label>
                                </div>
                                <div>
                                    @foreach($item->permissions as $permission)
                                        <span class="badge bg-dark me-1 mb-1">{{ $permission }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($item->access_levels && count($item->access_levels) > 0)
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fe fe-unlock text-muted me-2"></i>
                                    <label class="form-label text-muted mb-0">접근 레벨</label>
                                </div>
                                <div>
                                    @foreach($item->access_levels as $level)
                                        <span class="badge bg-secondary me-1 mb-1">{{ $level }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- 교육 및 인증 -->
            @if($item->training_requirements || $item->training_hours_required > 0 || $item->certification_valid_until)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fe fe-book me-2"></i>교육 및 인증
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @if($item->training_hours_required > 0)
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fe fe-clock text-muted me-2"></i>
                                            <label class="form-label text-muted mb-0">필수 교육 시간</label>
                                        </div>
                                        <div class="fs-5 fw-bold">{{ $item->training_hours_required }}시간</div>
                                    </div>
                                </div>
                            @endif

                            @if($item->certification_valid_until)
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fe fe-calendar text-muted me-2"></i>
                                            <label class="form-label text-muted mb-0">인증 유효기간</label>
                                        </div>
                                        <div class="fs-5 fw-bold">{{ $item->certification_valid_until->format('Y-m-d') }}</div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        @if($item->training_requirements && count($item->training_requirements) > 0)
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fe fe-list text-muted me-2"></i>
                                    <label class="form-label text-muted mb-0">교육 요구사항</label>
                                </div>
                                <div>
                                    @foreach($item->training_requirements as $requirement)
                                        <span class="badge bg-info me-1 mb-1">{{ $requirement }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <!-- 빠른 액션 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fe fe-zap me-2"></i>빠른 액션
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.' . $routePrefix . '.edit', $item->id) }}" class="btn btn-warning">
                            <i class="fe fe-edit me-2"></i>타입 수정
                        </a>
                        <button type="button" class="btn btn-outline-danger" onclick="deleteType({{ $item->id }})">
                            <i class="fe fe-trash-2 me-2"></i>타입 삭제
                        </button>
                        <a href="{{ route('admin.' . $routePrefix . '.create') }}" class="btn btn-outline-primary">
                            <i class="fe fe-plus me-2"></i>새 타입 생성
                        </a>
                    </div>
                </div>
            </div>

            <!-- 파트너 성과 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fe fe-bar-chart-2 me-2"></i>파트너 성과
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted"><i class="fe fe-dollar-sign me-1"></i>월 매출</span>
                            <span class="badge bg-primary fs-6">{{ number_format($partnerStats['monthly_sales'] / 10000) }}만원</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted"><i class="fe fe-star me-1"></i>평균 평점</span>
                            <span class="badge bg-warning fs-6">{{ number_format($partnerStats['avg_rating'], 1) }}</span>
                        </div>
                    </div>
                    <div class="mb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted"><i class="fe fe-check-circle me-1"></i>활성률</span>
                            <span class="badge bg-success fs-6">
                                {{ $item->partners_count > 0 ? round(($item->active_partners_count / $item->partners_count) * 100, 1) : 0 }}%
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 상위 성과자 -->
            @if($partnerStats['top_performers']->count() > 0)
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fe fe-award me-2"></i>상위 성과자
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        @foreach($partnerStats['top_performers'] as $partner)
                            <div class="d-flex align-items-center p-3 border-bottom">
                                <div class="avatar avatar-sm me-3">
                                    <span class="avatar-initials rounded-circle bg-primary">
                                        {{ strtoupper(substr($partner->name, 0, 1)) }}
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">{{ $partner->name }}</h6>
                                    <small class="text-muted">{{ $partner->email }}</small>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold">{{ number_format($partner->monthly_sales / 10000) }}만</div>
                                    <small class="text-muted">월매출</small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- 시스템 정보 -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fe fe-database me-2"></i>시스템 정보
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-1">
                            <i class="fe fe-plus-circle text-muted me-2"></i>
                            <small class="text-muted">생성일시</small>
                        </div>
                        <div class="ps-4">{{ $item->created_at->format('Y-m-d H:i:s') }}</div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-1">
                            <i class="fe fe-edit-3 text-muted me-2"></i>
                            <small class="text-muted">수정일시</small>
                        </div>
                        <div class="ps-4">{{ $item->updated_at->format('Y-m-d H:i:s') }}</div>
                    </div>
                    @if($item->creator)
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-1">
                                <i class="fe fe-user text-muted me-2"></i>
                                <small class="text-muted">생성자</small>
                            </div>
                            <div class="ps-4">{{ $item->creator->name }}</div>
                        </div>
                    @endif
                    @if($item->updater)
                        <div class="mb-0">
                            <div class="d-flex align-items-center mb-1">
                                <i class="fe fe-user-check text-muted me-2"></i>
                                <small class="text-muted">수정자</small>
                            </div>
                            <div class="ps-4">{{ $item->updater->name }}</div>
                        </div>
                    @endif

                    @if($item->admin_notes)
                        <hr>
                        <div class="mb-0">
                            <div class="d-flex align-items-center mb-1">
                                <i class="fe fe-file-text text-muted me-2"></i>
                                <small class="text-muted">관리자 메모</small>
                            </div>
                            <div class="ps-4">{{ $item->admin_notes }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 삭제 확인 모달 -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">파트너 타입 삭제</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>이 파트너 타입을 삭제하시겠습니까?</p>
                <p class="text-danger small">
                    <i class="fe fe-alert-triangle me-1"></i>
                    삭제된 타입은 복구할 수 없으며, 해당 타입을 사용하는 파트너들에게 영향을 줄 수 있습니다.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">삭제</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

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
// 삭제 확인
function deleteType(id) {
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    const form = document.getElementById('deleteForm');
    form.action = `/admin/partner/type/${id}`;
    modal.show();
}
</script>
@endpush