<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerApplication;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Illuminate\Http\Request;

class ChangeStatusController extends Controller
{
    /**
     * 지원서 상태 변경
     */
    public function __invoke(Request $request, $id)
    {
        $application = PartnerApplication::findOrFail($id);

        $request->validate([
            'status' => 'required|in:submitted,reviewing,interview,approved,rejected'
        ]);

        $application->update(['application_status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => '상태가 성공적으로 변경되었습니다.'
        ]);
    }
}