<?php

namespace Jiny\Partner\Http\Controllers\Api\Partner\Commission;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Jiny\Partner\Models\PartnerSales;
use Jiny\Partner\Models\PartnerCommission;
use Jiny\Partner\Models\PartnerUser;

/**
 * 파트너 커미션 계층적 분배 계산기
 *
 * 계산 로직 흐름도:
 * ==================
 *
 * __invoke() (Entry Point)
 * │
 * ├── Step 1: 매출 정보 검증 및 로드
 * │   └── validateSalesData()
 * │
 * └── Step 2: 트랜잭션 내 커미션 계산 실행
 *     └── calculateAndSaveCommissions()
 *         │
 *         ├── Step 2.1: 파트너 계층 구조 분석
 *         │   └── buildPartnerHierarchy()
 *         │       └── findParentPartner() (재귀 호출)
 *         │
 *         ├── Step 2.2: 계층적 커미션 분배 계산
 *         │   ├── calculateHierarchicalCommissions()
 *         │   │   ├── Step 2.2.1: 계층 역순 정렬 (최상위부터)
 *         │   │   ├── Step 2.2.2: 각 레벨별 차액 계산
 *         │   │   ├── Step 2.2.3: 커미션 레코드 생성
 *         │   │   └── buildTreePath()
 *         │   │
 *         │   └── calculateSinglePartnerCommission() (계층 없는 경우)
 *         │
 *         ├── Step 2.3: 매출 테이블 업데이트
 *         │
 *         └── Step 2.4: 계산 요약 정보 생성
 *             ├── getMaxCommissionRate()
 *             └── getHierarchyBreakdown()
 *
 * 핵심 계산 공식:
 * ==============
 * 각 파트너 수령액 = 매출액 × (자신의 수수료율 - 직속 하위 파트너 수수료율)
 *
 * 예시: 파트너1(20%) → 파트너2(15%) → 파트너3(10%), 매출 10,000원
 * - 파트너3: 10,000 × (10% - 0%) = 1,000원
 * - 파트너2: 10,000 × (15% - 10%) = 500원
 * - 파트너1: 10,000 × (20% - 15%) = 500원
 * - 총합: 2,000원 (최상위 20% 한도 내)
 */
class CalculateController extends Controller
{
    /**
     * === ENTRY POINT ===
     * 매출에 대한 커미션 계층적 분배 계산 및 저장
     *
     * @param Request $request
     * @param int $salesId 매출 ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, $salesId)
    {
        // === STEP 0: 요청 파라미터 검증 ===
        $request->validate([
            'force_recalculate' => 'boolean'
        ]);

        $forceRecalculate = $request->input('force_recalculate', false);

        // 계산 요청 로깅
        Log::info('Commission calculation request started', [
            'sales_id' => $salesId,
            'force_recalculate' => $forceRecalculate,
            'user_id' => auth()->id(),
            'ip' => $request->ip()
        ]);

        try {
            // === STEP 1: 매출 정보 검증 및 로드 ===
            $sales = $this->step1_ValidateAndLoadSales($salesId, $forceRecalculate);

            // === STEP 2: 트랜잭션 내 커미션 계산 실행 ===
            $result = DB::transaction(function () use ($sales, $forceRecalculate) {
                return $this->step2_CalculateAndSaveCommissions($sales, $forceRecalculate);
            });

            // === STEP 3: 성공 응답 생성 ===
            return $this->step3_BuildSuccessResponse($sales, $result);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->handleNotFoundError($salesId);
        } catch (\Exception $e) {
            return $this->handleCalculationError($salesId, $e);
        }
    }

    /**
     * === STEP 1: 매출 정보 검증 및 로드 ===
     *
     * @param int $salesId
     * @param bool $forceRecalculate
     * @return PartnerSales
     * @throws \Exception
     */
    private function step1_ValidateAndLoadSales($salesId, $forceRecalculate)
    {
        // Step 1.1: 매출 정보 조회 (필요한 관계 미리 로드)
        $sales = PartnerSales::with([
            'partner.partnerType',
            'partner.partnerTier'
        ])->findOrFail($salesId);

        // Step 1.2: 매출 상태 검증 (확정된 매출만 처리)
        if ($sales->status !== 'confirmed') {
            throw new \Exception(
                "확정된 매출에 대해서만 커미션을 계산할 수 있습니다. 현재 상태: {$sales->status}",
                400
            );
        }

        // Step 1.3: 중복 계산 방지 검사
        if ($sales->commission_calculated && !$forceRecalculate) {
            throw new \Exception(
                "이미 커미션이 계산되었습니다. 재계산하려면 force_recalculate=true를 설정하세요. " .
                "계산일시: {$sales->commission_calculated_at}",
                400
            );
        }

        return $sales;
    }

