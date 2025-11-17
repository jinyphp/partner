@extends('jiny-partner::layouts.admin.sidebar')

@section('content')
{{-- ============================================================= --}}
{{-- 📈 파트너 타입별 고급 분석 대시보드 --}}
{{-- ============================================================= --}}

<div class="container-fluid">
    {{-- 페이지 헤더 --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-chart-line text-info"></i>
                {{ $pageTitle }}
            </h1>
            <nav aria-label="breadcrumb" class="mt-2">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.partner.index') }}">파트너 관리</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.partner.type.index') }}">파트너 타입</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.partner.type.target') }}">실적 관리</a></li>
                    <li class="breadcrumb-item active">고급 분석</li>
                </ol>
            </nav>
        </div>

        <div class="btn-group">
            <a href="{{ route('admin.partner.type.target') }}" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> 기본 대시보드
            </a>
        </div>
    </div>

    {{-- 분석 기간 설정 --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-2">
                            <label for="period" class="form-label">분석 기간</label>
                            <select name="period" id="period" class="form-select">
                                <option value="year" {{ $period == 'year' ? 'selected' : '' }}>연간</option>
                                <option value="quarter" {{ $period == 'quarter' ? 'selected' : '' }}>분기별</option>
                                <option value="custom" {{ $period == 'custom' ? 'selected' : '' }}>사용자 정의</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="year" class="form-label">연도</label>
                            <select name="year" id="year" class="form-select">
                                @foreach($availableYears as $yearOption)
                                    <option value="{{ $yearOption }}" {{ $year == $yearOption ? 'selected' : '' }}>
                                        {{ $yearOption }}년
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3" id="custom-dates" style="{{ $period != 'custom' ? 'display:none;' : '' }}">
                            <label for="start_date" class="form-label">시작일</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $startDate }}">
                        </div>
                        <div class="col-md-3" id="custom-dates-end" style="{{ $period != 'custom' ? 'display:none;' : '' }}">
                            <label for="end_date" class="form-label">종료일</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $endDate }}">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-info">
                                <i class="fas fa-chart-bar"></i> 분석
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- 성과 비교 분석 --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-balance-scale"></i>
                        타입별 성과 비교 분석
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>파트너 타입</th>
                                    <th>활성 파트너 수</th>
                                    <th>총 매출</th>
                                    <th>평균 매출</th>
                                    <th>총 건수</th>
                                    <th>파트너당 매출</th>
                                    <th>수수료율</th>
                                    <th>종합 평가</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($analyticsData['performance_comparison'] as $comparison)
                                    @php
                                        $type = $comparison['type'];
                                        $metrics = $comparison['metrics'];

                                        // 종합 평가 점수 계산 (임시)
                                        $score = ($metrics['total_sales'] / 10000000) * 30 +
                                                ($metrics['sales_per_partner'] / 1000000) * 40 +
                                                ($metrics['commission_rate'] < 15 ? 30 : 0);

                                        if ($score >= 80) {
                                            $gradeConfig = ['class' => 'success', 'text' => 'A'];
                                        } elseif ($score >= 60) {
                                            $gradeConfig = ['class' => 'primary', 'text' => 'B'];
                                        } elseif ($score >= 40) {
                                            $gradeConfig = ['class' => 'warning', 'text' => 'C'];
                                        } else {
                                            $gradeConfig = ['class' => 'danger', 'text' => 'D'];
                                        }
                                    @endphp
                                    <tr>
                                        <td>
                                            <span style="color: {{ $type->color }}">
                                                <i class="{{ $type->icon }}"></i>
                                                {{ $type->type_name }}
                                            </span>
                                        </td>
                                        <td>{{ number_format($metrics['active_partners']) }}명</td>
                                        <td>{{ number_format($metrics['total_sales']) }}원</td>
                                        <td>{{ number_format($metrics['avg_sale']) }}원</td>
                                        <td>{{ number_format($metrics['total_cases']) }}건</td>
                                        <td>{{ number_format($metrics['sales_per_partner']) }}원</td>
                                        <td>{{ $metrics['commission_rate'] }}%</td>
                                        <td>
                                            <span class="badge badge-{{ $gradeConfig['class'] }} badge-lg">
                                                {{ $gradeConfig['text'] }}
                                            </span>
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

    {{-- 성장률 분석 --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-trending-up"></i>
                        전년 동기 대비 성장률 분석
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($analyticsData['growth_analysis'] as $growth)
                            @php
                                $type = $growth['type'];
                                $rates = $growth['growth_rates'];
                                $trend = $growth['trend'];

                                $trendConfig = [
                                    'strong_growth' => ['class' => 'success', 'icon' => 'fas fa-rocket', 'text' => '강한 성장'],
                                    'moderate_growth' => ['class' => 'primary', 'icon' => 'fas fa-arrow-up', 'text' => '온건한 성장'],
                                    'stable' => ['class' => 'info', 'icon' => 'fas fa-minus', 'text' => '안정'],
                                    'declining' => ['class' => 'warning', 'icon' => 'fas fa-arrow-down', 'text' => '하락'],
                                    'concerning' => ['class' => 'danger', 'icon' => 'fas fa-exclamation-triangle', 'text' => '우려']
                                ];

                                $config = $trendConfig[$trend] ?? $trendConfig['stable'];
                            @endphp
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card border-{{ $config['class'] }}">
                                    <div class="card-header bg-{{ $config['class'] }} text-white">
                                        <h6 class="mb-0">
                                            <i class="{{ $type->icon }}"></i>
                                            {{ $type->type_name }}
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row text-center">
                                            <div class="col-4">
                                                <div class="mb-2">
                                                    <small class="text-muted">매출 성장률</small>
                                                </div>
                                                <div class="h6 {{ $rates['sales'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ $rates['sales'] > 0 ? '+' : '' }}{{ $rates['sales'] }}%
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="mb-2">
                                                    <small class="text-muted">파트너 증가율</small>
                                                </div>
                                                <div class="h6 {{ $rates['partners'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ $rates['partners'] > 0 ? '+' : '' }}{{ $rates['partners'] }}%
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="mb-2">
                                                    <small class="text-muted">건수 증가율</small>
                                                </div>
                                                <div class="h6 {{ $rates['cases'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ $rates['cases'] > 0 ? '+' : '' }}{{ $rates['cases'] }}%
                                                </div>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="text-center">
                                            <span class="badge badge-{{ $config['class'] }}">
                                                <i class="{{ $config['icon'] }}"></i>
                                                {{ $config['text'] }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 시장 점유율 & ROI 분석 --}}
    <div class="row mb-4">
        <div class="col-lg-6">
            {{-- 시장 점유율 --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie"></i>
                        시장 점유율 분석
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="marketShareChart" style="height: 300px;"></canvas>
                    <div class="mt-3">
                        @foreach($analyticsData['market_share'] as $index => $share)
                            @php $type = $share['type']; @endphp
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <span class="badge" style="background-color: {{ $type->color }};">{{ $share['rank'] }}</span>
                                    {{ $type->type_name }}
                                </div>
                                <div>
                                    <strong>{{ $share['share_percentage'] }}%</strong>
                                    <small class="text-muted">({{ number_format($share['sales']) }}원)</small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            {{-- ROI 분석 --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-calculator"></i>
                        ROI 분석
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>타입</th>
                                    <th>매출</th>
                                    <th>총 비용</th>
                                    <th>순이익</th>
                                    <th>ROI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($analyticsData['roi_analysis'] as $roi)
                                    @php $type = $roi['type']; @endphp
                                    <tr>
                                        <td>
                                            <small style="color: {{ $type->color }}">
                                                <i class="{{ $type->icon }}"></i>
                                                {{ $type->type_name }}
                                            </small>
                                        </td>
                                        <td><small>{{ number_format($roi['revenue'] / 1000000, 1) }}M</small></td>
                                        <td><small>{{ number_format($roi['total_costs'] / 1000000, 1) }}M</small></td>
                                        <td class="{{ $roi['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                            <small>{{ number_format($roi['net_profit'] / 1000000, 1) }}M</small>
                                        </td>
                                        <td class="{{ $roi['roi_percentage'] >= 100 ? 'text-success' : 'text-warning' }}">
                                            <small><strong>{{ $roi['roi_percentage'] }}%</strong></small>
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

    {{-- 계절성 패턴 분석 --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt"></i>
                        {{ $year }}년 계절성 패턴 분석
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="seasonalPatternChart" style="height: 400px;"></canvas>

                    <div class="row mt-4">
                        @foreach($analyticsData['seasonal_patterns'] as $pattern)
                            @php
                                $type = $pattern['type'];
                                $peak = $pattern['peak_month'];
                                $low = $pattern['low_month'];
                            @endphp
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="card border-light">
                                    <div class="card-body text-center">
                                        <h6 style="color: {{ $type->color }}">
                                            <i class="{{ $type->icon }}"></i>
                                            {{ $type->type_name }}
                                        </h6>
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="text-success">
                                                    <small>최고월</small><br>
                                                    <strong>{{ $peak['month'] ?? '-' }}월</strong>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="text-danger">
                                                    <small>최저월</small><br>
                                                    <strong>{{ $low['month'] ?? '-' }}월</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 예측 인사이트 --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-crystal-ball"></i>
                        AI 예측 인사이트
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($analyticsData['predictive_insights'] as $insight)
                            @php $type = $insight['type']; @endphp
                            <div class="col-lg-6 mb-3">
                                <div class="card border-warning">
                                    <div class="card-body">
                                        <h6 style="color: {{ $type->color }}">
                                            <i class="{{ $type->icon }}"></i>
                                            {{ $type->type_name }}
                                        </h6>
                                        <p class="card-text">{{ $insight['insight'] }}</p>
                                        <small class="text-muted">
                                            다음 달 예상 매출: <strong>{{ number_format($insight['next_month_prediction']) }}원</strong>
                                        </small>
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 기간 선택 이벤트
    document.getElementById('period').addEventListener('change', function() {
        const customDates = document.getElementById('custom-dates');
        const customDatesEnd = document.getElementById('custom-dates-end');
        if (this.value === 'custom') {
            customDates.style.display = 'block';
            customDatesEnd.style.display = 'block';
        } else {
            customDates.style.display = 'none';
            customDatesEnd.style.display = 'none';
        }
    });

    // 시장 점유율 차트
    const marketCtx = document.getElementById('marketShareChart').getContext('2d');
    const marketShareData = {!! json_encode($analyticsData['market_share']) !!};

    new Chart(marketCtx, {
        type: 'doughnut',
        data: {
            labels: marketShareData.map(item => item.type.type_name),
            datasets: [{
                data: marketShareData.map(item => item.share_percentage),
                backgroundColor: marketShareData.map(item => item.type.color),
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.parsed + '%';
                        }
                    }
                }
            }
        }
    });

    // 계절성 패턴 차트
    const seasonalCtx = document.getElementById('seasonalPatternChart').getContext('2d');
    const seasonalData = {!! json_encode($analyticsData['seasonal_patterns']) !!};

    const seasonalDatasets = seasonalData.map(pattern => ({
        label: pattern.type.type_name,
        data: pattern.monthly_data.map(data => data.seasonal_index),
        borderColor: pattern.type.color,
        backgroundColor: pattern.type.color + '20',
        tension: 0.1
    }));

    new Chart(seasonalCtx, {
        type: 'line',
        data: {
            labels: ['1월', '2월', '3월', '4월', '5월', '6월', '7월', '8월', '9월', '10월', '11월', '12월'],
            datasets: seasonalDatasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: false,
                    title: {
                        display: true,
                        text: '계절성 지수 (%)'
                    },
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y + '%';
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush

@endsection
