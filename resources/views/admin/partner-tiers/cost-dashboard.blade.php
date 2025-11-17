@extends('jiny-partner::layouts.admin.sidebar')

@section('title', $title)

@section('content')
<div class="container-fluid">
    <!-- 헤더 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">{{ $title }}</h2>
                    <p class="text-muted mb-0">파트너 등급별 비용 구조와 수수료를 시뮬레이션합니다</p>
                </div>
                <div>
                    <a href="{{ route('admin.partner.tiers.index') }}" class="btn btn-outline-secondary">
                        <i class="fe fe-arrow-left me-2"></i>등급 관리
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 계산 설정 -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">계산 설정</h5>
        </div>
        <div class="card-body">
            <form id="calculationForm">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="tier_id" class="form-label">파트너 등급</label>
                            <select id="tier_id" name="tier_id" class="form-select">
                                <option value="">등급을 선택하세요</option>
                                @foreach($tiers as $tier)
                                    <option value="{{ $tier->id }}"
                                            {{ $selectedTier && $selectedTier->id == $tier->id ? 'selected' : '' }}
                                            data-commission-type="{{ $tier->commission_type }}"
                                            data-commission-rate="{{ $tier->commission_rate }}"
                                            data-commission-amount="{{ $tier->commission_amount }}">
                                        {{ $tier->tier_name }} ({{ $tier->getCommissionDisplayText() }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="partner_type_id" class="form-label">파트너 타입</label>
                            <select id="partner_type_id" name="partner_type_id" class="form-select">
                                <option value="">타입을 선택하세요 (선택사항)</option>
                                @foreach($partnerTypes as $type)
                                    <option value="{{ $type->id }}"
                                            {{ $selectedPartnerType && $selectedPartnerType->id == $type->id ? 'selected' : '' }}
                                            data-commission-type="{{ $type->commission_type }}"
                                            data-commission-rate="{{ $type->commission_rate }}"
                                            data-commission-amount="{{ $type->commission_amount }}">
                                        {{ $type->type_name }} ({{ $type->getCommissionDisplayText() }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="transaction_amount" class="form-label">거래 금액</label>
                            <div class="input-group">
                                <input type="number"
                                       id="transaction_amount"
                                       name="transaction_amount"
                                       class="form-control"
                                       value="1000000"
                                       min="0"
                                       step="10000">
                                <span class="input-group-text">원</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-primary me-2" onclick="calculateCosts()">
                            <i class="fe fe-calculator me-2"></i>비용 계산
                        </button>
                        <button type="button" class="btn btn-outline-info me-2" onclick="showSimulation()">
                            <i class="fe fe-trending-up me-2"></i>수수료 시뮬레이션
                        </button>
                        <button type="button" class="btn btn-outline-success" onclick="showOptimization()">
                            <i class="fe fe-target me-2"></i>최적화 추천
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 결과 영역 -->
    <div id="resultsArea" style="display: none;">

        <!-- 비용 구조 요약 -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">개별 비용 구조</h5>
                    </div>
                    <div class="card-body">
                        <div id="individualCosts"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">합산 비용 구조</h5>
                    </div>
                    <div class="card-body">
                        <div id="combinedCosts"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 수수료 계산 결과 -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">수수료 계산 결과</h5>
            </div>
            <div class="card-body">
                <div id="commissionResults"></div>
            </div>
        </div>

        <!-- 수수료 시뮬레이션 차트 -->
        <div class="card mb-4" id="simulationCard" style="display: none;">
            <div class="card-header">
                <h5 class="mb-0">수수료 시뮬레이션</h5>
            </div>
            <div class="card-body">
                <canvas id="simulationChart" width="800" height="400"></canvas>
            </div>
        </div>

        <!-- 최적화 추천 -->
        <div class="card" id="optimizationCard" style="display: none;">
            <div class="card-header">
                <h5 class="mb-0">비용 최적화 추천</h5>
            </div>
            <div class="card-body">
                <div id="optimizationResults"></div>
            </div>
        </div>
    </div>

    <!-- 등급 비교 모달 -->
    <div class="modal fade" id="compareModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">등급 간 비용 비교</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">비교할 등급 선택 (최대 5개)</label>
                            <div class="row">
                                @foreach($tiers as $tier)
                                    <div class="col-md-3 col-sm-6">
                                        <div class="form-check">
                                            <input class="form-check-input compare-tier"
                                                   type="checkbox"
                                                   value="{{ $tier->id }}"
                                                   id="compare_tier_{{ $tier->id }}">
                                            <label class="form-check-label" for="compare_tier_{{ $tier->id }}">
                                                {{ $tier->tier_name }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-primary" onclick="performComparison()">
                            <i class="fe fe-bar-chart me-2"></i>비교 실행
                        </button>
                    </div>
                    <hr>
                    <div id="comparisonResults"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.cost-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    margin-bottom: 8px;
    background-color: #f8f9fa;
    border-radius: 4px;
}

.cost-label {
    font-weight: 500;
    color: #495057;
}

.cost-value {
    font-weight: 600;
    color: #212529;
}

.commission-breakdown {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.tier-comparison-table th {
    background-color: #495057;
    color: white;
    font-weight: 600;
}

.tier-comparison-table td {
    vertical-align: middle;
}

.highlight-best {
    background-color: #d4edda !important;
    font-weight: 600;
}

.highlight-worst {
    background-color: #f8d7da !important;
}
</style>
@endpush

@push('scripts')
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
let simulationChart = null;

// 비용 계산 실행
async function calculateCosts() {
    const tierId = document.getElementById('tier_id').value;
    const partnerTypeId = document.getElementById('partner_type_id').value;
    const transactionAmount = document.getElementById('transaction_amount').value;

    if (!tierId) {
        alert('파트너 등급을 선택해주세요.');
        return;
    }

    try {
        const response = await fetch('/admin/partner/tiers/cost-calculation/calculate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                tier_id: tierId,
                partner_type_id: partnerTypeId,
                transaction_amounts: [transactionAmount]
            })
        });

        const result = await response.json();

        if (result.success) {
            displayResults(result.data);
        } else {
            alert('계산 중 오류가 발생했습니다.');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('계산 중 오류가 발생했습니다.');
    }
}

// 결과 표시
function displayResults(data) {
    document.getElementById('resultsArea').style.display = 'block';

    // 개별 비용 구조 표시
    displayIndividualCosts(data.individual_costs);

    // 합산 비용 구조 표시
    displayCombinedCosts(data.combined_costs);

    // 수수료 계산 결과 표시
    displayCommissionResults(data.commission_simulations);
}

// 개별 비용 구조 표시
function displayIndividualCosts(costs) {
    const container = document.getElementById('individualCosts');

    let html = '<h6 class="mb-3">등급별 비용</h6>';

    // 등급 비용
    html += '<div class="mb-3">';
    html += `<div class="cost-item"><span class="cost-label">가입비</span><span class="cost-value">${formatCurrency(costs.tier.registration_fee)}</span></div>`;
    html += `<div class="cost-item"><span class="cost-label">월 유지비</span><span class="cost-value">${formatCurrency(costs.tier.monthly_fee)}</span></div>`;
    html += `<div class="cost-item"><span class="cost-label">연 유지비</span><span class="cost-value">${formatCurrency(costs.tier.annual_fee)}</span></div>`;
    html += '</div>';

    // 파트너 타입 비용
    if (costs.partner_type.type_name) {
        html += '<h6 class="mb-3">파트너 타입별 비용</h6>';
        html += '<div class="mb-3">';
        html += `<div class="cost-item"><span class="cost-label">타입명</span><span class="cost-value">${costs.partner_type.type_name}</span></div>`;
        html += `<div class="cost-item"><span class="cost-label">가입비</span><span class="cost-value">${formatCurrency(costs.partner_type.registration_fee)}</span></div>`;
        html += `<div class="cost-item"><span class="cost-label">월 유지비</span><span class="cost-value">${formatCurrency(costs.partner_type.monthly_fee)}</span></div>`;
        html += `<div class="cost-item"><span class="cost-label">연 유지비</span><span class="cost-value">${formatCurrency(costs.partner_type.annual_fee)}</span></div>`;
        html += '</div>';
    }

    container.innerHTML = html;
}

// 합산 비용 구조 표시
function displayCombinedCosts(costs) {
    const container = document.getElementById('combinedCosts');

    let html = '<div class="commission-breakdown">';
    html += '<h6 class="mb-3 text-white">총 비용 구조</h6>';
    html += `<div class="cost-item bg-white bg-opacity-20 text-white mb-2"><span class="cost-label">총 가입비</span><span class="cost-value">${formatCurrency(costs.combined.registration_fee)}</span></div>`;
    html += `<div class="cost-item bg-white bg-opacity-20 text-white mb-2"><span class="cost-label">총 월 비용</span><span class="cost-value">${formatCurrency(costs.combined.total_monthly_cost)}</span></div>`;
    html += `<div class="cost-item bg-white bg-opacity-20 text-white mb-2"><span class="cost-label">총 연 비용</span><span class="cost-value">${formatCurrency(costs.combined.total_annual_cost)}</span></div>`;
    html += `<div class="cost-item bg-white bg-opacity-20 text-white"><span class="cost-label">첫해 총 비용</span><span class="cost-value">${formatCurrency(costs.combined.first_year_cost)}</span></div>`;
    html += '</div>';

    // 수수료율 정보
    html += '<div class="mt-3">';
    html += `<div class="cost-item"><span class="cost-label">합산 수수료율</span><span class="cost-value">${costs.combined.commission_rate}%</span></div>`;
    html += '</div>';

    container.innerHTML = html;
}

// 수수료 계산 결과 표시
function displayCommissionResults(simulations) {
    const container = document.getElementById('commissionResults');

    if (!simulations.simulations || simulations.simulations.length === 0) {
        container.innerHTML = '<p class="text-muted">수수료 계산 결과가 없습니다.</p>';
        return;
    }

    const simulation = simulations.simulations[0];

    let html = '<div class="commission-breakdown">';
    html += '<h6 class="mb-3 text-white">수수료 계산 상세</h6>';
    html += `<div class="row text-white">`;
    html += `<div class="col-md-3"><div class="text-center"><h4>${formatCurrency(simulation.transaction_amount)}</h4><small>거래 금액</small></div></div>`;
    html += `<div class="col-md-3"><div class="text-center"><h4>${formatCurrency(simulation.tier_commission)}</h4><small>등급 수수료</small></div></div>`;
    html += `<div class="col-md-3"><div class="text-center"><h4>${formatCurrency(simulation.type_commission)}</h4><small>타입 수수료</small></div></div>`;
    html += `<div class="col-md-3"><div class="text-center"><h4>${formatCurrency(simulation.total_commission)}</h4><small>총 수수료 (${simulation.effective_rate}%)</small></div></div>`;
    html += `</div>`;
    html += '</div>';

    // 수수료 분석표
    html += '<div class="table-responsive mt-3">';
    html += '<table class="table table-sm">';
    html += '<thead><tr><th>구분</th><th>타입</th><th>수수료율/금액</th><th>계산된 수수료</th></tr></thead>';
    html += '<tbody>';
    html += `<tr><td>${simulation.breakdown.tier.name}</td><td>${simulation.breakdown.tier.type}</td><td>${simulation.breakdown.tier.type === 'percentage' ? simulation.breakdown.tier.rate + '%' : formatCurrency(simulation.breakdown.tier.amount)}</td><td>${formatCurrency(simulation.breakdown.tier.commission)}</td></tr>`;
    if (simulation.breakdown.partner_type) {
        html += `<tr><td>${simulation.breakdown.partner_type.name}</td><td>${simulation.breakdown.partner_type.type}</td><td>${simulation.breakdown.partner_type.type === 'percentage' ? simulation.breakdown.partner_type.rate + '%' : formatCurrency(simulation.breakdown.partner_type.amount)}</td><td>${formatCurrency(simulation.breakdown.partner_type.commission)}</td></tr>`;
    }
    html += `<tr class="table-primary"><td colspan="3"><strong>합계</strong></td><td><strong>${formatCurrency(simulation.total_commission)}</strong></td></tr>`;
    html += '</tbody></table>';
    html += '</div>';

    if (simulation.cap_applied) {
        html += '<div class="alert alert-warning mt-2"><i class="fe fe-info me-2"></i>상한선이 적용되어 수수료가 조정되었습니다.</div>';
    }

    container.innerHTML = html;
}

// 수수료 시뮬레이션 차트 표시
async function showSimulation() {
    const tierId = document.getElementById('tier_id').value;
    const partnerTypeId = document.getElementById('partner_type_id').value;

    if (!tierId) {
        alert('파트너 등급을 선택해주세요.');
        return;
    }

    try {
        const response = await fetch('/admin/partner/tiers/cost-calculation/simulate-commission', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                tier_id: tierId,
                partner_type_id: partnerTypeId,
                start_amount: 100000,
                end_amount: 10000000,
                step_count: 20
            })
        });

        const result = await response.json();

        if (result.success) {
            displaySimulationChart(result.data);
            document.getElementById('simulationCard').style.display = 'block';
        }
    } catch (error) {
        console.error('Error:', error);
        alert('시뮬레이션 중 오류가 발생했습니다.');
    }
}

