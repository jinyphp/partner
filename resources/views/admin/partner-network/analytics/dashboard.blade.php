@extends('jiny-partner::layouts.admin.sidebar')

@section('content')
<div class="container-fluid px-6 py-4">
    <!-- Page Header -->
    <div class="row">
        <div class="col-lg-12 col-md-12 col-12">
            <div class="border-bottom pb-3 mb-3">
                <div class="mb-2 mb-lg-0">
                    <h1 class="mb-0 h2 fw-bold">{{ $pageTitle }}</h1>
                    <p class="mb-0 text-muted">네트워크 전체의 성과와 동향을 한눈에 확인하는 종합 대시보드입니다.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="row mb-4">
        <div class="col-xl-3 col-lg-6 col-md-12 col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h3 class="mb-0 text-primary">{{ number_format(\Jiny\Partner\Models\PartnerUser::count()) }}</h3>
                            <p class="mb-0 text-muted">총 파트너</p>
                            <small class="text-success">
                                <i class="fe fe-trending-up"></i>
                                +{{ number_format(\Jiny\Partner\Models\PartnerUser::whereDate('created_at', '>=', now()->subDays(30))->count()) }} (30일)
                            </small>
                        </div>
                        <div class="icon-shape icon-lg bg-primary text-white rounded-3">
                            <i class="fe fe-users"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-12 col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h3 class="mb-0 text-success">{{ number_format(\Jiny\Partner\Models\PartnerUser::where('status', 'active')->sum('monthly_sales')) }}원</h3>
                            <p class="mb-0 text-muted">월간 총 매출</p>
                            <small class="text-info">
                                <i class="fe fe-calendar"></i>
                                이번 달
                            </small>
                        </div>
                        <div class="icon-shape icon-lg bg-success text-white rounded-3">
                            <i class="fe fe-dollar-sign"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-12 col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h3 class="mb-0 text-warning">{{ number_format(\Jiny\Partner\Models\PartnerCommission::where('status', 'paid')->sum('commission_amount')) }}원</h3>
                            <p class="mb-0 text-muted">지급된 커미션</p>
                            <small class="text-success">
                                <i class="fe fe-check"></i>
                                {{ number_format(\Jiny\Partner\Models\PartnerCommission::where('status', 'paid')->count()) }}건
                            </small>
                        </div>
                        <div class="icon-shape icon-lg bg-warning text-white rounded-3">
                            <i class="fe fe-award"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-12 col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h3 class="mb-0 text-info">{{ number_format(\Jiny\Partner\Models\PartnerNetworkRelationship::active()->count()) }}</h3>
                            <p class="mb-0 text-muted">활성 네트워크 관계</p>
                            <small class="text-primary">
                                <i class="fe fe-link"></i>
                                연결된 관계
                            </small>
                        </div>
                        <div class="icon-shape icon-lg bg-info text-white rounded-3">
                            <i class="fe fe-git-branch"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Network Growth Chart -->
        <div class="col-lg-8 col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">네트워크 성장 추이</h5>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary active" onclick="changeChartPeriod('7days')">7일</button>
                        <button type="button" class="btn btn-outline-primary" onclick="changeChartPeriod('30days')">30일</button>
                        <button type="button" class="btn btn-outline-primary" onclick="changeChartPeriod('90days')">90일</button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="networkGrowthChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Tier Distribution -->
        <div class="col-lg-4 col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">티어별 분포</h5>
                </div>
                <div class="card-body">
                    <canvas id="tierDistributionChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Top Performers -->
        <div class="col-lg-6 col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">최고 성과자</h5>
                </div>
                <div class="card-body">
                    @php
                        $topPerformers = \Jiny\Partner\Models\PartnerUser::with('partnerTier')
                            ->where('status', 'active')
                            ->orderBy('monthly_sales', 'desc')
                            ->limit(5)
                            ->get();
                    @endphp
                    @foreach($topPerformers as $index => $performer)
                        <div class="d-flex align-items-center mb-3 {{ $index < 3 ? 'border-bottom pb-3' : '' }}">
                            <div class="me-3">
                                <span class="badge bg-{{ $index === 0 ? 'warning' : ($index === 1 ? 'secondary' : ($index === 2 ? 'info' : 'light')) }} rounded-circle">
                                    {{ $index + 1 }}
                                </span>
                            </div>
                            <div class="avatar avatar-md me-3">
                                <span class="avatar-initials rounded-circle bg-primary">
                                    {{ strtoupper(substr($performer->name, 0, 1)) }}
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0">{{ $performer->name }}</h6>
                                <small class="text-muted">{{ $performer->partnerTier->tier_name ?? 'N/A' }}</small>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-success">{{ number_format($performer->monthly_sales) }}원</div>
                                <small class="text-muted">월 매출</small>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="col-lg-6 col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">최근 활동</h5>
                </div>
                <div class="card-body">
                    @php
                        $recentActivities = collect([
                            [
                                'type' => 'recruitment',
                                'icon' => 'fe-user-plus',
                                'color' => 'success',
                                'title' => '새 파트너 가입',
                                'description' => '김철수님이 네트워크에 참여했습니다.',
                                'time' => '2시간 전'
                            ],
                            [
                                'type' => 'commission',
                                'icon' => 'fe-dollar-sign',
                                'color' => 'primary',
                                'title' => '커미션 지급',
                                'description' => '이영희님에게 150,000원이 지급되었습니다.',
                                'time' => '4시간 전'
                            ],
                            [
                                'type' => 'tier_upgrade',
                                'icon' => 'fe-arrow-up',
                                'color' => 'warning',
                                'title' => '티어 승급',
                                'description' => '박민수님이 골드 티어로 승급했습니다.',
                                'time' => '1일 전'
                            ],
                            [
                                'type' => 'sale',
                                'icon' => 'fe-shopping-cart',
                                'color' => 'info',
                                'title' => '대규모 매출',
                                'description' => '정상훈님이 5,000,000원의 매출을 달성했습니다.',
                                'time' => '2일 전'
                            ]
                        ]);
                    @endphp
                    @foreach($recentActivities as $activity)
                        <div class="d-flex align-items-start mb-3">
                            <div class="icon-shape icon-sm bg-{{ $activity['color'] }} text-white rounded-circle me-3">
                                <i class="fe {{ $activity['icon'] }}"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">{{ $activity['title'] }}</h6>
                                <p class="mb-1 text-muted">{{ $activity['description'] }}</p>
                                <small class="text-muted">{{ $activity['time'] }}</small>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Commission Overview -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">커미션 현황</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @php
                            $commissionStats = [
                                ['label' => '직접 판매', 'type' => 'direct_sales', 'color' => 'primary'],
                                ['label' => '팀 보너스', 'type' => 'team_bonus', 'color' => 'success'],
                                ['label' => '관리 보너스', 'type' => 'management_bonus', 'color' => 'info'],
                                ['label' => '오버라이드', 'type' => 'override_bonus', 'color' => 'warning'],
                                ['label' => '모집 보너스', 'type' => 'recruitment_bonus', 'color' => 'secondary'],
                                ['label' => '등급 보너스', 'type' => 'rank_bonus', 'color' => 'dark']
                            ];
                        @endphp
                        @foreach($commissionStats as $stat)
                            @php
                                $amount = \Jiny\Partner\Models\PartnerCommission::where('commission_type', $stat['type'])
                                    ->where('status', 'paid')
                                    ->sum('commission_amount');
                                $count = \Jiny\Partner\Models\PartnerCommission::where('commission_type', $stat['type'])
                                    ->where('status', 'paid')
                                    ->count();
                            @endphp
                            <div class="col-md-2 text-center mb-3">
                                <div class="card border-0">
                                    <div class="card-body">
                                        <div class="icon-shape icon-md bg-{{ $stat['color'] }} text-white rounded-3 mx-auto mb-2">
                                            <i class="fe fe-dollar-sign"></i>
                                        </div>
                                        <h6 class="mb-1">{{ $stat['label'] }}</h6>
                                        <div class="text-{{ $stat['color'] }} fw-bold">{{ number_format($amount) }}원</div>
                                        <small class="text-muted">{{ $count }}건</small>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Network Growth Chart
