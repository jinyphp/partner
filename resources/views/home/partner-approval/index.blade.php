@extends('jiny-partner::layouts.home')

@section('title', '파트너 승인 관리')

@section('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
    <div class="container-fluid p-4">

        <!-- 헤더 -->
        <section class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-2">
                            파트너 승인 관리
                        </h2>
                        <p class="text-muted mb-0">나의 파트너 코드로 신청된 파트너 목록을 관리합니다.</p>
                    </div>
                    <div>
                        <a href="{{ route('home.partner.network.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-diagram-3 me-2"></i>네트워크 보기
                        </a>

                        <a href="/home" class="btn btn-outline-secondary">
                            <i class="bi bi-house me-1"></i>홈으로 돌아가기
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- 나의 파트너 정보 -->
        <section class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <!-- 헤더 -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h3 class="card-title mb-0 fw-bold text-dark">
                                <i class="bi bi-person-badge me-2 text-primary"></i>나의 파트너 정보
                            </h3>
                            <div class="d-flex gap-2">
                                <span class="badge bg-primary bg-gradient px-3 py-2">
                                    <i class="bi bi-star me-1"></i>{{ $myPartner->partnerTier->tier_name ?? 'Bronze' }}
                                </span>
                                <span class="badge bg-success bg-gradient px-3 py-2">
                                    <i
                                        class="bi bi-briefcase me-1"></i>{{ $myPartner->partnerType->type_name ?? 'General' }}
                                </span>
                            </div>
                        </div>

                        <!-- Stats Cards -->
                        <div class="row g-3">
                            <!-- 내 파트너 정보 -->
                            <div class="col-lg-3 col-md-6">
                                <div class="card bg-primary bg-gradient text-white border-0 h-100">
                                    <div class="card-body text-center p-3">
                                        <div class="d-flex justify-content-center align-items-center mb-2">
                                            <i class="bi bi-person-badge display-6 opacity-75"></i>
                                        </div>
                                        <h4 class="card-title fs-4 mb-1">{{ $myPartner->partnerTier->tier_name ?? 'Bronze' }}</h4>
                                        <p class="card-text mb-1 small opacity-90">
                                            <i class="bi bi-percent me-1"></i>수수료율: {{ $myPartner->personal_commission_rate ?? 5.0 }}%
                                        </p>
                                        <p class="card-text mb-0 small opacity-75">
                                            <i class="bi bi-award me-1"></i>내 등급 정보
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- 추천한 파트너 수 -->
                            <div class="col-lg-3 col-md-6">
                                <div class="card bg-success bg-gradient text-white border-0 h-100">
                                    <div class="card-body text-center p-3">
                                        <div class="d-flex justify-content-center align-items-center mb-2">
                                            <i class="bi bi-people-fill display-6 opacity-75"></i>
                                        </div>
                                        <h4 class="card-title fs-3 mb-1">{{ $partnersAppliedWithMyCode->count() }}</h4>
                                        <p class="card-text mb-0 small opacity-90">
                                            <i class="bi bi-person-plus me-1"></i>추천한 파트너 수
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- 승인 대기 신청 -->
                            <div class="col-lg-3 col-md-6">
                                <div class="card bg-warning bg-gradient text-white border-0 h-100">
                                    <div class="card-body text-center p-3">
                                        <div class="d-flex justify-content-center align-items-center mb-2">
                                            <i class="bi bi-hourglass-split display-6 opacity-75"></i>
                                        </div>
                                        <h4 class="card-title fs-3 mb-1">{{ $pendingApplications->count() }}</h4>
                                        <p class="card-text mb-0 small opacity-90">
                                            <i class="bi bi-clock-history me-1"></i>승인 대기 신청
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- 총 매출 -->
                            <div class="col-lg-3 col-md-6">
                                <div class="card bg-info bg-gradient text-white border-0 h-100">
                                    <div class="card-body text-center p-3">
                                        <div class="d-flex justify-content-center align-items-center mb-2">
                                            <i class="bi bi-currency-dollar display-6 opacity-75"></i>
                                        </div>
                                        <h4 class="card-title fs-5 mb-1">{{ number_format($myPartner->total_sales ?? 0) }}원
                                        </h4>
                                        <p class="card-text mb-0 small opacity-90">
                                            <i class="bi bi-graph-up me-1"></i>총 매출
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 추가 정보 -->
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="alert alert-light border mb-0">
                                    <div class="row text-center">
                                        <div class="col-md-3">
                                            <small class="text-muted">가입일</small>
                                            <div class="fw-bold">
                                                {{ $myPartner->partner_joined_at ? $myPartner->partner_joined_at->format('Y-m-d') : '-' }}
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted">활동 상태</small>
                                            <div>
                                                <span
                                                    class="badge bg-{{ $myPartner->status === 'active' ? 'success' : 'secondary' }}">
                                                    {{ $myPartner->status === 'active' ? '활성' : '비활성' }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted">월 매출</small>
                                            <div class="fw-bold">{{ number_format($myPartner->monthly_sales ?? 0) }}원</div>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted">하위 파트너</small>
                                            <div class="fw-bold">{{ $myPartner->children_count ?? 0 }}명</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 메인 컨텐츠 -->
        <section class="row g-4 mb-4">
            <!-- 승인 대기 신청서 -->
            <div class="col-12">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="card-title mb-0 fw-bold text-dark">
                                    <i class="bi bi-clock-history me-2 text-warning"></i>승인 대기 신청서
                                </h4>
                                <p class="text-muted small mb-0 mt-1">
                                    나의 파트너 코드로 신청된 대기 중인 신청서들
                                </p>
                            </div>
                            @if (!$pendingApplications->isEmpty())
                                <div class="d-flex gap-2">
                                    <!-- 필터링 및 정렬 옵션 -->
                                    <div class="dropdown">
                                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                            id="filterDropdown" data-bs-toggle="dropdown">
                                            <i class="bi bi-funnel me-1"></i>필터
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item filter-option" href="#"
                                                    data-filter="all">전체</a></li>
                                            <li><a class="dropdown-item filter-option" href="#"
                                                    data-filter="submitted">접수완료</a></li>
                                            <li><a class="dropdown-item filter-option" href="#"
                                                    data-filter="reviewing">검토중</a></li>
                                            <li><a class="dropdown-item filter-option" href="#"
                                                    data-filter="interview">면접예정</a></li>
                                        </ul>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                            id="sortDropdown" data-bs-toggle="dropdown">
                                            <i class="bi bi-sort-down me-1"></i>정렬
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item sort-option" href="#"
                                                    data-sort="date-desc">최신순</a></li>
                                            <li><a class="dropdown-item sort-option" href="#"
                                                    data-sort="date-asc">과거순</a></li>
                                            <li><a class="dropdown-item sort-option" href="#"
                                                    data-sort="completeness-desc">완성도순</a></li>
                                            <li><a class="dropdown-item sort-option" href="#"
                                                    data-sort="experience-desc">경력순</a></li>
                                        </ul>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @if ($pendingApplications->isEmpty())
                            <div class="text-center py-5">
                                <i class="bi bi-hourglass-split display-1 text-muted opacity-50"></i>
                                <h5 class="mt-3 text-muted">승인 대기 신청서가 없습니다</h5>
                                <p class="text-muted small">새로운 신청서가 있을 때 여기에 표시됩니다.</p>
                            </div>
                        @else
                            <!-- 신청서 리스트 -->
                            <div class="applications-list">
                                @foreach ($pendingApplications as $index => $application)
                                    <div class="application-item border-bottom"
                                        data-status="{{ $application['application_status'] }}"
                                        data-submitted="{{ $application['submitted_at']->format('Y-m-d H:i:s') }}"
                                        data-completeness="{{ $application['completeness_score'] }}"
                                        data-experience="{{ $application['experience_years'] }}">

                                        <div class="d-flex align-items-center p-4 application-card">
                                            <!-- 우선순위 인디케이터 -->
                                            <div class="priority-indicator me-3">
                                                <div class="position-relative">
                                                    <div
                                                        class="application-avatar
                                                    @if ($application['application_status'] === 'interview') bg-warning text-dark
                                                    @elseif($application['application_status'] === 'reviewing') bg-info text-white
                                                    @else bg-light text-dark @endif">
                                                        {{ mb_substr($application['applicant_name'], 0, 1) }}
                                                    </div>
                                                    @if ($application['completeness_score'] >= 80)
                                                        <span
                                                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                                                            <i class="bi bi-star-fill"></i>
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- 신청자 정보 -->
                                            <div class="applicant-info flex-grow-1 me-3">
                                                <div class="d-flex align-items-center mb-1">
                                                    <h5 class="mb-0 me-2 fw-bold">{{ $application['applicant_name'] }}
                                                    </h5>
                                                    <span
                                                        class="badge application-status-badge
                                                    @if ($application['application_status'] === 'submitted') bg-warning text-dark
                                                    @elseif($application['application_status'] === 'reviewing') bg-info
                                                    @elseif($application['application_status'] === 'interview') bg-primary
                                                    @else bg-secondary @endif">
                                                        @if ($application['application_status'] === 'submitted')
                                                            접수완료
                                                        @elseif($application['application_status'] === 'reviewing')
                                                            검토중
                                                        @elseif($application['application_status'] === 'interview')
                                                            면접예정
                                                        @else
                                                            {{ $application['application_status'] }}
                                                        @endif
                                                    </span>
                                                </div>
                                                <div class="text-muted small mb-1">
                                                    <i class="bi bi-envelope me-1"></i>{{ $application['email'] }}
                                                    @if ($application['phone'])
                                                        <span class="ms-3">
                                                            <i
                                                                class="bi bi-telephone me-1"></i>{{ $application['phone'] }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="small text-muted">
                                                    <i
                                                        class="bi bi-calendar3 me-1"></i>{{ $application['submitted_at']->format('Y-m-d H:i') }}
                                                    <span class="ms-3">희망등급: <span
                                                            class="fw-bold text-dark">{{ $application['target_tier'] }}</span></span>
                                                </div>
                                            </div>

                                            <!-- 신청 정보 메트릭스 -->
                                            <div class="application-metrics me-4 d-none d-md-flex">
                                                <div class="metric-item me-3">
                                                    <div class="metric-value">{{ $application['experience_years'] }}년
                                                    </div>
                                                    <div class="metric-label">경력</div>
                                                </div>
                                                <div class="metric-item me-3">
                                                    <div
                                                        class="metric-value
                                                    @if ($application['completeness_score'] >= 80) text-success
                                                    @elseif($application['completeness_score'] >= 60) text-warning
                                                    @else text-danger @endif">
                                                        {{ $application['completeness_score'] }}%</div>
                                                    <div class="metric-label">완성도</div>
                                                </div>
                                            </div>

                                            <!-- 액션 버튼들 -->
                                            <div class="application-actions">
                                                <div class="d-flex gap-2">
                                                    <a href="{{ route('home.partner.approval.detail', $application['id']) }}"
                                                        class="btn btn-outline-primary btn-sm action-btn" title="상세보기">
                                                        <i class="bi bi-eye"></i>
                                                        <span class="d-none d-lg-inline ms-1">상세</span>
                                                    </a>

                                                    @if($application['application_status'] === 'approved')
                                                        <!-- 승인 완료 상태 표시 -->
                                                        <span class="btn btn-success btn-sm action-btn disabled" title="승인 완료">
                                                            <i class="bi bi-check-circle-fill"></i>
                                                            <span class="d-none d-lg-inline ms-1">승인완료</span>
                                                        </span>
                                                    @else
                                                        <!-- 승인 가능한 상태인 경우만 승인 버튼 표시 -->
                                                        <button type="button"
                                                            onclick="quickApprove({{ $application['id'] }})"
                                                            class="btn btn-success btn-sm action-btn"
                                                            title="승인하기 (내 등급: {{ $myPartner->partnerTier->tier_name ?? 'Bronze' }}, 최대 수수료: {{ $myPartner->personal_commission_rate ?? 5.0 }}%)"
                                                            data-bs-toggle="tooltip"
                                                            data-bs-placement="top">
                                                            <i class="bi bi-check-lg"></i>
                                                            <span class="d-none d-lg-inline ms-1">승인</span>
                                                        </button>
                                                    @endif

                                                    <button type="button"
                                                        onclick="showQuickActions({{ $application['id'] }})"
                                                        class="btn btn-outline-secondary btn-sm action-btn"
                                                        title="더보기">
                                                        <i class="bi bi-three-dots-vertical"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 확장 정보 영역 (모바일) -->
                                        <div class="d-md-none px-4 pb-3">
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <div class="small text-center p-2 bg-light rounded">
                                                        <div class="fw-bold">{{ $application['experience_years'] }}년</div>
                                                        <div class="text-muted">경력</div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="small text-center p-2 bg-light rounded">
                                                        <div
                                                            class="fw-bold
                                                        @if ($application['completeness_score'] >= 80) text-success
                                                        @elseif($application['completeness_score'] >= 60) text-warning
                                                        @else text-danger @endif">
                                                            {{ $application['completeness_score'] }}%</div>
                                                        <div class="text-muted">완성도</div>
                                                    </div>
                                                </div>
                                            </div>
                                            @if ($application['motivation'])
                                                <div class="mt-2 p-2 bg-light rounded small">
                                                    <strong class="text-muted">지원동기:</strong>
                                                    {{ Str::limit($application['motivation'], 80) }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- 빠른 일괄 액션 -->
                            <div class="bulk-actions-bar bg-light border-top p-3 d-none">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="bulk-selection-info">
                                        <span class="selected-count">0</span>개 선택됨
                                    </div>
                                    <div class="bulk-action-buttons">
                                        <button class="btn btn-success btn-sm me-2" onclick="bulkApprove()">
                                            <i class="bi bi-check-lg me-1"></i>일괄 승인
                                        </button>
                                        <button class="btn btn-outline-secondary btn-sm" onclick="clearSelection()">
                                            선택 해제
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <!-- 통계 요약 -->
        {{-- <section class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h4 class="card-title mb-0 fw-bold text-dark">
                            <i class="bi bi-graph-up me-2 text-info"></i>추천 활동 통계
                        </h4>
                        <p class="text-muted small mb-0 mt-1">전체 추천 활동에 대한 요약 정보입니다.</p>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-lg-4 col-md-6">
                                <div class="bg-light rounded-3 p-3 text-center">
                                    <i class="bi bi-person-check display-6 text-success mb-2"></i>
                                    <h4 class="fw-bold text-success mb-1">
                                        {{ $partnersAppliedWithMyCode->where('status', 'active')->count() }}</h4>
                                    <small class="text-muted">활성 파트너</small>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6">
                                <div class="bg-light rounded-3 p-3 text-center">
                                    <i class="bi bi-currency-dollar display-6 text-primary mb-2"></i>
                                    <h4 class="fw-bold text-primary mb-1 small">
                                        {{ $partnersAppliedWithMyCode->sum('total_sales') ? number_format($partnersAppliedWithMyCode->sum('total_sales')) : 0 }}원
                                    </h4>
                                    <small class="text-muted">추천 파트너 총 매출</small>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6">
                                <div class="bg-light rounded-3 p-3 text-center">
                                    <i class="bi bi-wallet2 display-6 text-warning mb-2"></i>
                                    <h4 class="fw-bold text-warning mb-1 small">
                                        {{ $partnersAppliedWithMyCode->sum('earned_commissions') ? number_format($partnersAppliedWithMyCode->sum('earned_commissions')) : 0 }}원
                                    </h4>
                                    <small class="text-muted">추천 파트너 총 커미션</small>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6">
                                <div class="bg-light rounded-3 p-3 text-center">
                                    <i class="bi bi-diagram-3 display-6 text-info mb-2"></i>
                                    <h4 class="fw-bold text-info mb-1">
                                        {{ $partnersAppliedWithMyCode->sum('children_count') ?? 0 }}</h4>
                                    <small class="text-muted">2차 추천 파트너</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section> --}}

    </div>

    <!-- 승인 모달 -->
    <div class="modal fade" id="quickApproveModal" tabindex="-1" aria-labelledby="quickApproveModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <div>
                            <h5 class="modal-title mb-1" id="quickApproveModalLabel">파트너 승인</h5>
                            <div class="d-flex gap-3">
                                <small class="text-muted">
                                    <i class="bi bi-person-badge me-1"></i>
                                    내 등급: <span class="badge bg-{{ $myPartner->partnerTier->tier_name == 'Gold' ? 'warning' : ($myPartner->partnerTier->tier_name == 'Platinum' ? 'dark' : ($myPartner->partnerTier->tier_name == 'Silver' ? 'secondary' : 'primary')) }}">{{ $myPartner->partnerTier->tier_name ?? 'Bronze' }}</span>
                                </small>
                                <small class="text-muted">
                                    <i class="bi bi-percent me-1"></i>
                                    내 수수료율: <span class="fw-bold text-primary">{{ $myPartner->personal_commission_rate ?? 5.0 }}%</span>
                                </small>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>승인 후 신청자가 새로운 파트너로 등록됩니다.</strong>
                    </div>

                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>승인 제한사항:</strong>
                        <ul class="mb-0 mt-2">
                            <li>자신의 등급({{ $myPartner->partnerTier->tier_name ?? 'Bronze' }})보다 높은 등급은 부여할 수 없습니다</li>
                            <li>자신의 커미션 비율({{ $myPartner->personal_commission_rate ?? 5.0 }}%)보다 높은 비율은 설정할 수 없습니다</li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <label for="approval_comments" class="form-label">승인 메모 (선택사항)</label>
                        <textarea id="approval_comments"
                                  class="form-control"
                                  rows="3"
                                  placeholder="승인과 관련된 메모를 입력하세요...">파트너 승인 관리 시스템을 통한 승인</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="assigned_tier" class="form-label">
                                    등급 할당
                                    <span class="badge bg-light text-dark ms-1">{{ $myPartner->partnerTier->tier_name ?? 'Bronze' }} 이하만</span>
                                </label>
                                <select id="assigned_tier" class="form-select">
                                    <option value="">기본 등급 사용</option>
                                    @php
                                        $currentTierLevel = match($myPartner->partnerTier->tier_name ?? 'Bronze') {
                                            'Bronze' => 1,
                                            'Silver' => 2,
                                            'Gold' => 3,
                                            'Platinum' => 4,
                                            default => 1
                                        };
                                        $tiers = [
                                            ['name' => 'Bronze', 'level' => 1],
                                            ['name' => 'Silver', 'level' => 2],
                                            ['name' => 'Gold', 'level' => 3],
                                            ['name' => 'Platinum', 'level' => 4]
                                        ];
                                    @endphp
                                    @foreach($tiers as $tier)
                                        @if($tier['level'] <= $currentTierLevel)
                                            <option value="{{ $tier['name'] }}">{{ $tier['name'] }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                <div class="form-text text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    내 등급({{ $myPartner->partnerTier->tier_name ?? 'Bronze' }}) 이하의 등급만 승인 가능
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="commission_rate" class="form-label">
                                    커미션 비율 (%)
                                    <span class="badge bg-light text-dark ms-1">최대 {{ $myPartner->personal_commission_rate ?? 5.0 }}%</span>
                                </label>
                                <div class="input-group">
                                    <input type="number"
                                           id="commission_rate"
                                           class="form-control"
                                           placeholder="기본값 사용"
                                           min="0"
                                           max="{{ $myPartner->personal_commission_rate ?? 5.0 }}"
                                           step="0.1">
                                    <span class="input-group-text">%</span>
                                </div>
                                <div class="form-text text-danger">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                    내 수수료율({{ $myPartner->personal_commission_rate ?? 5.0 }}%)을 초과할 수 없습니다
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="notify_user_approve" checked>
                        <label class="form-check-label" for="notify_user_approve">
                            신청자에게 승인 알림 전송
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="button" class="btn btn-success" onclick="confirmApprove()">
                        <i class="bi bi-check-lg me-1"></i>승인 확인
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <style>
        .avatar-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .hover-shadow {
            transition: all 0.3s ease;
        }

        .hover-shadow:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
        }

        .card {
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-1px);
        }

        .badge {
            font-size: 0.7rem;
        }

        .display-1 {
            opacity: 0.1;
        }

        /* 새로운 승인 목록 스타일 */
        .applications-list .application-item {
            transition: all 0.2s ease;
        }

        .applications-list .application-item:hover {
            background-color: #f8f9fa;
        }

        .applications-list .application-item:last-child {
            border-bottom: none !important;
        }

        .application-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            border: 2px solid #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .priority-indicator .badge {
            width: 20px;
            height: 20px;
            padding: 0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .application-status-badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.35rem 0.75rem;
            border-radius: 1rem;
        }

        .metric-item {
            text-align: center;
            min-width: 60px;
        }

        .metric-value {
            font-size: 1.1rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .metric-label {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 2px;
        }

        .action-btn {
            min-width: 40px;
            height: 38px;
            border-radius: 8px;
            transition: all 0.2s ease;
            position: relative;
        }

        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        /* 필터 및 정렬 드롭다운 */
        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
            border-radius: 12px;
            padding: 0.5rem 0;
        }

        .dropdown-item {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .dropdown-item:hover,
        .dropdown-item.active {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        /* 일괄 액션 바 */
        .bulk-actions-bar {
            border-top: 2px solid #e9ecef !important;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
        }

        /* 반응형 스타일 */
        @media (max-width: 768px) {
            .application-avatar {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }

            .action-btn {
                min-width: 36px;
                height: 36px;
                padding: 0.25rem;
            }

            .action-btn span {
                display: none !important;
            }
        }

        /* 애니메이션 */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .application-item {
            animation: slideIn 0.3s ease forwards;
        }

        .application-item.filtering-out {
            opacity: 0.3;
            transform: scale(0.98);
        }

        /* 로딩 상태 */
        .loading-skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, transparent 37%, #f0f0f0 63%);
            background-size: 400% 100%;
            animation: skeleton-loading 1.4s ease infinite;
        }

        @keyframes skeleton-loading {
            0% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0 50%;
            }
        }

        /* 성공/실패 알림 스타일 */
        .alert-floating {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            min-width: 300px;
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            transform: translateX(400px);
            transition: transform 0.3s ease;
        }

        .alert-floating.show {
            transform: translateX(0);
        }

        /* 완성도별 색상 */
        .completeness-high {
            color: #28a745 !important;
        }

        .completeness-medium {
            color: #ffc107 !important;
        }

        .completeness-low {
            color: #dc3545 !important;
        }
    </style>
@endsection

@push('scripts')
    <script>
        // 전역 변수
        let currentFilter = 'all';
        let currentSort = 'date-desc';

        // 현재 파트너 정보 (PHP에서 전달)
        const currentPartner = {
            tier: '{{ $myPartner->partnerTier->tier_name ?? 'Bronze' }}',
            commissionRate: {{ $myPartner->personal_commission_rate ?? 5.0 }},
            tierLevel: {
                'Bronze': 1,
                'Silver': 2,
                'Gold': 3,
                'Platinum': 4
            }
        };

        // DOM 로드 완료 후 초기화
        document.addEventListener('DOMContentLoaded', function() {
            console.log("home partner approval");
            initializeFiltersAndSorting();
            initializeTooltips();
            addKeyboardShortcuts();
        });

        // 필터링 및 정렬 초기화
        function initializeFiltersAndSorting() {
            // 필터 이벤트 리스너
            document.querySelectorAll('.filter-option').forEach(option => {
                option.addEventListener('click', function(e) {
                    e.preventDefault();
                    const filter = this.dataset.filter;
                    applyFilter(filter);

                    // 활성 상태 표시
                    document.querySelectorAll('.filter-option').forEach(opt => opt.classList.remove(
                        'active'));
                    this.classList.add('active');
                });
            });

            // 정렬 이벤트 리스너
            document.querySelectorAll('.sort-option').forEach(option => {
                option.addEventListener('click', function(e) {
                    e.preventDefault();
                    const sort = this.dataset.sort;
                    applySort(sort);

                    // 활성 상태 표시
                    document.querySelectorAll('.sort-option').forEach(opt => opt.classList.remove(
                        'active'));
                    this.classList.add('active');
                });
            });
        }

        // 필터 적용
        function applyFilter(filter) {
            currentFilter = filter;
            const applications = document.querySelectorAll('.application-item');

            applications.forEach(app => {
                const status = app.dataset.status;
                const shouldShow = filter === 'all' || status === filter;

                if (shouldShow) {
                    app.style.display = 'block';
                    app.classList.remove('filtering-out');
                } else {
                    app.classList.add('filtering-out');
                    setTimeout(() => {
                        app.style.display = 'none';
                    }, 200);
                }
            });

            updateApplicationCount();
        }

        // 정렬 적용
        function applySort(sort) {
            currentSort = sort;
            const container = document.querySelector('.applications-list');
            const applications = Array.from(container.children);

            applications.sort((a, b) => {
                switch (sort) {
                    case 'date-desc':
                        return new Date(b.dataset.submitted) - new Date(a.dataset.submitted);
                    case 'date-asc':
                        return new Date(a.dataset.submitted) - new Date(b.dataset.submitted);
                    case 'completeness-desc':
                        return parseInt(b.dataset.completeness) - parseInt(a.dataset.completeness);
                    case 'experience-desc':
                        return parseInt(b.dataset.experience) - parseInt(a.dataset.experience);
                    default:
                        return 0;
                }
            });

            // 정렬된 순서로 재배치
            applications.forEach(app => container.appendChild(app));
        }

        // 신청서 개수 업데이트
        function updateApplicationCount() {
            const visible = document.querySelectorAll('.application-item:not([style*="display: none"])').length;
            const total = document.querySelectorAll('.application-item').length;

            // 헤더에 카운트 표시 (필요시 추가)
            console.log(`Showing ${visible} of ${total} applications`);
        }

        // 툴팁 초기화
        function initializeTooltips() {
            // Bootstrap 툴팁 활성화 (필요시)
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
        }

        // 키보드 단축키 추가
        function addKeyboardShortcuts() {
            document.addEventListener('keydown', function(e) {
                // Ctrl/Cmd + A: 전체 선택
                if ((e.ctrlKey || e.metaKey) && e.key === 'a' && e.target.tagName !== 'INPUT') {
                    e.preventDefault();
                    // 전체 선택 로직 (필요시 구현)
                }

                // ESC: 필터 초기화
                if (e.key === 'Escape') {
                    applyFilter('all');
                    document.querySelector('.filter-option[data-filter="all"]').classList.add('active');
                }
            });
        }

        // 기존 함수들
        let currentApplicationId = null;

        function quickApprove(applicationId) {
            currentApplicationId = applicationId;

            // 모달 초기화
            document.getElementById('approval_comments').value = '파트너 승인 관리 시스템을 통한 승인';
            document.getElementById('assigned_tier').value = '';
            document.getElementById('commission_rate').value = '';
            document.getElementById('notify_user_approve').checked = true;

            // 현재 파트너의 tier에 따라 선택 가능한 등급 제한
            updateTierOptions();

            // 모달 표시
            const modal = new bootstrap.Modal(document.getElementById('quickApproveModal'));
            modal.show();
        }

        function updateTierOptions() {
            const tierSelect = document.getElementById('assigned_tier');
            const currentTierLevel = currentPartner.tierLevel[currentPartner.tier] || 1;

            // 기존 옵션 초기화
            tierSelect.innerHTML = '<option value="">기본 등급 사용</option>';

            // 현재 tier와 같거나 낮은 등급만 추가
            const tiers = ['Bronze', 'Silver', 'Gold', 'Platinum'];
            tiers.forEach(tier => {
                const tierLevel = currentPartner.tierLevel[tier];
                if (tierLevel <= currentTierLevel) {
                    const option = document.createElement('option');
                    option.value = tier;
                    option.textContent = tier;
                    tierSelect.appendChild(option);
                }
            });
        }


        function confirmApprove() {
            if (!currentApplicationId) return;

            const comments = document.getElementById('approval_comments').value;
            const assignedTier = document.getElementById('assigned_tier').value || null;
            const commissionRate = document.getElementById('commission_rate').value || null;
            const notifyUser = document.getElementById('notify_user_approve').checked;

            // 클라이언트 측 유효성 검사
            const validationError = validateApprovalData(assignedTier, commissionRate);
            if (validationError) {
                alert(validationError);
                return;
            }

            // 확인 버튼 비활성화
            const confirmBtn = document.querySelector('#quickApproveModal .btn-success');
            const originalText = confirmBtn.innerHTML;
            confirmBtn.innerHTML = '<i class="bi bi-spinner spin-animate me-1"></i>처리중...';
            confirmBtn.disabled = true;

            // 승인 요청 전송
            fetch(`/home/partner/approval/${currentApplicationId}/approve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    comments: comments,
                    assigned_tier: assignedTier,
                    commission_rate: commissionRate ? parseFloat(commissionRate) : null,
                    notify_user: notifyUser
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // 모달 닫기
                    const modal = bootstrap.Modal.getInstance(document.getElementById('quickApproveModal'));
                    modal.hide();

                    // 성공 메시지 표시
                    showFloatingAlert('success', data.message || '파트너 승인이 완료되었습니다.');

                    // 페이지 새로고침으로 목록 업데이트
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showFloatingAlert('error', data.message || '승인 처리 중 오류가 발생했습니다.');

                    // 버튼 복원
                    confirmBtn.innerHTML = originalText;
                    confirmBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Approval Error:', error);
                showFloatingAlert('error', '네트워크 오류가 발생했습니다.');

                // 버튼 복원
                confirmBtn.innerHTML = originalText;
                confirmBtn.disabled = false;
            });
        }

        // 빠른 액션 메뉴 표시
        function showQuickActions(applicationId) {
            // 컨텍스트 메뉴 또는 모달 표시
            console.log('Quick actions for application:', applicationId);
            // 추후 구현: 빠른 거절, 메모 추가, 우선순위 설정 등
        }

        // 일괄 승인
        function bulkApprove() {
            const selectedApplications = getSelectedApplications();
            if (selectedApplications.length === 0) {
                showFloatingAlert('warning', '선택된 신청서가 없습니다.');
                return;
            }

            showConfirmDialog(`${selectedApplications.length}개의 신청서를 일괄 승인하시겠습니까?`, () => {
                // 일괄 승인 로직 구현
                console.log('Bulk approving:', selectedApplications);
            });
        }

        // 선택 해제
        function clearSelection() {
            document.querySelectorAll('.application-checkbox:checked').forEach(checkbox => {
                checkbox.checked = false;
            });
            updateBulkActionBar();
        }

        // 선택된 신청서 가져오기
        function getSelectedApplications() {
            return Array.from(document.querySelectorAll('.application-checkbox:checked'))
                .map(checkbox => checkbox.value);
        }

        // 일괄 액션 바 업데이트
        function updateBulkActionBar() {
            const selected = getSelectedApplications();
            const bulkBar = document.querySelector('.bulk-actions-bar');
            const countElement = document.querySelector('.selected-count');

            if (selected.length > 0) {
                bulkBar.classList.remove('d-none');
                countElement.textContent = selected.length;
            } else {
                bulkBar.classList.add('d-none');
            }
        }

        // 확인 다이얼로그 표시
        function showConfirmDialog(message, onConfirm) {
            // 더 나은 UX를 위한 커스텀 확인 다이얼로그
            if (confirm(message)) {
                onConfirm();
            }
        }

        // 플로팅 알림 표시
        function showFloatingAlert(type, message) {
            const alertHtml = `
        <div class="alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'warning'} alert-floating" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                <div>${message}</div>
                <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
            </div>
        </div>
    `;

            document.body.insertAdjacentHTML('beforeend', alertHtml);
            const alert = document.querySelector('.alert-floating:last-child');

            // 애니메이션으로 표시
            setTimeout(() => alert.classList.add('show'), 100);

            // 자동 제거
            setTimeout(() => {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        }

        // 테이블 정렬 기능 (레거시)
        function sortTable(column) {
            console.log('Sorting by:', column);
            // 새로운 정렬 시스템 사용
        }

        // 파트너 코드 복사 기능
        function copyPartnerCode() {
            const partnerCode = '{{ $myPartner->partner_code }}';
            navigator.clipboard.writeText(partnerCode).then(function() {
                showFloatingAlert('success', `파트너 코드가 복사되었습니다: ${partnerCode}`);
            }).catch(function() {
                // 폴백: 텍스트 선택
                const textArea = document.createElement('textarea');
                textArea.value = partnerCode;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showFloatingAlert('success', `파트너 코드가 복사되었습니다: ${partnerCode}`);
            });
        }

        // 스피너 애니메이션 CSS 추가
        const style = document.createElement('style');
        style.textContent = `
    .spin-animate {
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
`;
        document.head.appendChild(style);

        // 승인 데이터 유효성 검사 함수
        function validateApprovalData(assignedTier, commissionRate) {
            const currentTierLevel = currentPartner.tierLevel[currentPartner.tier] || 1;

            // 1. 등급 검증
            if (assignedTier) {
                const assignedTierLevel = currentPartner.tierLevel[assignedTier] || 1;
                if (assignedTierLevel > currentTierLevel) {
                    return `자신의 등급(${currentPartner.tier})보다 높은 등급(${assignedTier})은 부여할 수 없습니다.`;
                }
            }

            // 2. 커미션 비율 검증
            if (commissionRate && parseFloat(commissionRate) > currentPartner.commissionRate) {
                return `자신의 커미션 비율(${currentPartner.commissionRate}%)보다 높은 비율(${commissionRate}%)은 부여할 수 없습니다.`;
            }

            return null; // 유효성 검사 통과
        }
    </script>
@endpush
