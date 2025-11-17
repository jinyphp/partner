<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerTiers;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerTier;
use Jiny\Partner\Models\PartnerType;
use Illuminate\Http\Request;

class CostCalculationController extends Controller
{
    /**
     * 비용 계산 API 엔드포인트
     * AJAX로 호출되어 실시간 비용 계산 결과를 반환
     */
    public function calculate(Request $request)
    {
        $request->validate([
            'tier_id' => 'required|exists:partner_tiers,id',
            'partner_type_id' => 'nullable|exists:partner_types,id',
            'transaction_amounts' => 'array',
            'transaction_amounts.*' => 'numeric|min:0'
        ]);

        $tier = PartnerTier::find($request->tier_id);
        $partnerType = $request->partner_type_id ? PartnerType::find($request->partner_type_id) : null;

        // 기본 거래 금액들 (설정되지 않은 경우)
        $defaultAmounts = [100000, 500000, 1000000, 5000000, 10000000];
        $transactionAmounts = $request->transaction_amounts ?: $defaultAmounts;

        return response()->json([
            'success' => true,
            'data' => [
                'tier_info' => [
                    'id' => $tier->id,
                    'tier_code' => $tier->tier_code,
                    'tier_name' => $tier->tier_name,
                    'commission_type' => $tier->commission_type,
                    'commission_rate' => $tier->commission_rate,
                    'commission_amount' => $tier->commission_amount,
                ],
                'partner_type_info' => $partnerType ? [
                    'id' => $partnerType->id,
                    'type_code' => $partnerType->type_code,
                    'type_name' => $partnerType->type_name,
                    'commission_type' => $partnerType->commission_type,
                    'commission_rate' => $partnerType->commission_rate,
                    'commission_amount' => $partnerType->commission_amount,
                ] : null,
                'individual_costs' => [
                    'tier' => $tier->getCostStructure(),
                    'partner_type' => $tier->getParentTypeCostStructure()
                ],
                'combined_costs' => $tier->getComprehensiveCostStructure($partnerType),
                'commission_simulations' => $tier->simulateCommissions($transactionAmounts, $partnerType),
                'generated_at' => now()->toISOString()
            ]
        ]);
    }

