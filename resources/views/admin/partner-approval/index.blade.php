@extends('jiny-partner::layouts.admin.sidebar')

@section('title', $pageTitle)

@section('content')
<div class="container-fluid">

    <!-- 헤더 -->
    <section class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">{{ $pageTitle }}</h2>
                    <p class="text-muted mb-0">파트너 신청을 검토하고 승인/거부 처리합니다</p>
                </div>
                <div>
                    <a href="{{ route('admin.partner.applications.index') }}" class="btn btn-outline-secondary me-2">
                        <i class="fe fe-file-text me-2"></i>전체 지원서
                    </a>
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fe fe-filter me-2"></i>빠른 필터
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="?status=pending">검토 대기</a></li>
                            <li><a class="dropdown-item" href="?status=interview">면접 예정</a></li>
                            <li><a class="dropdown-item" href="?status=reapplied">재신청</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="?">전체 보기</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 통계 카드 -->
    <section class="row mb-4">
        <div class="col-lg-3 col-md-6">
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
                            <h3 class="mb-0 fw-bold">{{ $statistics['counts']['pending'] }}</h3>
                            @if($statistics['urgent_count'] > 0)
                                <small class="text-danger">{{ $statistics['urgent_count'] }}개 긴급</small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
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
                            <h3 class="mb-0 fw-bold">{{ $statistics['counts']['interview'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-gradient rounded-circle p-3 stat-circle">
                                <i class="fe fe-check-circle text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 text-muted">승인률</h6>
                            <h3 class="mb-0 fw-bold">{{ $statistics['approval_rate'] }}%</h3>
                            <small class="text-muted">이번 달 {{ $statistics['this_month']['approved'] }}명 승인</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-gradient rounded-circle p-3 stat-circle">
                                <i class="fe fe-trending-up text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 text-muted">평균 처리일</h6>
                            <h3 class="mb-0 fw-bold">{{ $statistics['avg_processing_days'] }}</h3>
                            <small class="text-muted">일</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 필터 -->
    <section class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.partner.approval.index') }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search">검색</label>
                            <input type="text"
                                   id="search"
                                   name="search"
                                   class="form-control"
                                   placeholder="이름, 이메일로 검색..."
                                   value="{{ $currentFilters['search'] }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="status">상태</label>
                            <select id="status" name="status" class="form-control">
                                <option value="all" {{ $currentFilters['status'] === 'all' ? 'selected' : '' }}>전체</option>
                                @foreach($filterOptions['statuses'] as $value => $label)
                                    <option value="{{ $value }}" {{ $currentFilters['status'] === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="sort_by">정렬</label>
                            <select id="sort_by" name="sort_by" class="form-control">
                                @foreach($filterOptions['sort_options'] as $value => $label)
                                    <option value="{{ $value }}" {{ $currentFilters['sort_by'] === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="per_page">표시 개수</label>
                            <select id="per_page" name="per_page" class="form-control">
                                @foreach($filterOptions['per_page_options'] as $option)
                                    <option value="{{ $option }}" {{ $currentFilters['per_page'] == $option ? 'selected' : '' }}>
                                        {{ $option }}개
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-outline-primary me-2">
                            <i class="fe fe-search me-1"></i>검색
                        </button>
                        <a href="{{ route('admin.partner.approval.index') }}" class="btn btn-outline-secondary">
                            <i class="fe fe-refresh-cw me-1"></i>초기화
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- 지원서 목록 -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">파트너 신청 목록</h5>
                <small class="text-muted">
                    총 {{ $applications->total() }}개 중 {{ $applications->count() }}개 표시
                </small>
            </div>
        </div>
        <div class="card-body p-0">
            @if($applications->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="200">지원자 정보</th>
                                <th width="100">상태</th>
                                <th width="150">추천인 정보</th>
                                <th width="80">경력</th>
                                <th width="100">지원일</th>
                                <th width="80">완성도</th>
                                <th width="120">관리</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($applications as $application)
                            <tr>
                                <td>
                                    <div>
                                        <strong>{{ $application->personal_info['name'] ?? $application->user->name ?? 'Unknown' }}</strong>
                                        <br><small class="text-muted">{{ $application->personal_info['email'] ?? $application->user->email ?? 'No email' }}</small>
                                    </div>
                                </td>
                                <td>
                                    @if($application->application_status === 'submitted')
                                        <span class="badge bg-primary">제출됨</span>
                                    @elseif($application->application_status === 'reviewing')
                                        <span class="badge bg-warning">검토 중</span>
                                    @elseif($application->application_status === 'interview')
                                        <span class="badge bg-info">면접 예정</span>
                                    @elseif($application->application_status === 'approved')
                                        <span class="badge bg-success">승인됨</span>
                                    @elseif($application->application_status === 'rejected')
                                        <span class="badge bg-danger">반려됨</span>
                                    @elseif($application->application_status === 'reapplied')
                                        <span class="badge bg-secondary">재신청</span>
                                    @else
                                        <span class="badge bg-light text-dark">{{ $application->application_status }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($application->referrerPartner)
                                        <div class="small">
                                            <strong class="text-primary">{{ $application->referrerPartner->partner_code ?? 'N/A' }}</strong>
                                            <br><small class="text-muted">{{ $application->referrerPartner->name ?? '알 수 없음' }}</small>
                                        </div>
                                    @else
                                        <small class="text-muted">직접 신청</small>
                                    @endif
                                </td>
                                <td>
                                    {{ $application->experience_info['total_years'] ?? 0 }}년
                                </td>
                                <td>
                                    {{ $application->created_at->format('m-d') }}
                                    @if($application->created_at < now()->subDays(7))
                                        <br><small class="text-danger">긴급</small>
                                    @endif
                                </td>
                                <td>
                                    @php $score = $application->getCompletenessScore() @endphp
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar
                                            @if($score >= 80) bg-success
                                            @elseif($score >= 60) bg-warning
                                            @else bg-danger @endif"
                                            style="width: {{ $score }}%"></div>
                                    </div>
                                    <small class="text-muted">{{ $score }}%</small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.partner.approval.show', $application->id) }}"
                                           class="btn btn-outline-primary"
                                           title="상세보기">
                                            <i class="fe fe-eye"></i>
                                        </a>
                                        @if(in_array($application->application_status, ['submitted', 'reviewing', 'interview', 'reapplied']))
                                            <button type="button"
                                                    class="btn btn-outline-success"
                                                    title="승인"
                                                    onclick="quickApprove({{ $application->id }})">
                                                <i class="fe fe-check"></i>
                                            </button>
                                            <button type="button"
                                                    class="btn btn-outline-danger"
                                                    title="거부"
                                                    onclick="quickReject({{ $application->id }})">
                                                <i class="fe fe-x"></i>
                                            </button>
                                        @elseif($application->application_status === 'approved')
                                            <button type="button"
                                                    class="btn btn-outline-warning"
                                                    title="승인 취소"
                                                    onclick="quickRevoke({{ $application->id }})">
                                                <i class="fe fe-rotate-ccw"></i>
                                            </button>
                                        @endif
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
                            전체 {{ $applications->total() }}개 중
                            {{ $applications->firstItem() }}~{{ $applications->lastItem() }}개 표시
                        </div>
                        <div>
                            {{ $applications->appends(request()->query())->links('pagination::bootstrap-4') }}
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fe fe-users fe-3x text-muted mb-3"></i>
                    <h5 class="text-muted">조건에 맞는 파트너 신청이 없습니다</h5>
                    <p class="text-muted">필터를 조정하거나 새로운 신청을 기다려주세요.</p>
                    <a href="{{ route('admin.partner.approval.index') }}" class="btn btn-outline-primary">
                        <i class="fe fe-refresh-cw me-2"></i>전체 보기
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- 최근 활동 -->
    @if($recentActivities->count() > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">최근 활동</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        @foreach($recentActivities as $activity)
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">{{ $activity['action'] }}</h6>
                                <p class="mb-1">{{ $activity['user_name'] }}</p>
                                <small class="text-muted">
                                    @if($activity['date'] && method_exists($activity['date'], 'diffForHumans'))
                                        {{ $activity['date']->diffForHumans() }}
                                    @elseif($activity['date'])
                                        {{ \Carbon\Carbon::parse($activity['date'])->diffForHumans() }}
                                    @else
                                        시간 미정
                                    @endif
                                </small>
                                @if($activity['admin'])
                                    <small class="text-muted">by {{ $activity['admin']->name }}</small>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Quick Action Modals -->
@includeIf("jiny-partner::admin.partner-approval.partials.quick_approve_modal")

<div class="modal fade" id="quickRejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">빠른 거부</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>이 파트너 신청을 거부하시겠습니까?</p>
                <div class="form-group">
                    <label for="rejection_reason">거부 사유 <span class="text-danger">*</span></label>
                    <textarea id="rejection_reason" class="form-control" rows="3" placeholder="거부 사유를 입력하세요..." required></textarea>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="notify_user_reject" checked>
                    <label class="form-check-label" for="notify_user_reject">
                        사용자에게 알림 전송
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-danger" onclick="confirmReject()">거부 확인</button>
            </div>
        </div>
    </div>
</div>

<!-- 승인 취소 모달 -->
<div class="modal fade" id="quickRevokeModal" tabindex="-1" aria-labelledby="quickRevokeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">승인 취소</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning d-flex align-items-center" role="alert">
                    <i class="fe fe-alert-triangle me-2"></i>
                    <div>
                        <strong>주의!</strong> 이 작업은 파트너 회원 등록을 해제합니다.
                    </div>
                </div>
                <p>이 파트너 신청의 승인을 취소하시겠습니까?</p>
                <div class="bg-light p-3 rounded mb-3">
                    <small class="text-muted">
                        <strong>승인 취소 시 처리 사항:</strong><br>
                        • 파트너 회원 계정 삭제<br>
                        • 신청 상태를 "검토 중"으로 변경<br>
                        • 파트너 권한 및 데이터 초기화
                    </small>
                </div>
                <div class="form-group">
                    <label for="revoke_reason">취소 사유 <span class="text-danger">*</span></label>
                    <textarea id="revoke_reason" class="form-control" rows="3" placeholder="승인 취소 사유를 입력하세요..." required></textarea>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="notify_user_revoke" checked>
                    <label class="form-check-label" for="notify_user_revoke">
                        사용자에게 알림 전송
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-warning" onclick="confirmRevoke()">승인 취소 확인</button>
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

/* 타임라인 스타일 */
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    padding-bottom: 20px;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -22px;
    top: 20px;
    width: 2px;
    height: calc(100% - 10px);
    background-color: #e9ecef;
}

.timeline-marker {
    position: absolute;
    left: -26px;
    top: 4px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: #007bff;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #e9ecef;
}

.timeline-content h6 {
    margin-bottom: 2px;
}
</style>
@endpush

@push('scripts')
<script>
let currentApplicationId = null;

function quickReject(applicationId) {
    currentApplicationId = applicationId;
    new bootstrap.Modal(document.getElementById('quickRejectModal')).show();
}

function confirmReject() {
    if (!currentApplicationId) return;

    const reason = document.getElementById('rejection_reason').value;
    const notify = document.getElementById('notify_user_reject').checked;

    if (!reason.trim()) {
        alert('거부 사유를 입력해주세요.');
        return;
    }

    // FormData로 요청 데이터 생성 (상세페이지와 동일한 방식)
    const formData = new FormData();
    formData.append('rejection_reason', reason);
    formData.append('notify_user', notify ? '1' : '0');

    // 거부 요청 전송
    fetch(`{{ url('admin/partner/approval') }}/${currentApplicationId}/reject`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('거부 처리 중 오류가 발생했습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('거부 처리 중 오류가 발생했습니다.');
    });

    bootstrap.Modal.getInstance(document.getElementById('quickRejectModal')).hide();
}

// 승인 취소 모달 표시
function quickRevoke(applicationId) {
    currentApplicationId = applicationId;

    // 취소 사유 초기화
    document.getElementById('revoke_reason').value = '';

    // 모달 표시
    const modal = new bootstrap.Modal(document.getElementById('quickRevokeModal'));
    modal.show();
}

// 승인 취소 확인
function confirmRevoke() {
    if (!currentApplicationId) return;

    const reason = document.getElementById('revoke_reason').value;
    const notify = document.getElementById('notify_user_revoke').checked;

    if (!reason.trim()) {
        alert('취소 사유를 입력해주세요.');
        return;
    }

    // FormData로 요청 데이터 생성
    const formData = new FormData();
    formData.append('revoke_reason', reason);
    formData.append('notify_user', notify ? '1' : '0');

    // 승인 취소 요청 전송
    fetch(`{{ url('admin/partner/approval') }}/${currentApplicationId}/revoke`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('승인이 성공적으로 취소되었습니다.');
            location.reload();
        } else {
            alert(data.message || '승인 취소 처리 중 오류가 발생했습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('승인 취소 처리 중 오류가 발생했습니다.');
    });

    bootstrap.Modal.getInstance(document.getElementById('quickRevokeModal')).hide();
}

</script>
@endpush
