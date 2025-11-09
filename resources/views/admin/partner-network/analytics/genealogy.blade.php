@extends('jiny-partner::layouts.admin.sidebar')

@section('content')
<div class="container-fluid px-6 py-4">
    <!-- Page Header -->
    <div class="row">
        <div class="col-lg-12 col-md-12 col-12">
            <div class="border-bottom pb-3 mb-3 d-lg-flex align-items-center justify-content-between">
                <div class="mb-2 mb-lg-0">
                    <h1 class="mb-0 h2 fw-bold">{{ $pageTitle }}</h1>
                    <p class="mb-0 text-muted">파트너 계보와 혈통 분석을 통해 네트워크의 기원과 전파 경로를 추적합니다.</p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#searchModal">
                        <i class="fe fe-search me-2"></i>계보 검색
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="generateReport()">
                        <i class="fe fe-file-text me-2"></i>계보 보고서
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Genealogy Overview -->
    <div class="row mb-4">
        <div class="col-xl-3 col-lg-6 col-md-12 col-12">
            <div class="card">
                <div class="card-body text-center">
                    <div class="icon-shape icon-lg bg-primary text-white rounded-3 mx-auto mb-3">
                        <i class="fe fe-users"></i>
                    </div>
                    @php
                        $foundingPartners = \Jiny\Partner\Models\PartnerUser::roots()->count();
                    @endphp
                    <h4 class="mb-0">{{ $foundingPartners }}</h4>
                    <p class="mb-0 text-muted">창립 파트너</p>
                    <small class="text-info">최상위 계보</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-12 col-12">
            <div class="card">
                <div class="card-body text-center">
                    <div class="icon-shape icon-lg bg-success text-white rounded-3 mx-auto mb-3">
                        <i class="fe fe-git-branch"></i>
                    </div>
                    @php
                        $maxDepth = \Jiny\Partner\Models\PartnerUser::max('level') ?? 0;
                    @endphp
                    <h4 class="mb-0">{{ $maxDepth }}</h4>
                    <p class="mb-0 text-muted">최대 깊이</p>
                    <small class="text-success">계층 단계</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-12 col-12">
            <div class="card">
                <div class="card-body text-center">
                    <div class="icon-shape icon-lg bg-warning text-white rounded-3 mx-auto mb-3">
                        <i class="fe fe-trending-up"></i>
                    </div>
                    @php
                        $largestFamily = \Jiny\Partner\Models\PartnerUser::orderBy('total_children_count', 'desc')
                            ->first();
                        $largestFamilySize = $largestFamily ? $largestFamily->total_children_count : 0;
                    @endphp
                    <h4 class="mb-0">{{ $largestFamilySize }}</h4>
                    <p class="mb-0 text-muted">최대 계보</p>
                    <small class="text-warning">하위 구성원 수</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-12 col-12">
            <div class="card">
                <div class="card-body text-center">
                    <div class="icon-shape icon-lg bg-info text-white rounded-3 mx-auto mb-3">
                        <i class="fe fe-award"></i>
                    </div>
                    @php
                        $activeFamilies = \Jiny\Partner\Models\PartnerUser::roots()
                            ->whereHas('descendants', function($query) {
                                $query->where('status', 'active');
                            })->count();
                    @endphp
                    <h4 class="mb-0">{{ $activeFamilies }}</h4>
                    <p class="mb-0 text-muted">활성 계보</p>
                    <small class="text-info">성장 중인 가문</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Genealogy Tree Visualization -->
        <div class="col-lg-8 col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">계보 트리 시각화</h5>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary active" onclick="viewMode('tree')">트리뷰</button>
                        <button type="button" class="btn btn-outline-primary" onclick="viewMode('network')">네트워크뷰</button>
                        <button type="button" class="btn btn-outline-primary" onclick="viewMode('timeline')">타임라인</button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="genealogyVisualization" style="height: 500px; overflow: auto;">
                        <!-- D3.js or other visualization library would go here -->
                        <div class="text-center py-5">
                            <i class="fe fe-git-branch display-3 text-muted"></i>
                            <h5 class="mt-3">계보 시각화</h5>
                            <p class="text-muted">파트너를 선택하여 계보 트리를 확인하세요.</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#searchModal">
                                파트너 검색
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Genealogy Statistics -->
        <div class="col-lg-4 col-md-12">
            <!-- Family Lineages -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">주요 계보 현황</h6>
                </div>
                <div class="card-body p-0">
                    @php
                        $majorLineages = \Jiny\Partner\Models\PartnerUser::roots()
                            ->with('partnerTier')
                            ->orderBy('total_children_count', 'desc')
                            ->limit(5)
                            ->get();
                    @endphp
                    @foreach($majorLineages as $lineage)
                        <div class="d-flex align-items-center p-3 border-bottom">
                            <div class="avatar avatar-md me-3">
                                <span class="avatar-initials rounded-circle bg-primary">
                                    {{ strtoupper(substr($lineage->name, 0, 1)) }}
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0">{{ $lineage->name }} 가문</h6>
                                <small class="text-muted">{{ $lineage->partnerTier->tier_name ?? 'N/A' }}</small>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold">{{ $lineage->total_children_count }}</div>
                                <small class="text-muted">구성원</small>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Genealogy Depth Analysis -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">계층 깊이 분석</h6>
                </div>
                <div class="card-body">
                    <canvas id="depthChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Genealogy Analysis -->
    <div class="row mt-4">
        <!-- Generation Analysis -->
        <div class="col-lg-6 col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">세대별 분석</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>세대</th>
                                    <th>구성원 수</th>
                                    <th>평균 매출</th>
                                    <th>활성율</th>
                                    <th>성장률</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for($generation = 0; $generation <= 5; $generation++)
                                    @php
                                        $generationMembers = \Jiny\Partner\Models\PartnerUser::where('level', $generation)->get();
                                        $memberCount = $generationMembers->count();
                                        $avgSales = $memberCount > 0 ? $generationMembers->avg('monthly_sales') : 0;
                                        $activeCount = $generationMembers->where('status', 'active')->count();
                                        $activeRate = $memberCount > 0 ? round(($activeCount / $memberCount) * 100, 1) : 0;
                                        $growthRate = rand(-5, 15); // Mock growth rate
                                    @endphp
                                    @if($memberCount > 0)
                                        <tr>
                                            <td>
                                                <span class="badge bg-primary">{{ $generation }}세대</span>
                                            </td>
                                            <td>{{ number_format($memberCount) }}명</td>
                                            <td>{{ number_format($avgSales) }}원</td>
                                            <td>{{ $activeRate }}%</td>
                                            <td>
                                                <span class="text-{{ $growthRate >= 0 ? 'success' : 'danger' }}">
                                                    {{ $growthRate >= 0 ? '+' : '' }}{{ $growthRate }}%
                                                </span>
                                            </td>
                                        </tr>
                                    @endif
                                @endfor
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bloodline Success Stories -->
        <div class="col-lg-6 col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">계보 성공 사례</h5>
                </div>
                <div class="card-body">
                    @php
                        $successStories = [
                            [
                                'founder' => '김대표',
                                'generation' => 4,
                                'members' => 127,
                                'total_sales' => 15000000,
                                'achievement' => '다이아몬드 달성 최다 배출'
                            ],
                            [
                                'founder' => '이회장',
                                'generation' => 5,
                                'members' => 89,
                                'total_sales' => 12000000,
                                'achievement' => '월 매출 1억 돌파'
                            ],
                            [
                                'founder' => '박사장',
                                'generation' => 3,
                                'members' => 156,
                                'total_sales' => 18000000,
                                'achievement' => '최단기간 100명 달성'
                            ]
                        ];
                    @endphp
                    @foreach($successStories as $story)
                        <div class="alert alert-light d-flex align-items-start">
                            <div class="icon-shape icon-sm bg-success text-white rounded-circle me-3">
                                <i class="fe fe-award"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">{{ $story['founder'] }} 계보</h6>
                                <p class="mb-1">
                                    <strong>{{ $story['generation'] }}세대</strong>,
                                    <strong>{{ number_format($story['members']) }}명</strong> 구성원
                                </p>
                                <p class="mb-1">총 매출: <strong>{{ number_format($story['total_sales']) }}원</strong></p>
                                <small class="text-success">🏆 {{ $story['achievement'] }}</small>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Genealogy Timeline -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">계보 발전 타임라인</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        @php
                            $timelineEvents = [
                                ['date' => '2024-01', 'event' => '첫 번째 창립 파트너 가입', 'type' => 'founding'],
                                ['date' => '2024-03', 'event' => '첫 3세대 파트너 탄생', 'type' => 'generation'],
                                ['date' => '2024-06', 'event' => '100명 돌파 달성', 'type' => 'milestone'],
                                ['date' => '2024-09', 'event' => '첫 다이아몬드 티어 달성', 'type' => 'achievement'],
                                ['date' => '2024-11', 'event' => '5세대 네트워크 구축', 'type' => 'expansion']
                            ];
                        @endphp
                        @foreach($timelineEvents as $index => $event)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-{{
                                    $event['type'] === 'founding' ? 'primary' :
                                    ($event['type'] === 'generation' ? 'success' :
                                    ($event['type'] === 'milestone' ? 'warning' :
                                    ($event['type'] === 'achievement' ? 'info' : 'secondary')))
                                }}"></div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">{{ $event['event'] }}</h6>
                                    <small class="text-muted">{{ $event['date'] }}</small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search Modal -->
