<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerApproval;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Models\PartnerTier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * 파트너 신청 승인 처리 컨트롤러
 *
 * 처리 과정:
 * Step 1: 신청서 조회 및 기본 검증
 * Step 2: 승인 가능 상태 검사
 * Step 3: 요청 데이터 검증
 * Step 4: partner_applications 상태 변경
 * Step 5: partner_users에 파트너 등록
 * Step 6: 성공 응답 반환
 */
class ApproveController extends Controller
{
    /**
     * 파트너 신청 승인 처리 메인 메서드
     */
    public function __invoke(Request $request, $id)
    {
        // ===============================================
        // Step 1: 신청서 조회 및 기본 검증
        // ===============================================
        $application = $this->getApplication($id);

        // ===============================================
        // Step 2: 승인 가능 상태 검사
        // ===============================================
        $statusCheck = $this->checkApprovalStatus($application);
        if (!$statusCheck['success']) {
            return $this->response($request, false, $statusCheck['message']);
        }

        // ===============================================
        // Step 3: 요청 데이터 검증
        // ===============================================
        $validatedData = $this->validateRequestData($request);

        // ===============================================
        // 트랜잭션 시작 (Step 4-5를 원자적으로 처리)
        // ===============================================
        try {
            DB::beginTransaction();

            // ===============================================
            // Step 4: partner_applications 상태 변경
            // ===============================================
            $this->updateApplicationStatus($application, $validatedData);

            // ===============================================
            // Step 5: partner_users에 파트너 등록
            // ===============================================
            $partnerUser = $this->createPartnerUser($application);

            DB::commit();

            // ===============================================
            // Step 6: 성공 응답 반환
            // ===============================================
            return $this->successResponse($request, $application, $partnerUser);

        } catch (\Exception $e) {
            DB::rollBack();

            $errorMessage = 'Step 4-5 처리 중 오류가 발생했습니다: ' . $e->getMessage();
            \Log::error('ApproveController 트랜잭션 오류', [
                'application_id' => $application->id,
                'error' => $e->getMessage()
            ]);

            return $this->response($request, false, $errorMessage);
        }
    }

    /**
     * Step 1: 신청서 조회
     */
    private function getApplication($id)
    {
        return PartnerApplication::findOrFail($id);
    }

    /**
     * Step 2: 승인 가능 상태 검사
     */
    private function checkApprovalStatus(PartnerApplication $application)
    {
        // 이미 승인된 상태인지 확인
        if ($application->application_status === 'approved') {
            return [
                'success' => false,
                'message' => '이미 승인된 신청서입니다.'
            ];
        }

        // 승인 가능한 상태 목록
        $approvalableStatuses = ['submitted', 'reviewing', 'interview', 'reapplied'];

        if (!in_array($application->application_status, $approvalableStatuses)) {
            return [
                'success' => false,
                'message' => '현재 상태에서는 승인할 수 없습니다. (현재 상태: ' . $application->application_status . ')'
            ];
        }

        return ['success' => true];
    }

