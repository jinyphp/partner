@extends('jiny-partner::layouts.admin.sidebar')

@section('title', $title . ' 상세보기')

@section('content')
<div class="container-fluid">

    <!-- 헤더 -->
    <section class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">{{ $item->tier_name }}</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">관리자</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.partner.dashboard') }}">파트너</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.' . $routePrefix . '.index') }}">등급 관리</a></li>
                            <li class="breadcrumb-item active">{{ $item->tier_name }}</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="{{ route('admin.' . $routePrefix . '.edit', $item->id) }}" class="btn btn-warning me-2">
                        <i class="fe fe-edit me-2"></i>수정
                    </a>
                    <a href="{{ route('admin.' . $routePrefix . '.index') }}" class="btn btn-outline-secondary">
                        <i class="fe fe-arrow-left me-2"></i>목록으로
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- 등급 상태 요약 카드 -->
    <section class="row mb-4">
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
                            <h3 class="mb-0 fw-bold">{{ number_format($partnerStats['total_count']) }}명</h3>
                            <small class="text-muted">전체의 {{ $totalPartners > 0 ? number_format(($partnerStats['total_count'] / $totalPartners) * 100, 1) : 0 }}%</small>
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
                            <div class="bg-success bg-gradient rounded-circle p-3 stat-circle">
                                <i class="fe fe-{{ $item->commission_type === 'fixed_amount' ? 'dollar-sign' : 'percent' }} text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 text-muted">수수료</h6>
                            <h3 class="mb-0 fw-bold">{{ $item->getCommissionDisplayText() }}</h3>
                            <small class="text-muted">{{ $item->commission_type === 'fixed_amount' ? '고정금액' : '퍼센트' }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="{{ $item->is_active ? 'bg-success' : 'bg-secondary' }} bg-gradient rounded-circle p-3 stat-circle">
                                <i class="fe fe-{{ $item->is_active ? 'check' : 'x' }} text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 text-muted">상태</h6>
                            <h3 class="mb-0 fw-bold">{{ $item->is_active ? '활성' : '비활성' }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div> --}}
        <!-- 비용 정보 -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-gradient rounded-circle p-3 stat-circle">
                                <i class="fe fe-credit-card text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0 text-muted">비용 정보</h6>
                            @if($item->registration_fee > 0 || $item->monthly_fee > 0 || $item->annual_fee > 0)
                                <div class="mt-1">
                                    @if($item->registration_fee > 0)
                                        <div><strong>가입:</strong> {{ number_format($item->registration_fee) }}원</div>
                                    @endif
                                    @if($item->monthly_fee > 0)
                                        <div><strong>월간:</strong> {{ number_format($item->monthly_fee) }}원/월</div>
                                    @endif
                                    @if($item->annual_fee > 0)
                                        <div><strong>연간:</strong> {{ number_format($item->annual_fee) }}원/년</div>
                                    @endif
                                    @if($item->fee_waiver_available)
                                        <small class="text-success">면제 가능</small>
                                    @endif
                                </div>
                            @else
                                <h3 class="mb-0 fw-bold text-success">무료</h3>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 비용 정보 카드 -->
    {{-- <div class="row mb-4">

    </div> --}}

    <div class="row">
        <div class="col-lg-8">
            <!-- 기본 정보 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fe fe-info me-2"></i>기본 정보
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fe fe-code text-muted me-2"></i>
                                    <label class="form-label text-muted mb-0">등급 코드</label>
                                </div>
                                <div><code class="bg-light px-2 py-1 rounded fs-6">{{ $item->tier_code }}</code></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fe fe-tag text-muted me-2"></i>
                                    <label class="form-label text-muted mb-0">등급명</label>
                                </div>
                                <div><strong class="fs-5">{{ $item->tier_name }}</strong></div>
                            </div>
                        </div>
                    </div>

                    @if($item->description)
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fe fe-file-text text-muted me-2"></i>
                                <label class="form-label text-muted mb-0">설명</label>
                            </div>
                            <div class="p-3 bg-light rounded">{{ $item->description }}</div>
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fe fe-percent text-muted me-2"></i>
                                    <label class="form-label text-muted mb-0">수수료</label>
                                </div>
                                <div>
                                    @if($item->commission_type === 'percentage')
                                        <span class="badge bg-success fs-5 px-3 py-2">{{ $item->commission_rate }}%</span>
                                    @else
                                        <span class="badge bg-info fs-5 px-3 py-2">{{ number_format($item->commission_amount) }}원</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fe fe-star text-muted me-2"></i>
                                    <label class="form-label text-muted mb-0">우선순위</label>
                                </div>
                                <div>
                                    <span class="badge bg-info fs-5 px-3 py-2">{{ $item->priority_level }}</span>
                                    <small class="text-muted d-block mt-1">(낮을수록 높은 우선순위)</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fe fe-toggle-{{ $item->is_active ? 'right' : 'left' }} text-muted me-2"></i>
                                    <label class="form-label text-muted mb-0">상태</label>
                                </div>
                                <div>
                                    @if($item->is_active)
                                        <span class="badge bg-success fs-6 px-3 py-2">
                                            <i class="fe fe-check me-1"></i>활성
                                        </span>
                                    @else
                                        <span class="badge bg-secondary fs-6 px-3 py-2">
                                            <i class="fe fe-x me-1"></i>비활성
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fe fe-list text-muted me-2"></i>
                                    <label class="form-label text-muted mb-0">정렬 순서</label>
                                </div>
                                <div class="fs-5 fw-bold">{{ $item->sort_order }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 비용 구조 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fe fe-dollar-sign me-2"></i>비용 구조
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fe fe-credit-card text-muted me-2"></i>
                                    <label class="form-label text-muted mb-0">가입비</label>
                                </div>
                                <div class="fs-4 fw-bold text-primary">{{ number_format($item->registration_fee ?? 0) }}<small class="fs-6 text-muted">원</small></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fe fe-calendar text-muted me-2"></i>
                                    <label class="form-label text-muted mb-0">월 유지비</label>
                                </div>
                                <div class="fs-4 fw-bold text-warning">{{ number_format($item->monthly_fee ?? 0) }}<small class="fs-6 text-muted">원</small></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fe fe-calendar text-muted me-2"></i>
                                    <label class="form-label text-muted mb-0">연 유지비</label>
                                </div>
                                <div class="fs-4 fw-bold text-info">{{ number_format($item->annual_fee ?? 0) }}<small class="fs-6 text-muted">원</small></div>
                            </div>
                        </div>
                    </div>
                    @if($item->fee_structure_notes)
                        <div class="alert alert-info border-0 mt-3">
                            <i class="fe fe-info me-2"></i>
                            {{ $item->fee_structure_notes }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- 요구사항 -->
            @if($item->requirements)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fe fe-clipboard me-2"></i>요구사항
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info border-0">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="fe fe-info"></i>
                                </div>
                                <div class="flex-grow-1 ms-2">
                                    <h6 class="alert-heading">등급 요구사항</h6>
                                    <p class="mb-0 small">이 등급을 획득하기 위해 파트너가 충족해야 하는 조건들입니다.</p>
                                </div>
                            </div>
                        </div>
                        <div class="border rounded p-3 bg-light">
                            <pre class="mb-0 text-wrap"><code>{{ json_encode($item->requirements, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                        </div>
                    </div>
                </div>
            @endif

            <!-- 혜택 -->
            @if($item->benefits)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fe fe-gift me-2"></i>혜택
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success border-0">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="fe fe-check-circle"></i>
                                </div>
                                <div class="flex-grow-1 ms-2">
                                    <h6 class="alert-heading">등급별 혜택</h6>
                                    <p class="mb-0 small">이 등급의 파트너가 받을 수 있는 혜택들입니다.</p>
                                </div>
                            </div>
                        </div>
                        <div class="border rounded p-3 bg-light">
                            <pre class="mb-0 text-wrap"><code>{{ json_encode($item->benefits, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <!-- 빠른 액션 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fe fe-zap me-2"></i>빠른 액션
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.' . $routePrefix . '.edit', $item->id) }}" class="btn btn-warning">
                            <i class="fe fe-edit me-2"></i>등급 수정
                        </a>
                        <button type="button" class="btn btn-outline-danger" onclick="deleteTier({{ $item->id }})">
                            <i class="fe fe-trash-2 me-2"></i>등급 삭제
                        </button>
                        <a href="{{ route('admin.' . $routePrefix . '.create') }}" class="btn btn-outline-primary">
                            <i class="fe fe-plus me-2"></i>새 등급 생성
                        </a>
                    </div>
                </div>
            </div>

            <!-- 등급 통계 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fe fe-bar-chart-2 me-2"></i>등급 통계
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted"><i class="fe fe-users me-1"></i>현재 등급 파트너</span>
                            <span class="badge bg-primary fs-6">0명</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted"><i class="fe fe-user-check me-1"></i>활성 파트너</span>
                            <span class="badge bg-success fs-6">0명</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted"><i class="fe fe-star me-1"></i>평균 평점</span>
                            <span class="badge bg-warning fs-6">-</span>
                        </div>
                    </div>
                    <div class="mb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted"><i class="fe fe-dollar-sign me-1"></i>총 수익</span>
                            <span class="badge bg-info fs-6">-</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 파트너 회원 통계 -->
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="fe fe-users me-2"></i>등급별 파트너 현황
                        </h6>
                        @if($partnerStats['total_count'] > 0)
                            <a href="{{ route('admin.partner.users.index', ['tier' => $item->id]) }}"
                               class="btn btn-primary btn-sm">
                                <i class="fe fe-list me-1"></i>상세 회원 목록 보기
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if($partnerStats['total_count'] > 0)
                        <!-- 상태별 통계 -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h4 mb-1 text-success">{{ number_format($partnerStats['active_count']) }}</div>
                                    <small class="text-muted">활성 파트너</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h4 mb-1 text-warning">{{ number_format($partnerStats['pending_count']) }}</div>
                                    <small class="text-muted">대기 중</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h4 mb-1 text-danger">{{ number_format($partnerStats['suspended_count']) }}</div>
                                    <small class="text-muted">정지됨</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h4 mb-1 text-secondary">{{ number_format($partnerStats['inactive_count']) }}</div>
                                    <small class="text-muted">비활성</small>
                                </div>
                            </div>
                        </div>

                        <!-- 성과 통계 -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="d-flex align-items-center">
                                    <i class="fe fe-star text-warning me-2"></i>
                                    <div>
                                        <div class="fw-bold">{{ $partnerStats['avg_rating'] }}</div>
                                        <small class="text-muted">평균 평점</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex align-items-center">
                                    <i class="fe fe-check-circle text-success me-2"></i>
                                    <div>
                                        <div class="fw-bold">{{ number_format($partnerStats['avg_completed_jobs']) }}</div>
                                        <small class="text-muted">평균 완료 작업</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex align-items-center">
                                    <i class="fe fe-clock text-info me-2"></i>
                                    <div>
                                        <div class="fw-bold">{{ $partnerStats['avg_punctuality'] }}%</div>
                                        <small class="text-muted">평균 시간 준수율</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex align-items-center">
                                    <i class="fe fe-heart text-danger me-2"></i>
                                    <div>
                                        <div class="fw-bold">{{ $partnerStats['avg_satisfaction'] }}%</div>
                                        <small class="text-muted">평균 만족도</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 매출 통계 -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card bg-light border-0">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <i class="fe fe-dollar-sign text-success me-3"></i>
                                            <div>
                                                <div class="h5 mb-1">{{ number_format($partnerStats['total_sales']) }}원</div>
                                                <small class="text-muted">총 매출 (평균: {{ number_format($partnerStats['avg_monthly_sales']) }}원)</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light border-0">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <i class="fe fe-trending-up text-primary me-3"></i>
                                            <div>
                                                <div class="h5 mb-1">{{ number_format($partnerStats['total_commissions']) }}원</div>
                                                <small class="text-muted">총 커미션 (평균: {{ number_format($partnerStats['avg_commissions']) }}원)</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 네트워크 통계 -->
                        @if($partnerStats['with_children'] > 0)
                        <div class="row">
                            <div class="col-md-4">
                                <div class="text-center">
                                    <div class="h5 mb-1 text-info">{{ number_format($partnerStats['with_children']) }}</div>
                                    <small class="text-muted">하위 파트너 보유</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <div class="h5 mb-1 text-info">{{ number_format($partnerStats['total_children']) }}</div>
                                    <small class="text-muted">직계 하위 총합</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <div class="h5 mb-1 text-info">{{ number_format($partnerStats['total_descendants']) }}</div>
                                    <small class="text-muted">전체 하위 총합</small>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- 최근 가입자 -->
                        @if($partnerStats['recent_joins'] > 0)
                        <div class="alert alert-info mt-3" role="alert">
                            <i class="fe fe-info me-2"></i>
                            최근 30일간 <strong>{{ $partnerStats['recent_joins'] }}명</strong>이 이 등급으로 가입했습니다.
                        </div>
                        @endif
                    @else
                        <div class="text-center py-4">
                            <i class="fe fe-users fe-3x text-muted mb-3"></i>
                            <h5 class="text-muted">아직 이 등급에 가입한 파트너가 없습니다</h5>
                            <p class="text-muted">파트너가 가입하면 여기에 통계가 표시됩니다.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- 시스템 정보 -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fe fe-database me-2"></i>시스템 정보
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-1">
                            <i class="fe fe-plus-circle text-muted me-2"></i>
                            <small class="text-muted">생성일시</small>
                        </div>
                        <div class="ps-4">{{ $item->created_at->format('Y-m-d H:i:s') }}</div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-1">
                            <i class="fe fe-edit-3 text-muted me-2"></i>
                            <small class="text-muted">수정일시</small>
                        </div>
                        <div class="ps-4">{{ $item->updated_at->format('Y-m-d H:i:s') }}</div>
                    </div>
                    @if($item->deleted_at)
                        <div class="mb-0">
                            <div class="d-flex align-items-center mb-1">
                                <i class="fe fe-trash-2 text-danger me-2"></i>
                                <small class="text-muted">삭제일시</small>
                            </div>
                            <div class="ps-4 text-danger">{{ $item->deleted_at->format('Y-m-d H:i:s') }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 삭제 확인 모달 -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">파트너 등급 삭제</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>이 파트너 등급을 삭제하시겠습니까?</p>
                <p class="text-danger small">
                    <i class="fe fe-alert-triangle me-1"></i>
                    삭제된 등급은 복구할 수 없으며, 해당 등급을 사용하는 파트너들에게 영향을 줄 수 있습니다.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">삭제</button>
                </form>
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
</style>
@endpush

@push('scripts')
<script>
// 삭제 확인
function deleteTier(id) {
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    const form = document.getElementById('deleteForm');
    form.action = `/admin/partner/tiers/${id}`;
    modal.show();
}
</script>
@endpush
