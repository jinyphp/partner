@extends('jiny-partner::layouts.admin.sidebar')

@section('content')
<div class="container-fluid px-6 py-4">
    <!-- Page Header -->
    <div class="row">
        <div class="col-lg-12 col-md-12 col-12">
            <div class="border-bottom pb-3 mb-3 d-lg-flex align-items-center justify-content-between">
                <div class="mb-2 mb-lg-0">
                    <h1 class="mb-0 h2 fw-bold">{{ $pageTitle }}</h1>
                    <p class="mb-0 text-muted">파트너 네트워크의 계층 구조를 조정하고 관리합니다.</p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#movePartnerModal">
                        <i class="fe fe-move me-2"></i>파트너 이동
                    </button>
                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#restructureModal">
                        <i class="fe fe-refresh-cw me-2"></i>구조 재편
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hierarchy Overview -->
    <div class="row mb-4">
        <div class="col-xl-2 col-lg-4 col-md-6 col-12">
            <div class="card text-center">
                <div class="card-body">
                    <div class="icon-shape icon-md bg-primary text-white rounded-3 mx-auto mb-2">
                        <i class="fe fe-layers"></i>
                    </div>
                    @php
                        $maxLevel = \Jiny\Partner\Models\PartnerUser::max('level') ?? 0;
                    @endphp
                    <h5 class="mb-0">{{ $maxLevel + 1 }}</h5>
                    <p class="mb-0 text-muted small">총 계층 수</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-12">
            <div class="card text-center">
                <div class="card-body">
                    <div class="icon-shape icon-md bg-success text-white rounded-3 mx-auto mb-2">
                        <i class="fe fe-users"></i>
                    </div>
                    @php
                        $rootCount = \Jiny\Partner\Models\PartnerUser::roots()->count();
                    @endphp
                    <h5 class="mb-0">{{ $rootCount }}</h5>
                    <p class="mb-0 text-muted small">최상위 파트너</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-12">
            <div class="card text-center">
                <div class="card-body">
                    <div class="icon-shape icon-md bg-warning text-white rounded-3 mx-auto mb-2">
                        <i class="fe fe-git-branch"></i>
                    </div>
                    @php
                        $orphanCount = \Jiny\Partner\Models\PartnerUser::where('parent_id', null)
                            ->where('level', '>', 0)
                            ->count();
                    @endphp
                    <h5 class="mb-0">{{ $orphanCount }}</h5>
                    <p class="mb-0 text-muted small">연결 끊긴 파트너</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-12">
            <div class="card text-center">
                <div class="card-body">
                    <div class="icon-shape icon-md bg-info text-white rounded-3 mx-auto mb-2">
                        <i class="fe fe-shuffle"></i>
                    </div>
                    <h5 class="mb-0">0</h5>
                    <p class="mb-0 text-muted small">보류 중인 이동</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-12">
            <div class="card text-center">
                <div class="card-body">
                    <div class="icon-shape icon-md bg-secondary text-white rounded-3 mx-auto mb-2">
                        <i class="fe fe-alert-triangle"></i>
                    </div>
                    @php
                        $imbalancedCount = \Jiny\Partner\Models\PartnerUser::where('children_count', '>', 10)->count();
                    @endphp
                    <h5 class="mb-0">{{ $imbalancedCount }}</h5>
                    <p class="mb-0 text-muted small">불균형 노드</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-12">
            <div class="card text-center">
                <div class="card-body">
                    <div class="icon-shape icon-md bg-danger text-white rounded-3 mx-auto mb-2">
                        <i class="fe fe-activity"></i>
                    </div>
                    @php
                        $balanceScore = 85; // Mock balance score
                    @endphp
                    <h5 class="mb-0">{{ $balanceScore }}%</h5>
                    <p class="mb-0 text-muted small">균형 점수</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Hierarchy Structure -->
        <div class="col-lg-8 col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">계층 구조 관리</h5>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary active" onclick="viewHierarchy('levels')">레벨별</button>
                        <button type="button" class="btn btn-outline-primary" onclick="viewHierarchy('balance')">균형도</button>
                        <button type="button" class="btn btn-outline-primary" onclick="viewHierarchy('performance')">성과별</button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="hierarchyView">
                        <!-- Level-based view -->
                        <div class="hierarchy-levels">
                            @for($level = 0; $level <= $maxLevel; $level++)
                                @php
                                    $levelPartners = \Jiny\Partner\Models\PartnerUser::with('partnerTier')
                                        ->where('level', $level)
                                        ->where('status', 'active')
                                        ->limit(10)
                                        ->get();
                                @endphp
                                @if($levelPartners->count() > 0)
                                    <div class="level-group mb-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <h6 class="mb-0 me-3">레벨 {{ $level }}</h6>
                                            <span class="badge bg-primary">{{ $levelPartners->count() }}명</span>
                                            @if($level > 0)
                                                <button class="btn btn-outline-secondary btn-sm ms-auto" onclick="optimizeLevel({{ $level }})">
                                                    <i class="fe fe-zap"></i> 최적화
                                                </button>
                                            @endif
                                        </div>
                                        <div class="row">
                                            @foreach($levelPartners as $partner)
                                                <div class="col-md-3 mb-3">
                                                    <div class="card border">
                                                        <div class="card-body p-3">
                                                            <div class="d-flex align-items-center mb-2">
                                                                <div class="avatar avatar-xs me-2">
                                                                    <span class="avatar-initials rounded-circle bg-primary">
                                                                        {{ strtoupper(substr($partner->name, 0, 1)) }}
                                                                    </span>
                                                                </div>
                                                                <div class="flex-grow-1">
                                                                    <h6 class="mb-0 small">{{ $partner->name }}</h6>
                                                                    <small class="text-muted">{{ $partner->partnerTier->tier_name ?? 'N/A' }}</small>
                                                                </div>
                                                                <div class="dropdown">
                                                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                                                        <i class="fe fe-more-vertical"></i>
                                                                    </button>
                                                                    <ul class="dropdown-menu">
                                                                        <li><a class="dropdown-item" href="#" onclick="movePartner({{ $partner->id }})">이동</a></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="viewDetails({{ $partner->id }})">상세</a></li>
                                                                        <li><a class="dropdown-item" href="#" onclick="analyzePosition({{ $partner->id }})">위치 분석</a></li>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                            <div class="row g-1 text-center">
                                                                <div class="col-6">
                                                                    <div class="small text-muted">하위</div>
                                                                    <div class="fw-bold">{{ $partner->children_count ?? 0 }}</div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="small text-muted">매출</div>
                                                                    <div class="fw-bold">{{ number_format($partner->monthly_sales / 10000) }}만</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endfor
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Management Tools -->
        <div class="col-lg-4 col-md-12">
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">빠른 작업</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary" onclick="findOrphans()">
                            <i class="fe fe-search me-2"></i>고아 노드 찾기
                        </button>
                        <button class="btn btn-outline-warning" onclick="balanceTree()">
                            <i class="fe fe-shuffle me-2"></i>트리 균형 맞추기
                        </button>
                        <button class="btn btn-outline-info" onclick="optimizeStructure()">
                            <i class="fe fe-zap me-2"></i>구조 최적화
                        </button>
                        <button class="btn btn-outline-success" onclick="validateHierarchy()">
                            <i class="fe fe-check-circle me-2"></i>계층 검증
                        </button>
                    </div>
                </div>
            </div>

            <!-- Optimization Suggestions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">최적화 제안</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning alert-sm">
                        <i class="fe fe-alert-triangle me-2"></i>
                        <strong>불균형 감지:</strong> 레벨 2에서 일부 파트너가 과도한 하위 구성원을 보유하고 있습니다.
                    </div>
                    <div class="alert alert-info alert-sm">
                        <i class="fe fe-info-circle me-2"></i>
                        <strong>성장 기회:</strong> 레벨 3의 3명의 파트너가 승급 조건을 충족했습니다.
                    </div>
                    <div class="alert alert-success alert-sm">
                        <i class="fe fe-check-circle me-2"></i>
                        <strong>안정성:</strong> 전체 네트워크의 85%가 안정적인 구조를 유지하고 있습니다.
                    </div>
                </div>
            </div>

            <!-- Hierarchy Health -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">계층 건강도</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small>구조 균형</small>
                            <small>85%</small>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-success" style="width: 85%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small>연결 안정성</small>
                            <small>92%</small>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-success" style="width: 92%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small>성과 분포</small>
                            <small>78%</small>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-warning" style="width: 78%"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small>활동성</small>
                            <small>71%</small>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-warning" style="width: 71%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Move Partner Modal -->