    /**
     * === STEP 3: 성공 응답 생성 ===
     *
     * @param PartnerSales $sales
     * @param array $result
     * @return \Illuminate\Http\JsonResponse
     */
    private function step3_BuildSuccessResponse($sales, $result)
    {
        // 성공 로깅
        Log::info('Commission calculation completed successfully', [
            'sales_id' => $sales->id,
            'total_commission' => $result['total_commission'],
            'recipients_count' => $result['recipients_count'],
            'commissions_created' => count($result['commissions']),
            'max_commission_rate' => $result['summary']['max_commission_rate'] ?? 0
        ]);

        return response()->json([
            'success' => true,
            'message' => '커미션 계산이 완료되었습니다.',
            'data' => [
                'sales_id' => $sales->id,
                'sales_amount' => $sales->amount,
                'total_commission' => $result['total_commission'],
                'recipients_count' => $result['recipients_count'],
                'commissions' => $result['commissions'],
                'calculation_summary' => $result['summary'],
                'calculated_at' => now()->toISOString()
            ]
        ]);
    }

    /**
     * 404 오류 처리
     */
    private function handleNotFoundError($salesId)
    {
        return response()->json([
            'success' => false,
            'message' => '매출 정보를 찾을 수 없습니다.',
            'sales_id' => $salesId
        ], 404);
    }

