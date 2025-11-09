@extends($layout ?? 'jiny-auth::layouts.home')

@section('title', $pageTitle ?? '파트너 신청')

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
.hero-gradient {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    position: relative;
}
.hero-content {
    position: relative;
    z-index: 2;
}
.feature-card {
    transition: transform 0.2s ease-in-out;
}
.feature-card:hover {
    transform: translateY(-5px);
}
.process-step {
    position: relative;
}
.process-step::after {
    content: '';
    position: absolute;
    top: 50%;
    right: -25px;
    width: 50px;
    height: 2px;
    background: #e9ecef;
    z-index: 1;
}
.process-step:last-child::after {
    display: none;
}
.step-circle {
    width: 60px;
    height: 60px;
    background: white;
    border: 3px solid #667eea;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-weight: bold;
    color: #667eea;
    position: relative;
    z-index: 2;
}
.hero-text {
    color: #212529 !important;
}
.hero-lead {
    color: #495057 !important;
}
.hero-section-text {
    color: #212529 !important;
}
</style>
@endsection

@section('content')
<div class="min-vh-100 bg-light">
    <!-- Hero Section -->
    <div class="hero-gradient">
        <div class="container-fluid hero-content">
            <div class="row justify-content-center">
                <div class="col-12 col-xl-10">
                    <div class="py-5">
                        <!-- Header with user info and dashboard button -->
                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <div>
                                <span class="text-muted small d-flex align-items-center">
                                    <i class="bi bi-person-circle me-2"></i>{{ $user->name ?? '사용자' }}님으로 로그인됨
                                </span>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="/home" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-house me-1"></i>홈으로 돌아가기
                                </a>
                            </div>
                        </div>

                        <!-- Main content -->
                        <div class="row align-items-center">
                            <div class="col-12">
                                <div class="text-center">
                                    <h1 class="display-5 fw-bold mb-3 hero-text">파트너가 되어 함께 성장하세요</h1>
                                    <p class="lead mb-4 hero-lead mx-auto" style="max-width: 600px;">
                                        전문 기술을 활용하여 다양한 프로젝트에 참여하고,
                                        성과에 따른 보상을 받으며 전문성을 키워나가세요.
                                    </p>
                                    <div class="d-flex gap-3 justify-content-center">
                                        <a href="/home/partner/regist/create" class="btn btn-primary px-4 py-2">
                                            <i class="bi bi-rocket-takeoff me-2"></i>지금 시작하기
                                        </a>
                                        <button class="btn btn-outline-primary px-4 py-2" onclick="scrollToSection('benefits')">
                                            <i class="bi bi-info-circle me-2"></i>자세히 알아보기
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container-fluid py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">

                <!-- Key Benefits Section -->
                <section id="benefits" class="mb-5">
                    <div class="text-center mb-5">
                        <h2 class="fw-bold mb-3">파트너의 주요 혜택</h2>
                        <p class="text-muted">파트너가 되면 다양한 혜택과 기회를 누릴 수 있습니다.</p>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6 col-lg-3">
                            <div class="card border-0 shadow-sm h-100 feature-card">
                                <div class="card-body text-center p-4">
                                    <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                        <i class="bi bi-cash-coin text-success fs-4"></i>
                                    </div>
                                    <h5 class="fw-bold mb-2">경쟁력 있는 수수료</h5>
                                    <p class="text-muted small mb-0">프로젝트 성과에 따른 합리적인 수수료 체계</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-3">
                            <div class="card border-0 shadow-sm h-100 feature-card">
                                <div class="card-body text-center p-4">
                                    <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                        <i class="bi bi-briefcase text-primary fs-4"></i>
                                    </div>
                                    <h5 class="fw-bold mb-2">다양한 프로젝트</h5>
                                    <p class="text-muted small mb-0">자신의 전문성에 맞는 다양한 프로젝트 기회</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-3">
                            <div class="card border-0 shadow-sm h-100 feature-card">
                                <div class="card-body text-center p-4">
                                    <div class="bg-info bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                        <i class="bi bi-headset text-info fs-4"></i>
                                    </div>
                                    <h5 class="fw-bold mb-2">전문 지원</h5>
                                    <p class="text-muted small mb-0">프로젝트 진행 중 전문팀의 24/7 지원</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-3">
                            <div class="card border-0 shadow-sm h-100 feature-card">
                                <div class="card-body text-center p-4">
                                    <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                        <i class="bi bi-graph-up-arrow text-warning fs-4"></i>
                                    </div>
                                    <h5 class="fw-bold mb-2">성장 기회</h5>
                                    <p class="text-muted small mb-0">지속적인 교육과 스킬 개발 프로그램</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Process Steps -->
                {{-- <section class="mb-5">
                    <div class="text-center mb-5">
                        <h2 class="fw-bold mb-3">간단한 3단계 신청 과정</h2>
                        <p class="text-muted">파트너 신청부터 승인까지 간단하고 투명한 과정을 거칩니다.</p>
                    </div>

                    <div class="row text-center">
                        <div class="col-lg-4 process-step">
                            <div class="step-circle">1</div>
                            <h5 class="fw-bold mb-2">신청서 제출</h5>
                            <p class="text-muted small">개인정보, 경력사항, 전문분야를 포함한 신청서를 작성하여 제출합니다.</p>
                        </div>
                        <div class="col-lg-4 process-step">
                            <div class="step-circle">2</div>
                            <h5 class="fw-bold mb-2">검토 및 면접</h5>
                            <p class="text-muted small">제출된 신청서를 바탕으로 전문성을 검토하고 필요시 면접을 진행합니다.</p>
                        </div>
                        <div class="col-lg-4 process-step">
                            <div class="step-circle">3</div>
                            <h5 class="fw-bold mb-2">파트너 승인</h5>
                            <p class="text-muted small">승인 완료 후 파트너 대시보드에서 프로젝트 참여를 시작할 수 있습니다.</p>
                        </div>
                    </div>
                </section> --}}

                <!-- CTA Section -->
                {{-- <section class="mb-5">
                    <div class="card border-0 shadow-sm bg-gradient bg-primary text-white">
                        <div class="card-body p-5 text-center">
                            <h3 class="fw-bold mb-3">지금 바로 파트너 신청을 시작하세요!</h3>
                            <p class="mb-4 opacity-75">
                                전문성을 활용하여 새로운 기회를 만들어보세요.
                                신청은 무료이며, 언제든지 시작할 수 있습니다.
                            </p>
                            <div class="d-flex justify-content-center gap-3">
                                <a href="/home/partner/regist/create" class="btn btn-light btn-lg px-5 py-3">
                                    <i class="bi bi-file-earmark-plus me-2"></i>신청서 작성하기
                                </a>
                                <button class="btn btn-outline-light btn-lg px-5 py-3" onclick="showHelpModal()">
                                    <i class="bi bi-question-circle me-2"></i>궁금한 점이 있으신가요?
                                </button>
                            </div>
                        </div>
                    </div>
                </section> --}}

                <!-- Partner Tiers Information -->
                @if(isset($partnerTiers) && $partnerTiers->count() > 0)
                    <div class="mb-5">
                        <div class="d-flex align-items-center mb-4">
                            <i class="bi bi-award text-primary fs-3 me-3"></i>
                            <h3 class="h4 mb-0 fw-bold">파트너 등급 안내</h3>
                        </div>

                        <div class="row g-4">
                            @foreach($partnerTiers as $tier)
                                <div class="col-12 col-md-6 col-lg-4">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="card-header bg-gradient bg-primary text-white border-0">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h5 class="card-title mb-0 fw-bold">{{ $tier->tier_name }}</h5>
                                                <span class="badge bg-light text-primary fw-bold">
                                                    {{ $tier->commission_rate }}% 커미션
                                                </span>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <p class="text-muted small mb-4">{{ $tier->description }}</p>

                                            <!-- Requirements -->
                                            @if(isset($tier->requirements) && is_array($tier->requirements))
                                                <div class="mb-4">
                                                    <h6 class="fw-bold text-success mb-3">
                                                        <i class="bi bi-check-circle me-1"></i>가입 요건
                                                    </h6>
                                                    <ul class="list-unstyled">
                                                        @foreach($tier->requirements as $key => $value)
                                                            <li class="d-flex align-items-start mb-2">
                                                                <i class="bi bi-check text-success me-2 mt-1"></i>
                                                                <small class="text-muted">
                                                                    @if($key === 'min_experience_months')
                                                                        최소 {{ $value }}개월 경력
                                                                    @elseif($key === 'min_completed_jobs')
                                                                        최소 {{ number_format($value) }}건 완료
                                                                    @elseif($key === 'min_rating')
                                                                        최소 {{ $value }}점 평점
                                                                    @else
                                                                        {{ $key }}: {{ is_array($value) ? implode(', ', $value) : $value }}
                                                                    @endif
                                                                </small>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif

                                            <!-- Benefits -->
                                            @if(isset($tier->benefits) && is_array($tier->benefits))
                                                <div class="mb-3">
                                                    <h6 class="fw-bold text-info mb-3">
                                                        <i class="bi bi-gift me-1"></i>혜택
                                                    </h6>
                                                    <ul class="list-unstyled">
                                                        @foreach($tier->benefits as $key => $value)
                                                            <li class="d-flex align-items-start mb-2">
                                                                <i class="bi bi-star text-warning me-2 mt-1"></i>
                                                                <small class="text-muted">
                                                                    @if($key === 'job_assignment_priority')
                                                                        {{ $value === 'high' ? '높은' : '일반' }} 우선순위 업무 배정
                                                                    @elseif($key === 'maximum_concurrent_jobs')
                                                                        최대 {{ $value }}개 동시 업무
                                                                    @elseif($key === 'support_response_time')
                                                                        {{ $value }} 내 지원 응답
                                                                    @else
                                                                        {{ $key }}: {{ is_array($value) ? implode(', ', $value) : $value }}
                                                                    @endif
                                                                </small>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-top mt-5">
        <div class="container-fluid">
            <div class="row justify-content-center py-4">
                <div class="col-12 col-xl-10">
                    <div class="text-center">
                        <div class="d-flex justify-content-center align-items-center mb-2">
                            <i class="bi bi-headset text-primary me-2"></i>
                            <small class="text-muted">
                                파트너 신청에 관한 문의사항이 있으시면 고객센터로 연락해 주세요.
                            </small>
                        </div>
                        <div class="d-flex justify-content-center gap-3">
                            <a href="#" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-telephone me-1"></i>전화 문의
                            </a>
                            <a href="#" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-envelope me-1"></i>이메일 문의
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Smooth scrolling to sections
function scrollToSection(sectionId) {
    const element = document.getElementById(sectionId);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

// Help modal functionality
function showHelpModal() {
    const helpModal = new bootstrap.Modal(document.getElementById('helpModal') || createHelpModal());
    helpModal.show();
}

// Create help modal dynamically
function createHelpModal() {
    const modalHtml = `
        <div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="helpModalLabel">
                            <i class="bi bi-question-circle me-2"></i>파트너 신청 관련 도움말
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-4">
                            <div class="col-12">
                                <h6 class="fw-bold text-primary mb-3">
                                    <i class="bi bi-info-circle me-2"></i>자주 묻는 질문
                                </h6>
                            </div>
                            <div class="col-12">
                                <div class="accordion" id="faqAccordion">
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="faq1">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1">
                                                파트너 신청 자격은 무엇인가요?
                                            </button>
                                        </h2>
                                        <div id="collapse1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                            <div class="accordion-body">
                                                관련 분야의 전문 기술과 경험을 보유하신 분이라면 누구나 신청 가능합니다.
                                                자세한 자격 요건은 신청서 작성 시 확인하실 수 있습니다.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="faq2">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2">
                                                신청 후 승인까지 얼마나 걸리나요?
                                            </button>
                                        </h2>
                                        <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                            <div class="accordion-body">
                                                일반적으로 3-5일 정도 소요됩니다. 복잡한 케이스의 경우 추가 검토가 필요할 수 있습니다.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="faq3">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3">
                                                파트너 수수료는 어떻게 책정되나요?
                                            </button>
                                        </h2>
                                        <div id="collapse3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                            <div class="accordion-body">
                                                프로젝트 규모, 난이도, 파트너 등급에 따라 차등 적용됩니다.
                                                자세한 수수료 체계는 파트너 승인 후 안내해 드립니다.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="d-flex justify-content-between w-100">
                            <div>
                                <small class="text-muted">
                                    <i class="bi bi-headset me-1"></i>추가 문의: 고객센터
                                </small>
                            </div>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHtml);
    return document.getElementById('helpModal');
}

// Initialize page interactions
document.addEventListener('DOMContentLoaded', function() {
    // Add smooth hover effects for feature cards
    const featureCards = document.querySelectorAll('.feature-card');
    featureCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transition = 'transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out';
            this.style.boxShadow = '0 8px 25px rgba(0,0,0,0.15)';
        });

        card.addEventListener('mouseleave', function() {
            this.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
        });
    });

    // Add loading state for main CTA buttons
    const ctaButtons = document.querySelectorAll('a[href*="/create"], a[href*="/partner"]');
    ctaButtons.forEach(button => {
        button.addEventListener('click', function() {
            const icon = this.querySelector('i');
            const originalClass = icon.className;
            icon.className = 'bi bi-arrow-clockwise me-2';
            icon.style.animation = 'spin 1s linear infinite';

            // Reset after a short delay (in case navigation is slow)
            setTimeout(() => {
                icon.className = originalClass;
                icon.style.animation = '';
            }, 2000);
        });
    });
});

// Add CSS animation for loading state
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);
</script>
@endsection
