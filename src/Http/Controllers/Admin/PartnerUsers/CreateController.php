<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerUsers;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Models\PartnerTier;
use Jiny\Partner\Models\PartnerType;

class CreateController extends Controller
{
    protected $model;
    protected $viewPath;
    protected $routePrefix;
    protected $title;

    public function __construct()
    {
        $this->model = PartnerUser::class;
        $this->viewPath = 'jiny-partner::admin.partner-users';
        $this->routePrefix = 'partner.users';
        $this->title = '파트너 회원';
    }

    /**
     * 파트너 회원 생성 폼 표시
     */
    public function __invoke()
    {
        // 활성화된 파트너 타입 목록
        $partnerTypes = PartnerType::active()->orderBy('sort_order', 'asc')->orderBy('type_name', 'asc')->get();

        // 활성화된 파트너 등급 목록
        $partnerTiers = PartnerTier::active()->orderBy('priority_level')->get();

        // 상태 옵션
        $statusOptions = [
            'pending' => '대기',
            'active' => '활성',
            'inactive' => '비활성',
            'suspended' => '정지'
        ];

        return view("{$this->viewPath}.create", [
            'title' => $this->title,
            'routePrefix' => $this->routePrefix,
            'partnerTypes' => $partnerTypes,
            'partnerTiers' => $partnerTiers,
            'statusOptions' => $statusOptions
        ]);
    }

    protected function getValidationRules($item = null): array
    {
        return [];
    }
}