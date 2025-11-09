@extends('jiny-partner::layouts.admin.sidebar')

@section('title', $title)

@section('content')
<div class="container-fluid">
    <!-- 헤더 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">{{ $title }}</h2>
                    <p class="text-muted mb-0">파트너 시스템의 전체 현황을 한눈에 확인하세요</p>
                </div>
                <div>
                    <button class="btn btn-outline-primary me-2" onclick="refreshDashboard()">
                        <i class="fe fe-refresh-cw me-2"></i>새로고침
                    </button>
                    <a href="{{ route('admin.partner.users.create') }}" class="btn btn-primary">
                        <i class="fe fe-plus me-2"></i>새 파트너 등록
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 주요 지표 카드 -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-gradient rounded-circle p-3 stat-circle">
                                <i class="fe fe-users text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 text-muted">전체 파트너</h6>
                            <h3 class="mb-0 fw-bold">{{ number_format($statistics['total_partners']) }}</h3>
                            <small class="text-success">
                                <i class="fe fe-trending-up"></i>
                                활성: {{ number_format($statistics['active_partners']) }}명
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <a href="{{ route('admin.partner.applications.index') }}?status=submitted,reviewing"
               class="text-decoration-none">
                <div class="card border-0 shadow-sm clickable-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-warning bg-gradient rounded-circle p-3 stat-circle">
                                    <i class="fe fe-clock text-white"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0 text-muted">대기 신청서</h6>
                                <h3 class="mb-0 fw-bold text-dark">{{ number_format($statistics['pending_applications']) }}</h3>
                                <small class="text-warning">
                                    <i class="fe fe-alert-circle"></i>
                                    승인 대기중
                                    <i class="fe fe-arrow-right ms-1"></i>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-gradient rounded-circle p-3 stat-circle">
                                <i class="fe fe-dollar-sign text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 text-muted">월 커미션</h6>
                            <h3 class="mb-0 fw-bold">{{ number_format($statistics['monthly_commissions']) }}원</h3>
                            <small class="text-success">
                                <i class="fe fe-trending-up"></i>
                                이번 달
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-gradient rounded-circle p-3 stat-circle">
                                <i class="fe fe-bar-chart-2 text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 text-muted">총 매출</h6>
                            <h3 class="mb-0 fw-bold">{{ number_format($statistics['total_sales']) }}원</h3>
                            <small class="text-info">
                                <i class="fe fe-star"></i>
                                평점: {{ number_format($statistics['average_rating'], 1) }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 파트너 신청 현황 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">파트너 신청 현황</h5>
                    <p class="text-muted small mb-0">실시간 신청서 상태별 통계 및 처리 현황</p>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- 총 신청서 수 -->
                        <div class="col-md-2 col-6 mb-3">
                            <a href="{{ route('admin.partner.applications.index') }}" class="text-decoration-none">
                                <div class="text-center p-2 rounded clickable-stat-card">
                                    <div class="h2 mb-1 fw-bold text-primary">{{ number_format($statistics['total_applications'] ?? 0) }}</div>
                                    <div class="small text-muted">총 신청서 <i class="fe fe-arrow-right"></i></div>
                                </div>
                            </a>
                        </div>
                        <!-- 제출 완료 -->
                        <div class="col-md-2 col-6 mb-3">
                            <a href="{{ route('admin.partner.applications.index') }}?status=submitted" class="text-decoration-none">
                                <div class="text-center p-2 rounded clickable-stat-card">
                                    <div class="h2 mb-1 fw-bold text-info">{{ number_format($statistics['submitted_applications'] ?? 0) }}</div>
                                    <div class="small text-muted">제출 완료 <i class="fe fe-arrow-right"></i></div>
                                </div>
                            </a>
                        </div>
                        <!-- 검토중 -->
                        <div class="col-md-2 col-6 mb-3">
                            <a href="{{ route('admin.partner.applications.index') }}?status=reviewing" class="text-decoration-none">
                                <div class="text-center p-2 rounded clickable-stat-card">
                                    <div class="h2 mb-1 fw-bold text-warning">{{ number_format($statistics['reviewing_applications'] ?? 0) }}</div>
                                    <div class="small text-muted">검토중 <i class="fe fe-arrow-right"></i></div>
                                </div>
                            </a>
                        </div>
                        <!-- 면접예정 -->
                        <div class="col-md-2 col-6 mb-3">
                            <a href="{{ route('admin.partner.applications.index') }}?status=interview" class="text-decoration-none">
                                <div class="text-center p-2 rounded clickable-stat-card">
                                    <div class="h2 mb-1 fw-bold text-purple">{{ number_format($statistics['interview_applications'] ?? 0) }}</div>
                                    <div class="small text-muted">면접예정 <i class="fe fe-arrow-right"></i></div>
                                </div>
                            </a>
                        </div>
                        <!-- 승인됨 -->
                        <div class="col-md-2 col-6 mb-3">
                            <a href="{{ route('admin.partner.applications.index') }}?status=approved" class="text-decoration-none">
                                <div class="text-center p-2 rounded clickable-stat-card">
                                    <div class="h2 mb-1 fw-bold text-success">{{ number_format($statistics['approved_applications'] ?? 0) }}</div>
                                    <div class="small text-muted">승인됨 <i class="fe fe-arrow-right"></i></div>
                                </div>
                            </a>
                        </div>
                        <!-- 반려됨 -->
                        <div class="col-md-2 col-6 mb-3">
                            <a href="{{ route('admin.partner.applications.index') }}?status=rejected" class="text-decoration-none">
                                <div class="text-center p-2 rounded clickable-stat-card">
                                    <div class="h2 mb-1 fw-bold text-danger">{{ number_format($statistics['rejected_applications'] ?? 0) }}</div>
                                    <div class="small text-muted">반려됨 <i class="fe fe-arrow-right"></i></div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- 승인률 및 처리 속도 -->
                    <hr class="my-3">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">승인률</span>
                                <span class="fw-bold text-success">
                                    {{ number_format(($statistics['approved_applications'] ?? 0) / max($statistics['total_applications'] ?? 1, 1) * 100, 1) }}%
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">평균 처리 시간</span>
                                <span class="fw-bold">{{ $statistics['avg_processing_days'] ?? '0' }}일</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">이번달 신청</span>
                                <span class="fw-bold text-primary">{{ number_format($statistics['this_month_applications'] ?? 0) }}건</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 월별 성장 추이 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">월별 성장 추이</h5>
                </div>
                <div class="card-body">
                    <div style="height: 300px; position: relative;">
                        <canvas id="growthChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 상위 성과자 및 처리 대기중 신청서 -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <!-- 상위 성과자 -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">상위 성과자 TOP 10</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">순위</th>
                                    <th>파트너</th>
                                    <th>등급/타입</th>
                                    <th>매출/평점</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($performanceData['top_performers'] as $index => $performer)
                                <tr>
                                    <td>
                                        <div class="fw-bold text-primary">#{{ $index + 1 }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-bold">{{ $performer->name ?? '파트너 #' . $performer->id }}</div>
                                        <small class="text-muted">{{ $performer->email ?? '' }}</small>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <span class="badge bg-light text-dark">{{ $performer->tier->tier_name ?? '미지정' }}</span>
                                            <br>
                                            <span class="badge bg-light text-dark">{{ $performer->type->type_name ?? '미지정' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold">{{ number_format($performer->total_sales) }}원</div>
                                        <small class="text-muted">월: {{ number_format($performer->monthly_sales) }}원</small>
                                        <div class="d-flex align-items-center mt-2">
                                            <div class="rating-stars me-2">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <i class="fe fe-star{{ $performer->average_rating >= $i ? ' text-warning' : ' text-muted' }}"></i>
                                                @endfor
                                            </div>
                                            <span class="small">{{ number_format($performer->average_rating, 1) }}</span>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">
                                        <i class="fe fe-bar-chart-2 fe-2x mb-2"></i>
                                        <p>성과 데이터가 없습니다.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <!-- 처리 대기중 신청서 -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fe fe-clock text-warning me-2"></i>처리 대기중 신청서
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-warning">{{ count($recentActivities['pending_applications'] ?? []) }}건</span>
                        <a href="{{ route('admin.partner.applications.index') }}?status=submitted,reviewing,interview"
                           class="btn btn-sm btn-outline-primary">
                            <i class="fe fe-external-link"></i> 전체보기
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    @forelse($recentActivities['pending_applications'] ?? [] as $application)
                        @php
                            $isUrgent = $application->created_at <= now()->subDays(3);
                            $daysWaiting = $application->created_at->diffInDays(now());
                        @endphp
                        <div class="d-flex align-items-center p-3 border-bottom application-row clickable-row
                                    @if($isUrgent) bg-warning bg-opacity-10 @endif"
                             onclick="window.location.href='{{ route('admin.partner.applications.show', $application->id) }}'"
                             style="cursor: pointer; transition: all 0.2s ease;">
                            <div class="flex-shrink-0 me-3">
                                <div class="rounded-circle p-2
                                    @if($isUrgent) bg-warning
                                    @else
                                        @switch($application->application_status)
                                            @case('submitted') bg-info @break
                                            @case('reviewing') bg-primary @break
                                            @case('interview') bg-purple @break
                                            @default bg-secondary
                                        @endswitch
                                    @endif">
                                    @if($isUrgent)
                                        <i class="fe fe-alert-triangle text-white"></i>
                                    @else
                                        @switch($application->application_status)
                                            @case('submitted')
                                                <i class="fe fe-send text-white"></i>
                                                @break
                                            @case('reviewing')
                                                <i class="fe fe-eye text-white"></i>
                                                @break
                                            @case('interview')
                                                <i class="fe fe-video text-white"></i>
                                                @break
                                            @default
                                                <i class="fe fe-file-text text-white"></i>
                                        @endswitch
                                    @endif
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1 fw-bold">
                                            {{ $application->personal_info['name'] ?? '신청자 #' . $application->id }}
                                            @if($isUrgent)
                                                <span class="badge bg-warning ms-2">우선처리</span>
                                            @endif
                                        </h6>
                                        <div class="text-muted small mb-1">
                                            <i class="fe fe-hash"></i> 신청번호: {{ $application->id }} •
                                            <i class="fe fe-calendar"></i> {{ $application->created_at->format('m/d H:i') }}
                                            @if(isset($application->personal_info['phone']))
                                                • <i class="fe fe-phone"></i> {{ $application->personal_info['phone'] }}
                                            @endif
                                        </div>
                                        <div class="text-muted small">
                                            <i class="fe fe-clock"></i>
                                            {{ $application->created_at->diffForHumans() }}
                                            @if($isUrgent)
                                                <span class="text-warning ms-1">
                                                    <i class="fe fe-alert-circle"></i> {{ $daysWaiting }}일 경과
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="mb-2">
                                            <span class="badge
                                                @switch($application->application_status)
                                                    @case('submitted') bg-info @break
                                                    @case('reviewing') bg-warning @break
                                                    @case('interview') bg-purple @break
                                                    @default bg-secondary
                                                @endswitch">
                                                @switch($application->application_status)
                                                    @case('submitted') 제출완료 @break
                                                    @case('reviewing') 검토중 @break
                                                    @case('interview') 면접예정 @break
                                                    @default 알수없음
                                                @endswitch
                                            </span>
                                        </div>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-xs btn-outline-success"
                                                    onclick="event.stopPropagation(); window.location.href='{{ route('admin.partner.applications.show', $application->id) }}'"
                                                    title="바로 승인">
                                                <i class="fe fe-check"></i>
                                            </button>
                                            <button class="btn btn-xs btn-outline-primary"
                                                    onclick="event.stopPropagation(); window.location.href='{{ route('admin.partner.applications.show', $application->id) }}'"
                                                    title="상세 검토">
                                                <i class="fe fe-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-5">
                            <i class="fe fe-check-circle text-success fe-3x mb-3"></i>
                            <h6 class="mb-2">처리할 신청서가 없습니다</h6>
                            <p class="small mb-0">모든 파트너 신청서가 처리되었습니다.</p>
                        </div>
                    @endforelse

                    @if(count($recentActivities['pending_applications'] ?? []) >= 15)
                        <div class="text-center p-3 border-top bg-light">
                            <a href="{{ route('admin.partner.applications.index') }}?status=submitted,reviewing,interview"
                               class="btn btn-outline-primary">
                                <i class="fe fe-arrow-right me-1"></i>
                                모든 처리 대기중 신청서 보기
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 타입별 분포 및 등급별 분포 -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <!-- 타입별 분포 -->
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">타입별 분포</h5>
                </div>
                <div class="card-body d-flex flex-column">
                    <div class="flex-grow-1">
                        @foreach($typeDistribution as $type)
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex align-items-center">
                                <div class="icon-shape icon-sm rounded-circle text-white me-3"
                                     style="background-color: {{ $type['color'] }};">
                                    <i class="fe fe-users"></i>
                                </div>
                                <div>
                                    <div class="fw-bold">{{ $type['type_name'] }}</div>
                                    <small class="text-muted">{{ $type['type_code'] }}</small>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold">{{ $type['count'] }}명</div>
                                <small class="text-muted">
                                    {{ number_format(($type['count'] / max($statistics['total_partners'], 1)) * 100, 1) }}%
                                </small>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @if(count($typeDistribution) === 0)
                        <div class="text-center text-muted py-5 flex-grow-1 d-flex align-items-center justify-content-center">
                            <div>
                                <i class="fe fe-pie-chart fe-3x mb-3"></i>
                                <p>타입 분포 데이터가 없습니다.</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <!-- 등급별 분포 -->
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">등급별 분포</h5>
                </div>
                <div class="card-body d-flex flex-column">
                    <div style="height: 200px; position: relative;">
                        <canvas id="tierChart"></canvas>
                    </div>
                    <div class="mt-3 flex-grow-1">
                        @foreach($tierDistribution as $tier)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center">
                                <div class="badge me-2" style="background-color: {{ $tier['color'] }}; width: 12px; height: 12px;"></div>
                                <span>{{ $tier['tier_name'] }}</span>
                            </div>
                            <span class="fw-bold">{{ $tier['count'] }}명</span>
                        </div>
                        @endforeach
                        @if(count($tierDistribution) === 0)
                            <div class="text-center text-muted py-3">
                                <i class="fe fe-pie-chart fe-2x mb-2"></i>
                                <p>등급 분포 데이터가 없습니다.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 종합 통계 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fe fe-trending-up text-primary me-2"></i>종합 통계
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 col-6 mb-3">
                            <div class="text-center p-3 rounded bg-primary bg-opacity-10">
                                <h3 class="mb-1 fw-bold text-primary">{{ number_format($statistics['total_sales']) }}원</h3>
                                <p class="mb-0 text-muted small">전체 매출</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="text-center p-3 rounded bg-success bg-opacity-10">
                                <h3 class="mb-1 fw-bold text-success">{{ number_format($statistics['monthly_commissions']) }}원</h3>
                                <p class="mb-0 text-muted small">이번달 커미션</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="text-center p-3 rounded bg-info bg-opacity-10">
                                <h3 class="mb-1 fw-bold text-info">{{ number_format($statistics['average_rating'], 1) }}</h3>
                                <p class="mb-0 text-muted small">평균 평점</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="text-center p-3 rounded bg-warning bg-opacity-10">
                                <h3 class="mb-1 fw-bold text-warning">{{ $statistics['avg_processing_days'] ?? '0' }}일</h3>
                                <p class="mb-0 text-muted small">평균 처리시간</p>
                            </div>
                        </div>
                    </div>

                    <!-- 추가 상세 통계 -->
                    <hr class="my-4">
                    <div class="row">
                        <div class="col-md-4">
                            <h6 class="fw-semibold mb-3">파트너 현황</h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span>전체 파트너</span>
                                <span class="fw-bold">{{ number_format($statistics['total_partners']) }}명</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>활성 파트너</span>
                                <span class="fw-bold text-success">{{ number_format($statistics['active_partners']) }}명</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>활성률</span>
                                <span class="fw-bold">{{ number_format(($statistics['active_partners'] / max($statistics['total_partners'], 1)) * 100, 1) }}%</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h6 class="fw-semibold mb-3">신청 처리</h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span>전체 신청서</span>
                                <span class="fw-bold">{{ number_format($statistics['total_applications']) }}건</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>승인률</span>
                                <span class="fw-bold text-success">{{ number_format(($statistics['approved_applications'] / max($statistics['total_applications'], 1)) * 100, 1) }}%</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>이번달 신청</span>
                                <span class="fw-bold text-info">{{ number_format($statistics['this_month_applications']) }}건</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h6 class="fw-semibold mb-3">등급 및 타입</h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span>활성 등급</span>
                                <span class="fw-bold">{{ number_format($statistics['total_tiers']) }}개</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>활성 타입</span>
                                <span class="fw-bold">{{ number_format($statistics['total_types']) }}개</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>대기 승인</span>
                                <span class="fw-bold text-warning">{{ number_format($statistics['pending_applications']) }}건</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- 빠른 액세스 메뉴 -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">빠른 액세스</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('admin.partner.users.index') }}" class="btn btn-outline-primary w-100">
                                <i class="fe fe-users mb-2"></i>
                                <br>파트너 관리
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('admin.partner.applications.index') }}" class="btn btn-outline-warning w-100 position-relative">
                                <i class="fe fe-file-text mb-2"></i>
                                <br>신청서 관리
                                @if(($statistics['pending_applications'] ?? 0) > 0)
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        {{ $statistics['pending_applications'] }}
                                        <span class="visually-hidden">미승인 신청서</span>
                                    </span>
                                @endif
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('admin.partner.tiers.index') }}" class="btn btn-outline-info w-100">
                                <i class="fe fe-layers mb-2"></i>
                                <br>등급 관리
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('admin.partner.type.index') }}" class="btn btn-outline-success w-100">
                                <i class="fe fe-tag mb-2"></i>
                                <br>타입 관리
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* 통계 카드 원형 아이콘 스타일 */
.stat-circle {
    width: 48px !important;
    height: 48px !important;
    min-width: 48px;
    min-height: 48px;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-shrink: 0 !important;
}

