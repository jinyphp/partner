@extends('jiny-site::layouts.home')

@section('title', $pageTitle ?? '판매 대시보드')

@section('content')
<div class="container-fluid p-6">
    <!-- Page Header -->
    <div class="row">
        <div class="col-lg-12 col-md-12 col-12">
            <div class="border-bottom pb-3 mb-3 d-flex align-items-center justify-content-between">
                <div>
                    <h1 class="mb-1 h2 fw-bold">{{ $pageTitle }}</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('home.partner.index') }}">파트너 홈</a></li>
                            <li class="breadcrumb-item active" aria-current="page">판매 대시보드</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-xl-3 col-lg-6 col-md-12 col-12">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ number_format($salesStats['total_sales']) }}</h4>
                            <p class="mb-0">총 판매 건수</p>
                        </div>
                        <div class="icon-shape icon-md bg-primary text-white rounded-circle">
                            <i class="fe fe-shopping-cart"></i>
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
                            <h4 class="mb-0">{{ number_format($salesStats['total_amount']) }}원</h4>
                            <p class="mb-0">총 판매 금액</p>
                        </div>
                        <div class="icon-shape icon-md bg-success text-white rounded-circle">
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
                            <h4 class="mb-0">{{ number_format($salesStats['monthly_sales']) }}</h4>
                            <p class="mb-0">이번 달 판매</p>
                        </div>
                        <div class="icon-shape icon-md bg-warning text-white rounded-circle">
                            <i class="fe fe-calendar"></i>
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
                            <h4 class="mb-0">{{ $salesStats['success_rate'] }}%</h4>
                            <p class="mb-0">성공률</p>
                        </div>
                        <div class="icon-shape icon-md bg-info text-white rounded-circle">
                            <i class="fe fe-trending-up"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row">
        <div class="col-xl-8 col-lg-12 col-md-12 col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">월별 판매 트렌드</h4>
                </div>
                <div class="card-body">
                    <div id="monthlyTrendChart" style="height: 350px;"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-12 col-md-12 col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">최근 판매</h4>
                </div>
                <div class="card-body">
                    @forelse($recentSales as $sale)
                    <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
                        <div>
                            <h6 class="mb-1">{{ $sale->product_name ?? '상품명' }}</h6>
                            <p class="mb-0 text-muted">{{ $sale->created_at->format('Y-m-d H:i') }}</p>
                        </div>
                        <div class="text-end">
                            <h6 class="mb-0">{{ number_format($sale->amount) }}원</h6>
                            <span class="badge bg-{{ $sale->status === 'confirmed' ? 'success' : 'warning' }}">
                                {{ $sale->status === 'confirmed' ? '확정' : '대기' }}
                            </span>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-4">
                        <p class="text-muted">최근 판매 내역이 없습니다.</p>
                    </div>
                    @endforelse
                </div>
                <div class="card-footer">
                    <a href="{{ route('home.partner.sales.history') }}" class="btn btn-outline-primary btn-sm">
                        전체 판매 이력 보기
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">빠른 작업</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('home.partner.sales.history') }}" class="btn btn-outline-primary w-100">
                                <i class="fe fe-clock me-2"></i>판매 이력 보기
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('home.partner.sales.statistics') }}" class="btn btn-outline-info w-100">
                                <i class="fe fe-bar-chart-2 me-2"></i>상세 통계 보기
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('home.partner.commission.index') }}" class="btn btn-outline-success w-100">
                                <i class="fe fe-dollar-sign me-2"></i>커미션 현황 보기
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
// 월별 트렌드 차트
const monthlyOptions = {
    series: [{
        name: '판매 건수',
        data: @json($monthlyTrend['sales'])
    }, {
        name: '판매 금액',
        data: @json($monthlyTrend['amounts'])
    }],
    chart: {
        height: 350,
        type: 'line',
        zoom: {
            enabled: false
        }
    },
    dataLabels: {
        enabled: false
    },
    stroke: {
        curve: 'straight'
    },
    grid: {
        row: {
            colors: ['#f3f3f3', 'transparent'],
            opacity: 0.5
        },
    },
    xaxis: {
        categories: @json($monthlyTrend['months'])
    }
};

const monthlyChart = new ApexCharts(document.querySelector("#monthlyTrendChart"), monthlyOptions);
monthlyChart.render();
</script>
@endpush