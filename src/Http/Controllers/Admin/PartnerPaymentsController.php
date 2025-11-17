<?php

namespace Jiny\Partner\Http\Controllers\Admin;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PartnerPaymentsController extends BaseController
{
    /**
     * 지급 목록
     */
    public function index(Request $request)
    {
        $query = DB::table('partner_payments as pp')
            ->leftJoin('partner_users as pu', 'pp.partner_id', '=', 'pu.id')
            ->leftJoin('users as u', 'pu.user_id', '=', 'u.id')
            ->select([
                'pp.*',
                'u.name as partner_name_current',
                'u.email as partner_email_current',
                'pu.tier_level'
            ]);

        // 필터링
        if ($request->filled('partner_id')) {
            $query->where('pp.partner_id', $request->partner_id);
        }

        if ($request->filled('status')) {
            $query->where('pp.status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('pp.payment_method', $request->payment_method);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('pp.requested_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('pp.requested_at', '<=', $request->date_to);
        }

        if ($request->filled('min_amount')) {
            $query->where('pp.final_amount', '>=', $request->min_amount);
        }

        if ($request->filled('batch_id')) {
            $query->where('pp.batch_id', $request->batch_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('pp.payment_code', 'LIKE', "%{$search}%")
                  ->orWhere('pp.partner_name', 'LIKE', "%{$search}%")
                  ->orWhere('pp.partner_email', 'LIKE', "%{$search}%")
                  ->orWhere('u.name', 'LIKE', "%{$search}%")
                  ->orWhere('u.email', 'LIKE', "%{$search}%");
            });
        }

        $payments = $query->orderBy('pp.requested_at', 'desc')
            ->paginate(20);

        // 통계 데이터
        $stats = $this->getPaymentStats();

        // 파트너 목록
        $partners = DB::table('partner_users as pu')
            ->leftJoin('users as u', 'pu.user_id', '=', 'u.id')
            ->select(['pu.id', 'u.name', 'u.email'])
            ->orderBy('u.name')
            ->get();

        return view('jiny-partner::admin.partner-payments.index', compact('payments', 'stats', 'partners'));
    }

    /**
     * 지급 상세 보기
     */
    public function show($id)
    {
        $payment = DB::table('partner_payments as pp')
            ->leftJoin('partner_users as pu', 'pp.partner_id', '=', 'pu.id')
            ->leftJoin('users as u', 'pu.user_id', '=', 'u.id')
            ->leftJoin('users as approver', 'pp.approved_by', '=', 'approver.id')
            ->leftJoin('users as processor', 'pp.processed_by', '=', 'processor.id')
            ->select([
                'pp.*',
                'u.name as partner_name_current',
                'u.email as partner_email_current',
                'pu.tier_level',
                'approver.name as approver_name',
                'processor.name as processor_name'
            ])
            ->where('pp.id', $id)
            ->first();

        if (!$payment) {
            return redirect()->back()->with('error', '지급 내역을 찾을 수 없습니다.');
        }

        // JSON 필드 파싱
        $payment->metadata = json_decode($payment->metadata, true) ?? [];
        $payment->external_response = json_decode($payment->external_response, true) ?? [];

        // 포함된 커미션 목록
        $commissionItems = DB::table('partner_payment_items as ppi')
            ->leftJoin('partner_commissions as pc', 'ppi.commission_id', '=', 'pc.id')
            ->select([
                'ppi.*',
                'pc.commission_type',
                'pc.sales_id',
                'pc.reference_type',
                'pc.reference_id',
                'pc.earned_date'
            ])
            ->where('ppi.payment_id', $id)
            ->get();

        // 동일 배치의 다른 지급들 (배치 지급인 경우)
        $batchPayments = null;
        if ($payment->batch_id) {
            $batchPayments = DB::table('partner_payments')
                ->where('batch_id', $payment->batch_id)
                ->where('id', '!=', $id)
                ->get();
        }

        return view('jiny-partner::admin.partner-payments.show', compact('payment', 'commissionItems', 'batchPayments'));
    }