// 시뮬레이션 차트 표시
function displaySimulationChart(data) {
    const ctx = document.getElementById('simulationChart').getContext('2d');

    if (simulationChart) {
        simulationChart.destroy();
    }

    const labels = data.simulations.map(sim => formatCurrency(sim.transaction_amount));
    const commissionData = data.simulations.map(sim => sim.total_commission);
    const effectiveRateData = data.simulations.map(sim => sim.effective_rate);

    simulationChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: '총 수수료 (원)',
                data: commissionData,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                yAxisID: 'y'
            }, {
                label: '실효 수수료율 (%)',
                data: effectiveRateData,
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: '거래 금액별 수수료 시뮬레이션'
                }
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: '거래 금액'
                    }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: '수수료 (원)'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: '수수료율 (%)'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            }
        }
    });
}

// 최적화 추천 표시
async function showOptimization() {
    const tierId = document.getElementById('tier_id').value;
    const partnerTypeId = document.getElementById('partner_type_id').value;
    const transactionAmount = document.getElementById('transaction_amount').value;

    if (!tierId) {
        alert('파트너 등급을 선택해주세요.');
        return;
    }

    try {
        const response = await fetch('/admin/partner/tiers/cost-calculation/optimize', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                current_tier_id: tierId,
                partner_type_id: partnerTypeId,
                monthly_transaction_volume: transactionAmount
            })
        });

        const result = await response.json();

        if (result.success) {
            displayOptimizationResults(result.data);
            document.getElementById('optimizationCard').style.display = 'block';
        }
    } catch (error) {
        console.error('Error:', error);
        alert('최적화 분석 중 오류가 발생했습니다.');
    }
}

