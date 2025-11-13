@extends('jiny-partner::layouts.home')

@section('title', '파트너 신청 상세 검토')

@section('content')
    <div class="container-fluid p-4">
        <!-- 헤더 -->
        <section class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-2">
                            파트너 신청 상세 검토
                        </h2>
                        <p class="text-muted mb-0">
                            신청서 ID: #{{ $application->id }} | 신청자: {{ $reviewData['application_summary']['applicant_name'] }}
                        </p>
                    </div>
                    <div>
                        <a href="{{ route('home.partner.approval.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>승인 목록으로
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <div class="row">
            <!-- 메인 콘텐츠 -->
            <div class="col-lg-8">
                <!-- 신청자 기본 정보 -->
                <section class="card mb-4">
                    <div class="card-header bg-gradient-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-person-circle me-2"></i>신청자 정보
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="fw-bold text-primary mb-3">개인 정보</h6>
                                <div class="info-item mb-2">
                                    <span class="label">이름</span>
                                    <span class="value">{{ $reviewData['application_summary']['applicant_name'] }}</span>
                                </div>
                                <div class="info-item mb-2">
                                    <span class="label">이메일</span>
                                    <span class="value">{{ $reviewData['application_summary']['contact_info']['email'] }}</span>
                                </div>
                                <div class="info-item mb-2">
                                    <span class="label">연락처</span>
                                    <span class="value">{{ $reviewData['application_summary']['contact_info']['phone'] ?: '미제공' }}</span>
                                </div>
                                <div class="info-item mb-2">
                                    <span class="label">경력</span>
                                    <span class="value">{{ $reviewData['application_summary']['experience_years'] }}년</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold text-primary mb-3">신청 정보</h6>
                                <div class="info-item mb-2">
                                    <span class="label">희망 등급</span>
                                    <span class="badge bg-{{ $reviewData['application_summary']['target_tier'] === 'Bronze' ? 'secondary' : ($reviewData['application_summary']['target_tier'] === 'Silver' ? 'primary' : ($reviewData['application_summary']['target_tier'] === 'Gold' ? 'warning' : 'danger')) }}">
                                        {{ $reviewData['application_summary']['target_tier'] }}
                                    </span>
                                </div>
                                <div class="info-item mb-2">
                                    <span class="label">신청일</span>
                                    <span class="value">{{ $reviewData['application_summary']['application_date']->format('Y-m-d H:i') }}</span>
                                </div>
                                <div class="info-item mb-2">
                                    <span class="label">현재 상태</span>
                                    <span class="badge bg-{{ $application->application_status === 'submitted' ? 'warning' : ($application->application_status === 'reviewing' ? 'info' : 'primary') }}">
                                        @if($application->application_status === 'submitted') 접수완료
                                        @elseif($application->application_status === 'reviewing') 검토중
                                        @elseif($application->application_status === 'interview') 면접예정
                                        @else {{ $application->application_status }}
                                        @endif
                                    </span>
                                </div>
                                <div class="info-item mb-2">
                                    <span class="label">최근 업데이트</span>
                                    <span class="value">{{ $reviewData['application_summary']['last_updated']->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 경력 및 기술 정보 -->
                <section class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-briefcase me-2"></i>경력 및 기술
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="fw-bold text-primary mb-3">경력 정보</h6>
                                <div class="info-item mb-2">
                                    <span class="label">총 경력</span>
                                    <span class="value fw-bold text-success">{{ $application->experience_info['total_years'] ?? 0 }}년</span>
                                </div>
                                <div class="info-item mb-2">
                                    <span class="label">현재 직책</span>
                                    <span class="value">{{ $application->experience_info['current_position'] ?? '미제공' }}</span>
                                </div>
                                @if(!empty($application->experience_info['achievements']))
                                <div class="mt-3">
                                    <h6 class="small fw-bold mb-2">주요 성과</h6>
                                    <ul class="small text-muted">
                                        @foreach(array_slice($application->experience_info['achievements'], 0, 3) as $achievement)
                                        <li>{{ $achievement }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold text-primary mb-3">보유 기술</h6>
                                @if(!empty($reviewData['application_summary']['primary_skills']))
                                <div class="d-flex flex-wrap gap-1 mb-3">
                                    @foreach($reviewData['application_summary']['primary_skills'] as $skill)
                                    <span class="badge bg-light text-dark">{{ $skill }}</span>
                                    @endforeach
                                </div>
                                @else
                                <p class="text-muted small">기술 정보가 제공되지 않았습니다.</p>
                                @endif

                                @if(!empty($application->experience_info['certifications']))
                                <div class="mt-3">
                                    <h6 class="small fw-bold mb-2">자격증</h6>
                                    <ul class="small text-muted">
                                        @foreach(array_slice($application->experience_info['certifications'], 0, 3) as $cert)
                                        <li>{{ $cert }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 지원 동기 및 목표 -->
                @if($application->motivation || $application->goals)
                <section class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-lightbulb me-2"></i>지원 동기 및 목표
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($application->motivation)
                        <div class="mb-3">
                            <h6 class="fw-bold text-primary mb-2">지원 동기</h6>
                            <p class="text-muted">{{ $application->motivation }}</p>
                        </div>
                        @endif
                        @if($application->goals)
                        <div>
                            <h6 class="fw-bold text-primary mb-2">목표</h6>
                            <ul class="text-muted">
                                @if(is_array($application->goals))
                                    @foreach($application->goals as $goal)
                                    <li>{{ $goal }}</li>
                                    @endforeach
                                @else
                                    <li>{{ $application->goals }}</li>
                                @endif
                            </ul>
                        </div>
                        @endif
                    </div>
                </section>
                @endif

                <!-- 문서 첨부 현황 -->
                <section class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-paperclip me-2"></i>첨부 문서
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($reviewData['document_status'] as $docType => $status)
                            <div class="col-md-4 mb-3">
                                <div class="document-item p-3 border rounded">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-{{ $status['submitted'] ? 'check-circle text-success' : 'x-circle text-danger' }} me-2"></i>
                                        <div>
                                            <div class="fw-bold">{{ ucfirst($docType) }}</div>
                                            <small class="text-muted">
                                                @if($status['submitted'])
                                                    {{ number_format($status['file_size'] / 1024, 1) }} KB
                                                @else
                                                    미제출
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            </div>

            <!-- 사이드바 -->
            <div class="col-lg-4">
                <!-- 승인 추천 -->
                <section class="card mb-4">
                    <div class="card-header bg-gradient-info text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-lightbulb me-2"></i>AI 추천
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="recommendation-score mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold">종합 점수</span>
                                <span class="badge bg-{{ $reviewData['approval_recommendation']['score'] >= 70 ? 'success' : ($reviewData['approval_recommendation']['score'] >= 50 ? 'warning' : 'danger') }} fs-6">
                                    {{ $reviewData['approval_recommendation']['score'] }}/100
                                </span>
                            </div>
                            <div class="progress mb-2">
                                <div class="progress-bar bg-{{ $reviewData['approval_recommendation']['score'] >= 70 ? 'success' : ($reviewData['approval_recommendation']['score'] >= 50 ? 'warning' : 'danger') }}"
                                     role="progressbar"
                                     style="width: {{ $reviewData['approval_recommendation']['score'] }}%">
                                </div>
                            </div>
                        </div>

                        <div class="recommendation-result mb-3">
                            <span class="fw-bold">추천 결과: </span>
                            <span class="badge bg-{{ $reviewData['approval_recommendation']['recommendation'] === 'approve' ? 'success' : ($reviewData['approval_recommendation']['recommendation'] === 'interview' ? 'warning' : 'danger') }}">
                                @if($reviewData['approval_recommendation']['recommendation'] === 'approve') 승인 권장
                                @elseif($reviewData['approval_recommendation']['recommendation'] === 'interview') 면접 권장
                                @else 보류 권장
                                @endif
                            </span>
                        </div>

                        <div class="recommendation-reasons">
                            <h6 class="small fw-bold mb-2">평가 근거</h6>
                            <ul class="small text-muted">
                                @foreach($reviewData['approval_recommendation']['reasons'] as $reason)
                                <li>{{ $reason }}</li>
                                @endforeach
                            </ul>
                        </div>

                        <!-- 등급 적합성 -->
                        <div class="tier-analysis mt-3">
                            <h6 class="small fw-bold mb-2">등급 적합성</h6>
                            <div class="small">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>현재 신청: {{ $reviewData['tier_analysis']['target_tier'] }}</span>
                                    <span>추천: {{ $reviewData['tier_analysis']['best_match'] }}</span>
                                </div>
                                @if($reviewData['tier_analysis']['target_tier'] !== $reviewData['tier_analysis']['best_match'])
                                <div class="alert alert-warning py-1 px-2 small">
                                    {{ $reviewData['tier_analysis']['best_match'] }} 등급이 더 적합합니다.
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 추천인 정보 -->
                @if($reviewData['referrer_info'])
                <section class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-person-check me-2"></i>추천인 정보
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="referrer-info">
                            <div class="info-item mb-2">
                                <span class="label">이름</span>
                                <span class="value fw-bold">{{ $reviewData['referrer_info']['name'] }}</span>
                            </div>
                            <div class="info-item mb-2">
                                <span class="label">등급</span>
                                <span class="badge bg-{{ $reviewData['referrer_info']['tier'] === 'Bronze' ? 'secondary' : ($reviewData['referrer_info']['tier'] === 'Silver' ? 'primary' : ($reviewData['referrer_info']['tier'] === 'Gold' ? 'warning' : 'danger')) }}">
                                    {{ $reviewData['referrer_info']['tier'] }}
                                </span>
                            </div>
                            <div class="info-item mb-2">
                                <span class="label">총 추천</span>
                                <span class="value">{{ $reviewData['referrer_info']['total_referrals'] }}명</span>
                            </div>
                            <div class="info-item mb-2">
                                <span class="label">활성 파트너</span>
                                <span class="value text-success">{{ $reviewData['referrer_info']['active_partners'] }}명</span>
                            </div>
                        </div>
                    </div>
                </section>
                @endif

                <!-- 액션 버튼 -->
                <section class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-gear me-2"></i>검토 액션
                        </h5>
                    </div>
                    <div class="card-body">
                        @if(in_array('approve', $reviewData['approval_actions']))
                        <button type="button"
                                onclick="handleApproval({{ $application->id }}, 'approve')"
                                class="btn btn-success w-100 mb-2">
                            <i class="bi bi-check-lg me-2"></i>승인하기
                        </button>
                        @endif

                        @if(in_array('request_interview', $reviewData['approval_actions']))
                        <button type="button"
                                onclick="handleApproval({{ $application->id }}, 'interview')"
                                class="btn btn-warning w-100 mb-2">
                            <i class="bi bi-calendar-check me-2"></i>면접 요청
                        </button>
                        @endif

                        @if(in_array('reject', $reviewData['approval_actions']))
                        <button type="button"
                                onclick="handleApproval({{ $application->id }}, 'reject')"
                                class="btn btn-outline-danger w-100 mb-2">
                            <i class="bi bi-x-lg me-2"></i>반려하기
                        </button>
                        @endif

                        <hr>

                        <div class="d-grid gap-2">
                            <button type="button"
                                    onclick="addComment({{ $application->id }})"
                                    class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-chat-text me-1"></i>댓글 추가
                            </button>
                            <button type="button"
                                    onclick="printApplication()"
                                    class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-printer me-1"></i>인쇄하기
                            </button>
                        </div>
                    </div>
                </section>

                <!-- 권한 정보 -->
                <section class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0 text-muted">
                            <i class="bi bi-shield-check me-1"></i>승인 권한 현황
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="small text-muted">
                            <div class="d-flex justify-content-between mb-1">
                                <span>월 승인 한도</span>
                                <span>{{ $permissions['current_month_approvals'] }}/{{ $permissions['monthly_limit'] }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>관리 가능</span>
                                <span>{{ $permissions['total_managing'] }}/{{ $permissions['max_managing'] }}</span>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <style>
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.25rem 0;
            border-bottom: 1px solid #f8f9fa;
        }

        .info-item .label {
            font-weight: 500;
            color: #6c757d;
            min-width: 80px;
        }

        .info-item .value {
            flex-grow: 1;
            text-align: right;
        }

        .document-item {
            transition: all 0.2s ease;
        }

        .document-item:hover {
            background-color: #f8f9fa;
        }

        .recommendation-score .progress {
            height: 8px;
        }

        .card-header.bg-gradient-primary {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        }

        .card-header.bg-gradient-info {
            background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%);
        }

        .btn-action {
            min-height: 45px;
            font-weight: 600;
        }

        .badge {
            font-size: 0.8rem;
        }

        /* 반응형 */
        @media (max-width: 768px) {
            .info-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .info-item .value {
                text-align: left;
                margin-top: 0.25rem;
            }
        }

        /* 로딩 및 처리 상태 */
        .btn.processing {
            pointer-events: none;
            opacity: 0.6;
        }

        .btn.processing .spinner-border {
            width: 1rem;
            height: 1rem;
        }
    </style>
@endsection

@push('scripts')
    <script>
        // 승인 처리
        function handleApproval(applicationId, action) {
            let title, message, url;

            switch(action) {
                case 'approve':
                    title = '신청서 승인';
                    message = '이 신청서를 승인하시겠습니까?';
                    url = `/home/partner/approval/${applicationId}/approve`;
                    break;
                case 'reject':
                    title = '신청서 반려';
                    message = '이 신청서를 반려하시겠습니까?';
                    url = `/home/partner/approval/${applicationId}/reject`;
                    break;
                case 'interview':
                    title = '면접 요청';
                    message = '이 신청자에게 면접을 요청하시겠습니까?';
                    url = `/home/partner/approval/${applicationId}/interview`;
                    break;
            }

            if (confirm(`${title}\n\n${message}`)) {
                const button = event.target.closest('button');
                const originalHtml = button.innerHTML;

                // 로딩 상태
                button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>처리중...';
                button.classList.add('processing');

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        action: action,
                        comments: `파트너 승인 관리에서 ${action} 처리`
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`${title}이(가) 완료되었습니다.`);
                        window.location.href = '/home/partner/approval';
                    } else {
                        alert(data.message || `${title} 처리 중 오류가 발생했습니다.`);
                        button.innerHTML = originalHtml;
                        button.classList.remove('processing');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('네트워크 오류가 발생했습니다.');
                    button.innerHTML = originalHtml;
                    button.classList.remove('processing');
                });
            }
        }

        // 댓글 추가
        function addComment(applicationId) {
            const comment = prompt('댓글을 입력해주세요:');
            if (comment && comment.trim()) {
                // TODO: 댓글 추가 API 구현
                console.log('Adding comment:', comment);
                alert('댓글이 추가되었습니다.');
            }
        }

        // 인쇄
        function printApplication() {
            window.print();
        }

        // 페이지 로드시 초기화
        document.addEventListener('DOMContentLoaded', function() {
            // 툴팁 초기화
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
        });
    </script>
@endpush