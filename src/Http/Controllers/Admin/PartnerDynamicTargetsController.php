<?php

namespace Jiny\Partner\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Models\PartnerDynamicTarget;
use Jiny\Partner\Models\PartnerType;
use Jiny\Partner\Models\PartnerTier;

/**
 * 파트너 동적 목표 관리 컨트롤러
 *
 * 개인별 맞춤 목표 설정, 성과 추적, 보너스 계산 등의 기능을 제공
 */
class PartnerDynamicTargetsController extends Controller
{
    /**
     * 동적 목표 목록 페이지
     */
    public function index(Request $request)
    {
        $partnerId = $request->get('partner_id');

        if (!$partnerId) {
            return redirect()->route('admin.partner.users.index')
                ->with('error', '파트너를 선택해주세요.');
        }

        // 파트너 정보 조회
        $partner = PartnerUser::with(['partnerType', 'partnerTier'])->find($partnerId);

        if (!$partner) {
            return redirect()->route('admin.partner.users.index')
                ->with('error', '해당 파트너를 찾을 수 없습니다.');
        }

        // 목표 목록 조회 (최신순)
        $targets = PartnerDynamicTarget::where('partner_user_id', $partnerId)
            ->with(['createdBy', 'approvedBy'])
            ->orderBy('target_year', 'desc')
            ->orderBy('target_month', 'desc')
            ->orderBy('target_quarter', 'desc')
            ->paginate(15);

        // 현재 활성 목표 조회
        $activeTargets = PartnerDynamicTarget::where('partner_user_id', $partnerId)
            ->where('status', 'active')
            ->orderBy('target_year', 'desc')
            ->orderBy('target_month', 'desc')
            ->get();

        // 전체 성과 통계
        $performanceStats = $this->getPerformanceStats($partnerId);

        return view('jiny-partner::admin.partner-dynamic-targets.index', compact(
            'partner',
            'targets',
            'activeTargets',
            'performanceStats'
        ));
    }

    /**
     * 새 목표 생성 페이지
     */
    public function create(Request $request)
    {
        $partnerId = $request->get('partner_id');

        if (!$partnerId) {
            return redirect()->route('admin.partner.users.index')
                ->with('error', '파트너를 선택해주세요.');
        }

        $partner = PartnerUser::with(['partnerType', 'partnerTier'])->find($partnerId);

        if (!$partner) {
            return redirect()->route('admin.partner.users.index')
                ->with('error', '해당 파트너를 찾을 수 없습니다.');
        }

        // 기본 목표 계산
        $baseTargets = $this->calculateBaseTargets($partner);

        return view('jiny-partner::admin.partner-dynamic-targets.create', compact('partner', 'baseTargets'));
    }

    /**
     * 목표 저장
     */
    public function store(Request $request)
    {
        $request->validate([
            'partner_user_id' => 'required|exists:partner_users,id',
            'target_period_type' => 'required|in:monthly,quarterly,yearly',
            'target_year' => 'required|integer|min:2020|max:2050',
            'target_month' => 'nullable|integer|min:1|max:12',
            'target_quarter' => 'nullable|integer|min:1|max:4',
            'personal_adjustment_factor' => 'required|numeric|min:0.1|max:5.0',
            'market_condition_factor' => 'required|numeric|min:0.1|max:3.0',
            'seasonal_adjustment_factor' => 'required|numeric|min:0.1|max:3.0',
            'quality_score_target' => 'required|numeric|min:0|max:100',
            'customer_satisfaction_target' => 'required|numeric|min:0|max:100',
            'response_time_target' => 'required|numeric|min:1|max:168',
            'setting_notes' => 'nullable|string|max:1000'
        ]);

        DB::beginTransaction();

        try {
            $partner = PartnerUser::with(['partnerType', 'partnerTier'])->find($request->partner_user_id);
            $baseTargets = $this->calculateBaseTargets($partner);

            // 최종 목표 계산
            $finalTargets = $this->calculateFinalTargets($baseTargets, $request->all());

            $target = PartnerDynamicTarget::create([
                'partner_user_id' => $request->partner_user_id,
                'target_period_type' => $request->target_period_type,
                'target_year' => $request->target_year,
                'target_month' => $request->target_month,
                'target_quarter' => $request->target_quarter,

                // 기본 목표
                'base_sales_target' => $baseTargets['sales'],
                'base_cases_target' => $baseTargets['cases'],
                'base_revenue_target' => $baseTargets['revenue'],
                'base_clients_target' => $baseTargets['clients'],

                // 조정 계수
                'personal_adjustment_factor' => $request->personal_adjustment_factor,
                'market_condition_factor' => $request->market_condition_factor,
                'seasonal_adjustment_factor' => $request->seasonal_adjustment_factor,
                'team_performance_factor' => 1.0, // 기본값

                // 최종 목표
                'final_sales_target' => $finalTargets['sales'],
                'final_cases_target' => $finalTargets['cases'],
                'final_revenue_target' => $finalTargets['revenue'],
                'final_clients_target' => $finalTargets['clients'],

                // 추가 목표
                'quality_score_target' => $request->quality_score_target,
                'customer_satisfaction_target' => $request->customer_satisfaction_target,
                'response_time_target' => $request->response_time_target,

                // 보너스 설정 (기본값)
                'bonus_tier_config' => json_encode([
                    '150' => ['rate' => 3.0, 'description' => '150% 초과달성'],
                    '120' => ['rate' => 2.0, 'description' => '120% 우수달성'],
                    '100' => ['rate' => 1.0, 'description' => '100% 목표달성'],
                    '80' => ['rate' => 0.5, 'description' => '80% 부분달성']
                ]),

                'setting_notes' => $request->setting_notes,
                'status' => 'draft',
                'created_by' => auth()->id(),
                'auto_calculate_enabled' => true,
                'last_calculated_at' => now(),
                'next_review_date' => $this->calculateNextReviewDate($request->target_period_type)
            ]);

            DB::commit();

            Log::info('Partner dynamic target created', [
                'target_id' => $target->id,
                'partner_id' => $request->partner_user_id,
                'created_by' => auth()->id()
            ]);

            return redirect()
                ->route('admin.partner.targets.index', ['partner_id' => $request->partner_user_id])
                ->with('success', '목표가 성공적으로 생성되었습니다.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create partner dynamic target', [
                'error' => $e->getMessage(),
                'partner_id' => $request->partner_user_id
            ]);

            return back()->withInput()
                ->with('error', '목표 생성 중 오류가 발생했습니다: ' . $e->getMessage());
        }
    }

