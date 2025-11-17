@extends('jiny-partner::layouts.admin.sidebar')

@section('title', $title)

@section('content')
<div class="container-fluid">

    <!-- 헤더 -->
    <section class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">{{ $title }}</h2>
                    <p class="text-muted mb-0">파트너 등급별 수수료와 혜택을 관리합니다</p>
                </div>
                <div>
                    <a href="{{ route('admin.partner.dashboard') }}" class="btn btn-outline-secondary me-2">
                        <i class="fe fe-arrow-left me-2"></i>파트너 관리
                    </a>
                    <a href="{{ route('admin.' . $routePrefix . '.create') }}" class="btn btn-primary">
                        <i class="fe fe-plus me-2"></i>새 등급 생성
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- 통계 카드 -->
    <section class="row mb-4">
        <div class="col-md-3">

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-gradient rounded-circle p-3 stat-circle">
                                <i class="fe fe-layers text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 text-muted">전체 등급</h6>
                            <h3 class="mb-0 fw-bold">{{ $items->total() }}</h3>
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
                            <h6 class="mb-0 text-muted">활성 등급</h6>
                            <h3 class="mb-0 fw-bold">{{ $items->where('is_active', true)->count() }}</h3>
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
                                <i class="fe fe-trending-up text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 text-muted">최고 수수료율</h6>
                            <h3 class="mb-0 fw-bold">{{ $items->max('commission_rate') ?? 0 }}%</h3>
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
                            <div class="bg-warning bg-gradient rounded-circle p-3 stat-circle">
                                <i class="fe fe-users text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 text-muted">전체 파트너</h6>
                            <h3 class="mb-0 fw-bold">{{ number_format($totalPartners ?? 0) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 필터 -->
    <section class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.' . $routePrefix . '.index') }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search">검색</label>
                            <input type="text"
                                   id="search"
                                   name="search"
                                   class="form-control"
                                   placeholder="등급 코드, 이름 또는 설명으로 검색..."
                                   value="{{ $searchValue }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="is_active">상태</label>
                            <select id="is_active" name="is_active" class="form-control">
                                <option value="">전체</option>
                                <option value="1" {{ $selectedIsActive === '1' ? 'selected' : '' }}>활성</option>
                                <option value="0" {{ $selectedIsActive === '0' ? 'selected' : '' }}>비활성</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="commission_type">수수료 타입</label>
                            <select id="commission_type" name="commission_type" class="form-control">
                                <option value="">전체</option>
                                <option value="percentage" {{ request('commission_type') === 'percentage' ? 'selected' : '' }}>퍼센트</option>
                                <option value="fixed_amount" {{ request('commission_type') === 'fixed_amount' ? 'selected' : '' }}>고정금액</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="parent_type_id">연동 타입</label>
                            <select id="parent_type_id" name="parent_type_id" class="form-control">
                                <option value="">전체</option>
                                @foreach($availablePartnerTypes ?? [] as $type)
                                    <option value="{{ $type->id }}" {{ request('parent_type_id') == $type->id ? 'selected' : '' }}>
                                        {{ $type->type_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-outline-primary me-2">
                            <i class="fe fe-search me-1"></i>검색
                        </button>
                        <a href="{{ route('admin.' . $routePrefix . '.index') }}" class="btn btn-outline-secondary">
                            <i class="fe fe-refresh-cw me-1"></i>초기화
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- 등급 목록 -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">파트너 등급 목록</h5>
        </div>
        <div class="card-body p-0">

            @if($items->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="80">순서</th>
                                <th>등급 정보</th>
                                <th width="100">파트너</th>
                                <th width="160">수수료 정보</th>
                                <th width="180">비용 정보</th>
                                <th width="120">요구사항</th>
                                <th width="60">상태</th>
                                <th width="100">관리</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $index => $item)
                            <tr>
                                <td>
                                    <div class="text-center">
                                        <span class="fs-5">{{ $item->priority_level }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong>{{ $item->tier_name }}</strong>
                                        <code class="bg-light px-2 py-1 rounded ms-2 small">{{ $item->tier_code }}</code>
                                        @if($item->description)
                                            <br><small class="text-muted">{{ Str::limit($item->description, 50) }}</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="text-center">
                                        @if(($partnerCounts[$item->id] ?? 0) > 0)
                                            <a href="{{ route('admin.partner.users.index', ['tier' => $item->id]) }}"
                                               class="badge bg-info fs-6 text-decoration-none"
                                               title="이 등급의 파트너 목록 보기">
                                                {{ $partnerCounts[$item->id] ?? 0 }}명
                                            </a>
                                            <small class="text-muted">{{ number_format((($partnerCounts[$item->id] ?? 0) / max($totalPartners, 1)) * 100, 1) }}%</small>
                                        @else
                                            <span class="badge bg-secondary fs-6">0명</span>
                                            <small class="text-muted">0%</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="text-center">
                                        @if($item->commission_type === 'fixed_amount')
                                            <span class="badge bg-warning fs-6 mb-1">{{ number_format($item->commission_amount ?? 0) }}원</span>
                                            <br><small class="text-muted">고정금액</small>
                                        @else
                                            <span class="badge bg-success fs-6 mb-1">{{ $item->commission_rate }}%</span>
                                            <br><small class="text-muted">퍼센트</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="small">
                                        @if($item->registration_fee > 0 || $item->monthly_fee > 0 || $item->annual_fee > 0)
                                            @if($item->registration_fee > 0)
                                                <div class="mb-1">
                                                    <strong>가입:</strong> {{ number_format($item->registration_fee) }}원
                                                </div>
                                            @endif
                                            @if($item->monthly_fee > 0)
                                                <div class="mb-1">
                                                    <strong>월:</strong> {{ number_format($item->monthly_fee) }}원
                                                </div>
                                            @endif
                                            @if($item->annual_fee > 0)
                                                <div class="text-muted">
                                                    <strong>연:</strong> {{ number_format($item->annual_fee) }}원
                                                </div>
                                            @endif
                                            @if($item->fee_waiver_available)
                                                <div class="text-success mt-1">
                                                    <small>면제가능</small>
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-muted">무료</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="small text-center">
                                        @php
                                            $requirements = $item->getRequirements();
                                        @endphp
                                        @if(isset($requirements['min_completed_jobs']))
                                            <div class="mb-1">
                                                <strong>{{ number_format($requirements['min_completed_jobs']) }}건</strong>
                                            </div>
                                        @endif
                                        @if(isset($requirements['min_rating']))
                                            <div class="d-flex align-items-center justify-content-center">
                                                <i class="fe fe-star text-warning me-1"></i>
                                                <span>{{ $requirements['min_rating'] }}/5.0</span>
                                            </div>
                                        @endif
                                        @if(isset($requirements['min_experience_months']))
                                            <div class="text-muted mt-1">
                                                {{ $requirements['min_experience_months'] }}개월 경험
                                            </div>
                                        @endif
                                        @if(count($requirements) === 0)
                                            <span class="text-muted">없음</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if($item->is_active)
                                        <span class="badge bg-success">활성</span>
                                    @else
                                        <span class="badge bg-secondary">비활성</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.' . $routePrefix . '.show', $item->id) }}"
                                           class="btn btn-outline-primary"
                                           title="상세보기">
                                            <i class="fe fe-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.' . $routePrefix . '.edit', $item->id) }}"
                                           class="btn btn-outline-warning"
                                           title="수정">
                                            <i class="fe fe-edit"></i>
                                        </a>
                                        <button type="button"
                                                class="btn btn-outline-danger"
                                                title="삭제"
                                                onclick="deleteTier({{ $item->id }})">
                                            <i class="fe fe-trash-2"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- 페이지네이션 -->
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            전체 {{ $items->total() }}개 중
                            {{ $items->firstItem() }}~{{ $items->lastItem() }}개 표시
                        </div>
                        <div>
                            {{ $items->appends(request()->query())->links('pagination::bootstrap-4') }}
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fe fe-layers fe-3x text-muted mb-3"></i>
                    <h5 class="text-muted">등록된 파트너 등급이 없습니다</h5>
                    <p class="text-muted">새로운 파트너 등급을 생성해보세요.</p>
                    <a href="{{ route('admin.' . $routePrefix . '.create') }}" class="btn btn-primary">
                        <i class="fe fe-plus me-2"></i>첫 번째 등급 생성
                    </a>
                </div>
            @endif

        </div>
    </div>
</div>

<!-- 삭제 확인 모달 -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">파트너 등급 삭제</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>이 파트너 등급을 삭제하시겠습니까?</p>
                <p class="text-danger small">
                    <i class="fe fe-alert-triangle me-1"></i>
                    삭제된 등급은 복구할 수 없으며, 해당 등급을 사용하는 파트너들에게 영향을 줄 수 있습니다.
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
function deleteTier(id) {
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    const form = document.getElementById('deleteForm');
    form.action = `/admin/partner/tiers/${id}`;
    modal.show();
}
</script>
@endpush
