@extends('jiny-partner::layouts.home')

@section('title', $pageTitle)

@section('content')
<div class="container-fluid mt-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="text-dark fw-bold mb-1">{{ $partnerDetail['basic_info']['name'] }}</h2>
                    <p class="text-muted mb-0">파트너 상세 정보</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('home.partner.network.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>네트워크로 돌아가기
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Partner Basic Info -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card border shadow-sm bg-white h-100">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-person-circle me-2 text-primary"></i>기본 정보
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">파트너명</label>
                                <div class="d-flex align-items-center gap-2">
                                    <h6 class="mb-0 text-dark">{{ $partnerDetail['basic_info']['name'] }}</h6>
                                    @php
                                        $tierClass = match($partnerDetail['basic_info']['tier_name']) {
                                            'Diamond' => 'bg-primary',
                                            'Platinum' => 'bg-secondary',
                                            'Gold' => 'bg-warning',
                                            'Silver' => 'bg-info',
                                            default => 'bg-success'
                                        };
                                        $statusClass = $partnerDetail['basic_info']['status'] === 'active' ? 'success' : 'danger';
                                    @endphp
                                    <span class="badge {{ $tierClass }} text-white">{{ $partnerDetail['basic_info']['tier_name'] }}</span>
                                    <span class="badge bg-{{ $statusClass }}">{{ ucfirst($partnerDetail['basic_info']['status']) }}</span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">이메일</label>
                                <div class="text-dark">
                                    <i class="bi bi-envelope me-2 text-muted"></i>{{ $partnerDetail['basic_info']['email'] }}
                                </div>
                            </div>

                            @if($partnerDetail['basic_info']['partner_code'])
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">파트너 코드</label>
                                <div class="text-dark">
                                    <i class="bi bi-qr-code me-2 text-muted"></i>{{ $partnerDetail['basic_info']['partner_code'] }}
                                </div>
                            </div>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">가입일</label>
                                <div class="text-dark">
                                    <i class="bi bi-calendar me-2 text-muted"></i>
                                    {{ $partnerDetail['basic_info']['joined_at'] ? \Carbon\Carbon::parse($partnerDetail['basic_info']['joined_at'])->format('Y-m-d H:i') : '-' }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">네트워크 레벨</label>
                                <div class="text-dark">
                                    <i class="bi bi-diagram-3 me-2 text-muted"></i>Level {{ $partnerDetail['basic_info']['level'] }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-muted small fw-bold">최근 활동</label>
                                <div class="text-dark">
                                    <i class="bi bi-clock me-2 text-muted"></i>{{ $partnerDetail['basic_info']['last_activity'] }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border shadow-sm bg-white h-100">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-graph-up me-2 text-success"></i>성과 요약
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3 text-center">
                        <div class="h4 mb-1 fw-bold text-success">{{ number_format($partnerDetail['performance_info']['total_sales']) }}원</div>
                        <small class="text-muted">총 매출</small>
                    </div>

                    <div class="mb-3 text-center">
                        <div class="h5 mb-1 fw-bold text-info">{{ number_format($partnerDetail['performance_info']['monthly_sales']) }}원</div>
                        <small class="text-muted">월 매출</small>
                    </div>

                    <div class="mb-3 text-center">
                        <div class="h5 mb-1 fw-bold text-warning">{{ number_format($partnerDetail['performance_info']['earned_commissions']) }}원</div>
                        <small class="text-muted">수익 커미션</small>
                    </div>

                    <hr>

                    <div class="row text-center">
                        <div class="col-6">
                            <div class="h6 mb-0 fw-bold text-primary">{{ $partnerDetail['performance_info']['total_completed_jobs'] }}</div>
                            <small class="text-muted">완료 업무</small>
                        </div>
                        <div class="col-6">
                            <div class="h6 mb-0 fw-bold text-secondary">{{ number_format($partnerDetail['performance_info']['average_rating'], 1) }}</div>
                            <small class="text-muted">평균 평점</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Parent Partner Info -->
    @if($parentPartner)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border shadow-sm bg-white">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-arrow-up-circle me-2 text-primary"></i>상위 파트너
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar-circle bg-primary text-white">
                                {{ mb_substr($parentPartner->name, 0, 1, 'UTF-8') }}
                            </div>
                            <div>
                                <h6 class="mb-1 fw-bold text-dark">{{ $parentPartner->name }}</h6>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    @php
                                        $parentTierClass = match($parentPartner->partnerTier->tier_name ?? 'Bronze') {
                                            'Diamond' => 'bg-primary',
                                            'Platinum' => 'bg-secondary',
                                            'Gold' => 'bg-warning',
                                            'Silver' => 'bg-info',
                                            default => 'bg-success'
                                        };
                                    @endphp
                                    <span class="badge {{ $parentTierClass }} text-white">{{ $parentPartner->partnerTier->tier_name ?? 'Bronze' }}</span>
                                    <span class="badge bg-success">{{ ucfirst($parentPartner->status) }}</span>
                                </div>
                                <div class="text-muted small">
                                    <div><i class="bi bi-envelope me-1"></i>{{ $parentPartner->email }}</div>
                                    @if($parentPartner->partner_code)
                                    <div><i class="bi bi-qr-code me-1"></i>{{ $parentPartner->partner_code }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-3">
                            <div class="text-end">
                                <div class="text-muted small">가입일</div>
                                <div class="fw-bold">{{ $parentPartner->partner_joined_at ? \Carbon\Carbon::parse($parentPartner->partner_joined_at)->format('Y-m-d') : '-' }}</div>
                            </div>
                            <div class="text-end">
                                <div class="text-muted small">총 매출</div>
                                <div class="fw-bold text-success">{{ number_format($parentPartner->total_sales ?? 0) }}원</div>
                            </div>
                            <button type="button" class="btn btn-primary" onclick="viewPartnerDetail({{ $parentPartner->id }})">
                                <i class="bi bi-arrow-up-circle me-1"></i>상위 파트너로 이동
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Network & Activity -->
    <div class="row mb-4">
        <!-- Network Info -->
        <div class="col-md-6">
            <div class="card border shadow-sm bg-white h-100">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-diagram-2 me-2 text-info"></i>네트워크 정보
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="h4 mb-1 fw-bold text-primary">{{ $partnerDetail['network_info']['direct_children'] }}</div>
                                <small class="text-muted">직접 하위 파트너</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="h4 mb-1 fw-bold text-success">{{ $partnerDetail['network_info']['total_children'] }}</div>
                                <small class="text-muted">전체 하위 파트너</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="h5 mb-1 fw-bold text-info">{{ $partnerDetail['network_info']['max_network_depth'] }}</div>
                                <small class="text-muted">최대 네트워크 깊이</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="h6 mb-1 fw-bold text-{{ $partnerDetail['network_info']['can_recruit'] ? 'success' : 'danger' }}">
                                    {{ $partnerDetail['network_info']['can_recruit'] ? '활성' : '비활성' }}
                                </div>
                                <small class="text-muted">모집 권한</small>
                            </div>
                        </div>
                    </div>

                    @if($partnerDetail['referral_info'])
                    <hr>
                    <div>
                        <h6 class="fw-bold text-dark mb-2">추천인 정보</h6>
                        <div class="small text-muted">
                            <div><strong>추천인:</strong> {{ $partnerDetail['referral_info']['referrer_name'] ?? '-' }}</div>
                            <div><strong>추천 코드:</strong> {{ $partnerDetail['referral_info']['referrer_code'] ?? '-' }}</div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Activity History -->
        <div class="col-md-6">
            <div class="card border shadow-sm bg-white h-100">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-clock-history me-2 text-warning"></i>최근 활동 이력
                    </h5>
                </div>
                <div class="card-body p-4">
                    @if(count($activityHistory) > 0)
                        <div class="timeline">
                            @foreach($activityHistory as $activity)
                            <div class="timeline-item d-flex align-items-start mb-3">
                                <div class="timeline-marker me-3">
                                    <i class="bi {{ $activity['icon'] }} text-{{ $activity['color'] }}"></i>
                                </div>
                                <div class="timeline-content flex-grow-1">
                                    <h6 class="mb-1 fw-bold text-dark">{{ $activity['title'] }}</h6>
                                    <p class="mb-1 small text-muted">{{ $activity['description'] }}</p>
                                    <small class="text-muted">
                                        <i class="bi bi-clock me-1"></i>{{ $activity['date']->diffForHumans() }}
                                    </small>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-clock-history display-4 text-muted opacity-50"></i>
                            <h6 class="mt-3 text-muted">활동 이력이 없습니다</h6>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Sub Network -->
    @if(count($subNetwork) > 0)
    <div class="row">
        <div class="col-12">
            <div class="card border shadow-sm bg-white">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-people me-2 text-primary"></i>직접 하위 파트너 ({{ count($subNetwork) }}명)
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        @foreach($subNetwork as $subPartner)
                        <div class="col-md-6 col-lg-4">
                            <div class="card border hover-shadow h-100">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1 fw-bold text-dark">{{ $subPartner['name'] }}</h6>
                                            <small class="text-muted">{{ $subPartner['email'] }}</small>
                                        </div>
                                        <span class="badge bg-success">{{ $subPartner['tier_name'] }}</span>
                                    </div>

                                    <div class="mb-2 small text-muted">
                                        @if($subPartner['partner_code'])
                                        <div><i class="bi bi-qr-code me-1"></i>{{ $subPartner['partner_code'] }}</div>
                                        @endif
                                        <div><i class="bi bi-calendar me-1"></i>{{ \Carbon\Carbon::parse($subPartner['joined_at'])->format('Y-m-d') }}</div>
                                        @if($subPartner['children_count'] > 0)
                                        <div><i class="bi bi-people me-1"></i>하위 {{ $subPartner['children_count'] }}명</div>
                                        @endif
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-success fw-bold">{{ number_format($subPartner['total_sales']) }}원</small>
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="viewPartnerDetail({{ $subPartner['id'] }})">
                                            <i class="bi bi-eye me-1"></i>상세
                                        </button>
                                    </div>
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
</div>

<style>
.hover-shadow {
    transition: all 0.3s ease;
}

.hover-shadow:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.timeline-item {
    position: relative;
}

.timeline-marker {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: white;
    border: 2px solid #dee2e6;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.avatar-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
    flex-shrink: 0;
}
</style>

@endsection

@push('scripts')
<script>
// Partner detail view function
window.viewPartnerDetail = function(partnerId) {
    window.location.href = `/home/partner/network/${partnerId}`;
};
</script>
@endpush