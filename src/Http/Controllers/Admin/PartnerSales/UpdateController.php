<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerSales;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerSales;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Services\CommissionCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UpdateController extends Controller
{
    protected $routePrefix;
    protected $commissionService;

    public function __construct(CommissionCalculationService $commissionService)
    {
        $this->routePrefix = 'partner.sales';
        $this->commissionService = $commissionService;
    }

    /**
     * 파트너 매출 업데이트
     */
    public function __invoke(Request $request, $id)
    {
        $sales = PartnerSales::findOrFail($id);

        // 수정 권한 확인
        $canEdit = $this->canEditSales($sales);
        if (!$canEdit['allowed']) {
            return back()->withErrors(['error' => $canEdit['reason']])
                         ->withInput();
        }

        // 동적 유효성 검증 규칙 생성
        $validationRules = $this->buildValidationRules($sales, $request);

        $validated = $request->validate($validationRules);

        try {
            DB::beginTransaction();

            // 이전 상태 저장
            $previousStatus = $sales->status;
            $previousAmount = $sales->amount;
            $wasCommissionCalculated = $sales->commission_calculated;

            // 파트너 정보 업데이트 (파트너 변경이 허용된 경우)
            if (isset($validated['partner_id']) && $validated['partner_id'] != $sales->partner_id) {
                $partner = PartnerUser::find($validated['partner_id']);
                if ($partner) {
                    $validated['partner_name'] = $partner->name;
                    $validated['partner_email'] = $partner->email;
                }
            }

            // 기본 정보 업데이트
            $updateData = array_merge($validated, [
                'updated_by' => Auth::id(),
            ]);

            // 상태 변경 처리
            if (isset($validated['status']) && $validated['status'] !== $previousStatus) {
                $updateData = array_merge($updateData, $this->handleStatusChange($sales, $validated['status']));
            }

            $sales->update($updateData);

            // 커미션 재계산이 필요한 경우
            if ($this->needsCommissionRecalculation($sales, $previousStatus, $previousAmount, $wasCommissionCalculated)) {
                $recalculationResult = $this->handleCommissionRecalculation($sales, $wasCommissionCalculated);

                if (isset($recalculationResult['message'])) {
                    session()->flash('commission_result', $recalculationResult);
                }
            }

            DB::commit();

            $successMessage = '매출 정보가 성공적으로 수정되었습니다.';

            return redirect()
                ->route('admin.' . $this->routePrefix . '.show', $sales->id)
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withErrors(['error' => '매출 수정 중 오류가 발생했습니다: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * 매출 수정 권한 확인
     */
    private function canEditSales(PartnerSales $sales)
    {
        // 취소된 매출은 상태 변경만 허용
        if ($sales->status === 'cancelled') {
            return ['allowed' => true, 'reason' => null];
        }

        // 환불된 매출은 수정 불가
        if ($sales->status === 'refunded') {
            return ['allowed' => false, 'reason' => '환불된 매출은 수정할 수 없습니다.'];
        }

        return ['allowed' => true, 'reason' => null];
    }

    /**
     * 동적 유효성 검증 규칙 생성
     */
    private function buildValidationRules(PartnerSales $sales, Request $request)
    {
        $rules = [];

        // 커미션 계산 여부에 따른 규칙
        $isCommissionCalculated = $sales->commission_calculated;
        $isConfirmed = $sales->status === 'confirmed';

        // 기본 규칙
        if (!$isCommissionCalculated) {
            $rules['partner_id'] = [
                'sometimes',
                'required',
                'exists:partner_users,id'
            ];

            $rules['amount'] = [
                'sometimes',
                'required',
                'numeric',
                'min:0',
                'max:999999999999.99'
            ];

            $rules['currency'] = [
                'sometimes',
                'required',
                'string',
                'in:KRW,USD,EUR,JPY'
            ];

            $rules['sales_date'] = [
                'sometimes',
                'required',
                'date',
                'before_or_equal:today'
            ];
        }

        // 항상 수정 가능한 필드들
        $rules['title'] = 'sometimes|required|string|max:200';
        $rules['description'] = 'sometimes|nullable|string|max:1000';
        $rules['category'] = 'sometimes|nullable|string|max:50';
        $rules['product_type'] = 'sometimes|nullable|string|max:50';
        $rules['sales_channel'] = 'sometimes|nullable|string|max:50';
        $rules['admin_notes'] = 'sometimes|nullable|string|max:1000';
        $rules['external_reference'] = 'sometimes|nullable|string|max:100';

        // 주문번호는 고유성 검증 (자신 제외)
        if (!$isCommissionCalculated) {
            $rules['order_number'] = [
                'sometimes',
                'nullable',
                'string',
                'max:100',
                Rule::unique('partner_sales', 'order_number')->ignore($sales->id)
            ];
        }

        // 상태 변경
        $rules['status'] = 'sometimes|required|in:pending,confirmed,cancelled,refunded';

        // 승인 관련
        $rules['requires_approval'] = 'sometimes|boolean';
        $rules['approval_notes'] = 'sometimes|nullable|string|max:1000';

        return $rules;
    }

    /**
     * 상태 변경 처리
     */
    private function handleStatusChange(PartnerSales $sales, $newStatus)
    {
        $updateData = [];

        switch ($newStatus) {
            case 'confirmed':
                $updateData['confirmed_at'] = now();
                if (!$sales->is_approved) {
                    $updateData['is_approved'] = true;
                    $updateData['approved_by'] = Auth::id();
                    $updateData['approved_at'] = now();
                }
                break;

            case 'cancelled':
                $updateData['cancelled_at'] = now();
                break;

            case 'pending':
                $updateData['confirmed_at'] = null;
                $updateData['cancelled_at'] = null;
                break;
        }

        return $updateData;
    }

    /**
     * 커미션 재계산 필요 여부 확인
     */
    private function needsCommissionRecalculation(PartnerSales $sales, $previousStatus, $previousAmount, $wasCommissionCalculated)
    {
        // 상태가 confirmed로 변경되고 아직 커미션이 계산되지 않은 경우
        if ($sales->status === 'confirmed' && $previousStatus !== 'confirmed' && !$wasCommissionCalculated) {
            return true;
        }

        // 이미 커미션이 계산된 상태에서 취소된 경우
        if ($sales->status === 'cancelled' && $wasCommissionCalculated) {
            return true;
        }

        return false;
    }

    /**
     * 커미션 재계산 처리
     */
    private function handleCommissionRecalculation(PartnerSales $sales, $wasCommissionCalculated)
    {
        try {
            if ($sales->status === 'confirmed' && !$wasCommissionCalculated) {
                // 새로운 커미션 계산
                $result = $this->commissionService->calculateAndDistribute($sales);

                return [
                    'type' => 'success',
                    'message' => '커미션이 자동으로 계산되어 ' .
                               $result['recipients_count'] . '명에게 총 ' .
                               number_format($result['total_commission']) . '원이 분배되었습니다.',
                    'data' => $result,
                ];

            } elseif ($sales->status === 'cancelled' && $wasCommissionCalculated) {
                // 커미션 역계산
                $this->commissionService->reverseCalculation($sales);

                return [
                    'type' => 'info',
                    'message' => '매출 취소로 인해 커미션이 회수되었습니다.',
                ];
            }

        } catch (\Exception $e) {
            return [
                'type' => 'warning',
                'message' => '커미션 계산 중 오류가 발생했습니다: ' . $e->getMessage(),
            ];
        }

        return null;
    }
}