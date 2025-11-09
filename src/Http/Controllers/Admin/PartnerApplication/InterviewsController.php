<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerApplication;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Illuminate\Http\Request;

class InterviewsController extends Controller
{
    /**
     * Handle the request
     */
    public function __invoke(Request $request, $id = null)
    {
        // TODO: Implement InterviewsController logic
        return response()->json([
            'success' => true,
            'message' => 'InterviewsController functionality is under development'
        ]);
    }
}
