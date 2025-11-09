@extends('jiny-site::layouts.home')

@section('title', '판매 이력')

@section('content')
<div class="container-fluid p-6">
    <div class="row">
        <div class="col-lg-12">
            <div class="border-bottom pb-3 mb-3">
                <h1 class="mb-1 h2 fw-bold">판매 이력</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home.partner.index') }}">파트너 홈</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('home.partner.sales.index') }}">판매 관리</a></li>
                        <li class="breadcrumb-item active">판매 이력</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">기간</label>
                            <select name="period" class="form-select">
                                <option value="all" {{ $currentPeriod === 'all' ? 'selected' : '' }}>전체</option>
                                <option value="this_month" {{ $currentPeriod === 'this_month' ? 'selected' : '' }}>이번 달</option>
                                <option value="last_month" {{ $currentPeriod === 'last_month' ? 'selected' : '' }}>지난 달</option>
                                <option value="this_year" {{ $currentPeriod === 'this_year' ? 'selected' : '' }}>올해</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">상태</label>
                            <select name="status" class="form-select">
                                <option value="all" {{ $currentStatus === 'all' ? 'selected' : '' }}>전체</option>
                                <option value="confirmed" {{ $currentStatus === 'confirmed' ? 'selected' : '' }}>확정</option>
                                <option value="pending" {{ $currentStatus === 'pending' ? 'selected' : '' }}>대기</option>
                                <option value="cancelled" {{ $currentStatus === 'cancelled' ? 'selected' : '' }}>취소</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">검색</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ number_format($filteredStats['total_count']) }}</h4>
                    <p class="mb-0">총 건수</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ number_format($filteredStats['total_amount']) }}원</h4>
                    <p class="mb-0">총 금액</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ number_format($filteredStats['avg_amount']) }}원</h4>
                    <p class="mb-0">평균 금액</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ number_format($filteredStats['confirmed_count']) }}</h4>
                    <p class="mb-0">확정 건수</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales History Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">판매 내역</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>날짜</th>
                                    <th>상품명</th>
                                    <th>금액</th>
                                    <th>상태</th>
                                    <th>고객</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($salesHistory as $sale)
                                <tr>
                                    <td>{{ $sale->created_at->format('Y-m-d H:i') }}</td>
                                    <td>{{ $sale->product_name ?? '상품명' }}</td>
                                    <td>{{ number_format($sale->amount) }}원</td>
                                    <td>
                                        <span class="badge bg-{{ $sale->status === 'confirmed' ? 'success' : ($sale->status === 'pending' ? 'warning' : 'danger') }}">
                                            {{ $sale->status === 'confirmed' ? '확정' : ($sale->status === 'pending' ? '대기' : '취소') }}
                                        </span>
                                    </td>
                                    <td>{{ $sale->customer_name ?? '고객명' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <p class="text-muted">판매 내역이 없습니다.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($salesHistory && method_exists($salesHistory, 'links'))
                        {{ $salesHistory->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection