@extends($layout ?? 'jiny-auth::layouts.home')

@section('title', $pageTitle ?? '파트너 신청서 작성')

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
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
    border: 3px solid #e9ecef;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-weight: bold;
    color: #6c757d;
    position: relative;
    z-index: 2;
}
.step-circle.active {
    border-color: #667eea;
    color: #667eea;
    background: #667eea;
    color: white;
}
.form-section {
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    margin-bottom: 1rem;
}

/* Error state styling */
.field-error .form-label {
    color: #dc3545 !important;
}
.field-error .form-label .text-danger {
    color: #dc3545 !important;
}
.field-error .form-control,
.field-error .form-select {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}
.field-error .form-check-input {
    border-color: #dc3545;
}
.field-error .form-check-input:focus {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

/* Success state styling */
.field-success .form-label {
    color: #198754 !important;
}
.field-success .form-control,
.field-success .form-select {
    border-color: #198754;
    box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
}

/* Enhanced form section error state */
.form-section.has-errors {
    border-left: 4px solid #dc3545;
    background: #fff5f5;
}
.form-section.has-errors .card-header {
    background: #fee;
    border-bottom: 1px solid #fcc;
}
</style>
@endsection

@section('content')
<div class="container-fluid py-4">

    <!-- 헤더 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-2">{{ $pageTitle ?? '파트너 신청서 작성' }}</h1>
                    <p class="text-muted mb-0">간단한 3단계 과정을 통해 파트너가 되어보세요</p>
                </div>
                <div>
                    <span class="text-muted small">
                        <i class="bi bi-person-circle me-1"></i>{{ $currentUser->name ?? '사용자' }}님
                    </span>
                    <a href="{{ route('home.partner.regist.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>돌아가기
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">

            <!-- Process Steps -->
            <section class="mb-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="row text-center">
                            <div class="col-lg-4 process-step">
                                <div class="step-circle active">1</div>
                                <h6 class="fw-bold mb-2 text-primary">신청서 작성</h6>
                                <p class="text-muted small mb-0">개인정보, 경력사항, 전문분야를 포함한 신청서를 작성하여 제출합니다.</p>
                            </div>
                            <div class="col-lg-4 process-step">
                                <div class="step-circle">2</div>
                                <h6 class="fw-bold mb-2">검토 및 면접</h6>
                                <p class="text-muted small mb-0">제출된 신청서를 바탕으로 전문성을 검토하고 필요시 면접을 진행합니다.</p>
                            </div>
                            <div class="col-lg-4 process-step">
                                <div class="step-circle">3</div>
                                <h6 class="fw-bold mb-2">파트너 승인</h6>
                                <p class="text-muted small mb-0">승인 완료 후 파트너 대시보드에서 프로젝트 참여를 시작할 수 있습니다.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Application Form -->
            <form action="{{ route('home.partner.regist.store') }}" method="POST" enctype="multipart/form-data" id="create">
                @csrf
                <!-- 현재 로그인 사용자 정보 -->
                <input type="hidden" name="user_uuid" value="{{ $userInfo['uuid'] }}">
                <input type="hidden" name="current_user_id" value="{{ $currentUser->id }}">

                <!-- 오류 메시지 표시 -->
                @if($errors->any())
                    <div class="alert alert-danger d-flex align-items-start mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-3 mt-1 flex-shrink-0"></i>
                        <div class="flex-grow-1">
                            <h6 class="alert-heading fw-bold mb-2">입력 오류가 있습니다</h6>
                            <ul class="mb-0 small">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <!-- 세션 메시지 표시 -->
                @if(session('success'))
                    <div class="alert alert-success d-flex align-items-start mb-4" role="alert">
                        <i class="bi bi-check-circle-fill me-3 mt-1 flex-shrink-0"></i>
                        <div class="flex-grow-1">
                            <strong>성공!</strong> {{ session('success') }}
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger d-flex align-items-start mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-3 mt-1 flex-shrink-0"></i>
                        <div class="flex-grow-1">
                            <strong>오류!</strong> {{ session('error') }}
                        </div>
                    </div>
                @endif

                @if(session('info'))
                    <div class="alert alert-info d-flex align-items-start mb-4" role="alert">
                        <i class="bi bi-info-circle-fill me-3 mt-1 flex-shrink-0"></i>
                        <div class="flex-grow-1">
                            <strong>알림!</strong> {{ session('info') }}
                        </div>
                    </div>
                @endif

                <!-- Draft Application Notice -->
                @if($draftApplication)
                    <div class="alert alert-info d-flex align-items-start mb-4" role="alert">
                        <i class="bi bi-info-circle-fill me-3 mt-1 flex-shrink-0"></i>
                        <div class="flex-grow-1">
                            <h6 class="alert-heading fw-bold mb-2">임시 저장된 신청서가 있습니다</h6>
                            <p class="mb-2 small">
                                {{ $draftApplication->updated_at->format('Y년 m월 d일 H:i') }}에 저장된 신청서가 있습니다.
                                계속 작성하시거나 새로 작성하실 수 있습니다.
                            </p>
                            <div class="mt-2">
                                <a href="{{ route('home.partner.regist.edit', $draftApplication->id) }}"
                                    class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil me-1"></i>이어서 작성하기
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Personal Information -->
                <div class="card border-0 shadow-sm form-section mb-3">
                    <div class="card-header border-bottom">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-person text-primary fs-5 me-2"></i>
                            <div>
                                <h5 class="card-title mb-0 fw-bold">개인 정보</h5>
                                <small class="text-muted">현재 로그인 계정({{ $currentUser->email }})을 기준으로 파트너 신청서를 작성합니다.</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6 {{ $errors->has('name') ? 'field-error' : '' }}">
                                <label for="name" class="form-label fw-semibold">이름 <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" value="{{ old('name', $userInfo['name']) }}" required
                                        class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}">
                                @if($errors->has('name'))
                                    <div class="invalid-feedback">{{ $errors->first('name') }}</div>
                                @else
                                    <div class="invalid-feedback" id="name-error" style="display: none;"></div>
                                @endif
                                <div class="form-text">
                                    <i class="bi bi-info-circle me-1"></i>{{ $userInfo['name'] ? '로그인 계정의 이름이 입력되었습니다.' : '이름을 입력해주세요.' }}
                                </div>
                            </div>
                            <div class="col-md-6 {{ $errors->has('email') ? 'field-error' : '' }}">
                                <label for="email" class="form-label fw-semibold">이메일 <span class="text-danger">*</span></label>
                                <input type="email" name="email" id="email" value="{{ old('email', $userInfo['email']) }}" required readonly
                                        class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" style="background-color: #f8f9fa;">
                                @if($errors->has('email'))
                                    <div class="invalid-feedback">{{ $errors->first('email') }}</div>
                                @else
                                    <div class="invalid-feedback" id="email-error" style="display: none;"></div>
                                @endif
                                <div class="form-text">
                                    <i class="bi bi-info-circle me-1"></i>로그인 계정의 이메일이 자동으로 입력됩니다.
                                </div>
                            </div>
                            <div class="col-md-6 {{ $errors->has('phone') ? 'field-error' : '' }}">
                                <label for="phone" class="form-label fw-semibold">전화번호 <span class="text-danger">*</span></label>
                                <input type="tel" name="phone" id="phone" value="{{ old('phone', $userInfo['phone']) }}"
                                        placeholder="010-1234-5678" required
                                        class="form-control {{ $errors->has('phone') ? 'is-invalid' : '' }}">
                                @if($errors->has('phone'))
                                    <div class="invalid-feedback">{{ $errors->first('phone') }}</div>
                                @else
                                    <div class="invalid-feedback" id="phone-error" style="display: none;"></div>
                                @endif
                                <div class="form-text">
                                    <i class="bi bi-info-circle me-1"></i>연락 가능한 휴대폰 번호를 입력해주세요 (필수)
                                </div>
                            </div>
                            <div class="col-md-6 {{ $errors->has('birth_year') ? 'field-error' : '' }}">
                                <label for="birth_year" class="form-label fw-semibold">출생년도 <span class="text-danger">*</span></label>
                                <select name="birth_year" id="birth_year" required class="form-select {{ $errors->has('birth_year') ? 'is-invalid' : '' }}">
                                    <option value="">출생년도를 선택하세요</option>
                                    @for($year = date('Y') - 18; $year >= 1950; $year--)
                                        <option value="{{ $year }}" {{ old('birth_year') == $year ? 'selected' : '' }}>
                                            {{ $year }}년
                                        </option>
                                    @endfor
                                </select>
                                @if($errors->has('birth_year'))
                                    <div class="invalid-feedback">{{ $errors->first('birth_year') }}</div>
                                @else
                                    <div class="invalid-feedback" id="birth_year-error" style="display: none;"></div>
                                @endif
                            </div>
                        </div>

                        <!-- Address -->
                        <hr class="my-4">
                        <h6 class="fw-semibold mb-3">
                            <i class="bi bi-geo-alt text-primary me-2"></i>주소 정보
                        </h6>
                        <div class="row g-3">
                            <div class="col-12 {{ $errors->has('address') ? 'field-error' : '' }}">
                                <label for="address" class="form-label fw-semibold">주소 <span class="text-danger">*</span></label>
                                <input type="text" name="address" id="address" value="{{ old('address') }}" required
                                       placeholder="전체 주소를 입력하세요"
                                       class="form-control {{ $errors->has('address') ? 'is-invalid' : '' }}">
                                @if($errors->has('address'))
                                    <div class="invalid-feedback">{{ $errors->first('address') }}</div>
                                @else
                                    <div class="invalid-feedback" id="address-error" style="display: none;"></div>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Referral Information -->
                <div class="card border-0 shadow-sm form-section mb-3">
                    <div class="card-header border-bottom">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-person-plus text-primary fs-5 me-2"></i>
                            <div>
                                <h5 class="card-title mb-0 fw-bold">추천 파트너 정보</h5>
                                <small class="text-muted">파트너를 추천해주신 분의 정보를 입력해주세요 (선택사항)</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6 {{ $errors->has('referral_code') ? 'field-error' : '' }}">
                                <label for="referral_code" class="form-label fw-semibold">추천 코드</label>
                                <input type="text" name="referral_code" id="referral_code" value="{{ old('referral_code', request('ref')) }}"
                                       placeholder="추천받은 코드를 입력하세요"
                                       class="form-control {{ $errors->has('referral_code') ? 'is-invalid' : '' }}">
                                @if($errors->has('referral_code'))
                                    <div class="invalid-feedback">{{ $errors->first('referral_code') }}</div>
                                @else
                                    <div class="invalid-feedback" id="referral_code-error" style="display: none;"></div>
                                @endif
                                <div class="form-text">
                                    <i class="bi bi-info-circle me-1"></i>추천 파트너로부터 받은 고유 코드를 입력하세요
                                </div>
                            </div>
                            <div class="col-md-6 {{ $errors->has('referral_source') ? 'field-error' : '' }}">
                                <label for="referral_source" class="form-label fw-semibold">추천 경로</label>
                                <select name="referral_source" id="referral_source" class="form-select {{ $errors->has('referral_source') ? 'is-invalid' : '' }}">
                                    <option value="self_application" {{ old('referral_source') == 'self_application' ? 'selected' : '' }}>자율 지원</option>
                                    <option value="direct" {{ old('referral_source') == 'direct' ? 'selected' : '' }}>직접 추천</option>
                                    <option value="online_link" {{ old('referral_source') == 'online_link' ? 'selected' : '' }}>온라인 링크</option>
                                    <option value="offline_meeting" {{ old('referral_source') == 'offline_meeting' ? 'selected' : '' }}>오프라인 미팅</option>
                                    <option value="social_media" {{ old('referral_source') == 'social_media' ? 'selected' : '' }}>소셜미디어</option>
                                    <option value="event" {{ old('referral_source') == 'event' ? 'selected' : '' }}>이벤트/세미나</option>
                                    <option value="advertisement" {{ old('referral_source') == 'advertisement' ? 'selected' : '' }}>광고</option>
                                    <option value="word_of_mouth" {{ old('referral_source') == 'word_of_mouth' ? 'selected' : '' }}>구전</option>
                                    <option value="other" {{ old('referral_source') == 'other' ? 'selected' : '' }}>기타</option>
                                </select>
                                @if($errors->has('referral_source'))
                                    <div class="invalid-feedback">{{ $errors->first('referral_source') }}</div>
                                @else
                                    <div class="invalid-feedback" id="referral_source-error" style="display: none;"></div>
                                @endif
                            </div>
                        </div>

                        <div id="referralDetailsSection" class="mt-4" style="display: none;">
                            <hr class="my-4">
                            <h6 class="fw-semibold mb-3">
                                <i class="bi bi-info-circle text-primary me-2"></i>추천 상세 정보
                            </h6>

                            <div class="row g-3">
                                <div class="col-md-6 {{ $errors->has('referrer_name') ? 'field-error' : '' }}">
                                    <label for="referrer_name" class="form-label fw-semibold">추천인 이름</label>
                                    <input type="text" name="referrer_name" id="referrer_name" value="{{ old('referrer_name') }}"
                                           placeholder="추천해주신 분의 이름"
                                           class="form-control {{ $errors->has('referrer_name') ? 'is-invalid' : '' }}">
                                    @if($errors->has('referrer_name'))
                                        <div class="invalid-feedback">{{ $errors->first('referrer_name') }}</div>
                                    @else
                                        <div class="invalid-feedback" id="referrer_name-error" style="display: none;"></div>
                                    @endif
                                </div>
                                <div class="col-md-6 {{ $errors->has('referrer_contact') ? 'field-error' : '' }}">
                                    <label for="referrer_contact" class="form-label fw-semibold">추천인 연락처</label>
                                    <input type="text" name="referrer_contact" id="referrer_contact" value="{{ old('referrer_contact') }}"
                                           placeholder="추천해주신 분의 연락처"
                                           class="form-control {{ $errors->has('referrer_contact') ? 'is-invalid' : '' }}">
                                    @if($errors->has('referrer_contact'))
                                        <div class="invalid-feedback">{{ $errors->first('referrer_contact') }}</div>
                                    @else
                                        <div class="invalid-feedback" id="referrer_contact-error" style="display: none;"></div>
                                    @endif
                                </div>
                            </div>

                            <div class="row g-3 mt-3">
                                <div class="col-md-6 {{ $errors->has('referrer_relationship') ? 'field-error' : '' }}">
                                    <label for="referrer_relationship" class="form-label fw-semibold">관계</label>
                                    <input type="text" name="referrer_relationship" id="referrer_relationship" value="{{ old('referrer_relationship') }}"
                                           placeholder="추천인과의 관계 (예: 회사 동료, 친구)"
                                           class="form-control {{ $errors->has('referrer_relationship') ? 'is-invalid' : '' }}">
                                    @if($errors->has('referrer_relationship'))
                                        <div class="invalid-feedback">{{ $errors->first('referrer_relationship') }}</div>
                                    @else
                                        <div class="invalid-feedback" id="referrer_relationship-error" style="display: none;"></div>
                                    @endif
                                </div>
                                <div class="col-md-6 {{ $errors->has('meeting_date') ? 'field-error' : '' }}">
                                    <label for="meeting_date" class="form-label fw-semibold">만남/추천일</label>
                                    <input type="date" name="meeting_date" id="meeting_date" value="{{ old('meeting_date') }}"
                                           class="form-control {{ $errors->has('meeting_date') ? 'is-invalid' : '' }}">
                                    @if($errors->has('meeting_date'))
                                        <div class="invalid-feedback">{{ $errors->first('meeting_date') }}</div>
                                    @else
                                        <div class="invalid-feedback" id="meeting_date-error" style="display: none;"></div>
                                    @endif
                                </div>
                            </div>

                            <div class="row g-3 mt-3">
                                <div class="col-md-6 {{ $errors->has('meeting_location') ? 'field-error' : '' }}">
                                    <label for="meeting_location" class="form-label fw-semibold">만남 장소</label>
                                    <input type="text" name="meeting_location" id="meeting_location" value="{{ old('meeting_location') }}"
                                           placeholder="추천받은 장소 (예: 서울 강남구 카페)"
                                           class="form-control {{ $errors->has('meeting_location') ? 'is-invalid' : '' }}">
                                    @if($errors->has('meeting_location'))
                                        <div class="invalid-feedback">{{ $errors->first('meeting_location') }}</div>
                                    @else
                                        <div class="invalid-feedback" id="meeting_location-error" style="display: none;"></div>
                                    @endif
                                </div>
                                <div class="col-md-6 {{ $errors->has('introduction_method') ? 'field-error' : '' }}">
                                    <label for="introduction_method" class="form-label fw-semibold">소개 방법</label>
                                    <input type="text" name="introduction_method" id="introduction_method" value="{{ old('introduction_method') }}"
                                           placeholder="어떻게 소개받았는지 (예: 지인 소개, SNS)"
                                           class="form-control {{ $errors->has('introduction_method') ? 'is-invalid' : '' }}">
                                    @if($errors->has('introduction_method'))
                                        <div class="invalid-feedback">{{ $errors->first('introduction_method') }}</div>
                                    @else
                                        <div class="invalid-feedback" id="introduction_method-error" style="display: none;"></div>
                                    @endif
                                </div>
                            </div>

                            <div class="row g-3 mt-3">
                                <div class="col-12 {{ $errors->has('motivation') ? 'field-error' : '' }}">
                                    <label for="motivation" class="form-label fw-semibold">지원 동기</label>
                                    <textarea name="motivation" id="motivation" rows="3"
                                              placeholder="파트너 사업에 관심을 갖게 된 동기를 작성해주세요"
                                              class="form-control {{ $errors->has('motivation') ? 'is-invalid' : '' }}">{{ old('motivation') }}</textarea>
                                    @if($errors->has('motivation'))
                                        <div class="invalid-feedback">{{ $errors->first('motivation') }}</div>
                                    @else
                                        <div class="invalid-feedback" id="motivation-error" style="display: none;"></div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Professional Experience -->
                <div class="card border-0 shadow-sm form-section mb-3">
                    <div class="card-header border-bottom">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-briefcase text-primary fs-5 me-2"></i>
                            <div>
                                <h5 class="card-title mb-0 fw-bold">전문 경력</h5>
                                <small class="text-muted">전문 기술과 경력 사항을 입력해주세요.</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6 {{ $errors->has('total_years') ? 'field-error' : '' }}">
                                <label for="total_years" class="form-label fw-semibold">경력 (년)</label>
                                <select name="total_years" id="total_years" class="form-select {{ $errors->has('total_years') ? 'is-invalid' : '' }}">
                                    <option value="">경력을 선택하세요</option>
                                    @for($years = 0; $years <= 50; $years++)
                                        <option value="{{ $years }}" {{ old('total_years') == $years ? 'selected' : '' }}>
                                            {{ $years == 0 ? '신입 (1년 미만)' : $years . '년' }}
                                        </option>
                                    @endfor
                                </select>
                                @if($errors->has('total_years'))
                                    <div class="invalid-feedback">{{ $errors->first('total_years') }}</div>
                                @else
                                    <div class="invalid-feedback" id="total_years-error" style="display: none;"></div>
                                @endif
                            </div>
                            <div class="col-md-6 {{ $errors->has('portfolio_url') ? 'field-error' : '' }}">
                                <label for="portfolio_url" class="form-label fw-semibold">포트폴리오 URL</label>
                                <input type="url" name="portfolio_url" id="portfolio_url" value="{{ old('portfolio_url') }}"
                                       placeholder="https://github.com/username"
                                       class="form-control {{ $errors->has('portfolio_url') ? 'is-invalid' : '' }}">
                                @if($errors->has('portfolio_url'))
                                    <div class="invalid-feedback">{{ $errors->first('portfolio_url') }}</div>
                                @else
                                    <div class="invalid-feedback" id="portfolio_url-error" style="display: none;"></div>
                                @endif
                            </div>
                        </div>

                        <div class="row g-3 mt-3">
                            <div class="col-12 {{ $errors->has('career_summary') ? 'field-error' : '' }}">
                                <label for="career_summary" class="form-label fw-semibold">경력 요약</label>
                                <textarea name="career_summary" id="career_summary" rows="4"
                                          placeholder="주요 경력과 업무 경험을 요약하여 작성해주세요"
                                          class="form-control {{ $errors->has('career_summary') ? 'is-invalid' : '' }}">{{ old('career_summary') }}</textarea>
                                @if($errors->has('career_summary'))
                                    <div class="invalid-feedback">{{ $errors->first('career_summary') }}</div>
                                @else
                                    <div class="invalid-feedback" id="career_summary-error" style="display: none;"></div>
                                @endif
                            </div>
                            <div class="col-12 {{ $errors->has('bio') ? 'field-error' : '' }}">
                                <label for="bio" class="form-label fw-semibold">자기소개</label>
                                <textarea name="bio" id="bio" rows="3"
                                          placeholder="간단한 자기소개를 작성해주세요"
                                          class="form-control {{ $errors->has('bio') ? 'is-invalid' : '' }}">{{ old('bio') }}</textarea>
                                @if($errors->has('bio'))
                                    <div class="invalid-feedback">{{ $errors->first('bio') }}</div>
                                @else
                                    <div class="invalid-feedback" id="bio-error" style="display: none;"></div>
                                @endif
                            </div>
                        </div>

                        <!-- Skills -->
                        <hr class="my-4">
                        <h6 class="fw-semibold mb-3">
                            <i class="bi bi-code-slash text-primary me-2"></i>기술 스택 및 전문 분야
                        </h6>

                        <!-- Professional Skills -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold mb-3">전문 기술</label>
                            <div id="skillsContainer">
                                <div class="skill-item mb-3">
                                    <div class="row g-2">
                                        <div class="col-md-8">
                                            <input type="text" name="skills[]" placeholder="기술명 (예: PHP, Laravel, JavaScript)"
                                                   value="{{ old('skills.0') }}"
                                                   class="form-control">
                                        </div>
                                        <div class="col-md-4">
                                            <select name="skill_levels[]" class="form-select">
                                                <option value="기초" {{ old('skill_levels.0') == '기초' ? 'selected' : '' }}>기초</option>
                                                <option value="중급" {{ old('skill_levels.0') == '중급' ? 'selected' : '' }}>중급</option>
                                                <option value="고급" {{ old('skill_levels.0') == '고급' ? 'selected' : '' }}>고급</option>
                                                <option value="전문가" {{ old('skill_levels.0') == '전문가' ? 'selected' : '' }}>전문가</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                @for($i = 1; $i < 5; $i++)
                                    <div class="skill-item mb-3">
                                        <div class="row g-2">
                                            <div class="col-md-8">
                                                <input type="text" name="skills[]" placeholder="기술명 (선택)"
                                                       value="{{ old('skills.' . $i) }}"
                                                       class="form-control">
                                            </div>
                                            <div class="col-md-4">
                                                <select name="skill_levels[]" class="form-select">
                                                    <option value="기초" {{ old('skill_levels.' . $i) == '기초' ? 'selected' : '' }}>기초</option>
                                                    <option value="중급" {{ old('skill_levels.' . $i) == '중급' ? 'selected' : '' }}>중급</option>
                                                    <option value="고급" {{ old('skill_levels.' . $i) == '고급' ? 'selected' : '' }}>고급</option>
                                                    <option value="전문가" {{ old('skill_levels.' . $i) == '전문가' ? 'selected' : '' }}>전문가</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                @endfor
                            </div>
                        </div>

                        <!-- Certifications -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold mb-3">보유 자격증</label>
                            <div class="row g-2">
                                @for($i = 0; $i < 3; $i++)
                                    <div class="col-md-4">
                                        <input type="text" name="certifications[]" placeholder="자격증명 (선택)"
                                               value="{{ old('certifications.' . $i) }}"
                                               class="form-control">
                                    </div>
                                @endfor
                            </div>
                        </div>

                        <!-- Languages -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold mb-3">사용 가능한 언어</label>
                            <div class="row g-2">
                                @for($i = 0; $i < 3; $i++)
                                    <div class="col-md-4">
                                        <input type="text" name="languages[]" placeholder="언어명 (예: 한국어, 영어)"
                                               value="{{ old('languages.' . $i) }}"
                                               class="form-control">
                                    </div>
                                @endfor
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Work Conditions -->
                <div class="card border-0 shadow-sm form-section mb-3">
                    <div class="card-header border-bottom">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-currency-dollar text-primary fs-5 me-2"></i>
                            <div>
                                <h5 class="card-title mb-0 fw-bold">근무 조건</h5>
                                <small class="text-muted">희망하는 근무 조건을 설정하세요.</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6 {{ $errors->has('expected_hourly_rate') ? 'field-error' : '' }}">
                                <label for="expected_hourly_rate" class="form-label fw-semibold">희망 시급 (원)</label>
                                <input type="number" name="expected_hourly_rate" id="expected_hourly_rate"
                                       value="{{ old('expected_hourly_rate') }}"
                                       min="10000" max="100000" step="1000"
                                       class="form-control {{ $errors->has('expected_hourly_rate') ? 'is-invalid' : '' }}">
                                @if($errors->has('expected_hourly_rate'))
                                    <div class="invalid-feedback">{{ $errors->first('expected_hourly_rate') }}</div>
                                @else
                                    <div class="invalid-feedback" id="expected_hourly_rate-error" style="display: none;"></div>
                                @endif
                                <div class="form-text">최소 10,000원, 최대 100,000원</div>
                            </div>
                            <div class="col-md-6 {{ $errors->has('max_distance_km') ? 'field-error' : '' }}">
                                <label for="max_distance_km" class="form-label fw-semibold">최대 이동거리 (km)</label>
                                <input type="number" name="max_distance_km" id="max_distance_km"
                                       value="{{ old('max_distance_km') }}"
                                       min="1" max="200"
                                       class="form-control {{ $errors->has('max_distance_km') ? 'is-invalid' : '' }}">
                                @if($errors->has('max_distance_km'))
                                    <div class="invalid-feedback">{{ $errors->first('max_distance_km') }}</div>
                                @else
                                    <div class="invalid-feedback" id="max_distance_km-error" style="display: none;"></div>
                                @endif
                            </div>
                        </div>

                        <!-- Preferred Regions -->
                        <hr class="my-4">
                        <h6 class="fw-semibold mb-3">
                            <i class="bi bi-geo text-primary me-2"></i>선호 근무 지역
                        </h6>
                        <div class="row g-2">
                            @php
                                $selectedRegions = old('preferred_regions', []);
                                $regions = ['서울', '경기', '인천', '부산', '대구', '광주', '대전', '울산'];
                            @endphp
                            @foreach($regions as $region)
                                <div class="col-md-3 col-6">
                                    <div class="form-check">
                                        <input type="checkbox" name="preferred_regions[]" value="{{ $region }}"
                                               {{ in_array($region, $selectedRegions) ? 'checked' : '' }}
                                               class="form-check-input" id="region_{{ $loop->index }}">
                                        <label class="form-check-label" for="region_{{ $loop->index }}">{{ $region }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Availability Schedule -->
                        <hr class="my-4">
                        <h6 class="fw-semibold mb-3">
                            <i class="bi bi-calendar-week text-primary me-2"></i>근무 가능 시간
                        </h6>

                        @php
                            $weekdays = ['monday' => '월요일', 'tuesday' => '화요일', 'wednesday' => '수요일', 'thursday' => '목요일', 'friday' => '금요일'];
                        @endphp

                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <label class="form-label fw-semibold">평일 근무 가능 시간</label>
                            </div>
                            @foreach($weekdays as $day => $dayKorean)
                                <div class="col-12">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-md-2">
                                            <div class="form-check">
                                                <input type="checkbox" name="{{ $day }}_available" value="1"
                                                       {{ old($day.'_available') ? 'checked' : '' }}
                                                       class="form-check-input" id="{{ $day }}_available">
                                                <label class="form-check-label" for="{{ $day }}_available">{{ $dayKorean }}</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="time" name="{{ $day }}_start" value="{{ old($day.'_start', '09:00') }}"
                                                   class="form-control">
                                        </div>
                                        <div class="col-md-1 text-center">~</div>
                                        <div class="col-md-3">
                                            <input type="time" name="{{ $day }}_end" value="{{ old($day.'_end', '18:00') }}"
                                                   class="form-control">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input type="checkbox" name="saturday_available" value="1"
                                           {{ old('saturday_available') ? 'checked' : '' }}
                                           class="form-check-input" id="saturday_available">
                                    <label class="form-check-label" for="saturday_available">토요일 근무 가능</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input type="checkbox" name="sunday_available" value="1"
                                           {{ old('sunday_available') ? 'checked' : '' }}
                                           class="form-check-input" id="sunday_available">
                                    <label class="form-check-label" for="sunday_available">일요일 근무 가능</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input type="checkbox" name="holiday_work" value="1"
                                           {{ old('holiday_work') ? 'checked' : '' }}
                                           class="form-check-input" id="holiday_work">
                                    <label class="form-check-label" for="holiday_work">공휴일 근무 가능</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- File Attachments -->
                <div class="card border-0 shadow-sm form-section mb-3">
                    <div class="card-header border-bottom">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-paperclip text-primary fs-5 me-2"></i>
                            <div>
                                <h5 class="card-title mb-0 fw-bold">첨부 서류</h5>
                                <small class="text-muted">이력서와 포트폴리오 등 관련 서류를 첨부하세요.</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <!-- Resume -->
                        <div class="mb-4 {{ $errors->has('resume') ? 'field-error' : '' }}">
                            <label for="resume" class="form-label fw-semibold">이력서</label>
                            <input type="file" name="resume" id="resume" accept=".pdf,.doc,.docx"
                                   class="form-control {{ $errors->has('resume') ? 'is-invalid' : '' }}">
                            @if($errors->has('resume'))
                                <div class="invalid-feedback">{{ $errors->first('resume') }}</div>
                            @else
                                <div class="invalid-feedback" id="resume-error" style="display: none;"></div>
                            @endif
                            <div class="form-text">PDF, DOC, DOCX 파일만 업로드 가능 (최대 5MB)</div>
                        </div>

                        <!-- Portfolio -->
                        <div class="mb-4 {{ $errors->has('portfolio') ? 'field-error' : '' }}">
                            <label for="portfolio" class="form-label fw-semibold">포트폴리오</label>
                            <input type="file" name="portfolio" id="portfolio" accept=".pdf,.doc,.docx,.zip"
                                   class="form-control {{ $errors->has('portfolio') ? 'is-invalid' : '' }}">
                            @if($errors->has('portfolio'))
                                <div class="invalid-feedback">{{ $errors->first('portfolio') }}</div>
                            @else
                                <div class="invalid-feedback" id="portfolio-error" style="display: none;"></div>
                            @endif
                            <div class="form-text">PDF, DOC, DOCX, ZIP 파일만 업로드 가능 (최대 10MB)</div>
                        </div>

                        <!-- Other Documents -->
                        <div class="mb-3 {{ $errors->has('other_documents') ? 'field-error' : '' }}">
                            <label for="other_documents" class="form-label fw-semibold">기타 서류</label>
                            <input type="file" name="other_documents[]" id="other_documents" multiple
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                   class="form-control {{ $errors->has('other_documents') ? 'is-invalid' : '' }}">
                            @if($errors->has('other_documents'))
                                <div class="invalid-feedback">{{ $errors->first('other_documents') }}</div>
                            @else
                                <div class="invalid-feedback" id="other_documents-error" style="display: none;"></div>
                            @endif
                            <div class="form-text">PDF, DOC, DOCX, JPG, PNG 파일만 업로드 가능 (최대 5MB)</div>
                        </div>
                    </div>
                </div>

                <!-- Agreement -->
                <div class="card border-0 shadow-sm form-section mb-3">
                    <div class="card-body p-4 {{ $errors->has('terms_agreed') ? 'field-error' : '' }}">
                        <div class="form-check d-flex align-items-start">
                            <input type="checkbox" name="terms_agreed" id="terms_agreed" required
                                    class="form-check-input mt-1 me-3 {{ $errors->has('terms_agreed') ? 'is-invalid' : '' }}">
                            <div class="form-check-label">
                                <label for="terms_agreed" class="fw-semibold">
                                    개인정보 수집 및 이용에 동의합니다. <span class="text-danger">*</span>
                                </label>
                                <p class="text-muted small mb-0 mt-1">
                                    파트너 신청 심사를 위해 제공된 개인정보가 수집 및 이용됩니다.
                                </p>
                                @if($errors->has('terms_agreed'))
                                    <div class="invalid-feedback d-block">{{ $errors->first('terms_agreed') }}</div>
                                @else
                                    <div class="invalid-feedback" id="terms_agreed-error" style="display: none;"></div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <a href="{{ route('home.partner.regist.index') }}"
                        class="btn btn-outline-secondary px-4 py-2">
                        <i class="bi bi-arrow-left me-2"></i>취소
                    </a>
                    <div class="d-flex gap-3">
                        <button type="submit" name="submit_type" value="draft"
                                class="btn btn-outline-primary px-4 py-2">
                            <i class="bi bi-save me-2"></i>임시 저장
                        </button>
                        <button type="submit" name="submit_type" value="submit"
                                class="btn btn-primary px-4 py-2">
                            <i class="bi bi-send me-2"></i>신청서 제출
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<style>
/* 로딩 오버레이 스타일 */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.loading-spinner {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    text-align: center;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.spinner-border {
    width: 3rem;
    height: 3rem;
}

/* 기존 유효성 검사 스타일 유지 */
.field-error .form-label {
    color: #dc3545 !important;
    font-weight: 600;
}

.field-error .form-control,
.field-error .form-select {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.field-error {
    border-left: 4px solid #dc3545;
    padding-left: 15px;
    margin-left: -15px;
    background-color: rgba(220, 53, 69, 0.03);
    border-radius: 4px;
}

.field-success .form-label {
    color: #198754 !important;
}

.field-success .form-control,
.field-success .form-select {
    border-color: #198754;
    box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
}

.field-success {
    border-left: 4px solid #198754;
    padding-left: 15px;
    margin-left: -15px;
    background-color: rgba(25, 135, 84, 0.03);
    border-radius: 4px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('create');

    // 폼 요소 확인
    if (!form) {
        console.error('Form element not found');
        return;
    }

    console.log('Form found:', form);
    console.log('Form action:', form.action);
    console.log('Form method:', form.method);

    // CSRF 토큰 가져오기
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                     document.querySelector('input[name="_token"]')?.value;

    console.log('CSRF token:', csrfToken ? 'Found' : 'Not found');

    // 추천 경로 선택 시 상세 정보 표시/숨김 처리
    const referralSourceSelect = document.getElementById('referral_source');
    const referralDetailsSection = document.getElementById('referralDetailsSection');

    function toggleReferralDetails() {
        const selectedSource = referralSourceSelect.value;
        if (selectedSource === 'self_application') {
            referralDetailsSection.style.display = 'none';
        } else {
            referralDetailsSection.style.display = 'block';
        }
    }

    // 초기 상태 설정
    toggleReferralDetails();

    // 선택 변경 시 상세 정보 토글
    referralSourceSelect.addEventListener('change', toggleReferralDetails);

    // 추천 코드 입력 시 검증 (선택 사항)
    const referralCodeInput = document.getElementById('referral_code');
    referralCodeInput.addEventListener('blur', function() {
        const code = this.value.trim();
        if (code.length > 0 && code.length < 3) {
            this.classList.add('is-invalid');
            const errorDiv = document.getElementById('referral_code-error');
            if (errorDiv) {
                errorDiv.textContent = '추천 코드는 3자 이상 입력해주세요.';
                errorDiv.style.display = 'block';
            }
        } else {
            this.classList.remove('is-invalid');
            const errorDiv = document.getElementById('referral_code-error');
            if (errorDiv) {
                errorDiv.style.display = 'none';
            }
        }
    });

    // AJAX 폼 제출 처리
    form.addEventListener('submit', function(e) {
        console.log('=== 폼 제출 이벤트 시작 ===');
        e.preventDefault(); // 기본 폼 제출 방지

        const submitButton = e.submitter;
        const submitType = submitButton ? submitButton.value : 'submit';

        console.log('Submit button:', submitButton);
        console.log('Submit button value:', submitButton ? submitButton.value : 'null');
        console.log('Submit type:', submitType);

        // 로딩 상태 표시
        showLoadingOverlay();

        // 제출 버튼들 비활성화
        disableSubmitButtons(submitButton);

        // FormData 생성
        const formData = new FormData(form);
        formData.set('submit_type', submitType);

        // AJAX 요청 - URL을 직접 설정 (안전성을 위해)
        const actionUrl = '{{ route("home.partner.regist.store") }}';
        console.log('=== AJAX 요청 시작 ===');
        console.log('Request URL:', actionUrl);
        console.log('Request Method: POST');
        console.log('CSRF Token:', csrfToken);
        console.log('Submit Type:', submitType);
        console.log('FormData keys:', Array.from(formData.keys()));

        fetch(actionUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(response => {
            console.log('=== HTTP 응답 수신 ===');
            console.log('Response status:', response.status);
            console.log('Response statusText:', response.statusText);
            console.log('Response headers:', Object.fromEntries(response.headers.entries()));

            if (!response.ok) {
                console.error('HTTP 응답 오류:', response.status, response.statusText);

                // 응답 본문을 텍스트로 먼저 읽어보기
                return response.text().then(text => {
                    console.log('Error response text:', text);

                    try {
                        const data = JSON.parse(text);
                        console.log('Error response JSON:', data);
                        return Promise.reject(data);
                    } catch (e) {
                        console.error('JSON 파싱 실패:', e);
                        return Promise.reject({
                            message: `서버 오류: ${response.status} ${response.statusText}`,
                            responseText: text
                        });
                    }
                });
            }

            // 성공 응답도 텍스트로 먼저 확인
            return response.text().then(text => {
                console.log('Success response text:', text);

                try {
                    const data = JSON.parse(text);
                    console.log('Success response JSON:', data);
                    return data;
                } catch (e) {
                    console.error('성공 응답 JSON 파싱 실패:', e);
                    throw new Error('서버가 올바른 JSON 응답을 반환하지 않았습니다: ' + text);
                }
            });
        })
        .then(data => {
            console.log('=== 성공 응답 처리 ===');
            console.log('Response data:', data);
            hideLoadingOverlay();

            if (data.success) {
                console.log('성공 처리 시작');
                showSuccessAlert(data.message || '성공적으로 저장되었습니다!');

                // 1.5초 후 목록 페이지로 이동
                setTimeout(() => {
                    console.log('페이지 이동:', '{{ route("home.partner.regist.index") }}');
                    window.location.href = '{{ route("home.partner.regist.index") }}';
                }, 1500);
            } else {
                console.log('서버에서 success: false 응답');
                showErrorAlert(data.message || '처리 중 오류가 발생했습니다.');
                enableSubmitButtons();
            }
        })
        .catch(error => {
            console.log('=== CATCH 블록 실행 ===');
            console.error('AJAX 전체 오류:', error);
            console.error('오류 타입:', typeof error);
            console.error('오류 객체:', error);

            if (error instanceof TypeError) {
                console.error('네트워크 오류 또는 CORS 문제:', error.message);
            }

            hideLoadingOverlay();

            if (error && error.errors) {
                console.log('유효성 검사 오류 처리');
                console.log('Validation errors:', error.errors);
                displayValidationErrors(error.errors);
            } else {
                console.log('일반 오류 처리');
                const errorMessage = error?.message || error?.responseText || '요청 처리 중 오류가 발생했습니다.';
                console.log('Error message:', errorMessage);
                showErrorAlert(errorMessage);
            }

            enableSubmitButtons();
        });
    });

    // 로딩 오버레이 표시
    function showLoadingOverlay() {
        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.id = 'loadingOverlay';
        overlay.innerHTML = `
            <div class="loading-spinner">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">처리 중...</span>
                </div>
                <p class="mt-3 mb-0">신청서를 저장하고 있습니다...</p>
            </div>
        `;
        document.body.appendChild(overlay);
    }

    // 로딩 오버레이 숨기기
    function hideLoadingOverlay() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.remove();
        }
    }

    // 제출 버튼 비활성화
    function disableSubmitButtons(activeButton) {
        const submitButtons = form.querySelectorAll('button[type="submit"]');
        submitButtons.forEach(btn => {
            btn.disabled = true;
            if (btn === activeButton) {
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="spinner-border spinner-border-sm me-2"></i>처리 중...';
                btn.setAttribute('data-original-text', originalText);
            }
        });
    }

    // 제출 버튼 활성화
    function enableSubmitButtons() {
        const submitButtons = form.querySelectorAll('button[type="submit"]');
        submitButtons.forEach(btn => {
            btn.disabled = false;
            const originalText = btn.getAttribute('data-original-text');
            if (originalText) {
                btn.innerHTML = originalText;
                btn.removeAttribute('data-original-text');
            }
        });
    }

    // 성공 알림 표시
    function showSuccessAlert(message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success alert-dismissible d-flex align-items-start mb-4 fade show';
        alertDiv.innerHTML = `
            <i class="bi bi-check-circle-fill me-3 mt-1 flex-shrink-0"></i>
            <div class="flex-grow-1">
                <strong>성공!</strong> ${message}
            </div>
        `;
        insertAlert(alertDiv);
    }

    // 오류 알림 표시
    function showErrorAlert(message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible d-flex align-items-start mb-4 fade show';
        alertDiv.innerHTML = `
            <i class="bi bi-exclamation-triangle-fill me-3 mt-1 flex-shrink-0"></i>
            <div class="flex-grow-1">
                <strong>오류!</strong> ${message}
            </div>
        `;
        insertAlert(alertDiv);
    }

    // 유효성 검사 오류 표시
    function displayValidationErrors(errors) {
        // 필드별 오류 표시
        Object.keys(errors).forEach(fieldName => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                field.classList.add('is-invalid');
                field.classList.remove('is-valid');

                const errorDiv = document.getElementById(fieldName + '-error');
                if (errorDiv) {
                    errorDiv.textContent = errors[fieldName][0];
                    errorDiv.style.display = 'block';
                }

                const fieldContainer = field.closest('.col-md-6, .mb-4, .card-body');
                if (fieldContainer) {
                    fieldContainer.classList.add('field-error');
                    fieldContainer.classList.remove('field-success');
                }
            }
        });

        // 전체 오류 목록 표시
        const errorList = Object.values(errors).flat();
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible d-flex align-items-start mb-4 fade show';
        alertDiv.innerHTML = `
            <i class="bi bi-exclamation-triangle-fill me-3 mt-1 flex-shrink-0"></i>
            <div class="flex-grow-1">
                <h6 class="alert-heading fw-bold mb-2">입력 오류가 있습니다</h6>
                <ul class="mb-0 small">
                    ${errorList.slice(0, 5).map(error => `<li>${error}</li>`).join('')}
                    ${errorList.length > 5 ? '<li>그 외 ' + (errorList.length - 5) + '개 항목</li>' : ''}
                </ul>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        insertAlert(alertDiv);

        // 첫 번째 오류 필드로 스크롤
        const firstErrorField = form.querySelector('.is-invalid');
        if (firstErrorField) {
            firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstErrorField.focus();
        }
    }

    // 알림 삽입 및 기존 알림 제거
    function insertAlert(alertDiv) {
        // 기존 알림 제거
        const existingAlerts = form.querySelectorAll('.alert');
        existingAlerts.forEach(alert => alert.remove());

        // 폼 상단에 알림 추가
        form.insertBefore(alertDiv, form.firstChild);

        // 상단으로 스크롤
        alertDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });

        // 5초 후 자동 제거
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }

    // 실시간 유효성 검사 피드백
    form.querySelectorAll('input, select, textarea').forEach(function(field) {
        field.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                this.classList.remove('is-invalid');

                const errorDiv = document.getElementById(this.name + '-error');
                if (errorDiv) {
                    errorDiv.style.display = 'none';
                }

                const fieldContainer = this.closest('.col-md-6, .mb-4, .card-body');
                if (fieldContainer) {
                    fieldContainer.classList.remove('field-error');
                }
            }
        });
    });

    // 파일 업로드 피드백
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            if (this.files.length > 0) {
                const fileList = Array.from(this.files).map(file => file.name).join(', ');
                console.log(`${this.name} files selected:`, fileList);
            }
        });
    });
});
</script>
@endsection
