@extends('jiny-partner::layouts.www')

@section('title', '커미션 시스템 - Jiny Partners')

@push('styles')
<style>
    .hero-section { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 80px 0; }
    .commission-card { border: none; border-radius: 15px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); transition: transform 0.3s ease; }
    .commission-card:hover { transform: translateY(-5px); }
</style>
@endpush

@section('content')
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">투명하고 공정한<br><span class="text-warning">커미션 시스템</span></h1>
                    <p class="lead mb-4">다층 구조의 커미션 시스템으로 개인 성과와 팀 성과 모두에서 수익을 창출하세요.</p>
                </div>
                <div class="col-lg-6 text-center">
                    <i class="bi bi-cash-coin" style="font-size: 12rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h2 class="display-5 fw-bold">5가지 커미션 구조</h2>
                    <p class="lead text-muted mt-4">다양한 방식으로 수익을 창출할 수 있습니다</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="commission-card p-4 h-100">
                        <i class="bi bi-person-fill text-primary mb-3" style="font-size: 2.5rem;"></i>
                        <h4>개인 커미션</h4>
                        <p class="text-muted">직접 판매로 인한 기본 수수료 (3-4.5%)</p>
                        <ul class="list-unstyled">
                            <li><i class="bi bi-check text-success me-2"></i>즉시 지급</li>
                            <li><i class="bi bi-check text-success me-2"></i>등급별 차등 적용</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="commission-card p-4 h-100">
                        <i class="bi bi-people-fill text-success mb-3" style="font-size: 2.5rem;"></i>
                        <h4>그룹 커미션</h4>
                        <p class="text-muted">하위 조직 매출에 따른 수수료 (1-2%)</p>
                        <ul class="list-unstyled">
                            <li><i class="bi bi-check text-success me-2"></i>팀 확장 수익</li>
                            <li><i class="bi bi-check text-success me-2"></i>월별 정산</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="commission-card p-4 h-100">
                        <i class="bi bi-award text-warning mb-3" style="font-size: 2.5rem;"></i>
                        <h4>오버라이드 커미션</h4>
                        <p class="text-muted">리더십 단계별 추가 수수료</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="commission-card p-4 h-100">
                        <i class="bi bi-gem text-info mb-3" style="font-size: 2.5rem;"></i>
                        <h4>매칭 보너스</h4>
                        <p class="text-muted">양쪽 다리 균형 달성 시 특별 보너스</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="commission-card p-4 h-100">
                        <i class="bi bi-trophy text-danger mb-3" style="font-size: 2.5rem;"></i>
                        <h4>랭크 보너스</h4>
                        <p class="text-muted">등급 달성 시 월별 고정 보너스</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold">등급별 커미션율</h2>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>등급</th>
                            <th>개인 커미션</th>
                            <th>그룹 커미션</th>
                            <th>월 고정 보너스</th>
                            <th>특별 혜택</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Bronze</td><td>3%</td><td>-</td><td>-</td><td>기본 지원</td></tr>
                        <tr><td>Silver</td><td>3.3%</td><td>1%</td><td>5만원</td><td>우선 지원</td></tr>
                        <tr><td>Gold</td><td>3.6%</td><td>1.5%</td><td>15만원</td><td>전용 매니저</td></tr>
                        <tr><td>Platinum</td><td>4.05%</td><td>2%</td><td>30만원</td><td>VIP 서비스</td></tr>
                        <tr><td>Diamond</td><td>4.5%</td><td>2.5%</td><td>100만원</td><td>해외 연수</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- Sales Registration Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold">매출 등록 프로세스</h2>
                    <p class="lead text-muted mt-4">
                        파트너 매출을 등록하고 커미션을 받는 방법을 안내합니다
                    </p>
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-lg-8 mx-auto">
                    <div class="alert alert-info">
                        <h5><i class="bi bi-info-circle me-2"></i>매출 발생 조건</h5>
                        <p class="mb-2">
                            파트너는 제공되는 상품, 서비스에 대해서 <strong>일정 할인된 가격으로 공급</strong>받습니다.
                        </p>
                        <p class="mb-0">
                            서비스를 판매하고, <strong>실제 고객과의 매출이 성사가 완료</strong>되면
                            파트너 유형 및 등급에 따라서 이익을 분배합니다.
                        </p>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="commission-card p-4 h-100 text-center">
                        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px;">
                            <i class="bi bi-cart-plus"></i>
                        </div>
                        <h5>1. 상품/서비스 공급</h5>
                        <p class="text-muted small">
                            파트너 전용 할인가로 상품과 서비스를 공급받아
                            경쟁력 있는 가격 제공
                        </p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="commission-card p-4 h-100 text-center">
                        <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px;">
                            <i class="bi bi-handshake"></i>
                        </div>
                        <h5>2. 고객 판매</h5>
                        <p class="text-muted small">
                            고객에게 서비스를 판매하고
                            실제 매출이 확정될 때까지 진행
                        </p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="commission-card p-4 h-100 text-center">
                        <div class="bg-warning text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 50px; height: 50px;">
                            <i class="bi bi-clipboard-check"></i>
                        </div>
                        <h5>3. 매출 등록</h5>
                        <p class="text-muted small">
                            매출이 성사 완료되면 시스템에 등록하고
                            커미션 정산 요청
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Collaboration Commission Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold">협업 수익 분배 시스템</h2>
                    <p class="lead text-muted mt-4">
                        지인과의 협력을 통한 성과에 대한 자동 수익 분배
                    </p>
                </div>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-lg-6">
                    <div class="commission-card p-4 h-100">
                        <i class="bi bi-people-fill text-primary mb-3" style="font-size: 3rem;"></i>
                        <h4>협력자 등록 시스템</h4>
                        <p class="text-muted mb-3">
                            도움을 주신 지인들을 협력자로 등록하여
                            성과에 따른 수익을 함께 나눌 수 있습니다.
                        </p>
                        <ul class="list-unstyled">
                            <li><i class="bi bi-check text-success me-2"></i>협력자별 기여도 설정</li>
                            <li><i class="bi bi-check text-success me-2"></i>자동 수익 분배</li>
                            <li><i class="bi bi-check text-success me-2"></i>투명한 정산 내역</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="commission-card p-4 h-100">
                        <i class="bi bi-calculator text-success mb-3" style="font-size: 3rem;"></i>
                        <h4>지정 비율 분배</h4>
                        <p class="text-muted mb-3">
                            지정한 비율에 따라서 파트너의 이익을
                            자동으로 분배할 수 있습니다.
                        </p>
                        <ul class="list-unstyled">
                            <li><i class="bi bi-check text-success me-2"></i>유연한 비율 설정</li>
                            <li><i class="bi bi-check text-success me-2"></i>실시간 정산</li>
                            <li><i class="bi bi-check text-success me-2"></i>공정한 보상 체계</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="text-center p-4 bg-white rounded shadow-sm">
                        <h5 class="mb-3">💡 협업 수익의 예시</h5>
                        <p class="text-muted">
                            100만원의 매출이 발생했을 때, 본인 70% (70만원),
                            소개해 준 지인 20% (20만원), 상담 도움을 준 지인 10% (10만원)로
                            미리 설정한 비율에 따라 자동으로 분배됩니다.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Payment Process Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold">커미션 지급 프로세스</h2>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-3 text-center">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">1</div>
                    <h5>자동 계산</h5>
                    <p class="text-muted">매출 발생 즉시 커미션 자동 계산</p>
                </div>
                <div class="col-lg-3 text-center">
                    <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">2</div>
                    <h5>검토 및 승인</h5>
                    <p class="text-muted">시스템 검증 후 관리자 승인</p>
                </div>
                <div class="col-lg-3 text-center">
                    <div class="bg-warning text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">3</div>
                    <h5>지급 처리</h5>
                    <p class="text-muted">매월 정기 지급 (15일)</p>
                </div>
                <div class="col-lg-3 text-center">
                    <div class="bg-info text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">4</div>
                    <h5>정산 완료</h5>
                    <p class="text-muted">지급 완료 및 명세서 발송</p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="container text-center text-white">
            <h2 class="display-5 fw-bold mb-4">수익 창출 시작하기</h2>
            <p class="lead mb-5">투명하고 공정한 커미션 시스템으로 안정적인 수익을 만들어보세요.</p>
            <a href="{{ route('partner.www.application') }}" class="btn btn-warning btn-lg px-5">
                <i class="bi bi-currency-dollar me-2"></i>수익 여정 시작하기
            </a>
        </div>
    </section>
@endsection
