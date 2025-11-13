@extends('jiny-partner::layouts.home')

@section('title', $pageTitle ?? '매출 상세 정보')

@section('content')
<div class="container-fluid p-6">
    <!-- Page Header -->
    <div class="row">
        <div class="col-lg-12 col-md-12 col-12">
            <div class="border-bottom pb-3 mb-3 d-flex align-items-center justify-content-between">
                <div>
                    <h1 class="mb-1 h2 fw-bold">{{ $pageTitle }}</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('home.partner.index') }}">파트너 홈</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('home.partner.sales.index') }}">판매 관리</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('home.partner.sales.history') }}">판매 이력</a></li>
                            <li class="breadcrumb-item active" aria-current="page">상세 정보</li>
                        </ol>
                    </nav>
                </div>
                <div class="d-flex gap-2">
                    {{-- PENDING 상태: 수정, 승인, 반려, 삭제 --}}
                    @if($sale->status === 'pending')
                        {{-- 수정 버튼 (대기 상태만 가능) --}}
                        <a href="{{ route('home.partner.sales.edit', $sale->id) }}" class="btn btn-outline-primary">
                            <i class="fe fe-edit-2 me-2"></i>수정
                        </a>
                        @if($hasApprovalAccess ?? false)
                        {{-- 승인 버튼 --}}
                        <form method="POST" action="{{ route('home.partner.sales.approve', $sale->id) }}"
                              onsubmit="return confirm('이 대기 중인 매출을 승인하시겠습니까?\\n\\n승인하면 확정 상태가 됩니다.')" style="display: inline;">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-success">
                                <i class="fe fe-check me-2"></i>승인
                            </button>
                        </form>
                        {{-- 반려 버튼 --}}
                        <form method="POST" action="{{ route('home.partner.sales.reject', $sale->id) }}"
                              onsubmit="return confirm('이 대기 중인 매출을 반려하시겠습니까?\\n\\n반려하면 반려 상태가 됩니다.')" style="display: inline;">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-danger">
                                <i class="fe fe-x me-2"></i>반려
                            </button>
                        </form>
                        @endif
                        {{-- 삭제 버튼 (대기 상태만 가능) --}}
                        <form method="POST" action="{{ route('home.partner.sales.destroy', $sale->id) }}"
                              onsubmit="return confirm('정말로 이 대기 중인 매출을 삭제하시겠습니까?\\n\\n⚠️ 경고: 삭제된 매출은 완전히 제거되며 복구할 수 없습니다.\\n\\n매출 정보:\\n- 상품명: {{ $sale->title }}\\n- 금액: {{ number_format($sale->amount) }}원\\n\\n계속하려면 확인을 클릭하세요.')" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="fe fe-trash-2 me-2"></i>삭제
                            </button>
                        </form>
                    @endif

                    {{-- CONFIRMED 상태: 취소 요청 --}}
                    @if($sale->status === 'confirmed')
                        <form method="POST" action="{{ route('home.partner.sales.cancel', $sale->id) }}"
                              onsubmit="return confirm('확정된 매출의 취소를 요청하시겠습니까?\\n\\n취소 요청 후 승인이 필요합니다.')" style="display: inline;">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-warning">
                                <i class="fe fe-clock me-2"></i>취소 요청
                            </button>
                        </form>
                    @endif

                    {{-- REJECTED 상태: 취소 요청 --}}
                    @if($sale->status === 'rejected')
                        <form method="POST" action="{{ route('home.partner.sales.cancel', $sale->id) }}"
                              onsubmit="return confirm('반려된 매출의 취소를 요청하시겠습니까?\\n\\n취소 요청 후 승인이 필요합니다.')" style="display: inline;">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-warning">
                                <i class="fe fe-clock me-2"></i>취소 요청
                            </button>
                        </form>
                    @endif

                    {{-- CANCEL_PENDING 상태: 취소 승인/거부 --}}
                    @if($sale->status === 'cancel_pending')
                        @if($hasApprovalAccess ?? false)
                        <form method="POST" action="{{ route('home.partner.sales.cancel.approve', $sale->id) }}"
                              onsubmit="return confirm('이 매출의 취소를 승인하시겠습니까?\\n\\n승인 후에는 취소가 확정되어 되돌릴 수 없습니다.')" style="display: inline;">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-success">
                                <i class="fe fe-check me-2"></i>취소 승인
                            </button>
                        </form>
                        <form method="POST" action="{{ route('home.partner.sales.cancel.reject', $sale->id) }}"
                              onsubmit="return confirm('이 매출의 취소를 거부하시겠습니까?\\n\\n거부 시 원래 상태로 복원됩니다.')" style="display: inline;">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-outline-warning">
                                <i class="fe fe-x me-2"></i>취소 거부
                            </button>
                        </form>
                        @else
                        <div class="alert alert-info d-inline-block mb-0">
                            <i class="fe fe-clock me-2"></i>취소 승인 대기 중입니다.
                        </div>
                        @endif
                    @endif

                    {{-- CANCELLED 상태: 복원 또는 삭제 --}}
                    @if($sale->status === 'cancelled')
                        @if($hasApprovalAccess ?? false)
                        <form method="POST" action="{{ route('home.partner.sales.restore', $sale->id) }}"
                              onsubmit="return confirm('이 취소된 매출을 복원하시겠습니까?\\n\\n복원하면 원래 상태로 되돌아갑니다.')" style="display: inline;">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-success">
                                <i class="fe fe-rotate-ccw me-2"></i>복원
                            </button>
                        </form>
                        @endif
                        <form method="POST" action="{{ route('home.partner.sales.delete', $sale->id) }}"
                              onsubmit="return confirm('정말로 이 취소된 매출을 삭제하시겠습니까?\\n\\n⚠️ 경고: 삭제된 매출은 완전히 제거되며 복구할 수 없습니다.\\n\\n계속하려면 확인을 클릭하세요.')" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="fe fe-trash-2 me-2"></i>삭제
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('home.partner.sales.create') }}" class="btn btn-primary">
                        <i class="fe fe-plus me-2"></i>새 매출 등록
                    </a>
                    <a href="{{ route('home.partner.sales.history') }}" class="btn btn-outline-secondary">
                        <i class="fe fe-arrow-left me-2"></i>목록으로
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- 고객 정보 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fe fe-users me-2"></i>고객 정보
                    </h4>
                    <small class="text-muted">매출과 관련된 고객 정보입니다</small>
                </div>
                <div class="card-body">
                    @if($sale->customer_name)
                    <!-- 등록된 고객명이 있는 경우 -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-muted">
                            <i class="fe fe-user me-1"></i>고객명
                        </label>
                        <div class="card bg-light">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-lg rounded-circle bg-info text-white me-3">
                                        {{ mb_substr($sale->customer_name, 0, 1) }}
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="mb-1 text-info">👤 {{ $sale->customer_name }}</h5>
                                        <div class="text-muted">
                                            <small>
                                                <i class="fe fe-info me-1"></i>
                                                @php
                                                    // 이메일 형식인지 확인
                                                    $isEmail = filter_var($sale->customer_name, FILTER_VALIDATE_EMAIL);

                                                    // @ 포함 여부로 간단 체크
                                                    $hasAtSymbol = strpos($sale->customer_name, '@') !== false;
                                                @endphp
                                                @if($isEmail)
                                                    회원 검색으로 선택된 고객 (이메일 주소 형식)
                                                @elseif($hasAtSymbol)
                                                    이메일 형식으로 보이는 고객명
                                                @else
                                                    직접 입력된 고객명
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                    <span class="badge bg-info fs-6">고객</span>
                                </div>

                                {{-- 추가 고객 정보 표시 --}}
                                @if($isEmail)
                                <div class="border-top pt-3 mt-3">
                                    @if($customerInfo)
                                    <!-- 실제 회원 정보 발견됨 -->
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="alert alert-success d-flex align-items-start">
                                                <i class="fe fe-check-circle me-2 mt-1"></i>
                                                <div class="flex-grow-1">
                                                    <strong>✅ 등록된 회원 정보 발견</strong>
                                                    <small class="d-block mb-2">이 고객은 시스템에 등록된 회원입니다.</small>

                                                    <div class="row mt-2">
                                                        <div class="col-md-6">
                                                            <div class="small">
                                                                <strong>실제 회원명:</strong> {{ $customerInfo->name }}<br>
                                                                <strong>가입일:</strong> {{ \Carbon\Carbon::parse($customerInfo->created_at)->format('Y년 m월 d일') }}<br>
                                                                <strong>이메일 인증:</strong>
                                                                @if($customerInfo->email_verified)
                                                                    <span class="badge bg-success">인증완료</span>
                                                                @else
                                                                    <span class="badge bg-warning">미인증</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="small">
                                                                <strong>UUID:</strong> {{ substr($customerInfo->uuid, 0, 8) }}...<br>
                                                                <strong>계정 상태:</strong> <span class="badge bg-success">활성</span><br>
                                                                <strong>데이터 출처:</strong> <span class="badge bg-light text-dark">{{ $customerInfo->table_source ?? '알 수 없음' }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @else
                                    <!-- 회원 정보를 찾지 못함 -->
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="alert alert-warning d-flex align-items-center">
                                                <i class="fe fe-alert-triangle me-2"></i>
                                                <div class="flex-grow-1">
                                                    <strong>⚠️ 회원 정보 없음</strong>
                                                    <small class="d-block">
                                                        이메일 형식이지만 시스템에서 일치하는 회원 정보를 찾을 수 없습니다.
                                                        고객이 회원 가입을 하지 않았거나, 다른 이메일로 가입했을 가능성이 있습니다.
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @else
                    <!-- 고객명이 없는 경우 -->
                    <div class="text-center py-4 text-muted">
                        <i class="fe fe-user-x me-2" style="font-size: 2rem;"></i>
                        <div class="mt-2">
                            <h6 class="text-muted">고객 정보 없음</h6>
                            <small>이 매출에는 고객 정보가 등록되지 않았습니다.</small>
                        </div>

                        {{-- 고객 정보 추가 버튼 (향후 확장용) --}}
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fe fe-info me-1"></i>
                                매출 수정 기능을 통해 고객 정보를 추가할 수 있습니다.
                            </small>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- 기본 정보 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fe fe-info me-2"></i>기본 정보
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">매출 ID</label>
                                <p class="form-control-plaintext">#{{ $sale->id }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">상태</label>
                                <p class="form-control-plaintext">
                                    @if($sale->status === 'confirmed')
                                    <span class="badge bg-success fs-6">확정</span>
                                    @elseif($sale->status === 'pending')
                                    <span class="badge bg-warning fs-6">대기</span>
                                    @elseif($sale->status === 'cancel_pending')
                                    <span class="badge bg-info fs-6">취소 승인 대기</span>
                                    @elseif($sale->status === 'cancelled')
                                    <span class="badge bg-danger fs-6">취소됨</span>
                                    @elseif($sale->status === 'rejected')
                                    <span class="badge bg-secondary fs-6">반려됨</span>
                                    @else
                                    <span class="badge bg-secondary fs-6">{{ $sale->status }}</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">상품명</label>
                                <p class="form-control-plaintext">{{ $sale->product_name ?? $sale->title }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">매출 금액</label>
                                <p class="form-control-plaintext">
                                    <span class="h4 text-primary">{{ number_format($sale->amount) }}원</span>
                                    <small class="text-muted">({{ $sale->currency ?? 'KRW' }})</small>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">판매 일시</label>
                                <p class="form-control-plaintext">{{ $sale->sales_date ? \Carbon\Carbon::parse($sale->sales_date)->format('Y년 m월 d일 H:i') : '-' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">카테고리</label>
                                <p class="form-control-plaintext">
                                    <span class="badge bg-light text-dark">{{ $sale->category ?? '일반' }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                    @if($sale->description)
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted">설명</label>
                        <div class="card bg-light">
                            <div class="card-body">
                                {{ $sale->description }}
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- 시스템 정보 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fe fe-clock me-2"></i>시스템 정보
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">등록 일시</label>
                                <p class="form-control-plaintext">{{ $sale->created_at->format('Y년 m월 d일 H:i:s') }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">수정 일시</label>
                                <p class="form-control-plaintext">{{ $sale->updated_at->format('Y년 m월 d일 H:i:s') }}</p>
                            </div>
                        </div>
                    </div>
                    @if($sale->status === 'confirmed' && $sale->confirmed_at)
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">확정 일시</label>
                                <p class="form-control-plaintext">{{ \Carbon\Carbon::parse($sale->confirmed_at)->format('Y년 m월 d일 H:i:s') }}</p>
                            </div>
                        </div>
                    </div>
                    @endif
                    @if($sale->status === 'cancel_pending' && $sale->cancel_requested_at)
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">취소 요청 일시</label>
                                <p class="form-control-plaintext">{{ \Carbon\Carbon::parse($sale->cancel_requested_at)->format('Y년 m월 d일 H:i:s') }}</p>
                            </div>
                        </div>
                        @if($sale->status_reason)
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">취소 요청 이유</label>
                                <p class="form-control-plaintext">{{ $sale->status_reason }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif
                    @if($sale->status === 'cancelled')
                    <div class="row">
                        @if($sale->cancel_requested_at)
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">취소 요청 일시</label>
                                <p class="form-control-plaintext">{{ \Carbon\Carbon::parse($sale->cancel_requested_at)->format('Y년 m월 d일 H:i:s') }}</p>
                            </div>
                        </div>
                        @endif
                        @if($sale->cancelled_at)
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">취소 승인 일시</label>
                                <p class="form-control-plaintext">{{ \Carbon\Carbon::parse($sale->cancelled_at)->format('Y년 m월 d일 H:i:s') }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                    @if($sale->status_reason)
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">취소 이유</label>
                                <p class="form-control-plaintext">{{ $sale->status_reason }}</p>
                            </div>
                        </div>
                    </div>
                    @endif
                    @endif
                    @if($sale->status === 'rejected' && isset($sale->rejected_at) && $sale->rejected_at)
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">반려 일시</label>
                                <p class="form-control-plaintext">{{ \Carbon\Carbon::parse($sale->rejected_at)->format('Y년 m월 d일 H:i:s') }}</p>
                            </div>
                        </div>
                        @if($sale->status_reason)
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">반려 이유</label>
                                <p class="form-control-plaintext">{{ $sale->status_reason }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif
                    @if(isset($sale->restored_at) && $sale->restored_at)
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">복원 일시</label>
                                <p class="form-control-plaintext">{{ \Carbon\Carbon::parse($sale->restored_at)->format('Y년 m월 d일 H:i:s') }}</p>
                            </div>
                        </div>
                        @if($sale->status_reason && str_contains($sale->status_reason, '복원됨'))
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">복원 내용</label>
                                <p class="form-control-plaintext">{{ $sale->status_reason }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- 회원 및 파트너 정보 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fe fe-user me-2"></i>회원 및 파트너 정보
                    </h4>
                    <small class="text-muted">매출과 관련된 실제 회원과 파트너 계정 정보입니다</small>
                </div>
                <div class="card-body">
                    <!-- 매출 소유자 -->
                    @if($salePartnerUser || $salePartner)
                    <div class="mb-4">
                        <label class="form-label fw-bold text-muted">
                            <i class="fe fe-target me-1"></i>매출 소유자
                        </label>
                        <div class="card bg-light">
                            <div class="card-body p-3">
                                @if($salePartnerUser)
                                <!-- 실제 회원 정보 (메인) -->
                                <div class="d-flex align-items-center mb-3">
                                    <div class="avatar avatar-lg rounded-circle bg-success text-white me-3">
                                        {{ mb_substr($salePartnerUser->name ?? 'U', 0, 1) }}
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="mb-1 text-primary">👤 {{ $salePartnerUser->name ?? '정보 없음' }}</h5>
                                        <div class="mb-1">
                                            <strong>📧 {{ $salePartnerUser->email ?? '정보 없음' }}</strong>
                                        </div>
                                        <div class="text-muted">
                                            <small>📅 가입일: {{ $salePartnerUser->created_at ? \Carbon\Carbon::parse($salePartnerUser->created_at)->format('Y년 m월 d일') : '정보 없음' }}</small>
                                            @if(isset($salePartnerUser->phone) && $salePartnerUser->phone)
                                            <br><small>📱 연락처: {{ $salePartnerUser->phone }}</small>
                                            @endif
                                        </div>
                                    </div>
                                    @if($sale->partner_id === $partnerUser->id)
                                    <span class="badge bg-success fs-6">본인</span>
                                    @endif
                                </div>
                                @endif

                                @if($salePartner)
                                <!-- 파트너 정보 (보조) -->
                                <div class="border-top pt-2">
                                    <small class="text-muted fw-bold">🏢 파트너 계정 정보:</small>
                                    <div class="ms-3 mt-1">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <small><strong>파트너명:</strong> {{ $salePartner->name ?? '정보 없음' }}</small>
                                            </div>
                                            <div class="col-md-6">
                                                <small><strong>파트너 ID:</strong> {{ $salePartner->id ?? 'N/A' }}</small>
                                            </div>
                                        </div>
                                        <div class="row mt-1">
                                            <div class="col-md-6">
                                                <small><strong>파트너 이메일:</strong> {{ $salePartner->email ?? '정보 없음' }}</small>
                                            </div>
                                            <div class="col-md-6">
                                                @if($salePartner->user_uuid)
                                                <small><strong>UUID:</strong> {{ substr($salePartner->user_uuid, 0, 8) }}...</small>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- 매출 등록자 -->
                    @if($registeredUser || $registeredByUser)
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted">
                            <i class="fe fe-user-plus me-1"></i>매출 등록자
                        </label>
                        <div class="card bg-light">
                            <div class="card-body p-3">
                                @if($registeredUser)
                                <!-- 실제 회원 정보 (메인) -->
                                <div class="d-flex align-items-center mb-3">
                                    <div class="avatar avatar-lg rounded-circle bg-info text-white me-3">
                                        {{ mb_substr($registeredUser->name ?? 'U', 0, 1) }}
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="mb-1 text-info">👤 {{ $registeredUser->name ?? '정보 없음' }}</h5>
                                        <div class="mb-1">
                                            <strong>📧 {{ $registeredUser->email ?? '정보 없음' }}</strong>
                                        </div>
                                        <div class="text-muted">
                                            <small>📅 가입일: {{ $registeredUser->created_at ? \Carbon\Carbon::parse($registeredUser->created_at)->format('Y년 m월 d일') : '정보 없음' }}</small>
                                            @if(isset($registeredUser->phone) && $registeredUser->phone)
                                            <br><small>📱 연락처: {{ $registeredUser->phone }}</small>
                                            @endif
                                        </div>
                                    </div>
                                    @if($sale->created_by === $partnerUser->id)
                                    <span class="badge bg-info fs-6">본인 등록</span>
                                    @endif
                                </div>
                                @endif

                                @if($registeredByUser)
                                <!-- 파트너 정보 (보조) -->
                                <div class="border-top pt-2">
                                    <small class="text-muted fw-bold">🏢 파트너 계정 정보:</small>
                                    <div class="ms-3 mt-1">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <small><strong>파트너명:</strong> {{ $registeredByUser->name ?? '정보 없음' }}</small>
                                            </div>
                                            <div class="col-md-6">
                                                <small><strong>파트너 ID:</strong> {{ $registeredByUser->id ?? 'N/A' }}</small>
                                            </div>
                                        </div>
                                        <div class="row mt-1">
                                            <div class="col-md-6">
                                                <small><strong>파트너 이메일:</strong> {{ $registeredByUser->email ?? '정보 없음' }}</small>
                                            </div>
                                            <div class="col-md-6">
                                                @if($registeredByUser->user_uuid)
                                                <small><strong>UUID:</strong> {{ substr($registeredByUser->user_uuid, 0, 8) }}...</small>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- 현재 조회자 -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted">
                            <i class="fe fe-eye me-1"></i>현재 조회자
                        </label>
                        <div class="card" style="border: 2px solid #ffc107;">
                            <div class="card-body p-3 bg-warning bg-opacity-10">
                                @if($currentUser)
                                <!-- 실제 회원 정보 (메인) -->
                                <div class="d-flex align-items-center mb-3">
                                    <div class="avatar avatar-lg rounded-circle bg-warning text-white me-3">
                                        {{ mb_substr($currentUser->name ?? 'U', 0, 1) }}
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="mb-1 text-warning">👤 {{ $currentUser->name ?? '정보 없음' }}</h5>
                                        <div class="mb-1">
                                            <strong>📧 {{ $currentUser->email ?? '정보 없음' }}</strong>
                                        </div>
                                        <div class="text-muted">
                                            <small>📅 가입일: {{ $currentUser->created_at ? \Carbon\Carbon::parse($currentUser->created_at)->format('Y년 m월 d일') : '정보 없음' }}</small>
                                            @if(isset($currentUser->phone) && $currentUser->phone)
                                            <br><small>📱 연락처: {{ $currentUser->phone }}</small>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="badge bg-warning text-dark fs-6">나</span>
                                </div>
                                @endif

                                @if($partnerUser)
                                <!-- 파트너 정보 (보조) -->
                                <div class="border-top pt-2">
                                    <small class="text-muted fw-bold">🏢 파트너 계정 정보:</small>
                                    <div class="ms-3 mt-1">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <small><strong>파트너명:</strong> {{ $partnerUser->name ?? '정보 없음' }}</small>
                                            </div>
                                            <div class="col-md-6">
                                                <small><strong>파트너 ID:</strong> {{ $partnerUser->id ?? 'N/A' }}</small>
                                            </div>
                                        </div>
                                        <div class="row mt-1">
                                            <div class="col-md-6">
                                                <small><strong>파트너 이메일:</strong> {{ $partnerUser->email ?? '정보 없음' }}</small>
                                            </div>
                                            <div class="col-md-6">
                                                @if($partnerUser->user_uuid)
                                                <small><strong>UUID:</strong> {{ substr($partnerUser->user_uuid, 0, 8) }}...</small>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 커미션 정보 -->
            @if($commissionInfo)
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fe fe-dollar-sign me-2"></i>커미션 정보
                    </h4>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted">총 커미션 금액</label>
                        <p class="form-control-plaintext">
                            <span class="h5 text-success">{{ number_format($commissionInfo['total_amount']) }}원</span>
                        </p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted">커미션 수령자 수</label>
                        <p class="form-control-plaintext">{{ $commissionInfo['recipients_count'] }}명</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted">계산 일시</label>
                        <p class="form-control-plaintext">{{ \Carbon\Carbon::parse($commissionInfo['calculated_at'])->format('Y년 m월 d일 H:i:s') }}</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- 추가 정보 -->
            @if($sale->external_reference || $sale->order_number)
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fe fe-tag me-2"></i>추가 정보
                    </h4>
                </div>
                <div class="card-body">
                    @if($sale->order_number)
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted">주문번호</label>
                        <p class="form-control-plaintext">{{ $sale->order_number }}</p>
                    </div>
                    @endif
                    @if($sale->external_reference)
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted">외부 참조</label>
                        <p class="form-control-plaintext">{{ $sale->external_reference }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.avatar {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}
</style>
@endpush