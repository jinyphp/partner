<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerApproval;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Illuminate\Http\Request;

class ReferrerController extends Controller
{
    /**
     * Handle the request
     */
    public function __invoke(Request $request, $id = null)
    {
        // TODO: Implement ReferrerController logic
        return response()->json([
            'success' => true,
            'message' => 'ReferrerController functionality is under development'
        ]);
    }
}
