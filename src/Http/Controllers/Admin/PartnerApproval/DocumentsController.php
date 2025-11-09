<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerApproval;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Illuminate\Http\Request;

class DocumentsController extends Controller
{
    /**
     * 파트너 신청서 문서 관리
     */
    public function __invoke(Request $request, $id)
    {
        $application = PartnerApplication::findOrFail($id);

        return view('jiny-partner::admin.partner-approval.documents', [
            'application' => $application,
            'documents' => $application->documents ?? [],
            'title' => '신청서 문서 관리'
        ]);
    }
}