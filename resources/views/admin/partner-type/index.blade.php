@extends('jiny-partner::layouts.admin.sidebar')

@section('title', $title)

@section('content')
<div class="container-fluid">
    <!-- 헤더 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">{{ $title }}</h2>
                    <p class="text-muted mb-0">파트너의 전문성과 역할을 구분하여 관리합니다</p>
                </div>
                <div>
                    <a href="{{ route('admin.partner.dashboard') }}" class="btn btn-outline-secondary me-2">
                        <i class="fe fe-arrow-left me-2"></i>파트너 관리
                    </a>
                    <a href="{{ route('admin.' . $routePrefix . '.create') }}" class="btn btn-primary">
                        <i class="fe fe-plus me-2"></i>새 타입 생성
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 통계 카드 -->
    <div class="row mb-4">
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
                            <h6 class="mb-0 text-muted">전체 타입</h6>
                            <h3 class="mb-0 fw-bold">{{ $statistics['total_types'] }}</h3>
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
                            <h6 class="mb-0 text-muted">활성 타입</h6>
                            <h3 class="mb-0 fw-bold">{{ $statistics['active_types'] }}</h3>
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
                                <i class="fe fe-x-circle text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 text-muted">비활성 타입</h6>
                            <h3 class="mb-0 fw-bold">{{ $statistics['inactive_types'] }}</h3>
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
                                <i class="fe fe-users text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 text-muted">총 파트너</h6>
                            <h3 class="mb-0 fw-bold">{{ $statistics['total_partners'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 필터 -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.' . $routePrefix . '.index') }}">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="search">검색</label>
                            <input type="text"
                                   id="search"
                                   name="search"
                                   class="form-control"
                                   placeholder="타입 코드, 이름 또는 설명으로 검색..."
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
                    <div class="col-md-6 d-flex align-items-end">
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
    </div>

    <!-- 타입 목록 -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">파트너 타입 목록</h5>
        </div>
        <div class="card-body p-0">
            @if($items->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="80">코드</th>
                                <th>타입 정보</th>
                                <th width="200">전문 분야</th>
                                <th width="150">성과 목표</th>
                                <th width="100">파트너</th>
                                <th width="60">상태</th>
                                <th width="100">관리</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $item)
                            <tr>
                                <td>
                                    <code class="bg-light px-2 py-1 rounded">{{ $item->type_code }}</code>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <div class="icon-shape icon-sm rounded-circle text-white"
                                                 style="background-color: {{ $item->color }};">
                                                <i class="fe {{ $item->icon }}"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <strong>{{ $item->type_name }}</strong>
                                            @if($item->description)
                                                <br><small class="text-muted">{{ Str::limit($item->description, 60) }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small">
                                        @php
                                            $specialties = $item->specialties;
                                            // 안전한 배열 변환
                                            if (is_string($specialties)) {
                                                $specialties = json_decode($specialties, true) ?: [];
                                            } elseif (!is_array($specialties)) {
                                                $specialties = [];
                                            }
                                        @endphp
                                        @if($specialties && count($specialties) > 0)
                                            @foreach(array_slice($specialties, 0, 3) as $specialty)
                                                <span class="badge bg-light text-dark me-1 mb-1">{{ $specialty }}</span>
                                            @endforeach
                                            @if(count($specialties) > 3)
                                                <span class="text-muted">+{{ count($specialties) - 3 }}개</span>
                                            @endif
                                        @else
                                            <span class="text-muted">미설정</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="small">
                                        @if($item->target_sales_amount > 0)
                                            <div class="mb-1">
                                                <strong>매출:</strong> {{ number_format($item->target_sales_amount / 10000) }}만원
                                            </div>
                                        @endif
                                        @if($item->target_support_cases > 0)
                                            <div class="mb-1">
                                                <strong>지원:</strong> {{ number_format($item->target_support_cases) }}건
                                            </div>
                                        @endif
                                        @if($item->commission_bonus_rate > 0)
                                            <div class="text-success">
                                                <strong>보너스:</strong> +{{ $item->commission_bonus_rate }}%
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="fw-bold">{{ $item->partners_count }}</span>
                                    <small class="text-muted">명</small>
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
                                                onclick="deleteType({{ $item->id }})">
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
                    <h5 class="text-muted">등록된 파트너 타입이 없습니다</h5>
                    <p class="text-muted">새로운 파트너 타입을 생성해보세요.</p>
                    <a href="{{ route('admin.' . $routePrefix . '.create') }}" class="btn btn-primary">
                        <i class="fe fe-plus me-2"></i>첫 번째 타입 생성
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
