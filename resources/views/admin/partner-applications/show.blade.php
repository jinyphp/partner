@extends('jiny-partner::layouts.admin.sidebar')

@section('title', $title . ' 상세보기')

@section('content')
<div class="container-fluid">
    <!-- 헤더 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">{{ $title }} 상세보기</h2>
                    <p class="text-muted mb-0">지원자: {{ $item->personal_info['name'] ?? $item->user->name ?? 'Unknown' }}</p>
                </div>
                <div>
                    <a href="{{ route('admin.partner.applications.index') }}" class="btn btn-outline-secondary me-2">
                        <i class="fe fe-arrow-left me-2"></i>목록으로
                    </a>
                    <a href="{{ route('admin.partner.approval.show', $item->id) }}" class="btn btn-primary">
                        <i class="fe fe-settings me-2"></i>승인 관리
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 지원서 상태 -->
    <div class="row mb-4">
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
                            <h6 class="text-muted">희망 시급</h6>
                            <p class="mb-0 fw-bold">{{ number_format($item->expected_hourly_rate ?? 0) }}원</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- 지원자 정보 -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">개인정보</h5>
                </div>
                <div class="card-body">
                    @if($item->personal_info)
                        <div class="row">
                            <div class="col-6">
                                <strong>이름:</strong> {{ $item->personal_info['name'] ?? 'N/A' }}
                            </div>
                            <div class="col-6">
                                <strong>연락처:</strong> {{ $item->personal_info['phone'] ?? 'N/A' }}
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-6">
                                <strong>이메일:</strong> {{ $item->user->email ?? 'N/A' }}
                            </div>
                            <div class="col-6">
                                <strong>출생년도:</strong> {{ $item->personal_info['birth_year'] ?? 'N/A' }}
                            </div>
                        </div>
                        <div class="mt-2">
                            <strong>주소:</strong> {{ $item->personal_info['address'] ?? 'N/A' }}
                        </div>
                        <div class="mt-2">
                            <strong>학력:</strong> {{ $item->personal_info['education_level'] ?? 'N/A' }}
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
        </div>

        <div class="col-lg-6">
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

            <!-- 관리자 메모 -->
            @if($item->admin_notes)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">관리자 메모</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ $item->admin_notes }}</p>
                </div>
            </div>
            @endif

            <!-- 처리 내역 -->
            @if($item->approval_date || $item->rejection_date)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">처리 내역</h5>
                </div>
                <div class="card-body">
                    @if($item->approval_date)
                        <div class="mb-2">
                            <strong>승인일:</strong> {{ $item->approval_date->format('Y년 m월 d일 H시 i분') }}
                        </div>
                        @if($item->approver)
                            <div class="mb-2">
                                <strong>승인자:</strong> {{ $item->approver->name }}
                            </div>
                        @endif
                    @endif
                    @if($item->rejection_date)
                        <div class="mb-2">
                            <strong>반려일:</strong> {{ $item->rejection_date->format('Y년 m월 d일 H시 i분') }}
                        </div>
                        @if($item->rejector)
                            <div class="mb-2">
                                <strong>반려자:</strong> {{ $item->rejector->name }}
                            </div>
                        @endif
                        @if($item->rejection_reason)
                            <div class="mb-2">
                                <strong>반려 사유:</strong>
                                <p class="mt-1">{{ $item->rejection_reason }}</p>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection