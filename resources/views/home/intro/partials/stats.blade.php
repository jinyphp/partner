<style>
    .feature-card {
        transition: all 0.3s ease-in-out;
        border-radius: 20px;
    }

    .feature-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15) !important;
    }

    .icon-circle {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
        position: relative;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .icon-circle:hover {
        transform: scale(1.05);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
    }

    .icon-circle.success {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    }

    .icon-circle.primary {
        background: linear-gradient(135deg, #6f42c1 0%, #007bff 100%);
    }

    .icon-circle.info {
        background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
    }

    .icon-circle.warning {
        background: linear-gradient(135deg, #fd7e14 0%, #ffc107 100%);
    }

    .icon-circle i {
        color: white !important;
        font-size: 2.5rem;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    }

    .feature-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 1rem;
    }

    .feature-description {
        color: #6c757d;
        line-height: 1.6;
        font-size: 0.95rem;
    }
</style>

<section>
    <div class="row g-4 align-items-start">
        <div class="col-12 col-md-6">
            <div class="card border-0 shadow-sm h-100 feature-card">
                <div class="card-body text-center p-4">
                    <div class="icon-circle success">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                    <h5 class="feature-title">경쟁력 있는 수수료</h5>
                    <p class="feature-description mb-0">프로젝트 성과에 따른 합리적인 수수료 체계</p>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="card border-0 shadow-sm h-100 feature-card">
                <div class="card-body text-center p-4">
                    <div class="icon-circle primary">
                        <i class="bi bi-briefcase"></i>
                    </div>
                    <h5 class="feature-title">다양한 프로젝트</h5>
                    <p class="feature-description mb-0">자신의 전문성에 맞는 다양한 프로젝트 기회</p>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="card border-0 shadow-sm h-100 feature-card">
                <div class="card-body text-center p-4">
                    <div class="icon-circle info">
                        <i class="bi bi-headset"></i>
                    </div>
                    <h5 class="feature-title">전문 지원</h5>
                    <p class="feature-description mb-0">프로젝트 진행 중 전문팀의 24/7 지원</p>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="card border-0 shadow-sm h-100 feature-card">
                <div class="card-body text-center p-4">
                    <div class="icon-circle warning">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <h5 class="feature-title">성장 기회</h5>
                    <p class="feature-description mb-0">지속적인 교육과 스킬 개발 프로그램</p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Enhanced hover effects for feature cards
        const featureCards = document.querySelectorAll('.feature-card');
        featureCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                const iconCircle = this.querySelector('.icon-circle');
                if (iconCircle) {
                    iconCircle.style.transform = 'scale(1.1) rotate(5deg)';
                    iconCircle.style.boxShadow = '0 15px 50px rgba(0, 0, 0, 0.2)';
                }
            });

            card.addEventListener('mouseleave', function() {
                const iconCircle = this.querySelector('.icon-circle');
                if (iconCircle) {
                    iconCircle.style.transform = 'scale(1) rotate(0deg)';
                    iconCircle.style.boxShadow = '0 8px 30px rgba(0, 0, 0, 0.1)';
                }
            });
        });

        // Staggered animation on load
        const cards = document.querySelectorAll('.feature-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(50px)';

            setTimeout(() => {
                card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 150);
        });
    });
</script>
