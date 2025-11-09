<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerUsers;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreController extends Controller
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
     * 파트너 회원 생성
     */
    public function __invoke(Request $request)
    {
        $validatedData = $request->validate($this->getValidationRules(), $this->getValidationMessages());

        // JSON 필드 처리
        if ($request->has('profile_data')) {
            try {
                $validatedData['profile_data'] = json_decode($request->profile_data, true);
            } catch (\Exception $e) {
                $validatedData['profile_data'] = null;
            }
        }

        // 생성자 정보 추가
        $validatedData['created_by'] = Auth::id();

        // 날짜 필드 처리
        if (!$request->has('partner_joined_at')) {
            $validatedData['partner_joined_at'] = now();
        }

        if (!$request->has('tier_assigned_at')) {
            $validatedData['tier_assigned_at'] = now();
        }

        // 파트너 회원 생성
        $item = $this->model::create($validatedData);

        return redirect()->route("admin.{$this->routePrefix}.show", $item->id)
            ->with('success', $this->title . ' 항목이 성공적으로 생성되었습니다.');
    }

    /**
     * 유효성 검사 규칙
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
            'admin_notes' => 'nullable|string'
        ];

        // 중복 사용자 체크 (신규 생성시에만)
        if (!$item) {
            $rules['user_id'] = [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) use ($rules) {
                    $userTable = request('user_table', 'users');

                    // 활성 레코드 체크 (soft delete 제외)
                    $existing = PartnerUser::where('user_id', $value)
                        ->where('user_table', $userTable)
                        ->first();

                    if ($existing) {
                        $fail('이미 등록된 사용자입니다. (활성 상태)');
                        return;
                    }

                    // soft delete된 레코드 체크
                    $trashedExisting = PartnerUser::onlyTrashed()
                        ->where('user_id', $value)
                        ->where('user_table', $userTable)
                        ->first();

                    if ($trashedExisting) {
                        $fail('이전에 등록되었다가 삭제된 사용자입니다. 관리자에게 문의하여 복구하거나 완전 삭제 처리를 요청하세요.');
                        return;
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