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
                    <p class="text-muted mb-0">파트너로 등록된 회원들을 관리합니다</p>
                </div>
                <div>
                    <a href="{{ route('admin.partner.dashboard') }}" class="btn btn-outline-secondary me-2">
                        <i class="fe fe-arrow-left me-2"></i>파트너 관리
                    </a>
                    <a href="{{ route('admin.' . $routePrefix . '.create') }}" class="btn btn-primary">
                        <i class="fe fe-plus me-2"></i>새 파트너 등록
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
                                <i class="fe fe-users text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 text-muted">파트너 회원</h6>
                            <h3 class="mb-0 fw-bold">
                                <span class="text-success">{{ $statistics['active'] ?? 0 }}</span> / {{ $statistics['total'] ?? 0 }}
                            </h3>
                            <small class="text-muted">활성 / 전체</small>
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
                                <i class="fe fe-dollar-sign text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 text-muted">총 커미션</h6>
                            <h3 class="mb-0 fw-bold">{{ number_format($statistics['total_commissions'] ?? 0) }}원</h3>
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
                            <h6 class="mb-0 text-muted">총 매출</h6>
                            <h3 class="mb-0 fw-bold">{{ number_format($statistics['total_sales'] ?? 0) }}원</h3>
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
                                <i class="fe fe-star text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 text-muted">평균 평점</h6>
                            <h3 class="mb-0 fw-bold">{{ number_format($statistics['average_rating'] ?? 0, 1) }}</h3>
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
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search">검색</label>
                            <input type="text"
                                   id="search"
                                   name="search"
                                   class="form-control"
                                   placeholder="이메일, 이름, 메모로 검색..."
                                   value="{{ $searchValue }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="status">상태</label>
                            <select id="status" name="status" class="form-control">
                                <option value="">전체</option>
                                @foreach($filterOptions['statuses'] as $value => $label)
                                    <option value="{{ $value }}" {{ $selectedStatus === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="partner_tier_id">등급</label>
                            <select id="partner_tier_id" name="partner_tier_id" class="form-control">
                                <option value="">전체</option>
                                @foreach($filterOptions['tiers'] as $tier)
                                    <option value="{{ $tier->id }}" {{ ($selectedTier == $tier->id || request('tier') == $tier->id || request('partner_tier_id') == $tier->id) ? 'selected' : '' }}>
                                        {{ $tier->tier_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="user_table">사용자 테이블</label>
                            <select id="user_table" name="user_table" class="form-control">
                                <option value="">전체</option>
                                @foreach($filterOptions['userTables'] as $table)
                                    <option value="{{ $table }}" {{ $selectedUserTable === $table ? 'selected' : '' }}>
                                        {{ $table }}
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
    </div>

    <!-- 파트너 회원 목록 -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">파트너 회원 목록</h5>
        </div>
        <div class="card-body p-0">
            @if($items->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="180">회원 정보</th>
                                <th width="140">등급 / 깊이</th>
                                <th width="70">상태</th>
                                <th width="120">매출 / 커미션</th>
                                <th width="100">작업 / 평점</th>
                                <th width="120">가입일 / 테이블</th>
                                <th width="100">관리</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $item)
                            <tr>
                                <td>
                                    <div>
                                        <strong>{{ $item->name }}</strong>
                                        <br><small class="text-muted">{{ $item->email }}</small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <span class="badge bg-info">{{ $item->partnerTier->tier_name ?? 'N/A' }}</span>
                                        <br>
                                        <small class="text-muted d-flex align-items-center mt-1">
                                            {{-- @if($item->level > 0)
                                                @for($i = 0; $i < $item->level; $i++)
                                                    <span class="me-1" style="color: #ccc;">└</span>
                                                @endfor
                                            @endif --}}
                                            <i class="fe fe-layers me-1"></i>
                                            깊이: {{ $item->level }}
                                            @if($item->children_count == 0)
                                                <span class="badge badge-sm bg-light text-dark ms-1">
                                                    <i class="fe fe-corner-down-right me-1"></i>마지막 노드
                                                </span>
                                            @else
                                                <span class="text-info ms-1">({{ $item->children_count }}명 하위)</span>
                                            @endif
                                        </small>
                                    </div>
                                </td>
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
                                <td>
                                    <div class="text-end">
                                        <!-- 매출액 (첫 번째 줄) -->
                                        <div class="fw-bold text-success d-flex align-items-center justify-content-end">
                                            <i class="fe fe-trending-up me-1 text-success"></i>
                                            {{ number_format($item->monthly_sales) }}원
                                        </div>
                                        @if($item->team_sales > 0)
                                            <small class="text-muted">팀: {{ number_format($item->team_sales) }}원</small><br>
                                        @endif

                                        <!-- 커미션 (두 번째 줄) -->
                                        <div class="fw-bold text-primary d-flex align-items-center justify-content-end mt-1">
                                            <i class="fe fe-dollar-sign me-1 text-primary"></i>
                                            {{ number_format($item->earned_commissions) }}원
                                            @php
                                                $commissionRate = $item->monthly_sales > 0 ? round(($item->earned_commissions / $item->monthly_sales) * 100, 1) : 0;
                                            @endphp
                                            @if($commissionRate > 0)
                                                <small class="text-muted ms-1">({{ $commissionRate }}%)</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-center">
                                        <!-- 완료 작업 (첫 번째 줄) -->
                                        <div class="fw-bold text-info d-flex align-items-center justify-content-center">
                                            <i class="fe fe-check-circle me-1 text-info"></i>
                                            {{ number_format($item->total_completed_jobs) }}건
                                        </div>

                                        <!-- 평점 (두 번째 줄) -->
                                        <div class="fw-bold text-warning d-flex align-items-center justify-content-center mt-1">
                                            <i class="fe fe-star me-1 text-warning"></i>
                                            {{ $item->average_rating }}/5.0
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-center">
                                        <!-- 가입일 (첫 번째 줄) -->
                                        <div class="fw-bold text-dark d-flex align-items-center justify-content-center">
                                            <i class="fe fe-calendar me-1 text-muted"></i>
                                            {{ $item->partner_joined_at->format('Y-m-d') }}
                                        </div>

                                        <!-- 테이블 (두 번째 줄) -->
                                        <div class="d-flex align-items-center justify-content-center mt-1">
                                            <i class="fe fe-database me-1 text-muted"></i>
                                            <code class="bg-light px-2 py-1 rounded text-sm">{{ $item->user_table }}</code>
                                        </div>
                                    </div>
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
                                                onclick="deletePartnerUser({{ $item->id }})">
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
                    <i class="fe fe-users fe-3x text-muted mb-3"></i>
                    <h5 class="text-muted">등록된 파트너 회원이 없습니다</h5>
                    <p class="text-muted">새로운 파트너 회원을 등록해보세요.</p>
                    <a href="{{ route('admin.' . $routePrefix . '.create') }}" class="btn btn-primary">
                        <i class="fe fe-plus me-2"></i>첫 번째 파트너 등록
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- 삭제 확인 모달 -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">파트너 회원 삭제</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning d-flex align-items-center" role="alert">
                    <i class="fe fe-alert-triangle me-2"></i>
                    <div>
                        <strong>주의!</strong> 이 작업은 되돌릴 수 없습니다.
                    </div>
                </div>
                <p>정말로 이 파트너 회원을 삭제하시겠습니까?</p>
                <p class="text-muted small">
                    삭제된 회원 정보는 복구할 수 없으며, 관련된 작업 이력에 영향을 줄 수 있습니다.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fe fe-x me-1"></i>취소
                </button>
                <form id="deleteForm" method="POST" style="display: inline;" onsubmit="console.log('Form submitting to:', this.action);">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fe fe-trash-2 me-1"></i>삭제 확인
                    </button>
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

/* 카드 높이 균등화 */
.row.mb-4 {
    display: flex;
    flex-wrap: wrap;
}

.row.mb-4 > [class*="col-"] {
    display: flex;
    flex-direction: column;
}

.row.mb-4 .card {
    flex: 1;
    height: 100%;
}

.row.mb-4 .card-body {
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-height: 100px;
}
</style>
@endpush

@push('scripts')
<script>
// 삭제 확인
function deletePartnerUser(id) {
    console.log('Delete button clicked for user ID:', id); // 디버깅용

    try {
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        const form = document.getElementById('deleteForm');

        // 라우트 헬퍼 대신 정확한 경로 설정
        form.action = `{{ url('admin/partner/users') }}/${id}`;

        console.log('Form action set to:', form.action); // 디버깅용
        modal.show();
    } catch (error) {
        console.error('Error in deletePartnerUser:', error);
        alert('삭제 기능에서 오류가 발생했습니다: ' + error.message);
    }
}

// 폼 제출 시 추가 확인
document.addEventListener('DOMContentLoaded', function() {
    const deleteForm = document.getElementById('deleteForm');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
            console.log('Delete form submitted. Action:', this.action);

            // 한 번 더 확인
            if (!confirm('정말로 이 파트너 회원을 삭제하시겠습니까?')) {
                e.preventDefault();
                return false;
            }
        });
    }
});
</script>
@endpush
