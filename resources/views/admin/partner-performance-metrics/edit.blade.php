@extends('jiny-partner::layouts.admin.sidebar')

@section('title', '성과 지표 수정')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">📊 성과 지표 수정</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="/admin">관리자</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.partner.index') }}">파트너</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.partner.performance.metrics.index') }}">성과 지표</a></li>
                        <li class="breadcrumb-item active">수정</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.partner.performance.metrics.update', $metric->id) }}">
        @csrf
        @method('PUT')
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
                                    <label for="partner_id" class="form-label">파트너 <span class="text-danger">*</span></label>
                                    <select name="partner_id" id="partner_id" class="form-select" required>
                                        <option value="">파트너를 선택하세요</option>
                                        @foreach($partners as $p)
                                            <option value="{{ $p->id }}"
                                                    {{ (old('partner_id', $metric->partner_id) == $p->id) ? 'selected' : '' }}>
                                                {{ $p->name }} ({{ $p->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('partner_id')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="period_type" class="form-label">기간 유형 <span class="text-danger">*</span></label>
                                    <select name="period_type" id="period_type" class="form-select" required>
                                        <option value="">기간 유형을 선택하세요</option>
                                        <option value="weekly" {{ old('period_type', $metric->period_type) == 'weekly' ? 'selected' : '' }}>주간</option>
                                        <option value="monthly" {{ old('period_type', $metric->period_type) == 'monthly' ? 'selected' : '' }}>월간</option>
                                        <option value="quarterly" {{ old('period_type', $metric->period_type) == 'quarterly' ? 'selected' : '' }}>분기</option>
                                        <option value="yearly" {{ old('period_type', $metric->period_type) == 'yearly' ? 'selected' : '' }}>연간</option>
                                    </select>
                                    @error('period_type')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="period_start" class="form-label">시작일 <span class="text-danger">*</span></label>
                                    <input type="date" name="period_start" id="period_start" class="form-control"
                                           value="{{ old('period_start', $metric->period_start) }}" required>
                                    @error('period_start')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="period_end" class="form-label">종료일 <span class="text-danger">*</span></label>
                                    <input type="date" name="period_end" id="period_end" class="form-control"
                                           value="{{ old('period_end', $metric->period_end) }}" required>
                                    @error('period_end')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
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
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="total_sales" class="form-label">총 매출 (만원)</label>
                                    <input type="number" name="total_sales" id="total_sales" class="form-control"
                                           value="{{ old('total_sales', $metric->total_sales) }}" min="0" step="0.01">
                                    @error('total_sales')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="commission_earned" class="form-label">수수료 수익 (만원)</label>
                                    <input type="number" name="commission_earned" id="commission_earned" class="form-control"
                                           value="{{ old('commission_earned', $metric->commission_earned) }}" min="0" step="0.01">
                                    @error('commission_earned')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="deals_closed" class="form-label">성사된 거래 (건)</label>
                                    <input type="number" name="deals_closed" id="deals_closed" class="form-control"
                                           value="{{ old('deals_closed', $metric->deals_closed) }}" min="0">
                                    @error('deals_closed')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="average_deal_size" class="form-label">평균 거래 규모 (만원)</label>
                                    <input type="number" name="average_deal_size" id="average_deal_size" class="form-control"
                                           value="{{ old('average_deal_size', $metric->average_deal_size) }}" min="0" step="0.01">
                                    @error('average_deal_size')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
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
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="leads_generated" class="form-label">생성된 리드 (개)</label>
                                    <input type="number" name="leads_generated" id="leads_generated" class="form-control"
                                           value="{{ old('leads_generated', $metric->leads_generated) }}" min="0">
                                    @error('leads_generated')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customers_acquired" class="form-label">신규 고객 확보 (명)</label>
                                    <input type="number" name="customers_acquired" id="customers_acquired" class="form-control"
                                           value="{{ old('customers_acquired', $metric->customers_acquired) }}" min="0">
                                    @error('customers_acquired')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="support_tickets_resolved" class="form-label">지원 티켓 해결 (건)</label>
                                    <input type="number" name="support_tickets_resolved" id="support_tickets_resolved" class="form-control"
                                           value="{{ old('support_tickets_resolved', $metric->support_tickets_resolved) }}" min="0">
                                    @error('support_tickets_resolved')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="training_sessions_conducted" class="form-label">교육 세션 진행 (회)</label>
                                    <input type="number" name="training_sessions_conducted" id="training_sessions_conducted" class="form-control"
                                           value="{{ old('training_sessions_conducted', $metric->training_sessions_conducted) }}" min="0">
                                    @error('training_sessions_conducted')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
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
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customer_satisfaction_score" class="form-label">고객 만족도 점수 (0-5)</label>
                                    <input type="number" name="customer_satisfaction_score" id="customer_satisfaction_score" class="form-control"
                                           value="{{ old('customer_satisfaction_score', $metric->customer_satisfaction_score) }}" min="0" max="5" step="0.1">
                                    @error('customer_satisfaction_score')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="response_time_hours" class="form-label">평균 응답 시간 (시간)</label>
                                    <input type="number" name="response_time_hours" id="response_time_hours" class="form-control"
                                           value="{{ old('response_time_hours', $metric->response_time_hours) }}" min="0" step="0.1">
                                    @error('response_time_hours')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="complaints_received" class="form-label">접수된 불만 (건)</label>
                                    <input type="number" name="complaints_received" id="complaints_received" class="form-control"
                                           value="{{ old('complaints_received', $metric->complaints_received) }}" min="0">
                                    @error('complaints_received')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="task_completion_rate" class="form-label">작업 완료율 (%)</label>
                                    <input type="number" name="task_completion_rate" id="task_completion_rate" class="form-control"
                                           value="{{ old('task_completion_rate', $metric->task_completion_rate) }}" min="0" max="100" step="0.1">
                                    @error('task_completion_rate')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
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
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="referrals_made" class="form-label">추천한 파트너 (명)</label>
                                    <input type="number" name="referrals_made" id="referrals_made" class="form-control"
                                           value="{{ old('referrals_made', $metric->referrals_made) }}" min="0">
                                    @error('referrals_made')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="team_members_managed" class="form-label">관리 팀원 (명)</label>
                                    <input type="number" name="team_members_managed" id="team_members_managed" class="form-control"
                                           value="{{ old('team_members_managed', $metric->team_members_managed) }}" min="0">
                                    @error('team_members_managed')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="team_performance_bonus" class="form-label">팀 성과 보너스 (만원)</label>
                                    <input type="number" name="team_performance_bonus" id="team_performance_bonus" class="form-control"
                                           value="{{ old('team_performance_bonus', $metric->team_performance_bonus) }}" min="0" step="0.01">
                                    @error('team_performance_bonus')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- JSON 데이터 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">📊 확장 데이터 (선택사항)</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="detailed_metrics" class="form-label">상세 메트릭 (JSON)</label>
                                    <textarea name="detailed_metrics" id="detailed_metrics" class="form-control" rows="6"
                                              placeholder='{"custom_kpis": {"client_retention_rate": 95.5}}'>{{ old('detailed_metrics', $metric->detailed_metrics) }}</textarea>
                                    @error('detailed_metrics')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="goals_vs_actual" class="form-label">목표 대비 실적 (JSON)</label>
                                    <textarea name="goals_vs_actual" id="goals_vs_actual" class="form-control" rows="6"
                                              placeholder='{"sales_target": {"goal": 50000, "actual": 47500}}'>{{ old('goals_vs_actual', $metric->goals_vs_actual) }}</textarea>
                                    @error('goals_vs_actual')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- 현재 계산된 값 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">📊 현재 값</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">효율성 점수</label>
                            <div class="h4 text-primary">{{ $metric->efficiency_score ?? 0 }}</div>
                            <small class="text-muted">현재 저장된 값</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">성장률</label>
                            <div class="h5 {{ $metric->growth_rate > 0 ? 'text-success' : ($metric->growth_rate < 0 ? 'text-danger' : 'text-muted') }}">
                                {{ $metric->growth_rate ?? 0 }}%
                            </div>
                            <small class="text-muted">전기 대비</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">등급 내 순위</label>
                            <div class="text-info">{{ $metric->rank_in_tier ? $metric->rank_in_tier . '위' : '미계산' }}</div>
                        </div>
                    </div>
                </div>

                <!-- 실시간 미리보기 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">📋 실시간 미리보기</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">총 매출</label>
                            <div id="preview-total-sales" class="h4 text-primary">{{ number_format($metric->total_sales, 0) }}만원</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">예상 효율성 점수</label>
                            <div id="preview-efficiency" class="h5 text-info">{{ $metric->efficiency_score ?? 0 }}</div>
                            <div class="progress">
                                <div id="preview-efficiency-bar" class="progress-bar" style="width: {{ min($metric->efficiency_score ?? 0, 100) }}%"></div>
                            </div>
                            <small class="text-muted">매출 / (리드 + 고객 + 지원) × 100</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">평균 거래 규모</label>
                            <div id="preview-avg-deal" class="text-secondary">{{ number_format($metric->average_deal_size, 0) }}만원</div>
                            <small class="text-muted">총 매출 ÷ 거래 건수</small>
                        </div>
                    </div>
                </div>

                <!-- 수정 기록 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">📝 수정 기록</h5>
                    </div>
                    <div class="card-body">
                        <div class="small">
                            <div class="mb-2">
                                <strong>등록일:</strong><br>
                                {{ date('Y년 m월 d일 H:i', strtotime($metric->created_at)) }}
                            </div>
                            <div class="mb-2">
                                <strong>최종 수정:</strong><br>
                                {{ date('Y년 m월 d일 H:i', strtotime($metric->updated_at)) }}
                            </div>
                            <div class="mb-2">
                                <strong>대상 파트너:</strong><br>
                                {{ $metric->partner_name }}<br>
                                <small class="text-muted">{{ $metric->partner_email }}</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 제출 버튼 -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fe fe-save"></i> 성과 지표 수정
                            </button>
                            <a href="{{ route('admin.partner.performance.metrics.show', $metric->id) }}" class="btn btn-secondary">
                                <i class="fe fe-eye"></i> 상세보기
                            </a>
                            <a href="{{ route('admin.partner.performance.metrics.index') }}" class="btn btn-outline-secondary">
                                <i class="fe fe-x"></i> 취소
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// 실시간 미리보기 업데이트
function updatePreview() {
    const totalSales = parseFloat(document.getElementById('total_sales').value) || 0;
    const leadsGenerated = parseFloat(document.getElementById('leads_generated').value) || 0;
    const customersAcquired = parseFloat(document.getElementById('customers_acquired').value) || 0;
    const supportTicketsResolved = parseFloat(document.getElementById('support_tickets_resolved').value) || 0;
    const dealsClosed = parseFloat(document.getElementById('deals_closed').value) || 0;

    // 총 매출 업데이트
    document.getElementById('preview-total-sales').textContent = totalSales.toLocaleString() + '만원';

    // 효율성 점수 계산
    const totalActivities = leadsGenerated + customersAcquired + supportTicketsResolved;
    const efficiency = totalActivities > 0 ? Math.round((totalSales / totalActivities) * 100) / 100 : 0;
    document.getElementById('preview-efficiency').textContent = efficiency;

    const efficiencyPercent = Math.min(efficiency, 100);
    document.getElementById('preview-efficiency-bar').style.width = efficiencyPercent + '%';

    if (efficiency > 100) {
        document.getElementById('preview-efficiency-bar').className = 'progress-bar bg-success';
    } else if (efficiency > 50) {
        document.getElementById('preview-efficiency-bar').className = 'progress-bar bg-warning';
    } else {
        document.getElementById('preview-efficiency-bar').className = 'progress-bar bg-danger';
    }

    // 평균 거래 규모 계산
    const avgDeal = dealsClosed > 0 ? Math.round((totalSales / dealsClosed) * 100) / 100 : 0;
    document.getElementById('preview-avg-deal').textContent = avgDeal.toLocaleString() + '만원';
}

// 입력 필드 변경 시 미리보기 업데이트
document.addEventListener('DOMContentLoaded', function() {
    const inputFields = ['total_sales', 'leads_generated', 'customers_acquired', 'support_tickets_resolved', 'deals_closed'];

    inputFields.forEach(fieldId => {
        document.getElementById(fieldId).addEventListener('input', updatePreview);
    });

    // 초기 미리보기 업데이트
    updatePreview();
});
</script>
@endsection