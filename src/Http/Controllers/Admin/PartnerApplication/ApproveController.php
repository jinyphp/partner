<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerApplication;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Illuminate\Http\Request;

class ApproveController extends Controller
{
    /**
     * 지원서 승인
     */
    public function __invoke(Request $request, $id)
    {
        $application = PartnerApplication::findOrFail($id);

        if ($application->application_status !== 'interview') {
            return response()->json([
                'error' => '면접 완료된 지원서만 승인할 수 있습니다.'
            ], 400);
        }

        try {
            $partnerEngineer = $application->approve(auth()->id());

            return response()->json([
                'success' => true,
                'message' => '지원서가 승인되었습니다. 파트너 엔지니어 계정이 생성되었습니다.',
                'partner_engineer_id' => $partnerEngineer->id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => '승인 처리 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }
}