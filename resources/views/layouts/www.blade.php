<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Jiny Partners')</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    @stack('styles')

    <!-- Custom CSS -->
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
        }
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
        }
        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
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
</head>
<body>
    <!-- Navigation -->
    @section('navigation')
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm @yield('nav-class', 'sticky-top')">
        <div class="container">
            <a class="navbar-brand fw-bold" href="{{ route('partner.www.index') }}">
                <i class="bi bi-people-fill me-2"></i>Jiny Partners
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('partner.www.about')) active @endif" href="{{ route('partner.www.about') }}">파트너 소개</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('partner.www.tiers')) active @endif" href="{{ route('partner.www.tiers') }}">등급 시스템</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('partner.www.performance')) active @endif" href="{{ route('partner.www.performance') }}">성과 관리</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('partner.www.commission')) active @endif" href="{{ route('partner.www.commission') }}">수익 구조</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('partner.www.faq')) active @endif" href="{{ route('partner.www.faq') }}">FAQ</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="{{ route('partner.www.application') }}" class="btn btn-primary me-2">파트너 지원</a>
                    <a href="{{ route('partner.www.contact') }}" class="btn btn-outline-primary">문의하기</a>
                </div>
            </div>
        </div>
    </nav>
    @show

    <!-- Main Content -->
    @yield('content')

    <!-- Footer -->
    @section('footer')
    <footer class="footer bg-dark-stable py-8">
        <div class="container">
            <div class="row gy-6 pb-8">
                <!-- Company Info -->
                <div class="col-xl-3 col-lg-4 col-md-6 col-12">
                    <div class="d-flex flex-column gap-4">
                        <div>
                            <h5 class="text-white-stable mb-0">
                                <i class="bi bi-people-fill me-2"></i>Jiny Partners
                            </h5>
                        </div>
                        <p class="mb-0">
                            혁신적인 파트너 관리 시스템으로 여러분의 성공을 지원합니다.
                            함께 성장하는 파트너 생태계를 만들어갑니다.
                        </p>
                        <!-- Social Links -->
                        <div class="d-flex gap-3 mt-2">
                            <a href="#" class="nav-link">
                                <i class="bi bi-facebook fs-5"></i>
                            </a>
                            <a href="#" class="nav-link">
                                <i class="bi bi-twitter fs-5"></i>
                            </a>
                            <a href="#" class="nav-link">
                                <i class="bi bi-linkedin fs-5"></i>
                            </a>
                            <a href="#" class="nav-link">
                                <i class="bi bi-instagram fs-5"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- System Links -->
                <div class="col-xl-2 col-md-3 col-6">
                    <div class="d-flex flex-column gap-3">
                        <span class="text-white-stable">시스템</span>
                        <ul class="list-unstyled mb-0 d-flex flex-column nav nav-footer nav-x-0">
                            <li><a href="{{ route('partner.www.about') }}" class="nav-link">파트너 소개</a></li>
                            <li><a href="{{ route('partner.www.tiers') }}" class="nav-link">등급 시스템</a></li>
                            <li><a href="{{ route('partner.www.performance') }}" class="nav-link">성과 관리</a></li>
                            <li><a href="{{ route('partner.www.targets') }}" class="nav-link">목표 관리</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Revenue Links -->
                <div class="col-xl-2 col-md-3 col-6">
                    <div class="d-flex flex-column gap-3">
                        <span class="text-white-stable">수익</span>
                        <ul class="list-unstyled mb-0 d-flex flex-column nav nav-footer nav-x-0">
                            <li><a href="{{ route('partner.www.commission') }}" class="nav-link">커미션 구조</a></li>
                            <li><a href="{{ route('partner.www.payment') }}" class="nav-link">지급 관리</a></li>
                            <li><a href="{{ route('partner.www.network') }}" class="nav-link">네트워크</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Support Links -->
                <div class="col-xl-2 col-md-3 col-6">
                    <div class="d-flex flex-column gap-3">
                        <span class="text-white-stable">지원</span>
                        <ul class="list-unstyled mb-0 d-flex flex-column nav nav-footer nav-x-0">
                            <li><a href="{{ route('partner.www.application') }}" class="nav-link">파트너 지원</a></li>
                            <li><a href="{{ route('partner.www.interview') }}" class="nav-link">면접 과정</a></li>
                            <li><a href="{{ route('partner.www.faq') }}" class="nav-link">FAQ</a></li>
                            <li><a href="{{ route('partner.www.contact') }}" class="nav-link">문의하기</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Contact Info -->
                <div class="col-xl-3 col-lg-3 col-md-6 col-12">
                    <div class="d-flex flex-column gap-5">
                        <div class="d-flex flex-column gap-3">
                            <span class="text-white-stable">연락처</span>
                            <ul class="list-unstyled mb-0 d-flex flex-column nav nav-footer nav-x-0">
                                <li>
                                    전화:
                                    <span class="fw-semibold">02-1234-5678</span>
                                </li>
                                <li>
                                    운영 시간:
                                    <span class="fw-semibold">평일 9:00-18:00</span>
                                </li>
                                <li>
                                    이메일:
                                    <span class="fw-semibold">partners@jiny.com</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Copyright -->
            <div class="row align-items-center g-0 border-top border-gray-800 pt-3 flex-column gap-1 flex-lg-row gap-lg-0">
                <div class="col-lg-6 col-12 text-center text-md-start">
                    <span>
                        ©
                        <span id="copyright-year">
                            <script>
                                document.getElementById("copyright-year").appendChild(document.createTextNode(new Date().getFullYear()));
                            </script>
                        </span>
                        Jiny Partners. All rights reserved.
                    </span>
                </div>
                <div class="col-12 col-lg-6">
                    <nav class="nav nav-footer justify-content-center justify-content-md-start justify-content-lg-end">
                        <a class="nav-link" href="#">개인정보처리방침</a>
                        <a class="nav-link" href="#">이용약관</a>
                        <a class="nav-link" href="#">쿠키 정책</a>
                    </nav>
                </div>
            </div>
        </div>
    </footer>
    @show

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    @stack('scripts')
</body>
</html>