@extends('jiny-partner::layouts.admin.sidebar')

@section('content')
<div class="container-fluid px-6 py-4">
    <!-- Page Header -->
    <div class="row">
        <div class="col-lg-12 col-md-12 col-12">
            <div class="border-bottom pb-3 mb-3 d-lg-flex align-items-center justify-content-between">
                <div class="mb-2 mb-lg-0">
                    <h1 class="mb-0 h2 fw-bold">{{ $pageTitle }}</h1>
                    <p class="mb-0 text-muted">파트너별 성과 분석과 네트워크 효율성을 평가합니다.</p>
                </div>
                <div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#periodModal">
                        <i class="fe fe-calendar me-2"></i>기간 설정
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="exportData()">
                        <i class="fe fe-download me-2"></i>내보내기
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Summary -->
    <div class="row mb-4">
        <div class="col-xl-2 col-lg-4 col-md-6 col-12">
            <div class="card text-center">
                <div class="card-body">
                    <div class="icon-shape icon-lg bg-primary text-white rounded-3 mx-auto mb-3">
                        <i class="fe fe-trending-up"></i>
                    </div>
                    <h4 class="mb-0">{{ number_format(\Jiny\Partner\Models\PartnerUser::where('status', 'active')->avg('monthly_sales') ?? 0) }}원</h4>
                    <p class="mb-0 text-muted">평균 월 매출</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-12">
            <div class="card text-center">
                <div class="card-body">
                    <div class="icon-shape icon-lg bg-success text-white rounded-3 mx-auto mb-3">
                        <i class="fe fe-users"></i>
                    </div>
                    <h4 class="mb-0">{{ number_format(\Jiny\Partner\Models\PartnerUser::where('status', 'active')->avg('children_count') ?? 0, 1) }}</h4>
                    <p class="mb-0 text-muted">평균 팀 크기</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-12">
            <div class="card text-center">
                <div class="card-body">
                    <div class="icon-shape icon-lg bg-warning text-white rounded-3 mx-auto mb-3">
                        <i class="fe fe-award"></i>
                    </div>
                    <h4 class="mb-0">{{ number_format(\Jiny\Partner\Models\PartnerCommission::where('status', 'paid')->avg('commission_amount') ?? 0) }}원</h4>
                    <p class="mb-0 text-muted">평균 커미션</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-12">
            <div class="card text-center">
                <div class="card-body">
                    <div class="icon-shape icon-lg bg-info text-white rounded-3 mx-auto mb-3">
                        <i class="fe fe-target"></i>
                    </div>
                    @php
                        $activePeriod = \Jiny\Partner\Models\PartnerUser::where('status', 'active')
                            ->where('last_activity_at', '>=', now()->subDays(30))
                            ->count();
                        $totalActive = \Jiny\Partner\Models\PartnerUser::where('status', 'active')->count();
                        $activityRate = $totalActive > 0 ? round(($activePeriod / $totalActive) * 100, 1) : 0;
                    @endphp
                    <h4 class="mb-0">{{ $activityRate }}%</h4>
                    <p class="mb-0 text-muted">활동률 (30일)</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-12">
            <div class="card text-center">
                <div class="card-body">
                    <div class="icon-shape icon-lg bg-secondary text-white rounded-3 mx-auto mb-3">
                        <i class="fe fe-git-branch"></i>
                    </div>
                    @php
                        $totalRelationships = \Jiny\Partner\Models\PartnerNetworkRelationship::count();
                        $activeRelationships = \Jiny\Partner\Models\PartnerNetworkRelationship::active()->count();
                        $relationshipRate = $totalRelationships > 0 ? round(($activeRelationships / $totalRelationships) * 100, 1) : 0;
                    @endphp
                    <h4 class="mb-0">{{ $relationshipRate }}%</h4>
                    <p class="mb-0 text-muted">관계 유지율</p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6 col-12">
            <div class="card text-center">
                <div class="card-body">
                    <div class="icon-shape icon-lg bg-danger text-white rounded-3 mx-auto mb-3">
                        <i class="fe fe-percent"></i>
                    </div>
                    @php
                        $totalCommission = \Jiny\Partner\Models\PartnerCommission::sum('commission_amount');
                        $totalSales = \Jiny\Partner\Models\PartnerUser::sum('monthly_sales');
                        $commissionRate = $totalSales > 0 ? round(($totalCommission / $totalSales) * 100, 2) : 0;
                    @endphp
                    <h4 class="mb-0">{{ $commissionRate }}%</h4>
                    <p class="mb-0 text-muted">커미션 비율</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Performance Comparison Chart -->
        <div class="col-lg-8 col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">티어별 성과 비교</h5>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary active" onclick="changeMetric('sales')">매출</button>
                        <button type="button" class="btn btn-outline-primary" onclick="changeMetric('commission')">커미션</button>
                        <button type="button" class="btn btn-outline-primary" onclick="changeMetric('team_size')">팀 크기</button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="performanceChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Performance Distribution -->
        <div class="col-lg-4 col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">성과 분포</h5>
                </div>
                <div class="card-body">
                    <canvas id="distributionChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Performers Table -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">성과 순위</h5>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary active" onclick="sortTable('sales')">매출순</button>
                        <button type="button" class="btn btn-outline-primary" onclick="sortTable('commission')">커미션순</button>
                        <button type="button" class="btn btn-outline-primary" onclick="sortTable('team')">팀 크기순</button>
                        <button type="button" class="btn btn-outline-primary" onclick="sortTable('growth')">성장률순</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>순위</th>
                                    <th>파트너</th>
                                    <th>티어</th>
                                    <th>월 매출</th>
                                    <th>팀 매출</th>
                                    <th>커미션</th>
                                    <th>팀 크기</th>
                                    <th>성과 점수</th>
                                    <th>성장률</th>
                                    <th>액션</th>
                                </tr>
                            </thead>
                            <tbody id="performanceTableBody">
                                @php
                                    $topPerformers = \Jiny\Partner\Models\PartnerUser::with(['partnerTier'])
                                        ->where('status', 'active')
                                        ->orderBy('monthly_sales', 'desc')
                                        ->limit(20)
                                        ->get();
                                @endphp
                                @foreach($topPerformers as $index => $performer)
                                    <tr>
                                        <td>
                                            <span class="badge bg-{{ $index < 3 ? ($index === 0 ? 'warning' : ($index === 1 ? 'secondary' : 'info')) : 'light text-dark' }} rounded-circle">
                                                {{ $index + 1 }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm me-2">
                                                    <span class="avatar-initials rounded-circle bg-primary">
                                                        {{ strtoupper(substr($performer->name, 0, 1)) }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">{{ $performer->name }}</h6>
                                                    <small class="text-muted">{{ $performer->email }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($performer->partnerTier)
                                                <span class="badge bg-{{ $performer->partnerTier->tier_color ?? 'secondary' }}">
                                                    {{ $performer->partnerTier->tier_name }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="fw-bold text-primary">{{ number_format($performer->monthly_sales) }}원</td>
                                        <td class="fw-bold text-success">{{ number_format($performer->team_sales ?? 0) }}원</td>
                                        <td class="fw-bold text-warning">
                                            {{ number_format($performer->earned_commissions ?? 0) }}원
                                        </td>
                                        <td>{{ number_format($performer->children_count ?? 0) }}명</td>
                                        <td>
                                            @php
                                                // Simple performance score calculation
                                                $score = min(100,
                                                    ($performer->monthly_sales / 1000000 * 40) +
                                                    ($performer->children_count * 3) +
                                                    (($performer->last_activity_at && $performer->last_activity_at->diffInDays() <= 7) ? 30 : 0)
                                                );
                                            @endphp
                                            <div class="d-flex align-items-center">
                                                <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                                    <div class="progress-bar bg-{{ $score >= 80 ? 'success' : ($score >= 60 ? 'warning' : 'danger') }}"
                                                         style="width: {{ $score }}%"></div>
                                                </div>
                                                <span class="small fw-bold">{{ round($score) }}점</span>
                                            </div>
                                        </td>
                                        <td>
                                            @php
                                                $growth = rand(-10, 25); // Mock growth rate
                                            @endphp
                                            <span class="text-{{ $growth >= 0 ? 'success' : 'danger' }}">
                                                {{ $growth >= 0 ? '+' : '' }}{{ $growth }}%
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('admin.partner.users.show', $performer->id) }}"
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="fe fe-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.partner.network.commission.partner-summary', $performer->id) }}"
                                                   class="btn btn-outline-success btn-sm">
                                                    <i class="fe fe-dollar-sign"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Insights -->
    <div class="row mt-4">
        <div class="col-lg-6 col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">성과 인사이트</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info d-flex align-items-start">
                        <i class="fe fe-info-circle me-2 mt-1"></i>
                        <div>
                            <strong>최고 성과 티어:</strong> 골드 티어가 평균 매출에서 25% 앞서고 있습니다.
                        </div>
                    </div>
                    <div class="alert alert-success d-flex align-items-start">
                        <i class="fe fe-trending-up me-2 mt-1"></i>
                        <div>
                            <strong>성장 동향:</strong> 이번 달 신규 파트너 유입이 전월 대비 15% 증가했습니다.
                        </div>
                    </div>
                    <div class="alert alert-warning d-flex align-items-start">
                        <i class="fe fe-alert-triangle me-2 mt-1"></i>
                        <div>
                            <strong>주의사항:</strong> 브론즈 티어의 활동률이 60% 이하로 낮습니다.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Improvement Suggestions -->
        <div class="col-lg-6 col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">개선 제안</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex align-items-start border-0 px-0">
                            <div class="icon-shape icon-sm bg-primary text-white rounded-circle me-3">
                                <i class="fe fe-users"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">팀 빌딩 강화</h6>
                                <p class="mb-0 text-muted">상위 성과자들의 모집 활동을 더욱 장려하여 네트워크를 확장하세요.</p>
                            </div>
                        </div>
                        <div class="list-group-item d-flex align-items-start border-0 px-0">
                            <div class="icon-shape icon-sm bg-success text-white rounded-circle me-3">
                                <i class="fe fe-target"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">목표 설정</h6>
                                <p class="mb-0 text-muted">각 티어별로 명확한 성과 목표를 설정하고 인센티브를 제공하세요.</p>
                            </div>
                        </div>
                        <div class="list-group-item d-flex align-items-start border-0 px-0">
                            <div class="icon-shape icon-sm bg-warning text-white rounded-circle me-3">
                                <i class="fe fe-book"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">교육 프로그램</h6>
                                <p class="mb-0 text-muted">저성과 파트너들을 위한 교육과 멘토링 프로그램을 운영하세요.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Period Selection Modal -->
<div class="modal fade" id="periodModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">분석 기간 설정</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">분석 기간</label>
                    <select class="form-select" id="periodSelect">
                        <option value="this_month">이번 달</option>
                        <option value="last_month">지난 달</option>
                        <option value="this_quarter">이번 분기</option>
                        <option value="last_quarter">지난 분기</option>
                        <option value="this_year">올해</option>
                        <option value="custom">사용자 정의</option>
                    </select>
                </div>
                <div id="customPeriod" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">시작일</label>
                            <input type="date" class="form-control" id="startDate">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">종료일</label>
                            <input type="date" class="form-control" id="endDate">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" onclick="applyPeriod()">적용</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Performance Chart
const performanceCtx = document.getElementById('performanceChart').getContext('2d');
const performanceChart = new Chart(performanceCtx, {
    type: 'bar',
    data: {
        labels: ['브론즈', '실버', '골드', '플래티넘', '다이아몬드'],
        datasets: [{
            label: '평균 매출 (만원)',
            data: [50, 120, 250, 500, 1000],
            backgroundColor: ['#CD7F32', '#C0C0C0', '#FFD700', '#E5E4E2', '#B9F2FF'],
            borderColor: ['#CD7F32', '#C0C0C0', '#FFD700', '#E5E4E2', '#B9F2FF'],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Distribution Chart
const distributionCtx = document.getElementById('distributionChart').getContext('2d');
const distributionChart = new Chart(distributionCtx, {
    type: 'pie',
    data: {
        labels: ['상위 10%', '상위 30%', '중간 40%', '하위 20%'],
        datasets: [{
            data: [10, 20, 40, 30],
            backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#dc3545']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

function changeMetric(metric) {
    const buttons = document.querySelectorAll('.card-header .btn-group .btn');
    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');

    // Update chart data based on metric
    if (metric === 'sales') {
        performanceChart.data.datasets[0].label = '평균 매출 (만원)';
        performanceChart.data.datasets[0].data = [50, 120, 250, 500, 1000];
    } else if (metric === 'commission') {
        performanceChart.data.datasets[0].label = '평균 커미션 (만원)';
        performanceChart.data.datasets[0].data = [5, 15, 35, 75, 150];
    } else if (metric === 'team_size') {
        performanceChart.data.datasets[0].label = '평균 팀 크기 (명)';
        performanceChart.data.datasets[0].data = [2, 5, 12, 25, 50];
    }

    performanceChart.update();
}

function sortTable(criteria) {
    // Update table sorting
    const buttons = document.querySelectorAll('.card-header .btn-group .btn');
    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');

    // Here you would implement actual sorting logic
    alert(`테이블을 ${criteria} 기준으로 정렬합니다.`);
}

function exportData() {
    alert('성과 데이터를 Excel 파일로 내보냅니다.');
}

function applyPeriod() {
    const period = document.getElementById('periodSelect').value;
    alert(`${period} 기간으로 분석을 업데이트합니다.`);
    $('#periodModal').modal('hide');
}

// Period selection handler
document.getElementById('periodSelect').addEventListener('change', function() {
    const customPeriod = document.getElementById('customPeriod');
    if (this.value === 'custom') {
        customPeriod.style.display = 'block';
    } else {
        customPeriod.style.display = 'none';
    }
});
</script>
@endsection