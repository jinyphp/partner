@extends('jiny-site::layouts.home')

@section('title', '수익 계산기')

@section('content')
<div class="container-fluid p-6">
    <div class="row">
        <div class="col-lg-12">
            <div class="border-bottom pb-3 mb-3">
                <h1 class="mb-1 h2 fw-bold">수익 계산기</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home.partner.index') }}">파트너 홈</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('home.partner.commission.index') }}">커미션 관리</a></li>
                        <li class="breadcrumb-item active">수익 계산기</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Calculator Form -->
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">계산 조건</h4>
                </div>
                <div class="card-body">
                    <form id="calculatorForm">
                        <div class="mb-3">
                            <label class="form-label">판매 금액 (원)</label>
                            <input type="number" name="sale_amount" class="form-control" value="{{ $calculations['sale_amount'] ?? 100000 }}" min="0" step="1000">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">판매 건수</label>
                            <input type="number" name="sales_count" class="form-control" value="{{ $calculations['sales_count'] ?? 1 }}" min="1">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">기간</label>
                            <select name="period" class="form-select">
                                <option value="daily">일별</option>
                                <option value="weekly">주별</option>
                                <option value="monthly" selected>월별</option>
                                <option value="yearly">연별</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="include_referrals" class="form-check-input" id="includeReferrals">
                                <label class="form-check-label" for="includeReferrals">
                                    추천 보너스 포함
                                </label>
                            </div>
                        </div>

                        <div class="mb-3" id="referralSection" style="display: none;">
                            <label class="form-label">추천 건수</label>
                            <input type="number" name="referral_count" class="form-control" value="0" min="0">
                        </div>

                        <button type="submit" class="btn btn-primary w-100">계산하기</button>
                    </form>
                </div>
            </div>

            <!-- Current Tier Info -->
            <div class="card mt-4">
                <div class="card-header">
                    <h4 class="mb-0">현재 등급 정보</h4>
                </div>
                <div class="card-body">
                    <h5 class="text-primary">{{ $currentTier->name ?? '기본 등급' }}</h5>
                    <p class="mb-2">커미션 비율: <strong>{{ $calculations['commission_rate'] ?? 5 }}%</strong></p>
                    <p class="mb-0">추천 보너스: <strong>{{ $currentTier->referral_bonus_rate ?? 1 }}%</strong></p>
                </div>
            </div>
        </div>

        <!-- Results -->
        <div class="col-xl-8">
            <!-- Basic Calculation Results -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">계산 결과</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="p-3 border rounded text-center">
                                <h6 class="text-muted mb-1">직접 커미션</h6>
                                <h4 class="text-success">{{ number_format($calculations['direct_commission'] ?? 0) }}원</h4>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded text-center">
                                <h6 class="text-muted mb-1">총 예상 수익</h6>
                                <h4 class="text-primary">{{ number_format($calculations['total_commission'] ?? 0) }}원</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Period Projections -->
            @if(isset($calculations['projected_earnings']))
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">기간별 예상 수익</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($calculations['projected_earnings'] as $period => $amount)
                        <div class="col-md-3 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h6 class="mb-1">
                                    @if($period === 'daily') 일별
                                    @elseif($period === 'weekly') 주별
                                    @elseif($period === 'monthly') 월별
                                    @else 연별
                                    @endif
                                </h6>
                                <h5 class="text-primary">{{ number_format($amount) }}원</h5>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Tier Comparison -->
            @if(isset($tierComparison))
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">등급별 수익 비교</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>등급</th>
                                    <th>커미션 비율</th>
                                    <th>예상 수익</th>
                                    <th>차이</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tierComparison as $comparison)
                                <tr class="{{ $comparison['tier']->id == $currentTier->id ? 'table-primary' : '' }}">
                                    <td>{{ $comparison['tier']->name }}</td>
                                    <td>{{ $comparison['tier']->commission_rate }}%</td>
                                    <td>{{ number_format($comparison['commission']) }}원</td>
                                    <td>
                                        @if($comparison['tier']->id != $currentTier->id)
                                            <span class="text-{{ $comparison['difference'] > 0 ? 'success' : 'muted' }}">
                                                {{ $comparison['difference'] > 0 ? '+' : '' }}{{ number_format($comparison['difference']) }}원
                                            </span>
                                        @else
                                            <span class="badge bg-primary">현재 등급</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Growth Simulation -->
            @if(isset($growthSimulation))
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">연간 성장 시뮬레이션</h4>
                    <small class="text-muted">매월 10% 성장 가정</small>
                </div>
                <div class="card-body">
                    <div id="growthChart" style="height: 300px;"></div>
                    <div class="mt-3 text-center">
                        <h6>연간 총 예상 수익: <span class="text-success">{{ number_format($growthSimulation['total_year_earning']) }}원</span></h6>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
// 추천 보너스 체크박스 토글
document.getElementById('includeReferrals').addEventListener('change', function() {
    const referralSection = document.getElementById('referralSection');
    referralSection.style.display = this.checked ? 'block' : 'none';
});

// 계산기 폼 제출
document.getElementById('calculatorForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const params = new URLSearchParams(formData);

    window.location.href = '{{ route("home.partner.commission.calculate") }}?' + params.toString();
});

// 성장 시뮬레이션 차트
@if(isset($growthSimulation))
const growthOptions = {
    series: [{
        name: '예상 수익',
        data: @json($growthSimulation['earnings'])
    }],
    chart: {
        height: 300,
        type: 'line',
        curve: 'smooth'
    },
    xaxis: {
        categories: @json($growthSimulation['months'])
    },
    colors: ['#28a745'],
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.7,
            opacityTo: 0.3,
        }
    }
};

const growthChart = new ApexCharts(document.querySelector("#growthChart"), growthOptions);
growthChart.render();
@endif
</script>
@endpush