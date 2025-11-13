@extends('jiny-partner::layouts.admin.sidebar')

@section('title', $title . ' 상세보기')

@section('content')
<div class="container-fluid">
    <!-- 성공 메시지 표시 -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- 헤더 -->
    <div class="row mb-4">
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
    </div>

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
                                        @if($item->user_uuid)
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
                                        <div id="partner-code-section">
                                            @if($item->partner_code)
                                                <code id="partner-code-display" class="bg-light px-2 py-1 rounded">{{ $item->partner_code }}</code>
                                                <form method="POST" action="{{ route('admin.partner.users.partner-code.delete', $item->id) }}" style="display: inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger ms-2" onclick="return confirm('파트너 코드를 삭제하시겠습니까?')">
                                                        <i class="fe fe-trash-2"></i>
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-sm btn-outline-secondary ms-1" onclick="copyPartnerCode()">
                                                    <i class="fe fe-copy"></i>
                                                </button>
                                            @else
                                                <span id="no-code-message" class="text-muted">미생성</span>
                                                <form method="POST" action="{{ route('admin.partner.users.partner-code.generate', $item->id) }}" style="display: inline;">
                                                    @csrf
                                                    <input type="hidden" name="email" value="{{ $item->email }}">
                                                    <button type="submit" class="btn btn-sm btn-primary ms-2">
                                                        <i class="fe fe-plus me-1"></i>코드 생성
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="120" class="text-muted">등급:</td>
                                    <td>
                                        <span class="badge bg-info fs-6">{{ $item->partnerTier->tier_name ?? 'N/A' }}</span>
                                        @if($item->partnerTier)
                                            <small class="text-muted d-block">{{ $item->partnerTier->tier_code }}</small>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">파트너 타입:</td>
                                    <td>
                                        @if($item->partnerType)
                                            <span class="badge bg-primary fs-6">{{ $item->partnerType->type_name }}</span>
                                            <small class="text-muted d-block">{{ $item->partnerType->type_code }}</small>
                                        @else
                                            <span class="text-muted">미설정</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">상태:</td>
                                    <td>
                                        @if($item->status === 'active')
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
                                        @if($item->last_activity_at)
                                            <time datetime="{{ $item->last_activity_at->toISOString() }}" title="{{ $item->last_activity_at->format('Y-m-d H:i:s') }}">
                                                {{ $item->last_activity_at->diffForHumans() }}
                                            </time>
                                            <small class="text-muted d-block">{{ $item->last_activity_at->format('Y-m-d H:i:s') }}</small>
                                        @else
                                            <span class="text-muted">기록 없음</span>
                                        @endif
                                    </td>
                                </tr>
                                @if($item->last_performance_review_at)
                                <tr>
                                    <td class="text-muted">마지막 성과 평가:</td>
                                    <td>
                                        <time datetime="{{ $item->last_performance_review_at->toISOString() }}" title="{{ $item->last_performance_review_at->format('Y-m-d H:i:s') }}">
                                            {{ $item->last_performance_review_at->diffForHumans() }}
                                        </time>
                                        <small class="text-muted d-block">{{ $item->last_performance_review_at->format('Y-m-d H:i:s') }}</small>
                                    </td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    @if($item->status_reason)
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
                                <div class="display-6 fw-bold text-primary">{{ number_format($item->total_completed_jobs) }}</div>
                                <div class="text-muted">완료 작업</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="display-6 fw-bold text-warning">{{ $item->average_rating }}/5.0</div>
                                <div class="text-muted">평균 평점</div>
                                <div class="mt-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= floor($item->average_rating))
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
                                    <div class="progress-bar bg-info"
                                         style="width: {{ $item->punctuality_rate }}%"></div>
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

                    @if($item->last_performance_review_at)
                    <div class="mt-3 pt-3 border-top">
                        <small class="text-muted">
                            <i class="fe fe-calendar me-1"></i>
                            마지막 성과 평가: {{ $item->last_performance_review_at->format('Y-m-d') }}
                        </small>
                    </div>
                    @endif
                </div>
            </div>

            <!-- 수익 분배 정보 -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1">💰 수익 분배</h5>
                        <small class="text-muted">파트너의 매출 성과와 수수료 구조를 한눈에 확인하세요</small>
                        <div class="small text-info mt-1">
                            <i class="fe fe-info me-1"></i>
                            개인 성과, 팀 관리 보너스, 할인 권한 등 다양한 수익 요소를 분석합니다
                        </div>
                    </div>
                    <a href="{{ route('admin.partner.sales.index', ['partner_id' => $item->id]) }}"
                       class="btn btn-outline-primary btn-sm">
                        <i class="fe fe-external-link me-1"></i>상세 내역
                    </a>
                </div>
                <div class="card-body">
                    <!-- 매출 현황 -->
                    <div class="row mb-4">
                        <div class="col-12 mb-3">
                            <h6 class="text-primary mb-2">📊 매출 현황</h6>
                            <p class="text-muted small mb-0">이번 달 매출 성과와 수익 현황을 확인할 수 있습니다</p>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded-3 bg-white position-relative border-success">
                                <i class="fe fe-trending-up fs-4 mb-2 text-success"></i>
                                <div class="h3 fw-bold mb-1 text-dark">{{ number_format($item->monthly_sales) }}</div>
                                <div class="small text-dark fw-bold">개인 매출</div>
                                <small class="text-muted">이번 달 개인 성과</small>
                                <div class="position-absolute top-0 end-0 p-2">
                                    <i class="fe fe-help-circle text-success"
                                       data-bs-toggle="tooltip"
                                       data-bs-placement="top"
                                       title="파트너 본인이 직접 달성한 매출액입니다"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded-3 bg-white position-relative border-info">
                                <i class="fe fe-users fs-4 mb-2 text-info"></i>
                                @php
                                    $teamSales = $item->team_sales ?? ($item->monthly_sales + ($item->children ? $item->children->sum('monthly_sales') : 0));
                                @endphp
                                <div class="h3 fw-bold mb-1 text-dark">{{ number_format($teamSales) }}</div>
                                <div class="small text-dark fw-bold">팀 매출</div>
                                <small class="text-muted">개인 + 하위 파트너</small>
                                <div class="position-absolute top-0 end-0 p-2">
                                    <i class="fe fe-help-circle text-info"
                                       data-bs-toggle="tooltip"
                                       data-bs-placement="top"
                                       title="본인과 하위 파트너들의 매출 총합입니다"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded-3 bg-white position-relative border-warning">
                                <i class="fe fe-dollar-sign fs-4 mb-2 text-warning"></i>
                                <div class="h3 fw-bold mb-1 text-dark">{{ number_format($item->earned_commissions) }}</div>
                                <div class="small text-dark fw-bold">획득 커미션</div>
                                <small class="text-muted">실제 수익금</small>
                                <div class="position-absolute top-0 end-0 p-2">
                                    <i class="fe fe-help-circle text-warning"
                                       data-bs-toggle="tooltip"
                                       data-bs-placement="top"
                                       title="매출에서 발생한 실제 커미션 수익입니다"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded-3 bg-white position-relative border-primary">
                                <i class="fe fe-percent fs-4 mb-2 text-primary"></i>
                                @php
                                    $totalCommissionRate = $item->personal_commission_rate + $item->management_bonus_rate;
                                @endphp
                                <div class="h3 fw-bold mb-1 text-dark">{{ number_format($totalCommissionRate, 1) }}%</div>
                                <div class="small text-dark fw-bold">총 수수료율</div>
                                <small class="text-muted">{{ $item->partnerTier ? $item->partnerTier->tier_name : 'N/A' }} 등급</small>
                                <div class="position-absolute top-0 end-0 p-2">
                                    <i class="fe fe-help-circle text-primary"
                                       data-bs-toggle="tooltip"
                                       data-bs-placement="top"
                                       title="개인 커미션율과 관리 보너스율의 합계입니다"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 수수료 구조 -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary mb-2"><i class="fe fe-pie-chart me-2"></i>수수료 구조</h6>
                            <p class="text-muted small mb-3">파트너 등급에 따라 설정된 수수료율과 보너스 혜택입니다</p>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card bg-white border-primary position-relative">
                                        <div class="card-body text-center py-4">
                                            <div class="position-absolute top-0 start-0 p-2">
                                                <i class="fe fe-info text-primary"
                                                   data-bs-toggle="tooltip"
                                                   data-bs-placement="top"
                                                   title="본인의 직접 매출에 적용되는 기본 수수료율입니다. 매출액의 이 비율만큼 커미션을 받습니다."></i>
                                            </div>
                                            <div class="display-6 fw-bold text-primary mb-2">
                                                {{ number_format($item->personal_commission_rate, 1) }}%
                                            </div>
                                            <h6 class="text-dark mb-1">개인 커미션율</h6>
                                            <small class="text-muted">개인 성과에 대한 수수료</small>
                                            <div class="mt-2 small text-dark">
                                                <strong>적용 대상:</strong> 본인 직접 매출
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-white border-success position-relative">
                                        <div class="card-body text-center py-4">
                                            <div class="position-absolute top-0 start-0 p-2">
                                                <i class="fe fe-info text-success"
                                                   data-bs-toggle="tooltip"
                                                   data-bs-placement="top"
                                                   title="하위 파트너들을 관리하고 육성하는 것에 대한 추가 보상입니다. 하위 파트너의 매출에서 발생합니다."></i>
                                            </div>
                                            <div class="display-6 fw-bold text-success mb-2">
                                                {{ number_format($item->management_bonus_rate, 1) }}%
                                            </div>
                                            <h6 class="text-dark mb-1">관리 보너스율</h6>
                                            <small class="text-muted">팀 관리에 대한 추가 보상</small>
                                            <div class="mt-2 small text-dark">
                                                <strong>적용 대상:</strong> 하위 파트너 매출
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-white border-warning position-relative">
                                        <div class="card-body text-center py-4">
                                            <div class="position-absolute top-0 start-0 p-2">
                                                <i class="fe fe-info text-warning"
                                                   data-bs-toggle="tooltip"
                                                   data-bs-placement="top"
                                                   title="고객에게 제공할 수 있는 최대 할인율입니다. 영업 경쟁력을 높이는 중요한 도구입니다."></i>
                                            </div>
                                            <div class="display-6 fw-bold text-warning mb-2">
                                                {{ number_format($item->discount_rate, 1) }}%
                                            </div>
                                            <h6 class="text-dark mb-1">할인율</h6>
                                            <small class="text-muted">고객 할인 제공 권한</small>
                                            <div class="mt-2 small text-dark">
                                                <strong>활용:</strong> 고객 유치 및 협상력
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 수수료 계산 예시 -->
                            <div class="alert alert-light border mt-3">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h6 class="text-dark mb-1"><i class="fe fe-calculator me-2"></i>수수료 계산 예시</h6>
                                        <small class="text-muted">
                                            본인 매출 100만원 시: <strong class="text-primary">{{ number_format($item->personal_commission_rate * 10000) }}원</strong> |
                                            하위 파트너 매출 100만원 시: <strong class="text-success">{{ number_format($item->management_bonus_rate * 10000) }}원</strong> 추가
                                        </small>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <div class="small text-muted">
                                            최대 할인 가능:
                                            <span class="badge bg-warning text-dark">{{ number_format($item->discount_rate, 1) }}%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 등급별 기준 정보 -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary mb-2"><i class="fe fe-award me-2"></i>등급별 기준 정보</h6>
                            <p class="text-muted small mb-3">현재 등급 정보와 다른 등급들의 수수료 기준을 비교해보세요</p>

                            <!-- 현재 등급 vs 기본 등급 설정 비교 -->
                            <div class="card bg-light border-0 mb-3">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="text-primary mb-3"><i class="fe fe-user me-1"></i>현재 파트너 설정</h6>
                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="text-center p-2 border rounded bg-white">
                                                        <div class="small text-muted">개인 커미션율</div>
                                                        <div class="fw-bold text-primary">{{ number_format($item->personal_commission_rate, 1) }}%</div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="text-center p-2 border rounded bg-white">
                                                        <div class="small text-muted">관리 보너스율</div>
                                                        <div class="fw-bold text-success">{{ number_format($item->management_bonus_rate, 1) }}%</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-info mb-3"><i class="fe fe-award me-1"></i>{{ $item->partnerTier ? $item->partnerTier->tier_name : 'N/A' }} 등급 기준</h6>
                                            @if($item->partnerTier)
                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="text-center p-2 border rounded bg-white">
                                                        <div class="small text-muted">기본 커미션율</div>
                                                        <div class="fw-bold text-info">{{ number_format($item->partnerTier->base_commission_rate ?? $item->partnerTier->commission_rate, 1) }}%</div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="text-center p-2 border rounded bg-white">
                                                        <div class="small text-muted">기본 관리 보너스</div>
                                                        <div class="fw-bold text-info">{{ number_format($item->partnerTier->management_bonus_rate ?? 0, 1) }}%</div>
                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- 차이점 분석 -->
                                    @if($item->partnerTier)
                                    <div class="mt-3 pt-3 border-top">
                                        <div class="row">
                                            <div class="col-md-6">
                                                @php
                                                    $tierCommission = $item->partnerTier->base_commission_rate ?? $item->partnerTier->commission_rate;
                                                    $commissionDiff = $item->personal_commission_rate - $tierCommission;
                                                @endphp
                                                <small class="text-muted">
                                                    <i class="fe fe-trending-{{ $commissionDiff >= 0 ? 'up text-success' : 'down text-danger' }} me-1"></i>
                                                    등급 기준 대비:
                                                    <strong class="{{ $commissionDiff >= 0 ? 'text-success' : 'text-danger' }}">
                                                        {{ $commissionDiff >= 0 ? '+' : '' }}{{ number_format($commissionDiff, 1) }}%
                                                    </strong>
                                                </small>
                                            </div>
                                            <div class="col-md-6">
                                                @php
                                                    $tierBonus = $item->partnerTier->management_bonus_rate ?? 0;
                                                    $bonusDiff = $item->management_bonus_rate - $tierBonus;
                                                @endphp
                                                <small class="text-muted">
                                                    <i class="fe fe-trending-{{ $bonusDiff >= 0 ? 'up text-success' : 'down text-danger' }} me-1"></i>
                                                    관리 보너스 차이:
                                                    <strong class="{{ $bonusDiff >= 0 ? 'text-success' : 'text-danger' }}">
                                                        {{ $bonusDiff >= 0 ? '+' : '' }}{{ number_format($bonusDiff, 1) }}%
                                                    </strong>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- 모든 등급 비교표 -->
                            @php
                                $allTiers = \Jiny\Partner\Models\PartnerTier::where('is_active', true)
                                    ->orderBy('priority_level')
                                    ->get();
                            @endphp
                            @if($allTiers->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-center">등급</th>
                                            <th class="text-center">기본 커미션율</th>
                                            <th class="text-center">관리 보너스율</th>
                                            <th class="text-center">할인율</th>
                                            <th class="text-center">최소 완료 작업</th>
                                            <th class="text-center">최소 평점</th>
                                            <th class="text-center">상태</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($allTiers as $tier)
                                        <tr class="{{ $item->partner_tier_id == $tier->id ? 'table-primary' : '' }}">
                                            <td class="text-center">
                                                @if($item->partner_tier_id == $tier->id)
                                                    <span class="badge bg-primary">{{ $tier->tier_name }}</span>
                                                    <small class="d-block text-primary">현재 등급</small>
                                                @else
                                                    <span class="badge bg-light text-dark">{{ $tier->tier_name }}</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <strong class="text-primary">{{ number_format($tier->base_commission_rate ?? $tier->commission_rate, 1) }}%</strong>
                                            </td>
                                            <td class="text-center">
                                                <strong class="text-success">{{ number_format($tier->management_bonus_rate ?? 0, 1) }}%</strong>
                                            </td>
                                            <td class="text-center">
                                                <strong class="text-warning">{{ number_format($tier->discount_rate ?? 0, 1) }}%</strong>
                                            </td>
                                            <td class="text-center">
                                                <span class="text-muted">{{ number_format($tier->min_completed_jobs) }}개</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="text-muted">{{ $tier->min_rating }}/5.0</span>
                                            </td>
                                            <td class="text-center">
                                                @if($item->partner_tier_id == $tier->id)
                                                    <i class="fe fe-check-circle text-success" title="현재 등급"></i>
                                                @elseif($tier->priority_level < ($item->partnerTier->priority_level ?? 999))
                                                    @php
                                                        $canUpgrade = $item->total_completed_jobs >= $tier->min_completed_jobs &&
                                                                     $item->average_rating >= $tier->min_rating;
                                                    @endphp
                                                    @if($canUpgrade)
                                                        <i class="fe fe-arrow-up-circle text-success" title="승급 가능"></i>
                                                    @else
                                                        <i class="fe fe-clock text-warning" title="조건 미달"></i>
                                                    @endif
                                                @else
                                                    <i class="fe fe-arrow-down-circle text-muted" title="하위 등급"></i>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- 등급 승급 안내 -->
                            @php
                                $nextTier = $allTiers->where('priority_level', '<', $item->partnerTier->priority_level ?? 999)
                                    ->sortBy('priority_level')
                                    ->first();
                            @endphp
                            @if($nextTier)
                            <div class="alert alert-info border-info mt-3">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h6 class="text-info mb-1"><i class="fe fe-target me-2"></i>다음 등급: {{ $nextTier->tier_name }}</h6>
                                        <small class="text-muted">
                                            승급 조건: 완료 작업 <strong>{{ number_format($nextTier->min_completed_jobs) }}개 이상</strong>,
                                            평점 <strong>{{ $nextTier->min_rating }}/5.0 이상</strong>
                                        </small>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <div class="small text-info">
                                            예상 수익 증가:
                                            <strong>+{{ number_format(($nextTier->base_commission_rate ?? $nextTier->commission_rate) - ($item->partnerTier->base_commission_rate ?? $item->partnerTier->commission_rate), 1) }}%</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @endif
                        </div>
                    </div>

                    <!-- 성과 분석 -->
                    @if($item->monthly_sales > 0)
                    <div class="row">
                        <div class="col-md-8">
                            <h6 class="text-primary mb-3"><i class="fe fe-bar-chart-2 me-2"></i>성과 분석</h6>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">개인 매출 기여도</span>
                                    @php
                                        $personalContribution = $teamSales > 0 ? round(($item->monthly_sales / $teamSales) * 100, 1) : 100;
                                    @endphp
                                    <span class="fw-bold text-success">{{ $personalContribution }}%</span>
                                </div>
                                <div class="progress mb-1" style="height: 8px;">
                                    <div class="progress-bar bg-success" style="width: {{ $personalContribution }}%"></div>
                                </div>
                                <small class="text-muted">팀 전체 매출 중 개인 기여분</small>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">커미션 수익률</span>
                                    @php
                                        $commissionRate = $item->monthly_sales > 0 ? round(($item->earned_commissions / $item->monthly_sales) * 100, 1) : 0;
                                    @endphp
                                    <span class="fw-bold text-warning">{{ $commissionRate }}%</span>
                                </div>
                                <div class="progress mb-1" style="height: 8px;">
                                    <div class="progress-bar bg-warning" style="width: {{ min($commissionRate, 100) }}%"></div>
                                </div>
                                <small class="text-muted">매출 대비 획득한 커미션 비율</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light border-0">
                                <div class="card-body">
                                    <h6 class="text-dark mb-3"><i class="fe fe-calendar me-2"></i>월간 요약</h6>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">총 매출:</span>
                                        <strong class="text-success">{{ number_format($teamSales) }}원</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">총 커미션:</span>
                                        <strong class="text-warning">{{ number_format($item->earned_commissions) }}원</strong>
                                    </div>
                                    @if($item->children_count > 0)
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">하위 파트너:</span>
                                        <strong class="text-info">{{ $item->children_count }}명</strong>
                                    </div>
                                    @endif
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">수익률:</span>
                                        <strong class="text-primary">{{ $commissionRate }}%</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="text-center py-4">
                        <div class="bg-light p-4 rounded-3 border border-dashed">
                            <i class="fe fe-bar-chart-2 display-4 text-muted mb-3"></i>
                            <h6 class="text-muted">아직 등록된 매출이 없습니다</h6>
                            <p class="text-muted small mb-0">매출이 등록되면 상세한 분석 정보가 표시됩니다.</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- 네트워크 설정 정보 -->
            @if($item->network_settings)
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
                        @if(isset($networkSettings['auto_assign_leads']))
                        <div class="col-md-6 mb-3">
                            <h6 class="text-primary">자동 리드 할당</h6>
                            <span class="badge {{ $networkSettings['auto_assign_leads'] ? 'bg-success' : 'bg-secondary' }}">
                                {{ $networkSettings['auto_assign_leads'] ? '활성화' : '비활성화' }}
                            </span>
                        </div>
                        @endif

                        @if(isset($networkSettings['commission_sharing']))
                        <div class="col-md-6 mb-3">
                            <h6 class="text-primary">커미션 공유</h6>
                            @if($networkSettings['commission_sharing']['enabled'] ?? false)
                                <span class="badge bg-success">활성화</span>
                                <small class="text-muted d-block">
                                    공유율: {{ ($networkSettings['commission_sharing']['share_rate'] ?? 0) * 100 }}%
                                </small>
                            @else
                                <span class="badge bg-secondary">비활성화</span>
                            @endif
                        </div>
                        @endif

                        @if(isset($networkSettings['recruitment_settings']))
                        <div class="col-md-12 mb-3">
                            <h6 class="text-primary">모집 설정</h6>
                            <div class="bg-light p-3 rounded">
                                @if(isset($networkSettings['recruitment_settings']['max_monthly_recruits']))
                                <div class="mb-2">
                                    <strong>월간 최대 모집:</strong>
                                    {{ $networkSettings['recruitment_settings']['max_monthly_recruits'] }}명
                                </div>
                                @endif
                                @if(isset($networkSettings['recruitment_settings']['approval_required']))
                                <div>
                                    <strong>승인 필요 여부:</strong>
                                    <span class="badge {{ $networkSettings['recruitment_settings']['approval_required'] ? 'bg-warning' : 'bg-success' }}">
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
            @if($item->profile_data)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">프로필 정보</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @if(isset($item->profile_data['specializations']))
                        <div class="col-md-6 mb-3">
                            <h6 class="text-primary">전문 분야</h6>
                            @foreach($item->profile_data['specializations'] as $spec)
                                <span class="badge bg-light text-dark me-1">{{ $spec }}</span>
                            @endforeach
                        </div>
                        @endif

                        @if(isset($item->profile_data['certifications']))
                        <div class="col-md-6 mb-3">
                            <h6 class="text-primary">자격증</h6>
                            @foreach($item->profile_data['certifications'] as $cert)
                                <span class="badge bg-info me-1">{{ $cert }}</span>
                            @endforeach
                        </div>
                        @endif

                        @if(isset($item->profile_data['experience_years']))
                        <div class="col-md-6 mb-3">
                            <h6 class="text-primary">경력</h6>
                            <p class="mb-0">{{ $item->profile_data['experience_years'] }}년</p>
                        </div>
                        @endif

                        @if(isset($item->profile_data['preferred_locations']))
                        <div class="col-md-6 mb-3">
                            <h6 class="text-primary">선호 지역</h6>
                            @foreach($item->profile_data['preferred_locations'] as $location)
                                <span class="badge bg-secondary me-1">{{ $location }}</span>
                            @endforeach
                        </div>
                        @endif

                        @if(isset($item->profile_data['available_hours']))
                        <div class="col-md-6 mb-3">
                            <h6 class="text-primary">가능 시간</h6>
                            <p class="mb-0">{{ $item->profile_data['available_hours'] }}</p>
                        </div>
                        @endif

                        @if(isset($item->profile_data['phone']))
                        <div class="col-md-6 mb-3">
                            <h6 class="text-primary">연락처</h6>
                            <p class="mb-0">{{ $item->profile_data['phone'] }}</p>
                        </div>
                        @endif

                        @if(isset($item->profile_data['portfolio_url']))
                        <div class="col-md-12 mb-3">
                            <h6 class="text-primary">포트폴리오</h6>
                            <a href="{{ $item->profile_data['portfolio_url'] }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                <i class="fe fe-external-link me-1"></i>포트폴리오 보기
                            </a>
                        </div>
                        @endif

                        @if(isset($item->profile_data['bio']))
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
            @if($item->admin_notes)
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
                    <a href="{{ route('admin.' . $routePrefix . '.tree', $item->id) }}" class="btn btn-outline-primary btn-sm">
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
                        @if($item->level == 0)
                            <small class="text-muted">최상위 파트너입니다</small>
                        @else
                            <small class="text-muted">{{ $item->level }}단계 하위 파트너입니다</small>
                        @endif

                        @if($item->tree_path)
                        <div class="mt-2">
                            <small class="text-muted d-block">트리 경로:</small>
                            <code class="bg-light px-2 py-1 rounded small">{{ $item->tree_path }}</code>
                        </div>
                        @endif
                    </div>

                    <!-- 상위 파트너 -->
                    @if($item->parent)
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
                            @if($item->team_sales > 0)
                            <div class="col-6">
                                <div class="fw-bold text-info">{{ number_format($item->team_sales) }}</div>
                                <small class="text-muted">팀 매출</small>
                            </div>
                            @endif
                        </div>
                        @if($item->earned_commissions > 0)
                        <div class="text-center mt-2">
                            <div class="fw-bold text-primary">{{ number_format($item->earned_commissions) }}원</div>
                            <small class="text-muted">획득 커미션</small>
                        </div>
                        @endif
                    </div>

                    <!-- 모집 상태 -->
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted">모집 가능 여부:</span>
                        @if($item->can_recruit)
                            <span class="badge bg-success">
                                <i class="fe fe-check me-1"></i>모집 가능
                            </span>
                        @else
                            <span class="badge bg-secondary">
                                <i class="fe fe-x me-1"></i>모집 불가
                            </span>
                        @endif
                    </div>

                    @if($item->max_children)
                    <div class="mt-2">
                        <small class="text-muted">
                            최대 모집 가능: {{ $item->max_children }}명
                            (현재: {{ $item->children_count }}명)
                        </small>
                        <div class="progress mt-1" style="height: 6px;">
                            <div class="progress-bar"
                                 style="width: {{ $item->max_children > 0 ? ($item->children_count / $item->max_children) * 100 : 0 }}%"></div>
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
                        $availableTiers = \Jiny\Partner\Models\PartnerTier::where('priority_level', '<', $item->partnerTier->priority_level ?? 999)
                            ->orderBy('priority_level')
                            ->get();
                    @endphp

                    @if($availableTiers->count() > 0)
                        @foreach($availableTiers as $tier)
                            @php
                                $canUpgrade = $item->canUpgradeToTier($tier);
                            @endphp
                            <div class="mb-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge {{ $canUpgrade ? 'bg-success' : 'bg-light text-dark' }}">
                                        {{ $tier->tier_name }}
                                    </span>
                                    @if($canUpgrade)
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
                        <a href="{{ route('admin.' . $routePrefix . '.edit', $item->id) }}" class="btn btn-primary btn-sm">
                            <i class="fe fe-edit me-1"></i>정보 수정
                        </a>

                        @if($item->status === 'pending')
                        <button type="button" class="btn btn-success btn-sm" onclick="changeStatus('active')">
                            <i class="fe fe-check-circle me-1"></i>승인
                        </button>
                        @endif

                        @if($item->status === 'active')
                        <button type="button" class="btn btn-warning btn-sm" onclick="changeStatus('suspended')">
                            <i class="fe fe-pause-circle me-1"></i>정지
                        </button>
                        @endif

                        @if($item->status === 'suspended')
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
                        @if($item->creator)
                        <tr>
                            <td class="text-muted">등록자:</td>
                            <td>{{ $item->creator->name }}</td>
                        </tr>
                        @endif
                        @if($item->updater)
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
@includeIf("jiny-partner::admin.partner-users.partials.modal_auth")

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
                <form method="POST" action="{{ route('admin.' . $routePrefix . '.destroy', $item->id) }}" style="display: inline;">
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
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// 삭제 확인
function deletePartnerUser() {
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

// 파트너 코드 생성
async function generatePartnerCode() {
    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;

    try {
        // 버튼 비활성화 및 로딩 표시
        btn.disabled = true;
        btn.innerHTML = '<i class="spinner-border spinner-border-sm me-1"></i>생성중...';

        const response = await fetch(`/admin/partner/users/{{ $item->id }}/partner-code/generate`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                email: '{{ $item->email }}'
            })
        });

        const data = await response.json();

        if (response.ok && data.success) {
            // 성공 시 UI 업데이트
            updatePartnerCodeSection(data.partner_code);
            showAlert('success', '파트너 코드가 성공적으로 생성되었습니다.');
        } else {
            throw new Error(data.message || '파트너 코드 생성에 실패했습니다.');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('danger', error.message);

        // 버튼 복원
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// 파트너 코드 삭제
async function deletePartnerCode() {
    if (!confirm('파트너 코드를 삭제하시겠습니까?\n삭제된 코드는 복구할 수 없습니다.')) {
        return;
    }

    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;

    try {
        // 버튼 비활성화 및 로딩 표시
        btn.disabled = true;
        btn.innerHTML = '<i class="spinner-border spinner-border-sm"></i>';

        const response = await fetch(`/admin/partner/users/{{ $item->id }}/partner-code/delete`, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (response.ok && data.success) {
            // 성공 시 UI 업데이트
            updatePartnerCodeSection(null);
            showAlert('success', '파트너 코드가 성공적으로 삭제되었습니다.');
        } else {
            throw new Error(data.message || '파트너 코드 삭제에 실패했습니다.');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('danger', error.message);

        // 버튼 복원
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// 파트너 코드 복사
function copyPartnerCode() {
    const codeElement = document.getElementById('partner-code-display');
    const code = codeElement.textContent;

    navigator.clipboard.writeText(code).then(function() {
        showAlert('info', '파트너 코드가 클립보드에 복사되었습니다.');
    }).catch(function(err) {
        console.error('복사 실패:', err);
        showAlert('warning', '클립보드 복사에 실패했습니다.');
    });
}

// 파트너 코드 섹션 업데이트
function updatePartnerCodeSection(partnerCode) {
    const section = document.getElementById('partner-code-section');

    if (partnerCode) {
        // 코드가 있는 경우
        section.innerHTML = `
            <code id="partner-code-display" class="bg-light px-2 py-1 rounded">${partnerCode}</code>
            <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="deletePartnerCode()">
                <i class="fe fe-trash-2"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary ms-1" onclick="copyPartnerCode()">
                <i class="fe fe-copy"></i>
            </button>
        `;
    } else {
        // 코드가 없는 경우
        section.innerHTML = `
            <span id="no-code-message" class="text-muted">미생성</span>
            <button type="button" class="btn btn-sm btn-primary ms-2" onclick="generatePartnerCode()">
                <i class="fe fe-plus me-1"></i>코드 생성
            </button>
        `;
    }
}

// 알림 메시지 표시
function showAlert(type, message) {
    // 기존 알림 제거
    const existingAlert = document.querySelector('.alert-dynamic');
    if (existingAlert) {
        existingAlert.remove();
    }

    // 새 알림 생성
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show alert-dynamic`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    // 페이지 상단에 추가
    const container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);

    // 5초 후 자동 제거
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
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
