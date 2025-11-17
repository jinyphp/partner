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
                    <p class="text-muted mb-0">파트너 지원서를 관리합니다</p>
                </div>
                <div>
                    <a href="{{ route('admin.partner.applications.create') }}" class="btn btn-success me-2">
                        <i class="fe fe-plus me-2"></i>신청서 등록
                    </a>
                    <a href="{{ route('admin.partner.approval.index') }}" class="btn btn-primary">
                        <i class="fe fe-settings me-2"></i>승인 관리
                    </a>
                </div>
            </div>
        </div>
    </section>



    <!-- 통계 카드 -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-gradient rounded-circle p-3 stat-circle">
                                <i class="fe fe-file-text text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 text-muted">전체 지원서</h6>
                            <h3 class="mb-0 fw-bold">{{ $items->total() ?? 0 }}</h3>
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
                                <i class="fe fe-clock text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 text-muted">검토 대기</h6>
                            <h3 class="mb-0 fw-bold">{{ $items->where('application_status', 'submitted')->count() }}</h3>
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
                            <h6 class="mb-0 text-muted">승인 완료</h6>
                            <h3 class="mb-0 fw-bold">{{ $items->where('application_status', 'approved')->count() }}</h3>
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
                                <i class="fe fe-calendar text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 text-muted">면접 예정</h6>
                            <h3 class="mb-0 fw-bold">{{ $items->where('application_status', 'interview')->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 필터 -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.partner.applications.index') }}">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="search">검색</label>
                            <input type="text"
                                   id="search"
                                   name="search"
                                   class="form-control"
                                   placeholder="이름, 이메일, 메모로 검색..."
                                   value="{{ $searchValue }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="status">상태</label>
                            <select id="status" name="status" class="form-control">
                                <option value="">전체</option>
                                <option value="submitted" {{ $selectedStatus === 'submitted' ? 'selected' : '' }}>제출됨</option>
                                <option value="reviewing" {{ $selectedStatus === 'reviewing' ? 'selected' : '' }}>검토 중</option>
                                <option value="interview" {{ $selectedStatus === 'interview' ? 'selected' : '' }}>면접 예정</option>
                                <option value="approved" {{ $selectedStatus === 'approved' ? 'selected' : '' }}>승인됨</option>
                                <option value="rejected" {{ $selectedStatus === 'rejected' ? 'selected' : '' }}>반려됨</option>
                                <option value="reapplied" {{ $selectedStatus === 'reapplied' ? 'selected' : '' }}>재신청</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="submit" class="btn btn-outline-primary me-2">
                            <i class="fe fe-search me-1"></i>검색
                        </button>
                        <a href="{{ route('admin.partner.applications.index') }}" class="btn btn-outline-secondary">
                            <i class="fe fe-refresh-cw me-1"></i>초기화
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 알림 메시지 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fe fe-info me-2"></i>
                <strong>안내:</strong> 파트너 지원서 승인/거부는
                <a href="{{ route('admin.partner.approval.index') }}" class="alert-link">파트너 승인 관리</a>에서 진행해주세요.
            </div>
        </div>
    </div>

    <!-- 지원서 목록 -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">파트너 지원서 목록</h5>
        </div>
        <div class="card-body p-0">
            @if($items->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="200">지원자 정보</th>
                                <th width="100">상태</th>
                                <th width="150">추천인 정보</th>
                                <th width="100">지원일</th>
                                <th width="100">최종 수정일</th>
                                <th width="100">관리</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $item)
                            <tr>
                                <td>
                                    <div>
                                        <strong>{{ $item->personal_info['name'] ?? $item->user->name ?? 'Unknown' }}</strong>
                                        <br><small class="text-muted">{{ $item->personal_info['email'] ?? $item->user->email ?? 'Unknown' }}</small>
                                    </div>
                                </td>
                                <td>
                                    @if($item->application_status === 'submitted')
                                        <span class="badge bg-primary">제출됨</span>
                                    @elseif($item->application_status === 'reviewing')
                                        <span class="badge bg-warning">검토 중</span>
                                    @elseif($item->application_status === 'interview')
                                        <span class="badge bg-info">면접 예정</span>
                                    @elseif($item->application_status === 'approved')
                                        <span class="badge bg-success">승인됨</span>
                                    @elseif($item->application_status === 'rejected')
                                        <span class="badge bg-danger">반려됨</span>
                                    @elseif($item->application_status === 'reapplied')
                                        <span class="badge bg-secondary">재신청</span>
                                    @else
                                        <span class="badge bg-light text-dark">{{ $item->application_status }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($item->referrerPartner)
                                        <div class="small">
                                            <strong class="text-primary">{{ $item->referrerPartner->partner_code ?? 'N/A' }}</strong>
                                            <br><small class="text-muted">{{ $item->referrerPartner->name ?? '알 수 없음' }}</small>
                                        </div>
                                    @else
                                        <small class="text-muted">직접 신청</small>
                                    @endif
                                </td>
                                <td>
                                    {{ $item->created_at->format('Y-m-d') }}
                                </td>
                                <td>
                                    {{ $item->updated_at->format('Y-m-d') }}
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.partner.applications.show', $item->id) }}"
                                           class="btn btn-outline-primary"
                                           title="상세보기">
                                            <i class="fe fe-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.partner.applications.edit', $item->id) }}"
                                           class="btn btn-outline-secondary"
                                           title="수정">
                                            <i class="fe fe-edit-2"></i>
                                        </a>
                                        <button type="button"
                                                class="btn btn-outline-danger"
                                                title="삭제"
                                                onclick="confirmDelete({{ $item->id }}, '{{ $item->personal_info['name'] ?? $item->user->name ?? 'Unknown' }}')">
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
                    <i class="fe fe-file-text fe-3x text-muted mb-3"></i>
                    <h5 class="text-muted">등록된 파트너 지원서가 없습니다</h5>
                    <p class="text-muted">파트너 지원서가 제출되면 여기에 표시됩니다.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- 삭제 확인 모달 -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">신청서 삭제 확인</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong id="deleteName"></strong>님의 파트너 신청서를 정말 삭제하시겠습니까?</p>
                    <p class="text-danger small">이 작업은 되돌릴 수 없습니다.</p>
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
function confirmDelete(id, name) {
    document.getElementById('deleteName').textContent = name;
    document.getElementById('deleteForm').action = `{{ route('admin.partner.applications.index') }}/${id}`;

    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>
@endpush
