@extends($layout ?? 'jiny-auth::layouts.home')

@section('title', $pageTitle ?? '파트너 재신청')

@section('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
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
            border-color: #0d6efd;
            color: white;
            background: #0d6efd;
        }
        .rejection-card {
            border-left: 4px solid #dc3545;
            background: #fff5f5;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <!-- 헤더 -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-0">{{ $pageTitle ?? '파트너 재신청' }}</h2>
                        <p class="text-muted mb-0">반려 사유를 반영하여 개선된 신청서를 제출하세요.</p>
                    </div>
                    <div>
                        <span class="text-muted small d-block mb-2">{{ $currentUser->name ?? '사용자' }}님</span>
                        <a href="{{ route('home.partner.regist.status', $rejectedApplication->id) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left me-1"></i>이전 신청 확인
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Previous Application Notice -->
        <div class="card mb-4 rejection-card">
            <div class="card-body">
                <div class="d-flex align-items-start">
                    <div class="bg-danger bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3"
                         style="width: 40px; height: 40px;">
                        <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="card-title text-danger mb-2">
                            <i class="bi bi-arrow-clockwise me-2"></i>이전 신청서 정보
                        </h5>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <small class="text-muted d-block">신청번호</small>
                                <div class="fw-semibold">#{{ $application->id }}</div>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">반려일</small>
                                <div class="fw-semibold">{{ $application->rejection_date ? $application->rejection_date->format('Y년 m월 d일') : $application->updated_at->format('Y년 m월 d일') }}</div>
                            </div>
                        </div>

                        @if($application->rejection_reason)
                            <div class="alert alert-danger mb-3">
                                <h6 class="alert-heading mb-2">
                                    <i class="bi bi-x-circle me-1"></i>반려 사유
                                </h6>
                                <p class="mb-0">{{ $application->rejection_reason }}</p>
                            </div>
                        @endif

                        @if(isset($rejectionAnalysis['suggestions']) && count($rejectionAnalysis['suggestions']) > 0)
                            <div class="alert alert-info">
                                <h6 class="alert-heading mb-2">
                                    <i class="bi bi-lightbulb me-1"></i>개선 제안사항
                                </h6>
                                <ul class="list-unstyled mb-0">
                                    @foreach($rejectionAnalysis['suggestions'] as $suggestion)
                                        <li class="mb-1">
                                            <i class="bi bi-check-circle text-success me-2"></i>{{ $suggestion }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="alert alert-warning mb-0">
                            <h6 class="alert-heading mb-2">
                                <i class="bi bi-info-circle me-1"></i>재신청 시 유의사항
                            </h6>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-1"><i class="bi bi-arrow-right text-primary me-2"></i>반려 사유를 참고하여 신청서를 수정해주세요</li>
                                <li class="mb-1"><i class="bi bi-arrow-right text-primary me-2"></i>이전 신청서의 정보가 기본으로 입력됩니다</li>
                                <li class="mb-0"><i class="bi bi-arrow-right text-primary me-2"></i>재신청 후에는 다시 검토 과정을 거치게 됩니다</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-list-check text-primary me-2"></i>재신청 진행 단계
                    </h5>
                    <span class="badge bg-primary">1 / 4 단계</span>
                </div>
                <div class="progress mb-3" style="height: 8px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 25%"></div>
                </div>
                <div class="row text-center">
                    <div class="col-3 process-step">
                        <div class="step-circle active">1</div>
                        <small class="fw-semibold text-success">재신청서 작성</small>
                    </div>
                    <div class="col-3 process-step">
                        <div class="step-circle">2</div>
                        <small class="text-muted">검토</small>
                    </div>
                    <div class="col-3 process-step">
                        <div class="step-circle">3</div>
                        <small class="text-muted">면접</small>
                    </div>
                    <div class="col-3 process-step">
                        <div class="step-circle">4</div>
                        <small class="text-muted">승인</small>
                    </div>
                </div>
            </div>
        </div>


        <!-- 재신청 주의사항 -->
        <div class="alert alert-warning d-flex align-items-start" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-3 flex-shrink-0" style="font-size: 1.5rem;"></i>
            <div>
                <h5 class="alert-heading">재신청 전 확인사항</h5>
                <p class="mb-2">재신청을 위해서는 다음 항목들을 반드시 작성해야 합니다:</p>
                <ul class="mb-0">
                    <li><strong>개선 계획</strong>: 반려 사유를 어떻게 해결할 계획인지 구체적으로 작성</li>
                    <li><strong>신청 동기</strong>: 이전 반려 사유 개선사항과 함께 재기술</li>
                    <li><strong>개선 확인</strong>: 반려 사유를 검토하고 개선했음을 확인</li>
                    <li><strong>개인정보 동의</strong>: 재신청 심사를 위한 필수 동의</li>
                </ul>
            </div>
        </div>

        <!-- Reapplication Form -->
        <form action="{{ route('home.partner.regist.reapply', $rejectedApplication->id) }}" method="POST" enctype="multipart/form-data" id="reapplicationForm">
            @csrf
            <!-- 현재 로그인 사용자 정보 -->
            <input type="hidden" name="user_uuid" value="{{ $userInfo['uuid'] }}">
            <input type="hidden" name="current_user_id" value="{{ $currentUser->id }}">

            <!-- Personal Information -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-person-circle me-2"></i>개인 정보
                    </h5>
                    <small>현재 로그인 계정({{ $currentUser->email }})의 파트너 재신청서를 작성합니다.</small>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">이름 <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name', $application->personal_info['name'] ?? $userInfo['name']) }}" required
                                   class="form-control">
                            <div class="form-text">
                                <i class="bi bi-info-circle me-1"></i>{{ ($application->personal_info['name'] ?? $userInfo['name']) ? '기존 신청서 정보가 기본값으로 설정됩니다.' : '이름을 입력해주세요.' }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">이메일 <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" value="{{ old('email', $userInfo['email']) }}" required readonly
                                   class="form-control bg-light">
                            <div class="form-text">
                                <i class="bi bi-info-circle me-1"></i>로그인 계정의 이메일이 자동으로 입력됩니다.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">전화번호 <span class="text-danger">*</span></label>
                            <input type="tel" name="phone" id="phone" value="{{ old('phone', $application->personal_info['phone'] ?? $userInfo['phone']) }}" required
                                   placeholder="010-1234-5678"
                                   class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="birth_year" class="form-label">출생연도 <span class="text-danger">*</span></label>
                            <select name="birth_year" id="birth_year" required class="form-select">
                                <option value="">출생연도를 선택하세요</option>
                                @php
                                    $currentYear = date('Y');
                                    $minYear = 1950;
                                    $maxYear = $currentYear - 18; // 만 18세 이상
                                    $selectedBirthYear = old('birth_year',
                                        ($application->personal_info['birth_year'] ??
                                         ($application->birth_year ??
                                         (isset($application->personal_info['birth_date']) ?
                                          date('Y', strtotime($application->personal_info['birth_date'])) : ''))));
                                @endphp
                                @for($year = $maxYear; $year >= $minYear; $year--)
                                    <option value="{{ $year }}" {{ $selectedBirthYear == $year ? 'selected' : '' }}>
                                        {{ $year }}년
                                    </option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <!-- Address -->
                    <hr class="my-4">
                    <h6 class="fw-bold text-secondary">
                        <i class="bi bi-geo-alt me-2"></i>주소 정보
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="region" class="form-label">지역 <span class="text-danger">*</span></label>
                            <select name="region" id="region" required class="form-select">
                                <option value="">지역을 선택하세요</option>
                                @foreach($regionOptions as $region => $districts)
                                    <option value="{{ $region }}" {{ old('region', $application->personal_info['region'] ?? '') == $region ? 'selected' : '' }}>
                                        {{ $region }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="district" class="form-label">구/시</label>
                            <select name="district" id="district" class="form-select">
                                <option value="">구/시를 선택하세요</option>
                                <!-- 선택된 지역의 구/시 목록이 JavaScript로 채워집니다 -->
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="address" class="form-label">상세 주소 <span class="text-danger">*</span></label>
                            <input type="text" name="address" id="address" value="{{ old('address', $application->personal_info['address'] ?? '') }}" required
                                   placeholder="상세 주소를 입력하세요"
                                   class="form-control">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Referrer Information -->
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-people me-2"></i>추천자 정보
                    </h5>
                    <small>파트너 추천 관련 정보를 입력해주세요.</small>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="referral_source" class="form-label">신청 경로 <span class="text-danger">*</span></label>
                            <select name="referral_source" id="referral_source" required class="form-select">
                                <option value="">신청 경로를 선택하세요</option>
                                @php
                                    $referralSources = [
                                        'self_application' => '직접 신청',
                                        'direct' => '직접 추천',
                                        'online_link' => '온라인 링크',
                                        'offline_meeting' => '오프라인 미팅',
                                        'social_media' => '소셜미디어',
                                        'event' => '이벤트/세미나',
                                        'advertisement' => '광고',
                                        'word_of_mouth' => '지인 소개',
                                        'other' => '기타'
                                    ];
                                    $selectedSource = old('referral_source', $application->referral_source ?? '');
                                @endphp
                                @foreach($referralSources as $value => $label)
                                    <option value="{{ $value }}" {{ $selectedSource == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 referrer-field">
                            <label for="referral_code" class="form-label">추천 코드</label>
                            <input type="text" name="referral_code" id="referral_code" value="{{ old('referral_code', $application->referral_code ?? '') }}"
                                   placeholder="추천인으로부터 받은 코드를 입력하세요"
                                   class="form-control">
                            <div class="form-text">추천 코드가 있는 경우에만 입력해주세요.</div>
                        </div>
                        <div class="col-md-6 referrer-field">
                            <label for="referrer_name" class="form-label">추천인 이름</label>
                            <input type="text" name="referrer_name" id="referrer_name" value="{{ old('referrer_name', $application->referrer_name ?? '') }}"
                                   placeholder="추천인의 이름을 입력하세요"
                                   class="form-control">
                        </div>
                        <div class="col-md-6 referrer-field">
                            <label for="referrer_contact" class="form-label">추천인 연락처</label>
                            <input type="text" name="referrer_contact" id="referrer_contact" value="{{ old('referrer_contact', $application->referrer_contact ?? '') }}"
                                   placeholder="추천인의 전화번호를 입력하세요"
                                   class="form-control">
                        </div>
                        <div class="col-md-6 referrer-field">
                            <label for="referrer_relationship" class="form-label">추천인과의 관계</label>
                            <input type="text" name="referrer_relationship" id="referrer_relationship" value="{{ old('referrer_relationship', $application->referrer_relationship ?? '') }}"
                                   placeholder="예: 친구, 동료, 가족 등"
                                   class="form-control">
                        </div>
                        <div class="col-md-6 referrer-field">
                            <label for="meeting_date" class="form-label">만남/소개 일자</label>
                            <input type="date" name="meeting_date" id="meeting_date" value="{{ old('meeting_date', $application->meeting_date ?? '') }}"
                                   max="{{ date('Y-m-d') }}"
                                   class="form-control">
                            <div class="form-text">추천인과 처음 만난 날짜 또는 소개받은 날짜</div>
                        </div>
                        <div class="col-md-6 referrer-field">
                            <label for="meeting_location" class="form-label">만남 장소</label>
                            <input type="text" name="meeting_location" id="meeting_location" value="{{ old('meeting_location', $application->meeting_location ?? '') }}"
                                   placeholder="예: 서울역, 온라인, 회사 등"
                                   class="form-control">
                        </div>
                        <div class="col-md-6 referrer-field">
                            <label for="introduction_method" class="form-label">소개 방법</label>
                            <input type="text" name="introduction_method" id="introduction_method" value="{{ old('introduction_method', $application->introduction_method ?? '') }}"
                                   placeholder="예: 전화 통화, 카카오톡, 직접 만남 등"
                                   class="form-control">
                        </div>
                    </div>

                    <!-- Referral Information Display -->
                    <hr class="my-4">
                    <div class="alert alert-info">
                        <h6 class="alert-heading">
                            <i class="bi bi-info-circle me-1"></i>추천자 정보 안내
                        </h6>
                        <ul class="mb-0 small">
                            <li><strong>추천 코드</strong>: 기존 파트너로부터 받은 고유 코드입니다.</li>
                            <li><strong>추천인 정보</strong>: 파트너십 네트워크 구축을 위한 정보입니다.</li>
                            <li><strong>정확한 정보 입력</strong>: 향후 수수료 분배 및 네트워크 관리에 사용됩니다.</li>
                            <li><strong>선택사항</strong>: 직접 신청인 경우 추천인 정보는 비워두셔도 됩니다.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Professional Experience -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-briefcase me-2"></i>전문 경력
                    </h5>
                    <small>반려 사유를 참고하여 전문 기술과 경력을 보완해주세요.</small>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="experience_years" class="form-label">경력 (년) <span class="text-danger">*</span></label>
                            <select name="experience_years" id="experience_years" required class="form-select">
                                <option value="">경력을 선택하세요</option>
                                <option value="0" {{ old('experience_years', $application->experience_info['years'] ?? '') == '0' ? 'selected' : '' }}>신입 (1년 미만)</option>
                                <option value="1" {{ old('experience_years', $application->experience_info['years'] ?? '') == '1' ? 'selected' : '' }}>1년</option>
                                <option value="2" {{ old('experience_years', $application->experience_info['years'] ?? '') == '2' ? 'selected' : '' }}>2년</option>
                                <option value="3" {{ old('experience_years', $application->experience_info['years'] ?? '') == '3' ? 'selected' : '' }}>3년</option>
                                <option value="5" {{ old('experience_years', $application->experience_info['years'] ?? '') == '5' ? 'selected' : '' }}>5년</option>
                                <option value="10" {{ old('experience_years', $application->experience_info['years'] ?? '') == '10' ? 'selected' : '' }}>10년 이상</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="preferred_tier" class="form-label">희망 등급</label>
                            <select name="preferred_tier_id" id="preferred_tier" class="form-select">
                                <option value="">희망 등급을 선택하세요</option>
                                @foreach($partnerTiers as $tier)
                                    <option value="{{ $tier->id }}" {{ old('preferred_tier_id', ($application->personal_info['preferred_tier_id'] ?? $application->preferred_tier_id ?? '')) == $tier->id ? 'selected' : '' }}>
                                        {{ $tier->tier_name }} ({{ $tier->commission_rate }}% 커미션)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Skills -->
                    <hr class="my-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-bold text-secondary mb-0">
                            <i class="bi bi-code-square me-2"></i>기술 스택 및 전문 분야
                        </h6>
                        <span class="badge bg-warning">반려 사유 개선 필요</span>
                    </div>
                    <div class="alert alert-warning">
                        <small><i class="bi bi-exclamation-triangle me-1"></i>반려 사유에서 요구된 기술이나 경험을 추가로 선택해주세요.</small>
                    </div>

                    @php
                        $applicationSkills = $application->skills_info ?? [];

                        // 기존 신청서의 선택된 항목들 가져오기 (여러 가능한 경로 확인)
                        $selectedLanguages = $applicationSkills['languages'] ??
                                           ($application->languages ??
                                           (old('languages', [])));

                        $selectedFrameworks = $applicationSkills['frameworks'] ??
                                            ($application->frameworks ??
                                            (old('frameworks', [])));

                        $selectedSkills = $applicationSkills['skills'] ??
                                        ($application->skills ??
                                        (old('skills', [])));

                        // 배열이 아닌 경우 빈 배열로 초기화
                        $selectedLanguages = is_array($selectedLanguages) ? $selectedLanguages : [];
                        $selectedFrameworks = is_array($selectedFrameworks) ? $selectedFrameworks : [];
                        $selectedSkills = is_array($selectedSkills) ? $selectedSkills : [];
                    @endphp

                    <!-- Programming Languages -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-terminal me-2"></i>프로그래밍 언어
                        </label>
                        <div class="row g-2">
                            @foreach($skillOptions['languages'] as $language)
                                <div class="col-md-4 col-lg-2">
                                    <div class="form-check">
                                        <input type="checkbox" name="languages[]" value="{{ $language }}"
                                               id="lang_{{ $loop->index }}"
                                               {{ in_array($language, old('languages', $selectedLanguages)) ? 'checked' : '' }}
                                               class="form-check-input">
                                        <label class="form-check-label" for="lang_{{ $loop->index }}">
                                            {{ $language }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Frameworks -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-layers me-2"></i>프레임워크
                        </label>
                        <div class="row g-2">
                            @foreach($skillOptions['frameworks'] as $framework)
                                <div class="col-md-4 col-lg-3">
                                    <div class="form-check">
                                        <input type="checkbox" name="frameworks[]" value="{{ $framework }}"
                                               id="framework_{{ $loop->index }}"
                                               {{ in_array($framework, old('frameworks', $selectedFrameworks)) ? 'checked' : '' }}
                                               class="form-check-input">
                                        <label class="form-check-label" for="framework_{{ $loop->index }}">
                                            {{ $framework }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Professional Skills -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-gear me-2"></i>전문 분야
                        </label>
                        <div class="row g-2">
                            @foreach($skillOptions['skills'] as $skill)
                                <div class="col-md-6 col-lg-3">
                                    <div class="form-check">
                                        <input type="checkbox" name="skills[]" value="{{ $skill }}"
                                               id="skill_{{ $loop->index }}"
                                               {{ in_array($skill, old('skills', $selectedSkills)) ? 'checked' : '' }}
                                               class="form-check-input">
                                        <label class="form-check-label" for="skill_{{ $loop->index }}">
                                            {{ $skill }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Portfolio -->
                    <hr class="my-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="portfolio_url" class="form-label fw-semibold">
                                <i class="bi bi-folder-symlink me-2"></i>포트폴리오 URL
                            </label>
                            <input type="url" name="portfolio_url" id="portfolio_url" value="{{ old('portfolio_url', $application->documents['portfolio_url'] ?? '') }}"
                                   placeholder="https://github.com/username 또는 개인 포트폴리오 사이트"
                                   class="form-control">
                            <div class="form-text text-warning">
                                <i class="bi bi-exclamation-triangle me-1"></i>포트폴리오를 추가하거나 업데이트하여 더 나은 평가를 받으세요.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Application Details -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-pencil-square me-2"></i>재신청 내용
                    </h5>
                    <small>반려 사유를 바탕으로 신청 동기와 목표를 보완해주세요.</small>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="motivation" class="form-label">신청 동기 및 개선사항 <span class="text-danger">*</span></label>
                            <textarea name="motivation" id="motivation" rows="5" required
                                      placeholder="이전 반려 사유를 어떻게 개선했는지와 함께 파트너 신청 동기를 다시 기술해주세요."
                                      class="form-control">{{ old('motivation', $application->motivation) }}</textarea>
                            <div class="form-text text-warning">
                                <i class="bi bi-exclamation-triangle me-1"></i>반려 사유에 대한 개선사항을 구체적으로 명시해주세요.
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="bg-warning bg-opacity-10 border border-warning rounded p-3 mb-3">
                                <h6 class="text-warning mb-2">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>필수 작성 항목
                                </h6>
                                <p class="mb-0 small">개선 계획은 재신청을 위한 필수 항목입니다. 반려 사유를 어떻게 해결할 계획인지 구체적으로 작성해주세요.</p>
                            </div>
                            <label for="improvement_plan" class="form-label fw-bold">개선 계획 <span class="text-danger">*</span></label>
                            <textarea name="improvement_plan" id="improvement_plan" rows="4" required
                                      placeholder="예시: 기술 부족이 반려 사유였다면 '○○ 과정 수강 완료', '○○ 프로젝트 추가 개발' 등 구체적인 개선 활동을 기술해주세요."
                                      class="form-control border-warning">{{ old('improvement_plan') }}</textarea>
                            <div class="form-text text-danger">
                                <i class="bi bi-exclamation-circle me-1"></i>이 항목은 반드시 작성해야 재신청이 가능합니다.
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="project_experience" class="form-label">추가 프로젝트 경험</label>
                            <textarea name="project_experience" id="project_experience" rows="4"
                                      placeholder="이전 신청 이후 추가로 진행한 프로젝트나 경험을 기술해주세요."
                                      class="form-control">{{ old('project_experience', $application->project_experience) }}</textarea>
                        </div>

                        <div class="col-12">
                            <label for="goals" class="form-label">수정된 목표 및 계획</label>
                            <textarea name="goals" id="goals" rows="3"
                                      placeholder="반려 사유를 반영하여 수정된 목표와 계획을 기술해주세요."
                                      class="form-control">{{ old('goals', $application->goals) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Documents -->
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-file-earmark-plus me-2"></i>추가 서류
                    </h5>
                    <small>반려 사유 개선을 위한 추가 증빙 서류를 첨부해주세요.</small>
                </div>
                <div class="card-body">
                    <div>
                        <label for="additional_attachments" class="form-label">추가 파일 첨부</label>
                        <input type="file" name="additional_attachments[]" id="additional_attachments" multiple
                               accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                               class="form-control">
                        <div class="form-text">
                            <i class="bi bi-info-circle me-1"></i>추가 자격증, 포트폴리오, 추천서 등을 첨부하여 재평가를 받으세요. (최대 10MB)
                        </div>
                    </div>
                </div>
            </div>

            <!-- Agreement -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-check-square me-2"></i>확인 및 동의
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="bg-danger bg-opacity-10 border border-danger rounded p-3">
                                <div class="form-check">
                                    <input type="checkbox" name="improvement_confirmed" id="improvement_confirmed" required
                                           class="form-check-input border-danger">
                                    <label for="improvement_confirmed" class="form-check-label fw-bold">
                                        반려 사유를 확인하고 개선했음을 확인합니다. <span class="text-danger">*</span>
                                    </label>
                                    <div class="form-text text-danger">
                                        <i class="bi bi-check-circle me-1"></i>이전 반려 사유를 충분히 검토하고 개선사항을 반영했습니다. 체크하지 않으면 재신청할 수 없습니다.
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="bg-primary bg-opacity-10 border border-primary rounded p-3">
                                <div class="form-check">
                                    <input type="checkbox" name="terms_agreed" id="terms_agreed" required
                                           class="form-check-input border-primary">
                                    <label for="terms_agreed" class="form-check-label fw-bold">
                                        개인정보 수집 및 이용에 동의합니다. <span class="text-danger">*</span>
                                    </label>
                                    <div class="form-text text-primary">
                                        <i class="bi bi-shield-check me-1"></i>재신청 심사를 위해 제공된 개인정보가 수집 및 이용됩니다. 필수 동의 항목입니다.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="d-flex justify-content-between">
                <a href="{{ route('home.partner.regist.status', $application->id) }}"
                   class="btn btn-outline-secondary px-4 py-2">
                    <i class="bi bi-arrow-left me-2"></i>취소
                </a>
                <div class="d-flex gap-3">
                    <button type="submit" name="submit_type" value="draft"
                            class="btn btn-outline-primary px-4 py-2">
                        <i class="bi bi-save me-2"></i>임시 저장
                    </button>
                    <button type="submit" name="submit_type" value="submit"
                            class="btn btn-success px-4 py-2">
                        <i class="bi bi-send me-2"></i>재신청 제출
                    </button>
                </div>
            </div>
        </form>

    </div>

@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Region-District dependency
        const regionSelect = document.getElementById('region');
        const districtSelect = document.getElementById('district');
        const regionOptions = @json($regionOptions);
        const currentDistrict = "{{ old('district', $application->personal_info['district'] ?? '') }}";

        function updateDistricts() {
            const selectedRegion = regionSelect.value;
            districtSelect.innerHTML = '<option value="">구/시를 선택하세요</option>';

            if (selectedRegion && regionOptions[selectedRegion]) {
                regionOptions[selectedRegion].forEach(function(district) {
                    const option = document.createElement('option');
                    option.value = district;
                    option.textContent = district;
                    if (district === currentDistrict) {
                        option.selected = true;
                    }
                    districtSelect.appendChild(option);
                });
            }
        }

        // Initialize districts on page load
        updateDistricts();

        regionSelect.addEventListener('change', updateDistricts);

        // Referral source dependency - show/hide referrer fields
        const referralSourceSelect = document.getElementById('referral_source');
        const referrerFields = document.querySelectorAll('.referrer-field');

        function updateReferrerFields() {
            const selectedSource = referralSourceSelect.value;
            const shouldShowFields = selectedSource && selectedSource !== 'self_application';

            referrerFields.forEach(field => {
                if (shouldShowFields) {
                    field.style.display = 'block';
                    field.classList.remove('d-none');
                } else {
                    field.style.display = 'none';
                    field.classList.add('d-none');
                    // Clear values when hidden
                    const inputs = field.querySelectorAll('input');
                    inputs.forEach(input => {
                        if (input.type !== 'hidden') {
                            input.value = '';
                        }
                    });
                }
            });
        }

        // Initialize referrer fields visibility
        updateReferrerFields();

        referralSourceSelect.addEventListener('change', updateReferrerFields);

        // ===== AJAX 폼 제출 처리 (상세 로깅 포함) =====
        const form = document.getElementById('reapplicationForm');

        // 폼 요소 확인
        if (!form) {
            console.error('❌ 재신청 폼을 찾을 수 없습니다!');
            return;
        }

        console.log('✅ 재신청 폼 찾음:', form);

        // CSRF 토큰 가져오기
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                         document.querySelector('input[name="_token"]')?.value;

        console.log('🔐 CSRF 토큰:', csrfToken ? '확인됨' : '❌ 없음');

        // 헬퍼 함수들 정의
        function disableSubmitButtons(clickedButton) {
            const submitButtons = form.querySelectorAll('button[type="submit"]');
            submitButtons.forEach(btn => {
                btn.disabled = true;
                if (btn === clickedButton) {
                    btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>처리 중...';
                }
            });
            console.log('🔒 제출 버튼들 비활성화');
        }

        function enableSubmitButtons() {
            const submitButtons = form.querySelectorAll('button[type="submit"]');
            submitButtons.forEach(btn => {
                btn.disabled = false;
            });
            // 원래 텍스트 복원
            const draftBtn = form.querySelector('button[value="draft"]');
            const submitBtn = form.querySelector('button[value="submit"]');
            if (draftBtn) draftBtn.innerHTML = '<i class="bi bi-save me-2"></i>임시 저장';
            if (submitBtn) submitBtn.innerHTML = '<i class="bi bi-send me-2"></i>재신청 제출';
            console.log('🔓 제출 버튼들 활성화');
        }

        function showSuccessMessage(message) {
            console.log('✅ 성공 메시지:', message);

            // 기존 알림 제거
            const existingAlert = form.querySelector('.alert');
            if (existingAlert) existingAlert.remove();

            // 성공 알림 생성
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show mb-4';
            alert.innerHTML = `
                <i class="bi bi-check-circle me-2"></i>
                <strong>성공!</strong> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            form.insertBefore(alert, form.firstChild);
        }

        function showErrorMessage(message) {
            console.error('❌ 오류 메시지:', message);

            // 기존 알림 제거
            const existingAlert = form.querySelector('.alert');
            if (existingAlert) existingAlert.remove();

            // 오류 알림 생성
            const alert = document.createElement('div');
            alert.className = 'alert alert-danger alert-dismissible fade show mb-4';
            alert.innerHTML = `
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>오류!</strong> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            form.insertBefore(alert, form.firstChild);
        }

        // 폼 제출 이벤트 리스너
        form.addEventListener('submit', function(e) {
            console.log('🚀 === 재신청 폼 제출 이벤트 시작 ===');
            e.preventDefault(); // 기본 폼 제출 방지

            const submitButton = e.submitter;
            const submitType = submitButton ? submitButton.value : 'submit';

            console.log('🎯 Submit button:', submitButton);
            console.log('🎯 Submit type:', submitType);
            console.log('🎯 Form action:', form.action);

            try {
                // 로딩 상태 표시
                disableSubmitButtons(submitButton);

                // FormData 생성
                const formData = new FormData(form);
                formData.set('submit_type', submitType);

                console.log('📦 FormData 생성 완료. 전송할 데이터:');
                for (let [key, value] of formData.entries()) {
                    console.log(`  📝 ${key}: ${value}`);
                }

                // AJAX 제출
                console.log('📡 AJAX 요청 시작...');
                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken
                    }
                })
                .then(response => {
                    console.log('📨 === HTTP 응답 수신 ===');
                    console.log('📊 Response status:', response.status);
                    console.log('📊 Response statusText:', response.statusText);
                    console.log('📊 Response URL:', response.url);

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }

                    return response.json();
                })
                .then(data => {
                    console.log('🎉 === 성공 응답 처리 ===');
                    console.log('📄 Response data:', data);

                    if (data.success) {
                        console.log('✅ 재신청 처리 성공!');

                        // 성공 메시지 표시
                        showSuccessMessage(data.message || '재신청이 성공적으로 제출되었습니다.');

                        // 상태 페이지로 리다이렉트
                        const redirectUrl = data.redirect_url || `/home/partner/regist/${data.application_id}/status`;
                        console.log('🔄 리다이렉트 URL:', redirectUrl);

                        setTimeout(() => {
                            console.log('🚪 페이지 이동 중...');
                            window.location.href = redirectUrl;
                        }, 2000);
                    } else {
                        throw new Error(data.message || '서버에서 알 수 없는 오류가 반환되었습니다.');
                    }
                })
                .catch(error => {
                    console.error('💥 === 오류 발생 ===');
                    console.error('💥 Error type:', error.constructor.name);
                    console.error('💥 Error message:', error.message);
                    console.error('💥 Error stack:', error.stack);

                    // 오류 메시지 표시
                    showErrorMessage(error.message || '네트워크 오류가 발생했습니다.');
                })
                .finally(() => {
                    console.log('🔄 === 처리 완료, 버튼 상태 복원 ===');
                    enableSubmitButtons();
                });

            } catch (error) {
                console.error('💥 예외 발생:', error);
                showErrorMessage('폼 처리 중 예기치 못한 오류가 발생했습니다.');
                enableSubmitButtons();
            }
        });

        console.log('🎯 재신청 폼 이벤트 리스너 등록 완료');
    });
    </script>
@endsection

{{-- 자바스크립트 --}}
@push('scripts')

@endpush
