<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerType;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerType;
use Illuminate\Http\Request;

class DestroyController extends Controller
{
    protected $model;
    protected $routePrefix;
    protected $title;

    public function __construct()
    {
        $this->model = PartnerType::class;
        $this->routePrefix = 'partner.type';
        $this->title = '파트너 타입';
    }

    /**
     * 파트너 타입 삭제
     */
    public function __invoke(Request $request, $id)
    {
        $item = $this->model::findOrFail($id);

        // 이 타입을 사용하는 파트너가 있는지 확인
        $partnersCount = $item->partners()->count();

        if ($partnersCount > 0) {
            return back()->with('error',
                "이 타입을 사용하는 파트너가 {$partnersCount}명 있어 삭제할 수 없습니다. 먼저 파트너들의 타입을 변경해주세요."
            );
        }

        try {
            $item->delete();

            return redirect()->route("admin.{$this->routePrefix}.index")
                ->with('success', $this->title . ' 항목이 성공적으로 삭제되었습니다.');

        } catch (\Exception $e) {
            return back()->with('error', '삭제 중 오류가 발생했습니다: ' . $e->getMessage());
        }
    }
}