<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerApplication;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;

class ShowController extends Controller
{
    /**
     * 파트너 지원서 상세보기
     */
    public function __invoke($id)
    {
        $item = PartnerApplication::with(['user', 'approver', 'rejector', 'referrerPartner'])->findOrFail($id);

        // 완성도 점수 계산
        $completenessScore = $item->getCompletenessScore();

        return view('jiny-partner::admin.partner-applications.show', [
            'item' => $item,
            'completenessScore' => $completenessScore,
            'title' => '파트너 지원서',
            'routePrefix' => 'applications'
        ]);
    }
}