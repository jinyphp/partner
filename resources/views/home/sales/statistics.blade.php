@extends('jiny-site::layouts.home')

@section('title', '판매 통계')

@section('content')
<div class="container-fluid p-6">
    <div class="row">
        <div class="col-lg-12">
            <div class="border-bottom pb-3 mb-3">
                <h1 class="mb-1 h2 fw-bold">판매 통계</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home.partner.index') }}">파트너 홈</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('home.partner.sales.index') }}">판매 관리</a></li>
                        <li class="breadcrumb-item active">판매 통계</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <!-- Period Statistics -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">기간별 통계</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($periodStats as $period => $stats)
                        <div class="col-md-2">
                            <div class="text-center p-3 border rounded">
                                <h6 class="mb-1">
                                    @if($period === 'today') 오늘
                                    @elseif($period === 'this_week') 이번 주
                                    @elseif($period === 'this_month') 이번 달
                                    @elseif($period === 'this_year') 올해
                                    @else 전체
                                    @endif
                                </h6>
                                <h4 class="mb-1">{{ number_format($stats['count']) }}</h4>
                                <p class="mb-0 text-muted small">{{ number_format($stats['amount']) }}원</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row mb-4">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">월별 판매 트렌드</h4>
                </div>
                <div class="card-body">
                    <div id="monthlyChart" style="height: 400px;"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">성과 지표</h4>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h6>전환율</h6>
                        <div class="progress mb-2" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: {{ $performanceMetrics['conversion_rate'] }}%"></div>
                        </div>
                        <small class="text-muted">{{ $performanceMetrics['conversion_rate'] }}%</small>
                    </div>

                    <div class="mb-4">
                        <h6>평균 판매 금액</h6>
                        <h4 class="text-primary">{{ number_format($performanceMetrics['avg_sale_amount']) }}원</h4>
                    </div>

                    <div class="mb-4">
                        <h6>성장률 (전월 대비)</h6>
                        <h4 class="text-{{ $performanceMetrics['growth_rate'] >= 0 ? 'success' : 'danger' }}">
                            {{ $performanceMetrics['growth_rate'] >= 0 ? '+' : '' }}{{ $performanceMetrics['growth_rate'] }}%
                        </h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ranking -->
    @if($ranking && $ranking['my_rank'])
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">이번 달 파트너 랭킹</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5 class="alert-heading">내 순위: {{ $ranking['my_rank'] }}위 / {{ $ranking['total_partners'] }}명</h5>
                    </div>

                    <h6 class="mb-3">상위 파트너</h6>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>순위</th>
                                    <th>파트너명</th>
                                    <th>판매 금액</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ranking['top_partners'] as $index => $partner)
                                <tr class="{{ $partner->id == $partnerUser->id ? 'table-primary' : '' }}">
                                    <td>{{ $index + 1 }}위</td>
                                    <td>{{ $partner->name }}</td>
                                    <td>{{ number_format($partner->total_amount) }}원</td>
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
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
// 월별 통계 차트
const monthlyOptions = {
    series: [{
        name: '판매 건수',
        data: @json(array_column($monthlyStats, 'count'))
    }, {
        name: '판매 금액',
        data: @json(array_column($monthlyStats, 'amount'))
    }],
    chart: {
        height: 400,
        type: 'line'
    },
    xaxis: {
        categories: @json(array_column($monthlyStats, 'month'))
    },
    yaxis: [{
        title: {
            text: '판매 건수'
        }
    }, {
        opposite: true,
        title: {
            text: '판매 금액'
        }
    }]
};

const monthlyChart = new ApexCharts(document.querySelector("#monthlyChart"), monthlyOptions);
monthlyChart.render();
</script>
@endpush