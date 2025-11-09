<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerType;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerType;

class EditController extends Controller
{
    protected $model;
    protected $viewPath;
    protected $routePrefix;
    protected $title;

    public function __construct()
    {
        $this->model = PartnerType::class;
        $this->viewPath = 'jiny-partner::admin.partner-type';
        $this->routePrefix = 'partner.type';
        $this->title = '파트너 타입';
    }

    /**
     * 파트너 타입 수정 폼
     */
    public function __invoke($id)
    {
        $item = $this->model::findOrFail($id);

        // 기본 전문 분야 옵션
        $defaultSpecialties = [
            'sales' => '영업',
            'technical_support' => '기술지원',
            'customer_service' => '고객서비스',
            'marketing' => '마케팅',
            'training' => '교육',
            'consulting' => '컨설팅',
            'project_management' => '프로젝트 관리',
            'business_development' => '사업개발'
        ];

        // 기본 스킬 옵션
        $defaultSkills = [
            'communication' => '의사소통',
            'negotiation' => '협상',
            'presentation' => '프레젠테이션',
            'product_knowledge' => '제품지식',
            'technical_expertise' => '기술전문성',
            'problem_solving' => '문제해결',
            'leadership' => '리더십',
            'analytical_thinking' => '분석적 사고'
        ];

        // 기본 권한 옵션
        $defaultPermissions = [
            'can_handle_enterprise' => '대기업 고객 담당',
            'can_provide_technical_support' => '기술지원 제공',
            'can_conduct_training' => '교육 진행',
            'can_access_sensitive_data' => '민감정보 접근',
            'can_approve_discounts' => '할인 승인',
            'can_manage_contracts' => '계약 관리'
        ];

        return view($this->viewPath . '.edit', [
            'item' => $item,
            'title' => $this->title,
            'routePrefix' => $this->routePrefix,
            'defaultSpecialties' => $defaultSpecialties,
            'defaultSkills' => $defaultSkills,
            'defaultPermissions' => $defaultPermissions
        ]);
    }
}