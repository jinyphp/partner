@extends('jiny-partner::layouts.admin.sidebar')

@section('title', '성과 지표 상세')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">📊 성과 지표 상세</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="/admin">관리자</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.partner.index') }}">파트너</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.partner.performance.metrics.index') }}">성과 지표</a></li>
                        <li class="breadcrumb-item active">상세</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- 기본 정보 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">📋 기본 정보</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">파트너</label>
                                <div>{{ $metric->partner_name }}</div>
                                <small class="text-muted">{{ $metric->partner_email }}</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">등급</label>
                                <div>
                                    @if($metric->tier_name)
                                        <span class="badge bg-primary fs-6">{{ $metric->tier_name }} ({{ $metric->tier_level }}급)</span>
                                    @else
                                        <span class="text-muted">등급 없음</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">측정 기간</label>
                                <div>{{ date('Y년 m월 d일', strtotime($metric->period_start)) }} - {{ date('Y년 m월 d일', strtotime($metric->period_end)) }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">기간 유형</label>
                                <div>
                                    @switch($metric->period_type)
                                        @case('weekly')
                                            <span class="badge bg-info fs-6">🗓️ 주간</span>
                                            @break
                                        @case('monthly')
                                            <span class="badge bg-primary fs-6">📅 월간</span>
                                            @break
                                        @case('quarterly')
                                            <span class="badge bg-warning fs-6">📊 분기</span>
                                            @break
                                        @case('yearly')
                                            <span class="badge bg-success fs-6">🎯 연간</span>
                                            @break
                                    @endswitch
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 매출 메트릭 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">💰 매출 메트릭</h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="text-center p-3 bg-light rounded">
                                <h3 class="text-primary mb-2">{{ number_format($metric->total_sales, 0) }}만원</h3>
                                <p class="text-muted mb-0">총 매출</p>
                                @if($metric->growth_rate != 0)
                                    <small class="badge {{ $metric->growth_rate > 0 ? 'bg-success' : 'bg-danger' }}">
                                        {{ $metric->growth_rate > 0 ? '+' : '' }}{{ $metric->growth_rate }}%
                                    </small>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-center p-3 bg-light rounded">
                                <h3 class="text-success mb-2">{{ number_format($metric->commission_earned, 0) }}만원</h3>
                                <p class="text-muted mb-0">수수료 수익</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-center p-3 bg-light rounded">
                                <h3 class="text-info mb-2">{{ number_format($metric->deals_closed) }}건</h3>
                                <p class="text-muted mb-0">성사된 거래</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-center p-3 bg-light rounded">
                                <h3 class="text-warning mb-2">{{ number_format($metric->average_deal_size, 0) }}만원</h3>
                                <p class="text-muted mb-0">평균 거래 규모</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 활동 메트릭 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">🚀 활동 메트릭</h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="avatar avatar-md bg-primary-subtle rounded">
                                        <i class="fe fe-target text-primary"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="mb-1">{{ number_format($metric->leads_generated) }}개</h5>
                                    <p class="text-muted mb-0">생성된 리드</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="avatar avatar-md bg-success-subtle rounded">
                                        <i class="fe fe-user-plus text-success"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="mb-1">{{ number_format($metric->customers_acquired) }}명</h5>
                                    <p class="text-muted mb-0">신규 고객 확보</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="avatar avatar-md bg-info-subtle rounded">
                                        <i class="fe fe-help-circle text-info"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="mb-1">{{ number_format($metric->support_tickets_resolved) }}건</h5>
                                    <p class="text-muted mb-0">지원 티켓 해결</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="avatar avatar-md bg-warning-subtle rounded">
                                        <i class="fe fe-book text-warning"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="mb-1">{{ number_format($metric->training_sessions_conducted) }}회</h5>
                                    <p class="text-muted mb-0">교육 세션 진행</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 품질 메트릭 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">⭐ 품질 메트릭</h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="text-center">
                                <h4 class="mb-2">
                                    {{ $metric->customer_satisfaction_score ?? '미측정' }}
                                    @if($metric->customer_satisfaction_score)
                                        <small class="text-muted">/5.0</small>
                                    @endif
                                </h4>
                                <p class="text-muted mb-2">고객 만족도</p>
                                @if($metric->customer_satisfaction_score)
                                    <div class="rating-stars">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= $metric->customer_satisfaction_score)
                                                <i class="fe fe-star text-warning fs-16"></i>
                                            @else
                                                <i class="fe fe-star text-muted fs-16"></i>
                                            @endif
                                        @endfor
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-center">
                                <h4 class="mb-2">{{ $metric->response_time_hours ? number_format($metric->response_time_hours, 1) . '시간' : '미측정' }}</h4>
                                <p class="text-muted mb-0">평균 응답 시간</p>
                                @if($metric->response_time_hours)
                                    <small class="badge {{ $metric->response_time_hours <= 24 ? 'bg-success' : 'bg-warning' }}">
                                        {{ $metric->response_time_hours <= 24 ? '우수' : '개선 필요' }}
                                    </small>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-center">
                                <h4 class="mb-2">{{ number_format($metric->complaints_received) }}건</h4>
                                <p class="text-muted mb-0">접수된 불만</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-center">
                                <h4 class="mb-2">{{ number_format($metric->task_completion_rate, 1) }}%</h4>
                                <p class="text-muted mb-0">작업 완료율</p>
                                <div class="progress mt-2" style="height: 8px;">
                                    <div class="progress-bar {{ $metric->task_completion_rate >= 95 ? 'bg-success' : ($metric->task_completion_rate >= 80 ? 'bg-warning' : 'bg-danger') }}"
                                         style="width: {{ $metric->task_completion_rate }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 네트워크 메트릭 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">🌐 네트워크 메트릭</h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="text-center p-3 border rounded">
                                <h4 class="text-primary mb-2">{{ number_format($metric->referrals_made) }}</h4>
                                <p class="text-muted mb-0">추천한 파트너</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3 border rounded">
                                <h4 class="text-success mb-2">{{ number_format($metric->team_members_managed) }}</h4>
                                <p class="text-muted mb-0">관리 팀원</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3 border rounded">
                                <h4 class="text-info mb-2">{{ number_format($metric->team_performance_bonus, 0) }}만원</h4>
                                <p class="text-muted mb-0">팀 성과 보너스</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 상세 메트릭 -->
            @if(count($metric->detailed_metrics) > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">📊 상세 메트릭</h5>
                </div>
                <div class="card-body">
                    <pre class="bg-light p-3 rounded"><code>{{ json_encode($metric->detailed_metrics, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                </div>
            </div>
            @endif

            <!-- 목표 대비 실적 -->
            @if(count($metric->goals_vs_actual) > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">🎯 목표 대비 실적</h5>
                </div>
                <div class="card-body">
                    @foreach($metric->goals_vs_actual as $category => $data)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold">{{ $data['label'] ?? $category }}</span>
                                <span class="badge {{ ($data['achievement_rate'] ?? 0) >= 100 ? 'bg-success' : 'bg-warning' }}">
                                    {{ number_format($data['achievement_rate'] ?? 0, 1) }}%
                                </span>
                            </div>
                            <div class="row">
                                <div class="col-4">
                                    <small class="text-muted">목표: {{ number_format($data['goal'] ?? 0) }}</small>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted">실제: {{ number_format($data['actual'] ?? 0) }}</small>
                                </div>
                                <div class="col-4">
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar" style="width: {{ min(($data['achievement_rate'] ?? 0), 100) }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            <!-- 종합 평가 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">🏆 종합 평가</h5>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <h2 class="text-primary">{{ $metric->efficiency_score ?? '미계산' }}</h2>
                        <p class="text-muted">효율성 점수</p>
                        @if($metric->efficiency_score)
                            <div class="progress mx-auto" style="width: 200px; height: 10px;">
                                <div class="progress-bar {{ $metric->efficiency_score > 100 ? 'bg-success' : ($metric->efficiency_score > 50 ? 'bg-warning' : 'bg-danger') }}"
                                     style="width: {{ min($metric->efficiency_score, 100) }}%"></div>
                            </div>
                        @endif
                    </div>

                    @if($metric->rank_in_tier)
                        <div class="mb-3">
                            <h3 class="text-info">{{ $metric->rank_in_tier }}위</h3>
                            <p class="text-muted">등급 내 순위</p>
                        </div>
                    @endif

                    @if($comparison)
                        <hr>
                        <div class="small text-muted">
                            <div>평균 매출: {{ number_format($comparison->avg_sales, 0) }}만원</div>
                            <div>최고 매출: {{ number_format($comparison->max_sales, 0) }}만원</div>
                            <div>비교 대상: {{ $comparison->total_partners }}명</div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- 이전 기간 비교 -->
            @if($previousMetric)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">📈 이전 기간 비교</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h6>이전 기간</h6>
                            <div class="text-muted">{{ number_format($previousMetric->total_sales, 0) }}만원</div>
                        </div>
                        <div class="col-6">
                            <h6>현재 기간</h6>
                            <div class="text-primary">{{ number_format($metric->total_sales, 0) }}만원</div>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        @php
                            $changeAmount = $metric->total_sales - $previousMetric->total_sales;
                            $changePercent = $previousMetric->total_sales > 0 ? ($changeAmount / $previousMetric->total_sales) * 100 : 0;
                        @endphp
                        @if($changeAmount > 0)
                            <span class="text-success">
                                <i class="fe fe-trending-up"></i>
                                +{{ number_format($changeAmount, 0) }}만원 (+{{ number_format($changePercent, 1) }}%)
                            </span>
                        @elseif($changeAmount < 0)
                            <span class="text-danger">
                                <i class="fe fe-trending-down"></i>
                                {{ number_format($changeAmount, 0) }}만원 ({{ number_format($changePercent, 1) }}%)
                            </span>
                        @else
                            <span class="text-muted">변화 없음</span>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- 액션 버튼 -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">⚡ 액션</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.partner.performance.metrics.edit', $metric->id) }}"
                           class="btn btn-warning">
                            <i class="fe fe-edit"></i> 성과 지표 수정
                        </a>
                        <a href="{{ route('admin.partner.performance.metrics.index') }}"
                           class="btn btn-secondary">
                            <i class="fe fe-list"></i> 목록으로
                        </a>
                        <button type="button" class="btn btn-danger"
                                onclick="deleteMetric({{ $metric->id }})">
                            <i class="fe fe-trash"></i> 성과 지표 삭제
                        </button>
                    </div>
                </div>
            </div>

            <!-- 정보 -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">ℹ️ 정보</h5>
                </div>
                <div class="card-body">
                    <div class="small">
                        <div class="mb-2">
                            <strong>지표 ID:</strong> {{ $metric->id }}
                        </div>
                        <div class="mb-2">
                            <strong>등록일:</strong> {{ date('Y-m-d H:i', strtotime($metric->created_at)) }}
                        </div>
                        <div class="mb-2">
                            <strong>수정일:</strong> {{ date('Y-m-d H:i', strtotime($metric->updated_at)) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 삭제 확인 모달 -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">성과 지표 삭제 확인</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                정말로 이 성과 지표를 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.
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

<script>
function deleteMetric(id) {
    const deleteForm = document.getElementById('deleteForm');
    deleteForm.action = `/admin/partner/performance/metrics/${id}`;

    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}
</script>
@endsection