@extends('jiny-partner::layouts.home')

@section('title', '추천인 검색 결과')

@section('content')
<div class="container-fluid p-6">
    <div class="row">
        <div class="col-lg-12">
            <div class="border-bottom pb-3 mb-3">
                <h1 class="mb-1 h2 fw-bold">추천인 검색 결과</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home.partner.index') }}">파트너 홈</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('home.partner.search.referrer') }}">추천인 검색</a></li>
                        <li class="breadcrumb-item active">검색 결과</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <!-- 검색 쿼리 표시 -->
            <div class="alert alert-info mb-4">
                <div class="d-flex align-items-center">
                    <i class="fe fe-search me-2"></i>
                    <span><strong>검색 이메일:</strong> {{ $searchEmail }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- 사용자 정보 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fe fe-user me-2"></i>사용자 정보
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>이름:</strong> {{ $searchResult['user_info']['name'] }}</p>
                            <p><strong>이메일:</strong> {{ $searchResult['user_info']['email'] }}</p>
                            <p><strong>UUID:</strong> <code>{{ $searchResult['user_info']['user_uuid'] }}</code></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>샤드 ID:</strong> {{ $searchResult['user_info']['shard_id'] ?? 'N/A' }}</p>
                            <p><strong>검색 테이블:</strong> <code>{{ $searchResult['user_info']['source_table'] }}</code></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 파트너 정보 -->
            @if($searchResult['partner_info'])
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fe fe-briefcase me-2"></i>파트너 정보
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>등급:</strong>
                                <span class="badge bg-{{ $searchResult['partner_info']['tier_name'] === 'Platinum' ? 'success' : ($searchResult['partner_info']['tier_name'] === 'Gold' ? 'warning' : ($searchResult['partner_info']['tier_name'] === 'Silver' ? 'info' : 'secondary')) }}">
                                    {{ $searchResult['partner_info']['tier_name'] }}
                                </span>
                            </p>
                            <p><strong>상태:</strong>
                                <span class="badge bg-{{ $searchResult['partner_info']['status'] === 'active' ? 'success' : 'danger' }}">
                                    {{ $searchResult['partner_info']['status'] === 'active' ? '활성' : '비활성' }}
                                </span>
                            </p>
                            <p><strong>수수료율:</strong> {{ $searchResult['partner_info']['commission_rate'] ?? 'N/A' }}%</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>가입일:</strong> {{ $searchResult['partner_info']['joined_at'] ? \Carbon\Carbon::parse($searchResult['partner_info']['joined_at'])->format('Y-m-d') : 'N/A' }}</p>
                            <p><strong>관리 중인 파트너:</strong> {{ $searchResult['partner_info']['managed_partners_count'] }}명</p>
                            <p><strong>총 추천 수:</strong> {{ $searchResult['partner_info']['total_referrals'] }}명</p>
                        </div>
                    </div>
                </div>
            </div>
            @else
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fe fe-alert-circle display-4 text-warning"></i>
                    </div>
                    <h5 class="mb-2">파트너 정보 없음</h5>
                    <p class="text-muted">이 사용자는 아직 파트너로 등록되지 않았습니다.</p>
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            <!-- 추천인 자격 상태 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fe fe-{{ $searchResult['can_refer'] ? 'check-circle' : 'x-circle' }} me-2 text-{{ $searchResult['can_refer'] ? 'success' : 'danger' }}"></i>추천인 자격
                    </h5>
                </div>
                <div class="card-body">
                    @if($searchResult['can_refer'])
                        <div class="alert alert-success">
                            <h6 class="alert-heading">
                                <i class="fe fe-check me-1"></i>추천 가능
                            </h6>
                            <p class="mb-2">{{ $searchResult['eligibility']['reason'] }}</p>

                            @if(isset($searchResult['eligibility']['benefits']))
                            <hr>
                            <h6 class="small fw-bold mb-2">혜택:</h6>
                            <ul class="list-unstyled small mb-0">
                                @foreach($searchResult['eligibility']['benefits'] as $benefit)
                                <li><i class="fe fe-check me-1 text-success"></i>{{ $benefit }}</li>
                                @endforeach
                            </ul>
                            @endif

                            @if(isset($searchResult['eligibility']['remaining_slots']))
                            <hr>
                            <p class="small mb-0">
                                <strong>남은 추천 슬롯:</strong> {{ $searchResult['eligibility']['remaining_slots'] }}개
                            </p>
                            @endif
                        </div>
                    @else
                        <div class="alert alert-danger">
                            <h6 class="alert-heading">
                                <i class="fe fe-x me-1"></i>추천 불가
                            </h6>
                            <p class="mb-2">{{ $searchResult['eligibility']['reason'] }}</p>

                            @if(isset($searchResult['eligibility']['requirements']))
                            <hr>
                            <h6 class="small fw-bold mb-2">필요 조건:</h6>
                            <ul class="list-unstyled small mb-0">
                                @foreach($searchResult['eligibility']['requirements'] as $requirement)
                                <li><i class="fe fe-alert-circle me-1 text-warning"></i>{{ $requirement }}</li>
                                @endforeach
                            </ul>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- 승인 권한 정보 -->
            @if($searchResult['partner_info'] && isset($searchResult['partner_info']['approval_permissions']))
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fe fe-shield me-2"></i>승인 권한
                    </h6>
                </div>
                <div class="card-body">
                    @php $permissions = $searchResult['partner_info']['approval_permissions']; @endphp
                    <p><strong>직접 승인:</strong>
                        <span class="badge bg-{{ $permissions['can_approve'] ? 'success' : 'warning' }}">
                            {{ $permissions['can_approve'] ? '가능' : '불가' }}
                        </span>
                    </p>
                    <p><strong>월간 한도:</strong> {{ $permissions['monthly_limit'] }}명</p>
                </div>
            </div>
            @endif

            <!-- 액션 버튼 -->
            <div class="card">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('home.partner.search.referrer') }}" class="btn btn-outline-primary">
                            <i class="fe fe-search me-1"></i>다시 검색
                        </a>

                        @if($searchResult['can_refer'])
                        <button class="btn btn-success" onclick="useAsReferrer('{{ $searchResult['user_info']['user_uuid'] }}', '{{ $searchResult['user_info']['name'] }}')">
                            <i class="fe fe-user-plus me-1"></i>추천인으로 사용
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function useAsReferrer(userUuid, userName) {
    if (!confirm(`${userName}님을 추천인으로 설정하시겠습니까?`)) {
        return;
    }

    // 추천인 설정 로직 구현
    fetch('/home/partner/set-referrer', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            referrer_uuid: userUuid
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('추천인이 설정되었습니다.');
            // 적절한 페이지로 리다이렉트
            window.location.href = '/home/partner';
        } else {
            alert('오류가 발생했습니다: ' + (data.message || '알 수 없는 오류'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('네트워크 오류가 발생했습니다.');
    });
}

// JSON으로 결과 보기
function viewAsJson() {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('format', 'json');
    window.open(currentUrl.toString(), '_blank');
}
</script>
@endpush