<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerType;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerType;
use Illuminate\Http\Request;

class StoreController extends Controller
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
     * 파트너 타입 생성
     */
    public function __invoke(Request $request)
    {
        $validated = $request->validate($this->getValidationRules());

        // 배열 필드 처리
        $validated['specialties'] = $this->processArrayField($request->specialties);
        $validated['required_skills'] = $this->processArrayField($request->required_skills);
        $validated['certifications'] = $this->processArrayField($request->certifications);
        $validated['permissions'] = $this->processArrayField($request->permissions);
        $validated['access_levels'] = $this->processArrayField($request->access_levels);
        $validated['training_requirements'] = $this->processArrayField($request->training_requirements);

        // 체크박스 필드 처리
        $validated['is_active'] = $request->has('is_active');

        // 생성자 정보 추가
        $validated['created_by'] = auth()->id();

        $item = $this->model::create($validated);

        return redirect()->route("admin.{$this->routePrefix}.show", $item->id)
            ->with('success', $this->title . ' 항목이 성공적으로 생성되었습니다.');
    }

    /**
     * 배열 필드 처리
     */
    private function processArrayField($field)
    {
        if (is_string($field)) {
            return array_filter(array_map('trim', explode(',', $field)));
        }
        return is_array($field) ? array_filter($field) : [];
    }

    protected function getValidationRules(): array
    {
        return [
            'type_code' => 'required|string|max:20|unique:partner_types,type_code',
            'type_name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
            'color' => 'nullable|string|max:7|regex:/^#[a-fA-F0-9]{6}$/',
            'specialties' => 'nullable',
            'required_skills' => 'nullable',
            'certifications' => 'nullable',
            'target_sales_amount' => 'nullable|numeric|min:0',
            'target_support_cases' => 'nullable|integer|min:0',
            'commission_bonus_rate' => 'nullable|numeric|min:0|max:100',
            'permissions' => 'nullable',
            'access_levels' => 'nullable',
            'training_requirements' => 'nullable',
            'training_hours_required' => 'nullable|integer|min:0',
            'certification_valid_until' => 'nullable|date|after:today',
            'admin_notes' => 'nullable|string'
        ];
    }
}