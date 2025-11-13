<?php

namespace Jiny\Partner\Http\Controllers\Home\Sales;

use Jiny\Partner\Http\Controllers\PartnerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Jiny\Partner\Models\PartnerSales;
use Jiny\Partner\Models\PartnerUser;

class EditController extends PartnerController
{
    /**
     * 파트너 매출 수정 폼 표시 (대기 상태만 수정 가능)
     */
    public function __invoke(Request $request, $id)
    {
        try {
            // Step 1: 사용자 인증 확인
            $user = $this->auth($request);
            if (!$user) {
                return redirect()->route('login')->with('error', '로그인이 필요합니다.');
            }

            // Step 2: 파트너 등록 여부 확인
            $partner = $this->isPartner($user);
            if (!$partner) {
                return redirect()->route('home.partner.intro')
                    ->with('info', '파트너 프로그램에 먼저 가입해 주세요.');
            }

            // Step 3: 매출 정보 조회
            $sale = PartnerSales::find($id);

            if (!$sale) {
                return redirect()->route('home.partner.sales.index')
                    ->with('error', '매출 정보를 찾을 수 없습니다.');
            }

            // Step 4: 권한 확인 (본인 매출만 수정 가능)
            if ($sale->partner_id !== $partner->id) {
                return redirect()->route('home.partner.sales.index')
                    ->with('error', '본인의 매출만 수정할 수 있습니다.');
            }

            // Step 5: 수정 가능한 상태인지 확인 (pending 상태만 수정 가능)
            if ($sale->status !== 'pending') {
                return redirect()->route('home.partner.sales.show', $sale->id)
                    ->with('error', '대기 상태의 매출만 수정할 수 있습니다.');
            }

            // Step 6: 관련 파트너 정보 조회 (표시용)
            $salePartner = PartnerUser::find($sale->partner_id);
            $salePartnerUser = $salePartner ? $this->getUserByPartner($salePartner) : null;
            $currentUser = $this->getUserByPartner($partner);

            $viewData = [
                'user' => $user,
                'partnerUser' => $partner,
                'sale' => $sale,
                'salePartner' => $salePartner,
                'salePartnerUser' => $salePartnerUser,
                'currentUser' => $currentUser,
                'pageTitle' => '매출 수정'
            ];

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'code' => 200,
                    'data' => $viewData
                ]);
            }

            return view('jiny-partner::home.sales.edit', $viewData);

        } catch (\Exception $e) {
            \Log::error('Partner sales edit error: ' . $e->getMessage(), [
                'user_id' => $user->id ?? 'unknown',
                'user_uuid' => $user->uuid ?? 'unknown',
                'sale_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('home.partner.sales.index')
                ->with('error', '매출 수정 화면을 불러오는 중 오류가 발생했습니다.');
        }
    }
}