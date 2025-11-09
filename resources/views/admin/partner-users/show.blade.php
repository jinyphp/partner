@extends('jiny-partner::layouts.admin.sidebar')

@section('title', $title . ' 상세보기')

@section('content')
<div class="container-fluid">
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
                                    <td class="text-muted">상태:</td>
                                    <td>
                                        @if($item->status === 'active')
                                            <span class="badge bg-success fs-6">활성</span>
                                        @elseif($item->status === 'pending')
                                            <span class="badge bg-warning fs-6">대기</span>
                                        @elseif($item->status === 'suspended')
                                            <span class="badge bg-danger fs-6">정지</span>
                                        @else
                                            <span class="badge bg-secondary fs-6">비활성</span>
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

            <!-- 매출 및 커미션 정보 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">매출 및 커미션 정보</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- 개인 매출 -->
                        <div class="col-md-3">
                            <a href="{{ route('admin.partner.sales.index', ['partner_id' => $item->id]) }}"
                               class="text-decoration-none">
                                <div class="text-center p-3 border rounded sales-card-hover">
                                    <div class="display-6 fw-bold text-success mb-2">
                                        {{ number_format($item->monthly_sales) }}
                                    </div>
                                    <div class="text-muted">개인 매출</div>
                                    <small class="text-muted d-block">이번 달</small>
                                    <small class="text-primary d-block mt-1">
                                        <i class="fe fe-external-link me-1"></i>매출 내역 보기
                                    </small>
                                </div>
                            </a>
                        </div>

                        <!-- 팀 매출 -->
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded">
                                <div class="display-6 fw-bold text-info mb-2">
                                    @php
                                        $teamSales = $item->team_sales ?? ($item->monthly_sales +
                                            ($item->children ? $item->children->sum('monthly_sales') : 0));
                                    @endphp
                                    {{ number_format($teamSales) }}
                                </div>
                                <div class="text-muted">팀 매출</div>
                                <small class="text-muted d-block">이번 달</small>
                            </div>
                        </div>

                        <!-- 획득 커미션 -->
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded">
                                <div class="display-6 fw-bold text-warning mb-2">
                                    {{ number_format($item->earned_commissions) }}
                                </div>
                                <div class="text-muted">획득 커미션</div>
                                <small class="text-muted d-block">이번 달</small>
                            </div>
                        </div>

                        <!-- 커미션 비율 -->
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded">
                                <div class="display-6 fw-bold text-primary mb-2">
                                    {{ $item->partnerTier ? $item->partnerTier->commission_rate : 0 }}%
                                </div>
                                <div class="text-muted">커미션 비율</div>
                                <small class="text-muted d-block">{{ $item->partnerTier ? $item->partnerTier->tier_name : 'N/A' }}</small>
                            </div>
                        </div>
                    </div>

                    <!-- 매출 성과 차트 (간단한 막대 그래프) -->
                    @if($item->monthly_sales > 0)
                    <div class="mt-4">
                        <h6 class="text-primary mb-3">매출 성과 분석</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">개인 매출 기여도</span>
                                        @php
                                            $personalContribution = $teamSales > 0 ? round(($item->monthly_sales / $teamSales) * 100, 1) : 100;
                                        @endphp
                                        <span class="fw-bold">{{ $personalContribution }}%</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" style="width: {{ $personalContribution }}%"></div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">커미션 수익률</span>
                                        @php
                                            $commissionRate = $item->monthly_sales > 0 ? round(($item->earned_commissions / $item->monthly_sales) * 100, 1) : 0;
                                        @endphp
                                        <span class="fw-bold">{{ $commissionRate }}%</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-warning" style="width: {{ min($commissionRate, 100) }}%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="bg-light p-3 rounded">
                                    <h6 class="text-dark mb-2">월간 요약</h6>
                                    <ul class="list-unstyled mb-0">
                                        <li class="d-flex justify-content-between">
                                            <span>총 매출:</span>
                                            <strong class="text-success">{{ number_format($teamSales) }}원</strong>
                                        </li>
                                        <li class="d-flex justify-content-between">
                                            <span>커미션:</span>
                                            <strong class="text-warning">{{ number_format($item->earned_commissions) }}원</strong>
                                        </li>
                                        @if($item->children_count > 0)
                                        <li class="d-flex justify-content-between">
                                            <span>하위 파트너:</span>
                                            <strong class="text-info">{{ $item->children_count }}명</strong>
                                        </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="mt-4 text-center">
                        <div class="bg-light p-4 rounded">
                            <i class="fe fe-bar-chart-2 fs-1 text-muted mb-2"></i>
                            <p class="text-muted mb-0">아직 등록된 매출이 없습니다.</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

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

<!-- 상태 변경 모달 -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">상태 변경</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="statusForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <input type="hidden" id="new_status" name="status">
                    <div class="mb-3">
                        <label for="status_reason" class="form-label">변경 사유</label>
                        <textarea class="form-control" id="status_reason" name="status_reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">변경</button>
                </div>
            </form>
        </div>
    </div>
</div>

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
// 상태 변경
function changeStatus(newStatus) {
    const modal = new bootstrap.Modal(document.getElementById('statusModal'));
    const form = document.getElementById('statusForm');
    const newStatusInput = document.getElementById('new_status');

    // 폼 액션 설정
    form.action = `{{ route('admin.' . $routePrefix . '.update', $item->id) }}`;
    newStatusInput.value = newStatus;

    // 모달 제목 변경
    const modalTitle = document.querySelector('#statusModal .modal-title');
    switch(newStatus) {
        case 'active':
            modalTitle.textContent = '상태를 활성으로 변경';
            break;
        case 'suspended':
            modalTitle.textContent = '상태를 정지로 변경';
            break;
        case 'inactive':
            modalTitle.textContent = '상태를 비활성으로 변경';
            break;
    }

    modal.show();
}

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