@extends('jiny-site::layouts.home')

@section('title', $pageTitle ?? '커미션 현황')

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
                            <li class="breadcrumb-item active" aria-current="page">커미션 현황</li>
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
                            <h4 class="mb-0">{{ number_format($commissionStats['total_commission']) }}원</h4>
                            <p class="mb-0">총 커미션</p>
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
                            <h4 class="mb-0">{{ number_format($commissionStats['pending_commission']) }}원</h4>
                            <p class="mb-0">대기 중 커미션</p>
                        </div>
                        <div class="icon-shape icon-md bg-warning text-white rounded-circle">
                            <i class="fe fe-clock"></i>
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
                            <h4 class="mb-0">{{ number_format($commissionStats['monthly_commission']) }}원</h4>
                            <p class="mb-0">이번 달 커미션</p>
                        </div>
                        <div class="icon-shape icon-md bg-primary text-white rounded-circle">
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
                            <h4 class="mb-0">{{ $commissionStats['commission_rate'] }}%</h4>
                            <p class="mb-0">커미션 비율</p>
                        </div>
                        <div class="icon-shape icon-md bg-info text-white rounded-circle">
                            <i class="fe fe-percent"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Recent Activity -->
    <div class="row">
        <div class="col-xl-8 col-lg-12 col-md-12 col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">월별 커미션 트렌드</h4>
                </div>
                <div class="card-body">
                    <div id="commissionTrendChart" style="height: 350px;"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-12 col-md-12 col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">최근 커미션</h4>
                </div>
                <div class="card-body">
                    @forelse($recentCommissions as $commission)
                    <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
                        <div>
                            <h6 class="mb-1">{{ $commission->commission_type ?? '직접 판매' }}</h6>
                            <p class="mb-0 text-muted">{{ $commission->created_at->format('Y-m-d H:i') }}</p>
                        </div>
                        <div class="text-end">
                            <h6 class="mb-0">{{ number_format($commission->amount) }}원</h6>
                            <span class="badge bg-{{ $commission->status === 'paid' ? 'success' : 'warning' }}">
                                {{ $commission->status === 'paid' ? '지급' : '대기' }}
                            </span>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-4">
                        <p class="text-muted">최근 커미션 내역이 없습니다.</p>
                    </div>
                    @endforelse
                </div>
                <div class="card-footer">
                    <a href="{{ route('home.partner.commission.history') }}" class="btn btn-outline-primary btn-sm">
                        전체 커미션 이력 보기
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Next Payment Info -->
    @if($nextPayment)
    <div class="row">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="icon-shape icon-md bg-primary text-white rounded-circle me-3">
                            <i class="fe fe-calendar"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">다음 지급 예정</h6>
                            <p class="mb-0">{{ $nextPayment->payment_date ? $nextPayment->payment_date->format('Y년 m월 d일') : '미정' }}에 {{ number_format($nextPayment->amount) }}원이 지급 예정입니다.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Quick Actions -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">빠른 작업</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('home.partner.commission.history') }}" class="btn btn-outline-primary w-100">
                                <i class="fe fe-list me-2"></i>커미션 이력
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('home.partner.commission.calculate') }}" class="btn btn-outline-info w-100">
                                <i class="fe fe-calculator me-2"></i>수익 계산기
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('home.partner.sales.index') }}" class="btn btn-outline-success w-100">
                                <i class="fe fe-shopping-cart me-2"></i>판매 현황 보기
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
// 커미션 트렌드 차트
const commissionOptions = {
    series: [{
        name: '커미션',
        data: @json($monthlyTrend['commissions'])
    }],
    chart: {
        height: 350,
        type: 'area',
        zoom: {
            enabled: false
        }
    },
    dataLabels: {
        enabled: false
    },
    stroke: {
        curve: 'smooth'
    },
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.7,
            opacityTo: 0.3,
        }
    },
    grid: {
        row: {
            colors: ['#f3f3f3', 'transparent'],
            opacity: 0.5
        },
    },
    xaxis: {
        categories: @json($monthlyTrend['months'])
    },
    colors: ['#28a745']
};

const commissionChart = new ApexCharts(document.querySelector("#commissionTrendChart"), commissionOptions);
commissionChart.render();
</script>
@endpush