@extends('jiny-partner::layouts.www')

@section('title', 'FAQ - Jiny Partners')

@section('content')
    <section class="py-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-4">자주 묻는 질문</h1>
            <p class="lead">파트너 시스템에 대한 궁금한 점을 해결해드립니다</p>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    파트너가 되기 위한 자격 요건은 무엇인가요?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    만 20세 이상의 성인으로 신용에 결함이 없으며, 성실하고 적극적인 활동 의지가 있는 분이라면 누구나 지원 가능합니다.
                                    기본적인 컴퓨터 활용 능력과 고객 응대 스킬이 있으면 더욱 좋습니다.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    커미션은 언제 지급되나요?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    매월 15일에 정기 지급됩니다. 매출 발생과 동시에 커미션이 자동 계산되며,
                                    시스템 검증과 관리자 승인을 거쳐 안전하게 지급됩니다.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    등급은 어떻게 승급하나요?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    월 매출액, 총 매출액, 활동 기간, 고객 만족도, 팀 크기 등의 기준에 따라 자동으로 승급됩니다.
                                    각 등급별 구체적인 요건은 등급 시스템 페이지에서 확인할 수 있습니다.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    초기 비용이나 가입비가 있나요?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    파트너 가입에는 별도의 가입비나 초기 비용이 없습니다. 다만, 업무에 필요한 기본적인
                                    도구나 교육 자료는 파트너 본인이 준비해야 할 수 있습니다.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                    팀을 구성해야 하나요?
                                </button>
                            </h2>
                            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    팀 구성은 선택사항입니다. 개인 파트너로도 충분히 활동할 수 있지만,
                                    팀을 구성하면 그룹 커미션과 리더십 보너스 등 추가 수익을 얻을 수 있습니다.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                                    교육 프로그램은 어떻게 진행되나요?
                                </button>
                            </h2>
                            <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    등급별 맞춤 교육을 제공합니다. 온라인 교육과 오프라인 세미나가 병행되며,
                                    신규 파트너를 위한 기본 교육부터 고급 파트너를 위한 리더십 교육까지 다양합니다.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">
                                    성과는 어떻게 측정되나요?
                                </button>
                            </h2>
                            <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    매출, 활동, 품질, 네트워크 등 4대 영역으로 성과를 측정합니다.
                                    실시간 대시보드를 통해 현재 성과를 확인하고, 목표 대비 진행률을 추적할 수 있습니다.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item mb-3">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq8">
                                    지원 서비스는 어떤 것들이 있나요?
                                </button>
                            </h2>
                            <div id="faq8" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    24/7 고객 지원, 전담 매니저 배정(고등급), 정기 교육, 마케팅 자료 제공,
                                    기술 지원 등 다양한 서비스를 제공합니다. 등급에 따라 차별화된 지원을 받을 수 있습니다.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 bg-light">
        <div class="container text-center">
            <h3 class="mb-4">더 궁금한 점이 있으신가요?</h3>
            <p class="text-muted mb-4">언제든지 문의해주세요. 전문 상담팀이 도움을 드리겠습니다.</p>
            <a href="{{ route('partner.www.contact') }}" class="btn btn-primary btn-lg">
                <i class="bi bi-envelope me-2"></i>문의하기
            </a>
        </div>
    </section>
@endsection
