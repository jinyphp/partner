<?php

namespace Jiny\Partner\Http\Controllers\Home\Commission;

use Jiny\Partner\Http\Controllers\PartnerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Models\PartnerTier;
use Jiny\Partner\Models\PartnerSales;

class CalculateController extends PartnerController
{
    /**
     * 수익 계산기
     */
    public function __invoke(Request $request)
    {
        try {
            // 세션 인증 확인
            $user = $this->auth($request);
            if (!$user) {
                return redirect()->route('login')->with('error', '로그인이 필요합니다.');
            }

            // 파트너 사용자 정보 조회 (UUID 기반)
            $partnerUser = PartnerUser::where('user_uuid', $user->uuid)->first();

            if (!$partnerUser) {
                // 파트너 신청 정보 확인
                $partnerApplication = \Jiny\Partner\Models\PartnerApplication::where('user_uuid', $user->uuid)
                    ->latest()
                    ->first();

                if ($partnerApplication) {
                    return redirect()->route('home.partner.regist.status', $partnerApplication->id)
                        ->with('info', '파트너 신청이 아직 처리 중입니다.');
                } else {
                    return redirect()->route('home.partner.intro')
                        ->with('info', '파트너 프로그램에 먼저 가입해 주세요.');
                }
            }

            // 계산기 입력값
            $saleAmount = $request->get('sale_amount', 0);
            $salesCount = $request->get('sales_count', 1);
            $period = $request->get('period', 'monthly'); // daily, weekly, monthly, yearly
            $includeReferrals = $request->boolean('include_referrals', false);
            $referralCount = $request->get('referral_count', 0);

            // 현재 파트너 등급 정보
            $currentTier = $partnerUser->tier;
            $commissionRate = $currentTier->commission_rate ?? 5; // 기본 5%

            // 기본 커미션 계산
            $directCommission = $this->calculateDirectCommission($saleAmount, $salesCount, $commissionRate);

            // 추천 보너스 계산
            $referralBonus = 0;
            if ($includeReferrals && $referralCount > 0) {
                $referralBonus = $this->calculateReferralBonus($saleAmount, $referralCount, $currentTier);
            }

            // 기간별 수익 예상
            $projectedEarnings = $this->calculatePeriodProjections($directCommission + $referralBonus, $period);

            // 등급별 비교 계산
            $tierComparison = $this->calculateTierComparison($saleAmount, $salesCount);

            // 목표 달성 계산
            $goalCalculations = $this->calculateGoalAchievement($partnerUser, $saleAmount, $salesCount);

            // 세금 및 수수료 계산
            $netEarnings = $this->calculateNetEarnings($directCommission + $referralBonus);

            // 성장 시뮬레이션
            $growthSimulation = $this->calculateGrowthSimulation($saleAmount, $salesCount, $commissionRate);

            $viewData = [
                'user' => $user,
                'partnerUser' => $partnerUser,
                'currentTier' => $currentTier,
                'calculations' => [
                    'sale_amount' => $saleAmount,
                    'sales_count' => $salesCount,
                    'commission_rate' => $commissionRate,
                    'direct_commission' => $directCommission,
                    'referral_bonus' => $referralBonus,
                    'total_commission' => $directCommission + $referralBonus,
                    'projected_earnings' => $projectedEarnings,
                    'net_earnings' => $netEarnings
                ],
                'tierComparison' => $tierComparison,
                'goalCalculations' => $goalCalculations,
                'growthSimulation' => $growthSimulation,
                'allTiers' => PartnerTier::where('is_active', true)->orderBy('priority_level')->get(),
                'pageTitle' => '수익 계산기'
            ];

            if ($request->wantsJson()) {
                return $this->successResponse($viewData);
            }

            return view('jiny-partner::home.commission.calculate', $viewData);

        } catch (\Exception $e) {
            \Log::error('Partner commission calculate error: ' . $e->getMessage(), [
                'user_id' => $user->id ?? 'unknown',
                'user_uuid' => $user->uuid ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('home.partner.commission.index')
                ->with('error', '수익 계산 중 오류가 발생했습니다.');
        }
    }

    /**
     * 직접 판매 커미션 계산
     */
    private function calculateDirectCommission($saleAmount, $salesCount, $commissionRate)
    {
        return ($saleAmount * $salesCount * $commissionRate) / 100;
    }

    /**
     * 추천 보너스 계산
     */
    private function calculateReferralBonus($saleAmount, $referralCount, $tier)
    {
        $referralRate = $tier->referral_bonus_rate ?? 1; // 기본 1%
        return ($saleAmount * $referralCount * $referralRate) / 100;
    }

    /**
     * 기간별 수익 예상 계산
     */
    private function calculatePeriodProjections($baseEarning, $period)
    {
        $multipliers = [
            'daily' => 1,
            'weekly' => 7,
            'monthly' => 30,
            'yearly' => 365
        ];

        $projections = [];
        foreach ($multipliers as $periodType => $multiplier) {
            if ($period === 'daily') {
                $projections[$periodType] = $baseEarning * $multiplier;
            } elseif ($period === 'weekly') {
                $projections[$periodType] = ($baseEarning / 7) * $multiplier;
            } elseif ($period === 'monthly') {
                $projections[$periodType] = ($baseEarning / 30) * $multiplier;
            } else { // yearly
                $projections[$periodType] = ($baseEarning / 365) * $multiplier;
            }
        }

        return $projections;
    }

    /**
     * 등급별 비교 계산
     */
    private function calculateTierComparison($saleAmount, $salesCount)
    {
        $tiers = PartnerTier::where('is_active', true)->orderBy('priority_level')->get();
        $comparison = [];

        foreach ($tiers as $tier) {
            $commission = $this->calculateDirectCommission($saleAmount, $salesCount, $tier->commission_rate);
            $comparison[] = [
                'tier' => $tier,
                'commission' => $commission,
                'difference' => $commission
            ];
        }

        return $comparison;
    }

    /**
     * 목표 달성 계산
     */
    private function calculateGoalAchievement($partnerUser, $saleAmount, $salesCount)
    {
        // 다음 등급까지 필요한 실적
        $nextTier = PartnerTier::where('priority_level', '<', $partnerUser->tier->priority_level)
            ->where('is_active', true)
            ->orderBy('priority_level', 'desc')
            ->first();

        if (!$nextTier) {
            return null;
        }

        // 현재 월 실적
        $currentMonthSales = PartnerSales::where('partner_id', $partnerUser->id)
            ->whereBetween('created_at', [
                now()->startOfMonth(),
                now()->endOfMonth()
            ])
            ->sum('amount');

        // 다음 등급 승급 조건 (예시)
        $requiredAmount = $nextTier->min_sales_amount ?? 1000000; // 기본 100만원
        $remainingAmount = max(0, $requiredAmount - $currentMonthSales);
        $remainingSales = $saleAmount > 0 ? ceil($remainingAmount / $saleAmount) : 0;

        return [
            'current_tier' => $partnerUser->tier,
            'next_tier' => $nextTier,
            'current_amount' => $currentMonthSales,
            'required_amount' => $requiredAmount,
            'remaining_amount' => $remainingAmount,
            'remaining_sales' => $remainingSales,
            'progress_percentage' => min(100, ($currentMonthSales / $requiredAmount) * 100)
        ];
    }

    /**
     * 세금 및 수수료 제외한 순수익 계산
     */
    private function calculateNetEarnings($grossEarnings)
    {
        $taxRate = 10; // 10% 세금 (예시)
        $serviceFee = 2; // 2% 서비스 수수료 (예시)

        $tax = ($grossEarnings * $taxRate) / 100;
        $fee = ($grossEarnings * $serviceFee) / 100;
        $netEarnings = $grossEarnings - $tax - $fee;

        return [
            'gross_earnings' => $grossEarnings,
            'tax' => $tax,
            'service_fee' => $fee,
            'net_earnings' => $netEarnings,
            'tax_rate' => $taxRate,
            'service_fee_rate' => $serviceFee
        ];
    }

    /**
     * 성장 시뮬레이션 계산
     */
    private function calculateGrowthSimulation($saleAmount, $salesCount, $commissionRate)
    {
        $months = [];
        $earnings = [];
        $baseMonthlyEarning = $this->calculateDirectCommission($saleAmount, $salesCount * 30, $commissionRate);

        for ($i = 1; $i <= 12; $i++) {
            $months[] = $i . '개월';
            // 매월 10% 성장 가정
            $growthRate = pow(1.1, $i - 1);
            $earnings[] = $baseMonthlyEarning * $growthRate;
        }

        return [
            'months' => $months,
            'earnings' => $earnings,
            'total_year_earning' => array_sum($earnings)
        ];
    }
}