.stat-circle i {
    font-size: 20px;
}

/* 카드 그림자 효과 */
.shadow-sm {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
}

/* 평점 별표 스타일 */
.rating-stars i {
    font-size: 14px;
}

/* 빠른 액세스 버튼 */
.btn-outline-primary i,
.btn-outline-warning i,
.btn-outline-info i,
.btn-outline-success i {
    font-size: 24px;
}

/* 추가 색상 정의 */
.text-purple {
    color: #6f42c1 !important;
}

.bg-purple {
    background-color: #6f42c1 !important;
}

.badge.bg-purple {
    background-color: #6f42c1 !important;
    color: white;
}

/* 신청 현황 통계 카드 스타일 */
.application-stats .h2 {
    font-size: 2.5rem;
}

/* 우선 처리 카드 스타일 */
.card .card-header.text-warning h6 {
    color: #ffc107 !important;
}

/* 상태별 아이콘 배경 */
.bg-info { background-color: #0dcaf0 !important; }
.bg-warning { background-color: #ffc107 !important; }
.bg-success { background-color: #198754 !important; }
.bg-danger { background-color: #dc3545 !important; }
.bg-secondary { background-color: #6c757d !important; }

/* 클릭 가능한 카드 스타일 */
.clickable-card {
    transition: all 0.3s ease;
    cursor: pointer;
}

.clickable-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.clickable-stat-card {
    transition: all 0.2s ease;
    cursor: pointer;
}

.clickable-stat-card:hover {
    background-color: #f8f9fa !important;
    transform: translateY(-1px);
}

/* 신청서 항목 hover 효과 */
.application-item:hover {
    background-color: #f8f9fa !important;
    transform: translateX(2px);
}

.hover-bg-light:hover {
    background-color: #f8f9fa !important;
}

/* 미승인 신청서 항목 스타일 */
.pending-item {
    transition: all 0.2s ease;
}

.pending-item:hover {
    transform: translateX(3px);
    box-shadow: 0 2px 8px rgba(255, 193, 7, 0.2);
}

/* 버튼 그룹 개선 */
.btn-group .btn-xs {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

/* 화살표 아이콘 애니메이션 */
.fe-arrow-right {
    transition: transform 0.2s ease;
}

.clickable-card:hover .fe-arrow-right,
.clickable-stat-card:hover .fe-arrow-right,
.application-item:hover .fe-arrow-right {
    transform: translateX(3px);
}

/* 경과 일수 강조 */
.text-warning i.fe-alert-circle {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

/* 빈 상태 메시지 개선 */
.text-center.text-muted.py-4 {
    border: 2px dashed #e9ecef;
    border-radius: 8px;
    margin: 1rem 0;
}

/* 미승인 신청서 목록 스타일 */
.application-row {
    transition: all 0.3s ease;
}

.application-row:hover {
    background-color: #f8f9fa !important;
    transform: translateX(5px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.clickable-row {
    cursor: pointer;
}

.clickable-row:last-child {
    border-bottom: none !important;
}

/* 신청서 상태 배지 개선 */
.badge.bg-info { background-color: #0dcaf0 !important; }
.badge.bg-warning { background-color: #ffc107 !important; color: #000; }
.badge.bg-purple { background-color: #6f42c1 !important; }
.badge.bg-success { background-color: #198754 !important; }
.badge.bg-danger { background-color: #dc3545 !important; }

/* 장기 대기 강조 */
.text-warning i.fe-alert-triangle {
    animation: pulse-warning 2s infinite;
}

@keyframes pulse-warning {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

/* 빠른 액션 버튼 그룹 */
.application-row .btn-group .btn {
    opacity: 0.7;
    transition: opacity 0.2s ease;
}

.application-row:hover .btn-group .btn {
    opacity: 1;
}

/* 미승인 목록 헤더 */
.card-header h5 i.fe-users {
    color: #6f42c1;
}

/* 전체 관리 버튼 개선 */
.btn-outline-primary i.fe-external-link {
    transition: transform 0.2s ease;
}

.btn-outline-primary:hover i.fe-external-link {
    transform: rotate(45deg);
}

/* 신청서 카운트 배지 */
.text-muted.small {
    font-weight: 500;
}

</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// 대시보드 새로고침
function refreshDashboard() {
    location.reload();
}

// 월별 성장 추이 차트
const growthCtx = document.getElementById('growthChart').getContext('2d');
const growthChart = new Chart(growthCtx, {
    type: 'line',
    data: {
        labels: @json($monthlyGrowth->pluck('label')),
        datasets: [{
            label: '파트너 수',
            data: @json($monthlyGrowth->pluck('partners')),
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            tension: 0.4,
            borderWidth: 2,
            pointBackgroundColor: '#667eea',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7,
            fill: true
        }, {
            label: '커미션 (만원)',
            data: @json($monthlyGrowth->pluck('commissions')->map(function($item) { return $item / 10000; })),
            borderColor: '#f093fb',
            backgroundColor: 'rgba(240, 147, 251, 0.1)',
            tension: 0.4,
            borderWidth: 2,
            pointBackgroundColor: '#f093fb',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            intersect: false,
            mode: 'index'
        },
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: {
                    usePointStyle: true,
                    padding: 20,
                    font: {
                        size: 12
                    }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: '#fff',
                bodyColor: '#fff',
                borderColor: '#666',
                borderWidth: 1,
                cornerRadius: 6,
                displayColors: true,
                callbacks: {
                    title: function(context) {
                        return context[0].label;
                    },
                    label: function(context) {
                        const label = context.dataset.label || '';
                        if (label === '파트너 수') {
                            return `${label}: ${context.parsed.y}명`;
                        } else {
                            return `${label}: ${context.parsed.y}만원`;
                        }
                    }
                }
            }
        },
        scales: {
            x: {
                display: true,
                grid: {
                    display: false
                },
                ticks: {
                    font: {
                        size: 11
                    }
                }
            },
            y: {
                display: true,
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.1)',
                    borderDash: [5, 5]
                },
                ticks: {
                    font: {
                        size: 11
                    }
                }
            }
        },
        elements: {
            point: {
                hoverRadius: 8
            }
        },
        animation: {
            duration: 1500,
            easing: 'easeInOutQuart'
        }
    }
});

// 등급별 분포 도넛 차트
const tierCtx = document.getElementById('tierChart').getContext('2d');
const tierChart = new Chart(tierCtx, {
    type: 'doughnut',
    data: {
        labels: @json($tierDistribution->pluck('tier_name')),
        datasets: [{
            data: @json($tierDistribution->pluck('count')),
            backgroundColor: @json($tierDistribution->pluck('color')),
            borderWidth: 2,
            borderColor: '#fff',
            hoverBorderWidth: 3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            intersect: false
        },
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: '#fff',
                bodyColor: '#fff',
                borderColor: '#666',
                borderWidth: 1,
                cornerRadius: 6,
                displayColors: true,
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(1);
                        return `${label}: ${value}명 (${percentage}%)`;
                    }
                }
            }
        },
        cutout: '60%',
        elements: {
            arc: {
                borderWidth: 2,
                hoverBorderWidth: 4
            }
        },
        animation: {
            animateRotate: true,
            animateScale: true,
            duration: 1000
        }
    }
});
</script>
@endpush