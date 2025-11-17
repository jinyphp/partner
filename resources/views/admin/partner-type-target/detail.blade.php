@extends('jiny-partner::layouts.admin.sidebar')

@section('content')
{{-- ============================================================= --}}
{{-- 🔍 특정 파트너 타입 상세 실적 분석 --}}
{{-- ============================================================= --}}

<div class="container-fluid">
    {{-- 페이지 헤더 --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <span style="color: {{ $partnerType->color }}">
                    <i class="{{ $partnerType->icon }}"></i>
                    {{ $partnerType->type_name }}
                </span>
                상세 실적 분석
            </h1>
            <nav aria-label="breadcrumb" class="mt-2">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.partner.index') }}">파트너 관리</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.partner.type.index') }}">파트너 타입</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.partner.type.target') }}">실적 관리</a></li>
                    <li class="breadcrumb-item active">{{ $partnerType->type_name }}</li>
                </ol>
            </nav>
        </div>

        <div class="btn-group">
            <a href="{{ route('admin.partner.type.target') }}" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> 목록
            </a>
            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#goalUpdateModal">
                <i class="fas fa-bullseye"></i> 목표 설정
            </button>
        </div>
    </div>

    {{-- 조회 옵션 --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-2">
                            <label for="year" class="form-label">연도</label>
                            <select name="year" id="year" class="form-select">
                                @foreach($availableYears as $yearOption)
                                    <option value="{{ $yearOption }}" {{ $selectedYear == $yearOption ? 'selected' : '' }}>
                                        {{ $yearOption }}년
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="month" class="form-label">월</label>
                            <select name="month" id="month" class="form-select">
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ $selectedMonth == $i ? 'selected' : '' }}>
                                        {{ $i }}월
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="view" class="form-label">조회 방식</label>
                            <select name="view" id="view" class="form-select">
                                <option value="monthly" {{ $view == 'monthly' ? 'selected' : '' }}>월별</option>
                                <option value="quarterly" {{ $view == 'quarterly' ? 'selected' : '' }}>분기별</option>
                                <option value="yearly" {{ $view == 'yearly' ? 'selected' : '' }}>연별</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> 조회
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- 개요 정보 --}}
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-3x text-primary mb-3"></i>
                    <h4 class="font-weight-bold">{{ number_format($detailData['overview']['current_month']->active_partners ?? 0) }}</h4>
                    <p class="text-muted mb-0">활성 파트너</p>
                    <small class="text-success">
                        총 {{ number_format($detailData['overview']['total_partners']) }}명 중
                        {{ $detailData['overview']['participation_rate'] }}%
                    </small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-success">
                <div class="card-body text-center">
                    <i class="fas fa-won-sign fa-3x text-success mb-3"></i>
                    <h4 class="font-weight-bold">{{ number_format($detailData['overview']['current_month']->total_sales ?? 0) }}</h4>
                    <p class="text-muted mb-0">월 총 매출 (원)</p>
                    <small class="{{ $detailData['overview']['growth_rates']['sales'] >= 0 ? 'text-success' : 'text-danger' }}">
                        전월 대비 {{ $detailData['overview']['growth_rates']['sales'] > 0 ? '+' : '' }}{{ $detailData['overview']['growth_rates']['sales'] }}%
                    </small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-info">
                <div class="card-body text-center">
                    <i class="fas fa-chart-bar fa-3x text-info mb-3"></i>
                    <h4 class="font-weight-bold">{{ number_format($detailData['overview']['current_month']->total_cases ?? 0) }}</h4>
                    <p class="text-muted mb-0">총 처리 건수</p>
                    <small class="{{ $detailData['overview']['growth_rates']['cases'] >= 0 ? 'text-success' : 'text-danger' }}">
                        전월 대비 {{ $detailData['overview']['growth_rates']['cases'] > 0 ? '+' : '' }}{{ $detailData['overview']['growth_rates']['cases'] }}%
                    </small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <i class="fas fa-percentage fa-3x text-warning mb-3"></i>
                    <h4 class="font-weight-bold">{{ $detailData['overview']['efficiency_metrics']['commission_rate'] }}%</h4>
                    <p class="text-muted mb-0">실제 수수료율</p>
                    <small class="text-muted">
                        파트너당 {{ number_format($detailData['overview']['efficiency_metrics']['sales_per_partner']) }}원
                    </small>
                </div>
            </div>
        </div>
    </div>

    {{-- 목표 달성 현황 --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header" style="background-color: {{ $partnerType->color }}20">
                    <h5 class="mb-0" style="color: {{ $partnerType->color }}">
                        <i class="fas fa-bullseye"></i>
                        {{ $selectedMonth }}월 목표 달성 현황
                    </h5>
                </div>
                <div class="card-body">
                    @php
                        $achievement = $detailData['goal_achievement'];
                        $rate = $achievement['achievement_rate'];
                        $status = $achievement['status'];

                        $statusConfig = [
                            'excellent' => ['color' => 'success', 'width' => 100],
                            'success' => ['color' => 'success', 'width' => min(100, $rate)],
                            'warning' => ['color' => 'warning', 'width' => min(100, $rate)],
                            'danger' => ['color' => 'danger', 'width' => min(100, $rate)]
                        ];

                        $config = $statusConfig[$status] ?? $statusConfig['danger'];
                    @endphp

                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span><strong>매출 목표 달성률</strong></span>
                                    <span><strong>{{ $rate }}%</strong></span>
                                </div>
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar bg-{{ $config['color'] }}"
                                         role="progressbar"
                                         style="width: {{ $config['width'] }}%"
                                         aria-valuenow="{{ $rate }}"
                                         aria-valuemin="0"
                                         aria-valuemax="100">
                                        {{ $rate }}%
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <small class="text-muted">현재 실적</small><br>
                                    <strong>{{ number_format($achievement['current_performance']) }}원</strong>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">월 목표</small><br>
                                    <strong>{{ number_format($achievement['target']) }}원</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <h6>목표 달성 파트너</h6>
                                <div class="h4 text-{{ $config['color'] }}">
                                    {{ $achievement['achieving_partners'] }}명
                                </div>
                                <small class="text-muted">
                                    전체 {{ $achievement['total_active_partners'] }}명 중
                                    {{ $achievement['partner_achievement_rate'] }}%
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 성과 타임라인 --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line"></i>
                        {{ $selectedYear }}년 성과 타임라인 ({{ $view === 'monthly' ? '월별' : ($view === 'quarterly' ? '분기별' : '연별') }})
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="performanceTimelineChart" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- 파트너 랭킹 & 성과 분포 --}}
    <div class="row mb-4">
        <div class="col-lg-8">
            {{-- 파트너 랭킹 --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-trophy"></i>
                        {{ $selectedMonth }}월 TOP 파트너 랭킹
                    </h5>
                </div>
                <div class="card-body">
                    @if($detailData['partner_rankings']->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>순위</th>
                                        <th>파트너명</th>
                                        <th>파트너 코드</th>
                                        <th>등급</th>
                                        <th>총 매출</th>
                                        <th>건수</th>
                                        <th>평균 단가</th>
                                        <th>달성률</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($detailData['partner_rankings'] as $partner)
                                        <tr>
                                            <td>
                                                <span class="badge badge-{{ $partner->rank <= 3 ? 'warning' : 'secondary' }}">
                                                    {{ $partner->rank }}
                                                </span>
                                            </td>
                                            <td>{{ $partner->name }}</td>
                                            <td><code>{{ $partner->partner_code }}</code></td>
                                            <td><span class="badge badge-info">{{ $partner->tier_level }}</span></td>
                                            <td><strong>{{ number_format($partner->total_sales) }}원</strong></td>
                                            <td>{{ number_format($partner->total_cases) }}건</td>
                                            <td>{{ number_format($partner->avg_sale_amount) }}원</td>
                                            <td>
                                                <span class="badge badge-{{ $partner->achievement_rate >= 100 ? 'success' : 'warning' }}">
                                                    {{ $partner->achievement_rate }}%
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-chart-bar fa-3x mb-3"></i>
                            <p>해당 월에 활동한 파트너가 없습니다.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            {{-- 성과 분포 --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie"></i>
                        성과 분포
                    </h5>
                </div>
                <div class="card-body">
                    @php
                        $distribution = $detailData['performance_distribution'];
                        $ranges = $distribution['ranges'];
                        $totalPartners = $distribution['total_partners'];
                    @endphp

                    <canvas id="distributionChart" style="height: 250px;"></canvas>

                    <div class="mt-3">
                        <div class="row text-center">
                            <div class="col-6">
                                <small class="text-muted">평균 성과</small><br>
                                <strong>{{ number_format($distribution['avg_performance']) }}원</strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">중간값</small><br>
                                <strong>{{ number_format($distribution['median_performance']) }}원</strong>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="performance-breakdown">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small>우수 (150% 이상)</small>
                            <span class="badge badge-success">{{ $ranges['excellent'] }}명</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small>양호 (100-150%)</small>
                            <span class="badge badge-primary">{{ $ranges['high'] }}명</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small>보통 (50-100%)</small>
                            <span class="badge badge-warning">{{ $ranges['medium'] }}명</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small>미흡 (50% 미만)</small>
                            <span class="badge badge-danger">{{ $ranges['low'] }}명</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <small>미활동</small>
                            <span class="badge badge-secondary">{{ $ranges['no_sales'] }}명</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 성과 미달자 개선 지원 --}}
    @if($detailData['underperformers']->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle"></i>
                        성과 개선 대상 파트너
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>파트너명</th>
                                    <th>파트너 코드</th>
                                    <th>현재 매출</th>
                                    <th>목표까지 부족분</th>
                                    <th>건수</th>
                                    <th>개선 조치</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($detailData['underperformers'] as $partner)
                                    @php
                                        $shortage = $partnerType->min_baseline_sales - $partner->total_sales;
                                    @endphp
                                    <tr>
                                        <td>{{ $partner->name }}</td>
                                        <td><code>{{ $partner->partner_code }}</code></td>
                                        <td>{{ number_format($partner->total_sales) }}원</td>
                                        <td class="text-danger">
                                            <strong>{{ number_format($shortage) }}원</strong>
                                        </td>
                                        <td>{{ $partner->total_cases }}건</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-warning">
                                                <i class="fas fa-phone"></i> 상담
                                            </button>
                                            <button class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-graduation-cap"></i> 교육
                                            </button>
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
    @endif
</div>

{{-- 목표 설정 모달 --}}
<div class="modal fade" id="goalUpdateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.partner.type.target.update-goal', $partnerType->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">
                        <span style="color: {{ $partnerType->color }}">
                            <i class="{{ $partnerType->icon }}"></i>
                            {{ $partnerType->type_name }}
                        </span>
                        목표 설정
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="min_baseline_sales" class="form-label">최소 매출 기준 (원)</label>
                                <input type="number" class="form-control" id="min_baseline_sales" name="min_baseline_sales"
                                       value="{{ $partnerType->min_baseline_sales }}" step="100000">
                            </div>
                            <div class="mb-3">
                                <label for="min_baseline_cases" class="form-label">최소 처리 건수</label>
                                <input type="number" class="form-control" id="min_baseline_cases" name="min_baseline_cases"
                                       value="{{ $partnerType->min_baseline_cases }}">
                            </div>
                            <div class="mb-3">
                                <label for="min_baseline_clients" class="form-label">최소 고객 수</label>
                                <input type="number" class="form-control" id="min_baseline_clients" name="min_baseline_clients"
                                       value="{{ $partnerType->min_baseline_clients }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="baseline_quality_score" class="form-label">최소 품질 점수</label>
                                <input type="number" class="form-control" id="baseline_quality_score" name="baseline_quality_score"
                                       value="{{ $partnerType->baseline_quality_score }}" step="0.1" min="0" max="100">
                            </div>
                            <div class="mb-3">
                                <label for="default_commission_rate" class="form-label">기본 수수료율 (%)</label>
                                <input type="number" class="form-control" id="default_commission_rate" name="default_commission_rate"
                                       value="{{ $partnerType->default_commission_rate }}" step="0.01" min="0" max="100">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="update_reason" class="form-label">변경 사유 <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="update_reason" name="update_reason" rows="3" required
                                  placeholder="목표 변경 사유를 입력해주세요."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i> 목표 업데이트
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 성과 타임라인 차트
    const timelineCtx = document.getElementById('performanceTimelineChart').getContext('2d');
    const timelineData = {!! json_encode($detailData['performance_timeline']) !!};

    new Chart(timelineCtx, {
        type: 'bar',
        data: {
            labels: timelineData.map(item => item.period_name),
            datasets: [{
                label: '매출',
                data: timelineData.map(item => item.sales),
                backgroundColor: '{{ $partnerType->color }}80',
                borderColor: '{{ $partnerType->color }}',
                borderWidth: 1,
                yAxisID: 'y'
            }, {
                label: '건수',
                data: timelineData.map(item => item.cases),
                backgroundColor: '#28a74580',
                borderColor: '#28a745',
                borderWidth: 1,
                yAxisID: 'y1',
                type: 'line'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: '매출 (원)'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: '건수'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });

    // 성과 분포 차트
    const distributionCtx = document.getElementById('distributionChart').getContext('2d');
    const distributionData = {!! json_encode($detailData['performance_distribution']['ranges']) !!};

    new Chart(distributionCtx, {
        type: 'doughnut',
        data: {
            labels: ['우수', '양호', '보통', '미흡', '미활동'],
            datasets: [{
                data: [
                    distributionData.excellent,
                    distributionData.high,
                    distributionData.medium,
                    distributionData.low,
                    distributionData.no_sales
                ],
                backgroundColor: [
                    '#28a745',
                    '#007bff',
                    '#ffc107',
                    '#dc3545',
                    '#6c757d'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
});
</script>
@endpush

@endsection
