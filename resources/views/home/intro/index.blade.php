@extends('jiny-partner::layouts.home')

@section('title', $title ?? '파트너 프로그램 소개')

@section('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* 공통 스타일만 유지 - 각 컴포넌트 스타일은 개별 파일로 이동 */
        .container-fluid {
            max-width: 1200px;
        }

        /* 애니메이션 효과 */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeInUp 0.6s ease forwards;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid p-4">

        <!-- 헤더 -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-3">파트너 가입</h2>
                        {{-- <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); max-width: 300px;">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                            <i class="bi bi-person-fill text-white"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <h6 class="mb-1 fw-bold">{{ $user->name ?? '사용자' }}님</h6>
                                        <small class="text-muted">로그인된 사용자</small>
                                    </div>
                                </div>
                            </div>
                        </div> --}}
                    </div>
                    <div>
                        <a href="/home" class="btn btn-outline-secondary">
                            <i class="bi bi-house me-1"></i>홈으로 돌아가기
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @includeIf("jiny-partner::home.intro.partials.hero")

        @includeIf("jiny-partner::home.intro.partials.stats")

        @includeIf("jiny-partner::home.intro.partials.tiers")

        @includeIf("jiny-partner::home.intro.partials.faq")

        @includeIf("jiny-partner::home.intro.partials.help")

    </div>

@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Intersection Observer for smooth animations (공통 애니메이션)
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('section');
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-fade-in');
                    }
                });
            }, observerOptions);

            sections.forEach(section => {
                section.style.opacity = '0';
                section.style.transform = 'translateY(20px)';
                observer.observe(section);
            });
        });
    </script>
@endsection
