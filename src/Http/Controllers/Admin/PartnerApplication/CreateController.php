<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerApplication;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerTier;

/**
 * 파트너 신청서 등록 폼 컨트롤러
 */
class CreateController extends Controller
{
    /**
     * 신청서 등록 폼 표시
     */
    public function __invoke()
    {
        $partnerTiers = PartnerTier::where('is_active', true)->orderBy('priority_level')->get();

        return view('jiny-partner::admin.partner-applications.create', [
            'title' => '파트너 신청서 등록',
            'routePrefix' => 'partner.applications',
            'partnerTiers' => $partnerTiers,
            'statusOptions' => $this->getStatusOptions()
        ]);
    }

    /**
     * 상태 옵션 반환
     */
    private function getStatusOptions()
    {
        return [
            'submitted' => '제출됨',
            'reviewing' => '검토중',
            'interview' => '면접예정',
            'approved' => '승인됨',
            'rejected' => '거부됨'
        ];
    }
}