@extends('jiny-partner::layouts.home')

@section('title', '작성한 리뷰')

@section('content')
<div class="container-fluid p-6">
    <div class="row">
        <div class="col-lg-12">
            <div class="border-bottom pb-3 mb-3">
                <h1 class="mb-1 h2 fw-bold">작성한 리뷰</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home.partner.index') }}">파트너 홈</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('home.partner.reviews.index') }}">리뷰 현황</a></li>
                        <li class="breadcrumb-item active">작성한 리뷰</li>
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
                    <h4 class="mb-0">{{ $reviewStats['total_given'] }}</h4>
                    <p class="mb-0">총 작성 리뷰</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-12 col-12">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ number_format($reviewStats['average_given_rating'], 1) }}</h4>
                    <p class="mb-0">평균 작성 평점</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-12 col-12">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $reviewStats['this_month_given'] }}</h4>
                    <p class="mb-0">이번 달 작성</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-12 col-12">
            <div class="card">
                <div class="card-body text-center">
                    <h4 class="mb-0">{{ $reviewStats['pending_reviews'] }}</h4>
                    <p class="mb-0">리뷰 대기</p>
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
                            <label class="form-label">상태</label>
                            <select name="status" class="form-select">
                                <option value="all" {{ $currentStatus === 'all' ? 'selected' : '' }}>전체</option>
                                <option value="published" {{ $currentStatus === 'published' ? 'selected' : '' }}>게시됨</option>
                                <option value="draft" {{ $currentStatus === 'draft' ? 'selected' : '' }}>임시저장</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">검색</button>
                            <a href="{{ route('home.partner.reviews.given') }}" class="btn btn-outline-secondary ms-2">초기화</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Reviews Alert -->
    @if($reviewStats['pending_reviews'] > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-warning">
                <div class="d-flex align-items-center">
                    <i class="fe fe-clock me-2"></i>
                    <div class="flex-grow-1">
                        <strong>{{ $reviewStats['pending_reviews'] }}개의 프로젝트</strong>에 대한 리뷰 작성이 대기 중입니다.
                    </div>
                    <a href="#pendingReviews" class="btn btn-warning btn-sm">리뷰 작성하기</a>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Given Reviews List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">작성한 리뷰 목록</h4>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#writeReviewModal">
                        <i class="fe fe-plus me-1"></i>새 리뷰 작성
                    </button>
                </div>
                <div class="card-body">
                    @forelse($givenReviews as $review)
                    <div class="border-bottom pb-4 mb-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="d-flex">
                                <div class="avatar avatar-md me-3">
                                    <div class="avatar-img rounded-circle bg-info text-white d-flex align-items-center justify-content-center">
                                        {{ mb_substr($review->reviewee_name, 0, 1, 'UTF-8') }}
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <h6 class="mb-0 me-3">{{ $review->reviewee_name }}님에게 작성</h6>
                                        <div class="me-3">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="fe fe-star {{ $i <= $review->rating ? 'text-warning' : 'text-muted' }} small"></i>
                                            @endfor
                                        </div>
                                        <span class="badge bg-{{ $review->status === 'published' ? 'success' : 'warning' }}">
                                            {{ $review->status === 'published' ? '게시됨' : '임시저장' }}
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
                                        <a class="dropdown-item" href="#" onclick="editReview({{ $review->id }})">
                                            <i class="fe fe-edit me-2"></i>수정하기
                                        </a>
                                    </li>
                                    @if($review->status === 'draft')
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="publishReview({{ $review->id }})">
                                            <i class="fe fe-send me-2"></i>게시하기
                                        </a>
                                    </li>
                                    @endif
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="#" onclick="deleteReview({{ $review->id }})">
                                            <i class="fe fe-trash-2 me-2"></i>삭제하기
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="fe fe-edit display-4 text-muted"></i>
                        </div>
                        <h6 class="mb-1">작성한 리뷰가 없습니다</h6>
                        <p class="text-muted">완료된 프로젝트에 대해 리뷰를 작성해보세요.</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#writeReviewModal">
                            <i class="fe fe-plus me-1"></i>리뷰 작성하기
                        </button>
                    </div>
                    @endforelse

                    @if($givenReviews && method_exists($givenReviews, 'links'))
                        {{ $givenReviews->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Reviews Section -->
    @if($pendingProjects && count($pendingProjects) > 0)
    <div class="row mt-4" id="pendingReviews">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">리뷰 작성 대기 중인 프로젝트</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>프로젝트명</th>
                                    <th>상대방</th>
                                    <th>완료일</th>
                                    <th>대기일수</th>
                                    <th>작업</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingProjects as $project)
                                <tr>
                                    <td>{{ $project->title }}</td>
                                    <td>{{ $project->partner_name }}</td>
                                    <td>{{ $project->completed_at->format('Y-m-d') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $project->completed_at->diffInDays() > 7 ? 'danger' : 'warning' }}">
                                            {{ $project->completed_at->diffInDays() }}일
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" onclick="writeReviewForProject({{ $project->id }})">
                                            <i class="fe fe-edit me-1"></i>리뷰 작성
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Write Review Modal -->
<div class="modal fade" id="writeReviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">리뷰 작성</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="reviewForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">프로젝트 선택</label>
                        <select name="project_id" class="form-select" required>
                            <option value="">프로젝트를 선택하세요</option>
                            @foreach($availableProjects as $project)
                            <option value="{{ $project->id }}">{{ $project->title }} - {{ $project->partner_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">평점</label>
                        <div class="rating-input">
                            @for($i = 1; $i <= 5; $i++)
                            <input type="radio" name="rating" value="{{ $i }}" id="rating{{ $i }}" required>
                            <label for="rating{{ $i }}">
                                <i class="fe fe-star"></i>
                            </label>
                            @endfor
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">리뷰 내용</label>
                        <textarea name="comment" class="form-control" rows="4" placeholder="협업 경험과 만족도에 대해 작성해주세요..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_public" class="form-check-input" id="isPublic" checked>
                            <label class="form-check-label" for="isPublic">
                                공개 리뷰로 작성 (다른 사용자들이 볼 수 있습니다)
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="submit" name="action" value="draft" class="btn btn-outline-primary">임시저장</button>
                    <button type="submit" name="action" value="publish" class="btn btn-primary">게시하기</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.rating-input {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
}

.rating-input input[type=radio] {
    display: none;
}

.rating-input label {
    cursor: pointer;
    color: #ddd;
    font-size: 1.5rem;
    padding: 0 0.1rem;
}

.rating-input label:hover,
.rating-input label:hover ~ label,
.rating-input input[type=radio]:checked ~ label {
    color: #ffc107;
}
</style>
@endpush

@push('scripts')
<script>
// 리뷰 작성 폼 제출
document.getElementById('reviewForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const action = e.submitter.value;
    formData.append('status', action === 'publish' ? 'published' : 'draft');

    fetch('/home/partner/reviews/store', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('리뷰 작성에 실패했습니다: ' + (data.message || '알 수 없는 오류'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('오류가 발생했습니다.');
    });
});

// 특정 프로젝트에 대한 리뷰 작성
function writeReviewForProject(projectId) {
    const modal = new bootstrap.Modal(document.getElementById('writeReviewModal'));
    document.querySelector('select[name="project_id"]').value = projectId;
    modal.show();
}

// 리뷰 수정
function editReview(reviewId) {
    fetch(`/home/partner/reviews/${reviewId}/edit`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 모달에 기존 데이터 채우기
                const form = document.getElementById('reviewForm');
                form.querySelector('[name="project_id"]').value = data.review.project_id;
                form.querySelector(`[name="rating"][value="${data.review.rating}"]`).checked = true;
                form.querySelector('[name="comment"]').value = data.review.comment;
                form.querySelector('[name="is_public"]').checked = data.review.is_public;

                // 수정 모드로 설정
                form.dataset.reviewId = reviewId;
                form.dataset.mode = 'edit';

                const modal = new bootstrap.Modal(document.getElementById('writeReviewModal'));
                modal.show();
            }
        });
}

// 리뷰 게시
function publishReview(reviewId) {
    if (!confirm('이 리뷰를 게시하시겠습니까?')) return;

    fetch(`/home/partner/reviews/${reviewId}/publish`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('게시에 실패했습니다: ' + (data.message || '알 수 없는 오류'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('오류가 발생했습니다.');
    });
}

// 리뷰 삭제
function deleteReview(reviewId) {
    if (!confirm('이 리뷰를 삭제하시겠습니까? 삭제된 리뷰는 복구할 수 없습니다.')) return;

    fetch(`/home/partner/reviews/${reviewId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('삭제에 실패했습니다: ' + (data.message || '알 수 없는 오류'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('오류가 발생했습니다.');
    });
}
</script>
@endpush
