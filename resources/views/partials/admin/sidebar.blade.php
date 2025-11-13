<!-- Sidebar -->
<style>
.navbar-heading {
    color: #8a94a6 !important;
    font-weight: 600 !important;
    font-size: 0.75rem !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    margin-bottom: 0.5rem !important;
    padding: 0.75rem 1.5rem 0.25rem 1.5rem !important;
}

.navbar-vertical .navbar-nav .navbar-heading:not(:first-child) {
    margin-top: 2rem !important;
}
</style>

<nav class="navbar-vertical navbar">
    <div class="vh-100" data-simplebar>
        <!-- Brand logo -->
        <a class="navbar-brand" href="/">
            <img src="{{ asset('assets/images/brand/logo/logo-inverse.svg') }}" alt="Jiny" />
        </a>

        <!-- Navbar nav -->
        <ul class="navbar-nav flex-column" id="sideNavbar">

            {{-- ============================================
                대시보드
            ============================================ --}}
            <li class="nav-item">
                <a class="nav-link" href="/admin/partner">
                    <i class="nav-icon fe fe-home me-2"></i>
                    대시보드
                </a>
            </li>

            <li class="nav-item">
                <div class="nav-divider"></div>
            </li>

            {{-- ============================================
                실적 관리
            ============================================ --}}
            <li class="nav-item">
                <div class="navbar-heading">실적 관리</div>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.partner.sales.index') }}">
                    <i class="nav-icon fe fe-folder me-2"></i>
                    실적등록
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.partner.network.commission.index') }}">
                    <i class="nav-icon fe fe-dollar-sign me-2"></i>
                    커미션 관리
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.partner.network.analytics.performance') }}">
                    <i class="fe fe-activity me-2"></i>
                    성과 분석
                </a>
            </li>

            {{-- ============================================
                파트너 관리
            ============================================ --}}
            <li class="nav-item">
                <div class="navbar-heading">파트너 관리</div>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.partner.partner-dashboard') }}">
                    <i class="nav-icon fe fe-pie-chart me-2"></i>
                    파트너 현황
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.partner.users.index') }}">
                    <i class="nav-icon fe fe-users me-2"></i>
                    파트너 회원
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.partner.tiers.index') }}">
                    <i class="nav-icon fe fe-award me-2"></i>
                    파트너 등급
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.partner.type.index') }}">
                    <i class="nav-icon fe fe-tag me-2"></i>
                    파트너 타입
                </a>
            </li>



            <li class="nav-item">
                <div class="navbar-heading">승인 관리</div>
            </li>

             <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.partner.codes.index') }}">
                    <i class="nav-icon fe fe-hash me-2"></i>
                    파트너 코드
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.partner.applications.index') }}">
                    <i class="nav-icon fe fe-file-text me-2"></i>
                    파트너 지원서
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.partner.approval.index') }}">
                    <i class="nav-icon fe fe-check-circle me-2"></i>
                    파트너 승인
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.partner.interview.index') }}">
                    <i class="nav-icon fe fe-calendar me-2"></i>
                    파트너 면접
                </a>
            </li>

            {{-- ============================================
                네트워크 관리
            ============================================ --}}
            <li class="nav-item">
                <div class="navbar-heading">네트워크 관리</div>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.partner.network.analytics.dashboard') }}">
                    <i class="fe fe-trending-up me-2"></i>
                    분석 대시보드
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.partner.network.tree') }}">
                    <i class="nav-icon fe fe-git-branch me-2"></i>
                    네트워크 트리
                </a>
            </li>

        </ul>
    </div>
</nav>