    /**
     * 지급 신청 폼
     */
    public function create(Request $request)
    {
        $partnerId = $request->get('partner_id');
        $partner = null;

        if ($partnerId) {
            $partner = DB::table('partner_users as pu')
                ->leftJoin('users as u', 'pu.user_id', '=', 'u.id')
                ->select(['pu.*', 'u.name as partner_name', 'u.email as partner_email'])
                ->where('pu.id', $partnerId)
                ->first();
        }

        // 파트너 목록
        $partners = DB::table('partner_users as pu')
            ->leftJoin('users as u', 'pu.user_id', '=', 'u.id')
            ->select(['pu.id', 'u.name', 'u.email'])
            ->orderBy('u.name')
            ->get();

        // 지급 가능한 커미션 조회 (파트너별)
        $availableCommissions = [];
        if ($partnerId) {
            $availableCommissions = $this->getAvailableCommissions($partnerId);
        }

        return view('jiny-partner::admin.partner-payments.create', compact('partner', 'partners', 'availableCommissions'));
    }

    /**
     * 지급 신청 저장
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'partner_id' => 'required|exists:partner_users,id',
            'requested_amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:bank_transfer,cash,check,digital_wallet',
            'bank_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'account_holder' => 'nullable|string|max:100',
            'fee_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'commission_ids' => 'nullable|array',
            'commission_ids.*' => 'exists:partner_commissions,id',
        ]);

        // 파트너 정보 조회
        $partner = DB::table('partner_users as pu')
            ->leftJoin('users as u', 'pu.user_id', '=', 'u.id')
            ->select(['pu.*', 'u.name', 'u.email'])
            ->where('pu.id', $validatedData['partner_id'])
            ->first();

        // 최종 지급액 계산
        $feeAmount = $validatedData['fee_amount'] ?? 0;
        $taxAmount = $validatedData['tax_amount'] ?? 0;
        $finalAmount = $validatedData['requested_amount'] - $feeAmount - $taxAmount;

        // 지급 코드 생성
        $paymentCode = $this->generatePaymentCode();

        $paymentData = [
            'partner_id' => $validatedData['partner_id'],
            'partner_name' => $partner->name,
            'partner_email' => $partner->email,
            'payment_code' => $paymentCode,
            'requested_amount' => $validatedData['requested_amount'],
            'fee_amount' => $feeAmount,
            'tax_amount' => $taxAmount,
            'final_amount' => $finalAmount,
            'payment_method' => $validatedData['payment_method'],
            'bank_name' => $validatedData['bank_name'],
            'account_number' => $validatedData['account_number'],
            'account_holder' => $validatedData['account_holder'],
            'status' => 'requested',
            'requested_at' => now(),
            'notes' => $validatedData['notes'],
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $paymentId = DB::table('partner_payments')->insertGetId($paymentData);

        // 커미션 항목들 연결
        if (!empty($validatedData['commission_ids'])) {
            $this->attachCommissions($paymentId, $validatedData['commission_ids']);
        }

        return redirect()
            ->route('admin.partner.payments.show', $paymentId)
            ->with('success', '지급 신청이 성공적으로 등록되었습니다.');
    }

    /**
     * 지급 수정 폼
     */
    public function edit($id)
    {
        $payment = DB::table('partner_payments as pp')
            ->leftJoin('partner_users as pu', 'pp.partner_id', '=', 'pu.id')
            ->leftJoin('users as u', 'pu.user_id', '=', 'u.id')
            ->select([
                'pp.*',
                'u.name as partner_name_current',
                'u.email as partner_email_current'
            ])
            ->where('pp.id', $id)
            ->first();

        if (!$payment) {
            return redirect()->back()->with('error', '지급 내역을 찾을 수 없습니다.');
        }

        // 승인된 지급은 수정 불가
        if (in_array($payment->status, ['processing', 'completed'])) {
            return redirect()->back()->with('error', '처리 중이거나 완료된 지급은 수정할 수 없습니다.');
        }

        // 파트너 목록
        $partners = DB::table('partner_users as pu')
            ->leftJoin('users as u', 'pu.user_id', '=', 'u.id')
            ->select(['pu.id', 'u.name', 'u.email'])
            ->orderBy('u.name')
            ->get();

        return view('jiny-partner::admin.partner-payments.edit', compact('payment', 'partners'));
    }

