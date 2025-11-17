@extends('jiny-partner::layouts.www')

@section('title', '파트너 등급 시스템 - Jiny Partners')

@push('styles')
<style>
    .hero-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 80px 0;
    }
    .tier-card {
        border: none;
        border-radius: 20px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
        overflow: hidden;
        height: 100%;
        position: relative;
    }
    .tier-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
    }
    .tier-bronze { background: linear-gradient(135deg, #cd7f32 0%, #8b4513 100%); }
    .tier-silver { background: linear-gradient(135deg, #c0c0c0 0%, #808080 100%); }
    .tier-gold { background: linear-gradient(135deg, #ffd700 0%, #ffb347 100%); }
    .tier-platinum { background: linear-gradient(135deg, #e5e4e2 0%, #71797e 100%); }
    .tier-diamond { background: linear-gradient(135deg, #b9f2ff 0%, #4285f4 100%); }

    .tier-header {
        padding: 30px;
        text-align: center;
        color: white;
        position: relative;
    }
    .tier-icon {
        font-size: 3.5rem;
        margin-bottom: 15px;
        opacity: 0.9;
    }
    .tier-body {
        background: white;
        padding: 30px;
    }
    .tier-featured {
        position: relative;
        border: 3px solid #ffd700;
    }
    .tier-featured::before {
        content: "추천";
        position: absolute;
        top: -10px;
        right: 30px;
        background: #ffd700;
        color: #333;
        padding: 5px 20px;
        border-radius: 15px;
        font-weight: bold;
        font-size: 0.9rem;
    }
    .benefit-item {
        display: flex;
        align-items: center;
        margin-bottom: 12px;
        padding: 8px 0;
    }
    .requirement-badge {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 10px;
        margin-bottom: 10px;
        border-left: 4px solid #667eea;
    }
    .progression-section {
        background: #f8f9fa;
        padding: 80px 0;
    }
    .progression-arrow {
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: #667eea;
        margin: 20px 0;
    }
    .compare-table {
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        border-radius: 15px;
        overflow: hidden;
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
</style>
@endpush

@section('content')
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">
                        성과에 따른<br>
                        <span class="text-warning">파트너 등급 시스템</span>
                    </h1>
                    <p class="lead mb-4">
                        5단계 등급 체계로 파트너의 성장 단계를 명확히 하고,
                        각 등급별 차별화된 혜택을 제공하여 지속적인 발전을 지원합니다.
                    </p>
                    <a href="{{ route('partner.www.application') }}" class="btn btn-warning btn-lg">
                        <i class="bi bi-trophy me-2"></i>등급 여정 시작하기
                    </a>
                </div>
                <div class="col-lg-6 text-center">
                    <div class="d-flex justify-content-center align-items-center" style="font-size: 4rem; opacity: 0.3;">
                        <i class="bi bi-award text-warning me-3"></i>
                        <i class="bi bi-trophy text-warning me-3"></i>
                        <i class="bi bi-gem text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Tier Overview Section -->
    <section class="py-5">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h2 class="display-5 fw-bold section-title">파트너 등급 체계</h2>
                    <p class="lead text-muted mt-4">
                        Bronze부터 Diamond까지, 단계별 성장과 함께하는 파트너 여정
                    </p>
                </div>
            </div>

            <!-- Tier Cards -->
            <div class="row g-4 mb-5">
                <!-- Bronze Tier -->
                <div class="col-lg-3 col-md-6">
                    <div class="card tier-card">
                        <div class="tier-header tier-bronze">
                            <i class="bi bi-award tier-icon"></i>
                            <h4 class="mb-2">Bronze</h4>
                            <p class="mb-0">신규 파트너</p>
                        </div>
                        <div class="tier-body">
                            <div class="requirement-badge">
                                <h6 class="mb-2"><i class="bi bi-list-check me-2"></i>진입 조건</h6>
                                <small class="text-muted">파트너 등록 즉시 자동 부여</small>
                            </div>

                            <h6 class="mb-3"><i class="bi bi-gift me-2 text-primary"></i>혜택</h6>
                            <div class="benefit-item">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <span>기본 커미션 3%</span>
                            </div>
                            <div class="benefit-item">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <span>추천 보너스 1%</span>
                            </div>
                            <div class="benefit-item">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <span>기본 교육 제공</span>
                            </div>
                            <div class="benefit-item">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <span>일반 지원 서비스</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Silver Tier -->
                <div class="col-lg-3 col-md-6">
                    <div class="card tier-card">
                        <div class="tier-header tier-silver">
                            <i class="bi bi-award tier-icon"></i>
                            <h4 class="mb-2">Silver</h4>
                            <p class="mb-0">초급 파트너</p>
                        </div>
                        <div class="tier-body">
                            <div class="requirement-badge">
                                <h6 class="mb-2"><i class="bi bi-list-check me-2"></i>진입 조건</h6>
                                <ul class="small mb-0 list-unstyled">
                                    <li>• 월 매출 100만원 이상</li>
                                    <li>• 총 매출 300만원 이상</li>
                                    <li>• 3개월 이상 활동</li>
                                    <li>• 고객 만족도 70점 이상</li>
                                </ul>
                            </div>

                            <h6 class="mb-3"><i class="bi bi-gift me-2 text-primary"></i>혜택</h6>
                            <div class="benefit-item">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <span>기본 커미션 + 10% 보너스</span>
                            </div>
                            <div class="benefit-item">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <span>추천 보너스 1.5%</span>
                            </div>
                            <div class="benefit-item">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <span>월 고정 보너스 5만원</span>
                            </div>
                            <div class="benefit-item">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <span>중급 교육 프로그램</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gold Tier -->
                <div class="col-lg-3 col-md-6">
                    <div class="card tier-card tier-featured">
                        <div class="tier-header tier-gold">
                            <i class="bi bi-trophy tier-icon"></i>
                            <h4 class="mb-2">Gold</h4>
                            <p class="mb-0">중급 파트너</p>
                        </div>
                        <div class="tier-body">
                            <div class="requirement-badge">
                                <h6 class="mb-2"><i class="bi bi-list-check me-2"></i>진입 조건</h6>
                                <ul class="small mb-0 list-unstyled">
                                    <li>• 월 매출 300만원 이상</li>
                                    <li>• 총 매출 1,000만원 이상</li>
                                    <li>• 6개월 이상 활동</li>
                                    <li>• 고객 만족도 80점 이상</li>
                                    <li>• 최소 2명의 팀원</li>
                                </ul>
                            </div>

                            <h6 class="mb-3"><i class="bi bi-gift me-2 text-primary"></i>혜택</h6>
                            <div class="benefit-item">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <span>기본 커미션 + 20% 보너스</span>
                            </div>
                            <div class="benefit-item">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <span>추천 보너스 2%</span>
                            </div>
                            <div class="benefit-item">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <span>월 고정 보너스 15만원</span>
                            </div>
                            <div class="benefit-item">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <span>고급 교육 + 리더십 과정</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Platinum Tier -->
                <div class="col-lg-3 col-md-6">
                    <div class="card tier-card">
                        <div class="tier-header tier-platinum">
                            <i class="bi bi-gem tier-icon"></i>
                            <h4 class="mb-2">Platinum</h4>
                            <p class="mb-0">고급 파트너</p>
                        </div>
                        <div class="tier-body">
                            <div class="requirement-badge">
                                <h6 class="mb-2"><i class="bi bi-list-check me-2"></i>진입 조건</h6>
                                <ul class="small mb-0 list-unstyled">
                                    <li>• 월 매출 800만원 이상</li>
                                    <li>• 총 매출 3,000만원 이상</li>
                                    <li>• 12개월 이상 활동</li>
                                    <li>• 고객 만족도 85점 이상</li>
                                    <li>• 최소 5명의 팀원</li>
                                </ul>
                            </div>

                            <h6 class="mb-3"><i class="bi bi-gift me-2 text-primary"></i>혜택</h6>
                            <div class="benefit-item">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <span>기본 커미션 + 35% 보너스</span>
                            </div>
                            <div class="benefit-item">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <span>추천 보너스 2.5%</span>
                            </div>
                            <div class="benefit-item">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <span>월 고정 보너스 30만원</span>
                            </div>
                            <div class="benefit-item">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <span>전문가 과정 + 우선 지원</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Diamond Tier - Special Card -->
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="card tier-card">
                        <div class="tier-header tier-diamond">
                            <i class="bi bi-diamond tier-icon"></i>
                            <h3 class="mb-2">Diamond</h3>
                            <p class="mb-0">최상위 파트너 - VIP</p>
                        </div>
                        <div class="tier-body text-center">
                            <div class="requirement-badge">
                                <h6 class="mb-2"><i class="bi bi-list-check me-2"></i>진입 조건</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <ul class="small mb-0 list-unstyled text-start">
                                            <li>• 월 매출 1,500만원 이상</li>
                                            <li>• 총 매출 1억원 이상</li>
                                            <li>• 24개월 이상 활동</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <ul class="small mb-0 list-unstyled text-start">
                                            <li>• 고객 만족도 90점 이상</li>
                                            <li>• 최소 10명의 팀원</li>
                                            <li>• 리더십 인증 필수</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <h5 class="mb-4"><i class="bi bi-crown me-2 text-warning"></i>VIP 전용 혜택</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="benefit-item justify-content-center">
                                        <i class="bi bi-check-circle text-success me-2"></i>
                                        <span>기본 커미션 + 50% 보너스</span>
                                    </div>
                                    <div class="benefit-item justify-content-center">
                                        <i class="bi bi-check-circle text-success me-2"></i>
                                        <span>추천 보너스 3%</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="benefit-item justify-content-center">
                                        <i class="bi bi-check-circle text-success me-2"></i>
                                        <span>월 고정 보너스 100만원</span>
                                    </div>
                                    <div class="benefit-item justify-content-center">
                                        <i class="bi bi-check-circle text-success me-2"></i>
                                        <span>전담 매니저 배정</span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 p-3 bg-light rounded">
                                <h6 class="text-primary mb-2">특별 혜택</h6>
                                <small class="text-muted">
                                    연간 해외 연수, VIP 이벤트 초대, 경영진과의 직접 미팅,
                                    사업 확장 지원, 개인 브랜딩 지원
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Progression Section -->
    <section class="progression-section">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold section-title">등급 진행 과정</h2>
                    <p class="lead text-muted mt-4">
                        체계적인 단계별 성장으로 최상위 파트너가 되어보세요
                    </p>
                </div>
            </div>

            <!-- Mobile Progression -->
            <div class="d-block d-lg-none">
                <div class="text-center">
                    <div class="d-inline-flex flex-column align-items-center">
                        <div class="badge bg-warning text-dark p-3 mb-3">
                            <i class="bi bi-award me-2"></i>Bronze
                        </div>
                        <div class="progression-arrow">
                            <i class="bi bi-arrow-down"></i>
                        </div>
                        <div class="badge bg-secondary text-white p-3 mb-3">
                            <i class="bi bi-award me-2"></i>Silver
                        </div>
                        <div class="progression-arrow">
                            <i class="bi bi-arrow-down"></i>
                        </div>
                        <div class="badge bg-warning text-dark p-3 mb-3">
                            <i class="bi bi-trophy me-2"></i>Gold
                        </div>
                        <div class="progression-arrow">
                            <i class="bi bi-arrow-down"></i>
                        </div>
                        <div class="badge bg-info text-white p-3 mb-3">
                            <i class="bi bi-gem me-2"></i>Platinum
                        </div>
                        <div class="progression-arrow">
                            <i class="bi bi-arrow-down"></i>
                        </div>
                        <div class="badge bg-primary text-white p-3">
                            <i class="bi bi-diamond me-2"></i>Diamond
                        </div>
                    </div>
                </div>
            </div>

            <!-- Desktop Progression -->
            <div class="d-none d-lg-block">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-center">
                        <div class="badge bg-warning text-dark p-3 mb-3" style="font-size: 1.1rem;">
                            <i class="bi bi-award me-2"></i>Bronze
                        </div>
                        <p class="small text-muted">시작점</p>
                    </div>
                    <div class="progression-arrow">
                        <i class="bi bi-arrow-right"></i>
                    </div>
                    <div class="text-center">
                        <div class="badge bg-secondary text-white p-3 mb-3" style="font-size: 1.1rem;">
                            <i class="bi bi-award me-2"></i>Silver
                        </div>
                        <p class="small text-muted">안정화</p>
                    </div>
                    <div class="progression-arrow">
                        <i class="bi bi-arrow-right"></i>
                    </div>
                    <div class="text-center">
                        <div class="badge bg-warning text-dark p-3 mb-3" style="font-size: 1.1rem;">
                            <i class="bi bi-trophy me-2"></i>Gold
                        </div>
                        <p class="small text-muted">성장</p>
                    </div>
                    <div class="progression-arrow">
                        <i class="bi bi-arrow-right"></i>
                    </div>
                    <div class="text-center">
                        <div class="badge bg-info text-white p-3 mb-3" style="font-size: 1.1rem;">
                            <i class="bi bi-gem me-2"></i>Platinum
                        </div>
                        <p class="small text-muted">리더십</p>
                    </div>
                    <div class="progression-arrow">
                        <i class="bi bi-arrow-right"></i>
                    </div>
                    <div class="text-center">
                        <div class="badge bg-primary text-white p-3 mb-3" style="font-size: 1.1rem;">
                            <i class="bi bi-diamond me-2"></i>Diamond
                        </div>
                        <p class="small text-muted">최고급</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Comparison Table Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold section-title">등급별 혜택 비교</h2>
                    <p class="lead text-muted mt-4">한눈에 보는 등급별 차별화된 혜택</p>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="table-responsive compare-table">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                                <tr class="text-center">
                                    <th scope="col" class="py-4">구분</th>
                                    <th scope="col" class="py-4"><i class="bi bi-award me-2"></i>Bronze</th>
                                    <th scope="col" class="py-4"><i class="bi bi-award me-2"></i>Silver</th>
                                    <th scope="col" class="py-4 bg-warning text-dark"><i class="bi bi-trophy me-2"></i>Gold</th>
                                    <th scope="col" class="py-4"><i class="bi bi-gem me-2"></i>Platinum</th>
                                    <th scope="col" class="py-4"><i class="bi bi-diamond me-2"></i>Diamond</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-bold">기본 커미션</td>
                                    <td class="text-center">3%</td>
                                    <td class="text-center">3.3%</td>
                                    <td class="text-center bg-warning bg-opacity-10">3.6%</td>
                                    <td class="text-center">4.05%</td>
                                    <td class="text-center">4.5%</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">추천 보너스</td>
                                    <td class="text-center">1%</td>
                                    <td class="text-center">1.5%</td>
                                    <td class="text-center bg-warning bg-opacity-10">2%</td>
                                    <td class="text-center">2.5%</td>
                                    <td class="text-center">3%</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">월 고정 보너스</td>
                                    <td class="text-center">-</td>
                                    <td class="text-center">5만원</td>
                                    <td class="text-center bg-warning bg-opacity-10">15만원</td>
                                    <td class="text-center">30만원</td>
                                    <td class="text-center">100만원</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">교육 프로그램</td>
                                    <td class="text-center">기본</td>
                                    <td class="text-center">중급</td>
                                    <td class="text-center bg-warning bg-opacity-10">고급</td>
                                    <td class="text-center">전문가</td>
                                    <td class="text-center">VIP</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">지원 서비스</td>
                                    <td class="text-center">일반</td>
                                    <td class="text-center">우선</td>
                                    <td class="text-center bg-warning bg-opacity-10">고급</td>
                                    <td class="text-center">프리미엄</td>
                                    <td class="text-center">전담 매니저</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">특별 혜택</td>
                                    <td class="text-center">-</td>
                                    <td class="text-center">월간 세미나</td>
                                    <td class="text-center bg-warning bg-opacity-10">분기 워크샵</td>
                                    <td class="text-center">VIP 이벤트</td>
                                    <td class="text-center">해외 연수</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Tips Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold section-title">등급 승진 팁</h2>
                    <p class="lead text-muted mt-4">빠른 등급 상승을 위한 전문가 조언</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="bi bi-graph-up-arrow text-primary mb-3" style="font-size: 3rem;"></i>
                            <h5>꾸준한 매출 관리</h5>
                            <p class="text-muted">
                                월간 목표를 설정하고 꾸준히 달성하세요.
                                단기간의 높은 매출보다는 지속적인 성장이 중요합니다.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="bi bi-people text-success mb-3" style="font-size: 3rem;"></i>
                            <h5>팀 빌딩</h5>
                            <p class="text-muted">
                                우수한 하위 파트너를 모집하고 교육하세요.
                                팀의 성공이 곧 당신의 등급 상승으로 이어집니다.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <i class="bi bi-star text-warning mb-3" style="font-size: 3rem;"></i>
                            <h5>고객 만족도</h5>
                            <p class="text-muted">
                                고객 만족도는 승급의 핵심 요소입니다.
                                정성스러운 서비스로 높은 평점을 유지하세요.
                            </p>
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
                    <h2 class="display-5 fw-bold mb-4">등급 여정을 시작하세요!</h2>
                    <p class="lead mb-5">
                        Bronze부터 Diamond까지, 체계적인 등급 시스템과 함께
                        성공적인 파트너 라이프를 시작해보세요.
                    </p>
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <a href="{{ route('partner.www.application') }}" class="btn btn-warning btn-lg px-5">
                            <i class="bi bi-rocket-takeoff me-2"></i>파트너 지원하기
                        </a>
                        <a href="{{ route('partner.www.contact') }}" class="btn btn-outline-light btn-lg px-5">
                            <i class="bi bi-question-circle me-2"></i>등급 상담받기
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