    /**
     * Step 3: 요청 데이터 검증
     */
    private function validateRequestData(Request $request)
    {
        return $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
            'notify_user' => 'nullable|in:0,1',
            'welcome_message' => 'nullable|string|max:500'
        ]);
    }

    /**
     * Step 4: partner_applications 상태 변경
     */
    private function updateApplicationStatus(PartnerApplication $application, array $validatedData)
    {
        $application->update([
            'application_status' => 'approved',
            'approval_date' => now(),
            'approved_by' => Auth::id(),
            'admin_notes' => $validatedData['admin_notes'] ?? null
        ]);
    }

    /**
     * Step 5: partner_users에 파트너 등록
     */
    private function createPartnerUser(PartnerApplication $application)
    {
        // 5-1: 기본 파트너 등급 조회
        $defaultTier = $this->getDefaultPartnerTier();

        // 5-2: 샤드 테이블에서 사용자 정보 조회
        $userInfo = $this->getUserInfo($application);

        // 5-3: 중복 파트너 체크
        $existingPartner = $this->checkExistingPartner($application);
        if ($existingPartner) {
            return $existingPartner; // 이미 존재하는 파트너 반환
        }

        // 5-4: 프로필 데이터 구성
        $profileData = $this->buildProfileData($application);

        // 5-5: 새 파트너 생성
        return $this->createNewPartner($application, $defaultTier, $userInfo, $profileData);
    }

    /**
     * 5-1: 기본 파트너 등급 조회
     */
    private function getDefaultPartnerTier()
    {
        $defaultTier = PartnerTier::where('is_active', true)
            ->orderBy('priority_level', 'desc')
            ->first();

        if (!$defaultTier) {
            throw new \Exception('기본 파트너 등급이 설정되지 않았습니다.');
        }

        return $defaultTier;
    }

    /**
     * 5-2: 샤드 테이블에서 사용자 정보 조회
     */
    private function getUserInfo(PartnerApplication $application)
    {
        $userTable = $application->shard_number ?
            'user_' . str_pad($application->shard_number, 3, '0', STR_PAD_LEFT) : 'users';

        try {
            return DB::table($userTable)->where('id', $application->user_id)->first();
        } catch (\Exception $e) {
            // 샤드 테이블 조회 실패 시 null 반환 (지원서 정보 사용)
            \Log::info('샤드 테이블 조회 실패, 지원서 정보 사용', [
                'user_table' => $userTable,
                'user_id' => $application->user_id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * 5-3: 중복 파트너 체크 (트랜잭션 내에서 락을 걸어 Race condition 방지)
     */
    private function checkExistingPartner(PartnerApplication $application)
    {
        $userTable = $application->shard_number ?
            'user_' . str_pad($application->shard_number, 3, '0', STR_PAD_LEFT) : 'users';

        return PartnerUser::where('user_id', $application->user_id)
            ->where('user_table', $userTable)
            ->lockForUpdate()  // 락을 걸어서 동시 접근 방지
            ->first();
    }

    /**
     * 5-4: 프로필 데이터 구성
     */
    private function buildProfileData(PartnerApplication $application)
    {
        return [
            'application_id' => $application->id,
            'personal_info' => $application->personal_info,
            'experience_info' => $application->experience_info,
            'skills_info' => $application->skills_info,
            'preferred_work_areas' => $application->preferred_work_areas,
            'availability_schedule' => $application->availability_schedule,
            'expected_hourly_rate' => $application->expected_hourly_rate,
            'approved_at' => now()->toISOString(),
            'status_history' => [
                [
                    'status' => 'active',
                    'reason' => '파트너 지원서 승인',
                    'changed_by' => $application->approved_by,
                    'changed_at' => now()->toISOString()
                ]
            ]
        ];
    }

    /**
     * 5-5: 새 파트너 생성 (UNIQUE constraint violation 예외 처리 포함)
     */
    private function createNewPartner(PartnerApplication $application, $defaultTier, $userInfo, $profileData)
    {
        try {
            return PartnerUser::create([
                'user_id' => $application->user_id,
                'user_table' => $application->shard_number ?
                    'user_' . str_pad($application->shard_number, 3, '0', STR_PAD_LEFT) : 'users',
                'user_uuid' => $application->user_uuid ?? ($userInfo ? $userInfo->uuid ?? null : null),
                'shard_number' => $application->shard_number ?? 0,
                'email' => $application->personal_info['email'] ?? ($userInfo ? $userInfo->email ?? null : null),
                'name' => $application->personal_info['name'] ?? ($userInfo ? $userInfo->name ?? null : null),
                'partner_tier_id' => $defaultTier->id,
                'status' => 'active',
                'total_completed_jobs' => 0,
                'average_rating' => 0,
                'punctuality_rate' => 0,
                'satisfaction_rate' => 0,
                'partner_joined_at' => now(),
                'tier_assigned_at' => now(),
                'profile_data' => $profileData,
                'admin_notes' => '파트너 지원서 승인을 통해 등록됨',
                'created_by' => $application->approved_by,
                // 계층 구조 관련 필드 (기본값)
                'parent_id' => null,
                'level' => 0,
                'tree_path' => '',
                'children_count' => 0,
                'total_children_count' => 0,
                'max_children' => 10,
                'individual_commission_rate' => 0,
                'discount_rate' => 0,
                'monthly_sales' => 0,
                'total_sales' => 0,
                'team_sales' => 0,
                'earned_commissions' => 0,
                'can_recruit' => true,
                'last_activity_at' => now(),
                'network_settings' => [
                    'recruitment_enabled' => true,
                    'commission_sharing' => true,
                    'auto_tier_upgrade' => true
                ]
            ]);

        } catch (\Illuminate\Database\QueryException $e) {
            // UNIQUE constraint 위반 시 기존 파트너 조회하여 반환
            if (str_contains($e->getMessage(), 'UNIQUE constraint failed')) {
                \Log::warning('Step 5에서 UNIQUE constraint 위반 감지, 기존 파트너 조회', [
                    'application_id' => $application->id,
                    'user_id' => $application->user_id,
                    'user_table' => $application->shard_number ?
                        'user_' . str_pad($application->shard_number, 3, '0', STR_PAD_LEFT) : 'users',
                    'error_message' => $e->getMessage()
                ]);

                // 다시 한번 기존 파트너 조회
                $existingPartner = PartnerUser::where('user_id', $application->user_id)
                    ->where('user_table', $application->shard_number ?
                        'user_' . str_pad($application->shard_number, 3, '0', STR_PAD_LEFT) : 'users')
                    ->first();

                if ($existingPartner) {
                    return $existingPartner;
                }

                // 기존 파트너를 찾을 수 없으면 원래 예외를 다시 throw
                throw $e;
            }

            // 다른 데이터베이스 오류는 그대로 throw
            throw $e;
        }
    }

    /**
     * Step 6: 성공 응답 반환
     */
    private function successResponse(Request $request, PartnerApplication $application, PartnerUser $partnerUser = null)
    {
        $message = '파트너 신청이 승인되었습니다.';
        if ($partnerUser) {
            $message .= ' 파트너 회원으로 등록되었습니다. (파트너 ID: ' . $partnerUser->id . ')';
        }

        $responseData = [
            'application_id' => $application->id,
            'status' => $application->application_status,
            'approval_date' => $application->approval_date,
            'approved_by' => $application->approved_by,
            'partner_user_id' => $partnerUser ? $partnerUser->id : null,
            'partner_status' => $partnerUser ? $partnerUser->status : null
        ];

        return $this->response($request, true, $message, $responseData);
    }

    /**
     * 응답 처리 (AJAX/일반 요청 구분)
     */
    private function response($request, $success, $message, $data = null)
    {
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            $response = ['success' => $success, 'message' => $message];
            if ($data) {
                $response['data'] = $data;
            }
            return response()->json($response, $success ? 200 : 400);
        }

        if ($success) {
            return redirect()->route('admin.partner.approval.show', $data['application_id'] ?? 1)
                ->with('success', $message);
        } else {
            return back()->with('error', $message);
        }
    }
}