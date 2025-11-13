@extends('jiny-partner::layouts.home')

@section('title', $pageTitle ?? '파트너 신청서 수정')

@section('styles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
            margin-bottom: 1.5rem;
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
                        <h1 class="h3 mb-2">{{ $pageTitle ?? '파트너 신청서 수정' }}</h1>
                        <p class="text-muted mb-0"></p>
                        {{-- <span class="text-muted small">
                        <i class="bi bi-person-circle me-1"></i>{{ $currentUser->name ?? '사용자' }}님
                    </span> --}}
                    </div>
                    <div>



                        <a href="{{ route('home.partner.regist.status', $application->id) }}"
                            class="btn btn-outline-primary">
                            <i class="bi bi-eye me-1"></i>상태 확인
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Process Steps -->
        <section class="mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0 fw-bold">
                            <i class="bi bi-list-check text-primary me-2"></i>신청 진행 단계
                        </h5>
                        <span class="badge bg-warning">수정 중</span>
                    </div>

                    <div class="row text-center">
                        <div class="col-lg-4 process-step">
                            <div class="step-circle active">1</div>
                            <h6 class="fw-bold mb-2 text-primary">신청서 수정</h6>
                            <p class="text-muted small mb-0">개인정보, 경력사항, 전문분야를 수정하여 재제출합니다.</p>
                        </div>
                        <div class="col-lg-4 process-step">
                            <div class="step-circle">2</div>
                            <h6 class="fw-bold mb-2">재검토 및 면접</h6>
                            <p class="text-muted small mb-0">수정된 신청서를 바탕으로 재검토하고 필요시 면접을 진행합니다.</p>
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


        <!-- Main Content -->
        <main class="container-fluid py-4">
            <div class="row justify-content-center">
                <div class="col-12 col-xl-10">


                    <!-- Status Notice -->
                    @if ($application->application_status === 'draft')
                        <div class="alert alert-warning d-flex align-items-start mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-3 mt-1 flex-shrink-0"></i>
                            <div class="flex-grow-1">
                                <h6 class="alert-heading fw-bold mb-2">임시 저장된 신청서</h6>
                                <p class="mb-0 small">
                                    {{ $application->updated_at->format('Y년 m월 d일 H:i') }}에 마지막으로 저장되었습니다.
                                    수정 후 제출하시면 검토가 진행됩니다.
                                </p>
                            </div>
                        </div>
                    @elseif($application->application_status === 'rejected')
                        <div class="alert alert-danger d-flex align-items-start mb-4" role="alert">
                            <i class="bi bi-x-circle-fill me-3 mt-1 flex-shrink-0"></i>
                            <div class="flex-grow-1">
                                <h6 class="alert-heading fw-bold mb-2">반려된 신청서 수정</h6>
                                <p class="mb-0 small">
                                    {{ $application->rejected_at->format('Y년 m월 d일') }}에 반려되었습니다.
                                    @if ($application->rejection_reason)
                                        <br><strong>반려 사유:</strong> {{ $application->rejection_reason }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endif

                    <!-- Application Form -->
                    <form action="{{ route('home.partner.regist.update', $application->id) }}" method="POST"
                        enctype="multipart/form-data" id="applicationForm">
                        @csrf
                        @method('PUT')

                        <!-- 현재 로그인 사용자 정보 -->
                        <input type="hidden" name="user_uuid" value="{{ $userInfo['uuid'] }}">
                        <input type="hidden" name="current_user_id" value="{{ $currentUser->id }}">

                        <!-- Personal Information -->
                        <div class="card border-0 shadow-sm form-section mb-4">
                            <div class="card-header">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-person text-primary fs-5 me-2"></i>
                                    <div>
                                        <h5 class="card-title mb-0 fw-bold">개인 정보</h5>
                                        <small class="text-muted">현재 로그인 계정({{ $currentUser->email }})의 파트너 신청서를
                                            수정합니다.</small>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label fw-semibold">이름 <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="name" id="name"
                                            value="{{ old('name', $application->personal_info['name'] ?? $userInfo['name']) }}"
                                            required readonly class="form-control" style="background-color: #f8f9fa;">
                                        <div class="form-text">
                                            <i class="bi bi-info-circle me-1"></i>로그인 계정의 이름으로 고정됩니다. (수정 불가)
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label fw-semibold">이메일 <span
                                                class="text-danger">*</span></label>
                                        <input type="email" name="email" id="email"
                                            value="{{ old('email', $userInfo['email']) }}" required readonly
                                            class="form-control" style="background-color: #f8f9fa;">
                                        <div class="form-text">
                                            <i class="bi bi-info-circle me-1"></i>로그인 계정의 이메일로 고정됩니다. (수정 불가)
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label fw-semibold">전화번호 <span
                                                class="text-danger">*</span></label>
                                        <input type="tel" name="phone" id="phone"
                                            value="{{ old('phone', $application->personal_info['phone'] ?? $userInfo['phone']) }}"
                                            required placeholder="010-1234-5678" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="country" class="form-label fw-semibold">국가 <span
                                                class="text-danger">*</span></label>
                                        <select name="country" id="country" required
                                            class="form-select {{ $errors->has('country') ? 'is-invalid' : '' }}">
                                            <option value="">국가를 선택하세요</option>
                                            <option value="KR"
                                                {{ old('country', $application->personal_info['country'] ?? 'KR') == 'KR' ? 'selected' : '' }}>
                                                🇰🇷 대한민국</option>
                                            <option value="US"
                                                {{ old('country', $application->personal_info['country'] ?? 'KR') == 'US' ? 'selected' : '' }}>
                                                🇺🇸 미국</option>
                                            <option value="JP"
                                                {{ old('country', $application->personal_info['country'] ?? 'KR') == 'JP' ? 'selected' : '' }}>
                                                🇯🇵 일본</option>
                                            <option value="CN"
                                                {{ old('country', $application->personal_info['country'] ?? 'KR') == 'CN' ? 'selected' : '' }}>
                                                🇨🇳 중국</option>
                                            <option value="CA"
                                                {{ old('country', $application->personal_info['country'] ?? 'KR') == 'CA' ? 'selected' : '' }}>
                                                🇨🇦 캐나다</option>
                                            <option value="AU"
                                                {{ old('country', $application->personal_info['country'] ?? 'KR') == 'AU' ? 'selected' : '' }}>
                                                🇦🇺 호주</option>
                                            <option value="GB"
                                                {{ old('country', $application->personal_info['country'] ?? 'KR') == 'GB' ? 'selected' : '' }}>
                                                🇬🇧 영국</option>
                                            <option value="DE"
                                                {{ old('country', $application->personal_info['country'] ?? 'KR') == 'DE' ? 'selected' : '' }}>
                                                🇩🇪 독일</option>
                                            <option value="FR"
                                                {{ old('country', $application->personal_info['country'] ?? 'KR') == 'FR' ? 'selected' : '' }}>
                                                🇫🇷 프랑스</option>
                                            <option value="SG"
                                                {{ old('country', $application->personal_info['country'] ?? 'KR') == 'SG' ? 'selected' : '' }}>
                                                🇸🇬 싱가포르</option>
                                            <option value="OTHER"
                                                {{ old('country', $application->personal_info['country'] ?? 'KR') == 'OTHER' ? 'selected' : '' }}>
                                                🌍 기타</option>
                                        </select>
                                        @if ($errors->has('country'))
                                            <div class="invalid-feedback">
                                                {{ $errors->first('country') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Address -->
                                <hr class="my-4">
                                <h6 class="fw-semibold mb-3">
                                    <i class="bi bi-geo-alt text-primary me-2"></i>주소 정보
                                </h6>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="address" class="form-label fw-semibold">주소 <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="address" id="address"
                                            value="{{ old('address', $application->personal_info['address'] ?? '') }}"
                                            required placeholder="전체 주소를 입력하세요" class="form-control">
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- Professional Experience -->
                        <div class="card border-0 shadow-sm form-section mb-4">
                            <div class="card-header">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-briefcase text-primary fs-5 me-2"></i>
                                    <div>
                                        <h5 class="card-title mb-0 fw-bold">전문 경력</h5>
                                        <small class="text-muted">전문 기술과 경력 사항을 수정하세요.</small>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="total_years" class="form-label fw-semibold">경력 (년)</label>
                                        <select name="total_years" id="total_years" class="form-select">
                                            <option value="">경력을 선택하세요</option>
                                            @for ($years = 0; $years <= 50; $years++)
                                                <option value="{{ $years }}"
                                                    {{ old('total_years', $application->experience_info['total_years'] ?? '') == $years ? 'selected' : '' }}>
                                                    {{ $years == 0 ? '신입 (1년 미만)' : $years . '년' }}
                                                </option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="portfolio_url" class="form-label fw-semibold">포트폴리오 URL</label>
                                        <input type="url" name="portfolio_url" id="portfolio_url"
                                            value="{{ old('portfolio_url', $application->experience_info['portfolio_url'] ?? '') }}"
                                            placeholder="https://github.com/username" class="form-control">
                                    </div>
                                </div>

                                <div class="row g-3 mt-3">
                                    <div class="col-12">
                                        <label for="career_summary" class="form-label fw-semibold">경력 요약</label>
                                        <textarea name="career_summary" id="career_summary" rows="4" placeholder="주요 경력과 업무 경험을 요약하여 작성해주세요"
                                            class="form-control">{{ old('career_summary', $application->experience_info['career_summary'] ?? '') }}</textarea>
                                    </div>
                                    <div class="col-12">
                                        <label for="bio" class="form-label fw-semibold">자기소개</label>
                                        <textarea name="bio" id="bio" rows="3" placeholder="간단한 자기소개를 작성해주세요" class="form-control">{{ old('bio', $application->experience_info['bio'] ?? '') }}</textarea>
                                    </div>
                                </div>

                                <!-- Skills -->
                                <hr class="my-4">
                                <h6 class="fw-semibold mb-3">
                                    <i class="bi bi-code-slash text-primary me-2"></i>기술 스택 및 전문 분야
                                </h6>

                                @php
                                    $applicationSkills = $application->skills_info ?? [];
                                    $selectedSkills = $applicationSkills['skills'] ?? [];
                                    $skillLevels = $applicationSkills['skill_levels'] ?? [];
                                    $selectedCertifications = $applicationSkills['certifications'] ?? [];
                                    $selectedLanguages = $applicationSkills['languages'] ?? [];
                                @endphp

                                <!-- Professional Skills -->
                                <div class="mb-4">
                                    <label class="form-label fw-semibold mb-3">전문 기술</label>
                                    <div id="skillsContainer">
                                        <div class="skill-item mb-3">
                                            <div class="row g-2">
                                                <div class="col-md-8">
                                                    <input type="text" name="skills[]"
                                                        placeholder="기술명 (예: PHP, Laravel, JavaScript)"
                                                        value="{{ old('skills.0', $selectedSkills[0] ?? '') }}"
                                                        class="form-control">
                                                </div>
                                                <div class="col-md-4">
                                                    <select name="skill_levels[]" class="form-select">
                                                        <option value="기초"
                                                            {{ old('skill_levels.0', $skillLevels[$selectedSkills[0] ?? ''] ?? '기초') == '기초' ? 'selected' : '' }}>
                                                            기초</option>
                                                        <option value="중급"
                                                            {{ old('skill_levels.0', $skillLevels[$selectedSkills[0] ?? ''] ?? '기초') == '중급' ? 'selected' : '' }}>
                                                            중급</option>
                                                        <option value="고급"
                                                            {{ old('skill_levels.0', $skillLevels[$selectedSkills[0] ?? ''] ?? '기초') == '고급' ? 'selected' : '' }}>
                                                            고급</option>
                                                        <option value="전문가"
                                                            {{ old('skill_levels.0', $skillLevels[$selectedSkills[0] ?? ''] ?? '기초') == '전문가' ? 'selected' : '' }}>
                                                            전문가</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        @for ($i = 1; $i < max(5, count($selectedSkills)); $i++)
                                            <div class="skill-item mb-3">
                                                <div class="row g-2">
                                                    <div class="col-md-8">
                                                        <input type="text" name="skills[]" placeholder="기술명 (선택)"
                                                            value="{{ old('skills.' . $i, $selectedSkills[$i] ?? '') }}"
                                                            class="form-control">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <select name="skill_levels[]" class="form-select">
                                                            <option value="기초"
                                                                {{ old('skill_levels.' . $i, $skillLevels[$selectedSkills[$i] ?? ''] ?? '기초') == '기초' ? 'selected' : '' }}>
                                                                기초</option>
                                                            <option value="중급"
                                                                {{ old('skill_levels.' . $i, $skillLevels[$selectedSkills[$i] ?? ''] ?? '기초') == '중급' ? 'selected' : '' }}>
                                                                중급</option>
                                                            <option value="고급"
                                                                {{ old('skill_levels.' . $i, $skillLevels[$selectedSkills[$i] ?? ''] ?? '기초') == '고급' ? 'selected' : '' }}>
                                                                고급</option>
                                                            <option value="전문가"
                                                                {{ old('skill_levels.' . $i, $skillLevels[$selectedSkills[$i] ?? ''] ?? '기초') == '전문가' ? 'selected' : '' }}>
                                                                전문가</option>
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
                                        @for ($i = 0; $i < 3; $i++)
                                            <div class="col-md-4">
                                                <input type="text" name="certifications[]" placeholder="자격증명 (선택)"
                                                    value="{{ old('certifications.' . $i, $selectedCertifications[$i] ?? '') }}"
                                                    class="form-control">
                                            </div>
                                        @endfor
                                    </div>
                                </div>

                                <!-- Languages -->
                                <div class="mb-4">
                                    <label class="form-label fw-semibold mb-3">사용 가능한 언어</label>
                                    <div class="row g-2">
                                        @for ($i = 0; $i < 3; $i++)
                                            <div class="col-md-4">
                                                <input type="text" name="languages[]" placeholder="언어명 (예: 한국어, 영어)"
                                                    value="{{ old('languages.' . $i, $selectedLanguages[$i] ?? '') }}"
                                                    class="form-control">
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Work Conditions -->
                        <div class="card border-0 shadow-sm form-section mb-4">
                            <div class="card-header">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-currency-dollar text-primary fs-5 me-2"></i>
                                    <div>
                                        <h5 class="card-title mb-0 fw-bold">근무 조건</h5>
                                        <small class="text-muted">희망하는 근무 조건을 설정하세요.</small>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <!-- Preferred Regions -->
                                <h6 class="fw-semibold mb-3">
                                    <i class="bi bi-geo text-primary me-2"></i>선호 근무 지역
                                </h6>
                                <div class="row g-2">
                                    @php
                                        $selectedRegions = old(
                                            'preferred_regions',
                                            $application->preferred_work_areas['regions'] ?? [],
                                        );
                                        $regions = ['서울', '경기', '인천', '부산', '대구', '광주', '대전', '울산'];
                                    @endphp
                                    @foreach ($regions as $region)
                                        <div class="col-md-3 col-6">
                                            <div class="form-check">
                                                <input type="checkbox" name="preferred_regions[]"
                                                    value="{{ $region }}"
                                                    {{ in_array($region, $selectedRegions) ? 'checked' : '' }}
                                                    class="form-check-input" id="region_{{ $loop->index }}">
                                                <label class="form-check-label"
                                                    for="region_{{ $loop->index }}">{{ $region }}</label>
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
                                    $schedule = $application->availability_schedule ?? [];
                                    $weekdays = [
                                        'monday' => '월요일',
                                        'tuesday' => '화요일',
                                        'wednesday' => '수요일',
                                        'thursday' => '목요일',
                                        'friday' => '금요일',
                                    ];
                                @endphp

                                <div class="row g-3 mb-4">
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">평일 근무 가능 시간</label>
                                    </div>
                                    @foreach ($weekdays as $day => $dayKorean)
                                        <div class="col-12">
                                            <div class="row g-2 align-items-center">
                                                <div class="col-md-2">
                                                    <div class="form-check">
                                                        <input type="checkbox" name="{{ $day }}_available"
                                                            value="1"
                                                            {{ old($day . '_available', $schedule['weekdays'][$day]['available'] ?? false) ? 'checked' : '' }}
                                                            class="form-check-input" id="{{ $day }}_available">
                                                        <label class="form-check-label"
                                                            for="{{ $day }}_available">{{ $dayKorean }}</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="time" name="{{ $day }}_start"
                                                        value="{{ old($day . '_start', $schedule['weekdays'][$day]['start'] ?? '09:00') }}"
                                                        class="form-control">
                                                </div>
                                                <div class="col-md-1 text-center">~</div>
                                                <div class="col-md-3">
                                                    <input type="time" name="{{ $day }}_end"
                                                        value="{{ old($day . '_end', $schedule['weekdays'][$day]['end'] ?? '18:00') }}"
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
                                                {{ old('saturday_available', $schedule['weekend']['saturday']['available'] ?? false) ? 'checked' : '' }}
                                                class="form-check-input" id="saturday_available">
                                            <label class="form-check-label" for="saturday_available">토요일 근무 가능</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input type="checkbox" name="sunday_available" value="1"
                                                {{ old('sunday_available', $schedule['weekend']['sunday']['available'] ?? false) ? 'checked' : '' }}
                                                class="form-check-input" id="sunday_available">
                                            <label class="form-check-label" for="sunday_available">일요일 근무 가능</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input type="checkbox" name="holiday_work" value="1"
                                                {{ old('holiday_work', $schedule['holiday_work'] ?? false) ? 'checked' : '' }}
                                                class="form-check-input" id="holiday_work">
                                            <label class="form-check-label" for="holiday_work">공휴일 근무 가능</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- File Attachments -->
                        <div class="card border-0 shadow-sm form-section mb-4">
                            <div class="card-header">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-paperclip text-primary fs-5 me-2"></i>
                                    <div>
                                        <h5 class="card-title mb-0 fw-bold">첨부 서류</h5>
                                        <small class="text-muted">이력서와 포트폴리오 등 관련 서류를 첨부하세요.</small>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                @php
                                    $documents = $application->documents ?? [];
                                @endphp

                                <!-- Resume -->
                                <div class="mb-4">
                                    <label for="resume" class="form-label fw-semibold">이력서</label>
                                    @if (isset($documents['resume']))
                                        <div
                                            class="alert alert-success d-flex justify-content-between align-items-center py-2 mb-2">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-file-earmark-pdf text-success me-2"></i>
                                                <span
                                                    class="small">{{ $documents['resume']['original_name'] ?? '현재 이력서' }}</span>
                                            </div>
                                            <small
                                                class="text-muted">{{ isset($documents['resume']['uploaded_at']) ? '업로드: ' . date('Y-m-d', strtotime($documents['resume']['uploaded_at'])) : '' }}</small>
                                        </div>
                                        <div class="form-text mb-2">새 파일을 선택하면 기존 이력서가 교체됩니다.</div>
                                    @endif
                                    <input type="file" name="resume" id="resume" accept=".pdf,.doc,.docx"
                                        class="form-control">
                                    <div class="form-text">PDF, DOC, DOCX 파일만 업로드 가능 (최대 5MB)</div>
                                </div>

                                <!-- Portfolio -->
                                <div class="mb-4">
                                    <label for="portfolio" class="form-label fw-semibold">포트폴리오</label>
                                    @if (isset($documents['portfolio']))
                                        <div
                                            class="alert alert-success d-flex justify-content-between align-items-center py-2 mb-2">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-file-earmark-zip text-success me-2"></i>
                                                <span
                                                    class="small">{{ $documents['portfolio']['original_name'] ?? '현재 포트폴리오' }}</span>
                                            </div>
                                            <small
                                                class="text-muted">{{ isset($documents['portfolio']['uploaded_at']) ? '업로드: ' . date('Y-m-d', strtotime($documents['portfolio']['uploaded_at'])) : '' }}</small>
                                        </div>
                                        <div class="form-text mb-2">새 파일을 선택하면 기존 포트폴리오가 교체됩니다.</div>
                                    @endif
                                    <input type="file" name="portfolio" id="portfolio" accept=".pdf,.doc,.docx,.zip"
                                        class="form-control">
                                    <div class="form-text">PDF, DOC, DOCX, ZIP 파일만 업로드 가능 (최대 10MB)</div>
                                </div>

                                <!-- Other Documents -->
                                <div class="mb-3">
                                    <label for="other_documents" class="form-label fw-semibold">기타 서류</label>
                                    @if (isset($documents['other']) && count($documents['other']) > 0)
                                        <div class="mb-3">
                                            <h6 class="fw-semibold mb-2">기존 기타 서류</h6>
                                            @foreach ($documents['other'] as $index => $file)
                                                <div
                                                    class="alert alert-info d-flex justify-content-between align-items-center py-2 mb-1">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-file-earmark text-info me-2"></i>
                                                        <span
                                                            class="small">{{ $file['original_name'] ?? '기타 문서 ' . ($index + 1) }}</span>
                                                    </div>
                                                    <small
                                                        class="text-muted">{{ isset($file['uploaded_at']) ? '업로드: ' . date('Y-m-d', strtotime($file['uploaded_at'])) : '' }}</small>
                                                </div>
                                            @endforeach
                                            <div class="form-text mb-2">새 파일을 선택하면 모든 기존 기타 서류가 교체됩니다.</div>
                                        </div>
                                    @endif
                                    <input type="file" name="other_documents[]" id="other_documents" multiple
                                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="form-control">
                                    <div class="form-text">PDF, DOC, DOCX, JPG, PNG 파일만 업로드 가능 (최대 5MB)</div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <a href="{{ route('home.partner.regist.status', $application->id) }}"
                                class="btn btn-outline-secondary px-4 py-2">
                                <i class="bi bi-arrow-left me-2"></i>취소
                            </a>
                            <div>
                                @if ($application->application_status === 'draft')
                                    <button type="submit" name="submit_type" value="submit"
                                        class="btn btn-primary px-4 py-2">
                                        <i class="bi bi-send me-2"></i>신청서 제출
                                    </button>
                                @else
                                    <button type="submit" name="submit_type" value="resubmit"
                                        class="btn btn-success px-4 py-2">
                                        <i class="bi bi-arrow-repeat me-2"></i>재제출
                                    </button>
                                    @if ($application->application_status === 'rejected')
                                        <!-- Reapplication Reason -->
                                        <input type="hidden" name="reapplication_reason" value="파트너 신청서 정보 수정 및 재제출">
                                    @endif
                                @endif
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('applicationForm');

            if (form) {
                // AJAX form submission
                form.addEventListener('submit', function(e) {
                    e.preventDefault(); // 기본 폼 제출 방지

                    const submitBtn = e.submitter;
                    const originalText = submitBtn.innerHTML;

                    // 버튼 비활성화 및 로딩 표시
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="spinner-border spinner-border-sm me-2"></i>처리중...';

                    // FormData 생성 (파일 업로드 포함)
                    const formData = new FormData(this);

                    // CSRF 토큰 추가
                    const csrfToken = document.querySelector('meta[name="csrf-token"]');
                    if (csrfToken) {
                        formData.append('_token', csrfToken.getAttribute('content'));
                    }

                    // 제출 타입 추가 (재제출/신청서 제출)
                    formData.append('submit_type', submitBtn.value);

                    console.log('AJAX 폼 제출 시작:', this.action);

                    // AJAX 요청
                    fetch(this.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                        }
                    })
                    .then(response => {
                        console.log('응답 상태:', response.status);
                        return response.json();
                    })
                    .then(data => {
                        console.log('응답 데이터:', data);

                        if (data.success) {
                            // 성공 메시지 표시
                            alert(data.message || '신청서가 성공적으로 수정되었습니다.');

                            // 상태 페이지로 리다이렉트
                            if (data.redirect) {
                                window.location.href = data.redirect;
                            } else {
                                // 기본 리다이렉트
                                window.location.href = '{{ route("home.partner.regist.status", $application->id) }}';
                            }
                        } else {
                            // 에러 메시지 표시
                            alert(data.message || '수정 중 오류가 발생했습니다.');

                            // 유효성 검사 에러 표시
                            if (data.errors) {
                                displayValidationErrors(data.errors);
                            }
                        }
                    })
                    .catch(error => {
                        console.error('AJAX 에러:', error);
                        alert('서버와의 통신 중 오류가 발생했습니다. 다시 시도해주세요.');
                    })
                    .finally(() => {
                        // 버튼 상태 복원
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    });
                });

                // 유효성 검사 에러 표시 함수
                function displayValidationErrors(errors) {
                    // 기존 에러 메시지 제거
                    document.querySelectorAll('.is-invalid').forEach(el => {
                        el.classList.remove('is-invalid');
                    });
                    document.querySelectorAll('.invalid-feedback').forEach(el => {
                        el.remove();
                    });

                    // 새 에러 메시지 표시
                    Object.keys(errors).forEach(field => {
                        const input = document.querySelector(`[name="${field}"]`);
                        if (input) {
                            input.classList.add('is-invalid');

                            const errorDiv = document.createElement('div');
                            errorDiv.className = 'invalid-feedback';
                            errorDiv.textContent = errors[field][0];

                            input.parentNode.appendChild(errorDiv);
                        }
                    });
                }

                // 실시간 유효성 검사
                const inputs = form.querySelectorAll('input, textarea, select');
                inputs.forEach(function(input) {
                    input.addEventListener('blur', function() {
                        // 기본 유효성 검사
                        if (this.hasAttribute('required') && !this.value.trim()) {
                            this.classList.add('is-invalid');
                        } else {
                            this.classList.remove('is-invalid');
                        }

                        // 전화번호 형식 검사
                        if (this.type === 'tel' && this.value.trim() && !/^010-\d{4}-\d{4}$/.test(this.value.trim())) {
                            this.classList.add('is-invalid');
                        } else if (this.type === 'tel') {
                            this.classList.remove('is-invalid');
                        }
                    });
                });
            }
        });
    </script>
@endsection
