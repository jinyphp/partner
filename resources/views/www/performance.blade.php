@extends('jiny-partner::layouts.www')

@section('title', '성과 관리 시스템 - Jiny Partners')

@push('styles')
<style>
    .hero-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 80px 0;
    }
    .metric-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
        padding: 30px;
    }
    .metric-card:hover {
        transform: translateY(-5px);
    }
    .metric-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 20px;
    }
    .bg-sales { background: linear-gradient(135deg, #4caf50 0%, #45a049 100%); }
    .bg-activity { background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%); }
    .bg-quality { background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%); }
    .bg-network { background: linear-gradient(135deg, #9c27b0 0%, #7b1fa2 100%); }
</style>
@endpush

@section('content')
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">
                        데이터 기반<br>
                        <span class="text-warning">성과 관리 시스템</span>
                    </h1>
                    <p class="lead mb-4">
                        4대 성과 영역을 종합 분석하여 파트너의 성장을 체계적으로 지원하는
                        과학적 성과 관리 시스템입니다.
                    </p>
                </div>
                <div class="col-lg-6 text-center">
                    <i class="bi bi-graph-up-arrow" style="font-size: 12rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- 4 Core Metrics Section -->
    <section class="py-5">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h2 class="display-5 fw-bold">4대 성과 영역</h2>
                    <p class="lead text-muted mt-4">다면적 성과 측정으로 정확한 평가를 제공합니다</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="metric-card text-center h-100">
                        <div class="metric-icon bg-sales text-white mx-auto">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <h4>매출 메트릭</h4>
                        <p class="text-muted mb-4">총 매출액, 수수료 수익, 성사 거래 수, 평균 거래 규모</p>
                        <ul class="list-unstyled text-start">
                            <li><i class="bi bi-check-circle text-success me-2"></i>총 매출액 추적</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>커미션 수익 계산</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>거래 성사율 분석</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>평균 거래 규모 측정</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="metric-card text-center h-100">
                        <div class="metric-icon bg-activity text-white mx-auto">
                            <i class="bi bi-activity"></i>
                        </div>
                        <h4>활동 메트릭</h4>
                        <p class="text-muted mb-4">리드 생성, 고객 확보, 지원 해결, 교육 진행</p>
                        <ul class="list-unstyled text-start">
                            <li><i class="bi bi-check-circle text-success me-2"></i>리드 생성 수 추적</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>신규 고객 확보 분석</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>고객 지원 티켓 해결</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>교육 세션 진행 횟수</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="metric-card text-center h-100">
                        <div class="metric-icon bg-quality text-white mx-auto">
                            <i class="bi bi-star"></i>
                        </div>
                        <h4>품질 메트릭</h4>
                        <p class="text-muted mb-4">고객 만족도, 응답 시간, 불만 건수, 완료율</p>
                        <ul class="list-unstyled text-start">
                            <li><i class="bi bi-check-circle text-success me-2"></i>고객 만족도 점수</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>평균 응답 시간</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>불만 접수 건수</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>작업 완료율</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="metric-card text-center h-100">
                        <div class="metric-icon bg-network text-white mx-auto">
                            <i class="bi bi-diagram-3"></i>
                        </div>
                        <h4>네트워크 메트릭</h4>
                        <p class="text-muted mb-4">추천 파트너, 팀원 관리, 팀 성과 보너스</p>
                        <ul class="list-unstyled text-start">
                            <li><i class="bi bi-check-circle text-success me-2"></i>추천 파트너 수</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>관리 팀원 수</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>팀 성과 보너스</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>조직 확장 기여도</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Period Analysis Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold">기간별 성과 분석</h2>
                    <p class="lead text-muted mt-4">주간부터 연간까지 다양한 관점에서 성과를 분석합니다</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="card text-center p-4">
                        <i class="bi bi-calendar-week text-primary mb-3" style="font-size: 2.5rem;"></i>
                        <h5>주간 분석</h5>
                        <p class="text-muted">7일 단위 단기 성과 추적</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card text-center p-4">
                        <i class="bi bi-calendar-month text-success mb-3" style="font-size: 2.5rem;"></i>
                        <h5>월간 분석</h5>
                        <p class="text-muted">월별 성과 트렌드 분석</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card text-center p-4">
                        <i class="bi bi-calendar3 text-warning mb-3" style="font-size: 2.5rem;"></i>
                        <h5>분기 분석</h5>
                        <p class="text-muted">3개월 중기 성과 평가</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card text-center p-4">
                        <i class="bi bi-calendar-date text-info mb-3" style="font-size: 2.5rem;"></i>
                        <h5>연간 분석</h5>
                        <p class="text-muted">12개월 장기 성과 종합</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold">핵심 기능</h2>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card h-100 p-4">
                        <i class="bi bi-trophy text-warning mb-3" style="font-size: 2.5rem;"></i>
                        <h5>성과 순위 및 벤치마킹</h5>
                        <p class="text-muted">
                            동일 등급 내에서의 순위를 실시간으로 확인하고,
                            상위 파트너와 비교 분석할 수 있습니다.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card h-100 p-4">
                        <i class="bi bi-target text-primary mb-3" style="font-size: 2.5rem;"></i>
                        <h5>목표 대비 달성률</h5>
                        <p class="text-muted">
                            설정된 목표 대비 실제 성과를 추적하고,
                            달성률을 시각적으로 확인할 수 있습니다.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card h-100 p-4">
                        <i class="bi bi-graph-up text-success mb-3" style="font-size: 2.5rem;"></i>
                        <h5>성장률 자동 계산</h5>
                        <p class="text-muted">
                            전년 동기 대비 성장률과 효율성 지표를
                            자동으로 계산하여 제공합니다.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Analytics Dashboard Preview -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold">실시간 대시보드</h2>
                    <p class="lead text-muted mt-4">한눈에 보는 성과 현황과 트렌드</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card p-4">
                        <h5 class="card-title"><i class="bi bi-bar-chart me-2"></i>성과 트렌드</h5>
                        <div class="bg-light rounded p-5 text-center">
                            <i class="bi bi-graph-up-arrow text-primary" style="font-size: 5rem;"></i>
                            <p class="text-muted mt-3">실시간 차트와 그래프로 성과 추이를 시각화</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card p-4 h-100">
                        <h5 class="card-title"><i class="bi bi-speedometer2 me-2"></i>핵심 지표</h5>
                        <div class="mt-4">
                            <div class="d-flex justify-content-between mb-3">
                                <span>이번 달 매출</span>
                                <strong class="text-success">↗ 15%</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>고객 만족도</span>
                                <strong class="text-primary">4.8/5</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>응답 시간</span>
                                <strong class="text-warning">2.3시간</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>팀 성과</span>
                                <strong class="text-info">87%</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold">성과 관리의 장점</h2>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 1.5rem;"></i>
                        </div>
                        <div class="ms-3">
                            <h5>객관적 평가</h5>
                            <p class="text-muted">정량적 데이터를 기반으로 한 공정하고 투명한 성과 평가</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 1.5rem;"></i>
                        </div>
                        <div class="ms-3">
                            <h5>개선점 발견</h5>
                            <p class="text-muted">세부 지표 분석을 통한 구체적인 개선 영역 파악</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 1.5rem;"></i>
                        </div>
                        <div class="ms-3">
                            <h5>동기 부여</h5>
                            <p class="text-muted">명확한 성과 지표와 순위를 통한 건전한 경쟁 유도</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 1.5rem;"></i>
                        </div>
                        <div class="ms-3">
                            <h5>성장 지원</h5>
                            <p class="text-muted">데이터 기반 피드백으로 지속적인 성장 방향 제시</p>
                        </div>
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
                    <h2 class="display-5 fw-bold mb-4">과학적 성과 관리 시작하기</h2>
                    <p class="lead mb-5">
                        데이터 기반의 정확한 성과 측정으로 더 나은 파트너가 되어보세요.
                    </p>
                    <a href="{{ route('partner.www.application') }}" class="btn btn-warning btn-lg px-5">
                        <i class="bi bi-graph-up me-2"></i>성과 여정 시작하기
                    </a>
                </div>
            </div>
        </div>
    </section>
@endsection
