@extends('jiny-partner::layouts.admin.sidebar')

@section('title', '새 면접 예약')

@section('content')
<div class="container-fluid">
    <!-- 헤더 -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">새 면접 예약</h1>
            <p class="text-muted mb-0">파트너 지원자의 면접 일정을 생성합니다</p>
        </div>
        <div>
            <a href="{{ route('admin.partner.interview.index') }}" class="btn btn-secondary">
                <i class="fe fe-arrow-left me-1"></i>목록
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <form method="POST" action="{{ route('admin.partner.interview.store') }}">
                @csrf

                <!-- 신청서 선택 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fe fe-user me-2"></i>지원자 선택
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($application)
                            <!-- 특정 신청서가 지정된 경우 -->
                            <input type="hidden" name="application_id" value="{{ $application->id }}">
                            <div class="alert alert-info">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>지원자:</strong> {{ $application->personal_info['name'] ?? $application->user->name ?? '알 수 없음' }}<br>
                                        <strong>이메일:</strong> {{ $application->personal_info['email'] ?? $application->user->email ?? '알 수 없음' }}
                                    </div>
                                    <div class="col-md-6">
                                        <strong>신청 상태:</strong>
                                        <span class="badge bg-primary">{{ $application->application_status }}</span><br>
                                        @if($application->referrerPartner)
                                            <strong>추천인:</strong> {{ $application->referrerPartner->name }}
                                            <span class="badge bg-secondary">{{ $application->referrerPartner->partner_code }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @else
                            <!-- 신청서 선택 -->
                            <div class="mb-3">
                                <label for="application_id" class="form-label">신청서 선택 <span class="text-danger">*</span></label>
                                <select name="application_id" id="application_id" class="form-select @error('application_id') is-invalid @enderror" required>
                                    <option value="">신청서를 선택해주세요</option>
                                    @foreach($availableApplications as $app)
                                        <option value="{{ $app->id }}" {{ old('application_id') == $app->id ? 'selected' : '' }}>
                                            {{ $app->personal_info['name'] ?? $app->user->name ?? '알 수 없음' }}
                                            ({{ $app->personal_info['email'] ?? $app->user->email ?? '알 수 없음' }}) -
                                            {{ $app->application_status }}
                                            @if($app->referrerPartner)
                                                - 추천: {{ $app->referrerPartner->partner_code }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('application_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif
                    </div>
                </div>

                <!-- 면접 정보 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fe fe-calendar me-2"></i>면접 정보
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="interview_type" class="form-label">면접 유형 <span class="text-danger">*</span></label>
                                <select name="interview_type" id="interview_type" class="form-select @error('interview_type') is-invalid @enderror" required>
                                    <option value="video" {{ old('interview_type', $defaultSettings['interview_type']) === 'video' ? 'selected' : '' }}>화상면접</option>
                                    <option value="phone" {{ old('interview_type') === 'phone' ? 'selected' : '' }}>전화면접</option>
                                    <option value="in_person" {{ old('interview_type') === 'in_person' ? 'selected' : '' }}>대면면접</option>
                                    <option value="written" {{ old('interview_type') === 'written' ? 'selected' : '' }}>서면면접</option>
                                </select>
                                @error('interview_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="interview_round" class="form-label">면접 차수 <span class="text-danger">*</span></label>
                                <select name="interview_round" id="interview_round" class="form-select @error('interview_round') is-invalid @enderror" required>
                                    <option value="first" {{ old('interview_round', $defaultSettings['interview_round']) === 'first' ? 'selected' : '' }}>1차면접</option>
                                    <option value="second" {{ old('interview_round') === 'second' ? 'selected' : '' }}>2차면접</option>
                                    <option value="final" {{ old('interview_round') === 'final' ? 'selected' : '' }}>최종면접</option>
                                </select>
                                @error('interview_round')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="scheduled_at" class="form-label">면접 일시 <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="scheduled_at" id="scheduled_at"
                                       class="form-control @error('scheduled_at') is-invalid @enderror"
                                       value="{{ old('scheduled_at', now()->addDays(1)->format('Y-m-d\TH:i')) }}" required>
                                @error('scheduled_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="duration_minutes" class="form-label">면접 시간 (분)</label>
                                <input type="number" name="duration_minutes" id="duration_minutes"
                                       class="form-control @error('duration_minutes') is-invalid @enderror"
                                       value="{{ old('duration_minutes', $defaultSettings['duration']) }}" min="15" max="240">
                                @error('duration_minutes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="interviewer_id" class="form-label">면접관 <span class="text-danger">*</span></label>
                                <select name="interviewer_id" id="interviewer_id" class="form-select @error('interviewer_id') is-invalid @enderror" required>
                                    <option value="">면접관을 선택해주세요</option>
                                    @foreach($interviewers as $interviewer)
                                        <option value="{{ $interviewer->id }}" {{ old('interviewer_id') == $interviewer->id ? 'selected' : '' }}>
                                            {{ $interviewer->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('interviewer_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 면접 장소/연결 정보 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fe fe-map-pin me-2"></i>면접 장소/연결 정보
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="meeting-fields">
                            <div class="row">
                                <div class="col-md-12 mb-3" id="meeting-location-field">
                                    <label for="meeting_location" class="form-label">면접 장소</label>
                                    <input type="text" name="meeting_location" id="meeting_location"
                                           class="form-control @error('meeting_location') is-invalid @enderror"
                                           value="{{ old('meeting_location') }}" placeholder="예: 서울시 강남구 테헤란로 123, ABC빌딩 5층">
                                    @error('meeting_location')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-8 mb-3" id="meeting-url-field" style="display: none;">
                                    <label for="meeting_url" class="form-label">회의 URL</label>
                                    <input type="url" name="meeting_url" id="meeting_url"
                                           class="form-control @error('meeting_url') is-invalid @enderror"
                                           value="{{ old('meeting_url') }}" placeholder="예: https://zoom.us/j/1234567890">
                                    @error('meeting_url')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4 mb-3" id="meeting-password-field" style="display: none;">
                                    <label for="meeting_password" class="form-label">회의 비밀번호</label>
                                    <input type="text" name="meeting_password" id="meeting_password"
                                           class="form-control @error('meeting_password') is-invalid @enderror"
                                           value="{{ old('meeting_password') }}" placeholder="비밀번호">
                                    @error('meeting_password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 추가 정보 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fe fe-file-text me-2"></i>추가 정보
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="preparation_notes" class="form-label">준비 사항</label>
                                <textarea name="preparation_notes" id="preparation_notes" rows="3"
                                          class="form-control @error('preparation_notes') is-invalid @enderror"
                                          placeholder="면접 전 준비해야 할 사항이나 주의사항을 입력해주세요">{{ old('preparation_notes') }}</textarea>
                                @error('preparation_notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="interviewer_notes" class="form-label">면접관 메모</label>
                                <textarea name="interviewer_notes" id="interviewer_notes" rows="3"
                                          class="form-control @error('interviewer_notes') is-invalid @enderror"
                                          placeholder="면접관을 위한 메모나 특별 지시사항을 입력해주세요">{{ old('interviewer_notes') }}</textarea>
                                @error('interviewer_notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 제출 버튼 -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.partner.interview.index') }}" class="btn btn-secondary">취소</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fe fe-save me-1"></i>면접 예약 생성
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- 사이드바 -->
        <div class="col-md-4">
            <!-- 도움말 -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fe fe-help-circle me-2"></i>면접 예약 가이드
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <h6>면접 유형별 안내</h6>
                        <ul>
                            <li><strong>화상면접:</strong> 온라인 회의 URL 필요</li>
                            <li><strong>전화면접:</strong> 연락처 확인</li>
                            <li><strong>대면면접:</strong> 정확한 장소 입력</li>
                            <li><strong>서면면접:</strong> 질문지 준비</li>
                        </ul>

                        <h6 class="mt-3">권장 면접 시간</h6>
                        <ul>
                            <li>1차면접: 30-60분</li>
                            <li>2차면접: 60-90분</li>
                            <li>최종면접: 60-120분</li>
                        </ul>

                        <h6 class="mt-3">체크리스트</h6>
                        <ul class="list-unstyled">
                            <li><i class="fe fe-check text-success me-1"></i>지원자 연락처 확인</li>
                            <li><i class="fe fe-check text-success me-1"></i>면접관 일정 조율</li>
                            <li><i class="fe fe-check text-success me-1"></i>회의실/URL 준비</li>
                            <li><i class="fe fe-check text-success me-1"></i>질문지 준비</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const interviewType = document.getElementById('interview_type');
    const meetingLocation = document.getElementById('meeting-location-field');
    const meetingUrl = document.getElementById('meeting-url-field');
    const meetingPassword = document.getElementById('meeting-password-field');

    function toggleMeetingFields() {
        const type = interviewType.value;

        // 모든 필드 숨기기
        meetingLocation.style.display = 'none';
        meetingUrl.style.display = 'none';
        meetingPassword.style.display = 'none';

        // 면접 유형에 따라 필요한 필드만 표시
        switch(type) {
            case 'in_person':
                meetingLocation.style.display = 'block';
                break;
            case 'video':
                meetingUrl.style.display = 'block';
                meetingPassword.style.display = 'block';
                break;
            case 'phone':
                // 전화면접은 별도 필드 불필요
                break;
            case 'written':
                // 서면면접은 별도 필드 불필요
                break;
        }
    }

    // 초기 상태 설정
    toggleMeetingFields();

    // 면접 유형 변경 시 필드 토글
    interviewType.addEventListener('change', toggleMeetingFields);
});
</script>
@endsection
