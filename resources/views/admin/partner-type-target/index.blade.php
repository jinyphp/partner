@extends('jiny-partner::layouts.admin.sidebar')

@section('content')
{{-- ============================================================= --}}
{{-- 📊 파트너 타입별 실적 관리 대시보드 --}}
{{-- ============================================================= --}}

<div class="container-fluid">
    {{-- 페이지 헤더 --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-chart-bar text-primary"></i>
                {{ $pageTitle }}
            </h1>
            <nav aria-label="breadcrumb" class="mt-2">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.partner.index') }}">파트너 관리</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.partner.type.index') }}">파트너 타입</a></li>
                    <li class="breadcrumb-item active">실적 관리</li>
                </ol>
            </nav>
        </div>

        <div class="btn-group">
            <a href="{{ route('admin.partner.type.target.analytics') }}" class="btn btn-info">
                <i class="fas fa-chart-line"></i> 고급 분석
            </a>
        </div>
    </div>

    {{-- 기간 선택 필터 --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="year" class="form-label">연도</label>
                            <select name="year" id="year" class="form-select">
                                @foreach($availableYears as $year)
                                    <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>
                                        {{ $year }}년
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="month" class="form-label">월</label>
                            <select name="month" id="month" class="form-select">
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ $selectedMonth == $i ? 'selected' : '' }}>
                                        {{ $i }}월
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> 조회
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- 타입별 실적 요약 카드 --}}
    <div class="row mb-4">
        @foreach($typePerformanceData as $typeId => $data)
            @php
                $type = $data['type'];
                $monthly = $data['monthly'];
                $yearly = $data['yearly'];
            @endphp
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-uppercase mb-1"
                                     style="color: {{ $type->color }}">
                                    <i class="{{ $type->icon }}"></i>
                                    {{ $type->type_name }}
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ number_format($monthly['total_sales']) }}원
                                </div>
                                <div class="text-xs text-gray-600">
                                    {{ $monthly['active_partners'] }}/{{ $monthly['total_partners'] }}명 활동
                                    ({{ $monthly['participation_rate'] }}%)
                                </div>
                                <div class="row mt-2">
                                    <div class="col-6">
                                        <small class="text-muted">건수</small><br>
                                        <span class="font-weight-bold">{{ number_format($monthly['total_cases']) }}</span>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">평균 단가</small><br>
                                        <span class="font-weight-bold">{{ number_format($monthly['avg_sale_amount']) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('admin.partner.type.target.detail', $typeId) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    상세보기
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- 목표 달성률 섹션 --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-bullseye"></i>
                        {{ $selectedMonth }}월 목표 달성률
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($achievementRates as $typeId => $achievement)
                            @php
                                $type = $achievement['type'];
                                $rate = $achievement['achievement_rate'];
                                $status = $achievement['status'];

                                $statusConfig = [
                                    'excellent' => ['color' => 'success', 'icon' => 'fas fa-trophy', 'text' => '우수'],
                                    'success' => ['color' => 'success', 'icon' => 'fas fa-check-circle', 'text' => '달성'],
                                    'warning' => ['color' => 'warning', 'icon' => 'fas fa-exclamation-triangle', 'text' => '주의'],
                                    'danger' => ['color' => 'danger', 'icon' => 'fas fa-times-circle', 'text' => '미달']
                                ];

                                $config = $statusConfig[$status] ?? $statusConfig['danger'];
                            @endphp
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="card border-{{ $config['color'] }}">
                                    <div class="card-body text-center">
                                        <h6 class="card-title" style="color: {{ $type->color }}">
                                            <i class="{{ $type->icon }}"></i>
                                            {{ $type->type_name }}
                                        </h6>
                                        <div class="progress mb-2">
                                            <div class="progress-bar bg-{{ $config['color'] }}"
                                                 role="progressbar"
                                                 style="width: {{ min(100, $rate) }}%"
                                                 aria-valuenow="{{ $rate }}"
                                                 aria-valuemin="0"
                                                 aria-valuemax="100">
                                            </div>
                                        </div>
                                        <p class="card-text">
                                            <span class="h5 font-weight-bold text-{{ $config['color'] }}">{{ $rate }}%</span><br>
                                            <small class="text-muted">
                                                {{ number_format($achievement['current_performance']) }} /
                                                {{ number_format($achievement['target']) }}원
                                            </small><br>
                                            <span class="badge badge-{{ $config['color'] }}">
                                                <i class="{{ $config['icon'] }}"></i>
                                                {{ $config['text'] }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 월별 추이 차트 --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line"></i>
                        {{ $selectedYear }}년 월별 매출 추이
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="monthlyTrendChart" style="height: 400px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- 수수료 효율성 분석 --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-percent"></i>
                        수수료 효율성 분석
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>타입</th>
                                    <th>실제 수수료율</th>
                                    <th>건당 평균 매출</th>
                                    <th>건당 평균 수수료</th>
                                    <th>ROI</th>
                                    <th>효율성 등급</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($commissionEfficiency as $typeId => $efficiency)
                                    @php
                                        $type = $efficiency['type'];
                                        $roi = $efficiency['roi'];

                                        if ($roi >= 10) {
                                            $efficiencyGrade = ['class' => 'success', 'text' => '매우 우수'];
                                        } elseif ($roi >= 7) {
                                            $efficiencyGrade = ['class' => 'primary', 'text' => '우수'];
                                        } elseif ($roi >= 5) {
                                            $efficiencyGrade = ['class' => 'warning', 'text' => '보통'];
                                        } else {
                                            $efficiencyGrade = ['class' => 'danger', 'text' => '개선 필요'];
                                        }
                                    @endphp
                                    <tr>
                                        <td>
                                            <span style="color: {{ $type->color }}">
                                                <i class="{{ $type->icon }}"></i>
                                                {{ $type->type_name }}
                                            </span>
                                        </td>
                                        <td>{{ $efficiency['commission_rate'] }}%</td>
                                        <td>{{ number_format($efficiency['revenue_per_case']) }}원</td>
                                        <td>{{ number_format($efficiency['commission_per_case']) }}원</td>
                                        <td>{{ $roi }}</td>
                                        <td>
                                            <span class="badge badge-{{ $efficiencyGrade['class'] }}">
                                                {{ $efficiencyGrade['text'] }}
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

    {{-- TOP 파트너 섹션 --}}
    <div class="row">
        @foreach($topPartnersByType as $typeId => $topData)
            @php
                $type = $topData['type'];
                $partners = $topData['partners'];
            @endphp
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header" style="background-color: {{ $type->color }}10">
                        <h6 class="mb-0" style="color: {{ $type->color }}">
                            <i class="{{ $type->icon }}"></i>
                            {{ $type->type_name }} TOP 3
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($partners->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach($partners as $index => $partner)
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="d-flex align-items-center">
                                                <span class="badge badge-primary rounded-pill me-2">
                                                    {{ $index + 1 }}
                                                </span>
                                                <div>
                                                    <h6 class="mb-1">{{ $partner->name }}</h6>
                                                    <small class="text-muted">{{ $partner->partner_code }}</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="h6 mb-1">{{ number_format($partner->total_sales) }}원</div>
                                            <small class="text-muted">{{ $partner->total_cases }}건</small>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted text-center">해당 월에 활동한 파트너가 없습니다.</p>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

{{-- Chart.js 스크립트 --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 월별 추이 차트
    const ctx = document.getElementById('monthlyTrendChart').getContext('2d');

    const datasets = [
        @foreach($monthlyTrends as $typeId => $trend)
            {
                label: '{{ $trend["type"]->type_name }}',
                data: {!! json_encode(array_column($trend['data'], 'sales')) !!},
                borderColor: '{{ $trend["type"]->color }}',
                backgroundColor: '{{ $trend["type"]->color }}20',
                tension: 0.1,
                fill: false
            },
        @endforeach
    ];

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode(array_column($monthlyTrends[array_key_first($monthlyTrends)]['data'] ?? [], 'month_name')) !!},
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: '월'
                    }
                },
                y: {
                    display: true,
                    title: {
                        display: true,
                        text: '매출 (원)'
                    },
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('ko-KR').format(value) + '원';
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' +
                                   new Intl.NumberFormat('ko-KR').format(context.parsed.y) + '원';
                        }
                    }
                },
                legend: {
                    position: 'top',
                }
            }
        }
    });
});
</script>
@endpush

@endsection
