{{--
===============================================================================
파트너 매출 수정 페이지
===============================================================================

기능:
- 파트너의 개인 매출 정보 수정 (대기 상태만)
- AJAX 방식으로 실시간 처리 및 응답
- 유효성 검사 및 에러 처리
- 성공 시 상세 페이지로 이동

처리 흐름:
1. 기존 매출 정보를 폼에 미리 채워넣기
2. 폼 입력 및 유효성 검사 (클라이언트 사이드)
3. AJAX PUT 요청으로 UpdateController 호출
4. 서버 사이드 검증 및 데이터베이스 업데이트
5. JSON 응답 처리 (성공/실패)
6. 성공 시 매출 상세 페이지로 자동 이동

사용자 인증:
- JWT/세션 기반 사용자 인증 필수
- 파트너 등록 여부 확인
- 본인 매출만 수정 가능
- 대기 상태 매출만 수정 가능

--}}

@extends('jiny-partner::layouts.home')

@section('title', $pageTitle ?? '매출 수정')

@section('content')
    <div class="container-fluid p-6">
        {{-- ========================================
         페이지 헤더 섹션
         - 페이지 제목 및 브레드크럼 네비게이션
         - 돌아가기 버튼
    ========================================= --}}
        <div class="row">
            <div class="col-lg-12 col-md-12 col-12">
                <div class="border-bottom pb-3 mb-3 d-flex align-items-center justify-content-between">
                    <div>
                        <h1 class="mb-1 h2 fw-bold">{{ $pageTitle }}</h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('home.partner.index') }}">파트너 홈</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('home.partner.sales.index') }}">판매 대시보드</a>
                                </li>
                                <li class="breadcrumb-item"><a href="{{ route('home.partner.sales.show', $sale->id) }}">매출 상세</a>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">수정</li>
                            </ol>
                        </nav>
                    </div>
                    <div>
                        <a href="{{ route('home.partner.sales.show', $sale->id) }}" class="btn btn-outline-secondary">
                            <i class="fe fe-arrow-left me-2"></i>돌아가기
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================
         현재 매출 정보 표시 섹션 (수정 전 확인용)
    ========================================= --}}
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="alert alert-info mb-4">
                    <div class="d-flex align-items-center">
                        <i class="fe fe-info me-2"></i>
                        <div class="flex-grow-1">
                            <strong>수정하려는 매출 정보</strong>
                            <div class="mt-1">
                                <small>
                                    <strong>매출 ID:</strong> #{{ $sale->id }} |
                                    <strong>상품명:</strong> {{ $sale->title }} |
                                    <strong>금액:</strong> {{ number_format($sale->amount) }}원 |
                                    <strong>상태:</strong>
                                    <span class="badge bg-warning">{{ $sale->status === 'pending' ? '대기' : $sale->status }}</span>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================
         매출 수정 폼 섹션
         - 카드 레이아웃으로 구성된 수정 폼
         - AJAX 처리를 위한 ID 부여
         - 실시간 유효성 검사 및 피드백
         - 기존 데이터로 폼 채워넣기
    ========================================= --}}
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fe fe-edit-2 me-2"></i>매출 수정
                        </h4>
                    </div>
                    <div class="card-body">
                        {{-- 알림 메시지 영역 (AJAX 응답 표시용) --}}
                        <div id="alert-container"></div>

                        {{-- AJAX 매출 수정 폼 --}}
                        <form id="sales-form" novalidate>
                            @csrf
                            @method('PUT')
                            {{-- 상품명 및 매출 금액 입력 섹션 --}}
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="product_name" class="form-label">상품명 <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="product_name" name="product_name"
                                            value="{{ old('product_name', $sale->title) }}"
                                            placeholder="판매한 상품명을 입력해주세요" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="amount" class="form-label">매출 금액 <span
                                                class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="amount" name="amount"
                                            value="{{ old('amount', $sale->amount) }}"
                                            min="0" step="0.01" placeholder="150000" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            {{-- 판매 일시 및 카테고리 선택 섹션 --}}
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="sales_date" class="form-label">판매 일시 <span
                                                class="text-danger">*</span></label>
                                        <input type="datetime-local" class="form-control" id="sales_date" name="sales_date"
                                            value="{{ old('sales_date', \Carbon\Carbon::parse($sale->sales_date)->format('Y-m-d\TH:i')) }}" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="category" class="form-label">카테고리</label>
                                        <select class="form-control" id="category" name="category">
                                            <option value="">카테고리 선택</option>
                                            <option value="product" {{ old('category', $sale->category) === 'product' ? 'selected' : '' }}>상품</option>
                                            <option value="service" {{ old('category', $sale->category) === 'service' ? 'selected' : '' }}>서비스</option>
                                            <option value="subscription" {{ old('category', $sale->category) === 'subscription' ? 'selected' : '' }}>구독</option>
                                            <option value="commission" {{ old('category', $sale->category) === 'commission' ? 'selected' : '' }}>커미션</option>
                                            <option value="general" {{ old('category', $sale->category) === 'general' ? 'selected' : '' }}>일반</option>
                                            <option value="other" {{ old('category', $sale->category) === 'other' ? 'selected' : '' }}>기타</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>


                            {{-- 고객명 입력 섹션 (Livewire 컴포넌트 사용) --}}
                            @livewire('jiny-partner::customer', [
                                'value' => old('customer_name', $sale->customer_name),
                                'customerName' => $sale->customer_name,
                                'customerEmail' => '', // 필요시 확장 가능
                                'customerUuid' => '', // 필요시 확장 가능
                                'customerShard' => '', // 필요시 확장 가능
                                'placeholder' => '고객 이름 또는 이메일을 입력하세요'
                            ])


                            {{-- 설명 입력 섹션 --}}
                            <div class="mb-3">
                                <label for="description" class="form-label">설명</label>
                                <textarea class="form-control" id="description" name="description" rows="3"
                                    placeholder="매출에 대한 추가 설명을 입력해주세요 (선택사항)">{{ old('description', $sale->description) }}</textarea>
                                <div class="invalid-feedback"></div>
                            </div>

                            {{-- 숨겨진 필드 --}}
                            <input type="hidden" name="status" value="pending">

                            {{-- 버튼 섹션 --}}
                            <div class="text-end">
                                <a href="{{ route('home.partner.sales.show', $sale->id) }}" class="btn btn-secondary me-2">취소</a>
                                <button type="submit" class="btn btn-primary" id="submit-btn">
                                    <span class="btn-text">
                                        <i class="fe fe-save me-1"></i>수정 저장
                                    </span>
                                    <span class="btn-loading d-none">
                                        <span class="spinner-border spinner-border-sm me-1" role="status"
                                            aria-hidden="true"></span>
                                        수정 중...
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- ========================================
     매출 수정 AJAX 처리 JavaScript
