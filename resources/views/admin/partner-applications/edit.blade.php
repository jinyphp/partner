@extends('jiny-partner::layouts.admin.sidebar')

@section('title', $title)

@section('content')
<div class="container-fluid">
    <!-- 헤더 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">{{ $title }}</h2>
                    <p class="text-muted mb-0">파트너 신청서를 수정합니다</p>
                </div>
                <div>
                    <a href="{{ route('admin.partner.applications.show', $item->id) }}" class="btn btn-outline-secondary me-2">
                        <i class="fe fe-eye me-2"></i>상세보기
                    </a>
                    <a href="{{ route('admin.partner.applications.index') }}" class="btn btn-outline-secondary">
                        <i class="fe fe-arrow-left me-2"></i>목록으로
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 수정 폼 -->
    <form action="{{ route('admin.partner.applications.update', $item->id) }}" method="POST" enctype="multipart/form-data" id="applicationForm">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-lg-8">
                {{-- 사용자 검색 (수정 모드에서는 선택된 사용자 표시) --}}
                @includeIf("jiny-partner::admin.partner-applications.partials.search_user")

                <!-- 개인 정보 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fe fe-user me-2"></i>개인 정보
                        </h5>
                    </div>
                    <div class="card-body">
                        {{-- <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="personal_name" class="form-label">이름 <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control @error('personal_info.name') is-invalid @enderror"
                                       name="personal_info[name]"
                                       id="personal_name"
                                       value="{{ old('personal_info.name', $item->personal_info['name'] ?? '') }}"
                                       required>
                                @error('personal_info.name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="personal_email" class="form-label">이메일 <span class="text-danger">*</span></label>
                                <input type="email"
                                       class="form-control @error('personal_info.email') is-invalid @enderror"
                                       name="personal_info[email]"
                                       id="personal_email"
                                       value="{{ old('personal_info.email', $item->personal_info['email'] ?? '') }}"
                                       required>
                                @error('personal_info.email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div> --}}

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="personal_phone" class="form-label">연락처</label>
                                <input type="tel"
                                       class="form-control @error('personal_info.phone') is-invalid @enderror"
                                       name="personal_info[phone]"
                                       id="personal_phone"
                                       value="{{ old('personal_info.phone', $item->personal_info['phone'] ?? '') }}">
                                @error('personal_info.phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="personal_birth_date" class="form-label">생년월일</label>
                                <input type="date"
                                       class="form-control @error('personal_info.birth_date') is-invalid @enderror"
                                       name="personal_info[birth_date]"
                                       id="personal_birth_date"
                                       value="{{ old('personal_info.birth_date', $item->personal_info['birth_date'] ?? '') }}">
                                @error('personal_info.birth_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="personal_address" class="form-label">주소</label>
                                <input type="text"
                                       class="form-control @error('personal_info.address') is-invalid @enderror"
                                       name="personal_info[address]"
                                       id="personal_address"
                                       value="{{ old('personal_info.address', $item->personal_info['address'] ?? '') }}">
                                @error('personal_info.address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="emergency_contact" class="form-label">긴급연락인</label>
                                <input type="text"
                                       class="form-control @error('personal_info.emergency_contact') is-invalid @enderror"
                                       name="personal_info[emergency_contact]"
                                       id="emergency_contact"
                                       value="{{ old('personal_info.emergency_contact', $item->personal_info['emergency_contact'] ?? '') }}">
                                @error('personal_info.emergency_contact')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="emergency_phone" class="form-label">긴급연락처</label>
                                <input type="tel"
                                       class="form-control @error('personal_info.emergency_phone') is-invalid @enderror"
                                       name="personal_info[emergency_phone]"
                                       id="emergency_phone"
                                       value="{{ old('personal_info.emergency_phone', $item->personal_info['emergency_phone'] ?? '') }}">
                                @error('personal_info.emergency_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 경력 정보 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fe fe-briefcase me-2"></i>경력 정보
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="total_years" class="form-label">총 경력 (년)</label>
                                <input type="number"
                                       class="form-control @error('experience_info.total_years') is-invalid @enderror"
                                       name="experience_info[total_years]"
                                       id="total_years"
                                       value="{{ old('experience_info.total_years', $item->experience_info['total_years'] ?? 0) }}"
                                       min="0" max="50">
                                @error('experience_info.total_years')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="education" class="form-label">학력</label>
                            <textarea class="form-control @error('experience_info.education') is-invalid @enderror"
                                      name="experience_info[education]"
                                      id="education"
                                      rows="3"
                                      placeholder="학력 정보를 입력하세요...">{{ old('experience_info.education', $item->experience_info['education'] ?? '') }}</textarea>
                            @error('experience_info.education')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="achievements" class="form-label">주요 성과</label>
                            <textarea class="form-control @error('experience_info.achievements') is-invalid @enderror"
                                      name="experience_info[achievements]"
                                      id="achievements"
                                      rows="4"
                                      placeholder="주요 성과나 업적을 입력하세요...">{{ old('experience_info.achievements', $item->experience_info['achievements'] ?? '') }}</textarea>
                            @error('experience_info.achievements')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- 추가 정보 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fe fe-info me-2"></i>추가 정보
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="expected_hourly_rate" class="form-label">희망 시급 (원)</label>
                                <input type="number"
                                       class="form-control @error('expected_hourly_rate') is-invalid @enderror"
                                       name="expected_hourly_rate"
                                       id="expected_hourly_rate"
                                       value="{{ old('expected_hourly_rate', $item->expected_hourly_rate) }}"
                                       min="0" step="1000">
                                @error('expected_hourly_rate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="motivation" class="form-label">지원 동기</label>
                            <textarea class="form-control @error('motivation') is-invalid @enderror"
                                      name="motivation"
                                      id="motivation"
                                      rows="4"
                                      placeholder="파트너 지원 동기를 입력하세요...">{{ old('motivation', $item->motivation) }}</textarea>
                            @error('motivation')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="goals" class="form-label">목표</label>
                            <textarea class="form-control @error('goals') is-invalid @enderror"
                                      name="goals"
                                      id="goals"
                                      rows="4"
                                      placeholder="향후 목표를 입력하세요...">{{ old('goals', $item->goals) }}</textarea>
                            @error('goals')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="admin_notes" class="form-label">관리자 메모</label>
                            <textarea class="form-control @error('admin_notes') is-invalid @enderror"
                                      name="admin_notes"
                                      id="admin_notes"
                                      rows="3"
                                      placeholder="관리자 메모를 입력하세요...">{{ old('admin_notes', $item->admin_notes) }}</textarea>
                            @error('admin_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- 첨부 파일 -->
                @if($item->documents && count($item->documents) > 0)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fe fe-paperclip me-2"></i>첨부 파일
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($item->documents as $document)
                            <div class="col-md-6 mb-3">
                                <div class="card border">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1">{{ $document['original_name'] ?? '파일' }}</h6>
                                                <small class="text-muted">
                                                    크기: {{ isset($document['size']) ? number_format($document['size'] / 1024, 1) . ' KB' : 'N/A' }}
                                                </small>
                                            </div>
                                            <div>
                                                @if(isset($document['path']))
                                                <a href="{{ Storage::disk('public')->url($document['path']) }}"
                                                   target="_blank"
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="fe fe-download"></i>
                                                </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- 오른쪽 사이드바 -->
            <div class="col-lg-4">
                <!-- 신청서 상태 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">신청서 상태</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="application_status" class="form-label">상태 <span class="text-danger">*</span></label>
                            <select name="application_status"
                                    id="application_status"
                                    class="form-control @error('application_status') is-invalid @enderror"
                                    required>
                                @foreach($statusOptions as $value => $label)
                                    <option value="{{ $value }}" {{ old('application_status', $item->application_status) == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('application_status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 현재 상태 정보 -->
                        <div class="alert alert-info">
                            <h6 class="alert-heading">현재 상태</h6>
                            <p class="mb-1">
                                <strong>상태:</strong>
                                @switch($item->application_status)
                                    @case('submitted')
                                        <span class="badge bg-primary">제출됨</span>
                                        @break
                                    @case('reviewing')
                                        <span class="badge bg-warning">검토중</span>
                                        @break
                                    @case('interview')
                                        <span class="badge bg-info">면접예정</span>
                                        @break
                                    @case('approved')
                                        <span class="badge bg-success">승인됨</span>
                                        @break
                                    @case('rejected')
                                        <span class="badge bg-danger">거부됨</span>
                                        @break
                                @endswitch
                            </p>
                            <hr>
                            <p class="mb-0">
                                <small class="text-muted">
                                    신청일: {{ $item->submitted_at ? $item->submitted_at->format('Y-m-d H:i') : 'N/A' }}<br>
                                    수정일: {{ $item->updated_at->format('Y-m-d H:i') }}
                                </small>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 추천 파트너 정보 -->
                @if($item->referrerPartner)
                <div class="card mb-4 border-primary">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">
                            <i class="fe fe-users me-2"></i>추천 파트너 정보
                        </h6>
                    </div>
                    <div class="card-body">
                        <!-- 읽기 전용 추천인 정보 -->
                        <div class="alert alert-info mb-3">
                            <h6 class="alert-heading">
                                <i class="fe fe-info me-1"></i>추천인 정보 (읽기 전용)
                            </h6>
                            <div class="row">
                                <div class="col-6">
                                    <strong>파트너 코드:</strong>
                                    <span class="badge bg-primary">{{ $item->referrerPartner->partner_code }}</span>
                                </div>
                                <div class="col-6">
                                    <strong>추천인 이름:</strong> {{ $item->referrerPartner->name }}
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-6">
                                    <strong>추천인 이메일:</strong> {{ $item->referrerPartner->email ?? 'N/A' }}
                                </div>
                                <div class="col-6">
                                    <strong>등급:</strong>
                                    @if($item->referrerPartner->partnerTier)
                                        <span class="badge bg-success">{{ $item->referrerPartner->partnerTier->tier_name }}</span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- 편집 가능한 추천 정보 -->
                        <div class="mb-3">
                            <label for="referrer_name" class="form-label">추천자 이름 (신청서상)</label>
                            <input type="text"
                                   class="form-control @error('referrer_name') is-invalid @enderror"
                                   name="referrer_name"
                                   id="referrer_name"
                                   value="{{ old('referrer_name', $item->referrer_name) }}">
                            @error('referrer_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="referrer_contact" class="form-label">추천자 연락처</label>
                            <input type="text"
                                   class="form-control @error('referrer_contact') is-invalid @enderror"
                                   name="referrer_contact"
                                   id="referrer_contact"
                                   value="{{ old('referrer_contact', $item->referrer_contact) }}">
                            @error('referrer_contact')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="mb-3">
                                    <label for="referrer_relationship" class="form-label">관계</label>
                                    <input type="text"
                                           class="form-control @error('referrer_relationship') is-invalid @enderror"
                                           name="referrer_relationship"
                                           id="referrer_relationship"
                                           value="{{ old('referrer_relationship', $item->referrer_relationship) }}"
                                           placeholder="예: 동료, 친구, 상사 등">
                                    @error('referrer_relationship')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label for="referral_source" class="form-label">추천 방법</label>
                                    <select class="form-control @error('referral_source') is-invalid @enderror"
                                            name="referral_source"
                                            id="referral_source">
                                        <option value="">선택하세요</option>
                                        <option value="online_link" {{ old('referral_source', $item->referral_source) == 'online_link' ? 'selected' : '' }}>온라인 링크</option>
                                        <option value="personal_recommendation" {{ old('referral_source', $item->referral_source) == 'personal_recommendation' ? 'selected' : '' }}>개인 추천</option>
                                        <option value="social_media" {{ old('referral_source', $item->referral_source) == 'social_media' ? 'selected' : '' }}>소셜 미디어</option>
                                        <option value="other" {{ old('referral_source', $item->referral_source) == 'other' ? 'selected' : '' }}>기타</option>
                                    </select>
                                    @error('referral_source')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="meeting_date" class="form-label">만남 일자</label>
                            <input type="date"
                                   class="form-control @error('meeting_date') is-invalid @enderror"
                                   name="meeting_date"
                                   id="meeting_date"
                                   value="{{ old('meeting_date', $item->meeting_date) }}">
                            @error('meeting_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="introduction_method" class="form-label">소개 방법</label>
                            <textarea class="form-control @error('introduction_method') is-invalid @enderror"
                                      name="introduction_method"
                                      id="introduction_method"
                                      rows="3"
                                      placeholder="어떻게 소개받았는지 설명해주세요...">{{ old('introduction_method', $item->introduction_method) }}</textarea>
                            @error('introduction_method')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                @else
                <!-- 추천자 없음 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fe fe-user-plus me-2"></i>추천자 정보
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-light text-center">
                            <i class="fe fe-user-x fs-1 text-muted mb-2 d-block"></i>
                            <h6 class="text-muted">직접 신청</h6>
                            <p class="text-muted small mb-0">이 지원자는 추천인 없이 직접 신청했습니다.</p>
                        </div>

                        <!-- 수동 추천자 정보 입력 (관리자용) -->
                        <div class="mt-3">
                            <h6 class="text-muted small">관리자 추가 정보</h6>
                            <div class="mb-3">
                                <label for="referrer_name" class="form-label">추천자 이름</label>
                                <input type="text"
                                       class="form-control @error('referrer_name') is-invalid @enderror"
                                       name="referrer_name"
                                       id="referrer_name"
                                       value="{{ old('referrer_name', $item->referrer_name) }}"
                                       placeholder="수동으로 추천자 정보를 입력할 수 있습니다">
                                @error('referrer_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="referrer_contact" class="form-label">추천자 연락처</label>
                                <input type="text"
                                       class="form-control @error('referrer_contact') is-invalid @enderror"
                                       name="referrer_contact"
                                       id="referrer_contact"
                                       value="{{ old('referrer_contact', $item->referrer_contact) }}">
                                @error('referrer_contact')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- 수정 -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fe fe-save me-1"></i>수정 저장
                            </button>
                            <a href="{{ route('admin.partner.applications.show', $item->id) }}" class="btn btn-secondary">
                                <i class="fe fe-x me-1"></i>취소
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 기존 데이터로 숨겨진 필드 채우기
    @if($item->user_id)
        document.getElementById('user_id').value = '{{ $item->user_id }}';
    @endif
    @if($item->user_uuid)
        document.getElementById('user_uuid').value = '{{ $item->user_uuid }}';
    @endif
    @if($item->shard_number)
        document.getElementById('shard_number').value = '{{ $item->shard_number }}';
    @endif

    // 선택된 사용자 정보 표시
    showSelectedUserFromOldInput();

    // 폼 제출 전 유효성 검사
    document.getElementById('applicationForm').addEventListener('submit', function(e) {
        const userId = document.getElementById('user_id').value;
        const personalName = document.getElementById('personal_name').value.trim();
        const personalEmail = document.getElementById('personal_email').value.trim();

        if (!userId) {
            e.preventDefault();
            alert('사용자를 먼저 검색하고 선택해주세요.');
            return false;
        }

        if (!personalName || !personalEmail) {
            e.preventDefault();
            alert('이름과 이메일은 필수 입력 항목입니다.');
            return false;
        }
    });
});
</script>
@endpush
