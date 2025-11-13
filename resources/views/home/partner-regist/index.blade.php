@extends('jiny-partner::layouts.home')

@section('title', '파트너 가입 - 추천 코드 입력')

@section('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
@endsection

@section('content')
    <div class="container-fluid p-4">

        <!-- 헤더 -->
        <section class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-2">
                            파트너 가입
                        </h2>
                        <p class="text-muted mb-0">{{ $user->name ?? '사용자' }}님으로 로그인됨</p>
                    </div>
                    <div>
                        <a href="{{ route('home.partner.intro') }}" class="btn btn-outline-secondary ">
                            <i class="bi bi-arrow-left me-1"></i>이전으로
                        </a>
                        <a href="/home" class="btn btn-outline-secondary">
                            <i class="bi bi-house me-1"></i>홈으로 돌아가기
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- 알림 메시지 -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <strong>성공!</strong> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>오류!</strong> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('info'))
            <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-info-circle me-2"></i>
                <strong>안내!</strong> {{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <section class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                <div class="text-center mb-5">
                    <p class="lead text-muted mb-2">추천 파트너 코드를 입력해주세요</p>
                    <p class="text-muted small">기존 파트너의 추천을 통해서만 가입이 가능합니다</p>
                </div>
            </div>
        </section>


        <!-- Main Content -->
        <div class="row justify-content-center align-items-start">
            <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                <!-- 파트너 코드 입력 카드 -->
                <div class="card border-0 shadow-lg h-100">
                    <div class="card-header bg-primary text-white border-0">
                        <h5 class="card-title mb-0 fw-bold d-flex align-items-center">
                            <i class="bi bi-shield-check me-2"></i>
                            추천 파트너 코드 입력
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <form id="partnerCodeForm" action="javascript:void(0)" method="post">
                            @csrf
                            <div class="mb-4">
                                <label for="partner_code" class="form-label fw-semibold">
                                    파트너 코드 <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-key text-primary"></i>
                                    </span>
                                    <input type="text" id="partner_code" name="partner_code"
                                        class="form-control text-center fw-bold"
                                        style="font-family: 'Courier New', monospace; letter-spacing: 2px; text-transform: uppercase;"
                                        maxlength="20" pattern="[A-Z0-9]{20}" placeholder="추천인 코드를 입력해 주세요." required
                                        autocomplete="off">
                                </div>
                                <div id="partner_code_error" class="invalid-feedback d-none"></div>
                                <div class="form-text">
                                    <i class="bi bi-info-circle me-1"></i>
                                    영문 대문자와 숫자로 구성된 20자리 코드를 입력해주세요
                                </div>
                            </div>

                            <button type="submit" id="submitBtn" class="btn btn-primary w-100 fw-semibold"
                                onclick="
                                event.preventDefault();
                                const partnerCode = document.getElementById('partner_code').value.trim();
                                if (partnerCode.length !== 20) {
                                    alert('파트너 코드는 정확히 20자리여야 합니다.');
                                    return;
                                }
                                if (!/^[A-Z0-9]{20}$/.test(partnerCode)) {
                                    alert('파트너 코드는 영문 대문자와 숫자만 입력 가능합니다.');
                                    return;
                                }
                                alert('파트너 코드 ' + partnerCode + '로 가입을 진행합니다.');
                                setTimeout(function() {
                                    window.location.href = '/home/partner/regist/create/' + partnerCode;
                                }, 500);
                            ">
                                <span id="submitText">
                                    <i class="bi bi-rocket-takeoff me-2"></i>
                                    가입 진행
                                </span>
                                <span id="loadingSpinner" class="d-none">
                                    <span class="spinner-border spinner-border-sm me-2" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </span>
                                    검증 중...
                                </span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                <!-- 도움말 카드 -->
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header">
                        <div class="d-flex justify-content-between">
                            <h6 class="card-title text-primary fw-bold">
                                <i class="bi bi-question-circle me-2"></i>파트너 코드가 없으신가요?
                            </h6>
                            <div>
                                <a href="/home/partner/search/referrer" class="btn btn-primary btn-sm">파트너 검색</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">

                        <div class="text-muted">
                            <div class="d-flex align-items-start mb-2">
                                <i class="bi bi-dot text-primary fs-3 lh-1 me-1"></i>
                                <span class="small">기존 파트너에게 추천 코드를 요청하세요</span>
                            </div>
                            <div class="d-flex align-items-start mb-2">
                                <i class="bi bi-dot text-primary fs-3 lh-1 me-1"></i>
                                <span class="small">파트너 프로그램은 추천을 통해서만 가입할 수 있습니다</span>
                            </div>
                            <div class="d-flex align-items-start mb-2">
                                <i class="bi bi-dot text-primary fs-3 lh-1 me-1"></i>
                                <span class="small">문의사항이 있으시면 고객센터로 연락해주세요</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 추가 정보 -->
        <div class="text-center mt-4">
            <p class="text-muted small">
                이미 파트너이신가요?
                <a href="{{ route('home.partner.index') }}" class="text-decoration-none fw-semibold">
                    파트너 대시보드로 이동
                </a>
            </p>
        </div>

        <!-- Success Toast -->
        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 11">
            <div id="successToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                    <strong class="me-auto">성공</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body" id="toastBody">
                    <!-- 메시지가 여기에 표시됩니다 -->
                </div>
            </div>
        </div>

    </div>

@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 전역 함수로 정의하여 onclick에서 호출 가능하게 함
        window.handlePartnerCodeClick = function(event) {
            console.log('버튼 클릭됨!');
            event.preventDefault();
            event.stopPropagation();

            const partnerCodeInput = document.getElementById('partner_code');
            if (!partnerCodeInput) {
                console.error('파트너 코드 입력 필드를 찾을 수 없습니다.');
                return;
            }

            const partnerCode = partnerCodeInput.value.trim();
            console.log('입력된 파트너 코드:', partnerCode);

            // 유효성 검사
            if (partnerCode.length !== 20) {
                alert('파트너 코드는 정확히 20자리여야 합니다.');
                return;
            }

            if (!/^[A-Z0-9]{20}$/.test(partnerCode)) {
                alert('파트너 코드는 영문 대문자와 숫자만 입력 가능합니다.');
                return;
            }

            // 리다이렉션
            const redirectUrl = `/home/partner/regist/create/${partnerCode}`;
            console.log('리다이렉션 URL:', redirectUrl);

            alert(`파트너 코드 ${partnerCode}로 가입을 진행합니다.`);

            setTimeout(() => {
                console.log('페이지 이동 실행');
                window.location.href = redirectUrl;
            }, 500);
        };

        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM 로드 완료');

            const partnerCodeInput = document.getElementById('partner_code');
            if (!partnerCodeInput) {
                console.error('파트너 코드 입력 필드를 찾을 수 없습니다.');
                return;
            }

            // 파트너 코드 입력 처리
            partnerCodeInput.addEventListener('input', function() {
                // 대문자 변환 및 영숫자만 허용
                this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');

                // 20자리 제한
                if (this.value.length > 20) {
                    this.value = this.value.substring(0, 20);
                }

                // 에러 상태 초기화
                const errorDiv = document.getElementById('partner_code_error');
                if (errorDiv) {
                    errorDiv.classList.add('d-none');
                }

                // 실시간 유효성 검사
                if (this.value.length === 20) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    this.classList.remove('is-valid', 'is-invalid');
                }
            });

            // 포커스 시 전체 선택
            partnerCodeInput.addEventListener('focus', function() {
                this.select();
            });

            // 초기 포커스 설정
            partnerCodeInput.focus();

            // 카드 호버 효과
            const cards = document.querySelectorAll('.card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transition =
                        'transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out';
                    this.style.transform = 'translateY(-2px)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            console.log('파트너 코드 입력 시스템 준비 완료');
        });
    </script>
@endsection

@push('styles')
    <style>
        .hero-gradient {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .feature-card {
            transition: transform 0.2s ease-in-out;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        /* 입력 필드 커스텀 스타일 */
        #partner_code {
            transition: all 0.3s ease;
        }

        #partner_code:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        /* 버튼 호버 효과 */
        #submitBtn {
            transition: all 0.2s ease-in-out;
        }

        #submitBtn:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        #submitBtn:active {
            transform: translateY(0);
        }

        /* 카드 애니메이션 */
        .card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        /* Toast 애니메이션 개선 */
        .toast {
            backdrop-filter: blur(10px);
        }

        /* 반응형 간격 조정 */
        @media (max-width: 768px) {
            .container-fluid {
                padding: 1rem !important;
            }

            .col-12.col-md-8.col-lg-6.col-xl-5 {
                padding: 0 1rem;
            }
        }
    </style>
@endpush
