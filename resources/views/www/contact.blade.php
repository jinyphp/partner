@extends('jiny-partner::layouts.www')

@section('title', '연락처 - Jiny Partners')

@section('content')
    <section class="py-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-4">문의하기</h1>
            <p class="lead">언제든지 연락주세요. 전문 상담팀이 도움을 드리겠습니다.</p>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-6">
                    <h3 class="mb-4">연락처 정보</h3>
                    <div class="row g-4">
                        <div class="col-12">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                        <i class="bi bi-telephone"></i>
                                    </div>
                                </div>
                                <div class="ms-3">
                                    <h5>전화</h5>
                                    <p class="text-muted mb-0">02-1234-5678</p>
                                    <small class="text-muted">평일 9:00 - 18:00</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                        <i class="bi bi-envelope"></i>
                                    </div>
                                </div>
                                <div class="ms-3">
                                    <h5>이메일</h5>
                                    <p class="text-muted mb-0">partners@jiny.com</p>
                                    <small class="text-muted">24시간 접수</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                        <i class="bi bi-geo-alt"></i>
                                    </div>
                                </div>
                                <div class="ms-3">
                                    <h5>주소</h5>
                                    <p class="text-muted mb-0">서울특별시 강남구 테헤란로 123</p>
                                    <small class="text-muted">지니 빌딩 10층</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card shadow">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-4">문의 양식</h5>
                            <form>
                                <div class="mb-3">
                                    <label class="form-label">이름 *</label>
                                    <input type="text" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">이메일 *</label>
                                    <input type="email" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">전화번호</label>
                                    <input type="tel" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">문의 유형</label>
                                    <select class="form-select">
                                        <option>파트너 지원 문의</option>
                                        <option>등급 승진 관련</option>
                                        <option>커미션 문의</option>
                                        <option>기술 지원</option>
                                        <option>기타</option>
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">문의 내용 *</label>
                                    <textarea class="form-control" rows="5" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-send me-2"></i>문의 보내기
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
