@extends('jiny-partner::layouts.home')

@section('title', '매출 통계')

@section('content')
<div class="container-fluid p-6">
    <div class="row">
        <div class="col-lg-12">
            <div class="border-bottom pb-3 mb-3 d-flex align-items-center justify-content-between">
                <div>
                    <h1 class="mb-1 h2 fw-bold">매출 통계</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('home.partner.index') }}">파트너 홈</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('home.partner.sales.index') }}">매출 관리</a></li>
                            <li class="breadcrumb-item active">통계</li>
                        </ol>
                    </nav>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('home.partner.sales.history') }}" class="btn btn-outline-secondary">
                        <i class="fe fe-list me-2"></i>매출 이력
                    </a>
                    <a href="{{ route('home.partner.sales.create') }}" class="btn btn-primary">
                        <i class="fe fe-plus me-2"></i>매출 등록
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Period Filter -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">기간</label>
                            <select name="period" class="form-select" onchange="this.form.submit()">
                                <option value="today" {{ $period === 'today' ? 'selected' : '' }}>오늘</option>
                                <option value="this_week" {{ $period === 'this_week' ? 'selected' : '' }}>이번 주</option>
                                <option value="this_month" {{ $period === 'this_month' ? 'selected' : '' }}>이번 달</option>
                                <option value="this_year" {{ $period === 'this_year' ? 'selected' : '' }}>올해</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Period Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="mb-0 text-primary">{{ number_format($periodStats['this_month']['count']) }}</h4>
                    <p class="mb-0">이번 달 건수</p>
                    <small class="text-muted">{{ number_format($periodStats['this_month']['amount']) }}원</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="mb-0 text-success">{{ number_format($periodStats['this_month']['confirmed']) }}</h4>
                    <p class="mb-0">확정 건수</p>
                    <small class="text-muted">{{ number_format($periodStats['this_month']['confirmed_amount']) }}원</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="mb-0 text-warning">{{ number_format($periodStats['this_month']['pending']) }}</h4>
                    <p class="mb-0">대기 건수</p>
                    <small class="text-muted">{{ number_format($periodStats['this_month']['pending_amount']) }}원</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="mb-0 text-info">{{ round($performanceMetrics['avg_sale_amount']) }}</h4>
                    <p class="mb-0">평균 금액</p>
                    <small class="text-muted">전환율: {{ $performanceMetrics['conversion_rate'] }}%</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Growth Rate & Performance -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">성장률</h4>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1 text-{{ $growthDetails['is_positive'] ? 'success' : 'danger' }}">
                                {{ $growthDetails['is_positive'] ? '+' : '' }}{{ $growthDetails['rate'] }}%
                            </h3>
                            <p class="mb-0 text-muted">
                                @if($period === 'this_month')
                                    전월 대비
                                @elseif($period === 'this_week')
                                    전주 대비
                                @else
                                    이전 기간 대비
                                @endif
                            </p>
                        </div>
                        <div class="text-end">
                            <div class="small text-muted">현재: {{ number_format($growthDetails['current_amount']) }}원</div>
                            <div class="small text-muted">이전: {{ number_format($growthDetails['previous_amount']) }}원</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">기간별 비교</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <small class="text-muted">오늘</small>
                            <h6>{{ number_format($periodStats['today']['count']) }}건</h6>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">이번 주</small>
                            <h6>{{ number_format($periodStats['this_week']['count']) }}건</h6>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">이번 달</small>
                            <h6>{{ number_format($periodStats['this_month']['count']) }}건</h6>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">올해</small>
                            <h6>{{ number_format($periodStats['this_year']['count']) }}건</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Additional Stats -->
    <div class="row mb-4">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">월별 매출 트렌드 (최근 12개월)</h4>
                </div>
                <div class="card-body">
                    <div id="monthlyChart" style="height: 400px;"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">상태별 통계</h4>
                </div>
                <div class="card-body">
                    @forelse($statusStats as $status)
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            @if($status->status === 'confirmed')
                                <span class="badge bg-success">확정</span>
                            @elseif($status->status === 'pending')
                                <span class="badge bg-warning">대기</span>
                            @elseif($status->status === 'cancelled')
                                <span class="badge bg-danger">취소</span>
                            @elseif($status->status === 'rejected')
                                <span class="badge bg-secondary">반려</span>
                            @else
                                <span class="badge bg-light text-dark">{{ $status->status }}</span>
                            @endif
                        </div>
                        <div class="text-end">
                            <div class="fw-bold">{{ number_format($status->count) }}건</div>
                            <small class="text-muted">{{ number_format($status->total_amount) }}원</small>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted text-center">매출 데이터가 없습니다.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Category and Recent Sales -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">카테고리별 매출</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>카테고리</th>
                                    <th>건수</th>
                                    <th>총 금액</th>
                                    <th>평균</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($categoryStats as $category)
                                <tr>
                                    <td>{{ $category->category_name }}</td>
                                    <td>{{ number_format($category->count) }}</td>
                                    <td>{{ number_format($category->total_amount) }}원</td>
                                    <td>{{ number_format($category->avg_amount) }}원</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">카테고리 데이터가 없습니다.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">최근 매출 활동</h4>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @forelse($recentSales as $sale)
                        <div class="list-group-item px-0">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">{{ $sale->title ?? '매출' }}</h6>
                                    <p class="mb-1 small text-muted">
                                        {{ $sale->created_at->format('m/d H:i') }}
                                        @if($sale->partner && $sale->partner->name)
                                            - {{ $sale->partner->name }}
                                        @endif
                                    </p>
                                </div>
                                <div class="text-end">
                                    <span class="fw-bold">{{ number_format($sale->amount) }}원</span>
                                    @if($sale->status === 'confirmed')
                                        <br><span class="badge bg-success small">확정</span>
                                    @elseif($sale->status === 'pending')
                                        <br><span class="badge bg-warning small">대기</span>
                                    @elseif($sale->status === 'cancelled')
                                        <br><span class="badge bg-danger small">취소</span>
                                    @elseif($sale->status === 'rejected')
                                        <br><span class="badge bg-secondary small">반려</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="text-center text-muted py-3">
                            최근 매출 활동이 없습니다.
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Best Month Performance -->
    @if($performanceMetrics['best_month'])
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">최고 실적 정보</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <h5 class="alert-heading">최고 실적 월: {{ $performanceMetrics['best_month']->month }}</h5>
                        <p class="mb-0">총 매출: {{ number_format($performanceMetrics['best_month']->total) }}원</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 데이터 준비
    const monthlyData = @json($monthlyStats);
    console.log('Monthly data:', monthlyData);

    // 차트 데이터 추출
    const categories = monthlyData.map(item => item.month_name);
    const countData = monthlyData.map(item => parseInt(item.count) || 0);
    const amountData = monthlyData.map(item => parseInt(item.amount) || 0);

    console.log('Categories:', categories);
    console.log('Count data:', countData);
    console.log('Amount data:', amountData);

    // 차트 옵션 - 더 간단한 설정
    const options = {
        series: [{
            name: '매출 건수',
            type: 'column',
            data: countData
        }, {
            name: '매출 금액 (만원)',
            type: 'line',
            data: amountData.map(amount => Math.round(amount / 10000))
        }],
        chart: {
            height: 400,
            type: 'line',
            stacked: false
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            width: [1, 4]
        },
        xaxis: {
            categories: categories
        },
        yaxis: [
            {
                axisTicks: {
                    show: true,
                },
                axisBorder: {
                    show: true,
                    color: '#008FFB'
                },
                labels: {
                    style: {
                        colors: '#008FFB',
                    }
                },
                title: {
                    text: "매출 건수",
                    style: {
                        color: '#008FFB',
                    }
                },
                tooltip: {
                    enabled: true
                }
            },
            {
                seriesName: '매출 금액 (만원)',
                opposite: true,
                axisTicks: {
                    show: true,
                },
                axisBorder: {
                    show: true,
                    color: '#00E396'
                },
                labels: {
                    style: {
                        colors: '#00E396',
                    }
                },
                title: {
                    text: "매출 금액 (만원)",
                    style: {
                        color: '#00E396',
                    }
                },
            }
        ],
        tooltip: {
            fixed: {
                enabled: true,
                position: 'topLeft',
                offsetY: 30,
                offsetX: 60
            },
        },
        legend: {
            horizontalAlign: 'left',
            offsetX: 40
        }
    };

    // 차트 요소 확인 및 렌더링
    const chartElement = document.querySelector("#monthlyChart");
    console.log('Chart element:', chartElement);

    if (chartElement) {
        try {
            console.log('Creating chart with options:', options);
            const chart = new ApexCharts(chartElement, options);
            chart.render();
            console.log('Chart rendered successfully');
        } catch (error) {
            console.error('Chart creation failed:', error);
            // 오류 시 간단한 메시지 표시
            chartElement.innerHTML = '<div class="alert alert-warning text-center">차트를 로드하는 중 오류가 발생했습니다.</div>';
        }
    } else {
        console.error('Chart container not found!');
    }
});
</script>
@endpush
