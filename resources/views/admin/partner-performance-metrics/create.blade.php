@extends('jiny-partner::layouts.admin.sidebar')

@section('title', '성과 지표 등록')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">📊 성과 지표 등록</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="/admin">관리자</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.partner.index') }}">파트너</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.partner.performance.metrics.index') }}">성과 지표</a></li>
                        <li class="breadcrumb-item active">등록</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.partner.performance.metrics.store') }}">
        @csrf
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
                                                    {{ (request('partner_id') == $p->id || (isset($partner) && $partner->id == $p->id)) ? 'selected' : '' }}>
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
                                        <option value="weekly" {{ old('period_type') == 'weekly' ? 'selected' : '' }}>주간</option>
                                        <option value="monthly" {{ old('period_type') == 'monthly' ? 'selected' : '' }}>월간</option>
                                        <option value="quarterly" {{ old('period_type') == 'quarterly' ? 'selected' : '' }}>분기</option>
                                        <option value="yearly" {{ old('period_type') == 'yearly' ? 'selected' : '' }}>연간</option>
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
                                           value="{{ old('period_start') }}" required>
                                    @error('period_start')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="period_end" class="form-label">종료일 <span class="text-danger">*</span></label>
                                    <input type="date" name="period_end" id="period_end" class="form-control"
                                           value="{{ old('period_end') }}" required>
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
                                           value="{{ old('total_sales', 0) }}" min="0" step="0.01">
                                    @error('total_sales')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="commission_earned" class="form-label">수수료 수익 (만원)</label>
                                    <input type="number" name="commission_earned" id="commission_earned" class="form-control"
                                           value="{{ old('commission_earned', 0) }}" min="0" step="0.01">
                                    @error('commission_earned')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="deals_closed" class="form-label">성사된 거래 (건)</label>
                                    <input type="number" name="deals_closed" id="deals_closed" class="form-control"
                                           value="{{ old('deals_closed', 0) }}" min="0">
                                    @error('deals_closed')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="average_deal_size" class="form-label">평균 거래 규모 (만원)</label>
                                    <input type="number" name="average_deal_size" id="average_deal_size" class="form-control"
                                           value="{{ old('average_deal_size', 0) }}" min="0" step="0.01">
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
                                           value="{{ old('leads_generated', 0) }}" min="0">
                                    @error('leads_generated')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customers_acquired" class="form-label">신규 고객 확보 (명)</label>
                                    <input type="number" name="customers_acquired" id="customers_acquired" class="form-control"
                                           value="{{ old('customers_acquired', 0) }}" min="0">
                                    @error('customers_acquired')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="support_tickets_resolved" class="form-label">지원 티켓 해결 (건)</label>
                                    <input type="number" name="support_tickets_resolved" id="support_tickets_resolved" class="form-control"
                                           value="{{ old('support_tickets_resolved', 0) }}" min="0">
                                    @error('support_tickets_resolved')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="training_sessions_conducted" class="form-label">교육 세션 진행 (회)</label>
                                    <input type="number" name="training_sessions_conducted" id="training_sessions_conducted" class="form-control"
                                           value="{{ old('training_sessions_conducted', 0) }}" min="0">
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
                                           value="{{ old('customer_satisfaction_score') }}" min="0" max="5" step="0.1">
                                    @error('customer_satisfaction_score')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="response_time_hours" class="form-label">평균 응답 시간 (시간)</label>
                                    <input type="number" name="response_time_hours" id="response_time_hours" class="form-control"
                                           value="{{ old('response_time_hours') }}" min="0" step="0.1">
                                    @error('response_time_hours')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="complaints_received" class="form-label">접수된 불만 (건)</label>
                                    <input type="number" name="complaints_received" id="complaints_received" class="form-control"
                                           value="{{ old('complaints_received', 0) }}" min="0">
                                    @error('complaints_received')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="task_completion_rate" class="form-label">작업 완료율 (%)</label>
                                    <input type="number" name="task_completion_rate" id="task_completion_rate" class="form-control"
                                           value="{{ old('task_completion_rate', 0) }}" min="0" max="100" step="0.1">
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
                                           value="{{ old('referrals_made', 0) }}" min="0">
                                    @error('referrals_made')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="team_members_managed" class="form-label">관리 팀원 (명)</label>
                                    <input type="number" name="team_members_managed" id="team_members_managed" class="form-control"
                                           value="{{ old('team_members_managed', 0) }}" min="0">
                                    @error('team_members_managed')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="team_performance_bonus" class="form-label">팀 성과 보너스 (만원)</label>
                                    <input type="number" name="team_performance_bonus" id="team_performance_bonus" class="form-control"
                                           value="{{ old('team_performance_bonus', 0) }}" min="0" step="0.01">
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
                                              placeholder='{"custom_kpis": {"client_retention_rate": 95.5}}'>{{ old('detailed_metrics') }}</textarea>
                                    @error('detailed_metrics')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="goals_vs_actual" class="form-label">목표 대비 실적 (JSON)</label>
                                    <textarea name="goals_vs_actual" id="goals_vs_actual" class="form-control" rows="6"
                                              placeholder='{"sales_target": {"goal": 50000, "actual": 47500}}'>{{ old('goals_vs_actual') }}</textarea>
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
                <!-- 미리보기 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">📋 미리보기</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">총 매출</label>
                            <div id="preview-total-sales" class="h4 text-primary">0만원</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">예상 효율성 점수</label>
                            <div id="preview-efficiency" class="h5 text-info">0</div>
                            <div class="progress">
                                <div id="preview-efficiency-bar" class="progress-bar" style="width: 0%"></div>
                            </div>
                            <small class="text-muted">매출 / (리드 + 고객 + 지원) × 100</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">평균 거래 규모</label>
                            <div id="preview-avg-deal" class="text-secondary">0만원</div>
                            <small class="text-muted">총 매출 ÷ 거래 건수</small>
                        </div>
                    </div>
                </div>

                <!-- 도움말 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">💡 도움말</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled small">
                            <li class="mb-2">• <strong>효율성 점수:</strong> 매출 대비 활동량을 나타내는 지표</li>
                            <li class="mb-2">• <strong>성장률:</strong> 이전 동일 기간 대비 증감률 자동 계산</li>
                            <li class="mb-2">• <strong>순위:</strong> 동일 기간 유형 내에서의 매출 순위</li>
                            <li class="mb-2">• <strong>JSON 데이터:</strong> 추가적인 사용자 정의 메트릭</li>
                        </ul>
                    </div>
                </div>

                <!-- 제출 버튼 -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fe fe-save"></i> 성과 지표 등록
                            </button>
                            <a href="{{ route('admin.partner.performance.metrics.index') }}" class="btn btn-secondary">
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

// 기간 유형 변경 시 종료일 자동 설정
document.getElementById('period_type').addEventListener('change', function() {
    const periodType = this.value;
    const startDate = document.getElementById('period_start').value;

    if (periodType && startDate) {
        const start = new Date(startDate);
        let end = new Date(start);

        switch(periodType) {
            case 'weekly':
                end.setDate(start.getDate() + 6);
                break;
            case 'monthly':
                end.setMonth(start.getMonth() + 1);
                end.setDate(end.getDate() - 1);
                break;
            case 'quarterly':
                end.setMonth(start.getMonth() + 3);
                end.setDate(end.getDate() - 1);
                break;
            case 'yearly':
                end.setFullYear(start.getFullYear() + 1);
                end.setDate(end.getDate() - 1);
                break;
        }

        document.getElementById('period_end').value = end.toISOString().split('T')[0];
    }
});

// 시작일 변경 시 종료일 자동 업데이트
document.getElementById('period_start').addEventListener('change', function() {
    document.getElementById('period_type').dispatchEvent(new Event('change'));
});
</script>
@endsection