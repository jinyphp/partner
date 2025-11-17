<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerType;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
     * 파트너 타입 수정 처리
     *
     * 마이그레이션 구조에 맞춘 완전한 업데이트 처리:
     * - 모든 27개 필드 지원 (partner_tiers_count 제외 - 자동 계산)
     * - JSON 필드 올바른 처리
     * - 수수료 타입별 조건부 검증 및 처리
     * - 체크박스 필드 명시적 boolean 변환
     * - 기존 데이터와의 무결성 보장
     */
    public function __invoke(Request $request, $id)
    {
        $item = $this->model::findOrFail($id);
        $validated = $request->validate($this->getValidationRules($item, $request));

        // JSON 배열 필드 처리 (StoreController와 동일)
        $validated['specialties'] = $this->processJsonField($request->specialties);
        $validated['required_skills'] = $this->processJsonField($request->required_skills);

        // 체크박스 필드 처리 (명시적 boolean 변환)
        $validated['is_active'] = $request->boolean('is_active');
        $validated['fee_waiver_available'] = $request->boolean('fee_waiver_available');

        // 수수료 타입별 조건부 처리
        if ($validated['default_commission_type'] === 'percentage') {
            $validated['default_commission_amount'] = null;
        } else {
            $validated['default_commission_rate'] = null;
        }

        // 시스템 필드 설정
        $validated['updated_by'] = auth()->id();
        // partner_tiers_count는 자동 계산되므로 제외

        try {
            $oldData = [
                'type_code' => $item->type_code,
                'type_name' => $item->type_name,
                'is_active' => $item->is_active
            ];

            $item->update($validated);

            // 중요 필드 변경 시 로그 생성
            $this->logImportantChanges($item, $oldData, $validated);

            return redirect()->route("admin.{$this->routePrefix}.show", $item->id)
                ->with('success', "{$this->title} '{$item->type_name}'이 성공적으로 수정되었습니다.");
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', '파트너 타입 수정 중 오류가 발생했습니다: ' . $e->getMessage());
        }
    }

    /**
     * JSON 필드 처리 (StoreController와 동일)
     */
    private function processJsonField($field)
    {
        if (is_string($field)) {
            // 콤마 구분 문자열을 배열로 변환
            $array = array_filter(array_map('trim', explode(',', $field)));
        } elseif (is_array($field)) {
            // 이미 배열인 경우 필터링
            $array = array_filter($field);
        } else {
            $array = [];
        }

        return empty($array) ? null : array_values($array);
    }

    /**
     * 중요 필드 변경 로깅
     */
    private function logImportantChanges($item, $oldData, $newData)
    {
        $changes = [];

        if ($oldData['type_code'] !== $newData['type_code']) {
            $changes[] = "타입 코드: {$oldData['type_code']} → {$newData['type_code']}";
        }

        if ($oldData['type_name'] !== $newData['type_name']) {
            $changes[] = "타입 이름: {$oldData['type_name']} → {$newData['type_name']}";
        }

        if ($oldData['is_active'] !== $newData['is_active']) {
            $status = $newData['is_active'] ? '활성화' : '비활성화';
            $changes[] = "상태: {$status}";
        }

        if (!empty($changes)) {
            \Log::info("파트너 타입 수정 [ID: {$item->id}]", [
                'user_id' => auth()->id(),
                'changes' => $changes
            ]);
        }
    }

    /**
     * 유효성 검증 규칙 (마이그레이션 27개 필드 완전 지원)
     */
    protected function getValidationRules($item, Request $request): array
    {
        $rules = [
            // 기본 정보 필드
            'type_code' => [
                'required',
                'string',
                'max:20',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('partner_types', 'type_code')->ignore($item->id)
            ],
            'type_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:2000',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0|max:999',
            'color' => [
                'nullable',
                'string',
                'max:7',
                'regex:/^#[0-9A-Fa-f]{6}$/'
            ],
            'is_active' => 'nullable|boolean',

            // JSON 필드
            'specialties' => 'nullable', // 별도 처리
            'required_skills' => 'nullable', // 별도 처리

            // 최소 기준치 시스템 필드
            'min_baseline_sales' => 'nullable|numeric|min:0|max:999999999.99',
            'min_baseline_cases' => 'nullable|integer|min:0|max:999999',
            'min_baseline_revenue' => 'nullable|numeric|min:0|max:999999999.99',
            'min_baseline_clients' => 'nullable|integer|min:0|max:999999',
            'baseline_quality_score' => 'nullable|numeric|min:0|max:100',

            // 수수료 관련 필드 (조건부 검증)
            'default_commission_type' => 'required|in:percentage,fixed_amount',
            'commission_notes' => 'nullable|string|max:1000',

            // 비용 관련 필드
            'registration_fee' => 'nullable|numeric|min:0|max:999999.99',
            'monthly_maintenance_fee' => 'nullable|numeric|min:0|max:999999.99',
            'annual_maintenance_fee' => 'nullable|numeric|min:0|max:999999.99',
            'fee_waiver_available' => 'nullable|boolean',
            'fee_structure_notes' => 'nullable|string|max:1000',

            // 메모 필드
            'admin_notes' => 'nullable|string|max:2000'
        ];

        // 수수료 타입별 조건부 검증
        if ($request->get('default_commission_type') === 'percentage') {
            $rules['default_commission_rate'] = 'required|numeric|min:0|max:100';
            $rules['default_commission_amount'] = 'nullable';
        } else {
            $rules['default_commission_amount'] = 'required|numeric|min:0|max:999999.99';
            $rules['default_commission_rate'] = 'nullable';
        }

        return $rules;
    }

    /**
     * 사용자 정의 에러 메시지
     */
    protected function getValidationMessages(): array
    {
        return [
            'type_code.required' => '타입 코드는 필수입니다.',
            'type_code.unique' => '이미 사용 중인 타입 코드입니다.',
            'type_code.regex' => '타입 코드는 영문 소문자, 숫자, 언더스코어만 사용할 수 있습니다.',
            'type_name.required' => '타입 이름은 필수입니다.',
            'color.regex' => '색상은 #RRGGBB 형식으로 입력해주세요.',
            'default_commission_type.required' => '수수료 타입을 선택해주세요.',
            'default_commission_rate.required' => '비율 기반 수수료율을 입력해주세요.',
            'default_commission_amount.required' => '고정 수수료 금액을 입력해주세요.',
            'baseline_quality_score.max' => '품질 점수는 100점을 넘을 수 없습니다.'
        ];
    }
}