<div class="modal fade" id="movePartnerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">파트너 이동</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="movePartnerForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">이동할 파트너</label>
                        <select name="partner_id" class="form-select" required>
                            <option value="">파트너를 선택하세요</option>
                            @foreach(\Jiny\Partner\Models\PartnerUser::where('status', 'active')->get() as $partner)
                                <option value="{{ $partner->id }}">{{ $partner->name }} ({{ $partner->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">새로운 상위 파트너</label>
                        <select name="new_parent_id" class="form-select" required>
                            <option value="">상위 파트너를 선택하세요</option>
                            @foreach(\Jiny\Partner\Models\PartnerUser::where('status', 'active')->get() as $partner)
                                <option value="{{ $partner->id }}">{{ $partner->name }} ({{ $partner->email }}) - 레벨 {{ $partner->level }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">이동 사유</label>
                        <textarea name="reason" class="form-control" rows="3" required
                                  placeholder="파트너를 이동하는 사유를 입력하세요"></textarea>
                    </div>

                    <div class="alert alert-warning">
                        <i class="fe fe-alert-triangle me-2"></i>
                        <strong>주의:</strong> 파트너 이동은 하위 네트워크 전체에 영향을 미칠 수 있습니다.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">이동 실행</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Restructure Modal -->
<div class="modal fade" id="restructureModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">구조 재편</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fe fe-alert-triangle me-2"></i>
                    <strong>경고:</strong> 구조 재편은 전체 네트워크에 중대한 영향을 미칩니다. 신중하게 진행하세요.
                </div>

                <div class="mb-3">
                    <label class="form-label">재편 유형</label>
                    <select class="form-select" id="restructureType">
                        <option value="balance">균형 재편 (불균형 해소)</option>
                        <option value="optimize">성과 최적화 재편</option>
                        <option value="consolidate">계층 통합 재편</option>
                        <option value="custom">사용자 정의 재편</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">적용 범위</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="scope" value="all" checked>
                        <label class="form-check-label">전체 네트워크</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="scope" value="level">
                        <label class="form-check-label">특정 레벨만</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="scope" value="branch">
                        <label class="form-check-label">특정 브랜치만</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">재편 목표</label>
                    <textarea class="form-control" rows="3"
                              placeholder="재편을 통해 달성하고자 하는 목표를 설명하세요"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-warning" onclick="previewRestructure()">미리보기</button>
                <button type="button" class="btn btn-danger" onclick="executeRestructure()">재편 실행</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewHierarchy(mode) {
    const buttons = document.querySelectorAll('.card-header .btn-group .btn');
    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');

    const hierarchyView = document.getElementById('hierarchyView');

    if (mode === 'levels') {
        // Already showing levels view
    } else if (mode === 'balance') {
        hierarchyView.innerHTML = `
            <div class="text-center py-5">
                <i class="fe fe-shuffle display-4 text-warning"></i>
                <h5 class="mt-3">균형도 분석 뷰</h5>
                <p class="text-muted">각 노드의 균형 상태를 분석합니다.</p>
                <button class="btn btn-warning" onclick="analyzeBalance()">균형도 분석 시작</button>
            </div>
        `;
    } else if (mode === 'performance') {
        hierarchyView.innerHTML = `
            <div class="text-center py-5">
                <i class="fe fe-trending-up display-4 text-success"></i>
                <h5 class="mt-3">성과별 분석 뷰</h5>
                <p class="text-muted">성과를 기준으로 계층 구조를 분석합니다.</p>
                <button class="btn btn-success" onclick="analyzePerformance()">성과 분석 시작</button>
            </div>
        `;
    }
}

function movePartner(partnerId) {
    document.querySelector('select[name="partner_id"]').value = partnerId;
    $('#movePartnerModal').modal('show');
}

function viewDetails(partnerId) {
    window.open(`/admin/partner/users/${partnerId}`, '_blank');
}

function analyzePosition(partnerId) {
    alert(`파트너 ID ${partnerId}의 위치를 분석합니다.`);
}

function optimizeLevel(level) {
    if (confirm(`레벨 ${level}을 최적화하시겠습니까?`)) {
        alert(`레벨 ${level} 최적화를 시작합니다.`);
    }
}

function findOrphans() {
    alert('고아 노드를 검색합니다.');
}

function balanceTree() {
    if (confirm('트리 균형을 맞추시겠습니까? 이 작업은 일부 파트너의 위치를 변경할 수 있습니다.')) {
        alert('트리 균형 맞추기를 시작합니다.');
    }
}

function optimizeStructure() {
    if (confirm('구조를 최적화하시겠습니까?')) {
        alert('구조 최적화를 시작합니다.');
    }
}

function validateHierarchy() {
    alert('계층 구조를 검증합니다.');
}

function analyzeBalance() {
    alert('균형도 분석을 시작합니다.');
}

function analyzePerformance() {
    alert('성과 분석을 시작합니다.');
}

function previewRestructure() {
    alert('재편 미리보기를 생성합니다.');
}

function executeRestructure() {
    if (confirm('정말로 구조 재편을 실행하시겠습니까? 이 작업은 되돌릴 수 없습니다.')) {
        alert('구조 재편을 실행합니다.');
        $('#restructureModal').modal('hide');
    }
}

// Move Partner Form
document.getElementById('movePartnerForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    if (confirm('파트너를 이동하시겠습니까?')) {
        // Here you would make an AJAX call to move the partner
        alert('파트너 이동을 실행합니다.');
        $('#movePartnerModal').modal('hide');
        this.reset();
    }
});
</script>
@endsection