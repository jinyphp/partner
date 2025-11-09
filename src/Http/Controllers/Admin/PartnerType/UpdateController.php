<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerType;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerType;
use Illuminate\Http\Request;

class UpdateController extends Controller
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
     * 파트너 타입 수정
     */
    public function __invoke(Request $request, $id)
    {
        $item = $this->model::findOrFail($id);
        $validated = $request->validate($this->getValidationRules($item));

        // 배열 필드 처리
        $validated['specialties'] = $this->processArrayField($request->specialties);
        $validated['required_skills'] = $this->processArrayField($request->required_skills);
        $validated['certifications'] = $this->processArrayField($request->certifications);
        $validated['permissions'] = $this->processArrayField($request->permissions);
        $validated['access_levels'] = $this->processArrayField($request->access_levels);
        $validated['training_requirements'] = $this->processArrayField($request->training_requirements);

        // 체크박스 필드 처리
        $validated['is_active'] = $request->has('is_active');

        // 수정자 정보 추가
        $validated['updated_by'] = auth()->id();

        $item->update($validated);

        return redirect()->route("admin.{$this->routePrefix}.show", $item->id)
            ->with('success', $this->title . ' 항목이 성공적으로 수정되었습니다.');
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

    protected function getValidationRules($item = null): array
    {
        $typeCodeRule = 'required|string|max:20';
        if ($item) {
            $typeCodeRule .= '|unique:partner_types,type_code,' . $item->id;
        } else {
            $typeCodeRule .= '|unique:partner_types,type_code';
        }

        return [
            'type_code' => $typeCodeRule,
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