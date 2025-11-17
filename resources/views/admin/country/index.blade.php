@extends('jiny-partner::layouts.admin.sidebar')

@section('title', $pageTitle ?? '국가별 파트너 현황 분석')

@section('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
        }

        .stats-card {
            background: #fff;
            border: 1px solid #e3e6f0;
            border-left: 4px solid #5a67d8;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            height: 100%;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }

        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.25rem 2rem 0 rgba(58, 59, 69, 0.15);
        }

        .stats-card.primary { border-left-color: #4e73df; }
        .stats-card.success { border-left-color: #1cc88a; }
        .stats-card.info { border-left-color: #36b9cc; }
        .stats-card.warning { border-left-color: #f6c23e; }
        .stats-card.secondary { border-left-color: #858796; }
        .stats-card.danger { border-left-color: #e74a3b; }

        .stats-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
        }

        .stats-icon.primary { background: linear-gradient(45deg, #4e73df, #6f42c1); }
        .stats-icon.success { background: linear-gradient(45deg, #1cc88a, #13b76d); }
        .stats-icon.info { background: linear-gradient(45deg, #36b9cc, #2c9faf); }
        .stats-icon.warning { background: linear-gradient(45deg, #f6c23e, #dda20a); }
        .stats-icon.secondary { background: linear-gradient(45deg, #858796, #6c757d); }
        .stats-icon.danger { background: linear-gradient(45deg, #e74a3b, #dc3545); }

        .stats-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2e3440;
            margin-bottom: 0.25rem;
        }

        .stats-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #858796;
            text-transform: uppercase;
            letter-spacing: 0.05rem;
        }

        .stats-body {
            padding: 1.25rem;
        }

        .country-flag {
            width: 24px;
            height: 16px;
            margin-right: 0.5rem;
            border-radius: 2px;
        }

        .performance-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 0.5rem;
        }

        .performance-excellent { background-color: #28a745; }
        .performance-good { background-color: #17a2b8; }
        .performance-average { background-color: #ffc107; }
        .performance-poor { background-color: #dc3545; }

        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.1);
        }
    </style>
@endsection

@section('content')
<div class="container-fluid">

    @if(isset($error))
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ $error }}
        </div>
    @else

    <!-- 헤더 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-1">{{ $pageTitle ?? '국가별 파트너 현황 분석' }}</h1>
                    <p class="text-muted">파트너 등록 현황과 매출 성과를 국가별로 분석합니다</p>
                </div>
                <div>
                    <button class="btn btn-primary me-2" onclick="exportData()">
                        <i class="bi bi-download me-1"></i>데이터 내보내기
                    </button>
                    <button class="btn btn-outline-secondary" onclick="refreshData()">
                        <i class="bi bi-arrow-clockwise me-1"></i>새로고침
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 필터 영역 -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ url()->current() }}" class="row align-items-end">
                <div class="col-md-3">
                    <label class="form-label">시작일</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $filters['start_date'] }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">종료일</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $filters['end_date'] }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">기간 단위</label>
                    <select name="period" class="form-select">
                        <option value="month" {{ $filters['period'] === 'month' ? 'selected' : '' }}>월별</option>
                        <option value="quarter" {{ $filters['period'] === 'quarter' ? 'selected' : '' }}>분기별</option>
                        <option value="year" {{ $filters['period'] === 'year' ? 'selected' : '' }}>연도별</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i>조회
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 요약 통계 카드 -->
    <div class="row mb-4">
        <div class="col-xl-2 col-md-6 mb-3">
            <div class="stats-card primary">
                <div class="stats-body">
                    <div class="stats-icon primary">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <div class="stats-value">{{ number_format($performanceMetrics['total_applications']) }}</div>
                    <div class="stats-label">총 신청서</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-6 mb-3">
            <div class="stats-card success">
                <div class="stats-body">
                    <div class="stats-icon success">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="stats-value">{{ number_format($performanceMetrics['total_approved']) }}</div>
                    <div class="stats-label">승인됨</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-6 mb-3">
            <div class="stats-card info">
                <div class="stats-body">
                    <div class="stats-icon info">
                        <i class="bi bi-percent"></i>
                    </div>
                    <div class="stats-value">{{ $performanceMetrics['approval_rate'] }}%</div>
                    <div class="stats-label">승인율</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-6 mb-3">
            <div class="stats-card warning">
                <div class="stats-body">
                    <div class="stats-icon warning">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div class="stats-value">₩{{ number_format($performanceMetrics['total_sales']) }}</div>
                    <div class="stats-label">총 매출</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-6 mb-3">
            <div class="stats-card secondary">
                <div class="stats-body">
                    <div class="stats-icon secondary">
                        <i class="bi bi-globe"></i>
                    </div>
                    <div class="stats-value">{{ $performanceMetrics['active_countries'] }}</div>
                    <div class="stats-label">활성 국가</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-6 mb-3">
            <div class="stats-card danger">
                <div class="stats-body">
                    <div class="stats-icon danger">
                        <i class="bi bi-bar-chart"></i>
                    </div>
                    <div class="stats-value">₩{{ number_format($performanceMetrics['avg_sales_per_country']) }}</div>
                    <div class="stats-label">국가당 평균</div>
                </div>
            </div>
        </div>
    </div>

    <!-- 차트 영역 -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-bar-chart me-2"></i>국가별 파트너 등록 현황
                    </h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="partnersChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-graph-up me-2"></i>국가별 매출 현황
                    </h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top 10 국가 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-trophy me-2"></i>Top 10 국가 (매출액 기준)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>순위</th>
                                    <th>국가</th>
                                    <th>파트너 수</th>
                                    <th>거래 건수</th>
                                    <th>총 매출</th>
                                    <th>파트너당 평균</th>
                                    <th>성과</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topCountries as $index => $country)
                                <tr>
                                    <td>
                                        <span class="badge bg-primary">{{ $index + 1 }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="country-flag me-2">🏳️</span>
                                            <strong>{{ $country->country_name }}</strong>
                                            <small class="text-muted ms-2">({{ $country->country }})</small>
                                        </div>
                                    </td>
                                    <td>{{ number_format($country->partner_count) }}</td>
                                    <td>{{ number_format($country->sales_count) }}</td>
                                    <td>
                                        <strong>₩{{ number_format($country->total_sales) }}</strong>
                                    </td>
                                    <td>
                                        ₩{{ number_format($country->partner_count > 0 ? $country->total_sales / $country->partner_count : 0) }}
                                    </td>
                                    <td>
                                        @php
                                            $avgSales = $country->partner_count > 0 ? $country->total_sales / $country->partner_count : 0;
                                            $performanceClass = 'performance-poor';
                                            if ($avgSales >= 10000000) $performanceClass = 'performance-excellent';
                                            elseif ($avgSales >= 5000000) $performanceClass = 'performance-good';
                                            elseif ($avgSales >= 1000000) $performanceClass = 'performance-average';
                                        @endphp
                                        <span class="performance-indicator {{ $performanceClass }}"></span>
                                        @if($performanceClass === 'performance-excellent') 우수
                                        @elseif($performanceClass === 'performance-good') 양호
                                        @elseif($performanceClass === 'performance-average') 보통
                                        @else 개선필요
                                        @endif
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

    <!-- 상세 통계 테이블 -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-people me-2"></i>파트너 등록 상세 현황
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>국가</th>
                                    <th>총 신청</th>
                                    <th>승인</th>
                                    <th>거부</th>
                                    <th>승인율</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($partnerStats as $stat)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="country-flag me-2">🏳️</span>
                                            {{ $stat->country_name }}
                                        </div>
                                    </td>
                                    <td>{{ number_format($stat->total_applications) }}</td>
                                    <td><span class="text-success">{{ number_format($stat->approved_count) }}</span></td>
                                    <td><span class="text-danger">{{ number_format($stat->rejected_count) }}</span></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                                <div class="progress-bar" role="progressbar"
                                                     style="width: {{ $stat->approval_rate }}%"></div>
                                            </div>
                                            <small>{{ $stat->approval_rate }}%</small>
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

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-currency-dollar me-2"></i>매출 상세 현황
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>국가</th>
                                    <th>파트너 수</th>
                                    <th>확정 매출</th>
                                    <th>평균 거래액</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($salesStats as $stat)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="country-flag me-2">🏳️</span>
                                            {{ $stat->country_name }}
                                        </div>
                                    </td>
                                    <td>{{ number_format($stat->partner_count) }}</td>
                                    <td>
                                        <strong>₩{{ number_format($stat->confirmed_sales) }}</strong>
                                        @if($stat->pending_sales > 0)
                                            <br><small class="text-warning">대기: ₩{{ number_format($stat->pending_sales) }}</small>
                                        @endif
                                    </td>
                                    <td>₩{{ number_format($stat->avg_sale_amount ?? 0) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// 차트 데이터 준비
const partnerData = @json($partnerStats ?? []);
const salesData = @json($salesStats ?? []);

// 파트너 등록 현황 차트
const partnersCtx = document.getElementById('partnersChart').getContext('2d');
new Chart(partnersCtx, {
    type: 'doughnut',
    data: {
        labels: partnerData.map(item => item.country_name),
        datasets: [{
            label: '총 신청서',
            data: partnerData.map(item => item.total_applications),
            backgroundColor: [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
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

// 매출 현황 차트
const salesCtx = document.getElementById('salesChart').getContext('2d');
new Chart(salesCtx, {
    type: 'bar',
    data: {
        labels: salesData.map(item => item.country_name),
        datasets: [{
            label: '확정 매출',
            data: salesData.map(item => item.confirmed_sales),
            backgroundColor: 'rgba(54, 162, 235, 0.8)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₩' + value.toLocaleString();
                    }
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

// 데이터 내보내기
function exportData() {
    window.open('{{ url()->current() }}?export=excel&' + new URLSearchParams(window.location.search));
}

// 데이터 새로고침
function refreshData() {
    window.location.reload();
}

// 자동 새로고침 (5분마다)
setInterval(refreshData, 300000);
</script>
@endpush