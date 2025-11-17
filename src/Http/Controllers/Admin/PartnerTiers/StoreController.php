<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerTiers;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerTier;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StoreController extends Controller
{
    protected $model;
    protected $viewPath;
    protected $routePrefix;
    protected $title;

    public function __construct()
    {
        $this->model = PartnerTier::class;
        $this->viewPath = 'jiny-partner::admin.partner-tiers';
        $this->routePrefix = 'partner.tiers';
        $this->title = '파트너 등급';
    }

    /**
     * 파트너 등급 생성
     */
    public function __invoke(Request $request)
    {
        $validated = $request->validate($this->getValidationRules());

        // tier_code 자동 생성
        if (empty($validated['tier_code'])) {
            $validated['tier_code'] = $this->generateTierCode($validated['tier_name']);
        } else {
            // tier_code를 소문자로 변환 (새 구조에 맞춤)
            $validated['tier_code'] = strtolower($validated['tier_code']);
        }

        // sort_order 자동 설정
        if (!isset($validated['sort_order']) || $validated['sort_order'] == 0) {
            $validated['sort_order'] = $this->getNextSortOrder();
        }

        // JSON 필드 처리
        if ($request->has('requirements') && is_string($request->requirements)) {
            $validated['requirements'] = json_decode($request->requirements, true) ?? [];
        } else {
            $validated['requirements'] = [];
        }

        if ($request->has('benefits') && is_string($request->benefits)) {
            $validated['benefits'] = json_decode($request->benefits, true) ?? [];
        } else {
            $validated['benefits'] = [];
        }

        // Checkbox 필드 처리
        $validated['fee_waiver_available'] = $request->has('fee_waiver_available');
        $validated['is_active'] = $request->has('is_active');

        // 수수료 타입별 처리
        if ($validated['commission_type'] === 'percentage') {
            $validated['commission_amount'] = null;
        } else {
            $validated['commission_rate'] = null;
        }

        $item = $this->model::create($validated);

        return redirect()->route("admin.{$this->routePrefix}.show", $item->id)
            ->with('success', $this->title . ' 항목이 성공적으로 생성되었습니다.');
    }

    /**
     * 등급 코드 자동 생성
     */
    protected function generateTierCode($tierName)
    {
        $code = strtolower($tierName);
        $code = preg_replace('/[^a-z0-9가-힣\s-]/u', '', $code);
        $code = preg_replace('/\s+/', '_', $code);
        $code = trim($code, '_');

        // 소문자로 유지 (새 구조에 맞춤)
        $code = strtolower($code);

        // 중복 검사 및 번호 추가
        $originalCode = $code;
        $counter = 1;
        while ($this->model::where('tier_code', $code)->exists()) {
            $code = $originalCode . '_' . $counter;
            $counter++;
        }

        return $code;
    }

    /**
     * 다음 정렬 순서 계산
     */
    protected function getNextSortOrder()
    {
        $maxOrder = $this->model::max('sort_order');
        return ($maxOrder ?? 0) + 10;
    }

    protected function getValidationRules($item = null): array
    {
        return [
            'tier_code' => 'nullable|string|max:20|unique:partner_tiers,tier_code' . ($item ? ',' . $item->id : ''),
            'tier_name' => 'required|string|max:100|unique:partner_tiers,tier_name' . ($item ? ',' . $item->id : ''),
            'description' => 'nullable|string|max:1000',

            // 우선순위
            'priority_level' => 'required|integer|min:1|max:99',
            'sort_order' => 'nullable|integer|min:0',

            // 수수료 설정
            'commission_type' => 'required|in:percentage,fixed_amount',
            'commission_rate' => 'nullable|numeric|min:0|max:100|required_if:commission_type,percentage',
            'commission_amount' => 'nullable|numeric|min:0|required_if:commission_type,fixed_amount',

            // 비용 관리
            'registration_fee' => 'nullable|numeric|min:0',
            'monthly_fee' => 'nullable|numeric|min:0',
            'annual_fee' => 'nullable|numeric|min:0',
            'fee_waiver_available' => 'boolean',
            'fee_structure_notes' => 'nullable|string|max:500',

            // JSON 필드
            'requirements' => 'nullable|json',
            'benefits' => 'nullable|json',

            // 시스템 설정
            'is_active' => 'boolean',
        ];
    }
}