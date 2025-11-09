<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerApproval;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Illuminate\Http\Request;

class CompleteInterviewController extends Controller
{
    /**
     * Handle the request
     */
    public function __invoke(Request $request, $id = null)
    {
        // TODO: Implement CompleteInterviewController logic
        return response()->json([
            'success' => true,
            'message' => 'CompleteInterviewController functionality is under development'
        ]);
    }
}
