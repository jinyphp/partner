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

class StoreController extends Controller
{
    protected $routePrefix;
    protected $commissionService;

    public function __construct(CommissionCalculationService $commissionService)
    {
        $this->routePrefix = 'partner.sales';
        $this->commissionService = $commissionService;
    }

    /**
     * 파트너 매출 저장
     */
    public function __invoke(Request $request)
    {
        // 유효성 검증
        $validated = $request->validate([
            'partner_id' => 'required|exists:partner_users,id',
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|numeric|min:0|max:999999999999.99',
            'currency' => 'required|string|in:KRW,USD,EUR,JPY',
            'sales_date' => 'required|date|before_or_equal:today',
            'order_number' => 'nullable|string|max:100|unique:partner_sales,order_number',
            'category' => 'nullable|string|max:50',
            'product_type' => 'nullable|string|max:50',
            'sales_channel' => 'nullable|string|max:50',
            'status' => 'required|in:pending,confirmed',
            'requires_approval' => 'boolean',
            'admin_notes' => 'nullable|string|max:1000',
            'auto_calculate_commission' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            // 파트너 정보 조회
            $partner = PartnerUser::find($validated['partner_id']);
            if (!$partner) {
                return back()->withErrors(['partner_id' => '파트너를 찾을 수 없습니다.'])
                           ->withInput();
            }

            // 주문번호 자동 생성 (입력되지 않은 경우)
            if (empty($validated['order_number'])) {
                $validated['order_number'] = $this->generateOrderNumber();
            }

            // 승인 설정
            $requiresApproval = $validated['requires_approval'] ?? false;
            $isApproved = !$requiresApproval || $validated['status'] === 'confirmed';

            // 매출 레코드 생성
            $salesData = array_merge($validated, [
                'partner_name' => $partner->name,
                'partner_email' => $partner->email,
                'requires_approval' => $requiresApproval,
                'is_approved' => $isApproved,
                'approved_by' => $isApproved ? Auth::id() : null,
                'approved_at' => $isApproved ? now() : null,
                'confirmed_at' => $validated['status'] === 'confirmed' ? now() : null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $sales = PartnerSales::create($salesData);

            // 자동 커미션 계산 (확정 상태이고 자동 계산 옵션이 선택된 경우)
            if ($validated['status'] === 'confirmed' &&
                ($validated['auto_calculate_commission'] ?? false)) {

                try {
                    $commissionResult = $this->commissionService->calculateAndDistribute($sales);

                    $successMessage = '매출이 성공적으로 등록되었습니다.';
                    if ($commissionResult['success']) {
                        $successMessage .= ' 커미션도 자동으로 계산되어 ' .
                                         $commissionResult['recipients_count'] . '명에게 총 ' .
                                         number_format($commissionResult['total_commission']) . '원이 분배되었습니다.';
                    }

                    session()->flash('commission_result', $commissionResult);
                } catch (\Exception $e) {
                    // 커미션 계산 실패 시에도 매출 등록은 성공으로 처리
                    $successMessage = '매출이 등록되었으나 커미션 계산에 실패했습니다: ' . $e->getMessage();
                }
            } else {
                $successMessage = '매출이 성공적으로 등록되었습니다.';
                if ($validated['status'] === 'confirmed') {
                    $successMessage .= ' 커미션 계산은 별도로 실행해주세요.';
                }
            }

            DB::commit();

            return redirect()
                ->route('admin.' . $this->routePrefix . '.show', $sales->id)
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withErrors(['error' => '매출 등록 중 오류가 발생했습니다: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * 주문번호 자동 생성
     */
    private function generateOrderNumber()
    {
        $prefix = 'PS-' . now()->format('Ymd') . '-';
        $lastOrder = PartnerSales::where('order_number', 'like', $prefix . '%')
                                ->orderBy('order_number', 'desc')
                                ->first();

        if ($lastOrder) {
            $lastNumber = (int) substr($lastOrder->order_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}