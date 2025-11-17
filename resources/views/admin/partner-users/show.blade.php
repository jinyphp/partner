@extends('jiny-partner::layouts.admin.sidebar')

@section('title', $title . ' 상세보기')

@section('content')
    <div class="container-fluid">
        <!-- 성공 메시지 표시 -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- 헤더 -->
        <section class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1">{{ $title }} 상세보기</h2>
                        <p class="text-muted mb-0">{{ $item->name }}님의 파트너 정보</p>
                    </div>
                    <div>
                        <a href="{{ route('admin.' . $routePrefix . '.index') }}" class="btn btn-outline-secondary me-2">
                            <i class="fe fe-arrow-left me-2"></i>목록으로
                        </a>
                        <a href="{{ route('admin.' . $routePrefix . '.edit', $item->id) }}" class="btn btn-primary">
                            <i class="fe fe-edit me-2"></i>수정
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <div class="row">
            <!-- 왼쪽 컬럼: 기본 정보 -->
            <div class="col-lg-8">
                <!-- 기본 정보 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">기본 정보</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="120" class="text-muted">이름:</td>
                                        <td><strong>{{ $item->name }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">이메일:</td>
                                        <td>{{ $item->email }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">사용자 ID:</td>
                                        <td>{{ $item->user_id }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">사용자 테이블:</td>
                                        <td><code class="bg-light px-2 py-1 rounded">{{ $item->user_table }}</code></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">UUID:</td>
                                        <td>
                                            @if ($item->user_uuid)
                                                <code class="bg-light px-2 py-1 rounded">{{ $item->user_uuid }}</code>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">샤드 번호:</td>
                                        <td>
                                            <span class="badge bg-info">{{ $item->shard_number ?? 0 }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">파트너 코드:</td>
                                        <td>
                                            @includeIf('jiny-partner::admin.partner-users.partials.code')

                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="120" class="text-muted">등급:</td>
                                        <td>
                                            <span
                                                class="badge bg-info fs-6">{{ $item->partnerTier->tier_name ?? 'N/A' }}</span>
                                            @if ($item->partnerTier)
                                                <small
                                                    class="text-muted d-block">{{ $item->partnerTier->tier_code }}</small>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">파트너 타입:</td>
                                        <td>
                                            @if ($item->partnerType)
                                                <span
                                                    class="badge bg-primary fs-6">{{ $item->partnerType->type_name }}</span>
                                                <small
                                                    class="text-muted d-block">{{ $item->partnerType->type_code }}</small>
                                            @else
                                                <span class="text-muted">미설정</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">상태:</td>
                                        <td>
                                            @if ($item->status === 'active')
                                                <span class="badge bg-success fs-6">승인</span>
                                            @elseif($item->status === 'pending')
                                                <span class="badge bg-warning fs-6">대기</span>
                                            @elseif($item->status === 'suspended')
                                                <span class="badge bg-danger fs-6">정지</span>
                                            @else
                                                <span class="badge bg-secondary fs-6">알 수 없음</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">가입일:</td>
                                        <td>{{ $item->partner_joined_at->format('Y-m-d') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">등급 할당일:</td>
                                        <td>{{ $item->tier_assigned_at->format('Y-m-d') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">마지막 활동:</td>
                                        <td>
                                            @if ($item->last_activity_at)
                                                <time datetime="{{ $item->last_activity_at->toISOString() }}"
                                                    title="{{ $item->last_activity_at->format('Y-m-d H:i:s') }}">
                                                    {{ $item->last_activity_at->diffForHumans() }}
                                                </time>
                                                <small
                                                    class="text-muted d-block">{{ $item->last_activity_at->format('Y-m-d H:i:s') }}</small>
                                            @else
                                                <span class="text-muted">기록 없음</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @if ($item->last_performance_review_at)
                                        <tr>
                                            <td class="text-muted">마지막 성과 평가:</td>
                                            <td>
                                                <time datetime="{{ $item->last_performance_review_at->toISOString() }}"
                                                    title="{{ $item->last_performance_review_at->format('Y-m-d H:i:s') }}">
                                                    {{ $item->last_performance_review_at->diffForHumans() }}
                                                </time>
                                                <small
                                                    class="text-muted d-block">{{ $item->last_performance_review_at->format('Y-m-d H:i:s') }}</small>
                                            </td>
                                        </tr>
                                    @endif
                                </table>
                            </div>
                        </div>

                        @if ($item->status_reason)
                            <div class="alert alert-info mt-3">
                                <strong>상태 변경 사유:</strong> {{ $item->status_reason }}
                            </div>
                        @endif
                    </div>
                </div>

                <!-- 성과 정보 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">성과 정보</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="display-6 fw-bold text-primary">
                                        {{ number_format($item->total_completed_jobs) }}</div>
                                    <div class="text-muted">완료 작업</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="display-6 fw-bold text-warning">{{ $item->average_rating }}/5.0</div>
                                    <div class="text-muted">평균 평점</div>
                                    <div class="mt-1">
                                        @for ($i = 1; $i <= 5; $i++)
                                            @if ($i <= floor($item->average_rating))
                                                <i class="fe fe-star text-warning"></i>
                                            @elseif($i - 0.5 <= $item->average_rating)
                                                <i class="fe fe-star-half text-warning"></i>
                                            @else
                                                <i class="fe fe-star text-muted"></i>
                                            @endif
                                        @endfor
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="display-6 fw-bold text-info">{{ $item->punctuality_rate }}%</div>
                                    <div class="text-muted">시간 준수율</div>
                                    <div class="progress mt-2" style="height: 8px;">
                                        <div class="progress-bar bg-info" style="width: {{ $item->punctuality_rate }}%">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="display-6 fw-bold text-success">{{ $item->satisfaction_rate }}%</div>
                                    <div class="text-muted">만족도</div>
                                    <div class="progress mt-2" style="height: 8px;">
                                        <div class="progress-bar bg-success"
                                            style="width: {{ $item->satisfaction_rate }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if ($item->last_performance_review_at)
                            <div class="mt-3 pt-3 border-top">
                                <small class="text-muted">
                                    <i class="fe fe-calendar me-1"></i>
                                    마지막 성과 평가: {{ $item->last_performance_review_at->format('Y-m-d') }}
                                </small>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- 수수료 계산 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fe fe-dollar-sign me-2"></i>수수료 계산
                        </h5>
                    </div>
                    <div class="card-body">
                        @php
                            $breakdown = $item->getCommissionBreakdown(100000); // 10만원 기준으로 계산
                        @endphp

                        <div class="row">
                            <div class="col-md-4">
                                <div class="text-center p-3 bg-light rounded">
                                    <div class="display-6 fw-bold text-primary">
                                        {{ number_format($breakdown['total']['total_rate'], 1) }}%</div>
                                    <small class="text-muted">총 수수료율</small>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="row g-3">
                                    <!-- 파트너 타입 수수료 -->
                                    <div class="col-4">
                                        <div class="text-center p-3 border rounded">
                                            <div class="h4 fw-bold text-info mb-1">
                                                {{ number_format($breakdown['partner_type']['rate'], 1) }}%</div>
                                            <div class="small text-muted">파트너 타입</div>
                                            <div class="small fw-bold text-info">
                                                {{ $item->partnerType->type_name ?? 'N/A' }}
                                            </div>
                                            @if ($breakdown['partner_type']['amount'] > 0)
                                                <div class="small text-success">
                                                    +{{ number_format($breakdown['partner_type']['amount']) }}원
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- 파트너 등급 수수료 -->
                                    <div class="col-4">
                                        <div class="text-center p-3 border rounded">
                                            <div class="h4 fw-bold text-warning mb-1">
                                                {{ number_format($breakdown['partner_tier']['rate'], 1) }}%</div>
                                            <div class="small text-muted">파트너 등급</div>
                                            <div class="small fw-bold text-warning">
                                                {{ $item->partnerTier->tier_name ?? 'N/A' }}
                                            </div>
                                            @if ($breakdown['partner_tier']['amount'] > 0)
                                                <div class="small text-success">
                                                    +{{ number_format($breakdown['partner_tier']['amount']) }}원
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- 개별 수수료 -->
                                    <div class="col-4">
                                        <div class="text-center p-3 border rounded">
                                            <div class="h4 fw-bold text-success mb-1">
                                                @if ($breakdown['individual']['type'] === 'percentage')
                                                    {{ number_format($breakdown['individual']['rate'], 1) }}%
                                                @else
                                                    {{ number_format($breakdown['individual']['amount']) }}원
                                                @endif
                                            </div>
                                            <div class="small text-muted">개별 수수료</div>
                                            <div class="small fw-bold text-success">
                                                {{ $breakdown['individual']['type'] === 'percentage' ? '퍼센트' : '고정금액' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 수수료 계산 예시 -->
                        <div class="mt-4 p-3 bg-light rounded">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-primary mb-3">
                                        <i class="fe fe-calculator me-1"></i>수수료 계산 예시 (100,000원 기준)
                                    </h6>
                                    <div class="small">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>파트너 타입
                                                ({{ number_format($breakdown['partner_type']['rate'], 1) }}%):</span>
                                            <span
                                                class="fw-bold">{{ number_format($breakdown['partner_type']['calculated_amount']) }}원</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>파트너 등급
                                                ({{ number_format($breakdown['partner_tier']['rate'], 1) }}%):</span>
                                            <span
                                                class="fw-bold">{{ number_format($breakdown['partner_tier']['calculated_amount']) }}원</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>개별 수수료:</span>
                                            <span
                                                class="fw-bold">{{ number_format($breakdown['individual']['calculated_amount']) }}원</span>
                                        </div>
                                        @if ($breakdown['total']['total_fixed_amount'] > 0)
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>고정 수수료:</span>
                                                <span
                                                    class="fw-bold">{{ number_format($breakdown['total']['total_fixed_amount']) }}원</span>
                                            </div>
                                        @endif
                                        <hr class="my-2">
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-bold">총 수수료:</span>
                                            <span
                                                class="fw-bold text-primary">{{ number_format($breakdown['total']['total_commission']) }}원</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    @if ($item->commission_notes)
                                        <h6 class="text-muted mb-3">
                                            <i class="fe fe-message-circle me-1"></i>수수료 설정 메모
                                        </h6>
                                        <div class="small text-muted bg-white p-2 rounded border">
                                            {{ $item->commission_notes }}
                                        </div>
                                    @endif

                                    <!-- 실제 수수료 실적 (있는 경우) -->
                                    @if ($item->earned_commissions > 0)
                                        <h6 class="text-success mb-3 mt-3">
                                            <i class="fe fe-trending-up me-1"></i>실제 수수료 실적
                                        </h6>
                                        <div class="small">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>총 획득 커미션:</span>
                                                <span
                                                    class="fw-bold text-success">{{ number_format($item->earned_commissions) }}원</span>
                                            </div>
                                            @if ($item->monthly_sales > 0)
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span>실효 수수료율:</span>
                                                    <span
                                                        class="fw-bold">{{ number_format(($item->earned_commissions / $item->monthly_sales) * 100, 1) }}%</span>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 동적 목표 및 성과 분석 -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">🎯 동적 목표 & 성과 분석</h5>
                            <small class="text-muted">개인별 맞춤 목표와 실시간 성과 추적 시스템</small>
                            <div class="small text-info mt-1">
                                <i class="fe fe-info me-1"></i>
                                타입 기준치 × 등급 승수 × 개인 조정으로 생성된 동적 목표 기반 성과 관리
                            </div>
                        </div>
                        <a href="{{ route('admin.partner.targets.index', ['partner_id' => $item->id]) }}"
                            class="btn btn-outline-primary btn-sm">
                            <i class="fe fe-external-link me-1"></i>목표 관리
                        </a>
                    </div>
                    <div class="card-body">
                        <!-- 현재 목표 및 달성 현황 -->
                        @php
                            // 현재 활성화된 월별 동적 목표 가져오기
                            $currentTarget = $item
                                ->dynamicTargets()
                                ->where('target_period_type', 'monthly')
                                ->where('target_year', date('Y'))
                                ->where('target_month', date('n'))
                                ->where('status', 'active')
                                ->first();
                        @endphp

                        <div class="row mb-4">
                            <div class="col-12 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-primary mb-2">🎯 이번 달 목표 및 달성률</h6>
                                        <p class="text-muted small mb-0">
                                            @if ($currentTarget)
                                                동적 목표 기반 실시간 성과 추적 ({{ date('Y년 n월') }})
                                            @else
                                                아직 설정된 월별 목표가 없습니다
                                            @endif
                                        </p>
                                    </div>
                                    @if ($currentTarget)
                                        <div class="text-end">
                                            <div
                                                class="badge bg-{{ $currentTarget->overall_achievement_rate >= 100 ? 'success' : 'primary' }} fs-6">
                                                종합 달성률: {{ number_format($currentTarget->overall_achievement_rate, 1) }}%
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            @if ($currentTarget)
                                <div class="col-md-3">
                                    <div
                                        class="text-center p-3 border rounded-3 bg-white position-relative border-success">
                                        <i class="fe fe-trending-up fs-4 mb-2 text-success"></i>
                                        <div class="h4 fw-bold mb-1 text-dark">
                                            {{ number_format($currentTarget->current_sales_achievement) }}원</div>
                                        <div class="small text-dark fw-bold">매출 달성</div>
                                        <div class="small text-muted mb-2">목표:
                                            {{ number_format($currentTarget->final_sales_target) }}원</div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar bg-success"
                                                style="width: {{ min($currentTarget->sales_achievement_rate, 100) }}%">
                                            </div>
                                        </div>
                                        <small
                                            class="text-{{ $currentTarget->sales_achievement_rate >= 100 ? 'success' : 'muted' }}">
                                            {{ number_format($currentTarget->sales_achievement_rate, 1) }}% 달성
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center p-3 border rounded-3 bg-white position-relative border-info">
                                        <i class="fe fe-briefcase fs-4 mb-2 text-info"></i>
                                        <div class="h4 fw-bold mb-1 text-dark">
                                            {{ number_format($currentTarget->current_cases_achievement) }}건</div>
                                        <div class="small text-dark fw-bold">처리건수 달성</div>
                                        <div class="small text-muted mb-2">목표:
                                            {{ number_format($currentTarget->final_cases_target) }}건</div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar bg-info"
                                                style="width: {{ min($currentTarget->cases_achievement_rate, 100) }}%">
                                            </div>
                                        </div>
                                        <small
                                            class="text-{{ $currentTarget->cases_achievement_rate >= 100 ? 'success' : 'muted' }}">
                                            {{ number_format($currentTarget->cases_achievement_rate, 1) }}% 달성
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div
                                        class="text-center p-3 border rounded-3 bg-white position-relative border-warning">
                                        <i class="fe fe-dollar-sign fs-4 mb-2 text-warning"></i>
                                        <div class="h4 fw-bold mb-1 text-dark">
                                            {{ number_format($currentTarget->current_revenue_achievement) }}원</div>
                                        <div class="small text-dark fw-bold">수익 달성</div>
                                        <div class="small text-muted mb-2">목표:
                                            {{ number_format($currentTarget->final_revenue_target) }}원</div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar bg-warning"
                                                style="width: {{ min(($currentTarget->current_revenue_achievement / max($currentTarget->final_revenue_target, 1)) * 100, 100) }}%">
                                            </div>
                                        </div>
                                        <small class="text-warning">
                                            {{ number_format(($currentTarget->current_revenue_achievement / max($currentTarget->final_revenue_target, 1)) * 100, 1) }}%
                                            달성
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div
                                        class="text-center p-3 border rounded-3 bg-white position-relative border-primary">
                                        <i class="fe fe-award fs-4 mb-2 text-primary"></i>
                                        <div class="h4 fw-bold mb-1 text-dark">
                                            {{ number_format($currentTarget->calculated_bonus_amount) }}원</div>
                                        <div class="small text-dark fw-bold">예상 보너스</div>
                                        <div class="small text-muted mb-2">보너스율:
                                            {{ number_format($currentTarget->achieved_bonus_rate, 1) }}%</div>
                                        <div
                                            class="badge bg-{{ $currentTarget->achieved_bonus_rate > 0 ? 'primary' : 'secondary' }}">
                                            {{ $currentTarget->achieved_bonus_rate > 0 ? '보너스 적용' : '기본 수준' }}
                                        </div>
                                    </div>
                                </div>
                            @else
                                <!-- 목표가 없을 때 기본 표시 -->
                                <div class="col-12">
                                    <div class="text-center py-4">
                                        <div class="bg-light p-4 rounded-3 border border-dashed">
                                            <i class="fe fe-target display-4 text-muted mb-3"></i>
                                            <h6 class="text-muted">아직 설정된 동적 목표가 없습니다</h6>
                                            <p class="text-muted small mb-3">
                                                동적 목표를 설정하면 실시간 성과 추적과 보너스 계산이 가능합니다
                                            </p>
                                            <a href="{{ route('admin.partner.targets.create', ['partner_id' => $item->id]) }}"
                                                class="btn btn-primary btn-sm">
                                                <i class="fe fe-plus me-1"></i>동적 목표 생성
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- 기준치 및 승수 시스템 -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-2"><i class="fe fe-layers me-2"></i>목표 계산 시스템</h6>
                                <p class="text-muted small mb-3">파트너 타입 기준치 × 등급 승수 × 개인 조정으로 개인별 목표가 계산됩니다</p>

                                <div class="row">
                                    <!-- 파트너 타입 기준치 -->
                                    <div class="col-md-4">
                                        <div class="card bg-white border-info position-relative">
                                            <div class="card-body text-center py-4">
                                                <div class="position-absolute top-0 start-0 p-2">
                                                    <i class="fe fe-info text-info" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="파트너 타입별로 설정된 최소 기준치입니다. 모든 목표 계산의 기반이 됩니다."></i>
                                                </div>
                                                <div class="h5 fw-bold text-info mb-2">📊 타입 기준치</div>
                                                @if ($item->partnerType)
                                                    <div class="small mb-2">
                                                        <strong>{{ $item->partnerType->type_name }}</strong>
                                                    </div>
                                                    <div class="small text-dark">
                                                        <div>매출:
                                                            {{ number_format($item->partnerType->min_baseline_sales ?? ($item->partnerType->target_sales_amount ?? 0)) }}원
                                                        </div>
                                                        <div>건수:
                                                            {{ number_format($item->partnerType->min_baseline_cases ?? ($item->partnerType->target_support_cases ?? 0)) }}건
                                                        </div>
                                                        @if ($item->partnerType->baseline_quality_score)
                                                            <div>품질: {{ $item->partnerType->baseline_quality_score }}점
                                                            </div>
                                                        @endif
                                                    </div>
                                                @else
                                                    <div class="text-muted">타입 미설정</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 등급 승수 -->
                                    <div class="col-md-4">
                                        <div class="card bg-white border-primary position-relative">
                                            <div class="card-body text-center py-4">
                                                <div class="position-absolute top-0 start-0 p-2">
                                                    <i class="fe fe-info text-primary" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="등급별 승수로 기준치에 곱해져 개인별 기본 목표가 됩니다. 높은 등급일수록 높은 승수를 가집니다."></i>
                                                </div>
                                                <div class="h5 fw-bold text-primary mb-2">⚡ 등급 승수</div>
                                                @if ($item->partnerTier)
                                                    <div class="small mb-2">
                                                        <strong>{{ $item->partnerTier->tier_name }}</strong>
                                                    </div>
                                                    <div class="small text-dark">
                                                        <div>매출:
                                                            {{ number_format($item->partnerTier->sales_multiplier ?? 1.0, 1) }}x
                                                        </div>
                                                        <div>건수:
                                                            {{ number_format($item->partnerTier->cases_multiplier ?? 1.0, 1) }}x
                                                        </div>
                                                        <div>수익:
                                                            {{ number_format($item->partnerTier->revenue_multiplier ?? 1.0, 1) }}x
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="text-muted">등급 미설정</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 개인 조정 -->
                                    <div class="col-md-4">
                                        <div class="card bg-white border-success position-relative">
                                            <div class="card-body text-center py-4">
                                                <div class="position-absolute top-0 start-0 p-2">
                                                    <i class="fe fe-info text-success" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="개인별 상황에 따른 추가 조정 계수입니다. 시장 상황, 계절성, 개인 성과 등이 반영됩니다."></i>
                                                </div>
                                                <div class="h5 fw-bold text-success mb-2">🎯 개인 조정</div>
                                                @if ($currentTarget)
                                                    <div class="small mb-2">
                                                        <strong>동적 조정 계수</strong>
                                                    </div>
                                                    <div class="small text-dark">
                                                        <div>개인:
                                                            {{ number_format($currentTarget->personal_adjustment_factor, 2) }}x
                                                        </div>
                                                        <div>시장:
                                                            {{ number_format($currentTarget->market_condition_factor, 2) }}x
                                                        </div>
                                                        <div>계절:
                                                            {{ number_format($currentTarget->seasonal_adjustment_factor, 2) }}x
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="text-muted">목표 미설정</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 목표 계산 공식 -->
                                <div class="alert alert-light border mt-3">
                                    <div class="row align-items-center">
                                        <div class="col-md-12">
                                            <h6 class="text-dark mb-2"><i class="fe fe-calculator me-2"></i>목표 계산 공식</h6>
                                            <div class="d-flex align-items-center justify-content-center flex-wrap">
                                                <div class="text-center mx-2">
                                                    <div class="badge bg-info text-white mb-1">기준치</div>
                                                    <div class="small">타입별 최소값</div>
                                                </div>
                                                <div class="mx-2 text-muted">×</div>
                                                <div class="text-center mx-2">
                                                    <div class="badge bg-primary text-white mb-1">승수</div>
                                                    <div class="small">등급별 배율</div>
                                                </div>
                                                <div class="mx-2 text-muted">×</div>
                                                <div class="text-center mx-2">
                                                    <div class="badge bg-success text-white mb-1">조정</div>
                                                    <div class="small">개인별 계수</div>
                                                </div>
                                                <div class="mx-2 text-muted">=</div>
                                                <div class="text-center mx-2">
                                                    <div class="badge bg-warning text-dark mb-1">최종 목표</div>
                                                    <div class="small">개인별 맞춤</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 동적 목표 이력 및 성과 트렌드 -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-2"><i class="fe fe-trending-up me-2"></i>성과 트렌드 분석</h6>
                                <p class="text-muted small mb-3">최근 동적 목표 설정 이력과 달성률 추이를 확인하세요</p>

                                @php
                                    // 최근 3개월의 동적 목표 데이터 가져오기
                                    $recentTargets = $item
                                        ->dynamicTargets()
                                        ->where('target_period_type', 'monthly')
                                        ->where('status', 'completed')
                                        ->orderBy('target_year', 'desc')
                                        ->orderBy('target_month', 'desc')
                                        ->limit(6)
                                        ->get();
                                @endphp

                                @if ($recentTargets->count() > 0)
                                    <div class="card bg-light border-0 mb-3">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6 class="text-primary mb-3"><i class="fe fe-calendar me-1"></i>최근 성과
                                                        추이</h6>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm">
                                                            <thead>
                                                                <tr>
                                                                    <th>기간</th>
                                                                    <th>종합 달성률</th>
                                                                    <th>보너스</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($recentTargets as $target)
                                                                    <tr>
                                                                        <td class="small">{{ $target->target_year }}년
                                                                            {{ $target->target_month }}월</td>
                                                                        <td>
                                                                            <div class="d-flex align-items-center">
                                                                                <div class="progress flex-grow-1 me-2"
                                                                                    style="height: 4px;">
                                                                                    <div class="progress-bar bg-{{ $target->overall_achievement_rate >= 100 ? 'success' : 'primary' }}"
                                                                                        style="width: {{ min($target->overall_achievement_rate, 100) }}%">
                                                                                    </div>
                                                                                </div>
                                                                                <small
                                                                                    class="text-{{ $target->overall_achievement_rate >= 100 ? 'success' : 'muted' }}">
                                                                                    {{ number_format($target->overall_achievement_rate, 1) }}%
                                                                                </small>
                                                                            </div>
                                                                        </td>
                                                                        <td>
                                                                            <small
                                                                                class="text-{{ $target->achieved_bonus_rate > 0 ? 'success' : 'muted' }}">
                                                                                {{ number_format($target->calculated_bonus_amount) }}원
                                                                            </small>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6 class="text-success mb-3"><i class="fe fe-bar-chart me-1"></i>성과
                                                        통계</h6>
                                                    @php
                                                        $avgAchievement = $recentTargets->avg(
                                                            'overall_achievement_rate',
                                                        );
                                                        $totalBonus = $recentTargets->sum('calculated_bonus_amount');
                                                        $achievementCount = $recentTargets
                                                            ->where('overall_achievement_rate', '>=', 100)
                                                            ->count();
                                                    @endphp
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <div class="text-center p-2 border rounded bg-white">
                                                                <div class="small text-muted">평균 달성률</div>
                                                                <div class="fw-bold text-primary">
                                                                    {{ number_format($avgAchievement, 1) }}%</div>
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="text-center p-2 border rounded bg-white">
                                                                <div class="small text-muted">목표 달성 횟수</div>
                                                                <div class="fw-bold text-success">{{ $achievementCount }}회
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row mt-2">
                                                        <div class="col-12">
                                                            <div class="text-center p-2 border rounded bg-white">
                                                                <div class="small text-muted">누적 보너스</div>
                                                                <div class="fw-bold text-warning">
                                                                    {{ number_format($totalBonus) }}원</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="card bg-light border-0 mb-3">
                                        <div class="card-body text-center py-4">
                                            <i class="fe fe-bar-chart display-4 text-muted mb-3"></i>
                                            <h6 class="text-muted">완료된 목표 이력이 없습니다</h6>
                                            <p class="text-muted small mb-0">동적 목표를 완료하면 성과 추이 분석이 표시됩니다.</p>
                                        </div>
                                    </div>
                                @endif

                                <!-- 등급별 승수 비교표 -->
                                @php
                                    $allTiers = \Jiny\Partner\Models\PartnerTier::where('is_active', true)
                                        ->orderBy('priority_level')
                                        ->get();
                                @endphp
                                @if ($allTiers->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="text-center">등급</th>
                                                    <th class="text-center">매출 승수</th>
                                                    <th class="text-center">건수 승수</th>
                                                    <th class="text-center">수익 승수</th>
                                                    <th class="text-center">최소 달성률</th>
                                                    <th class="text-center">연속 달성</th>
                                                    <th class="text-center">승급 상태</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($allTiers as $tier)
                                                    <tr
                                                        class="{{ $item->partner_tier_id == $tier->id ? 'table-primary' : '' }}">
                                                        <td class="text-center">
                                                            @if ($item->partner_tier_id == $tier->id)
                                                                <span
                                                                    class="badge bg-primary">{{ $tier->tier_name }}</span>
                                                                <small class="d-block text-primary">현재 등급</small>
                                                            @else
                                                                <span
                                                                    class="badge bg-light text-dark">{{ $tier->tier_name }}</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            <strong
                                                                class="text-primary">{{ number_format($tier->sales_multiplier ?? 1.0, 1) }}x</strong>
                                                        </td>
                                                        <td class="text-center">
                                                            <strong
                                                                class="text-info">{{ number_format($tier->cases_multiplier ?? 1.0, 1) }}x</strong>
                                                        </td>
                                                        <td class="text-center">
                                                            <strong
                                                                class="text-success">{{ number_format($tier->revenue_multiplier ?? 1.0, 1) }}x</strong>
                                                        </td>
                                                        <td class="text-center">
                                                            <span
                                                                class="text-muted">{{ number_format($tier->min_achievement_rate ?? 70) }}%</span>
                                                        </td>
                                                        <td class="text-center">
                                                            <span
                                                                class="text-muted">{{ $tier->required_consecutive_months ?? 1 }}개월</span>
                                                        </td>
                                                        <td class="text-center">
                                                            @if ($item->partner_tier_id == $tier->id)
                                                                <i class="fe fe-check-circle text-success"
                                                                    title="현재 등급"></i>
                                                            @elseif($tier->priority_level < ($item->partnerTier->priority_level ?? 999))
                                                                @php
                                                                    $canUpgrade =
                                                                        $item->total_completed_jobs >=
                                                                            $tier->min_completed_jobs &&
                                                                        $item->average_rating >= $tier->min_rating;
                                                                @endphp
                                                                @if ($canUpgrade)
                                                                    <i class="fe fe-arrow-up-circle text-success"
                                                                        title="승급 가능"></i>
                                                                @else
                                                                    <i class="fe fe-clock text-warning"
                                                                        title="조건 미달"></i>
                                                                @endif
                                                            @else
                                                                <i class="fe fe-arrow-down-circle text-muted"
                                                                    title="하위 등급"></i>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- 등급 승급 안내 -->
                                    @php
                                        $nextTier = $allTiers
                                            ->where('priority_level', '<', $item->partnerTier->priority_level ?? 999)
                                            ->sortBy('priority_level')
                                            ->first();
                                    @endphp
                                    @if ($nextTier)
                                        <div class="alert alert-info border-info mt-3">
                                            <div class="row align-items-center">
                                                <div class="col-md-8">
                                                    <h6 class="text-info mb-1"><i class="fe fe-target me-2"></i>다음 등급:
                                                        {{ $nextTier->tier_name }}</h6>
                                                    <small class="text-muted">
                                                        승급 조건: 목표 달성률
                                                        <strong>{{ number_format($nextTier->min_achievement_rate ?? 80) }}%
                                                            이상</strong>을
                                                        <strong>{{ $nextTier->required_consecutive_months ?? 2 }}개월
                                                            연속</strong> 달성
                                                    </small>
                                                </div>
                                                <div class="col-md-4 text-end">
                                                    <div class="small text-info">
                                                        승수 증가 예상:
                                                        <div><strong>매출
                                                                {{ number_format(($nextTier->sales_multiplier ?? 1.0) - ($item->partnerTier->sales_multiplier ?? 1.0), 1) }}x
                                                                ↑</strong></div>
                                                        <div><strong>건수
                                                                {{ number_format(($nextTier->cases_multiplier ?? 1.0) - ($item->partnerTier->cases_multiplier ?? 1.0), 1) }}x
                                                                ↑</strong></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>

                        <!-- 동적 목표 및 보너스 분석 -->
                        @if ($currentTarget)
                            <div class="row">
                                <div class="col-md-8">
                                    <h6 class="text-primary mb-3"><i class="fe fe-analytics me-2"></i>동적 목표 분석</h6>

                                    <!-- 목표 대비 성과 -->
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">매출 목표 달성률</span>
                                            <span
                                                class="fw-bold text-success">{{ number_format($currentTarget->sales_achievement_rate, 1) }}%</span>
                                        </div>
                                        <div class="progress mb-1" style="height: 8px;">
                                            <div class="progress-bar bg-success"
                                                style="width: {{ min($currentTarget->sales_achievement_rate, 100) }}%">
                                            </div>
                                        </div>
                                        <small class="text-muted">목표:
                                            {{ number_format($currentTarget->final_sales_target) }}원</small>
                                    </div>

                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">처리건수 목표 달성률</span>
                                            <span
                                                class="fw-bold text-info">{{ number_format($currentTarget->cases_achievement_rate, 1) }}%</span>
                                        </div>
                                        <div class="progress mb-1" style="height: 8px;">
                                            <div class="progress-bar bg-info"
                                                style="width: {{ min($currentTarget->cases_achievement_rate, 100) }}%">
                                            </div>
                                        </div>
                                        <small class="text-muted">목표:
                                            {{ number_format($currentTarget->final_cases_target) }}건</small>
                                    </div>

                                    <!-- 보너스 단계 분석 -->
                                    @if ($currentTarget->bonus_tier_config)
                                        @php
                                            $bonusConfig = is_string($currentTarget->bonus_tier_config)
                                                ? json_decode($currentTarget->bonus_tier_config, true)
                                                : $currentTarget->bonus_tier_config;
                                        @endphp
                                        <div class="mb-3">
                                            <h6 class="text-secondary mb-2">보너스 단계별 현황</h6>
                                            <div class="small">
                                                @foreach ($bonusConfig as $threshold => $bonus)
                                                    <div
                                                        class="d-flex justify-content-between py-1 {{ $currentTarget->overall_achievement_rate >= $threshold ? 'text-success' : 'text-muted' }}">
                                                        <span>{{ $threshold }}% 달성 시</span>
                                                        <span>{{ $bonus['rate'] ?? 0 }}% 보너스
                                                            {{ $currentTarget->overall_achievement_rate >= $threshold ? '✓' : '' }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="col-md-4">
                                    <div class="card bg-light border-0">
                                        <div class="card-body">
                                            <h6 class="text-dark mb-3"><i
                                                    class="fe fe-calendar me-2"></i>{{ date('Y년 n월') }} 요약</h6>

                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-muted">종합 달성률:</span>
                                                <strong
                                                    class="text-{{ $currentTarget->overall_achievement_rate >= 100 ? 'success' : 'primary' }}">
                                                    {{ number_format($currentTarget->overall_achievement_rate, 1) }}%
                                                </strong>
                                            </div>

                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-muted">예상 보너스:</span>
                                                <strong
                                                    class="text-warning">{{ number_format($currentTarget->calculated_bonus_amount) }}원</strong>
                                            </div>

                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-muted">보너스율:</span>
                                                <strong
                                                    class="text-info">{{ number_format($currentTarget->achieved_bonus_rate, 1) }}%</strong>
                                            </div>

                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-muted">목표 상태:</span>
                                                @php
                                                    $statusColors = [
                                                        'active' => 'success',
                                                        'completed' => 'primary',
                                                        'pending_approval' => 'warning',
                                                        'draft' => 'secondary',
                                                    ];
                                                @endphp
                                                <span
                                                    class="badge bg-{{ $statusColors[$currentTarget->status] ?? 'secondary' }}">
                                                    {{ ucfirst($currentTarget->status) }}
                                                </span>
                                            </div>

                                            @if ($currentTarget->next_review_date)
                                                <div class="d-flex justify-content-between">
                                                    <span class="text-muted">다음 검토일:</span>
                                                    <strong
                                                        class="text-secondary">{{ $currentTarget->next_review_date->format('m/d') }}</strong>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <div class="bg-light p-4 rounded-3 border border-dashed">
                                    <i class="fe fe-target display-4 text-muted mb-3"></i>
                                    <h6 class="text-muted">활성화된 동적 목표가 없습니다</h6>
                                    <p class="text-muted small mb-3">동적 목표를 설정하면 실시간 성과 분석이 가능합니다.</p>
                                    <a href="{{ route('admin.partner.targets.create', ['partner_id' => $item->id]) }}"
                                        class="btn btn-primary btn-sm">
                                        <i class="fe fe-plus me-1"></i>동적 목표 생성
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- 네트워크 설정 정보 -->
                @if ($item->network_settings)
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">네트워크 설정 정보</h5>
                        </div>
                        <div class="card-body">
                            @php
                                $networkSettings = is_string($item->network_settings)
                                    ? json_decode($item->network_settings, true)
                                    : $item->network_settings;
                            @endphp

                            <div class="row">
                                @if (isset($networkSettings['auto_assign_leads']))
                                    <div class="col-md-6 mb-3">
                                        <h6 class="text-primary">자동 리드 할당</h6>
                                        <span
                                            class="badge {{ $networkSettings['auto_assign_leads'] ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $networkSettings['auto_assign_leads'] ? '활성화' : '비활성화' }}
                                        </span>
                                    </div>
                                @endif

                                @if (isset($networkSettings['commission_sharing']))
                                    <div class="col-md-6 mb-3">
                                        <h6 class="text-primary">커미션 공유</h6>
                                        @if ($networkSettings['commission_sharing']['enabled'] ?? false)
                                            <span class="badge bg-success">활성화</span>
                                            <small class="text-muted d-block">
                                                공유율:
                                                {{ ($networkSettings['commission_sharing']['share_rate'] ?? 0) * 100 }}%
                                            </small>
                                        @else
                                            <span class="badge bg-secondary">비활성화</span>
                                        @endif
                                    </div>
                                @endif

                                @if (isset($networkSettings['recruitment_settings']))
                                    <div class="col-md-12 mb-3">
                                        <h6 class="text-primary">모집 설정</h6>
                                        <div class="bg-light p-3 rounded">
                                            @if (isset($networkSettings['recruitment_settings']['max_monthly_recruits']))
                                                <div class="mb-2">
                                                    <strong>월간 최대 모집:</strong>
                                                    {{ $networkSettings['recruitment_settings']['max_monthly_recruits'] }}명
                                                </div>
                                            @endif
                                            @if (isset($networkSettings['recruitment_settings']['approval_required']))
                                                <div>
                                                    <strong>승인 필요 여부:</strong>
                                                    <span
                                                        class="badge {{ $networkSettings['recruitment_settings']['approval_required'] ? 'bg-warning' : 'bg-success' }}">
                                                        {{ $networkSettings['recruitment_settings']['approval_required'] ? '승인 필요' : '자동 승인' }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Raw JSON 표시 (개발자용) -->
                            <details class="mt-3">
                                <summary class="text-muted small">Raw JSON 데이터 보기</summary>
                                <pre class="bg-light p-2 rounded small mt-2"><code>{{ json_encode($networkSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                            </details>
                        </div>
                    </div>
                @endif

                <!-- 프로필 정보 -->
                @if ($item->profile_data)
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">프로필 정보</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @if (isset($item->profile_data['specializations']))
                                    <div class="col-md-6 mb-3">
                                        <h6 class="text-primary">전문 분야</h6>
                                        @foreach ($item->profile_data['specializations'] as $spec)
                                            <span class="badge bg-light text-dark me-1">{{ $spec }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                @if (isset($item->profile_data['certifications']))
                                    <div class="col-md-6 mb-3">
                                        <h6 class="text-primary">자격증</h6>
                                        @foreach ($item->profile_data['certifications'] as $cert)
                                            <span class="badge bg-info me-1">{{ $cert }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                @if (isset($item->profile_data['experience_years']))
                                    <div class="col-md-6 mb-3">
                                        <h6 class="text-primary">경력</h6>
                                        <p class="mb-0">{{ $item->profile_data['experience_years'] }}년</p>
                                    </div>
                                @endif

                                @if (isset($item->profile_data['preferred_locations']))
                                    <div class="col-md-6 mb-3">
                                        <h6 class="text-primary">선호 지역</h6>
                                        @foreach ($item->profile_data['preferred_locations'] as $location)
                                            <span class="badge bg-secondary me-1">{{ $location }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                @if (isset($item->profile_data['available_hours']))
                                    <div class="col-md-6 mb-3">
                                        <h6 class="text-primary">가능 시간</h6>
                                        <p class="mb-0">{{ $item->profile_data['available_hours'] }}</p>
                                    </div>
                                @endif

                                @if (isset($item->profile_data['phone']))
                                    <div class="col-md-6 mb-3">
                                        <h6 class="text-primary">연락처</h6>
                                        <p class="mb-0">{{ $item->profile_data['phone'] }}</p>
                                    </div>
                                @endif

                                @if (isset($item->profile_data['portfolio_url']))
                                    <div class="col-md-12 mb-3">
                                        <h6 class="text-primary">포트폴리오</h6>
                                        <a href="{{ $item->profile_data['portfolio_url'] }}" target="_blank"
                                            class="btn btn-outline-primary btn-sm">
                                            <i class="fe fe-external-link me-1"></i>포트폴리오 보기
                                        </a>
                                    </div>
                                @endif

                                @if (isset($item->profile_data['bio']))
                                    <div class="col-md-12">
                                        <h6 class="text-primary">소개</h6>
                                        <p class="text-muted">{{ $item->profile_data['bio'] }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <!-- 관리자 메모 -->
                @if ($item->admin_notes)
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">관리자 메모</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-0">{{ $item->admin_notes }}</p>
                        </div>
                    </div>
                @endif
            </div>

            <!-- 오른쪽 컬럼: 추가 정보 및 액션 -->
            <div class="col-lg-4">
                <!-- 계층구조 정보 -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">계층구조 정보</h6>
                        <a href="{{ route('admin.' . $routePrefix . '.tree', $item->id) }}"
                            class="btn btn-outline-primary btn-sm">
                            <i class="fe fe-git-branch me-1"></i>상세 보기
                        </a>
                    </div>
                    <div class="card-body">
                        <!-- 현재 위치 -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fe fe-layers me-2 text-info"></i>
                                <strong>현재 깊이: {{ $item->level }}</strong>
                            </div>
                            @if ($item->level == 0)
                                <small class="text-muted">최상위 파트너입니다</small>
                            @else
                                <small class="text-muted">{{ $item->level }}단계 하위 파트너입니다</small>
                            @endif

                            @if ($item->tree_path)
                                <div class="mt-2">
                                    <small class="text-muted d-block">트리 경로:</small>
                                    <code class="bg-light px-2 py-1 rounded small">{{ $item->tree_path }}</code>
                                </div>
                            @endif
                        </div>

                        <!-- 상위 파트너 -->
                        @if ($item->parent)
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fe fe-arrow-up me-2 text-success"></i>
                                    <strong>상위 파트너</strong>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="bg-light rounded p-2 flex-grow-1">
                                        <div class="fw-bold">{{ $item->parent->name }}</div>
                                        <small class="text-muted">{{ $item->parent->email }}</small>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fe fe-arrow-up me-2 text-muted"></i>
                                    <strong>상위 파트너</strong>
                                </div>
                                <small class="text-muted">상위 파트너가 없습니다 (최상위)</small>
                            </div>
                        @endif

                        <!-- 하위 파트너 요약 -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fe fe-arrow-down me-2 text-primary"></i>
                                <strong>하위 파트너</strong>
                            </div>
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="fw-bold text-primary">{{ $item->children_count }}</div>
                                    <small class="text-muted">직계 하위</small>
                                </div>
                                <div class="col-6">
                                    <div class="fw-bold text-info">{{ $item->total_children_count }}</div>
                                    <small class="text-muted">전체 하위</small>
                                </div>
                            </div>
                        </div>

                        <!-- 매출/커미션 요약 -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fe fe-dollar-sign me-2 text-warning"></i>
                                <strong>매출 정보</strong>
                            </div>
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="fw-bold text-success">{{ number_format($item->monthly_sales) }}</div>
                                    <small class="text-muted">개인 매출</small>
                                </div>
                                @if ($item->team_sales > 0)
                                    <div class="col-6">
                                        <div class="fw-bold text-info">{{ number_format($item->team_sales) }}</div>
                                        <small class="text-muted">팀 매출</small>
                                    </div>
                                @endif
                            </div>
                            @if ($item->earned_commissions > 0)
                                <div class="text-center mt-2">
                                    <div class="fw-bold text-primary">{{ number_format($item->earned_commissions) }}원
                                    </div>
                                    <small class="text-muted">획득 커미션</small>
                                </div>
                            @endif
                        </div>

                        <!-- 모집 상태 -->
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted">모집 가능 여부:</span>
                            @if ($item->can_recruit)
                                <span class="badge bg-success">
                                    <i class="fe fe-check me-1"></i>모집 가능
                                </span>
                            @else
                                <span class="badge bg-secondary">
                                    <i class="fe fe-x me-1"></i>모집 불가
                                </span>
                            @endif
                        </div>

                        @if ($item->max_children)
                            <div class="mt-2">
                                <small class="text-muted">
                                    최대 모집 가능: {{ $item->max_children }}명
                                    (현재: {{ $item->children_count }}명)
                                </small>
                                <div class="progress mt-1" style="height: 6px;">
                                    <div class="progress-bar"
                                        style="width: {{ $item->max_children > 0 ? ($item->children_count / $item->max_children) * 100 : 0 }}%">
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- 등급 승급 확인 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">등급 승급 가능성</h6>
                    </div>
                    <div class="card-body">
                        @php
                            $availableTiers = \Jiny\Partner\Models\PartnerTier::where(
                                'priority_level',
                                '<',
                                $item->partnerTier->priority_level ?? 999,
                            )
                                ->orderBy('priority_level')
                                ->get();
                        @endphp

                        @if ($availableTiers->count() > 0)
                            @foreach ($availableTiers as $tier)
                                @php
                                    $canUpgrade = $item->canUpgradeToTier($tier);
                                @endphp
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge {{ $canUpgrade ? 'bg-success' : 'bg-light text-dark' }}">
                                            {{ $tier->tier_name }}
                                        </span>
                                        @if ($canUpgrade)
                                            <i class="fe fe-check-circle text-success"></i>
                                        @else
                                            <i class="fe fe-x-circle text-muted"></i>
                                        @endif
                                    </div>
                                    <small class="text-muted d-block">
                                        작업 {{ $tier->min_completed_jobs }}개 이상,
                                        평점 {{ $tier->min_rating }} 이상
                                    </small>
                                </div>
                            @endforeach
                        @else
                            <p class="text-muted mb-0">현재 최고 등급입니다.</p>
                        @endif
                    </div>
                </div>

                <!-- 관리 액션 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">관리 액션</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('admin.' . $routePrefix . '.edit', $item->id) }}"
                                class="btn btn-primary btn-sm">
                                <i class="fe fe-edit me-1"></i>정보 수정
                            </a>

                            @if ($item->status === 'pending')
                                <button type="button" class="btn btn-success btn-sm" onclick="changeStatus('active')">
                                    <i class="fe fe-check-circle me-1"></i>승인
                                </button>
                            @endif

                            @if ($item->status === 'active')
                                <button type="button" class="btn btn-warning btn-sm"
                                    onclick="changeStatus('suspended')">
                                    <i class="fe fe-pause-circle me-1"></i>정지
                                </button>
                            @endif

                            @if ($item->status === 'suspended')
                                <button type="button" class="btn btn-info btn-sm" onclick="changeStatus('active')">
                                    <i class="fe fe-play-circle me-1"></i>정지 해제
                                </button>
                            @endif

                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="deletePartnerUser()">
                                <i class="fe fe-trash-2 me-1"></i>삭제
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 등록 정보 -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">등록 정보</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td class="text-muted">등록일:</td>
                                <td>{{ $item->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">수정일:</td>
                                <td>{{ $item->updated_at->format('Y-m-d H:i') }}</td>
                            </tr>
                            @if ($item->creator)
                                <tr>
                                    <td class="text-muted">등록자:</td>
                                    <td>{{ $item->creator->name }}</td>
                                </tr>
                            @endif
                            @if ($item->updater)
                                <tr>
                                    <td class="text-muted">수정자:</td>
                                    <td>{{ $item->updater->name }}</td>
                                </tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 승인 상태 변경 --}}
    @includeIf('jiny-partner::admin.partner-users.partials.modal_auth')

    <!-- 삭제 확인 모달 -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">파트너 회원 삭제</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>이 파트너 회원을 삭제하시겠습니까?</p>
                    <p class="text-danger small">
                        <i class="fe fe-alert-triangle me-1"></i>
                        삭제된 회원은 복구할 수 없으며, 관련된 작업 이력도 함께 영향을 받을 수 있습니다.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <form method="POST" action="{{ route('admin.' . $routePrefix . '.destroy', $item->id) }}"
                        style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">삭제</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Bootstrap 툴팁 초기화
        document.addEventListener('DOMContentLoaded', function() {
            // 모든 툴팁 초기화
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // 삭제 확인
        function deletePartnerUser() {
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }





    </script>
@endpush

@push('styles')
    <style>
        .display-6 {
            font-size: 2rem;
        }

        .progress {
            background-color: #e9ecef;
        }

        .table-borderless td {
            border: none !important;
            padding: 0.25rem 0.5rem;
        }

        .badge.fs-6 {
            font-size: 0.875rem !important;
        }

        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
    </style>
@endpush