    /**
     * 목표 상세 조회
     */
    public function show(PartnerDynamicTarget $target)
    {
        $target->load(['partnerUser.partnerType', 'partnerUser.partnerTier', 'createdBy', 'approvedBy']);

        // 성과 차트 데이터
        $chartData = $this->getPerformanceChartData($target);

        // 달성 마일스톤
        $milestones = json_decode($target->achievement_milestones, true) ?? [];

        return view('jiny-partner::admin.partner-dynamic-targets.show', compact('target', 'chartData', 'milestones'));
    }

    /**
     * 목표 수정 페이지
     */
    public function edit(PartnerDynamicTarget $target)
    {
        if (in_array($target->status, ['completed', 'cancelled'])) {
            return back()->with('error', '완료되거나 취소된 목표는 수정할 수 없습니다.');
        }

        $target->load(['partnerUser.partnerType', 'partnerUser.partnerTier']);
        $baseTargets = $this->calculateBaseTargets($target->partnerUser);

        return view('jiny-partner::admin.partner-dynamic-targets.edit', compact('target', 'baseTargets'));
    }

    /**
     * 목표 업데이트
     */
    public function update(Request $request, PartnerDynamicTarget $target)
    {
        if (in_array($target->status, ['completed', 'cancelled'])) {
            return back()->with('error', '완료되거나 취소된 목표는 수정할 수 없습니다.');
        }

        $request->validate([
            'personal_adjustment_factor' => 'required|numeric|min:0.1|max:5.0',
            'market_condition_factor' => 'required|numeric|min:0.1|max:3.0',
            'seasonal_adjustment_factor' => 'required|numeric|min:0.1|max:3.0',
            'quality_score_target' => 'required|numeric|min:0|max:100',
            'customer_satisfaction_target' => 'required|numeric|min:0|max:100',
            'response_time_target' => 'required|numeric|min:1|max:168',
            'setting_notes' => 'nullable|string|max:1000'
        ]);

        DB::beginTransaction();

        try {
            $baseTargets = [
                'sales' => $target->base_sales_target,
                'cases' => $target->base_cases_target,
                'revenue' => $target->base_revenue_target,
                'clients' => $target->base_clients_target
            ];

            $finalTargets = $this->calculateFinalTargets($baseTargets, $request->all());

            $target->update([
                'personal_adjustment_factor' => $request->personal_adjustment_factor,
                'market_condition_factor' => $request->market_condition_factor,
                'seasonal_adjustment_factor' => $request->seasonal_adjustment_factor,

                'final_sales_target' => $finalTargets['sales'],
                'final_cases_target' => $finalTargets['cases'],
                'final_revenue_target' => $finalTargets['revenue'],
                'final_clients_target' => $finalTargets['clients'],

                'quality_score_target' => $request->quality_score_target,
                'customer_satisfaction_target' => $request->customer_satisfaction_target,
                'response_time_target' => $request->response_time_target,

                'setting_notes' => $request->setting_notes,
                'last_calculated_at' => now()
            ]);

            DB::commit();

            return redirect()
                ->route('admin.partner.targets.show', $target)
                ->with('success', '목표가 성공적으로 업데이트되었습니다.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', '목표 업데이트 중 오류가 발생했습니다: ' . $e->getMessage());
        }
    }

