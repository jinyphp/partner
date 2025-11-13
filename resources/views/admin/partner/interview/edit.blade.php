@extends('jiny-partner::layouts.admin.sidebar')

@section('title', '면접 정보 수정')

@section('content')
<div class="container-fluid">
    <!-- 헤더 -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">면접 정보 수정</h1>
            <p class="text-muted mb-0">{{ $interview->name }}님의 면접 정보를 수정합니다</p>
        </div>
        <div>
            <a href="{{ route('admin.partner.interview.show', $interview->id) }}" class="btn btn-secondary">
                <i class="fe fe-arrow-left me-1"></i>상세보기
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <form method="POST" action="{{ route('admin.partner.interview.update', $interview->id) }}">
                @csrf
                @method('PUT')

                <!-- 지원자 정보 (읽기 전용) -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fe fe-user me-2"></i>지원자 정보
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-light">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>이름:</strong> {{ $interview->name }}<br>
                                    <strong>이메일:</strong> {{ $interview->email }}
                                </div>
                                <div class="col-md-6">
                                    <strong>신청서:</strong> #{{ $interview->application_id }}<br>
                                    @if($interview->referrer_code)
                                        <strong>추천인:</strong> {{ $interview->referrer_name }}
                                        <span class="badge bg-primary">{{ $interview->referrer_code }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
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
                            @if(isset($editableFields['interviewer_id']))
                                <div class="col-md-6 mb-3">
                                    <label for="interviewer_id" class="form-label">면접관</label>
                                    <select name="interviewer_id" id="interviewer_id" class="form-select @error('interviewer_id') is-invalid @enderror">
                                        <option value="">면접관을 선택해주세요</option>
                                        @foreach($interviewers as $interviewer)
                                            <option value="{{ $interviewer->id }}"
                                                {{ (old('interviewer_id') ?? $interview->interviewer_id) == $interviewer->id ? 'selected' : '' }}>
                                                {{ $interviewer->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('interviewer_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @else
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">면접관</label>
                                    <p class="form-control-plaintext">{{ $interview->interviewer->name ?? '미배정' }}</p>
                                </div>
                            @endif

                            @if(isset($editableFields['interview_type']))
                                <div class="col-md-6 mb-3">
                                    <label for="interview_type" class="form-label">면접 유형</label>
                                    <select name="interview_type" id="interview_type" class="form-select @error('interview_type') is-invalid @enderror">
                                        <option value="video" {{ (old('interview_type') ?? $interview->interview_type) === 'video' ? 'selected' : '' }}>화상면접</option>
                                        <option value="phone" {{ (old('interview_type') ?? $interview->interview_type) === 'phone' ? 'selected' : '' }}>전화면접</option>
                                        <option value="in_person" {{ (old('interview_type') ?? $interview->interview_type) === 'in_person' ? 'selected' : '' }}>대면면접</option>
                                        <option value="written" {{ (old('interview_type') ?? $interview->interview_type) === 'written' ? 'selected' : '' }}>서면면접</option>
                                    </select>
                                    @error('interview_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @else
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">면접 유형</label>
                                    <p class="form-control-plaintext">{{ $interview->type_label }}</p>
                                </div>
                            @endif

                            @if(isset($editableFields['interview_round']))
                                <div class="col-md-6 mb-3">
                                    <label for="interview_round" class="form-label">면접 차수</label>
                                    <select name="interview_round" id="interview_round" class="form-select @error('interview_round') is-invalid @enderror">
                                        <option value="first" {{ (old('interview_round') ?? $interview->interview_round) === 'first' ? 'selected' : '' }}>1차면접</option>
                                        <option value="second" {{ (old('interview_round') ?? $interview->interview_round) === 'second' ? 'selected' : '' }}>2차면접</option>
                                        <option value="final" {{ (old('interview_round') ?? $interview->interview_round) === 'final' ? 'selected' : '' }}>최종면접</option>
                                    </select>
                                    @error('interview_round')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @else
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">면접 차수</label>
                                    <p class="form-control-plaintext">{{ $interview->round_label }}</p>
                                </div>
                            @endif

                            @if(isset($editableFields['scheduled_at']))
                                <div class="col-md-6 mb-3">
                                    <label for="scheduled_at" class="form-label">면접 일시</label>
                                    <input type="datetime-local" name="scheduled_at" id="scheduled_at"
                                           class="form-control @error('scheduled_at') is-invalid @enderror"
                                           value="{{ old('scheduled_at', $interview->scheduled_at?->format('Y-m-d\TH:i')) }}">
                                    @error('scheduled_at')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    @if($interview->interview_status === 'scheduled')
                                        <div class="form-text">일정 변경 시 면접 상태가 '재일정'으로 변경됩니다.</div>
                                    @endif
                                </div>
                            @else
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">면접 일시</label>
                                    <p class="form-control-plaintext">
                                        {{ $interview->scheduled_at?->format('Y-m-d H:i') ?? '미정' }}
                                    </p>
                                </div>
                            @endif

                            @if(isset($editableFields['duration_minutes']))
                                <div class="col-md-6 mb-3">
                                    <label for="duration_minutes" class="form-label">면접 시간 (분)</label>
                                    <input type="number" name="duration_minutes" id="duration_minutes"
                                           class="form-control @error('duration_minutes') is-invalid @enderror"
                                           value="{{ old('duration_minutes', $interview->duration_minutes) }}" min="15" max="240">
                                    @error('duration_minutes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @else
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">면접 시간</label>
                                    <p class="form-control-plaintext">{{ $interview->duration_minutes ?? 60 }}분</p>
                                </div>
                            @endif

                            <!-- 면접 상태 표시 -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">면접 상태</label>
                                <p class="form-control-plaintext">
                                    @php
                                        $statusColor = match($interview->interview_status) {
                                            'scheduled' => 'warning',
                                            'in_progress' => 'info',
                                            'completed' => 'success',
                                            'cancelled' => 'danger',
                                            'no_show' => 'dark',
                                            default => 'secondary'
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $statusColor }}">{{ $interview->status_label }}</span>
                                </p>
                            </div>
                        </div>

                        @if(isset($editableFields['scheduled_at']) && $interview->interview_status === 'scheduled')
                            <div class="row">
                                <div class="col-12">
                                    <label for="reschedule_reason" class="form-label">일정 변경 사유 (선택사항)</label>
                                    <textarea name="reschedule_reason" id="reschedule_reason" rows="2"
                                              class="form-control"
                                              placeholder="일정 변경 사유를 입력하면 로그에 기록됩니다">{{ old('reschedule_reason') }}</textarea>
                                </div>
                            </div>
                        @endif
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
                        <div class="row">
                            @if(isset($editableFields['meeting_location']))
                                <div class="col-md-12 mb-3">
                                    <label for="meeting_location" class="form-label">면접 장소</label>
                                    <input type="text" name="meeting_location" id="meeting_location"
                                           class="form-control @error('meeting_location') is-invalid @enderror"
                                           value="{{ old('meeting_location', $interview->meeting_location) }}"
                                           placeholder="예: 서울시 강남구 테헤란로 123, ABC빌딩 5층">
                                    @error('meeting_location')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @else
                                @if($interview->meeting_location)
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">면접 장소</label>
                                        <p class="form-control-plaintext">{{ $interview->meeting_location }}</p>
                                    </div>
                                @endif
                            @endif

                            @if(isset($editableFields['meeting_url']))
                                <div class="col-md-8 mb-3">
                                    <label for="meeting_url" class="form-label">회의 URL</label>
                                    <input type="url" name="meeting_url" id="meeting_url"
                                           class="form-control @error('meeting_url') is-invalid @enderror"
                                           value="{{ old('meeting_url', $interview->meeting_url) }}"
                                           placeholder="예: https://zoom.us/j/1234567890">
                                    @error('meeting_url')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @else
                                @if($interview->meeting_url)
                                    <div class="col-md-8 mb-3">
                                        <label class="form-label">회의 URL</label>
                                        <p class="form-control-plaintext">
                                            <a href="{{ $interview->meeting_url }}" target="_blank">{{ $interview->meeting_url }}</a>
                                        </p>
                                    </div>
                                @endif
                            @endif

                            @if(isset($editableFields['meeting_password']))
                                <div class="col-md-4 mb-3">
                                    <label for="meeting_password" class="form-label">회의 비밀번호</label>
                                    <input type="text" name="meeting_password" id="meeting_password"
                                           class="form-control @error('meeting_password') is-invalid @enderror"
                                           value="{{ old('meeting_password', $interview->meeting_password) }}"
                                           placeholder="비밀번호">
                                    @error('meeting_password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @else
                                @if($interview->meeting_password)
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">회의 비밀번호</label>
                                        <p class="form-control-plaintext">{{ $interview->meeting_password }}</p>
                                    </div>
                                @endif
                            @endif
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
                            @if(isset($editableFields['preparation_notes']))
                                <div class="col-md-12 mb-3">
                                    <label for="preparation_notes" class="form-label">준비 사항</label>
                                    <textarea name="preparation_notes" id="preparation_notes" rows="3"
                                              class="form-control @error('preparation_notes') is-invalid @enderror"
                                              placeholder="면접 전 준비해야 할 사항이나 주의사항을 입력해주세요">{{ old('preparation_notes', $interview->preparation_notes) }}</textarea>
                                    @error('preparation_notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @else
                                @if($interview->preparation_notes)
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">준비 사항</label>
                                        <p class="form-control-plaintext">{!! nl2br(e($interview->preparation_notes)) !!}</p>
                                    </div>
                                @endif
                            @endif

                            @if(isset($editableFields['interviewer_notes']))
                                <div class="col-md-12 mb-3">
                                    <label for="interviewer_notes" class="form-label">면접관 메모</label>
                                    <textarea name="interviewer_notes" id="interviewer_notes" rows="3"
                                              class="form-control @error('interviewer_notes') is-invalid @enderror"
                                              placeholder="면접관을 위한 메모나 특별 지시사항을 입력해주세요">{{ old('interviewer_notes', $interview->interviewer_notes) }}</textarea>
                                    @error('interviewer_notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @else
                                @if($interview->interviewer_notes)
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">면접관 메모</label>
                                        <p class="form-control-plaintext">{!! nl2br(e($interview->interviewer_notes)) !!}</p>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>

                <!-- 제출 버튼 -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.partner.interview.show', $interview->id) }}" class="btn btn-secondary">취소</a>
                            @if(count($editableFields) > 0)
                                <button type="submit" class="btn btn-primary">
                                    <i class="fe fe-save me-1"></i>변경사항 저장
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- 사이드바 -->
        <div class="col-md-4">
            <!-- 수정 가능한 필드 안내 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fe fe-info me-2"></i>수정 가능한 항목
                    </h6>
                </div>
                <div class="card-body">
                    @if(count($editableFields) > 0)
                        <div class="small">
                            <p class="text-muted mb-2">현재 면접 상태({{ $interview->status_label }})에서 수정 가능한 항목:</p>
                            <ul class="list-unstyled mb-0">
                                @foreach($editableFields as $field => $label)
                                    <li><i class="fe fe-check text-success me-1"></i>{{ $label }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <div class="text-muted small">
                            <p class="mb-0">현재 면접 상태에서는 수정할 수 있는 항목이 없습니다.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- 면접 상태별 안내 -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fe fe-help-circle me-2"></i>면접 상태별 수정 규칙
                    </h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <div class="mb-3">
                            <strong>예정/재일정:</strong><br>
                            <span class="text-muted">모든 항목 수정 가능</span>
                        </div>
                        <div class="mb-3">
                            <strong>진행중:</strong><br>
                            <span class="text-muted">면접 시간, 장소, URL, 메모만 수정 가능</span>
                        </div>
                        <div class="mb-3">
                            <strong>취소/불참:</strong><br>
                            <span class="text-muted">면접관 메모만 수정 가능</span>
                        </div>
                        <div class="mb-0">
                            <strong>완료:</strong><br>
                            <span class="text-muted">수정 불가 (결과는 별도 기능 이용)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
