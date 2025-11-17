@extends('jiny-partner::layouts.www')

@section('title', '파트너 지원서 - Jiny Partners')

@section('content')
    <section class="py-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-4">파트너 지원서</h1>
            <p class="lead">새로운 기회를 시작하기 위한 첫 번째 단계입니다</p>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow">
                        <div class="card-body p-5">
                            <h4 class="mb-4">기본 정보</h4>
                            <form>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label">성명 *</label>
                                        <input type="text" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">영문명</label>
                                        <input type="text" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">생년월일 *</label>
                                        <input type="date" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">성별 *</label>
                                        <select class="form-select" required>
                                            <option value="">선택하세요</option>
                                            <option>남성</option>
                                            <option>여성</option>
                                        </select>
                                    </div>
                                </div>

                                <h4 class="mb-4">연락처 정보</h4>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label">이메일 *</label>
                                        <input type="email" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">휴대전화 *</label>
                                        <input type="tel" class="form-control" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">주소 *</label>
                                        <input type="text" class="form-control" required>
                                    </div>
                                </div>

                                <h4 class="mb-4">파트너 정보</h4>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label">파트너 유형 *</label>
                                        <select class="form-select" required>
                                            <option value="">선택하세요</option>
                                            <option>개인 파트너</option>
                                            <option>기업 파트너</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">추천인 코드 *</label>
                                        <input type="text" class="form-control" placeholder="추천인 코드를 입력하세요" required>
                                        <div class="form-text">
                                            <i class="bi bi-info-circle me-1"></i>
                                            파트너 가입을 위해서는 추천회원 코드가 필수입니다.
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-warning">
                                    <h6><i class="bi bi-exclamation-triangle me-2"></i>추천회원 안내</h6>
                                    <p class="mb-2">
                                        파트너 가입을 위해서는 <strong>추천회원 코드</strong>가 필요합니다.
                                        이는 신뢰할 수 있는 회원을 필터링하기 위한 최소한의 제한 조치입니다.
                                    </p>
                                    <p class="mb-0">
                                        <i class="bi bi-lightbulb me-1"></i>
                                        <strong>추천회원이 없는 경우</strong>, 관리자에게 요청하시면 신뢰하는 <strong>매니저</strong>를 안내해 드립니다.
                                    </p>
                                </div>

                                <h4 class="mb-4">경력 및 경험</h4>
                                <div class="mb-4">
                                    <label class="form-label">관련 경력</label>
                                    <textarea class="form-control" rows="4" placeholder="관련 업무 경력이나 경험을 자세히 작성해주세요"></textarea>
                                </div>

                                <h4 class="mb-4">지원 동기</h4>
                                <div class="mb-4">
                                    <label class="form-label">지원 동기 *</label>
                                    <textarea class="form-control" rows="5" placeholder="파트너 지원 동기와 목표를 작성해주세요" required></textarea>
                                </div>

                                <h4 class="mb-4">활동 계획</h4>
                                <div class="mb-4">
                                    <label class="form-label">활동 계획 *</label>
                                    <textarea class="form-control" rows="5" placeholder="파트너로서의 활동 계획과 전략을 작성해주세요" required></textarea>
                                </div>

                                <h4 class="mb-4">가입비용 및 조건</h4>
                                <div class="alert alert-info mb-4">
                                    <h6><i class="bi bi-cash-coin me-2"></i>파트너십 투자</h6>
                                    <p class="mb-2">
                                        파트너는 서로 상생을 위한 비즈니스 동반자입니다. 함께 성장하기 위한 동반자로서
                                        서로 의지하면서 함께 나아가야 합니다.
                                    </p>
                                    <p class="mb-2">
                                        일부 파트너들이 성실의무를 소홀히 하는 경우를 방지하기 위하여
                                        소정의 <strong>가입비용과 월 비용</strong>을 부과합니다.
                                    </p>
                                    <p class="mb-0">
                                        <i class="bi bi-star me-1"></i>
                                        또한 파트너 유형과 등급에 따라서 보다 많은 이익을 분배하는 조건이 됩니다.
                                    </p>
                                </div>

                                <div class="mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="cost-agree" required>
                                        <label class="form-check-label" for="cost-agree">
                                            파트너 가입비용 및 월 비용 조건을 이해하고 동의합니다. *
                                        </label>
                                    </div>
                                </div>

                                <div class="form-check mb-4">
                                    <input class="form-check-input" type="checkbox" id="agree" required>
                                    <label class="form-check-label" for="agree">
                                        개인정보 수집 및 이용에 동의합니다. *
                                    </label>
                                </div>

                                <div class="form-check mb-4">
                                    <input class="form-check-input" type="checkbox" id="terms" required>
                                    <label class="form-check-label" for="terms">
                                        파트너 약관에 동의합니다. *
                                    </label>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-send me-2"></i>지원서 제출
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="alert alert-info mt-4">
                        <h6><i class="bi bi-info-circle me-2"></i>지원 프로세스 안내</h6>
                        <ol class="mb-3">
                            <li><strong>온라인 지원서 제출</strong> - 추천인 코드 포함하여 지원서 작성</li>
                            <li><strong>서류 검토 (3-5일)</strong> - 추천인 확인 및 기본 자격 심사</li>
                            <li><strong>매니저 승인</strong> - 새로운 파트너 신청 시 매니저를 통한 승인 필요</li>
                            <li><strong>면접 진행 (화상/대면)</strong> - 별도의 상담 또는 면접 과정 진행</li>
                            <li><strong>최종 승인 및 파트너 등록</strong> - 모든 과정 완료 후 파트너 등록</li>
                        </ol>
                        <div class="bg-warning bg-opacity-10 p-3 rounded">
                            <small class="text-muted">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <strong>참고:</strong> 승인 과정에서 별도의 상담 또는 면접 과정이 있을 수 있으며,
                                이는 파트너십의 질을 보장하고 상호 신뢰를 구축하기 위함입니다.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