    /**
     * 계산 오류 처리
     */
    private function handleCalculationError($salesId, \Exception $e)
    {
        Log::error('Commission calculation failed', [
            'sales_id' => $salesId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        $statusCode = method_exists($e, 'getCode') && $e->getCode() > 0 ? $e->getCode() : 500;

        return response()->json([
            'success' => false,
            'message' => '커미션 계산 중 오류가 발생했습니다: ' . $e->getMessage(),
            'error_code' => 'COMMISSION_CALCULATION_FAILED'
        ], $statusCode);
    }

    /**
     * === STEP 2: 커미션 계산 및 저장 - 계층적 네트워크 분배 ===
     *
     * @param PartnerSales $sales
     * @param bool $forceRecalculate
     * @return array
     */
    private function step2_CalculateAndSaveCommissions(PartnerSales $sales, bool $forceRecalculate = false)
    {
        Log::info('Commission calculation process started', [
            'sales_id' => $sales->id,
            'sales_amount' => $sales->amount,
            'partner_id' => $sales->partner_id
        ]);

        // === Step 2.1: 기존 커미션 정리 (재계산인 경우) ===
        $this->step2_1_CleanupExistingCommissions($sales, $forceRecalculate);

        // === Step 2.2: 파트너 계층 구조 분석 ===
        $partnerHierarchy = $this->step2_2_BuildPartnerHierarchy($sales->partner);

        // === Step 2.3: 커미션 분배 계산 ===
        $commissionResults = $this->step2_3_CalculateCommissionDistribution($sales, $partnerHierarchy);

        // === Step 2.4: 매출 테이블 업데이트 ===
        $this->step2_4_UpdateSalesRecord($sales, $commissionResults['total_commission']);

        // === Step 2.5: 파트너별 커미션은 생성 시 자동으로 Balance에 반영됨 ===

        // === Step 2.6: 계산 요약 정보 생성 ===
        $summary = $this->step2_6_BuildCalculationSummary($partnerHierarchy, $commissionResults);

        Log::info('Commission calculation process completed', [
            'sales_id' => $sales->id,
            'total_commission' => $commissionResults['total_commission'],
            'recipients_count' => $commissionResults['recipients_count'],
            'hierarchy_levels' => count($partnerHierarchy)
        ]);

        return [
            'total_commission' => $commissionResults['total_commission'],
            'recipients_count' => $commissionResults['recipients_count'],
            'commissions' => $commissionResults['commissions'],
            'summary' => $summary
        ];
    }

    /**
     * === Step 2.1: 기존 커미션 정리 ===
     *
     * @param PartnerSales $sales
     * @param bool $forceRecalculate
     */
    private function step2_1_CleanupExistingCommissions(PartnerSales $sales, bool $forceRecalculate)
    {
        if ($forceRecalculate) {
            $deletedCount = PartnerCommission::where('order_id', $sales->id)->delete();
            Log::info('Existing commissions cleaned up', [
                'sales_id' => $sales->id,
                'deleted_commissions' => $deletedCount
            ]);
        }
    }

    /**
     * === Step 2.2: 파트너 계층 구조 분석 ===
     *
     * @param PartnerUser $salesPartner
     * @return array
     */
    private function step2_2_BuildPartnerHierarchy(PartnerUser $salesPartner)
    {
        $hierarchy = [];
        $currentPartner = $salesPartner;
        $maxLevels = 10; // 무한 루프 방지
        $level = 0;

        Log::info('Building partner hierarchy', [
            'starting_partner_id' => $salesPartner->id,
            'starting_partner_name' => $salesPartner->name
        ]);

        // 매출 파트너부터 시작하여 상위로 올라가면서 계층 구축
        while ($currentPartner && $level < $maxLevels) {
            $commissionRate = $currentPartner->getTotalCommissionRate();

            $hierarchy[] = [
                'partner' => $currentPartner,
                'level' => $level,
                'commission_rate' => $commissionRate,
                'partner_id' => $currentPartner->id,
                'partner_name' => $currentPartner->name
            ];

            Log::debug('Added partner to hierarchy', [
                'level' => $level,
                'partner_id' => $currentPartner->id,
                'partner_name' => $currentPartner->name,
                'commission_rate' => $commissionRate
            ]);

            // 상위 파트너 찾기
            $parentPartner = $this->findParentPartner($currentPartner);
            if (!$parentPartner) {
                break;
            }

            $currentPartner = $parentPartner;
            $level++;
        }

        Log::info('Partner hierarchy built', [
            'total_levels' => count($hierarchy),
            'max_commission_rate' => $this->getMaxCommissionRate($hierarchy)
        ]);

        return $hierarchy;
    }

    /**
     * === Step 2.3: 커미션 분배 계산 ===
     *
     * @param PartnerSales $sales
     * @param array $partnerHierarchy
     * @return array
     */
    private function step2_3_CalculateCommissionDistribution(PartnerSales $sales, array $partnerHierarchy)
    {
        $commissions = [];
        $totalCommission = 0;
        $recipientsCount = 0;

        if (empty($partnerHierarchy)) {
            // 계층이 없으면 직접 파트너만 계산
            Log::info('No hierarchy found, calculating single partner commission');
            $directCommission = $this->calculateSinglePartnerCommission($sales, $sales->partner, 0);

            if ($directCommission) {
                $commissions[] = $directCommission;
                $totalCommission += $directCommission['commission_amount'];
                $recipientsCount++;
            }
        } else {
            // 계층적 커미션 분배 계산
            Log::info('Calculating hierarchical commission distribution', [
                'hierarchy_levels' => count($partnerHierarchy)
            ]);

            $hierarchicalCommissions = $this->calculateHierarchicalCommissions($sales, $partnerHierarchy);
            foreach ($hierarchicalCommissions as $commission) {
                $commissions[] = $commission;
                $totalCommission += $commission['commission_amount'];
                $recipientsCount++;
            }
        }

        return [
            'commissions' => $commissions,
            'total_commission' => $totalCommission,
            'recipients_count' => $recipientsCount
        ];
    }

    /**
     * === Step 2.4: 매출 테이블 업데이트 ===
     *
     * @param PartnerSales $sales
     * @param float $totalCommission
     */
    private function step2_4_UpdateSalesRecord(PartnerSales $sales, float $totalCommission)
    {
        $sales->update([
            'commission_calculated' => true,
            'commission_calculated_at' => now(),
            'total_commission_amount' => $totalCommission
        ]);

        Log::info('Sales record updated', [
            'sales_id' => $sales->id,
            'total_commission_amount' => $totalCommission
        ]);
    }

    /**
     * === Step 2.5: 파트너별 커미션 및 Balance 업데이트 ===
     *
     * *** DEPRECATED: 커미션 생성 시 자동으로 Balance에 반영되므로 더 이상 사용하지 않음 ***
     *
     * 커미션 계산 후 각 파트너의 earned_commissions와 balance 값을 업데이트
     * 매출 주문코드별로 그룹화하여 관리
     *
     * @param array $commissions
     * @param PartnerSales $sales
     * @deprecated 커미션 생성 시 자동으로 Balance가 업데이트됨
     */
    private function step2_5_UpdatePartnerBalances(array $commissions, PartnerSales $sales)
    {
        $orderCode = $sales->order_code ?? 'SALES-' . $sales->id;

        Log::info('Updating partner balances by order code', [
            'sales_id' => $sales->id,
            'order_code' => $orderCode,
            'commissions_count' => count($commissions)
        ]);

        // 파트너별로 커미션 집계 (같은 파트너에게 여러 커미션이 있을 수 있음)
        $partnerCommissions = [];

        foreach ($commissions as $commissionData) {
            $partnerId = $commissionData['partner_id'];
            $commissionAmount = $commissionData['commission_amount'];

            if (!isset($partnerCommissions[$partnerId])) {
                $partnerCommissions[$partnerId] = [
                    'total_commission' => 0,
                    'commission_count' => 0,
                    'commission_types' => []
                ];
            }

            $partnerCommissions[$partnerId]['total_commission'] += $commissionAmount;
            $partnerCommissions[$partnerId]['commission_count']++;
            $partnerCommissions[$partnerId]['commission_types'][] = $commissionData['commission_type'];
        }

        $updateResults = [];

        foreach ($partnerCommissions as $partnerId => $commissionInfo) {
            try {
                $partner = PartnerUser::findOrFail($partnerId);

                // 기존 값 기록
                $beforeCommissions = $partner->earned_commissions ?? 0;

                // 파트너의 총 커미션 증가 (원자적 업데이트)
                $partner->increment('earned_commissions', $commissionInfo['total_commission']);

                // 새로운 값 조회
                $partner->refresh();
                $afterCommissions = $partner->earned_commissions;

                // 통계 캐시 갱신 (즉시 실행)
                try {
                    $partner->updateCachedStatistics();
                } catch (\Exception $cacheError) {
                    Log::warning('Failed to update cached statistics', [
                        'partner_id' => $partnerId,
                        'error' => $cacheError->getMessage()
                    ]);
                }

                $updateResults[] = [
                    'partner_id' => $partnerId,
                    'partner_name' => $partner->name,
                    'commission_before' => $beforeCommissions,
                    'commission_added' => $commissionInfo['total_commission'],
                    'commission_after' => $afterCommissions,
                    'commission_count' => $commissionInfo['commission_count'],
                    'commission_types' => array_unique($commissionInfo['commission_types'])
                ];

                Log::debug('Partner balance updated successfully', [
                    'partner_id' => $partnerId,
                    'partner_name' => $partner->name,
                    'order_code' => $orderCode,
                    'commission_added' => $commissionInfo['total_commission'],
                    'commission_count' => $commissionInfo['commission_count'],
                    'before' => $beforeCommissions,
                    'after' => $afterCommissions
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to update partner balance', [
                    'partner_id' => $partnerId,
                    'order_code' => $orderCode,
                    'commission_amount' => $commissionInfo['total_commission'],
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                $updateResults[] = [
                    'partner_id' => $partnerId,
                    'error' => $e->getMessage()
                ];
            }
        }

        Log::info('Partner balances updated for order', [
            'sales_id' => $sales->id,
            'order_code' => $orderCode,
            'total_partners_updated' => count($partnerCommissions),
            'successful_updates' => count(array_filter($updateResults, fn($r) => !isset($r['error']))),
            'failed_updates' => count(array_filter($updateResults, fn($r) => isset($r['error']))),
            'update_results' => $updateResults
        ]);

        return $updateResults;
    }

    /**
     * === Step 2.6: 계산 요약 정보 생성 ===
     *
     * @param array $partnerHierarchy
     * @param array $commissionResults
     * @return array
     */
    private function step2_6_BuildCalculationSummary(array $partnerHierarchy, array $commissionResults)
    {
        return [
            'calculation_method' => 'hierarchical_network_distribution',
            'hierarchy_levels' => count($partnerHierarchy),
            'max_commission_rate' => $this->getMaxCommissionRate($partnerHierarchy),
            'total_distributed' => $commissionResults['total_commission'],
            'recipients_count' => $commissionResults['recipients_count'],
            'commission_breakdown' => $this->getHierarchyBreakdown($partnerHierarchy),
            'calculation_timestamp' => now()->toISOString(),
            'commission_log_count' => count($commissionResults['commissions']),
            'partner_balances_updated' => true
        ];
    }

    /**
     * === 계층적 커미션 분배 계산 (핵심 로직) ===
     * 각 파트너는 자신의 수수료율에서 직속 하위 파트너 수수료율을 뺀 차액만 받음
     *
     * 계산 공식: 파트너 수령액 = 매출액 × (자신의 수수료율 - 직속 하위 파트너 수수료율)
     *
     * @param PartnerSales $sales
     * @param array $partnerHierarchy
     * @return array
     */
    private function calculateHierarchicalCommissions(PartnerSales $sales, array $partnerHierarchy)
    {
        $commissions = [];
        $salesAmount = $sales->amount;

        Log::info('Starting hierarchical commission calculation', [
            'sales_amount' => $salesAmount,
            'hierarchy_levels' => count($partnerHierarchy)
        ]);

        // === Step 2.2.1: 계층을 역순으로 정렬 (최상위부터 계산) ===
        $reversedHierarchy = array_reverse($partnerHierarchy);

        // === Step 2.2.2: 각 레벨별 차액 커미션 계산 ===
        for ($i = 0; $i < count($reversedHierarchy); $i++) {
            $currentLevel = $reversedHierarchy[$i];
            $currentPartner = $currentLevel['partner'];
            $currentRate = $currentLevel['commission_rate'];

            // 직속 하위 파트너의 수수료율 찾기 (없으면 0)
            $lowerRate = $this->getLowerPartnerRate($reversedHierarchy, $i);

            // === 핵심 공식: 실제 받을 수수료율 = 자신의 수수료율 - 하위 파트너 수수료율 ===
            $actualRate = $currentRate - $lowerRate;

            Log::debug('Calculating commission for partner', [
                'partner_id' => $currentPartner->id,
                'partner_name' => $currentPartner->name,
                'level' => $currentLevel['level'],
                'partner_rate' => $currentRate,
                'lower_rate' => $lowerRate,
                'actual_rate' => $actualRate
            ]);

            // 실제 수수료가 있을 때만 커미션 생성
            if ($actualRate > 0) {
                // === Step 2.2.3: 커미션 레코드 생성 ===
                $commission = $this->createCommissionRecord(
                    $sales,
                    $currentPartner,
                    $currentLevel,
                    $actualRate,
                    $currentRate,
                    $lowerRate,
                    $partnerHierarchy
                );

                $commissions[] = $commission;

                Log::info('Commission created', [
                    'partner_id' => $currentPartner->id,
                    'commission_amount' => $commission['commission_amount'],
                    'actual_rate' => $actualRate
                ]);
            } else {
                Log::debug('No commission for partner (rate <= 0)', [
                    'partner_id' => $currentPartner->id,
                    'actual_rate' => $actualRate
                ]);
            }
        }

        Log::info('Hierarchical commission calculation completed', [
            'total_commissions_created' => count($commissions)
        ]);

        return $commissions;
    }

    /**
     * 직속 하위 파트너의 수수료율 찾기
     *
     * @param array $reversedHierarchy
     * @param int $currentIndex
     * @return float
     */
    private function getLowerPartnerRate(array $reversedHierarchy, int $currentIndex)
    {
        // 다음 인덱스가 직속 하위 파트너
        $lowerIndex = $currentIndex + 1;

        if ($lowerIndex < count($reversedHierarchy)) {
            return $reversedHierarchy[$lowerIndex]['commission_rate'];
        }

        return 0; // 최하위 파트너인 경우
    }

    /**
     * 커미션 레코드 생성
     *
     * @param PartnerSales $sales
     * @param PartnerUser $partner
     * @param array $levelInfo
     * @param float $actualRate
     * @param float $partnerRate
     * @param float $lowerRate
     * @param array $partnerHierarchy
     * @return array
     */
    private function createCommissionRecord(
        PartnerSales $sales,
        PartnerUser $partner,
        array $levelInfo,
        float $actualRate,
        float $partnerRate,
        float $lowerRate,
        array $partnerHierarchy
    ) {
        $salesAmount = $sales->amount;

        // 커미션 금액 계산
        $commissionAmount = ($salesAmount * $actualRate) / 100;
        $taxAmount = $commissionAmount * 0.1; // 10% 세금
        $netAmount = $commissionAmount - $taxAmount;

        // 커미션 타입 결정
        $isDirectSales = ($levelInfo['level'] == 0);
        $commissionType = $isDirectSales ? 'direct_sales' : 'indirect_referral';

        // 상세 계산 정보 (확장된 로그 정보)
        $calculationDetails = [
            'partner_full_rate' => $partnerRate,
            'lower_partner_rate' => $lowerRate,
            'actual_rate_difference' => $actualRate,
            'hierarchy_level' => $levelInfo['level'],
            'calculation_method' => 'hierarchical_difference',
            'formula' => "{$partnerRate}% - {$lowerRate}% = {$actualRate}%",

            // 매출 정보
            'sales_info' => [
                'sales_id' => $sales->id,
                'sales_amount' => $salesAmount,
                'sales_date' => $sales->sales_date ? $sales->sales_date->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
                'order_code' => $sales->order_code ?? 'SALES-' . $sales->id
            ],

            // 파트너 정보
            'partner_info' => [
                'partner_id' => $partner->id,
                'partner_name' => $partner->name,
                'partner_email' => $partner->email,
                'partner_type' => $partner->partnerType->type_name ?? null,
                'partner_tier' => $partner->partnerTier->tier_name ?? null,
                'tree_level' => $levelInfo['level']
            ],

            // 계산 시점 정보
            'calculation_timestamp' => now()->toISOString(),
            'calculated_by' => auth()->id() ?? 'system',
            'ip_address' => request()->ip() ?? null,

            // 세금 및 수수료 정보
            'tax_calculation' => [
                'tax_rate' => 10, // 10% 세금
                'gross_amount' => $commissionAmount,
                'tax_amount' => $taxAmount,
                'net_amount' => $netAmount
            ]
        ];

        // 설명 메모 (상세 정보 포함)
        $notes = $isDirectSales
            ? "직접 매출 커미션 ({$actualRate}%) - 매출코드: " . ($sales->order_code ?? 'SALES-' . $sales->id)
            : "계층 차액 커미션 ({$partnerRate}% - {$lowerRate}% = {$actualRate}%) - 매출코드: " . ($sales->order_code ?? 'SALES-' . $sales->id) . " / {$levelInfo['level']}단계 상위";

        // 데이터베이스에 저장 (완전한 로그 기록)
        $commission = PartnerCommission::create([
            'partner_id' => $partner->id,
            'source_partner_id' => $partnerHierarchy[0]['partner']->id, // 매출 파트너
            'order_id' => $sales->id,
            'commission_type' => $commissionType,
            'level_difference' => $levelInfo['level'],
            'tree_path_at_time' => json_encode($this->buildTreePath($partnerHierarchy, $levelInfo['level'])),
            'original_amount' => $salesAmount,
            'commission_rate' => $actualRate,
            'commission_amount' => $commissionAmount,
            'tax_amount' => $taxAmount,
            'net_amount' => $netAmount,
            'status' => 'calculated',
            'earned_at' => now(),
            'calculated_at' => now(),
            'calculation_details' => json_encode($calculationDetails),
            'notes' => $notes
        ]);

        // 파트너 balance에 커미션 누적 (세후 금액 기준)
        $partner->increment('earned_commissions', $netAmount);

        // 로그 기록
        Log::info('Commission record created and balance updated', [
            'commission_id' => $commission->id,
            'partner_id' => $partner->id,
            'partner_name' => $partner->name,
            'source_partner_id' => $partnerHierarchy[0]['partner']->id,
            'sales_id' => $sales->id,
            'order_code' => $sales->order_code ?? 'SALES-' . $sales->id,
            'commission_amount' => $commissionAmount,
            'commission_rate' => $actualRate,
            'commission_type' => $commissionType,
            'hierarchy_level' => $levelInfo['level'],
            'net_amount_added_to_balance' => $netAmount,
            'new_partner_balance' => $partner->fresh()->earned_commissions
        ]);

        return $commission->toArray();
    }

    /**
     * === 단일 파트너 커미션 계산 (계층이 없는 경우) ===
     *
     * @param PartnerSales $sales
     * @param PartnerUser $partner
     * @param int $level
     * @return array
     */
    private function calculateSinglePartnerCommission(PartnerSales $sales, PartnerUser $partner, int $level)
    {
        $commissionRate = $partner->getTotalCommissionRate();
        $commissionAmount = ($sales->amount * $commissionRate) / 100;
        $taxAmount = $commissionAmount * 0.1; // 10% 세금
        $netAmount = $commissionAmount - $taxAmount;

        Log::info('Calculating single partner commission', [
            'partner_id' => $partner->id,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount
        ]);

        $commission = PartnerCommission::create([
            'partner_id' => $partner->id,
            'source_partner_id' => $partner->id,
            'order_id' => $sales->id,
            'commission_type' => 'direct_sales',
            'level_difference' => $level,
            'tree_path_at_time' => json_encode([$partner->id]),
            'original_amount' => $sales->amount,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'tax_amount' => $taxAmount,
            'net_amount' => $netAmount,
            'status' => 'calculated',
            'earned_at' => now(),
            'calculation_details' => json_encode([
                'type_rate' => $partner->partnerType->default_commission_rate ?? 0,
                'tier_rate' => $partner->partnerTier->commission_rate ?? 0,
                'individual_rate' => $partner->individual_commission_rate ?? 0,
                'total_rate' => $commissionRate,
                'calculation_method' => 'single_partner'
            ]),
            'notes' => '단독 파트너 커미션 (계층 없음)'
        ]);

        // 파트너 balance에 커미션 누적 (세후 금액 기준)
        $partner->increment('earned_commissions', $netAmount);

        // 로그 기록
        Log::info('Single partner commission created and balance updated', [
            'commission_id' => $commission->id,
            'partner_id' => $partner->id,
            'partner_name' => $partner->name,
            'commission_amount' => $commissionAmount,
            'net_amount_added_to_balance' => $netAmount,
            'new_partner_balance' => $partner->fresh()->earned_commissions
        ]);

        return $commission->toArray();
    }

    // =================================================================
    // === 유틸리티 헬퍼 메소드들 ===
    // =================================================================

    /**
     * 계층 구조에서 트리 경로 생성
     * 매출 파트너부터 해당 레벨까지의 경로를 배열로 반환
     *
     * @param array $partnerHierarchy
     * @param int $targetLevel
     * @return array
     */
    private function buildTreePath(array $partnerHierarchy, int $targetLevel)
    {
        $path = [];
        foreach ($partnerHierarchy as $level) {
            $path[] = $level['partner_id'];
            if ($level['level'] == $targetLevel) {
                break;
            }
        }
        return $path;
    }

    /**
     * 상위 파트너 찾기
     *
     * TODO: 실제 구현에서는 파트너 계층 구조 테이블(partner_hierarchy) 사용 필요
     * 현재는 임시로 created_at 기준으로 상위 파트너 판정
     *
     * @param PartnerUser $partner
     * @return PartnerUser|null
     */
    private function findParentPartner(PartnerUser $partner)
    {
        // 임시 구현: created_at이 더 이른 파트너를 상위로 간주
        // 실제로는 referral_code, sponsor_id 등의 필드를 사용해야 함
        return PartnerUser::where('created_at', '<', $partner->created_at)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * 계층에서 최대 커미션율 찾기
     * 전체 커미션 분배의 상한선을 결정
     *
     * @param array $partnerHierarchy
     * @return float
     */
    private function getMaxCommissionRate(array $partnerHierarchy)
    {
        $maxRate = 0;
        foreach ($partnerHierarchy as $level) {
            if ($level['commission_rate'] > $maxRate) {
                $maxRate = $level['commission_rate'];
            }
        }
        return $maxRate;
    }

    /**
     * 계층 분석 정보 생성
     * 디버깅 및 리포팅용 상세 정보
     *
     * @param array $partnerHierarchy
     * @return array
     */
    private function getHierarchyBreakdown(array $partnerHierarchy)
    {
        $breakdown = [];
        foreach ($partnerHierarchy as $level) {
            $breakdown[] = [
                'level' => $level['level'],
                'partner_id' => $level['partner_id'],
                'partner_name' => $level['partner_name'],
                'commission_rate' => $level['commission_rate'],
                'level_description' => $level['level'] == 0
                    ? '매출 파트너 (직접 판매)'
                    : "{$level['level']}단계 상위 파트너"
            ];
        }
        return $breakdown;
    }

    // =================================================================
    // === 커미션 로그 관리 메소드들 ===
    // =================================================================

    /**
     * 주문 코드별 커미션 로그 조회
     *
     * @param string $orderCode
     * @return array
     */
    public static function getCommissionLogsByOrderCode($orderCode)
    {
        $commissions = PartnerCommission::where(function ($query) use ($orderCode) {
            $query->where('notes', 'like', '%' . $orderCode . '%');
        })
        ->with(['partner', 'sourcePartner'])
        ->orderBy('level_difference')
        ->orderBy('created_at')
        ->get();

        return [
            'order_code' => $orderCode,
            'total_commissions' => $commissions->count(),
            'total_amount' => $commissions->sum('commission_amount'),
            'total_tax' => $commissions->sum('tax_amount'),
            'total_net' => $commissions->sum('net_amount'),
            'commission_records' => $commissions->map(function ($commission) {
                return [
                    'id' => $commission->id,
                    'partner_name' => $commission->partner->name ?? 'Unknown',
                    'partner_email' => $commission->partner->email ?? 'Unknown',
                    'source_partner_name' => $commission->sourcePartner->name ?? 'Unknown',
                    'commission_type' => $commission->commission_type,
                    'level_difference' => $commission->level_difference,
                    'commission_rate' => $commission->commission_rate,
                    'commission_amount' => $commission->commission_amount,
                    'tax_amount' => $commission->tax_amount,
                    'net_amount' => $commission->net_amount,
                    'status' => $commission->status,
                    'earned_at' => $commission->earned_at,
                    'notes' => $commission->notes,
                    'calculation_details' => json_decode($commission->calculation_details, true)
                ];
            })
        ];
    }

    /**
     * 매출 ID별 커미션 로그 조회
     *
     * @param int $salesId
     * @return array
     */
    public static function getCommissionLogsBySalesId($salesId)
    {
        $commissions = PartnerCommission::where('order_id', $salesId)
            ->with(['partner', 'sourcePartner'])
            ->orderBy('level_difference')
            ->orderBy('created_at')
            ->get();

        $sales = \Jiny\Partner\Models\PartnerSales::find($salesId);

        return [
            'sales_id' => $salesId,
            'order_code' => $sales->order_code ?? 'SALES-' . $salesId,
            'sales_amount' => $sales->amount ?? 0,
            'total_commissions' => $commissions->count(),
            'total_commission_amount' => $commissions->sum('commission_amount'),
            'commission_records' => $commissions
        ];
    }

    /**
     * 파트너별 커미션 로그 요약
     *
     * @param int $partnerId
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public static function getPartnerCommissionSummary($partnerId, $startDate = null, $endDate = null)
    {
        $query = PartnerCommission::where('partner_id', $partnerId);

        if ($startDate && $endDate) {
            $query->whereBetween('earned_at', [$startDate, $endDate]);
        }

        $commissions = $query->get();

        return [
            'partner_id' => $partnerId,
            'period' => $startDate && $endDate ? [$startDate, $endDate] : 'all_time',
            'total_records' => $commissions->count(),
            'total_earned' => $commissions->sum('commission_amount'),
            'total_tax' => $commissions->sum('tax_amount'),
            'total_net' => $commissions->sum('net_amount'),
            'by_status' => $commissions->groupBy('status')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'amount' => $group->sum('commission_amount')
                ];
            }),
            'by_type' => $commissions->groupBy('commission_type')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'amount' => $group->sum('commission_amount')
                ];
            }),
            'recent_records' => $commissions->sortByDesc('earned_at')->take(10)->values()
        ];
    }
}