========================================= --}}
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('sales-form');
            const submitBtn = document.getElementById('submit-btn');
            const alertContainer = document.getElementById('alert-container');

            // 폼 제출 처리 (PUT 요청으로 수정)
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                if (!validateForm()) return;

                setLoading(true);
                clearErrors();

                // FormData에 _method 필드 추가 (PUT 메소드 지원)
                const formData = new FormData(form);
                formData.append('_method', 'PUT');

                fetch('{{ route('home.partner.sales.update', $sale->id) }}', {
                        method: 'POST', // Laravel에서는 POST로 보내고 _method로 PUT 처리
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: formData
                    })
                    .then(response => response.json().then(data => ({
                        status: response.status,
                        data
                    })))
                    .then(result => {
                        const {
                            status,
                            data
                        } = result;

                        if (status === 200 && data.success) {
                            showAlert('success', data.message || '매출이 수정되었습니다.');
                            setTimeout(() => window.location.href =
                                '{{ route('home.partner.sales.show', $sale->id) }}', 1000);
                        } else if (status === 422) {
                            showAlert('danger', '입력 데이터를 확인해주세요.');
                            showErrors(data.errors);
                            setLoading(false);
                        } else if (status === 403) {
                            showAlert('danger', data.message || '수정 권한이 없습니다.');
                            setLoading(false);
                        } else if (status === 400) {
                            showAlert('danger', data.message || '대기 상태의 매출만 수정할 수 있습니다.');
                            setTimeout(() => window.location.href =
                                '{{ route('home.partner.sales.show', $sale->id) }}', 1500);
                        } else {
                            // 디버그 정보가 있으면 console에 출력
                            if (data.debug) {
                                console.error('서버 오류 상세:', data.debug);
                            }
                            showAlert('danger', data.message || '매출 수정 중 오류가 발생했습니다.');
                            setLoading(false);
                        }
                    })
                    .catch(error => {
                        console.error('네트워크 오류:', error);
                        showAlert('danger', '서버 연결에 실패했습니다.');
                        setLoading(false);
                    });
            });

            // 유효성 검사
            function validateForm() {
                let valid = true;
                ['product_name', 'amount', 'sales_date'].forEach(name => {
                    const field = document.getElementById(name);
                    if (!field?.value.trim()) {
                        showFieldError(field, '필수 항목입니다.');
                        valid = false;
                    }
                });

                const amount = document.getElementById('amount');
                if (amount.value && (isNaN(amount.value) || amount.value < 0)) {
                    showFieldError(amount, '올바른 금액을 입력해주세요.');
                    valid = false;
                }

                return valid;
            }

            // 로딩 상태 관리
            function setLoading(loading) {
                submitBtn.disabled = loading;
                submitBtn.querySelector('.btn-text').classList.toggle('d-none', loading);
                submitBtn.querySelector('.btn-loading').classList.toggle('d-none', !loading);
            }

            // 알림 표시
            function showAlert(type, message) {
                const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
                alertContainer.innerHTML = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`;
                alertContainer.scrollIntoView({
                    behavior: 'smooth'
                });
            }

            // 필드 오류 표시
            function showFieldError(field, message) {
                field.classList.add('is-invalid');
                const feedback = field.parentNode.querySelector('.invalid-feedback');
                if (feedback) feedback.textContent = message;
            }

            // 서버 오류 표시
            function showErrors(errors) {
                Object.keys(errors).forEach(field => {
                    const input = document.getElementById(field);
                    const message = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
                    if (input) showFieldError(input, message);
                });
            }

            // 오류 초기화
            function clearErrors() {
                alertContainer.innerHTML = '';
                form.querySelectorAll('.is-invalid').forEach(field => {
                    field.classList.remove('is-invalid');
                    const feedback = field.parentNode.querySelector('.invalid-feedback');
                    if (feedback) feedback.textContent = '';
                });
            }

            // 실시간 오류 제거
            form.addEventListener('input', function(e) {
                if (e.target.classList.contains('is-invalid')) {
                    e.target.classList.remove('is-invalid');
                    const feedback = e.target.parentNode.querySelector('.invalid-feedback');
                    if (feedback) feedback.textContent = '';
                }
            });
        });

        // ========================================
        // 고객 검색 기능 JavaScript (순수 JavaScript 컴포넌트 사용)
        // ========================================
        let selectedCustomerData = null; // 선택된 고객 정보 저장

        /**
         * 고객 선택 콜백 함수 (컴포넌트에서 호출)
         *
         * @param {string} uuid - 사용자 UUID
         * @param {string} name - 사용자 이름
         * @param {string} email - 사용자 이메일
         * @param {object} userData - 전체 사용자 데이터
         */
        function handleCustomerSelect(uuid, name, email, userData) {
            selectedCustomerData = {
                uuid,
                name,
                email,
                ...userData
            };

            console.log('Customer selected:', selectedCustomerData);

            // 매출 수정 폼에 추가 정보 설정 (필요한 경우)
            // 예: 고객 이메일을 숨겨진 필드에 설정
            // const hiddenEmailField = document.getElementById('customer_email');
            // if (hiddenEmailField) {
            //     hiddenEmailField.value = email;
            // }

            showAlert('success', `${name} 고객을 선택했습니다.`);
        }

        // 컴포넌트 이벤트 리스너 등록
        document.addEventListener('user-search-user-selected', function(event) {
            const user = event.detail.user;
            console.log('User selected via custom event:', user);

            // 추가 처리가 필요한 경우 여기에 작성
        });

        document.addEventListener('user-search-user-cleared', function(event) {
            selectedCustomerData = null;
            console.log('User selection cleared via custom event');
        });
    </script>
@endpush

{{-- 회원 검색 기능은 user-search-input 컴포넌트에 통합되어 있음 --}}
