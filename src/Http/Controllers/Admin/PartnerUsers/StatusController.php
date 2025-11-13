<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerUsers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Jiny\Partner\Models\PartnerUser;

/**
 * 파트너 사용자 상태 변경 AJAX API 컨트롤러
 *
 * AJAX 요청을 통한 파트너 사용자 상태 변경 처리
 */
class StatusController extends Controller
{
    /**
     * 파트너 사용자 상태 변경 (AJAX)
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            Log::info('AJAX status change request received', [
                'partner_user_id' => $id,
                'request_method' => $request->method(),
                'is_authenticated' => auth()->check(),
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            // 인증 확인
            if (!auth()->check()) {
                Log::warning('Unauthenticated status change request', ['partner_user_id' => $id]);
                return response()->json([
                    'success' => false,
                    'message' => '로그인이 필요합니다.'
                ], 401);
            }

            // 입력 유효성 검사 (간단한 워크플로우: 대기->승인->정지)
            $validationRules = [
                'status' => 'required|in:pending,active,suspended',
                'status_reason' => 'nullable|string|max:1000',
                'approval_type' => 'nullable|in:approved,pending,suspended',
                'approval_notes' => 'nullable|string|max:500'
            ];

            // 활성 상태로 변경시 승인 타입 필수
            if ($request->status === 'active') {
                $validationRules['approval_type'] = 'required|in:approved,pending,suspended';
            }

            $request->validate($validationRules);

            // 파트너 사용자 조회
            $partnerUser = PartnerUser::findOrFail($id);

            Log::info('Partner user status change request', [
                'partner_user_id' => $id,
                'current_status' => $partnerUser->status,
                'new_status' => $request->status,
                'reason' => $request->status_reason,
                'approval_type' => $request->approval_type,
                'approval_notes' => $request->approval_notes,
                'admin_user' => auth()->id()
            ]);

            // 현재 상태와 동일한지 확인
            if ($partnerUser->status === $request->status) {
                return response()->json([
                    'success' => false,
                    'message' => '이미 같은 상태입니다.'
                ], 400);
            }

            DB::beginTransaction();

            // 이전 상태 저장
            $previousStatus = $partnerUser->status;

            // 업데이트할 데이터 준비
            $updateData = [
                'status' => $request->status,
                'status_reason' => $request->status_reason,
                'updated_by' => auth()->id()
            ];

            // 활성 상태로 변경시 승인 관련 데이터 추가
            if ($request->status === 'active') {
                $updateData['approval_type'] = $request->approval_type;
                $updateData['approved_at'] = now();
                $updateData['approved_by'] = auth()->id();

                if ($request->approval_notes) {
                    $updateData['approval_notes'] = $request->approval_notes;
                }
            }

            // 상태 변경
            $partnerUser->update($updateData);

            // 상태 변경 로그 기록 (필요시 추가)
            $this->logStatusChange($partnerUser, $previousStatus, $request->status, $request->status_reason);

            DB::commit();

            Log::info('Partner user status changed successfully', [
                'partner_user_id' => $id,
                'previous_status' => $previousStatus,
                'new_status' => $request->status,
                'admin_user' => auth()->id()
            ]);

            // 성공 메시지 구성 (간단한 워크플로우)
            $statusMessages = [
                'active' => '파트너가 성공적으로 승인되었습니다.',
                'suspended' => '파트너가 정지되었습니다.',
                'pending' => '파트너가 대기 상태로 변경되었습니다.'
            ];
            $message = $statusMessages[$request->status] ?? '상태가 성공적으로 변경되었습니다.';

            if ($request->status === 'active' && $request->approval_type) {
                $approvalTypeLabels = [
                    'approved' => '승인',
                    'pending' => '대기',
                    'suspended' => '정지'
                ];
                $message .= ' (' . ($approvalTypeLabels[$request->approval_type] ?? $request->approval_type) . ')';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'id' => $partnerUser->id,
                    'status' => $partnerUser->status,
                    'status_reason' => $partnerUser->status_reason,
                    'approval_type' => $partnerUser->approval_type ?? null,
                    'approval_notes' => $partnerUser->approval_notes ?? null,
                    'approved_at' => $partnerUser->approved_at ? $partnerUser->approved_at->format('Y-m-d H:i:s') : null,
                    'approved_by' => $partnerUser->approved_by ?? null,
                    'status_badge' => $this->getStatusBadgeHtml($partnerUser->status),
                    'updated_at' => $partnerUser->updated_at->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '입력 데이터가 올바르지 않습니다.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => '파트너 사용자를 찾을 수 없습니다.'
            ], 404);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Partner user status change failed', [
                'partner_user_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '상태 변경 중 오류가 발생했습니다.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 상태 변경 로그 기록
     *
     * @param PartnerUser $partnerUser
     * @param string $previousStatus
     * @param string $newStatus
     * @param string|null $reason
     * @return void
     */
    private function logStatusChange(PartnerUser $partnerUser, string $previousStatus, string $newStatus, ?string $reason): void
    {
        // 상태 변경 히스토리 테이블이 있다면 기록
        // 현재는 일반 로그만 기록하지만 필요시 별도 테이블 생성 가능

        Log::channel('partner')->info('Partner user status changed', [
            'partner_user_id' => $partnerUser->id,
            'partner_name' => $partnerUser->name,
            'partner_email' => $partnerUser->email,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'reason' => $reason,
            'changed_by' => auth()->id(),
            'changed_at' => now()->toISOString()
        ]);
    }

    /**
     * 상태에 따른 배지 HTML 반환
     *
     * @param string $status
     * @return string
     */
    private function getStatusBadgeHtml(string $status): string
    {
        switch ($status) {
            case 'active':
                return '<span class="badge bg-success fs-6">승인</span>';
            case 'pending':
                return '<span class="badge bg-warning fs-6">대기</span>';
            case 'suspended':
                return '<span class="badge bg-danger fs-6">정지</span>';
            default:
                return '<span class="badge bg-secondary fs-6">알 수 없음</span>';
        }
    }

    /**
     * 파트너 사용자 상태 조회 (AJAX)
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $partnerUser = PartnerUser::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $partnerUser->id,
                    'status' => $partnerUser->status,
                    'status_reason' => $partnerUser->status_reason,
                    'status_badge' => $this->getStatusBadgeHtml($partnerUser->status),
                    'updated_at' => $partnerUser->updated_at->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => '파트너 사용자를 찾을 수 없습니다.'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Partner user status fetch failed', [
                'partner_user_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '상태 조회 중 오류가 발생했습니다.'
            ], 500);
        }
    }
}