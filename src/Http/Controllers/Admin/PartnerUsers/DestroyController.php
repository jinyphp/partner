<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerUsers;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerUser;

class DestroyController extends Controller
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
     * 파트너 회원 삭제
     */
    public function __invoke($id)
    {
        $item = $this->model::findOrFail($id);

        // 소프트 삭제 실행
        $item->delete();

        return redirect()->route("admin.{$this->routePrefix}.index")
            ->with('success', $this->title . ' 항목이 성공적으로 삭제되었습니다.');
    }

    protected function getValidationRules($item = null): array
    {
        return [];
    }
}