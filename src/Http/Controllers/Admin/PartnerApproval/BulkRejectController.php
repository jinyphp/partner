<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerApproval;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Illuminate\Http\Request;

class BulkRejectController extends Controller
{
    /**
     * Handle the request
     */
    public function __invoke(Request $request, $id = null)
    {
        // TODO: Implement BulkRejectController logic
        return response()->json([
            'success' => true,
            'message' => 'BulkRejectController functionality is under development'
        ]);
    }
}
