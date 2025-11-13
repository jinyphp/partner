@extends('jiny-partner::layouts.home')

@section('title', $pageTitle ?? '리뷰 현황')

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
                            <li class="breadcrumb-item active" aria-current="page">리뷰 현황</li>
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
                            <h4 class="mb-0">{{ $reviewStats['total_received'] }}</h4>
                            <p class="mb-0">받은 리뷰</p>
                        </div>
                        <div class="icon-shape icon-md bg-primary text-white rounded-circle">
                            <i class="fe fe-inbox"></i>
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
                            <h4 class="mb-0">{{ $reviewStats['total_given'] }}</h4>
                            <p class="mb-0">작성한 리뷰</p>
                        </div>
                        <div class="icon-shape icon-md bg-success text-white rounded-circle">
                            <i class="fe fe-send"></i>
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
                            <h4 class="mb-0">{{ number_format($reviewStats['average_rating'], 1) }}</h4>
                            <p class="mb-0">평균 평점</p>
                        </div>
                        <div class="icon-shape icon-md bg-warning text-white rounded-circle">
                            <i class="fe fe-star"></i>
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
                            <div class="d-flex align-items-center">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="fe fe-star {{ $i <= $reviewStats['average_rating'] ? 'text-warning' : 'text-muted' }} me-1"></i>
                                @endfor
                            </div>
                            <p class="mb-0">별점 분포</p>
                        </div>
                        <div class="icon-shape icon-md bg-info text-white rounded-circle">
                            <i class="fe fe-award"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rating Distribution -->
    <div class="row">
        <div class="col-xl-6 col-lg-12 col-md-12 col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">평점 분포</h4>
                </div>
                <div class="card-body">
                    @foreach([5,4,3,2,1] as $rating)
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3" style="width: 80px;">
                            <span>{{ $rating }}점</span>
                            @for($i = 1; $i <= $rating; $i++)
                                <i class="fe fe-star text-warning small"></i>
                            @endfor
                        </div>
                        <div class="flex-grow-1">
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar" role="progressbar"
                                     style="width: {{ $reviewStats['total_received'] > 0 ? ($reviewStats['rating_distribution'][$rating] / $reviewStats['total_received']) * 100 : 0 }}%"></div>
                            </div>
                        </div>
                        <div class="ms-3">
                            <span class="text-muted">{{ $reviewStats['rating_distribution'][$rating] }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-xl-6 col-lg-12 col-md-12 col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">월별 리뷰 트렌드</h4>
                </div>
                <div class="card-body">
                    <div id="reviewTrendChart" style="height: 280px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Reviews -->
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">최근 받은 리뷰</h4>
                    <a href="{{ route('home.partner.reviews.received') }}" class="btn btn-outline-primary btn-sm">
                        전체 보기
                    </a>
                </div>
                <div class="card-body">
                    @if(count($reviewStats['recent_reviews']) > 0)
                        @foreach($reviewStats['recent_reviews'] as $review)
                        <div class="d-flex border-bottom pb-3 mb-3">
                            <div class="avatar avatar-md">
                                <div class="avatar-img rounded-circle bg-primary text-white d-flex align-items-center justify-content-center">
                                    {{ mb_substr($review->reviewer_name, 0, 1, 'UTF-8') }}
                                </div>
                            </div>
                            <div class="ms-3 flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">{{ $review->reviewer_name }}</h6>
                                        <div class="mb-2">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="fe fe-star {{ $i <= $review->rating ? 'text-warning' : 'text-muted' }} small"></i>
                                            @endfor
                                        </div>
                                        <p class="mb-1">{{ $review->comment }}</p>
                                        <small class="text-muted">{{ $review->project_title }} • {{ $review->created_at->diffForHumans() }}</small>
                                    </div>
                                    <span class="badge bg-{{ $review->is_public ? 'success' : 'secondary' }}">
                                        {{ $review->is_public ? '공개' : '비공개' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i class="fe fe-inbox display-4 text-muted"></i>
                            </div>
                            <h6 class="mb-1">아직 받은 리뷰가 없습니다</h6>
                            <p class="text-muted">프로젝트를 완료하면 고객으로부터 리뷰를 받을 수 있습니다.</p>
                        </div>
                    @endif
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
                            <a href="{{ route('home.partner.reviews.received') }}" class="btn btn-outline-primary w-100">
                                <i class="fe fe-inbox me-2"></i>받은 리뷰 보기
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('home.partner.reviews.given') }}" class="btn btn-outline-success w-100">
                                <i class="fe fe-send me-2"></i>작성한 리뷰 보기
                            </a>
                        </div>
                        <div class="col-md-4 mb-3">
                            <a href="{{ route('home.partner.sales.index') }}" class="btn btn-outline-info w-100">
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
// 리뷰 트렌드 차트
const reviewOptions = {
    series: [{
        name: '받은 리뷰',
        data: @json($monthlyTrend['received'])
    }, {
        name: '작성한 리뷰',
        data: @json($monthlyTrend['given'])
    }],
    chart: {
        height: 280,
        type: 'bar',
        toolbar: {
            show: false
        }
    },
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: '55%',
            endingShape: 'rounded'
        },
    },
    dataLabels: {
        enabled: false
    },
    stroke: {
        show: true,
        width: 2,
        colors: ['transparent']
    },
    xaxis: {
        categories: @json($monthlyTrend['months'])
    },
    fill: {
        opacity: 1
    },
    colors: ['#007bff', '#28a745']
};

const reviewChart = new ApexCharts(document.querySelector("#reviewTrendChart"), reviewOptions);
reviewChart.render();
</script>
@endpush