    /**
     * 지급 업데이트
     */
    public function update(Request $request, $id)
    {
        $payment = DB::table('partner_payments')->where('id', $id)->first();

        if (!$payment) {
            return redirect()->back()->with('error', '지급 내역을 찾을 수 없습니다.');
        }

        // 승인된 지급은 수정 불가
        if (in_array($payment->status, ['processing', 'completed'])) {
            return redirect()->back()->with('error', '처리 중이거나 완료된 지급은 수정할 수 없습니다.');
        }

        $validatedData = $request->validate([
            'requested_amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:bank_transfer,cash,check,digital_wallet',
            'bank_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'account_holder' => 'nullable|string|max:100',
            'fee_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        // 최종 지급액 재계산
        $feeAmount = $validatedData['fee_amount'] ?? 0;
        $taxAmount = $validatedData['tax_amount'] ?? 0;
        $finalAmount = $validatedData['requested_amount'] - $feeAmount - $taxAmount;

        $validatedData['final_amount'] = $finalAmount;
        $validatedData['updated_at'] = now();

        DB::table('partner_payments')
            ->where('id', $id)
            ->update($validatedData);

        return redirect()
            ->route('admin.partner.payments.show', $id)
            ->with('success', '지급 정보가 성공적으로 수정되었습니다.');
    }

    /**
     * 지급 삭제
     */
    public function destroy($id)
    {
        $payment = DB::table('partner_payments')->where('id', $id)->first();

        if (!$payment) {
            return redirect()->back()->with('error', '지급 내역을 찾을 수 없습니다.');
        }

        // 승인된 지급은 삭제 불가
        if ($payment->status !== 'requested') {
            return redirect()->back()->with('error', '신청 상태의 지급만 삭제할 수 있습니다.');
        }

        DB::table('partner_payments')->where('id', $id)->delete();

        return redirect()
            ->route('admin.partner.payments.index')
            ->with('success', '지급 신청이 삭제되었습니다.');
    }

    /**
     * 지급 승인
     */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'approval_notes' => 'nullable|string|max:500'
        ]);

        $payment = DB::table('partner_payments')->where('id', $id)->first();

        if (!$payment || $payment->status !== 'requested') {
            return redirect()->back()->with('error', '승인할 수 없는 지급입니다.');
        }

