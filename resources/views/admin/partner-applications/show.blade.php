@extends('jiny-partner::layouts.admin.sidebar')

@section('title', $title . ' 상세보기')

@section('content')
<div class="container-fluid">

    <!-- 헤더 -->
    <section class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">{{ $title }} 상세보기</h2>
                    <p class="text-muted mb-1">
                        지원자: {{ $item->personal_info['name'] ?? $item->user->name ?? 'Unknown' }}
                    </p>
                    {{-- @php
                        $headerEmail = null;
                        // 이메일 정보 확인 (4단계 검색)
                        if (!empty($item->personal_info['email'])) {
                            $headerEmail = $item->personal_info['email'];
                        } elseif (isset($item->user) && !empty($item->user->email)) {
                            $headerEmail = $item->user->email;
                        } elseif (!empty($item->user_uuid)) {
                            $user = \App\Models\User::where('uuid', $item->user_uuid)->first();
                            if ($user && !empty($user->email)) {
                                $headerEmail = $user->email;
                            }
                        } elseif (!empty($item->email)) {
                            $headerEmail = $item->email;
                        }
                    @endphp --}}
                    {{-- @if($headerEmail)
                        <p class="text-muted mb-0">
                            <i class="fe fe-mail me-1"></i>{{ $headerEmail }}
                            <a href="mailto:{{ $headerEmail }}" class="btn btn-sm btn-link p-0 ms-2">
                                <i class="fe fe-external-link"></i>
                            </a>
                        </p>
                    @else
                        <p class="text-danger mb-0">
                            <i class="fe fe-alert-triangle me-1"></i>이메일 정보가 없습니다
                        </p>
                    @endif --}}
                </div>
                <div>
                    <a href="{{ route('admin.partner.applications.edit', $item->id) }}" class="btn btn-primary me-2">
                        <i class="fe fe-edit me-2"></i>수정
                    </a>
                    <button type="button" class="btn btn-danger me-2" onclick="confirmDelete({{ $item->id }}, '{{ $item->personal_info['name'] ?? $item->user->name ?? 'Unknown' }}')">
                        <i class="fe fe-trash-2 me-2"></i>삭제
                    </button>
                    <a href="{{ route('admin.partner.applications.index') }}" class="btn btn-outline-secondary me-2">
                        <i class="fe fe-arrow-left me-2"></i>목록으로
                    </a>
                    <a href="{{ route('admin.partner.approval.show', $item->id) }}" class="btn btn-outline-primary">
                        <i class="fe fe-settings me-2"></i>승인 관리
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- 지원서 상태 -->
    <section class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <h6 class="text-muted">현재 상태</h6>
                            @if($item->application_status === 'submitted')
                                <span class="badge bg-primary fs-6">제출됨</span>
                            @elseif($item->application_status === 'reviewing')
                                <span class="badge bg-warning fs-6">검토 중</span>
                            @elseif($item->application_status === 'interview')
                                <span class="badge bg-info fs-6">면접 예정</span>
                            @elseif($item->application_status === 'approved')
                                <span class="badge bg-success fs-6">승인됨</span>
                            @elseif($item->application_status === 'rejected')
                                <span class="badge bg-danger fs-6">반려됨</span>
                            @elseif($item->application_status === 'reapplied')
                                <span class="badge bg-secondary fs-6">재신청</span>
                            @else
                                <span class="badge bg-light text-dark fs-6">{{ $item->application_status }}</span>
                            @endif
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-muted">지원일</h6>
                            <p class="mb-0">{{ $item->created_at->format('Y년 m월 d일') }}</p>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-muted">완성도</h6>
                            <div class="d-flex align-items-center">
                                <div class="progress me-2" style="width: 100px; height: 20px;">
                                    <div class="progress-bar" style="width: {{ $completenessScore }}%"></div>
                                </div>
                                <span class="fw-bold">{{ $completenessScore }}%</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-muted">추천인</h6>
                            @if($item->referrerPartner)
                                <p class="mb-0">
                                    <strong class="text-primary">{{ $item->referrerPartner->partner_code }}</strong><br>
                                    <small class="text-muted">{{ $item->referrerPartner->name }}</small>
                                </p>
                            @else
                                <p class="mb-0 text-muted">직접 신청</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="row">
        <!-- 좌측 메인 컨텐츠 (col-8) -->
        <div class="col-8">

            <!-- 개인정보 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">개인정보</h5>
                </div>
                <div class="card-body">
                    @if($item->personal_info)
                        <div class="row">
                            <div class="col-6 mb-3">
                                <strong>이름:</strong> {{ $item->personal_info['name'] ?? 'N/A' }}
                            </div>
                            <div class="col-6 mb-3">
                                <strong>연락처:</strong> {{ $item->personal_info['phone'] ?? 'N/A' }}
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <strong>이메일:</strong>
                                @php
                                    $email = null;
                                    // 4단계 이메일 검색
                                    if (!empty($item->personal_info['email'])) {
                                        $email = $item->personal_info['email'];
                                    } elseif (isset($item->user) && !empty($item->user->email)) {
                                        $email = $item->user->email;
                                    } elseif (!empty($item->user_uuid)) {
                                        $user = \App\Models\User::where('uuid', $item->user_uuid)->first();
                                        if ($user && !empty($user->email)) {
                                            $email = $user->email;
                                        }
                                    } elseif (!empty($item->email)) {
                                        $email = $item->email;
                                    }
                                @endphp

                                @if($email)
                                    <span class="text-primary">{{ $email }}</span>
                                    {{-- <a href="mailto:{{ $email }}" class="btn btn-sm btn-outline-primary ms-2">
                                        <i class="fe fe-mail"></i>
                                    </a> --}}
                                @else
                                    <span class="text-muted">이메일 정보 없음</span>
                                    <small class="text-danger ms-2">(확인 필요)</small>
                                @endif
                            </div>
                        </div>
                        <div class="mt-2">
                            <strong>주소:</strong> {{ $item->personal_info['address'] ?? 'N/A' }}
                        </div>
                    @else
                        <p class="text-muted">개인정보가 입력되지 않았습니다.</p>
                    @endif
                </div>
            </div>

            <!-- 경력정보 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">경력정보</h5>
                </div>
                <div class="card-body">
                    @if($item->experience_info)
                        <div class="mb-3">
                            <strong>총 경력:</strong> {{ $item->experience_info['total_years'] ?? 0 }}년
                        </div>
                        @if(isset($item->experience_info['career_summary']))
                            <div class="mb-3">
                                <strong>경력 요약:</strong>
                                <p class="mt-1">{{ $item->experience_info['career_summary'] }}</p>
                            </div>
                        @endif
                        @if(isset($item->experience_info['bio']))
                            <div class="mb-3">
                                <strong>자기소개:</strong>
                                <p class="mt-1">{{ $item->experience_info['bio'] }}</p>
                            </div>
                        @endif
                        @if(isset($item->experience_info['portfolio_url']))
                            <div class="mb-3">
                                <strong>포트폴리오:</strong>
                                <a href="{{ $item->experience_info['portfolio_url'] }}" target="_blank" class="ms-2">
                                    {{ $item->experience_info['portfolio_url'] }}
                                </a>
                            </div>
                        @endif
                    @else
                        <p class="text-muted">경력정보가 입력되지 않았습니다.</p>
                    @endif
                </div>
            </div>

            <!-- 기술정보 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">기술정보</h5>
                </div>
                <div class="card-body">
                    @if($item->skills_info)
                        @if(isset($item->skills_info['skills']) && count($item->skills_info['skills']) > 0)
                            <div class="mb-3">
                                <strong>보유 기술:</strong>
                                <div class="mt-1">
                                    @foreach($item->skills_info['skills'] as $skill)
                                        <span class="badge bg-primary me-1 mb-1">{{ $skill }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @if(isset($item->skills_info['certifications']) && count($item->skills_info['certifications']) > 0)
                            <div class="mb-3">
                                <strong>자격증:</strong>
                                <div class="mt-1">
                                    @foreach($item->skills_info['certifications'] as $cert)
                                        <span class="badge bg-success me-1 mb-1">{{ $cert }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @if(isset($item->skills_info['languages']) && count($item->skills_info['languages']) > 0)
                            <div class="mb-3">
                                <strong>언어:</strong>
                                <div class="mt-1">
                                    @foreach($item->skills_info['languages'] as $lang)
                                        <span class="badge bg-info me-1 mb-1">{{ $lang }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @else
                        <p class="text-muted">기술정보가 입력되지 않았습니다.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- 우측 사이드바 (col-4) -->
        <div class="col-4">

            <!-- 추천인 정보 (추천인이 있는 경우만 표시) -->
            @if($item->referrerPartner)
            <div class="card mb-4">
                <div class="card-header text-white">
                    <h5 class="mb-0">
                        <i class="fe fe-users me-2"></i>추천인 정보
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>파트너 코드:</strong>
                        <br><span class="badge bg-primary fs-6">{{ $item->referrerPartner->partner_code }}</span>
                    </div>
                    <div class="mb-3">
                        <strong>추천인 이름:</strong>
                        <br>{{ $item->referrerPartner->name }}
                    </div>
                    <div class="mb-3">
                        <strong>추천인 이메일:</strong>
                        <br>
                        @php
                            $referrerEmail = null;
                            // 추천인 이메일 확인
                            if (!empty($item->referrerPartner->email)) {
                                $referrerEmail = $item->referrerPartner->email;
                            } elseif (!empty($item->referrerPartner->user_uuid)) {
                                $referrerUser = \App\Models\User::where('uuid', $item->referrerPartner->user_uuid)->first();
                                if ($referrerUser && !empty($referrerUser->email)) {
                                    $referrerEmail = $referrerUser->email;
                                }
                            }
                        @endphp

                        @if($referrerEmail)
                            <span class="text-success">{{ $referrerEmail }}</span>
                            {{-- <a href="mailto:{{ $referrerEmail }}" class="btn btn-sm btn-outline-success ms-1">
                                <i class="fe fe-mail"></i>
                            </a> --}}
                        @else
                            <small class="text-muted">이메일 정보 없음</small>
                        @endif
                    </div>
                    @if($item->referrerPartner->partnerTier)
                    <div class="mb-3">
                        <strong>추천인 등급:</strong>
                        <br><span class="badge bg-success">{{ $item->referrerPartner->partnerTier->tier_name }}</span>
                    </div>
                    @endif
                    @if(isset($item->referral_source))
                    <div class="mb-3">
                        <strong>추천 방법:</strong>
                        <br><span class="text-capitalize">{{ str_replace('_', ' ', $item->referral_source) }}</span>
                    </div>
                    @endif
                    @if(isset($item->meeting_date))
                    <div class="mb-3">
                        <strong>만남 일자:</strong>
                        <br>{{ \Carbon\Carbon::parse($item->meeting_date)->format('Y년 m월 d일') }}
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- 지원서 진행률 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0 fw-bold">
                        <i class="fe fe-activity me-2"></i>지원서 진행률
                    </h6>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <div class="h2 mb-1">{{ $completenessScore }}%</div>
                        <small class="text-muted">완성도</small>
                    </div>
                    <div class="progress mb-3" style="height: 10px;">
                        <div class="progress-bar
                            @if($completenessScore >= 80) bg-success
                            @elseif($completenessScore >= 60) bg-warning
                            @else bg-danger
                            @endif"
                            style="width: {{ $completenessScore }}%">
                        </div>
                    </div>
                    <small class="text-muted">
                        지원일: {{ $item->created_at->format('Y-m-d') }}
                    </small>
                </div>
            </div>

            <!-- 관리자 메모 -->
            @if($item->admin_notes)
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0 fw-bold">
                        <i class="fe fe-message-square me-2"></i>관리자 메모
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-0 small">{{ $item->admin_notes }}</p>
                </div>
            </div>
            @endif

            <!-- 처리 내역 -->
            @if($item->approval_date || $item->rejection_date)
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0 fw-bold">
                        <i class="fe fe-check-circle me-2"></i>처리 내역
                    </h6>
                </div>
                <div class="card-body">
                    @if($item->approval_date)
                        <div class="mb-3 p-2 bg-success bg-opacity-10 rounded">
                            <div class="fw-bold text-success small">승인 완료</div>
                            <div class="small">{{ $item->approval_date->format('Y년 m월 d일 H시 i분') }}</div>
                            @if($item->approver)
                                <div class="small text-muted">승인자: {{ $item->approver->name }}</div>
                            @endif
                        </div>
                    @endif
                    @if($item->rejection_date)
                        <div class="mb-3 p-2 bg-danger bg-opacity-10 rounded">
                            <div class="fw-bold text-danger small">반려됨</div>
                            <div class="small">{{ $item->rejection_date->format('Y년 m월 d일 H시 i분') }}</div>
                            @if($item->rejector)
                                <div class="small text-muted">반려자: {{ $item->rejector->name }}</div>
                            @endif
                            @if($item->rejection_reason)
                                <div class="small mt-2 p-2 bg-light rounded">
                                    <strong>반려 사유:</strong><br>
                                    {{ $item->rejection_reason }}
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- 빠른 액션 -->
            <section>
                <a href="{{ route('admin.partner.approval.show', $item->id) }}"
                           class="btn btn-success">
                    <i class="fe fe-settings me-1"></i>승인 관리
                </a>
            </section>

        </div>
    </div>

    <!-- 삭제 확인 모달 -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">신청서 삭제 확인</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong id="deleteName"></strong>님의 파트너 신청서를 정말 삭제하시겠습니까?</p>
                    <p class="text-danger small">이 작업은 되돌릴 수 없습니다.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <form id="deleteForm" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">삭제</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function confirmDelete(id, name) {
    document.getElementById('deleteName').textContent = name;
    document.getElementById('deleteForm').action = `{{ route('admin.partner.applications.index') }}/${id}`;

    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>
@endpush
