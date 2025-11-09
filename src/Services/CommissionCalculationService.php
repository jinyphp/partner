<?php

namespace Jiny\Partner\Services;

use Jiny\Partner\Models\PartnerSales;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Models\PartnerCommission;
use Jiny\Partner\Models\PartnerTier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class CommissionCalculationService
{
    /**
     * 커미션 계산 및 분배
     */
    public function calculateAndDistribute(PartnerSales $sales)
    {
        try {
            DB::beginTransaction();

            // 이미 계산된 매출인지 확인
            if ($sales->commission_calculated) {
                throw new Exception('이미 커미션이 계산된 매출입니다.');
            }

            // 매출이 확정 상태인지 확인
            if ($sales->status !== 'confirmed') {
                throw new Exception('확정된 매출만 커미션 계산이 가능합니다.');
            }

            // 파트너 정보 조회
            $partner = $sales->partner;
            if (!$partner) {
                throw new Exception('파트너 정보를 찾을 수 없습니다.');
            }

            // 트리 구조 스냅샷 생성
            $sales->createTreeSnapshot();

            // 상위 파트너들 조회 (계층 구조)
            $ancestors = $this->getAncestors($partner);

            // 커미션 분배 계산
            $distributions = $this->calculateCommissionDistribution($sales, $partner, $ancestors);

            // 커미션 레코드 생성
            $this->createCommissionRecords($sales, $distributions);

            // 파트너 매출 실적 업데이트
            $this->updatePartnerSalesStats($partner, $sales->amount);

            // 매출 레코드 업데이트
            $sales->update([
                'commission_calculated' => true,
                'commission_calculated_at' => now(),
                'total_commission_amount' => collect($distributions)->sum('commission_amount'),
                'commission_recipients_count' => count($distributions),
                'commission_distribution' => $distributions,
            ]);

            DB::commit();

            // 로그 기록
            $this->logCommissionCalculation($sales, $distributions);

            return [
                'success' => true,
                'total_commission' => collect($distributions)->sum('commission_amount'),
                'recipients_count' => count($distributions),
                'distributions' => $distributions,
            ];

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('커미션 계산 실패', [
                'sales_id' => $sales->id,
                'partner_id' => $sales->partner_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * 상위 파트너들 조회 (계층별)
     */
    private function getAncestors(PartnerUser $partner)
    {
        $ancestors = [];

        if (!$partner->tree_path) {
            return $ancestors;
        }

        // tree_path에서 상위 파트너 ID들 추출
        $ancestorIds = array_filter(explode('/', $partner->tree_path));

        if (empty($ancestorIds)) {
            return $ancestors;
        }

        // 상위 파트너들 조회 (level 순으로 정렬)
        $ancestorPartners = PartnerUser::with(['tier', 'type'])
            ->whereIn('id', $ancestorIds)
            ->where('status', 'active')
            ->orderBy('level')
            ->get();

        foreach ($ancestorPartners as $ancestor) {
            $ancestors[] = [
                'partner' => $ancestor,
                'level_difference' => $ancestor->level - $partner->level,
                'tier' => $ancestor->tier,
                'type' => $ancestor->type,
            ];
        }

        return $ancestors;
    }

    /**
     * 커미션 분배 계산
     */
    private function calculateCommissionDistribution(PartnerSales $sales, PartnerUser $partner, array $ancestors)
    {
        $distributions = [];
        $baseAmount = $sales->amount;

        // 1. 직접 커미션 (매출을 올린 파트너)
        $directCommission = $this->calculateDirectCommission($partner, $baseAmount);
        if ($directCommission > 0) {
            $distributions[] = [
                'partner_id' => $partner->id,
                'partner_name' => $partner->name,
                'partner_email' => $partner->email,
                'commission_type' => 'direct_sales',
                'level_difference' => 0,
                'original_amount' => $baseAmount,
                'commission_rate' => $partner->personal_commission_rate ?? 0,
                'commission_amount' => $directCommission,
                'tier_at_time' => $partner->tier->tier_code ?? null,
                'type_at_time' => $partner->type->type_code ?? null,
                'tree_path_at_time' => $partner->tree_path,
                'calculation_details' => [
                    'base_amount' => $baseAmount,
                    'tier_rate' => $partner->personal_commission_rate ?? 0,
                    'calculation' => 'base_amount * tier_rate / 100',
                ],
            ];
        }

        // 2. 간접 커미션 (상위 파트너들)
        foreach ($ancestors as $ancestorInfo) {
            $ancestor = $ancestorInfo['partner'];
            $levelDiff = abs($ancestorInfo['level_difference']);

            // 레벨별 커미션율 계산 (레벨이 높을수록 낮은 비율)
            $indirectCommission = $this->calculateIndirectCommission($ancestor, $baseAmount, $levelDiff);

            if ($indirectCommission > 0) {
                // 하위 파트너 수에 따른 분배 계산
                $childrenCount = $this->getChildrenCount($ancestor, $partner->level);
                $dividedCommission = $childrenCount > 1 ? $indirectCommission / $childrenCount : $indirectCommission;

                $distributions[] = [
                    'partner_id' => $ancestor->id,
                    'partner_name' => $ancestor->name,
                    'partner_email' => $ancestor->email,
                    'commission_type' => 'team_bonus',
                    'level_difference' => $levelDiff,
                    'original_amount' => $baseAmount,
                    'commission_rate' => $this->getIndirectCommissionRate($ancestor, $levelDiff),
                    'commission_amount' => $dividedCommission,
                    'children_count' => $childrenCount,
                    'tier_at_time' => $ancestor->tier->tier_code ?? null,
                    'type_at_time' => $ancestor->type->type_code ?? null,
                    'tree_path_at_time' => $ancestor->tree_path,
                    'calculation_details' => [
                        'base_amount' => $baseAmount,
                        'level_rate' => $this->getIndirectCommissionRate($ancestor, $levelDiff),
                        'before_division' => $indirectCommission,
                        'children_count' => $childrenCount,
                        'calculation' => 'base_amount * level_rate / 100 / children_count',
                    ],
                ];
            }
        }

        return $distributions;
    }

    /**
     * 직접 커미션 계산 (매출을 올린 파트너)
     */
    private function calculateDirectCommission(PartnerUser $partner, $amount)
    {
        $rate = $partner->personal_commission_rate ?? 0;

        // 파트너 등급별 기본 커미션율 적용
        if ($rate == 0 && $partner->tier) {
            $rate = $partner->tier->commission_rate ?? 0;
        }

        // 파트너 타입별 추가 커미션율 적용
        if ($partner->type && $partner->type->commission_bonus_rate) {
            $rate += $partner->type->commission_bonus_rate;
        }

        return $amount * ($rate / 100);
    }

    /**
     * 간접 커미션 계산 (상위 파트너들)
     */
    private function calculateIndirectCommission(PartnerUser $ancestor, $amount, $levelDifference)
    {
        $rate = $this->getIndirectCommissionRate($ancestor, $levelDifference);
        return $amount * ($rate / 100);
    }

    /**
     * 레벨별 간접 커미션율 계산
     */
    private function getIndirectCommissionRate(PartnerUser $ancestor, $levelDifference)
    {
        // 기본 간접 커미션율 (레벨별로 차등 적용)
        $baseRates = [
            1 => 3.0, // 직접 상위: 3%
            2 => 2.0, // 2단계 상위: 2%
            3 => 1.0, // 3단계 상위: 1%
            4 => 0.5, // 4단계 상위: 0.5%
        ];

        $baseRate = $baseRates[$levelDifference] ?? 0;

        // 파트너 등급별 보너스 적용
        if ($ancestor->tier) {
            $tierBonus = $ancestor->tier->management_bonus_rate ?? 0;
            $baseRate += $tierBonus;
        }

        // 파트너 타입별 추가 보너스
        if ($ancestor->type && $ancestor->type->commission_bonus_rate) {
            $baseRate += ($ancestor->type->commission_bonus_rate * 0.5); // 간접은 절반만 적용
        }

        return max(0, $baseRate); // 음수 방지
    }

    /**
     * 특정 레벨의 하위 파트너 수 조회
     */
    private function getChildrenCount(PartnerUser $ancestor, $targetLevel)
    {
        // 해당 상위 파트너의 직접 하위 파트너 중에서 target level에 해당하는 파트너 수
        return PartnerUser::where('parent_id', $ancestor->id)
                          ->where('level', '>', $ancestor->level)
                          ->where('level', '<=', $targetLevel)
                          ->where('status', 'active')
                          ->count() ?: 1; // 최소 1로 설정하여 0으로 나누기 방지
    }

    /**
     * 커미션 레코드 생성
     */
    private function createCommissionRecords(PartnerSales $sales, array $distributions)
    {
        foreach ($distributions as $distribution) {
            PartnerCommission::create([
                'partner_id' => $distribution['partner_id'],
                'source_partner_id' => $sales->partner_id,
                'order_id' => $sales->id,
                'commission_type' => $distribution['commission_type'],
                'level_difference' => $distribution['level_difference'],
                'tree_path_at_time' => $distribution['tree_path_at_time'],
                'original_amount' => $distribution['original_amount'],
                'commission_rate' => $distribution['commission_rate'],
                'commission_amount' => $distribution['commission_amount'],
                'tax_amount' => $distribution['commission_amount'] * 0.1, // 10% 세금
                'net_amount' => $distribution['commission_amount'] * 0.9, // 세후 금액
                'status' => 'calculated',
                'earned_at' => now(),
                'calculation_details' => json_encode($distribution['calculation_details']),
                'notes' => "매출 ID {$sales->id}에 대한 {$distribution['commission_type']} 커미션",
            ]);
        }
    }

    /**
     * 파트너 매출 실적 업데이트
     */
    private function updatePartnerSalesStats(PartnerUser $partner, $amount)
    {
        $partner->increment('total_sales', $amount);
        $partner->increment('monthly_sales', $amount);
        $partner->update(['last_activity_at' => now()]);

        // 상위 파트너들의 팀 매출도 업데이트
        $ancestors = $this->getAncestors($partner);
        foreach ($ancestors as $ancestorInfo) {
            $ancestor = $ancestorInfo['partner'];
            $ancestor->increment('team_sales', $amount);
        }
    }

    /**
     * 커미션 계산 로그 기록
     */
    private function logCommissionCalculation(PartnerSales $sales, array $distributions)
    {
        Log::info('커미션 계산 완료', [
            'sales_id' => $sales->id,
            'partner_id' => $sales->partner_id,
            'sales_amount' => $sales->amount,
            'total_commission' => collect($distributions)->sum('commission_amount'),
            'recipients_count' => count($distributions),
            'distributions' => $distributions,
        ]);
    }

    /**
     * 매출 취소에 따른 커미션 역계산
     */
    public function reverseCalculation(PartnerSales $sales)
    {
        try {
            DB::beginTransaction();

            if (!$sales->commission_calculated) {
                throw new Exception('커미션이 계산되지 않은 매출입니다.');
            }

            // 기존 커미션 레코드들을 취소 상태로 변경
            PartnerCommission::where('order_id', $sales->id)
                             ->update([
                                 'status' => 'cancelled',
                                 'cancelled_at' => now(),
                                 'notes' => '매출 취소로 인한 커미션 회수'
                             ]);

            // 파트너들의 매출 실적 차감
            if ($sales->commission_distribution) {
                foreach ($sales->commission_distribution as $distribution) {
                    $partner = PartnerUser::find($distribution['partner_id']);
                    if ($partner) {
                        if ($distribution['commission_type'] === 'direct_sales') {
                            $partner->decrement('total_sales', $sales->amount);
                            $partner->decrement('monthly_sales', $sales->amount);
                        } else {
                            $partner->decrement('team_sales', $sales->amount);
                        }
                        $partner->decrement('earned_commissions', $distribution['commission_amount']);
                    }
                }
            }

            // 매출 레코드 업데이트
            $sales->update([
                'commission_calculated' => false,
                'commission_calculated_at' => null,
                'total_commission_amount' => 0,
                'commission_recipients_count' => 0,
            ]);

            DB::commit();

            // 로그 기록
            Log::info('커미션 역계산 완료', [
                'sales_id' => $sales->id,
                'partner_id' => $sales->partner_id,
                'original_amount' => $sales->amount,
            ]);

            return true;

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('커미션 역계산 실패', [
                'sales_id' => $sales->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * 대량 커미션 계산 (배치 처리)
     */
    public function batchCalculateCommissions($limit = 50)
    {
        $pendingSales = PartnerSales::needsCommissionCalculation()
                                   ->orderBy('sales_date')
                                   ->limit($limit)
                                   ->get();

        $results = [
            'processed' => 0,
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($pendingSales as $sales) {
            try {
                $this->calculateAndDistribute($sales);
                $results['success']++;
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'sales_id' => $sales->id,
                    'error' => $e->getMessage(),
                ];
            }
            $results['processed']++;
        }

        return $results;
    }
}