<div class="modal fade" id="searchModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">계보 검색</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">파트너 검색</label>
                    <input type="text" class="form-control" id="partnerSearch" placeholder="이름 또는 이메일로 검색">
                </div>
                <div class="mb-3">
                    <label class="form-label">계보 유형</label>
                    <select class="form-select" id="genealogyType">
                        <option value="ancestors">상위 계보 (조상)</option>
                        <option value="descendants">하위 계보 (후손)</option>
                        <option value="full">전체 계보</option>
                        <option value="siblings">동급 계보 (형제)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">검색 깊이</label>
                    <select class="form-select" id="searchDepth">
                        <option value="3">3단계</option>
                        <option value="5">5단계</option>
                        <option value="all">전체</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" onclick="searchGenealogy()">검색</button>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding: 20px 0;
}

.timeline:before {
    content: '';
    position: absolute;
    top: 0;
    left: 25px;
    height: 100%;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 30px;
    padding-left: 60px;
}

.timeline-marker {
    position: absolute;
    left: 15px;
    top: 0;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 3px solid white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.timeline-content {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Depth Distribution Chart
const depthCtx = document.getElementById('depthChart').getContext('2d');
const depthChart = new Chart(depthCtx, {
    type: 'doughnut',
    data: {
        labels: ['1세대', '2세대', '3세대', '4세대', '5세대+'],
        datasets: [{
            data: [5, 25, 45, 35, 15],
            backgroundColor: [
                '#007bff',
                '#28a745',
                '#ffc107',
                '#fd7e14',
                '#6f42c1'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    usePointStyle: true,
                    padding: 20
                }
            }
        }
    }
});

function viewMode(mode) {
    const buttons = document.querySelectorAll('.card-header .btn-group .btn');
    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');

    const visualization = document.getElementById('genealogyVisualization');

    if (mode === 'tree') {
        visualization.innerHTML = `
            <div class="text-center py-5">
                <i class="fe fe-git-branch display-3 text-primary"></i>
                <h5 class="mt-3">트리 시각화 모드</h5>
                <p class="text-muted">계층적 트리 구조로 계보를 표시합니다.</p>
            </div>
        `;
    } else if (mode === 'network') {
        visualization.innerHTML = `
            <div class="text-center py-5">
                <i class="fe fe-share-2 display-3 text-success"></i>
                <h5 class="mt-3">네트워크 시각화 모드</h5>
                <p class="text-muted">관계 네트워크 형태로 연결을 표시합니다.</p>
            </div>
        `;
    } else if (mode === 'timeline') {
        visualization.innerHTML = `
            <div class="text-center py-5">
                <i class="fe fe-clock display-3 text-warning"></i>
                <h5 class="mt-3">타임라인 모드</h5>
                <p class="text-muted">시간 순서로 계보 발전을 추적합니다.</p>
            </div>
        `;
    }
}

function searchGenealogy() {
    const partnerSearch = document.getElementById('partnerSearch').value;
    const genealogyType = document.getElementById('genealogyType').value;
    const searchDepth = document.getElementById('searchDepth').value;

    if (!partnerSearch) {
        alert('파트너를 검색해주세요.');
        return;
    }

    alert(`"${partnerSearch}"의 ${genealogyType} 계보를 ${searchDepth}단계 깊이로 검색합니다.`);
    $('#searchModal').modal('hide');

    // Update visualization with search results
    const visualization = document.getElementById('genealogyVisualization');
    visualization.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">검색 중...</span>
            </div>
            <h5>계보 검색 중...</h5>
            <p class="text-muted">${partnerSearch}의 계보를 분석하고 있습니다.</p>
        </div>
    `;

    // Simulate search completion
    setTimeout(() => {
        visualization.innerHTML = `
            <div class="alert alert-success">
                <h6 class="mb-2">${partnerSearch}의 계보 검색 완료</h6>
                <p class="mb-0">총 15명의 관련 파트너를 찾았습니다. (상위 3명, 하위 12명)</p>
            </div>
            <div class="genealogy-tree">
                <!-- Here would be the actual genealogy visualization -->
                <div class="text-center py-4">
                    <p class="text-muted">계보 트리가 여기에 표시됩니다.</p>
                </div>
            </div>
        `;
    }, 2000);
}

function generateReport() {
    alert('계보 분석 보고서를 생성합니다.');
}
</script>
@endsection