{{-- Chevron rotation styles for collapse menus --}}
@push('styles')
<style>
.collapse-icon {
    transition: transform 0.2s ease-in-out;
}

.nav-link.collapsed .collapse-icon {
    transform: rotate(0deg);
}

.nav-link:not(.collapsed) .collapse-icon {
    transform: rotate(180deg);
}
</style>
@endpush

<li class="nav-item">
    <div class="navbar-heading">파트너 시스템</div>
</li>

{{-- 파트너 대시보드 --}}
<li class="nav-item">
    <a class="nav-link" href="{{ route('home.partner.index') }}">
        <i class="nav-icon fe fe-home me-2"></i>
        파트너 대시보드
    </a>
</li>

{{-- 파트너 등록 관리 --}}
<li class="nav-item">
    <a class="nav-link collapsed d-flex justify-content-between align-items-center"
        href="#" data-bs-toggle="collapse"
        data-bs-target="#navPartnerRegist"
        aria-expanded="false" aria-controls="navPartnerRegist">
        <div class="d-flex align-items-center">
            <i class="nav-icon fe fe-user-plus me-2"></i>
            파트너 등록
        </div>
        <i class="fe fe-chevron-down collapse-icon"></i>
    </a>
    <div id="navPartnerRegist" class="collapse" data-bs-parent="#sidenavNavbar">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('home.partner.regist.index') }}">
                    <i class="fe fe-list me-2"></i>등록 현황
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('home.partner.regist.create') }}">
                    <i class="fe fe-plus-circle me-2"></i>신규 등록
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('home.partner.regist.status') }}">
                    <i class="fe fe-check-square me-2"></i>상태 확인
                </a>
            </li>
        </ul>
    </div>
</li>

{{-- 판매 관리 --}}
<li class="nav-item">
    <a class="nav-link collapsed d-flex justify-content-between align-items-center" href="#" data-bs-toggle="collapse" data-bs-target="#navPartnerSales"
        aria-expanded="false" aria-controls="navPartnerSales">
        <div class="d-flex align-items-center">
            <i class="nav-icon fe fe-shopping-cart me-2"></i>
            판매 관리
        </div>
        <i class="fe fe-chevron-down collapse-icon"></i>
    </a>
    <div id="navPartnerSales" class="collapse" data-bs-parent="#sidenavNavbar">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('home.partner.sales.index') }}">
                    <i class="fe fe-pie-chart me-2"></i>판매 대시보드
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('home.partner.sales.history') }}">
                    <i class="fe fe-clock me-2"></i>판매 이력
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('home.partner.sales.statistics') }}">
                    <i class="fe fe-bar-chart-2 me-2"></i>판매 통계
                </a>
            </li>
        </ul>
    </div>
</li>

{{-- 커미션 관리 --}}
<li class="nav-item">
    <a class="nav-link collapsed d-flex justify-content-between align-items-center" href="#" data-bs-toggle="collapse" data-bs-target="#navPartnerCommission"
        aria-expanded="false" aria-controls="navPartnerCommission">
        <div class="d-flex align-items-center">
            <i class="nav-icon fe fe-dollar-sign me-2"></i>
            커미션 관리
        </div>
        <i class="fe fe-chevron-down collapse-icon"></i>
    </a>
    <div id="navPartnerCommission" class="collapse" data-bs-parent="#sidenavNavbar">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('home.partner.commission.index') }}">
                    <i class="fe fe-trending-up me-2"></i>커미션 현황
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('home.partner.commission.history') }}">
                    <i class="fe fe-list me-2"></i>커미션 이력
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('home.partner.commission.calculate') }}">
                    <i class="fe fe-calculator me-2"></i>수익 계산기
                </a>
            </li>
        </ul>
    </div>
</li>

{{-- 승인 관리 --}}
<li class="nav-item">
    <a class="nav-link collapsed d-flex justify-content-between align-items-center" href="#" data-bs-toggle="collapse" data-bs-target="#navPartnerApproval"
        aria-expanded="false" aria-controls="navPartnerApproval">
        <div class="d-flex align-items-center">
            <i class="nav-icon fe fe-check-circle me-2"></i>
            승인 관리
        </div>
        <i class="fe fe-chevron-down collapse-icon"></i>
    </a>
    <div id="navPartnerApproval" class="collapse" data-bs-parent="#sidenavNavbar">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('home.partner.approval.index') }}">
                    <i class="fe fe-clipboard me-2"></i>승인 대시보드
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('home.partner.approval.pending') }}">
                    <i class="fe fe-clock me-2"></i>승인 대기
                    @php
                        try {
                            // 현재 사용자의 승인 권한 범위 내 대기 중인 신청서 수 체크
                            $pendingApprovals = \Jiny\Partner\Models\PartnerApplication::whereIn('application_status', [
                                'submitted',
                                'reviewing',
                            ])->count();
                            if ($pendingApprovals > 0) {
                                echo '<span class="badge bg-warning ms-2">' . $pendingApprovals . '</span>';
                            }
                        } catch (\Exception $e) {
                            // 테이블이 존재하지 않는 경우 무시
                        }
                    @endphp
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('home.partner.approval.referrals') }}">
                    <i class="fe fe-users me-2"></i>추천 관리
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('home.partner.approval.limits') }}">
                    <i class="fe fe-shield me-2"></i>승인 한도
                </a>
            </li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link" href="{{ route('home.partner.network.index') }}">
        하위관리
    </a>
</li>

{{-- 리뷰 관리 --}}
<li class="nav-item">
    <a class="nav-link collapsed d-flex justify-content-between align-items-center" href="#" data-bs-toggle="collapse" data-bs-target="#navPartnerReviews"
        aria-expanded="false" aria-controls="navPartnerReviews">
        <div class="d-flex align-items-center">
            <i class="nav-icon fe fe-star me-2"></i>
            리뷰 관리
        </div>
        <i class="fe fe-chevron-down collapse-icon"></i>
    </a>
    <div id="navPartnerReviews" class="collapse" data-bs-parent="#sidenavNavbar">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('home.partner.reviews.index') }}">
                    <i class="fe fe-eye me-2"></i>리뷰 현황
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('home.partner.reviews.received') }}">
                    <i class="fe fe-inbox me-2"></i>받은 리뷰
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('home.partner.reviews.given') }}">
                    <i class="fe fe-send me-2"></i>작성한 리뷰
                </a>
            </li>
        </ul>
    </div>
</li>

{{-- 추천인 검색 --}}
<li class="nav-item">
    <a class="nav-link" href="{{ route('home.partner.search.referrer') }}">
        <i class="fe fe-search me-2"></i>추천인 검색
    </a>
</li>