    /**
     * 비용 구조 비교 API 엔드포인트
     * 여러 등급 간의 비용 구조를 비교
     */
    public function compare(Request $request)
    {
        $request->validate([
            'tier_ids' => 'required|array|min:1|max:5',
            'tier_ids.*' => 'exists:partner_tiers,id',
            'partner_type_id' => 'nullable|exists:partner_types,id',
            'transaction_amount' => 'nullable|numeric|min:0'
        ]);

        $tiers = PartnerTier::whereIn('id', $request->tier_ids)
            ->orderBy('priority_level')
            ->get();

        $partnerType = $request->partner_type_id ? PartnerType::find($request->partner_type_id) : null;
        $transactionAmount = $request->transaction_amount ?: 1000000;

        $comparisons = [];
        foreach ($tiers as $tier) {
            $comparisons[] = [
                'tier_info' => [
                    'id' => $tier->id,
                    'tier_code' => $tier->tier_code,
                    'tier_name' => $tier->tier_name,
                    'priority_level' => $tier->priority_level,
                ],
                'cost_structure' => $tier->getComprehensiveCostStructure($partnerType),
                'commission_detail' => $tier->calculateDetailedCommission($transactionAmount, $partnerType)
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'partner_type_info' => $partnerType ? [
                    'id' => $partnerType->id,
                    'type_name' => $partnerType->type_name,
                ] : null,
                'transaction_amount' => $transactionAmount,
                'comparisons' => $comparisons,
                'summary' => [
                    'total_tiers' => count($comparisons),
                    'highest_commission_rate' => collect($comparisons)->max('commission_detail.effective_rate'),
                    'lowest_commission_rate' => collect($comparisons)->min('commission_detail.effective_rate'),
                    'highest_monthly_cost' => collect($comparisons)->max('cost_structure.combined.total_monthly_cost'),
                    'lowest_monthly_cost' => collect($comparisons)->min('cost_structure.combined.total_monthly_cost'),
                ],
                'generated_at' => now()->toISOString()
            ]
        ]);
    }

    /**
     * 비용 계산 대시보드 페이지
     */
    public function dashboard(Request $request)
    {
        $selectedTier = $request->tier_id ? PartnerTier::find($request->tier_id) : null;
        $selectedPartnerType = $request->partner_type_id ? PartnerType::find($request->partner_type_id) : null;

        $tiers = PartnerTier::active()
            ->with('parentPartnerType')
            ->orderBy('priority_level')
            ->get();

        $partnerTypes = PartnerType::active()
            ->orderBy('priority_level')
            ->get();

        return view('jiny-partner::admin.partner-tiers.cost-dashboard', [
            'title' => '파트너 등급 비용 계산',
            'tiers' => $tiers,
            'partnerTypes' => $partnerTypes,
            'selectedTier' => $selectedTier,
            'selectedPartnerType' => $selectedPartnerType
        ]);
    }

    /**
     * 수수료 시뮬레이션 API 엔드포인트
     */
    public function simulateCommission(Request $request)
    {
        $request->validate([
            'tier_id' => 'required|exists:partner_tiers,id',
            'partner_type_id' => 'nullable|exists:partner_types,id',
            'start_amount' => 'required|numeric|min:0',
            'end_amount' => 'required|numeric|gt:start_amount',
            'step_count' => 'required|integer|min:5|max:50'
        ]);

        $tier = PartnerTier::find($request->tier_id);
        $partnerType = $request->partner_type_id ? PartnerType::find($request->partner_type_id) : null;

        $startAmount = $request->start_amount;
        $endAmount = $request->end_amount;
        $stepCount = $request->step_count;

        // 구간별 시뮬레이션 금액 계산
        $amounts = [];
        for ($i = 0; $i <= $stepCount; $i++) {
            $ratio = $i / $stepCount;
            $amounts[] = $startAmount + ($endAmount - $startAmount) * $ratio;
        }

        $simulation = $tier->simulateCommissions($amounts, $partnerType);

        return response()->json([
            'success' => true,
            'data' => $simulation
        ]);
    }

    /**
     * 비용 최적화 추천 API 엔드포인트
     */
    public function optimize(Request $request)
    {
        $request->validate([
            'current_tier_id' => 'required|exists:partner_tiers,id',
            'partner_type_id' => 'nullable|exists:partner_types,id',
            'monthly_transaction_volume' => 'required|numeric|min:0',
            'target_commission_rate' => 'nullable|numeric|min:0|max:100'
        ]);

        $currentTier = PartnerTier::find($request->current_tier_id);
        $partnerType = $request->partner_type_id ? PartnerType::find($request->partner_type_id) : null;
        $monthlyVolume = $request->monthly_transaction_volume;
        $targetRate = $request->target_commission_rate;

        // 모든 등급의 비용 효율성 계산
        $alternatives = PartnerTier::active()
            ->where('id', '!=', $currentTier->id)
            ->get()
            ->map(function ($tier) use ($partnerType, $monthlyVolume) {
                $commissionDetail = $tier->calculateDetailedCommission($monthlyVolume, $partnerType);
                $costStructure = $tier->getComprehensiveCostStructure($partnerType);

                return [
                    'tier_info' => [
                        'id' => $tier->id,
                        'tier_code' => $tier->tier_code,
                        'tier_name' => $tier->tier_name,
                        'priority_level' => $tier->priority_level,
                    ],
                    'commission_detail' => $commissionDetail,
                    'cost_structure' => $costStructure,
                    'monthly_cost_efficiency' => $commissionDetail['total_commission'] + $costStructure['combined']['total_monthly_cost'],
                    'annual_cost_efficiency' => ($commissionDetail['total_commission'] * 12) + $costStructure['combined']['total_annual_cost']
                ];
            })
            ->sortBy('monthly_cost_efficiency');

        // 현재 등급 대비 추천 등급들
        $currentCommission = $currentTier->calculateDetailedCommission($monthlyVolume, $partnerType);
        $currentCostStructure = $currentTier->getComprehensiveCostStructure($partnerType);
        $currentMonthlyCost = $currentCommission['total_commission'] + $currentCostStructure['combined']['total_monthly_cost'];

        $recommendations = $alternatives->filter(function ($alternative) use ($currentMonthlyCost, $targetRate) {
            $isCheaper = $alternative['monthly_cost_efficiency'] < $currentMonthlyCost;
            $meetsTargetRate = !$targetRate || $alternative['commission_detail']['effective_rate'] >= $targetRate;
            return $isCheaper && $meetsTargetRate;
        })->take(3);

        return response()->json([
            'success' => true,
            'data' => [
                'current_tier' => [
                    'tier_info' => [
                        'id' => $currentTier->id,
                        'tier_name' => $currentTier->tier_name
                    ],
                    'commission_detail' => $currentCommission,
                    'cost_structure' => $currentCostStructure,
                    'monthly_cost_efficiency' => $currentMonthlyCost
                ],
                'recommendations' => $recommendations->values(),
                'alternatives' => $alternatives->take(10)->values(),
                'analysis' => [
                    'monthly_transaction_volume' => $monthlyVolume,
                    'target_commission_rate' => $targetRate,
                    'total_alternatives_analyzed' => $alternatives->count(),
                    'recommendations_found' => $recommendations->count(),
                    'potential_monthly_savings' => $recommendations->isNotEmpty()
                        ? $currentMonthlyCost - $recommendations->first()['monthly_cost_efficiency']
                        : 0
                ],
                'generated_at' => now()->toISOString()
            ]
        ]);
    }
}