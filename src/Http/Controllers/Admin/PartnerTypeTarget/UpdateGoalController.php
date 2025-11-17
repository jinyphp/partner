<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerTypeTarget;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerType;
use Illuminate\Http\Request;

/**
 * 파트너 타입별 목표 업데이트 컨트롤러
 *
 * =======================================================================
 * 📊 핵심 기능
 * =======================================================================
 * ✓ 타입별 최소 기준치 업데이트
 * ✓ 수수료 정책 조정
 * ✓ 성과 목표 재설정
 * ✓ 변경 이력 추적
 */
class UpdateGoalController extends Controller
{
    /**
     * 파트너 타입 목표 업데이트
     */
    public function __invoke(Request $request, $typeId)
    {
        $partnerType = PartnerType::findOrFail($typeId);

        $validated = $request->validate([
            // 성과 기준 필드
            'min_baseline_sales' => 'nullable|numeric|min:0',
            'min_baseline_cases' => 'nullable|integer|min:0',
            'min_baseline_revenue' => 'nullable|numeric|min:0',
            'min_baseline_clients' => 'nullable|integer|min:0',
            'baseline_quality_score' => 'nullable|numeric|min:0|max:100',

            // 수수료 관련 필드
            'default_commission_type' => 'nullable|in:percentage,fixed_amount',
            'default_commission_rate' => 'nullable|numeric|min:0|max:100',
            'default_commission_amount' => 'nullable|numeric|min:0',
            'commission_notes' => 'nullable|string|max:1000',

            // 비용 관련 필드
            'registration_fee' => 'nullable|numeric|min:0',
            'monthly_maintenance_fee' => 'nullable|numeric|min:0',
            'annual_maintenance_fee' => 'nullable|numeric|min:0',
            'fee_waiver_available' => 'nullable|boolean',
            'fee_structure_notes' => 'nullable|string|max:1000',

            // 관리 메모
            'admin_notes' => 'nullable|string',
            'update_reason' => 'required|string|max:500', // 업데이트 사유
        ]);

        // 변경 전 데이터 저장 (로깅용)
        $oldData = $partnerType->only([
            'min_baseline_sales',
            'min_baseline_cases',
            'min_baseline_revenue',
            'min_baseline_clients',
            'baseline_quality_score',
            'default_commission_type',
            'default_commission_rate',
            'default_commission_amount',
            'commission_notes',
            'registration_fee',
            'monthly_maintenance_fee',
            'annual_maintenance_fee',
            'fee_waiver_available',
            'fee_structure_notes',
            'admin_notes',
        ]);

        // 업데이트 실행
        $validated['updated_by'] = auth()->id();

        // 수수료 타입에 따른 자동 조정
        if ($validated['default_commission_type'] === 'percentage') {
            $validated['default_commission_amount'] = 0;
        } elseif ($validated['default_commission_type'] === 'fixed_amount') {
            $validated['default_commission_rate'] = 0;
        }

        $partnerType->update($validated);

        // 변경 이력 로깅
        $this->logGoalUpdate($partnerType, $oldData, $validated, $request->update_reason);

        return redirect()
            ->route('admin.partner.type.target.detail', $typeId)
            ->with('success', $partnerType->type_name . ' 타입의 목표 설정이 업데이트되었습니다.');
    }

    /**
     * 목표 변경 이력 로깅
     */
    private function logGoalUpdate($partnerType, $oldData, $newData, $reason)
    {
        $changes = [];

        foreach ($oldData as $key => $oldValue) {
            $newValue = $newData[$key] ?? $oldValue;

            if ($oldValue != $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue
                ];
            }
        }

        if (!empty($changes)) {
            \Log::info('파트너 타입 목표 업데이트', [
                'partner_type_id' => $partnerType->id,
                'partner_type_name' => $partnerType->type_name,
                'updated_by' => auth()->id(),
                'update_reason' => $reason,
                'changes' => $changes,
                'timestamp' => now()->toISOString(),
            ]);

            // 데이터베이스에 변경 이력 저장 (선택사항 - 별도 테이블 필요)
            /*
            DB::table('partner_type_goal_changes')->insert([
                'partner_type_id' => $partnerType->id,
                'updated_by' => auth()->id(),
                'update_reason' => $reason,
                'changes' => json_encode($changes),
                'created_at' => now(),
            ]);
            */
        }
    }
}