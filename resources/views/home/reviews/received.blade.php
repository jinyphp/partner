@extends('jiny-partner::layouts.home')

@section('title', '받은 리뷰')

@section('content')
<div class="container-fluid p-6">
    <div class="row">
        <div class="col-lg-12">
            <div class="border-bottom pb-3 mb-3">
                <h1 class="mb-1 h2 fw-bold">받은 리뷰</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home.partner.index') }}">파트너 홈</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('home.partner.reviews.index') }}">리뷰 현황</a></li>
                        <li class="breadcrumb-item active">받은 리뷰</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row mb-4">
        <div class="col-xl-3 col-lg-6 col-md-12 col-12">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $reviewStats['total_received'] }}</h4>
                    <p class="mb-0">총 받은 리뷰</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-12 col-12">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ number_format($reviewStats['average_rating'], 1) }}</h4>
                    <p class="mb-0">평균 평점</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-12 col-12">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $reviewStats['five_star_count'] }}</h4>
                    <p class="mb-0">5점 리뷰</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-12 col-12">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $reviewStats['this_month_count'] }}</h4>
                    <p class="mb-0">이번 달 리뷰</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">평점</label>
                            <select name="rating" class="form-select">
                                <option value="all" {{ $currentRating === 'all' ? 'selected' : '' }}>전체</option>
                                <option value="5" {{ $currentRating === '5' ? 'selected' : '' }}>5점</option>
                                <option value="4" {{ $currentRating === '4' ? 'selected' : '' }}>4점</option>
                                <option value="3" {{ $currentRating === '3' ? 'selected' : '' }}>3점</option>
                                <option value="2" {{ $currentRating === '2' ? 'selected' : '' }}>2점</option>
                                <option value="1" {{ $currentRating === '1' ? 'selected' : '' }}>1점</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">기간</label>
                            <select name="period" class="form-select">
                                <option value="all" {{ $currentPeriod === 'all' ? 'selected' : '' }}>전체</option>
                                <option value="this_month" {{ $currentPeriod === 'this_month' ? 'selected' : '' }}>이번 달</option>
                                <option value="last_month" {{ $currentPeriod === 'last_month' ? 'selected' : '' }}>지난 달</option>
                                <option value="this_year" {{ $currentPeriod === 'this_year' ? 'selected' : '' }}>올해</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">공개설정</label>
                            <select name="visibility" class="form-select">
                                <option value="all" {{ $currentVisibility === 'all' ? 'selected' : '' }}>전체</option>
                                <option value="public" {{ $currentVisibility === 'public' ? 'selected' : '' }}>공개</option>
                                <option value="private" {{ $currentVisibility === 'private' ? 'selected' : '' }}>비공개</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">검색</button>
                            <a href="{{ route('home.partner.reviews.received') }}" class="btn btn-outline-secondary ms-2">초기화</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Reviews List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">리뷰 목록</h4>
                    <div class="d-flex gap-2">
                        <div class="btn-group btn-group-sm" role="group">
                            <input type="radio" class="btn-check" name="view_mode" id="list_view" checked>
                            <label class="btn btn-outline-primary" for="list_view">
                                <i class="fe fe-list"></i>
                            </label>
                            <input type="radio" class="btn-check" name="view_mode" id="card_view">
                            <label class="btn btn-outline-primary" for="card_view">
                                <i class="fe fe-grid"></i>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- List View -->
                    <div id="listView">
                        @forelse($receivedReviews as $review)
                        <div class="border-bottom pb-4 mb-4">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="d-flex">
                                    <div class="avatar avatar-md me-3">
                                        <div class="avatar-img rounded-circle bg-primary text-white d-flex align-items-center justify-content-center">
                                            {{ mb_substr($review->reviewer_name, 0, 1, 'UTF-8') }}
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-2">
                                            <h6 class="mb-0 me-3">{{ $review->reviewer_name }}</h6>
                                            <div class="me-3">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <i class="fe fe-star {{ $i <= $review->rating ? 'text-warning' : 'text-muted' }} small"></i>
                                                @endfor
                                            </div>
                                            <span class="badge bg-{{ $review->is_public ? 'success' : 'secondary' }}">
                                                {{ $review->is_public ? '공개' : '비공개' }}
                                            </span>
                                        </div>
                                        <p class="mb-2">{{ $review->comment }}</p>
                                        <div class="d-flex align-items-center text-muted small">
                                            <span class="me-3">
                                                <i class="fe fe-briefcase me-1"></i>{{ $review->project_title }}
                                            </span>
                                            <span class="me-3">
                                                <i class="fe fe-calendar me-1"></i>{{ $review->created_at->format('Y-m-d') }}
                                            </span>
                                            <span>
                                                <i class="fe fe-clock me-1"></i>{{ $review->created_at->diffForHumans() }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="dropdown">
                                        <i class="fe fe-more-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="#" onclick="shareReview({{ $review->id }})">
                                                <i class="fe fe-share-2 me-2"></i>공유하기
                                            </a>
                                        </li>
                                        @if($review->is_public)
                                        <li>
                                            <a class="dropdown-item" href="#" onclick="toggleVisibility({{ $review->id }}, false)">
                                                <i class="fe fe-eye-off me-2"></i>비공개로 변경
                                            </a>
                                        </li>
                                        @else
                                        <li>
                                            <a class="dropdown-item" href="#" onclick="toggleVisibility({{ $review->id }}, true)">
                                                <i class="fe fe-eye me-2"></i>공개로 변경
                                            </a>
                                        </li>
                                        @endif
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item" href="#" onclick="reportReview({{ $review->id }})">
                                                <i class="fe fe-flag me-2"></i>신고하기
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fe fe-inbox display-4 text-muted"></i>
                            </div>
                            <h6 class="mb-1">받은 리뷰가 없습니다</h6>
                            <p class="text-muted">프로젝트를 완료하면 고객으로부터 리뷰를 받을 수 있습니다.</p>
                        </div>
                        @endforelse
                    </div>

                    <!-- Card View (Hidden by default) -->
                    <div id="cardView" style="display: none;">
                        <div class="row">
                            @foreach($receivedReviews as $review)
                            <div class="col-lg-6 col-xl-4 mb-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="avatar avatar-sm me-2">
                                                <div class="avatar-img rounded-circle bg-primary text-white d-flex align-items-center justify-content-center">
                                                    {{ mb_substr($review->reviewer_name, 0, 1, 'UTF-8') }}
                                                </div>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $review->reviewer_name }}</h6>
                                                <small class="text-muted">{{ $review->created_at->format('Y-m-d') }}</small>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="fe fe-star {{ $i <= $review->rating ? 'text-warning' : 'text-muted' }} small"></i>
                                            @endfor
                                        </div>
                                        <p class="card-text">{{ Str::limit($review->comment, 100) }}</p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">{{ $review->project_title }}</small>
                                            <span class="badge bg-{{ $review->is_public ? 'success' : 'secondary' }}">
                                                {{ $review->is_public ? '공개' : '비공개' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    @if($receivedReviews && method_exists($receivedReviews, 'links'))
                        {{ $receivedReviews->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// 뷰 모드 전환
document.getElementById('list_view').addEventListener('change', function() {
    if (this.checked) {
        document.getElementById('listView').style.display = 'block';
        document.getElementById('cardView').style.display = 'none';
    }
});

document.getElementById('card_view').addEventListener('change', function() {
    if (this.checked) {
        document.getElementById('listView').style.display = 'none';
        document.getElementById('cardView').style.display = 'block';
    }
});

// 리뷰 공개/비공개 설정 변경
function toggleVisibility(reviewId, isPublic) {
    if (!confirm(isPublic ? '이 리뷰를 공개로 변경하시겠습니까?' : '이 리뷰를 비공개로 변경하시겠습니까?')) {
        return;
    }

    fetch(`/home/partner/reviews/${reviewId}/visibility`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            is_public: isPublic
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('변경에 실패했습니다: ' + (data.message || '알 수 없는 오류'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('오류가 발생했습니다.');
    });
}

// 리뷰 공유
function shareReview(reviewId) {
    const shareUrl = `${window.location.origin}/reviews/${reviewId}`;

    if (navigator.share) {
        navigator.share({
            title: '리뷰 공유',
            url: shareUrl
        });
    } else {
        // 클립보드에 복사
        navigator.clipboard.writeText(shareUrl).then(() => {
            alert('링크가 클립보드에 복사되었습니다.');
        });
    }
}

// 리뷰 신고
function reportReview(reviewId) {
    const reason = prompt('신고 사유를 입력해주세요:');
    if (!reason) return;

    fetch(`/home/partner/reviews/${reviewId}/report`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            reason: reason
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('신고가 접수되었습니다.');
        } else {
            alert('신고 접수에 실패했습니다: ' + (data.message || '알 수 없는 오류'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('오류가 발생했습니다.');
    });
}
</script>
@endpush
