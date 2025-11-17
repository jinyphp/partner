<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerUsers;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdateController extends Controller
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
     * 파트너 회원 수정
     */
    public function __invoke(Request $request, $id)
    {
        $item = $this->model::findOrFail($id);

        $validatedData = $request->validate($this->getValidationRules($item), $this->getValidationMessages());

        // JSON 필드 처리
        if ($request->has('profile_data')) {
            try {
                $validatedData['profile_data'] = json_decode($request->profile_data, true);
            } catch (\Exception $e) {
                $validatedData['profile_data'] = $item->profile_data; // 기존 값 유지
            }
        }

        // 수정자 정보 추가
        $validatedData['updated_by'] = Auth::id();

        // 등급 변경 시 할당일 업데이트
        if ($request->partner_tier_id != $item->partner_tier_id) {
            $validatedData['tier_assigned_at'] = now();
        }

        // 상태 변경 시 로그 (간단한 형태)
        if ($request->status != $item->status) {
            $statusHistory = $item->profile_data['status_history'] ?? [];
            $statusHistory[] = [
                'from' => $item->status,
                'to' => $request->status,
                'reason' => $request->status_reason,
                'changed_by' => Auth::id(),
                'changed_at' => now()->toISOString()
            ];

            $validatedData['profile_data'] = array_merge(
                $validatedData['profile_data'] ?? $item->profile_data ?? [],
                ['status_history' => $statusHistory]
            );
        }

        // 개별 수수료 타입에 따른 필드 정리
        if (isset($validatedData['individual_commission_type'])) {
            if ($validatedData['individual_commission_type'] === 'percentage') {
                $validatedData['individual_commission_amount'] = 0;
            } elseif ($validatedData['individual_commission_type'] === 'fixed_amount') {
                $validatedData['individual_commission_rate'] = 0;
            }
        }

        // 파트너 회원 수정
        $item->update($validatedData);

        return redirect()->route("admin.{$this->routePrefix}.show", $item->id)
            ->with('success', $this->title . ' 항목이 성공적으로 수정되었습니다.');
    }

    /**
     * 유효성 검사 규칙 (수정용)
     */
    protected function getValidationRules($item = null): array
    {
        $rules = [
            'user_id' => 'required|integer|min:1',
            'user_table' => 'required|string|max:20',
            'user_uuid' => 'nullable|string|max:36',
            'shard_number' => 'nullable|integer|min:0|max:999',
            'email' => 'required|email|max:191',
            'name' => 'required|string|max:100',
            'partner_type_id' => 'required|exists:partner_types,id',
            'partner_tier_id' => 'required|exists:partner_tiers,id',
            'status' => 'required|in:active,inactive,suspended,pending',
            'status_reason' => 'nullable|string',
            'total_completed_jobs' => 'nullable|integer|min:0',
            'average_rating' => 'nullable|numeric|between:0,5',
            'punctuality_rate' => 'nullable|numeric|between:0,100',
            'satisfaction_rate' => 'nullable|numeric|between:0,100',
            'partner_joined_at' => 'nullable|date',
            'tier_assigned_at' => 'nullable|date',
            'last_performance_review_at' => 'nullable|date',
            'profile_data' => 'nullable|string',
            'admin_notes' => 'nullable|string',

            // 개별 수수료 설정
            'individual_commission_type' => 'nullable|in:percentage,fixed_amount',
            'individual_commission_rate' => 'nullable|numeric|min:0|max:100|required_if:individual_commission_type,percentage',
            'individual_commission_amount' => 'nullable|numeric|min:0|required_if:individual_commission_type,fixed_amount',
            'commission_notes' => 'nullable|string|max:1000'
        ];

        // 수정시에는 다른 레코드와의 중복만 체크
        if ($item) {
            $rules['user_id'] = [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) use ($item) {
                    $userTable = request('user_table', 'users');
                    $existing = PartnerUser::where('user_id', $value)
                        ->where('user_table', $userTable)
                        ->where('id', '!=', $item->id)
                        ->first();

                    if ($existing) {
                        $fail('이미 등록된 사용자입니다.');
                    }
                }
            ];
        }

        return $rules;
    }

    /**
     * 커스텀 에러 메시지
     */
    protected function getValidationMessages(): array
    {
        return [
            'user_id.required' => '사용자 ID는 필수입니다.',
            'user_id.integer' => '사용자 ID는 숫자여야 합니다.',
            'user_table.required' => '사용자 테이블은 필수입니다.',
            'email.required' => '이메일은 필수입니다.',
            'email.email' => '올바른 이메일 형식이 아닙니다.',
            'name.required' => '이름은 필수입니다.',
            'partner_tier_id.required' => '파트너 등급은 필수입니다.',
            'partner_tier_id.exists' => '선택한 파트너 등급이 존재하지 않습니다.',
            'status.required' => '상태는 필수입니다.',
            'status.in' => '올바른 상태를 선택해주세요.',
            'average_rating.between' => '평점은 0과 5 사이의 값이어야 합니다.',
            'punctuality_rate.between' => '시간 준수율은 0과 100 사이의 값이어야 합니다.',
            'satisfaction_rate.between' => '만족도는 0과 100 사이의 값이어야 합니다.'
        ];
    }
}