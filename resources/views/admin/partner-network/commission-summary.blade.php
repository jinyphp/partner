@extends('jiny-partner::layouts.admin.sidebar')

@section('title', $pageTitle)

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<style>
/* 기본 글자색을 검은색으로 설정 */
.container-fluid {
    color: #333333;
}

.card-body {
    color: #333333;
}

.card-body h5, .card-body h6, .card-body p, .card-body div {
    color: #333333 !important;
}

/* 라벨과 제목 글자색 강제 설정 */
label, .font-weight-bold, .form-label {
    color: #333333 !important;
}

/* 브레드크럼과 제목 */
.breadcrumb, .h3 {
    color: #333333;
}

/* text-muted 클래스를 가독성 있는 색상으로 변경 */
.text-muted {
    color: #666666 !important;
}

/* small 텍스트 색상 */
.small {
    color: #666666 !important;
}

/* 테이블 텍스트 색상 강제 설정 */
.table td, .table th {
    color: #333333 !important;
}

.table tbody td {
    color: #333333 !important;
}

/* 챠트 컨테이너 */
.chart-container {
    position: relative;
    height: 300px;
}
</style>
@endpush

@section('content')
<div class="container-fluid px-6 py-4">
    <!-- Page Header -->
    <div class="row">
        <div class="col-lg-12 col-md-12 col-12">
            <div class="border-bottom pb-3 mb-3 d-lg-flex align-items-center justify-content-between">
                <div class="mb-2 mb-lg-0">
                    <h1 class="mb-0 h2 fw-bold">{{ $pageTitle }}</h1>
                    <p class="mb-0 text-muted">{{ $partner->name }}님의 커미션 내역과 성과를 확인합니다.</p>
                </div>
                <div>
                    <a href="{{ route('admin.partner.network.commission.index') }}" class="btn btn-outline-secondary">
                        <i class="fe fe-arrow-left me-2"></i>커미션 관리로 돌아가기
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Partner Info Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="avatar avatar-lg">
                                <span class="avatar-initials rounded-circle bg-primary fs-4">
                                    {{ strtoupper(substr($partner->name, 0, 1)) }}
                                </span>
                            </div>
                        </div>
                        <div class="col">
                            <h4 class="mb-1">{{ $partner->name }}</h4>
                            <p class="mb-0 text-muted">{{ $partner->email }}</p>
                            @if($partner->partnerTier)
                                <span class="badge bg-primary">{{ $partner->partnerTier->tier_name }}</span>
                            @endif
                        </div>
                        <div class="col-auto">
                            <div class="text-end">
                                <div class="h5 mb-0">
                                    {{ $period === 'today' ? '오늘' :
                                       ($period === 'this_week' ? '이번주' :
                                       ($period === 'this_month' ? '이번달' :
                                       ($period === 'this_quarter' ? '이번분기' :
                                       ($period === 'this_year' ? '올해' : '이번달')))) }}
                                </div>
                                <small class="text-muted">
                                    {{ \Carbon\Carbon::parse($dateRange[0])->format('Y-m-d') }} ~
                                    {{ \Carbon\Carbon::parse($dateRange[1])->format('Y-m-d') }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Commission Statistics -->
    <div class="row mb-4">
        <div class="col-xl-3 col-lg-6 col-md-12 col-12">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ number_format($commissionStats['total_earned'] ?? 0) }}원</h4>
                            <p class="mb-0 text-muted">총 커미션</p>
                            @if(isset($previousStats['total_earned']))
                                @php
                                    $current = $commissionStats['total_earned'] ?? 0;
                                    $previous = $previousStats['total_earned'] ?? 0;
                                    $change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
                                @endphp
                                <small class="text-{{ $change >= 0 ? 'success' : 'danger' }}">
                                    <i class="fe fe-trending-{{ $change >= 0 ? 'up' : 'down' }}"></i>
                                    {{ number_format(abs($change), 1) }}% (이전 기간 대비)
                                </small>
                            @endif
                        </div>
                        <div class="icon-shape icon-md bg-primary text-white rounded-3">
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
                            <h4 class="mb-0">{{ number_format($commissionStats['total_active'] ?? 0) }}원</h4>
                            <p class="mb-0 text-muted">활성 커미션</p>
                        </div>
                        <div class="icon-shape icon-md bg-success text-white rounded-3">
                            <i class="fe fe-check-circle"></i>
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
                            <h4 class="mb-0">{{ $commissionStats['commission_count'] ?? 0 }}건</h4>
                            <p class="mb-0 text-muted">커미션 건수</p>
                        </div>
                        <div class="icon-shape icon-md bg-info text-white rounded-3">
                            <i class="fe fe-activity"></i>
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
                            @php
                                $count = $commissionStats['commission_count'] ?? 0;
                                $total = $commissionStats['total_earned'] ?? 0;
                                $average = $count > 0 ? $total / $count : 0;
                            @endphp
                            <h4 class="mb-0">{{ number_format($average) }}원</h4>
                            <p class="mb-0 text-muted">평균 커미션</p>
                        </div>
                        <div class="icon-shape icon-md bg-warning text-white rounded-3">
                            <i class="fe fe-trending-up"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Commission Type Breakdown -->
    @if(isset($commissionStats['by_type']) && count($commissionStats['by_type']) > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">커미션 타입별 분석</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($commissionStats['by_type'] as $type => $stats)
                            @php
                                $typeNames = [
                                    'direct_sales' => '직접 판매',
                                    'indirect_referral' => '간접 추천',
                                    'team_bonus' => '팀 보너스',
                                    'management_bonus' => '관리 보너스',
                                    'override_bonus' => '오버라이드 보너스',
                                    'recruitment_bonus' => '모집 보너스',
                                    'rank_bonus' => '등급 보너스'
                                ];
                            @endphp
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card border">
                                    <div class="card-body text-center">
                                        <h6 class="mb-2">{{ $typeNames[$type] ?? $type }}</h6>
                                        <div class="h4 mb-1 text-primary">{{ number_format($stats['amount']) }}원</div>
                                        <small class="text-muted">{{ $stats['count'] }}건</small>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Period Selector -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">기간별 조회</h5>
                        <div class="btn-group" role="group">
                            <a href="?period=today" class="btn btn-sm {{ $period === 'today' ? 'btn-primary' : 'btn-outline-primary' }}">오늘</a>
                            <a href="?period=this_week" class="btn btn-sm {{ $period === 'this_week' ? 'btn-primary' : 'btn-outline-primary' }}">이번주</a>
                            <a href="?period=this_month" class="btn btn-sm {{ $period === 'this_month' ? 'btn-primary' : 'btn-outline-primary' }}">이번달</a>
                            <a href="?period=this_quarter" class="btn btn-sm {{ $period === 'this_quarter' ? 'btn-primary' : 'btn-outline-primary' }}">이번분기</a>
                            <a href="?period=this_year" class="btn btn-sm {{ $period === 'this_year' ? 'btn-primary' : 'btn-outline-primary' }}">올해</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Commission Details Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">커미션 상세 내역</h5>
                    <div>
                        <span class="badge bg-info">{{ count($commissions) }}건</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>커미션 타입</th>
                                    <th>매출 정보</th>
                                    <th>수수료율</th>
                                    <th>커미션 금액</th>
                                    <th>세금</th>
                                    <th>순금액</th>
                                    <th>상태</th>
                                    <th>발생일시</th>
                                    <th>액션</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($commissions as $commission)
                                    <tr>
                                        <td>
                                            @php
                                                $typeNames = [
                                                    'direct_sales' => '직접 판매',
                                                    'indirect_referral' => '간접 추천',
                                                    'team_bonus' => '팀 보너스',
                                                    'management_bonus' => '관리 보너스',
                                                    'override_bonus' => '오버라이드 보너스',
                                                    'recruitment_bonus' => '모집 보너스',
                                                    'rank_bonus' => '등급 보너스'
                                                ];
                                                $typeBadges = [
                                                    'direct_sales' => 'primary',
                                                    'indirect_referral' => 'info',
                                                    'team_bonus' => 'success',
                                                    'management_bonus' => 'warning',
                                                    'override_bonus' => 'danger',
                                                    'recruitment_bonus' => 'secondary',
                                                    'rank_bonus' => 'dark'
                                                ];
                                            @endphp
                                            <span class="badge bg-{{ $typeBadges[$commission->commission_type] ?? 'secondary' }}">
                                                {{ $typeNames[$commission->commission_type] ?? $commission->commission_type }}
                                            </span>
                                            @if($commission->level_difference > 0)
                                                <br><small class="text-muted">{{ $commission->level_difference }}단계 상위</small>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-bold">주문 #{{ $commission->order_id }}</div>
                                            <small class="text-muted">원금액: {{ number_format($commission->original_amount) }}원</small>
                                            @if($commission->sourcePartner && $commission->sourcePartner->id !== $commission->partner_id)
                                                <br><small class="text-muted">소스: {{ $commission->sourcePartner->name }}</small>
                                            @endif
                                        </td>
                                        <td class="fw-bold">{{ $commission->commission_rate }}%</td>
                                        <td class="fw-bold text-primary">{{ number_format($commission->commission_amount) }}원</td>
                                        <td class="text-muted">{{ number_format($commission->tax_amount ?? 0) }}원</td>
                                        <td class="fw-bold text-success">{{ number_format($commission->net_amount ?? $commission->commission_amount) }}원</td>
                                        <td>
                                            @php
                                                $statusColors = [
                                                    'pending' => 'warning',
                                                    'calculated' => 'info',
                                                    'paid' => 'success',
                                                    'cancelled' => 'danger'
                                                ];
                                                $statusLabels = [
                                                    'pending' => '대기',
                                                    'calculated' => '계산완료',
                                                    'paid' => '지급완료',
                                                    'cancelled' => '취소'
                                                ];
                                            @endphp
                                            <span class="badge bg-{{ $statusColors[$commission->status] ?? 'secondary' }}">
                                                {{ $statusLabels[$commission->status] ?? $commission->status }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-bold">{{ $commission->earned_at->format('Y-m-d') }}</div>
                                            <small class="text-muted">{{ $commission->earned_at->format('H:i:s') }}</small>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.partner.network.commission.show', $commission->id) }}"
                                               class="btn btn-outline-primary btn-sm">
                                                <i class="fe fe-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <i class="fe fe-dollar-sign display-4 text-muted"></i>
                                            <h5 class="mt-3">해당 기간에 커미션 데이터가 없습니다</h5>
                                            <p class="text-muted">다른 기간을 선택해보세요.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if(count($commissions) > 0)
                    <div class="card-footer">
                        <div class="row align-items-center">
                            <div class="col">
                                <small class="text-muted">총 {{ count($commissions) }}건의 커미션 내역</small>
                            </div>
                            <div class="col-auto">
                                <small class="text-muted">
                                    총액: <span class="fw-bold text-primary">{{ number_format($commissionStats['total_earned'] ?? 0) }}원</span>
                                </small>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Additional JavaScript can be added here if needed
</script>
@endpush