    /**
     * 목표 승인
     */
    public function approve(Request $request, PartnerDynamicTarget $target)
    {
        if ($target->status !== 'pending_approval') {
            return back()->with('error', '승인 대기 상태의 목표만 승인할 수 있습니다.');
        }

        $request->validate([
            'approval_notes' => 'nullable|string|max:500'
        ]);

        $target->update([
            'status' => 'approved',
            'approval_notes' => $request->approval_notes,
            'approved_by' => auth()->id(),
            'approved_at' => now()
        ]);

        return back()->with('success', '목표가 승인되었습니다.');
    }

    /**
     * 목표 활성화
     */
    public function activate(PartnerDynamicTarget $target)
    {
        if ($target->status !== 'approved') {
            return back()->with('error', '승인된 목표만 활성화할 수 있습니다.');
        }

        // 동일 기간의 다른 활성 목표가 있는지 확인
        $existingActive = PartnerDynamicTarget::where('partner_user_id', $target->partner_user_id)
            ->where('target_period_type', $target->target_period_type)
            ->where('target_year', $target->target_year)
            ->where('target_month', $target->target_month)
            ->where('target_quarter', $target->target_quarter)
            ->where('status', 'active')
            ->where('id', '!=', $target->id)
            ->first();

        if ($existingActive) {
            return back()->with('error', '동일 기간에 이미 활성화된 목표가 있습니다.');
        }

        $target->update([
            'status' => 'active',
            'activated_at' => now()
        ]);

        return back()->with('success', '목표가 활성화되었습니다.');
    }

    /**
     * 목표 삭제
     */
    public function destroy(PartnerDynamicTarget $target)
    {
        if (in_array($target->status, ['active', 'completed'])) {
            return back()->with('error', '활성화되거나 완료된 목표는 삭제할 수 없습니다.');
        }

        $partnerId = $target->partner_user_id;
        $target->delete();

        return redirect()
            ->route('admin.partner.targets.index', ['partner_id' => $partnerId])
            ->with('success', '목표가 삭제되었습니다.');
    }

    /**
     * 기본 목표 계산
     */
    private function calculateBaseTargets(PartnerUser $partner): array
    {
        $partnerType = $partner->partnerType;
        $partnerTier = $partner->partnerTier;

        // 기본 계산 (타입별 최소 기준 × 등급별 승수)
        $baseSales = ($partnerType?->minimum_sales_target ?? 1000000) * ($partnerTier?->target_multiplier ?? 1.0);
        $baseCases = ($partnerType?->minimum_cases_target ?? 10) * ($partnerTier?->target_multiplier ?? 1.0);
        $baseRevenue = $baseSales * 0.1; // 매출의 10%를 수익 목표로
        $baseClients = max(5, intval($baseCases * 0.8)); // 건수의 80%를 고객 수로

        return [
            'sales' => $baseSales,
            'cases' => intval($baseCases),
            'revenue' => $baseRevenue,
            'clients' => $baseClients
        ];
    }

    /**
     * 최종 목표 계산
     */
    private function calculateFinalTargets(array $baseTargets, array $factors): array
    {
        $personalFactor = $factors['personal_adjustment_factor'] ?? 1.0;
        $marketFactor = $factors['market_condition_factor'] ?? 1.0;
        $seasonalFactor = $factors['seasonal_adjustment_factor'] ?? 1.0;

        $totalFactor = $personalFactor * $marketFactor * $seasonalFactor;

        return [
            'sales' => $baseTargets['sales'] * $totalFactor,
            'cases' => intval($baseTargets['cases'] * $totalFactor),
            'revenue' => $baseTargets['revenue'] * $totalFactor,
            'clients' => intval($baseTargets['clients'] * $totalFactor)
        ];
    }

    /**
     * 성과 통계 조회
     */
    private function getPerformanceStats(int $partnerId): array
    {
        $stats = PartnerDynamicTarget::where('partner_user_id', $partnerId)
            ->where('status', 'active')
            ->selectRaw('
                COUNT(*) as active_targets,
                AVG(overall_achievement_rate) as avg_achievement,
                SUM(calculated_bonus_amount) as total_bonus
            ')
            ->first();

        return [
            'active_targets' => $stats->active_targets ?? 0,
            'avg_achievement' => round($stats->avg_achievement ?? 0, 1),
            'total_bonus' => $stats->total_bonus ?? 0
        ];
    }

    /**
     * 성과 차트 데이터
     */
    private function getPerformanceChartData(PartnerDynamicTarget $target): array
    {
        // 월별 성과 추이 데이터를 반환
        return [
            'labels' => ['1월', '2월', '3월', '4월', '5월', '6월'],
            'target' => [100, 100, 100, 100, 100, 100],
            'achievement' => [85, 92, 78, 105, 110, 95]
        ];
    }

    /**
     * 다음 검토 일자 계산
     */
    private function calculateNextReviewDate(string $periodType): \Carbon\Carbon
    {
        return match($periodType) {
            'monthly' => now()->addMonth(),
            'quarterly' => now()->addMonths(3),
            'yearly' => now()->addYear(),
            default => now()->addMonth()
        };
    }
}