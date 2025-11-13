@extends('jiny-partner::layouts.admin.sidebar')

@section('title', $pageTitle)

@section('content')
<div class="container-fluid">
    <!-- 헤더 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-1">{{ $pageTitle }}</h3>
                    <p class="text-muted mb-0">파트너 현황과 성과를 한눈에 확인하세요</p>
                </div>
                <div>
                    <button class="btn btn-outline-primary btn-sm me-2" onclick="refreshData()">
                        <i class="fe fe-refresh-cw me-1"></i>새로고침
                    </button>
                    <button class="btn btn-primary btn-sm">
                        <i class="fe fe-download me-1"></i>보고서 다운로드
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 기본 통계 카드들 -->
    <div class="row mb-4">
        <div class="col-xl-3 col-lg-6 col-md-6 col-12">
            <div class="card bg-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ number_format($stats['total_partners']) }}</h4>
                            <p class="mb-0">전체 파트너</p>
                        </div>
                        <div class="icon-shape icon-md bg-primary text-white rounded-3">
                            <i class="fe fe-users"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6 col-12">
            <div class="card bg-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ number_format($stats['active_partners']) }}</h4>
                            <p class="mb-0">활성 파트너</p>
                        </div>
                        <div class="icon-shape icon-md bg-success text-white rounded-3">
                            <i class="fe fe-user-check"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6 col-12">
            <div class="card bg-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ number_format($stats['this_month_partners']) }}</h4>
                            <p class="mb-0">이번 달 신규</p>
                            @if($stats['growth_rate'] > 0)
                                <small class="text-success"><i class="fe fe-trending-up"></i> +{{ $stats['growth_rate'] }}%</small>
                            @elseif($stats['growth_rate'] < 0)
                                <small class="text-danger"><i class="fe fe-trending-down"></i> {{ $stats['growth_rate'] }}%</small>
                            @else
                                <small class="text-muted">변화 없음</small>
                            @endif
                        </div>
                        <div class="icon-shape icon-md bg-info text-white rounded-3">
                            <i class="fe fe-trending-up"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6 col-12">
            <div class="card bg-white">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ number_format($stats['pending_applications']) }}</h4>
                            <p class="mb-0">대기 중인 지원서</p>
                        </div>
                        <div class="icon-shape icon-md bg-warning text-white rounded-3">
                            <i class="fe fe-file-text"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- 파트너 등록 추이 차트 -->
        <div class="col-xl-8 col-lg-8 col-md-12 col-12">
            <div class="card bg-white">
                <div class="card-header">
                    <h5 class="mb-0">파트너 등록 추이 (최근 6개월)</h5>
                </div>
                <div class="card-body">
                    <canvas id="partnerGrowthChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- 등급별 분포 -->
        <div class="col-xl-4 col-lg-4 col-md-12 col-12">
            <div class="card bg-white">
                <div class="card-header">
                    <h5 class="mb-0">등급별 분포</h5>
                </div>
                <div class="card-body">
                    <canvas id="tierDistributionChart" height="150"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <!-- 최고 성과 파트너 -->
        <div class="col-xl-8 col-lg-8 col-md-12 col-12">
            <div class="card bg-white">
                <div class="card-header">
                    <h5 class="mb-0">최고 성과 파트너 TOP 10</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>순위</th>
                                    <th>파트너명</th>
                                    <th>등급</th>
                                    <th>하위 수</th>
                                    <th>월 매출</th>
                                    <th>성과 점수</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topPerformers as $index => $performer)
                                <tr>
                                    <td>
                                        @if($index < 3)
                                            <span class="badge bg-warning">{{ $index + 1 }}</span>
                                        @else
                                            <span class="text-muted">{{ $index + 1 }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div>
                                            <div class="fw-medium">{{ $performer['name'] }}</div>
                                            <small class="text-muted">{{ $performer['email'] }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $performer['tier'] === 'Diamond' ? 'primary' : ($performer['tier'] === 'Gold' ? 'warning' : 'secondary') }}">
                                            {{ $performer['tier'] }}
                                        </span>
                                    </td>
                                    <td>{{ $performer['children_count'] }}명</td>
                                    <td>{{ number_format($performer['monthly_sales'] / 10000) }}만원</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                                <div class="progress-bar bg-success" style="width: {{ $performer['performance_score'] }}%"></div>
                                            </div>
                                            <small class="text-muted">{{ $performer['performance_score'] }}</small>
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

        <!-- 최근 활동 -->
        <div class="col-xl-4 col-lg-4 col-md-12 col-12">
            <div class="card bg-white">
                <div class="card-header">
                    <h5 class="mb-0">최근 활동</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @foreach($recentActivities->take(8) as $activity)
                        <div class="list-group-item border-0 px-0">
                            <div class="d-flex align-items-start">
                                <div class="avatar avatar-sm me-3">
                                    <span class="avatar-initials rounded-circle bg-{{ $activity['color'] }} text-white">
                                        <i class="{{ $activity['icon'] }}"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 small">{{ $activity['title'] }}</h6>
                                    <p class="mb-1 small text-muted">{{ $activity['description'] }}</p>
                                    <small class="text-muted">{{ $activity['time']->diffForHumans() }}</small>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// 파트너 등록 추이 차트
const ctx1 = document.getElementById('partnerGrowthChart').getContext('2d');
const partnerGrowthChart = new Chart(ctx1, {
    type: 'line',
    data: {
        labels: @json($chartData['labels']),
        datasets: [{
            label: '파트너 등록 수',
            data: @json($chartData['data']),
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.1,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// 등급별 분포 차트
const ctx2 = document.getElementById('tierDistributionChart').getContext('2d');
const tierDistributionChart = new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: @json($tierDistribution->keys()),
        datasets: [{
            data: @json($tierDistribution->values()),
            backgroundColor: [
                '#FF6384',
                '#36A2EB',
                '#FFCE56',
                '#4BC0C0',
                '#9966FF'
            ]
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

function refreshData() {
    location.reload();
}
</script>
@endsection