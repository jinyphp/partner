@extends('jiny-partner::layouts.admin.sidebar')

@section('content')
<div class="container-fluid px-6 py-4">
    <!-- Page Header -->
    <div class="row">
        <div class="col-lg-12 col-md-12 col-12">
            <div class="border-bottom pb-3 mb-3 d-lg-flex align-items-center justify-content-between">
                <div class="mb-2 mb-lg-0">
                    <h1 class="mb-0 h2 fw-bold">{{ $pageTitle }}</h1>
                    <p class="mb-0 text-muted">파트너 모집 활동을 관리하고 네트워크 관계를 구축합니다.</p>
                </div>
                <div>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#recruitModal">
                        <i class="fe fe-user-plus me-2"></i>파트너 모집
                    </button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
                        <i class="fe fe-filter me-2"></i>필터
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-lg-6 col-md-12 col-12">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ number_format($statistics['total_relationships']) }}</h4>
                            <p class="mb-0 text-muted">총 관계</p>
                        </div>
                        <div class="icon-shape icon-md bg-primary text-white rounded-3">
                            <i class="fe fe-link"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-12 col-12">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ number_format($statistics['active_relationships']) }}</h4>
                            <p class="mb-0 text-muted">활성 관계</p>
                        </div>
                        <div class="icon-shape icon-md bg-success text-white rounded-3">
                            <i class="fe fe-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-12 col-12">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ number_format($statistics['this_month_recruits']) }}</h4>
                            <p class="mb-0 text-muted">이번 달 모집</p>
                        </div>
                        <div class="icon-shape icon-md bg-info text-white rounded-3">
                            <i class="fe fe-calendar"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-12 col-12">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $statistics['recruitment_rate'] }}%</h4>
                            <p class="mb-0 text-muted">성공률</p>
                        </div>
                        <div class="icon-shape icon-md bg-warning text-white rounded-3">
                            <i class="fe fe-trending-up"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Recruiters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">최고 모집자들</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($topRecruiters as $recruiter)
                            <div class="col-md-3 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-md me-3">
                                        @if($recruiter->avatar)
                                            <img src="{{ $recruiter->avatar }}" alt="{{ $recruiter->name }}" class="rounded-circle">
                                        @else
                                            <span class="avatar-initials rounded-circle bg-primary">
                                                {{ strtoupper(substr($recruiter->name, 0, 1)) }}
                                            </span>
                                        @endif
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $recruiter->name }}</h6>
                                        <small class="text-muted">총 {{ $recruiter->total_recruits }}명 모집</small>
                                        <div class="text-success small">성공률 {{ $recruiter->recruitment_rate }}%</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recruitment Relationships -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">모집 관계 목록</h5>
                    <div>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="selectAll()">
                            전체 선택
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="bulkRemove()">
                            선택 해제
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>
                                        <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll()">
                                    </th>
                                    <th>상위 파트너</th>
                                    <th>하위 파트너</th>
                                    <th>모집자</th>
                                    <th>모집일</th>
                                    <th>상태</th>
                                    <th>성과</th>
                                    <th>액션</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($relationships as $relationship)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="relationship-checkbox" value="{{ $relationship->id }}">
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-xs me-2">
                                                    <span class="avatar-initials rounded-circle bg-primary">
                                                        {{ strtoupper(substr($relationship->parent->name, 0, 1)) }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">{{ $relationship->parent->name }}</h6>
                                                    <small class="text-muted">{{ $relationship->parent->email }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-xs me-2">
                                                    <span class="avatar-initials rounded-circle bg-info">
                                                        {{ strtoupper(substr($relationship->child->name, 0, 1)) }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">{{ $relationship->child->name }}</h6>
                                                    <small class="text-muted">{{ $relationship->child->email }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($relationship->recruiter)
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-xs me-2">
                                                        <span class="avatar-initials rounded-circle bg-success">
                                                            {{ strtoupper(substr($relationship->recruiter->name, 0, 1)) }}
                                                        </span>
                                                    </div>
                                                    <span>{{ $relationship->recruiter->name }}</span>
                                                </div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ $relationship->recruited_at->format('Y-m-d') }}</td>
                                        <td>
                                            <span class="badge bg-{{ $relationship->is_active ? 'success' : 'secondary' }}">
                                                {{ $relationship->is_active ? '활성' : '비활성' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="text-center">
                                                <div class="small text-primary fw-bold">
                                                    {{ number_format($relationship->total_generated_sales ?? 0) }}원
                                                </div>
                                                <div class="small text-muted">매출</div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('admin.partner.users.show', $relationship->child->id) }}"
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="fe fe-eye"></i>
                                                </a>
                                                @if($relationship->is_active)
                                                    <button type="button" class="btn btn-outline-danger btn-sm"
                                                            onclick="removeRelationship({{ $relationship->id }})">
                                                        <i class="fe fe-x"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fe fe-users display-4 text-muted"></i>
                                            <h5 class="mt-3">모집 관계가 없습니다</h5>
                                            <p class="text-muted">파트너 모집을 시작해보세요.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($relationships->hasPages())
                    <div class="card-footer">
                        {{ $relationships->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Recruit Modal -->
<div class="modal fade" id="recruitModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">파트너 모집</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="recruitForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">상위 파트너</label>
                        <select name="parent_id" class="form-select" required>
                            <option value="">상위 파트너를 선택하세요</option>
                            @foreach($availableRecruiters as $recruiter)
                                <option value="{{ $recruiter->id }}">{{ $recruiter->name }} ({{ $recruiter->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">모집할 파트너</label>
                        <select name="child_id" class="form-select" required>
                            <option value="">파트너를 선택하세요</option>
                            @foreach(\Jiny\Partner\Models\PartnerUser::where('parent_id', null)->where('status', 'active')->get() as $available)
                                <option value="{{ $available->id }}">{{ $available->name }} ({{ $available->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">모집자 (선택)</label>
                        <select name="recruiter_id" class="form-select">
                            <option value="">모집자를 선택하세요 (기본: 상위 파트너)</option>
                            @foreach($availableRecruiters as $recruiter)
                                <option value="{{ $recruiter->id }}">{{ $recruiter->name }} ({{ $recruiter->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">모집 메모</label>
                        <textarea name="recruitment_notes" class="form-control" rows="3"
                                  placeholder="모집에 대한 추가 정보를 입력하세요"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-success">모집하기</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">필터 설정</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="GET" action="{{ route('admin.partner.network.recruitment.index') }}">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">상태</label>
                        <select name="status" class="form-select">
                            <option value="all" {{ $currentFilters['status'] === 'all' ? 'selected' : '' }}>전체</option>
                            <option value="active" {{ $currentFilters['status'] === 'active' ? 'selected' : '' }}>활성</option>
                            <option value="inactive" {{ $currentFilters['status'] === 'inactive' ? 'selected' : '' }}>비활성</option>
                            <option value="recent" {{ $currentFilters['status'] === 'recent' ? 'selected' : '' }}>최근 30일</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">모집자</label>
                        <select name="recruiter_id" class="form-select">
                            <option value="">전체 모집자</option>
                            @foreach($availableRecruiters as $recruiter)
                                <option value="{{ $recruiter->id }}" {{ $currentFilters['recruiter_id'] == $recruiter->id ? 'selected' : '' }}>
                                    {{ $recruiter->name }} ({{ $recruiter->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">페이지당 항목 수</label>
                        <select name="per_page" class="form-select">
                            <option value="20" {{ $currentFilters['per_page'] == 20 ? 'selected' : '' }}>20개</option>
                            <option value="50" {{ $currentFilters['per_page'] == 50 ? 'selected' : '' }}>50개</option>
                            <option value="100" {{ $currentFilters['per_page'] == 100 ? 'selected' : '' }}>100개</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">적용</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Recruit Partner
$('#recruitForm').on('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    $.ajax({
        url: '{{ route("admin.partner.network.recruitment.recruit") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                alert('파트너 모집이 성공적으로 완료되었습니다.');
                location.reload();
            } else {
                alert('오류: ' + response.message);
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            alert('오류: ' + (response?.message || '알 수 없는 오류가 발생했습니다.'));
        }
    });
});

// Selection functions
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAllCheckbox');
    const checkboxes = document.querySelectorAll('.relationship-checkbox');

    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

function selectAll() {
    const checkboxes = document.querySelectorAll('.relationship-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
    });
    document.getElementById('selectAllCheckbox').checked = true;
}

// Remove relationship
function removeRelationship(id) {
    const reason = prompt('관계 해제 사유를 입력하세요:');
    if (!reason) return;

    $.ajax({
        url: '{{ route("admin.partner.network.recruitment.remove-relationship", ":id") }}'.replace(':id', id),
        method: 'DELETE',
        data: {
            reason: reason,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                alert('관계가 성공적으로 해제되었습니다.');
                location.reload();
            } else {
                alert('오류: ' + response.message);
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            alert('오류: ' + (response?.message || '알 수 없는 오류가 발생했습니다.'));
        }
    });
}

// Bulk remove relationships
function bulkRemove() {
    const selectedIds = [];
    document.querySelectorAll('.relationship-checkbox:checked').forEach(checkbox => {
        selectedIds.push(checkbox.value);
    });

    if (selectedIds.length === 0) {
        alert('선택된 관계가 없습니다.');
        return;
    }

    const reason = prompt(`선택된 ${selectedIds.length}개 관계의 해제 사유를 입력하세요:`);
    if (!reason) return;

    if (!confirm(`선택된 ${selectedIds.length}개의 관계를 해제하시겠습니까?`)) {
        return;
    }

    // Note: You may need to implement a bulk remove endpoint
    alert('대량 해제 기능은 개별 해제를 사용하세요.');
}
</script>
@endsection