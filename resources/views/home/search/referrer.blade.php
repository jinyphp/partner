@extends('jiny-partner::layouts.home')

@section('title', '추천인 검색')

@section('content')
<div class="container-fluid p-6">
    <div class="row">
        <div class="col-lg-12">
            <div class="border-bottom pb-3 mb-3">
                <h1 class="mb-1 h2 fw-bold">추천인 검색</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home.partner.index') }}">파트너 홈</a></li>
                        <li class="breadcrumb-item active">추천인 검색</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fe fe-search me-2"></i>추천인 검색
                    </h4>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">
                        이메일을 입력하여 추천인으로 사용 가능한 파트너를 검색할 수 있습니다.
                    </p>

                    @if($errors->any())
                        <div class="alert alert-danger">
                            @foreach($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <form action="{{ route('home.partner.search.referrer') }}" method="GET" class="needs-validation" novalidate>
                        <div class="mb-4">
                            <label for="email" class="form-label">이메일 주소</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fe fe-mail"></i>
                                </span>
                                <input type="email"
                                       id="email"
                                       name="email"
                                       class="form-control"
                                       placeholder="예: partner@example.com"
                                       value="{{ request('email') }}"
                                       required>
                                <div class="invalid-feedback">
                                    올바른 이메일 주소를 입력해주세요.
                                </div>
                            </div>
                            <div class="form-text">
                                검색하려는 파트너의 등록된 이메일 주소를 입력하세요.
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fe fe-search me-2"></i>추천인 검색
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 검색 가이드 -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fe fe-info me-2"></i>추천인 검색 가이드
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold text-success">
                                <i class="fe fe-check-circle me-1"></i>추천 가능한 파트너
                            </h6>
                            <ul class="list-unstyled text-muted">
                                <li><i class="fe fe-check me-1 text-success"></i> 활성 상태의 파트너</li>
                                <li><i class="fe fe-check me-1 text-success"></i> 추천 한도 내의 파트너</li>
                                <li><i class="fe fe-check me-1 text-success"></i> 정상 등급의 파트너</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold text-warning">
                                <i class="fe fe-alert-circle me-1"></i>등급별 추천 한도
                            </h6>
                            <ul class="list-unstyled text-muted">
                                <li><i class="fe fe-award me-1 text-muted"></i> Bronze: 최대 10명</li>
                                <li><i class="fe fe-award me-1 text-info"></i> Silver: 최대 25명</li>
                                <li><i class="fe fe-award me-1 text-warning"></i> Gold: 최대 50명</li>
                                <li><i class="fe fe-award me-1 text-success"></i> Platinum: 최대 100명</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// 폼 유효성 검사
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// 엔터 키로 폼 제출
document.getElementById('email').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        this.closest('form').submit();
    }
});
</script>
@endpush