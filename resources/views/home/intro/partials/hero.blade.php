<style>
    .hero-gradient {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        position: relative;
    }

    .hero-content {
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

<!-- Main content -->
<section class="row align-items-center mb-4">
    <div class="col-12">
        <div class="text-center">
            <h1 class="display-5 fw-bold mb-3 hero-text">파트너가 되어 함께 성장하세요</h1>
            <p class="lead mb-4 hero-lead mx-auto" style="max-width: 600px;">
                전문 기술을 활용하여 다양한 프로젝트에 참여하고,
                성과에 따른 보상을 받으며 전문성을 키워나가세요.
            </p>

            @if (!isset($statusInfo) || ($statusInfo['status'] ?? '') === 'new')
                <div class="d-flex gap-3 justify-content-center">
                    <a href="{{ route('home.partner.regist.index') }}" class="btn btn-primary px-4 py-2">
                        <i class="bi bi-rocket-takeoff me-2"></i>지금 시작하기
                    </a>
                    <button class="btn btn-outline-primary px-4 py-2" onclick="scrollToSection('benefits')">
                        <i class="bi bi-info-circle me-2"></i>자세히 알아보기
                    </button>
                </div>
            @endif
        </div>
    </div>
</section>

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

    // Loading animation for CTA buttons
    document.addEventListener('DOMContentLoaded', function() {
        const ctaButtons = document.querySelectorAll('a[href*="/regist"]');
        ctaButtons.forEach(button => {
            button.addEventListener('click', function() {
                const icon = this.querySelector('i');
                if (icon && icon.classList.contains('bi-rocket-takeoff')) {
                    const originalClass = icon.className;
                    icon.className = 'bi bi-arrow-clockwise me-2';
                    icon.style.animation = 'spin 1s linear infinite';

                    setTimeout(() => {
                        icon.className = originalClass;
                        icon.style.animation = '';
                    }, 2000);
                }
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
    });
</script>