        DB::table('partner_payments')
            ->where('id', $id)
            ->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => Auth::id(),
                'approval_notes' => $request->approval_notes,
                'updated_at' => now(),
            ]);

        return redirect()->back()->with('success', '지급이 승인되었습니다.');
    }

    /**
     * 지급 처리 (송금 시작)
     */
    public function process(Request $request, $id)
    {
        $request->validate([
            'processing_notes' => 'nullable|string|max:500'
        ]);

        $payment = DB::table('partner_payments')->where('id', $id)->first();

        if (!$payment || $payment->status !== 'approved') {
            return redirect()->back()->with('error', '처리할 수 없는 지급입니다.');
        }

        DB::table('partner_payments')
            ->where('id', $id)
            ->update([
                'status' => 'processing',
                'processed_at' => now(),
                'processed_by' => Auth::id(),
                'processing_notes' => $request->processing_notes,
                'updated_at' => now(),
            ]);

        return redirect()->back()->with('success', '지급 처리가 시작되었습니다.');
    }

    /**
     * 지급 완료
     */
    public function complete(Request $request, $id)
    {
        $request->validate([
            'external_transaction_id' => 'nullable|string|max:100'
        ]);

        $payment = DB::table('partner_payments')->where('id', $id)->first();

        if (!$payment || $payment->status !== 'processing') {
            return redirect()->back()->with('error', '완료할 수 없는 지급입니다.');
        }

        DB::table('partner_payments')
            ->where('id', $id)
            ->update([
                'status' => 'completed',
                'completed_at' => now(),
                'external_transaction_id' => $request->external_transaction_id,
                'updated_at' => now(),
            ]);

        return redirect()->back()->with('success', '지급이 완료되었습니다.');
    }

    /**
     * 지급 취소
     */
    public function cancel(Request $request, $id)
    {
        $request->validate([
            'failure_reason' => 'required|string|max:500'
        ]);

        $payment = DB::table('partner_payments')->where('id', $id)->first();

        if (!$payment || in_array($payment->status, ['completed', 'cancelled'])) {
            return redirect()->back()->with('error', '취소할 수 없는 지급입니다.');
        }

        DB::table('partner_payments')
            ->where('id', $id)
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'failure_reason' => $request->failure_reason,
                'updated_at' => now(),
            ]);

        return redirect()->back()->with('success', '지급이 취소되었습니다.');
    }

    /**
     * 통계 데이터 조회
     */
    private function getPaymentStats()
    {
        $currentMonth = date('Y-m');

        return [
            'total_payments' => DB::table('partner_payments')->count(),
            'pending_approval' => DB::table('partner_payments')->where('status', 'requested')->count(),
            'processing_payments' => DB::table('partner_payments')->whereIn('status', ['approved', 'processing'])->count(),
            'completed_this_month' => DB::table('partner_payments')
                ->where('status', 'completed')
                ->whereRaw("strftime('%Y-%m', completed_at) = ?", [$currentMonth])
                ->count(),
            'total_amount_this_month' => DB::table('partner_payments')
                ->where('status', 'completed')
                ->whereRaw("strftime('%Y-%m', completed_at) = ?", [$currentMonth])
                ->sum('final_amount'),
            'avg_payment_amount' => DB::table('partner_payments')
                ->where('status', 'completed')
                ->avg('final_amount'),
        ];
    }

    /**
     * 지급 코드 생성
     */
    private function generatePaymentCode()
    {
        $date = date('Ymd');
        $prefix = 'PAY-' . $date . '-';

        $lastCode = DB::table('partner_payments')
            ->where('payment_code', 'LIKE', $prefix . '%')
            ->orderBy('payment_code', 'desc')
            ->value('payment_code');

        if ($lastCode) {
            $lastNumber = (int) substr($lastCode, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * 지급 가능한 커미션 조회
     */
    private function getAvailableCommissions($partnerId)
    {
        return DB::table('partner_commissions as pc')
            ->leftJoin('partner_payment_items as ppi', 'pc.id', '=', 'ppi.commission_id')
            ->select([
                'pc.*',
                DB::raw('CASE WHEN ppi.commission_id IS NULL THEN 1 ELSE 0 END as available')
            ])
            ->where('pc.partner_id', $partnerId)
            ->where('pc.status', 'confirmed')
            ->whereNull('ppi.commission_id') // 아직 지급에 포함되지 않은 것들
            ->orderBy('pc.earned_date', 'desc')
            ->get();
    }

    /**
     * 커미션과 지급 연결
     */
    private function attachCommissions($paymentId, $commissionIds)
    {
        $items = [];
        foreach ($commissionIds as $commissionId) {
            $commission = DB::table('partner_commissions')->where('id', $commissionId)->first();
            if ($commission) {
                $items[] = [
                    'payment_id' => $paymentId,
                    'commission_id' => $commissionId,
                    'commission_amount' => $commission->amount,
                    'included_amount' => $commission->amount,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!empty($items)) {
            DB::table('partner_payment_items')->insert($items);
        }
    }
}