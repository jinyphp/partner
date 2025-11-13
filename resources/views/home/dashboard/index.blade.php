@extends('jiny-partner::layouts.home')

@section('title', '파트너 대시보드')

@section('content')
<div class="container-fluid p-6">
    <!-- Page Header with Partner Status -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="border-bottom pb-3 mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-1 h2 fw-bold">파트너 대시보드</h1>
                        <p class="text-muted mb-0">안녕하세요, <strong>{{ $user->name ?? $userInfo['name'] }}</strong>님! 파트너 활동 현황을 확인하세요.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <span class="badge bg-primary fs-6" id="partner-tier">
                            <i class="fe fe-award me-1"></i>{{ $partner->partnerTier->tier_name ?? '기본 티어' }}
                        </span>
                        <span class="badge bg-success fs-6" id="partner-type">
                            <i class="fe fe-tag me-1"></i>{{ $partner->partnerType->type_name ?? '기본 타입' }}
                        </span>
                        <span class="badge bg-info fs-6">
                            <i class="fe fe-activity me-1"></i>활성 상태
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Partner Info Summary Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient-primary text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h4 class="text-white mb-2">
                                <i class="fe fe-user me-2"></i>파트너 정보
                            </h4>
                            <div class="row">
                                <div class="col-md-3">
                                    <p class="mb-1"><strong>이름:</strong> {{ $user->name ?? $userInfo['name'] }}</p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1"><strong>이메일:</strong> {{ $user->email ?? $userInfo['email'] }}</p>
                                </div>
                                <div class="col-md-2">
                                    <p class="mb-1"><strong>레벨:</strong> {{ $networkInfo['level'] }}</p>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-1"><strong>가입일:</strong> {{ $partner->created_at->format('Y-m-d') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="avatar avatar-xl">
                                <div class="avatar-img rounded-circle bg-light text-primary d-flex align-items-center justify-content-center">
                                    <i class="fe fe-user display-6"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <!-- Network Information -->
        <div class="col-xl-3 col-lg-6 col-md-12 col-12">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $networkInfo['children_count'] }}</h4>
                            <p class="mb-0">하위 파트너</p>
                            <small class="text-muted">
                                상위: {{ $networkInfo['parent_partner']->name ?? '없음' }}
                            </small>
                        </div>
                        <div class="icon-shape icon-md bg-info text-white rounded-circle">
                            <i class="fe fe-users"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales Information -->
        <div class="col-xl-3 col-lg-6 col-md-12 col-12">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ number_format($salesStats['current_month_sales']) }}원</h4>
                            <p class="mb-0">이번 달 매출</p>
                            <small class="text-muted">
                                총 매출: {{ number_format($salesStats['total_sales']) }}원
                            </small>
                        </div>
                        <div class="icon-shape icon-md bg-success text-white rounded-circle">
                            <i class="fe fe-trending-up"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Commission Information -->
        <div class="col-xl-3 col-lg-6 col-md-12 col-12">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ number_format($commissionStats['this_month_commission']) }}원</h4>
                            <p class="mb-0">이번 달 커미션</p>
                            <small class="text-muted">
                                총 커미션: {{ number_format($commissionStats['total_commission']) }}원
                            </small>
                        </div>
                        <div class="icon-shape icon-md bg-warning text-white rounded-circle">
                            <i class="fe fe-dollar-sign"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Commission -->
        <div class="col-xl-3 col-lg-6 col-md-12 col-12">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ number_format($commissionStats['pending_commission']) }}원</h4>
                            <p class="mb-0">대기 중 커미션</p>
                            <small class="text-muted">
                                총 {{ $commissionStats['commission_count'] }}건
                            </small>
                        </div>
                        <div class="icon-shape icon-md bg-secondary text-white rounded-circle">
                            <i class="fe fe-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="row mb-4">
        <!-- Recent Sales Records -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">최근 매출 기록</h4>
                    <a href="{{ route('home.partner.sales.index') }}" class="btn btn-outline-primary btn-sm">
                        전체 보기
                    </a>
                </div>
                <div class="card-body">
                    @if($recentSales->count() > 0)
                        @foreach($recentSales as $sale)
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3 sales-record" data-sale-id="{{ $sale->id }}">
                            <div>
                                <h6 class="mb-1 sales-title">{{ $sale->title ?? '매출' }}</h6>
                                <p class="text-muted mb-1 sales-date">{{ $sale->sales_date ? date('Y-m-d', strtotime($sale->sales_date)) : $sale->created_at->format('Y-m-d') }}</p>
                                <small class="sales-category">{{ $sale->category ?? '일반' }} - {{ $sale->product_type ?? '상품' }}</small>
                            </div>
                            <div class="text-end">
                                <h6 class="mb-1 text-success sales-amount">{{ number_format($sale->amount) }}원</h6>
                                <span class="badge bg-{{ $sale->status === 'confirmed' ? 'success' : ($sale->status === 'pending' ? 'warning' : 'secondary') }} sales-status">
                                    {{ $sale->status === 'confirmed' ? '확정' : ($sale->status === 'pending' ? '대기' : '기타') }}
                                </span>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <i class="fe fe-bar-chart display-4 text-muted"></i>
                            <p class="text-muted mt-2">아직 매출 기록이 없습니다.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sub Partners -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">하위 파트너</h4>
                    <span class="badge bg-info">{{ $networkInfo['children_count'] }}명</span>
                </div>
                <div class="card-body">
                    @if($subPartners->count() > 0)
                        @foreach($subPartners as $subPartner)
                        <div class="d-flex align-items-center mb-3 sub-partner" data-partner-id="{{ $subPartner->id }}">
                            <div class="avatar avatar-sm me-3">
                                <div class="avatar-img rounded-circle bg-primary text-white d-flex align-items-center justify-content-center">
                                    {{ mb_substr($subPartner->name, 0, 1, 'UTF-8') }}
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0 sub-partner-name">{{ $subPartner->name }}</h6>
                                <small class="text-muted sub-partner-email">{{ $subPartner->email }}</small>
                                <div class="d-flex gap-1 mt-1">
                                    <span class="badge bg-primary sub-partner-tier" style="font-size: 0.7rem;">
                                        {{ $subPartner->partnerTier->tier_name ?? '기본' }}
                                    </span>
                                    <span class="badge bg-success sub-partner-type" style="font-size: 0.7rem;">
                                        {{ $subPartner->partnerType->type_name ?? '일반' }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-end">
                                <small class="text-muted d-block sub-partner-monthly-sales">
                                    월: {{ number_format($subPartner->monthly_sales ?? 0) }}원
                                </small>
                                <small class="text-muted sub-partner-total-sales">
                                    총: {{ number_format($subPartner->total_sales ?? 0) }}원
                                </small>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <i class="fe fe-users display-4 text-muted"></i>
                            <p class="text-muted mt-2 small">아직 하위 파트너가 없습니다.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Statistics -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">상세 통계</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <div class="p-4 bg-light rounded">
                                <h2 class="text-primary mb-2" id="total-sales-count">{{ $salesStats['total_sales_count'] }}</h2>
                                <p class="mb-0 text-muted">총 거래 건수</p>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="p-4 bg-light rounded">
                                <h2 class="text-success mb-2" id="current-year-sales">
                                    {{ number_format($salesStats['current_year_sales']) }}원
                                </h2>
                                <p class="mb-0 text-muted">올해 매출</p>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="p-4 bg-light rounded">
                                <h2 class="text-warning mb-2" id="commission-count">{{ $commissionStats['commission_count'] }}</h2>
                                <p class="mb-0 text-muted">커미션 건수</p>
                            </div>
                        </div>
                    </div>
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
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="{{ route('home.partner.sales.index') }}" class="btn btn-outline-primary w-100">
                                <i class="fe fe-trending-up me-2"></i>판매 현황 보기
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="{{ route('home.partner.commission.index') }}" class="btn btn-outline-success w-100">
                                <i class="fe fe-dollar-sign me-2"></i>커미션 현황
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="{{ route('home.partner.reviews.index') }}" class="btn btn-outline-info w-100">
                                <i class="fe fe-star me-2"></i>리뷰 관리
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="{{ route('home.partner.commission.calculate') }}" class="btn btn-outline-warning w-100">
                                <i class="fe fe-calculator me-2"></i>수익 계산기
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.bg-gradient-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
}

.card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.icon-shape {
    width: 3rem;
    height: 3rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.icon-md {
    width: 2.5rem;
    height: 2.5rem;
}

.avatar {
    position: relative;
    display: inline-block;
}

.avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-sm {
    width: 2rem;
    height: 2rem;
}

.avatar-xl {
    width: 4rem;
    height: 4rem;
}
</style>
@endsection
