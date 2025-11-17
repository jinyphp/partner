@extends('jiny-partner::layouts.www')

@section('title', '파트너 소개 - Jiny Partners')

@push('styles')
<style>
    .hero-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 80px 0;
    }
    .feature-card {
        transition: transform 0.3s ease;
        border: none;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        border-radius: 15px;
    }
    .feature-card:hover {
        transform: translateY(-5px);
    }
    .section-title {
        position: relative;
        padding-bottom: 20px;
    }
    .section-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 3px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .process-card {
        background: white;
        border-radius: 15px;
        padding: 30px;
        text-align: center;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        position: relative;
    }
    .process-number {
        position: absolute;
        top: -20px;
        left: 50%;
        transform: translateX(-50%);
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
    }
    .benefit-item {
        padding: 20px;
        text-align: center;
        border-radius: 10px;
        background: #f8f9fa;
        height: 100%;
    }
    .requirement-card {
        background: white;
        border-left: 4px solid #667eea;
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 0 10px 10px 0;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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
                        Jiny Partners와 함께<br>
                        <span class="text-warning">새로운 기회</span>를 만나세요
                    </h1>
                    <p class="lead mb-4">
                        체계적인 관리 시스템과 투명한 수익 구조로
                        여러분의 성공을 함께 만들어갑니다.
                    </p>
                </div>
                <div class="col-lg-6 text-center">
                    <i class="bi bi-person-workspace" style="font-size: 12rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- What is Partner Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold section-title">Jiny Partners란?</h2>
                    <p class="lead text-muted mt-4">
                        혁신적인 파트너 관리 시스템으로 개인과 기업의 성장을 지원하는 통합 플랫폼입니다
                    </p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="feature-card p-4 h-100">
                        <div class="text-center mb-4">
                            <i class="bi bi-diagram-3-fill text-primary" style="font-size: 3rem;"></i>
                        </div>
                        <h4 class="text-center mb-3">체계적인 조직 관리</h4>
                        <p class="text-muted">
                            MLM 기반의 다단계 조직 구조로 추천인-하위 파트너 관계를 명확히 하고,
                            팀 빌딩과 조직 확장을 체계적으로 관리합니다.
                        </p>
                        <ul class="list-unstyled">
                            <li><i class="bi bi-check-circle text-success me-2"></i>추천인 관계 관리</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>조직 트리 구조</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>팀 성과 통합</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="feature-card p-4 h-100">
                        <div class="text-center mb-4">
                            <i class="bi bi-person-badge-fill text-success" style="font-size: 3rem;"></i>
                        </div>
                        <h4 class="text-center mb-3">통합 프로필 관리</h4>
                        <p class="text-muted">
                            파트너의 기본 정보부터 비즈니스 정보, 계약 조건까지
                            모든 정보를 체계적으로 관리하는 통합 시스템입니다.
                        </p>
                        <ul class="list-unstyled">
                            <li><i class="bi bi-check-circle text-success me-2"></i>개인/법인 정보 관리</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>비즈니스 정보 등록</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>계약 조건 관리</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="feature-card p-4 h-100">
                        <div class="text-center mb-4">
                            <i class="bi bi-graph-up text-warning" style="font-size: 3rem;"></i>
                        </div>
                        <h4 class="text-center mb-3">실시간 성과 추적</h4>
                        <p class="text-muted">
                            월별/연도별 매출 실적과 팀 성과를 실시간으로 추적하고,
                            목표 대비 달성률을 투명하게 관리합니다.
                        </p>
                        <ul class="list-unstyled">
                            <li><i class="bi bi-check-circle text-success me-2"></i>매출 성과 추적</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>팀 통합 성과</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>목표 달성률 관리</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How to Become Partner Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold section-title">파트너가 되는 방법</h2>
                    <p class="lead text-muted mt-4">
                        간단한 절차를 통해 Jiny Partners의 일원이 될 수 있습니다
                    </p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-3">
                    <div class="process-card">
                        <div class="process-number">1</div>
                        <i class="bi bi-file-earmark-text text-primary mb-3" style="font-size: 2.5rem;"></i>
                        <h5>지원서 작성</h5>
                        <p class="text-muted">
                            온라인 지원서를 통해 기본 정보와 사업 계획을 제출합니다.
                        </p>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="process-card">
                        <div class="process-number">2</div>
                        <i class="bi bi-search text-success mb-3" style="font-size: 2.5rem;"></i>
                        <h5>서류 검토</h5>
                        <p class="text-muted">
                            제출된 서류를 검토하고 파트너 자격을 확인합니다.
                        </p>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="process-card">
                        <div class="process-number">3</div>
                        <i class="bi bi-person-video2 text-warning mb-3" style="font-size: 2.5rem;"></i>
                        <h5>면접 진행</h5>
                        <p class="text-muted">
                            체계적인 면접을 통해 역량과 적합성을 평가합니다.
                        </p>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="process-card">
                        <div class="process-number">4</div>
                        <i class="bi bi-award text-info mb-3" style="font-size: 2.5rem;"></i>
                        <h5>파트너 승인</h5>
                        <p class="text-muted">
                            최종 승인 후 파트너 활동을 시작할 수 있습니다.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Partner Types Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold section-title">파트너 유형</h2>
                    <p class="lead text-muted mt-4">
                        다양한 형태의 파트너십을 통해 여러분에게 맞는 방식을 선택하세요
                    </p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="feature-card p-4 h-100">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-person text-primary me-3" style="font-size: 2.5rem;"></i>
                            <div>
                                <h4>개인 파트너</h4>
                                <small class="text-muted">Individual Partner</small>
                            </div>
                        </div>
                        <p class="text-muted mb-3">
                            개인 자격으로 참여하는 파트너로, 개인의 역량과 네트워크를 활용하여
                            사업을 확장하고 수익을 창출합니다.
                        </p>
                        <div class="row">
                            <div class="col-md-6">
                                <h6>특징</h6>
                                <ul class="list-unstyled text-muted small">
                                    <li><i class="bi bi-check text-success me-2"></i>유연한 활동 시간</li>
                                    <li><i class="bi bi-check text-success me-2"></i>낮은 진입 장벽</li>
                                    <li><i class="bi bi-check text-success me-2"></i>개인 브랜딩 가능</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>적합 대상</h6>
                                <ul class="list-unstyled text-muted small">
                                    <li><i class="bi bi-person me-2"></i>프리랜서</li>
                                    <li><i class="bi bi-person me-2"></i>부업 희망자</li>
                                    <li><i class="bi bi-person me-2"></i>경력 전환자</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="feature-card p-4 h-100">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-building text-success me-3" style="font-size: 2.5rem;"></i>
                            <div>
                                <h4>기업 파트너</h4>
                                <small class="text-muted">Corporate Partner</small>
                            </div>
                        </div>
                        <p class="text-muted mb-3">
                            법인 자격으로 참여하는 파트너로, 기업의 조직력과 시스템을 통해
                            대규모 사업을 운영합니다.
                        </p>
                        <div class="row">
                            <div class="col-md-6">
                                <h6>특징</h6>
                                <ul class="list-unstyled text-muted small">
                                    <li><i class="bi bi-check text-success me-2"></i>대규모 운영 가능</li>
                                    <li><i class="bi bi-check text-success me-2"></i>체계적인 관리</li>
                                    <li><i class="bi bi-check text-success me-2"></i>안정적인 수익</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>적합 대상</h6>
                                <ul class="list-unstyled text-muted small">
                                    <li><i class="bi bi-building me-2"></i>기존 사업자</li>
                                    <li><i class="bi bi-building me-2"></i>투자 기업</li>
                                    <li><i class="bi bi-building me-2"></i>대형 조직</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Requirements Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold section-title">파트너 자격 요건</h2>
                    <p class="lead text-muted mt-4">
                        성공적인 파트너십을 위한 기본 자격 요건을 확인하세요
                    </p>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6">
                    <div class="requirement-card">
                        <h5><i class="bi bi-person-check me-2 text-primary"></i>기본 자격</h5>
                        <ul class="mb-0">
                            <li>만 20세 이상의 성인</li>
                            <li>신용에 결함이 없는 자</li>
                            <li>성실하고 적극적인 활동 의지</li>
                            <li>팀워크와 협업 능력</li>
                        </ul>
                    </div>
                    <div class="requirement-card">
                        <h5><i class="bi bi-mortarboard me-2 text-success"></i>역량 요건</h5>
                        <ul class="mb-0">
                            <li>기본적인 컴퓨터 활용 능력</li>
                            <li>고객 응대 및 커뮤니케이션 스킬</li>
                            <li>지속적인 학습과 자기계발 의지</li>
                            <li>목표 지향적 사고방식</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="requirement-card">
                        <h5><i class="bi bi-file-text me-2 text-warning"></i>서류 요건</h5>
                        <ul class="mb-0">
                            <li>신분증 사본</li>
                            <li>사업자등록증 (법인 파트너)</li>
                            <li>통장 사본 (수익 지급용)</li>
                            <li>자기소개서 및 사업계획서</li>
                        </ul>
                    </div>
                    <div class="requirement-card">
                        <h5><i class="bi bi-shield-check me-2 text-info"></i>활동 조건</h5>
                        <ul class="mb-0">
                            <li>월 최소 활동 기준 준수</li>
                            <li>정기 교육 프로그램 참여</li>
                            <li>윤리 규정 및 약관 준수</li>
                            <li>정기적인 성과 보고</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sales Management Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold section-title">매출 관리 시스템</h2>
                    <p class="lead text-muted mt-4">
                        투명하고 체계적인 매출 관리를 통한 공정한 수익 분배
                    </p>
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-lg-8 mx-auto">
                    <div class="text-center">
                        <p class="lead text-muted">
                            파트너는 제공되는 상품, 서비스에 대해서 일정 할인된 가격으로 공급받습니다.
                            서비스를 판매하고, 실제 고객과의 매출이 성사 완료되면
                            <strong>파트너 유형 및 등급에 따라서 이익을 분배</strong>합니다.
                        </p>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="feature-card p-4 h-100 text-center">
                        <i class="bi bi-calculator text-primary mb-3" style="font-size: 3rem;"></i>
                        <h5>할인된 공급가</h5>
                        <p class="text-muted">
                            파트너 전용 할인가로 상품과 서비스를 공급받아
                            경쟁력 있는 가격으로 고객에게 제공할 수 있습니다.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="feature-card p-4 h-100 text-center">
                        <i class="bi bi-check-circle text-success mb-3" style="font-size: 3rem;"></i>
                        <h5>매출 확정 시스템</h5>
                        <p class="text-muted">
                            실제 고객과의 매출이 완료되었을 때만 수익이 발생하는
                            명확하고 투명한 매출 확정 시스템을 운영합니다.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="feature-card p-4 h-100 text-center">
                        <i class="bi bi-pie-chart text-warning mb-3" style="font-size: 3rem;"></i>
                        <h5>등급별 이익 분배</h5>
                        <p class="text-muted">
                            파트너 유형과 등급에 따른 차별화된 이익 분배로
                            성과에 합당한 보상을 제공합니다.
                        </p>
                    </div>
                </div>
            </div>

            <div class="row mt-5">
                <div class="col-12">
                    <div class="text-center">
                        <a href="{{ route('partner.www.commission') }}" class="btn btn-primary btn-lg">
                            <i class="bi bi-graph-up me-2"></i>매출 등록 방법 자세히 보기
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold section-title">파트너 혜택</h2>
                    <p class="lead text-muted mt-4">
                        Jiny Partners와 함께하면 누릴 수 있는 다양한 혜택들을 소개합니다
                    </p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="benefit-item">
                        <i class="bi bi-currency-dollar text-success mb-3" style="font-size: 2.5rem;"></i>
                        <h5>수익 보장</h5>
                        <p class="text-muted small">
                            투명한 커미션 구조와 정기적인 수익 지급으로 안정적인 소득을 보장합니다.
                        </p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="benefit-item">
                        <i class="bi bi-book text-primary mb-3" style="font-size: 2.5rem;"></i>
                        <h5>전문 교육</h5>
                        <p class="text-muted small">
                            체계적인 교육 프로그램과 지속적인 역량 개발 기회를 제공합니다.
                        </p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="benefit-item">
                        <i class="bi bi-headset text-info mb-3" style="font-size: 2.5rem;"></i>
                        <h5>24/7 지원</h5>
                        <p class="text-muted small">
                            언제든지 도움이 필요할 때 전문 지원팀이 빠르게 응답합니다.
                        </p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="benefit-item">
                        <i class="bi bi-trophy text-warning mb-3" style="font-size: 2.5rem;"></i>
                        <h5>성과 인센티브</h5>
                        <p class="text-muted small">
                            우수한 성과에 대한 특별 보너스와 인센티브 프로그램을 운영합니다.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="container">
            <div class="row justify-content-center text-center text-white">
                <div class="col-lg-8">
                    <h2 class="display-5 fw-bold mb-4">지금 바로 시작하세요!</h2>
                    <p class="lead mb-5">
                        Jiny Partners와 함께 새로운 가능성을 발견하고
                        성공적인 파트너십을 구축해보세요.
                    </p>
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <a href="{{ route('partner.www.application') }}" class="btn btn-warning btn-lg px-5">
                            <i class="bi bi-file-earmark-text me-2"></i>지원서 작성하기
                        </a>
                        <a href="{{ route('partner.www.contact') }}" class="btn btn-outline-light btn-lg px-5">
                            <i class="bi bi-telephone me-2"></i>상담 예약하기
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