// 최적화 결과 표시
function displayOptimizationResults(data) {
    const container = document.getElementById('optimizationResults');

    let html = '<div class="mb-4">';
    html += '<h6>현재 등급 분석</h6>';
    html += `<div class="alert alert-info">`;
    html += `<strong>${data.current_tier.tier_info.tier_name}</strong><br>`;
    html += `월간 총 비용: <strong>${formatCurrency(data.current_tier.monthly_cost_efficiency)}</strong><br>`;
    html += `(수수료: ${formatCurrency(data.current_tier.commission_detail.total_commission)} + 유지비: ${formatCurrency(data.current_tier.cost_structure.combined.total_monthly_cost)})`;
    html += `</div>`;
    html += '</div>';

    if (data.recommendations.length > 0) {
        html += '<h6>추천 등급</h6>';
        html += '<div class="table-responsive">';
        html += '<table class="table table-sm">';
        html += '<thead><tr><th>등급명</th><th>월간 총 비용</th><th>절약 금액</th><th>수수료율</th><th>상세</th></tr></thead>';
        html += '<tbody>';

        data.recommendations.forEach(rec => {
            const savings = data.current_tier.monthly_cost_efficiency - rec.monthly_cost_efficiency;
            html += '<tr class="table-success">';
            html += `<td><strong>${rec.tier_info.tier_name}</strong></td>`;
            html += `<td>${formatCurrency(rec.monthly_cost_efficiency)}</td>`;
            html += `<td class="text-success"><strong>-${formatCurrency(savings)}</strong></td>`;
            html += `<td>${rec.commission_detail.effective_rate}%</td>`;
            html += `<td><button class="btn btn-sm btn-outline-info" onclick="selectTier(${rec.tier_info.id})">선택</button></td>`;
            html += '</tr>';
        });

        html += '</tbody></table>';
        html += '</div>';
    } else {
        html += '<div class="alert alert-warning">현재 등급이 이미 최적화되어 있거나 더 나은 대안을 찾을 수 없습니다.</div>';
    }

    container.innerHTML = html;
}

// 등급 선택
function selectTier(tierId) {
    document.getElementById('tier_id').value = tierId;
    calculateCosts();
}

// 통화 포맷팅
function formatCurrency(amount) {
    return new Intl.NumberFormat('ko-KR', {
        style: 'currency',
        currency: 'KRW',
        minimumFractionDigits: 0
    }).format(amount);
}

// 페이지 로드 시 초기 계산 (선택된 등급이 있는 경우)
document.addEventListener('DOMContentLoaded', function() {
    @if($selectedTier)
        calculateCosts();
    @endif
});
</script>
@endpush