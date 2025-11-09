@extends('jiny-partner::layouts.admin.sidebar')

@section('content')
<div class="container-fluid px-6 py-4">
    <!-- Page Header -->
    <div class="row">
        <div class="col-lg-12 col-md-12 col-12">
            <div class="border-bottom pb-3 mb-3 d-lg-flex align-items-center justify-content-between">
                <div class="mb-2 mb-lg-0">
                    <h1 class="mb-0 h2 fw-bold">{{ $pageTitle }}</h1>
                    <p class="mb-0 text-muted">파트너 네트워크의 계층 구조를 트리 형태로 확인하고 관리합니다.</p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#treeOptionsModal">
                        <i class="fe fe-settings me-2"></i>트리 옵션
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
                            <h4 class="mb-0">{{ number_format($statistics['total_partners']) }}</h4>
                            <p class="mb-0 text-muted">전체 파트너</p>
                        </div>
                        <div class="icon-shape icon-md bg-primary text-white rounded-3">
                            <i class="fe fe-users"></i>
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
                            <h4 class="mb-0">{{ number_format($statistics['active_partners']) }}</h4>
                            <p class="mb-0 text-muted">활성 파트너</p>
                        </div>
                        <div class="icon-shape icon-md bg-success text-white rounded-3">
                            <i class="fe fe-user-check"></i>
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
                            <h4 class="mb-0">{{ number_format($statistics['total_sales']) }}원</h4>
                            <p class="mb-0 text-muted">총 매출</p>
                        </div>
                        <div class="icon-shape icon-md bg-info text-white rounded-3">
                            <i class="fe fe-dollar-sign"></i>
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
                            <h4 class="mb-0">{{ $statistics['average_team_size'] }}</h4>
                            <p class="mb-0 text-muted">평균 팀 규모</p>
                        </div>
                        <div class="icon-shape icon-md bg-warning text-white rounded-3">
                            <i class="fe fe-git-branch"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tree Visualization -->
    <div class="row">
        <div class="col-lg-8 col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">네트워크 트리 구조</h4>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="expandAll()">
                            <i class="fe fe-plus-circle"></i> 전체 확장
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="collapseAll()">
                            <i class="fe fe-minus-circle"></i> 전체 접기
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="networkTree" class="tree-container">
                        @if($tree && $tree->count() > 0)
                            @foreach($tree as $rootNode)
                                @include('jiny-partner::admin.partner-network.partials.tree-node', ['node' => $rootNode, 'isRoot' => true])
                            @endforeach
                        @else
                            <div class="text-center py-5">
                                <i class="fe fe-git-branch display-4 text-muted"></i>
                                <h5 class="mt-3">네트워크 데이터가 없습니다</h5>
                                <p class="text-muted">파트너를 등록하고 네트워크를 구축해보세요.</p>
                                <a href="{{ route('admin.partner.users.create') }}" class="btn btn-primary">
                                    파트너 등록하기
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Network Statistics Sidebar -->
        <div class="col-lg-4 col-md-12">
            <!-- Level Distribution -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">레벨별 분포</h5>
                </div>
                <div class="card-body">
                    @forelse($statistics['level_distribution'] as $level => $count)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>레벨 {{ $level }}</span>
                            <span class="badge bg-primary">{{ $count }}명</span>
                        </div>
                        <div class="progress mb-3" style="height: 6px;">
                            <div class="progress-bar" role="progressbar"
                                 style="width: {{ $statistics['total_partners'] > 0 ? ($count / $statistics['total_partners']) * 100 : 0 }}%"></div>
                        </div>
                    @empty
                        <p class="text-muted">데이터가 없습니다.</p>
                    @endforelse
                </div>
            </div>

            <!-- Tier Distribution -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">티어별 분포</h5>
                </div>
                <div class="card-body">
                    @forelse($statistics['tier_distribution'] as $tierName => $count)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>{{ $tierName }}</span>
                            <span class="badge bg-info">{{ $count }}명</span>
                        </div>
                        <div class="progress mb-3" style="height: 6px;">
                            <div class="progress-bar bg-info" role="progressbar"
                                 style="width: {{ $statistics['total_partners'] > 0 ? ($count / $statistics['total_partners']) * 100 : 0 }}%"></div>
                        </div>
                    @empty
                        <p class="text-muted">데이터가 없습니다.</p>
                    @endforelse
                </div>
            </div>

            <!-- Recruitment Stats -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">모집 활동</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>모집 가능 파트너</span>
                        <strong>{{ number_format($statistics['recruitment_stats']['total_recruiters']) }}명</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>활성 관계</span>
                        <strong>{{ number_format($statistics['recruitment_stats']['total_relationships']) }}건</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>이번 달 모집</span>
                        <strong class="text-success">{{ number_format($statistics['recruitment_stats']['this_month_recruits']) }}건</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tree Options Modal -->
<div class="modal fade" id="treeOptionsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">트리 표시 옵션</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="GET" action="{{ route('admin.partner.network.tree') }}">
                    <div class="mb-3">
                        <label class="form-label">최대 깊이</label>
                        <select name="max_depth" class="form-select">
                            <option value="3" {{ $maxDepth == 3 ? 'selected' : '' }}>3단계</option>
                            <option value="5" {{ $maxDepth == 5 ? 'selected' : '' }}>5단계</option>
                            <option value="7" {{ $maxDepth == 7 ? 'selected' : '' }}>7단계</option>
                            <option value="10" {{ $maxDepth == 10 ? 'selected' : '' }}>10단계</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">루트 파트너</label>
                        <select name="root_id" class="form-select">
                            <option value="">전체 트리</option>
                            @foreach(\Jiny\Partner\Models\PartnerUser::active()->get() as $partner)
                                <option value="{{ $partner->id }}" {{ request('root_id') == $partner->id ? 'selected' : '' }}>
                                    {{ $partner->name }} ({{ $partner->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="show_inactive" value="1"
                               {{ $showInactive ? 'checked' : '' }}>
                        <label class="form-check-label">
                            비활성 파트너 포함
                        </label>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                        <button type="submit" class="btn btn-primary">적용</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.tree-container {
    max-height: 600px;
    overflow-y: auto;
}

.tree-node {
    margin-left: 20px;
    position: relative;
}

.tree-node:before {
    content: '';
    position: absolute;
    left: -20px;
    top: 20px;
    width: 15px;
    height: 1px;
    background: #dee2e6;
}

.tree-node:after {
    content: '';
    position: absolute;
    left: -20px;
    top: 0;
    width: 1px;
    height: 100%;
    background: #dee2e6;
}

.tree-node:last-child:after {
    height: 20px;
}

.partner-card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 10px;
    background: white;
    position: relative;
}

.partner-card:hover {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.tier-badge {
    position: absolute;
    top: -8px;
    right: 10px;
    z-index: 1;
}
</style>

<script>
function expandAll() {
    $('.tree-node').show();
    $('.collapse-btn').html('<i class="fe fe-minus-circle"></i>');
}

function collapseAll() {
    $('.tree-node .tree-node').hide();
    $('.collapse-btn').html('<i class="fe fe-plus-circle"></i>');
}

function toggleNode(element) {
    const children = $(element).siblings('.tree-node');
    children.toggle();

    const icon = $(element).find('.collapse-btn i');
    if (children.is(':visible')) {
        icon.removeClass('fe-plus-circle').addClass('fe-minus-circle');
    } else {
        icon.removeClass('fe-minus-circle').addClass('fe-plus-circle');
    }
}
</script>
@endsection