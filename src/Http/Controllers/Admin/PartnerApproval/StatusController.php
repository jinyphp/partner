<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerApproval;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    /**
     * Handle the request
     */
    public function __invoke(Request $request, $id = null)
    {
        // TODO: Implement StatusController logic
        return response()->json([
            'success' => true,
            'message' => 'StatusController functionality is under development'
        ]);
    }
}
