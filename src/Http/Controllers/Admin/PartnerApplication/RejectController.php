<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerApplication;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Illuminate\Http\Request;

class RejectController extends Controller
{
    /**
     * 지원서 거절
     */
    public function __invoke(Request $request, $id)
    {
        $application = PartnerApplication::findOrFail($id);

        $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        $application->reject(auth()->id(), $request->rejection_reason);

        return response()->json([
            'success' => true,
            'message' => '지원서가 거절되었습니다.'
        ]);
    }
}