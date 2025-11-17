@extends('jiny-partner::layouts.www')

@section('title', '파트너 네트워크 & 협업 시스템 - Jiny Partners')

@push('styles')
<style>
    .hero-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 80px 0;
    }
    .network-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        overflow: hidden;
        height: 100%;
    }
    .network-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }
    .level-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        text-align: center;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        position: relative;
        margin-bottom: 30px;
    }
    .level-general { border-top: 5px solid #ff6b6b; }
    .level-wholesale { border-top: 5px solid #4ecdc4; }
    .level-reseller { border-top: 5px solid #45b7d1; }
    .level-agency { border-top: 5px solid #f9ca24; }
    .level-retail { border-top: 5px solid #6c5ce7; }

    .network-flow {
        position: relative;
        padding: 40px 0;
    }
    .flow-arrow {
        font-size: 2rem;
        color: #667eea;
        text-align: center;
        margin: 20px 0;
    }
    .collaboration-feature {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 30px;
        text-align: center;
        height: 100%;
        border-left: 4px solid #667eea;
    }
    .profit-distribution {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        padding: 30px;
        text-align: center;
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
    .step-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        position: relative;
        height: 100%;
    }
    .step-number {
        position: absolute;
        top: -15px;
        left: 50%;
        transform: translateX(-50%);
        width: 30px;
        height: 30px;
        background: #667eea;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 0.9rem;
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
                        파트너 네트워크 &<br>
                        <span class="text-warning">협업 시스템</span>
                    </h1>
                    <p class="lead mb-4">
                        체계적인 다단계 네트워크와 협업 시스템을 통해
                        함께 성장하는 파트너 생태계를 구축합니다.
                        지인과의 협력으로 더 큰 성과를 만들어보세요.
                    </p>
                    <a href="{{ route('partner.www.application') }}" class="btn btn-warning btn-lg">
                        <i class="bi bi-people me-2"></i>네트워크 참여하기
                    </a>
                </div>
                <div class="col-lg-6 text-center">
                    <i class="bi bi-diagram-3" style="font-size: 12rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Collaboration System Section -->
    <section class="py-5">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h2 class="display-5 fw-bold section-title">파트너 협업 시스템</h2>
                    <p class="lead text-muted mt-4">
                        혼자만의 힘으로는 한계가 있습니다. 지인의 도움과 소개를 통한 협업으로 더 큰 성과를 창출하세요.
                    </p>
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-lg-8 mx-auto">
                    <div class="text-center mb-4">
                        <h4 class="mb-3">💡 협업 시스템의 핵심</h4>
                        <p class="text-muted">
                            실제 현장에서는 주변 지인의 도움이나 소개로 성과를 내는 경우가 대부분입니다.
                            지니 파트너 시스템은 이러한 분들을 등록하여 협업 시스템으로 영업을 지원하고,
                            지정한 비율에 따라서 파트너의 이익을 자동으로 분배할 수 있습니다.
                        </p>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="collaboration-feature">
                        <i class="bi bi-person-plus text-primary mb-3" style="font-size: 3rem;"></i>
                        <h5>협력자 등록</h5>
                        <p class="text-muted mb-0">
                            도움을 주신 지인들을 협력자로 등록하여
                            체계적인 협업 네트워크를 구축합니다.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="collaboration-feature">
                        <i class="bi bi-arrow-left-right text-success mb-3" style="font-size: 3rem;"></i>
                        <h5>영업 지원</h5>
                        <p class="text-muted mb-0">
                            협업 시스템을 통해 효과적인 영업 활동을
                            지원하고 성과를 극대화합니다.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="collaboration-feature">
                        <i class="bi bi-calculator text-warning mb-3" style="font-size: 3rem;"></i>
                        <h5>자동 정산</h5>
                        <p class="text-muted mb-0">
                            지정한 비율에 따라 이익을 자동으로 분배하여
                            공정한 수익 배분을 보장합니다.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sales Network Structure Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h2 class="display-5 fw-bold section-title">판매망 관리 체계</h2>
                    <p class="lead text-muted mt-4">
                        체계적으로 분업화된 역량으로 시장 개척을 위한 다단계 판매망 구조
                    </p>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="network-flow">
                        <!-- 총판 -->
                        <div class="level-card level-general">
                            <i class="bi bi-building text-danger mb-3" style="font-size: 2.5rem;"></i>
                            <h4>총판 (General Distributor)</h4>
                            <p class="text-muted mb-3">최상위 판매망으로 전체 지역을 담당하는 총괄 파트너</p>
                            <div class="row">
                                <div class="col-md-6">
                                    <small class="text-muted">
                                        <i class="bi bi-check-circle text-success me-1"></i>지역 총괄 관리<br>
                                        <i class="bi bi-check-circle text-success me-1"></i>대량 공급 책임
                                    </small>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">
                                        <i class="bi bi-check-circle text-success me-1"></i>하위 판매망 관리<br>
                                        <i class="bi bi-check-circle text-success me-1"></i>전략 수립 및 실행
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="flow-arrow">
                            <i class="bi bi-arrow-down"></i>
                        </div>

                        <!-- 도매/리셀러 -->
                        <div class="row g-4 mb-4">
                            <div class="col-lg-6">
                                <div class="level-card level-wholesale">
                                    <i class="bi bi-shop text-info mb-3" style="font-size: 2rem;"></i>
                                    <h5>도매 (Wholesale)</h5>
                                    <p class="text-muted small mb-2">중간 유통 단계로 대량 구매 후 재판매</p>
                                    <small class="text-muted">
                                        <i class="bi bi-check-circle text-success me-1"></i>대량 구매 할인<br>
                                        <i class="bi bi-check-circle text-success me-1"></i>재고 관리 책임
                                    </small>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="level-card level-reseller">
                                    <i class="bi bi-laptop text-primary mb-3" style="font-size: 2rem;"></i>
                                    <h5>리셀러 (Reseller)</h5>
                                    <p class="text-muted small mb-2">전문적인 재판매 업체로 특정 영역 담당</p>
                                    <small class="text-muted">
                                        <i class="bi bi-check-circle text-success me-1"></i>전문 영역 집중<br>
                                        <i class="bi bi-check-circle text-success me-1"></i>마케팅 활동 수행
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="flow-arrow">
                            <i class="bi bi-arrow-down"></i>
                        </div>

                        <!-- 에이전시/소매점 -->
                        <div class="row g-4">
                            <div class="col-lg-6">
                                <div class="level-card level-agency">
                                    <i class="bi bi-person-workspace text-warning mb-3" style="font-size: 2rem;"></i>
                                    <h5>에이전시 (Agency)</h5>
                                    <p class="text-muted small mb-2">고객 직접 접점에서 서비스 제공</p>
                                    <small class="text-muted">
                                        <i class="bi bi-check-circle text-success me-1"></i>고객 상담 및 지원<br>
                                        <i class="bi bi-check-circle text-success me-1"></i>지역 밀착 서비스
                                    </small>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="level-card level-retail">
                                    <i class="bi bi-storefront text-secondary mb-3" style="font-size: 2rem;"></i>
                                    <h5>소매점 (Retail)</h5>
                                    <p class="text-muted small mb-2">최종 소비자 대상 직접 판매</p>
                                    <small class="text-muted">
                                        <i class="bi bi-check-circle text-success me-1"></i>최종 고객 응대<br>
                                        <i class="bi bi-check-circle text-success me-1"></i>개별 맞춤 서비스
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-5">
                <div class="col-lg-8 mx-auto">
                    <div class="profit-distribution">
                        <h4 class="mb-4"><i class="bi bi-pie-chart me-2"></i>계층별 수익 분배</h4>
                        <p class="mb-4">
                            각 계층마다 제공되는 마진과 수수료를 체계적으로 계산하여
                            이익을 함께 나누며 지속가능한 성장 환경을 제공합니다.
                        </p>
                        <div class="row g-3">
                            <div class="col-6 col-md-3">
                                <div class="text-center">
                                    <h6>총판</h6>
                                    <small>5-15%</small>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-center">
                                    <h6>도매</h6>
                                    <small>3-8%</small>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-center">
                                    <h6>리셀러</h6>
                                    <small>2-6%</small>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-center">
                                    <h6>에이전시/소매</h6>
                                    <small>1-4%</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Network Benefits Section -->
    <section class="py-5">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h2 class="display-5 fw-bold section-title">네트워크의 장점</h2>
                    <p class="lead text-muted mt-4">
                        체계적인 네트워크를 통해 얻을 수 있는 핵심 혜택들
                    </p>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="network-card p-4 text-center">
                        <i class="bi bi-shield-check text-success mb-3" style="font-size: 3rem;"></i>
                        <h5>위험 분산</h5>
                        <p class="text-muted small">
                            여러 단계를 거치는 구조로 개별 위험을 분산하고
                            안정적인 운영을 보장합니다.
                        </p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="network-card p-4 text-center">
                        <i class="bi bi-people-fill text-primary mb-3" style="font-size: 3rem;"></i>
                        <h5>책임 분담</h5>
                        <p class="text-muted small">
                            각 계층별 명확한 역할과 책임으로
                            효율적인 업무 분담을 실현합니다.
                        </p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="network-card p-4 text-center">
                        <i class="bi bi-graph-up text-warning mb-3" style="font-size: 3rem;"></i>
                        <h5>지속 성장</h5>
                        <p class="text-muted small">
                            체계적인 네트워크 확장을 통해
                            지속가능한 성장 동력을 확보합니다.
                        </p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="network-card p-4 text-center">
                        <i class="bi bi-award text-info mb-3" style="font-size: 3rem;"></i>
                        <h5>전문성 향상</h5>
                        <p class="text-muted small">
                            각 단계별 전문성을 발휘하여
                            전체 네트워크의 경쟁력을 강화합니다.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How to Build Network Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h2 class="display-5 fw-bold section-title">네트워크 구축 단계</h2>
                    <p class="lead text-muted mt-4">효과적인 파트너 네트워크를 구축하는 방법</p>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <i class="bi bi-person-check text-primary mb-3" style="font-size: 2rem;"></i>
                        <h6>협력자 발굴</h6>
                        <p class="text-muted small">
                            신뢰할 수 있는 지인들을 파악하고
                            협력 가능성을 검토합니다.
                        </p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <i class="bi bi-handshake text-success mb-3" style="font-size: 2rem;"></i>
                        <h6>파트너십 제안</h6>
                        <p class="text-muted small">
                            상호 이익이 되는 파트너십을
                            제안하고 조건을 협의합니다.
                        </p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <i class="bi bi-gear text-warning mb-3" style="font-size: 2rem;"></i>
                        <h6>시스템 등록</h6>
                        <p class="text-muted small">
                            협력자를 시스템에 등록하고
                            수익 분배 비율을 설정합니다.
                        </p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="step-card">
                        <div class="step-number">4</div>
                        <i class="bi bi-rocket text-info mb-3" style="font-size: 2rem;"></i>
                        <h6>협업 시작</h6>
                        <p class="text-muted small">
                            본격적인 협업을 시작하고
                            성과를 함께 창출합니다.
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
                    <h2 class="display-5 fw-bold mb-4">함께 성장하는 네트워크에 참여하세요!</h2>
                    <p class="lead mb-5">
                        체계적인 파트너 네트워크와 협업 시스템으로
                        더 큰 성과와 지속가능한 성장을 경험해보세요.
                    </p>
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <a href="{{ route('partner.www.application') }}" class="btn btn-warning btn-lg px-5">
                            <i class="bi bi-diagram-3 me-2"></i>네트워크 참여하기
                        </a>
                        <a href="{{ route('partner.www.contact') }}" class="btn btn-outline-light btn-lg px-5">
                            <i class="bi bi-question-circle me-2"></i>협업 상담받기
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