const growthCtx = document.getElementById('networkGrowthChart').getContext('2d');
const networkGrowthChart = new Chart(growthCtx, {
    type: 'line',
    data: {
        labels: ['1주전', '6일전', '5일전', '4일전', '3일전', '2일전', '1일전', '오늘'],
        datasets: [{
            label: '신규 파트너',
            data: [5, 8, 12, 7, 15, 10, 18, 22],
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            tension: 0.1
        }, {
            label: '활성 파트너',
            data: [120, 125, 130, 135, 142, 148, 155, 162],
            borderColor: 'rgb(255, 99, 132)',
            backgroundColor: 'rgba(255, 99, 132, 0.1)',
            tension: 0.1
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

// Tier Distribution Chart
const tierCtx = document.getElementById('tierDistributionChart').getContext('2d');
const tierDistributionChart = new Chart(tierCtx, {
    type: 'doughnut',
    data: {
        labels: ['브론즈', '실버', '골드', '플래티넘', '다이아몬드'],
        datasets: [{
            data: [45, 35, 25, 15, 5],
            backgroundColor: [
                '#CD7F32',
                '#C0C0C0',
                '#FFD700',
                '#E5E4E2',
                '#B9F2FF'
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

function changeChartPeriod(period) {
    // Update chart data based on period
    const buttons = document.querySelectorAll('.btn-group .btn');
    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');

    // Here you would typically make an AJAX call to get new data
    // For demo purposes, we'll just update with static data
    if (period === '7days') {
        networkGrowthChart.data.labels = ['1주전', '6일전', '5일전', '4일전', '3일전', '2일전', '1일전', '오늘'];
        networkGrowthChart.data.datasets[0].data = [5, 8, 12, 7, 15, 10, 18, 22];
    } else if (period === '30days') {
        networkGrowthChart.data.labels = ['4주전', '3주전', '2주전', '1주전', '오늘'];
        networkGrowthChart.data.datasets[0].data = [45, 52, 68, 75, 85];
    } else if (period === '90days') {
        networkGrowthChart.data.labels = ['3개월전', '2개월전', '1개월전', '오늘'];
        networkGrowthChart.data.datasets[0].data = [120, 180, 220, 280];
    }

    networkGrowthChart.update();
}
</script>
@endsection