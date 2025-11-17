@extends('jiny-partner::layouts.www')

@section('title', '파트너 시스템 - Jiny Partners')

@push('styles')
<style>
    .hero-section {
        padding: 100px 0;
    }
    .feature-card {
        transition: transform 0.3s ease;
        border: none;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .feature-card:hover {
        transform: translateY(-5px);
    }
    .stats-section {
        background: #f8f9fa;
        padding: 80px 0;
    }
    .stats-item {
        text-align: center;
        padding: 20px;
    }
    .stats-number {
        font-size: 3rem;
        font-weight: bold;
        color: #667eea;
    }
</style>
@endpush

@section('content')

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">
                        함께 성장하는<br>
                        <span class="text-warning">파트너 생태계</span>
                    </h1>
                    <p class="lead mb-4">
                        Jiny Partners는 혁신적인 파트너 관리 시스템으로 다양한 파트너들과 협업하여 매출을 성장시키고,
                        다양한 마케팅 활동으로 새로운 시장을 동료와 함께 개척해 나갈 수 있습니다.
                        체계적인 등급 시스템, 투명한 성과 관리, 그리고 공정한 수익 분배로 지속 가능한 파트너십을 구축하세요.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="{{ route('partner.www.application') }}" class="btn btn-warning btn-lg px-4">
                            <i class="bi bi-rocket-takeoff me-2"></i>지금 시작하기
                        </a>
                        <a href="{{ route('partner.www.about') }}" class="btn btn-outline-light btn-lg px-4">
                            <i class="bi bi-play-circle me-2"></i>자세히 알아보기
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <div class="hero-image">
                        <i class="bi bi-diagram-3" style="font-size: 15rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6">
                    <div class="stats-item">
                        <div class="stats-number">1,500+</div>
                        <h5>활성 파트너</h5>
                        <p class="text-muted">전국 각지의 파트너들이 함께하고 있습니다</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stats-item">
                        <div class="stats-number">98%</div>
                        <h5>만족도</h5>
                        <p class="text-muted">파트너들의 높은 만족도를 자랑합니다</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stats-item">
                        <div class="stats-number">24/7</div>
                        <h5>지원 시스템</h5>
                        <p class="text-muted">언제나 도움이 필요할 때 지원합니다</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stats-item">
                        <div class="stats-number">5년+</div>
                        <h5>운영 경험</h5>
                        <p class="text-muted">검증된 시스템과 풍부한 경험</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h2 class="display-5 fw-bold mb-3">왜 Jiny Partners를 선택해야 할까요?</h2>
                    <p class="lead text-muted">차별화된 파트너 시스템의 핵심 기능들을 소개합니다</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="card feature-card h-100 p-4">
                        <div class="text-center mb-4">
                            <i class="bi bi-trophy text-warning" style="font-size: 3rem;"></i>
                        </div>
                        <h4 class="card-title text-center mb-3">체계적인 등급 시스템</h4>
                        <p class="card-text text-muted">
                            성과에 따른 명확한 등급 체계로 성장 경로를 제시하고,
                            각 등급별로 차별화된 혜택과 지원을 제공합니다.
                        </p>
                        <div class="mt-auto">
                            <a href="{{ route('partner.www.tiers') }}" class="btn btn-outline-primary w-100">
                                자세히 보기 <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="card feature-card h-100 p-4">
                        <div class="text-center mb-4">
                            <i class="bi bi-graph-up-arrow text-success" style="font-size: 3rem;"></i>
                        </div>
                        <h4 class="card-title text-center mb-3">실시간 성과 추적</h4>
                        <p class="card-text text-muted">
                            개인 맞춤 목표 설정부터 실시간 진행률 추적까지,
                            데이터 기반의 투명한 성과 관리 시스템을 제공합니다.
                        </p>
                        <div class="mt-auto">
                            <a href="{{ route('partner.www.performance') }}" class="btn btn-outline-primary w-100">
                                자세히 보기 <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="card feature-card h-100 p-4">
                        <div class="text-center mb-4">
                            <i class="bi bi-cash-coin text-info" style="font-size: 3rem;"></i>
                        </div>
                        <h4 class="card-title text-center mb-3">투명한 수익 구조</h4>
                        <p class="card-text text-muted">
                            명확한 커미션 체계와 공정한 수익 분배로
                            파트너의 노력에 합당한 보상을 제공합니다.
                        </p>
                        <div class="mt-auto">
                            <a href="{{ route('partner.www.commission') }}" class="btn btn-outline-primary w-100">
                                자세히 보기 <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="card feature-card h-100 p-4">
                        <div class="text-center mb-4">
                            <i class="bi bi-people text-primary" style="font-size: 3rem;"></i>
                        </div>
                        <h4 class="card-title text-center mb-3">협업 네트워크 관리</h4>
                        <p class="card-text text-muted">
                            지인의 도움과 소개를 통한 협업 시스템으로 영업을 지원하고,
                            지정한 비율에 따라서 파트너의 이익을 자동으로 분배합니다.
                        </p>
                        <div class="mt-auto">
                            <a href="{{ route('partner.www.network') }}" class="btn btn-outline-primary w-100">
                                자세히 보기 <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="card feature-card h-100 p-4">
                        <div class="text-center mb-4">
                            <i class="bi bi-bullseye text-danger" style="font-size: 3rem;"></i>
                        </div>
                        <h4 class="card-title text-center mb-3">동적 목표 관리</h4>
                        <p class="card-text text-muted">
                            개인별 맞춤 목표 설정과 유연한 조정 시스템으로
                            현실적이고 도전적인 목표를 제시합니다.
                        </p>
                        <div class="mt-auto">
                            <a href="{{ route('partner.www.targets') }}" class="btn btn-outline-primary w-100">
                                자세히 보기 <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="card feature-card h-100 p-4">
                        <div class="text-center mb-4">
                            <i class="bi bi-headset text-secondary" style="font-size: 3rem;"></i>
                        </div>
                        <h4 class="card-title text-center mb-3">전문 지원 서비스</h4>
                        <p class="card-text text-muted">
                            체계적인 면접 평가부터 지속적인 교육과 지원까지,
                            파트너의 성공을 위한 전문적인 서비스를 제공합니다.
                        </p>
                        <div class="mt-auto">
                            <a href="{{ route('partner.www.interview') }}" class="btn btn-outline-primary w-100">
                                자세히 보기 <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8">
                    <h2 class="display-5 fw-bold mb-4">지금 바로 파트너가 되어보세요!</h2>
                    <p class="lead text-muted mb-5">
                        혁신적인 파트너 시스템과 함께 새로운 기회를 만들어보세요.
                        추천회원 시스템을 통한 신뢰할 수 있는 파트너십과 체계적인 관리로 여러분의 성공을 돕겠습니다.
                    </p>
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <a href="{{ route('partner.www.application') }}" class="btn btn-primary-custom btn-lg px-5">
                            <i class="bi bi-file-earmark-text me-2"></i>지원서 작성
                        </a>
                        <a href="{{ route('partner.www.contact') }}" class="btn btn-outline-primary btn-lg px-5">
                            <i class="bi bi-telephone me-2"></i>상담 신청
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection