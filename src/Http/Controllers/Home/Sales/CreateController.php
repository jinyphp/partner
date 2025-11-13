<?php

namespace Jiny\Partner\Http\Controllers\Home\Sales;

use Jiny\Partner\Http\Controllers\PartnerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Jiny\Partner\Models\PartnerSales;
use Jiny\Partner\Models\PartnerUser;

class CreateController extends PartnerController
{
    /**
     * 파트너 매출 등록 폼 페이지
     */
    public function __invoke(Request $request)
    {
        try {
            // 파트너 인증 및 정보 조회 (공통 로직 사용)
            $authResult = $this->authenticateAndGetPartner($request, 'sales_create');
            if (!$authResult['success']) {
                return $authResult['redirect'];
            }

            $user = $authResult['user'];
            $partnerUser = $authResult['partner'];

            $viewData = [
                'user' => $user,
                'partnerUser' => $partnerUser,
                'pageTitle' => '매출 등록'
            ];

            if ($request->wantsJson()) {
                return $this->successResponse($viewData);
            }

            return view('jiny-partner::home.sales.create', $viewData);

        } catch (\Exception $e) {
            \Log::error('Partner sales create page error: ' . $e->getMessage(), [
                'user_id' => $user->id ?? 'unknown',
                'user_uuid' => $user->uuid ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('home.partner.sales.index')
                ->with('error', '매출 등록 페이지를 불러오는 중 오류가 발생했습니다.');
        }
    }

}