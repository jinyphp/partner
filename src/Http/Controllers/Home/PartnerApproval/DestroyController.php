<?php

namespace Jiny\Partner\Http\Controllers\Home\PartnerApproval;

use Jiny\Partner\Http\Controllers\PartnerController;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class DestroyController extends PartnerController
{
    /**
     * 파트너 신청서 삭제 (Home 영역)
     * AJAX 요청으로 처리되며, 성공시 목록 페이지로 이동
     */
    public function __invoke(Request $request, $id)
    {
        // 세션 인증 확인
        $user = $this->auth($request);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ], 401);
        }

        // 파트너 정보 확인 (tier 관계 포함 로드)
        $partner = PartnerUser::with('tier')->where('user_uuid', $user->uuid)->first();
        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => '파트너 등록이 필요합니다.'
            ], 403);
        }

        try {
            DB::beginTransaction();

            // 신청서 찾기
            $application = PartnerApplication::findOrFail($id);

            // 삭제 가능한 상태인지 확인 (항상 허용하도록 수정됨)
            if ($this->canDelete($application)) {
                // 관련 데이터 정리
                $this->cleanupRelatedData($application);

                // 신청서 삭제
                $application->delete();

                DB::commit();

                // AJAX 응답
                return response()->json([
                    'success' => true,
                    'message' => '파트너 신청서가 성공적으로 삭제되었습니다.',
                    'redirect' => route('admin.partner.approval.index'),
                    'application_id' => $id,
                    'application_name' => $application->personal_info['name'] ?? 'Unknown'
                ]);

            } else {
                return response()->json([
                    'success' => false,
                    'message' => '이 신청서는 삭제할 수 없는 상태입니다. (현재 상태: ' . $this->getStatusText($application->application_status) . ')',
                    'status' => $application->application_status
                ], 400);
            }

        } catch (Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => '삭제 중 오류가 발생했습니다: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 삭제 가능한 상태인지 확인 (임시로 모든 상태에서 삭제 허용)
     */
    private function canDelete($application)
    {
        // 임시로 모든 상태에서 삭제를 허용
        return true;

        // // 이미 승인된 신청서는 삭제 불가
        // if ($application->application_status === 'approved') {
        //     return false;
        // }

        // // 면접 진행 중인 신청서는 삭제 불가
        // if ($application->application_status === 'interview_scheduled' ||
        //     $application->application_status === 'interview_completed') {
        //     return false;
        // }

        // // 승인 대기 중이거나 거절된 신청서는 삭제 가능
        // return in_array($application->application_status, [
        //     'pending',
        //     'under_review',
        //     'reviewing',
        //     'rejected',
        //     'draft',
        //     'cancelled'
        // ]);
    }

    /**
     * 관련 데이터 정리
     */
    private function cleanupRelatedData($application)
    {
        // 면접 일정이 있다면 삭제
        if ($application->interview_scheduled_at) {
            // 면접 관련 알림이나 스케줄 정리
            // 추후 Interview 모델이 있다면 해당 데이터도 삭제
        }

        // 업로드된 문서 파일들 삭제
        if (isset($application->documents)) {
            foreach ($application->documents as $documentType => $filePath) {
                if ($filePath && Storage::exists($filePath)) {
                    Storage::delete($filePath);
                }
            }
        }

        // 추가 첨부 파일들 삭제
        $attachmentFields = ['resume_file', 'business_license', 'portfolio_file', 'certificate_file'];
        foreach ($attachmentFields as $field) {
            if ($application->$field && Storage::exists($application->$field)) {
                Storage::delete($application->$field);
            }
        }

        // 로그 기록
        if (function_exists('activity')) {
            activity()
                ->performedOn($application)
                ->withProperties([
                    'action' => 'deleted',
                    'application_id' => $application->id,
                    'application_status' => $application->application_status,
                    'personal_info' => $application->personal_info,
                    'deleted_by' => auth()->user()->id ?? null,
                    'deleted_at' => now(),
                    'deleted_from' => 'home_controller'
                ])
                ->log('Partner application deleted from home controller');
        } else {
            // 대체 로깅 - Laravel Log 사용
            \Log::info('Partner application deleted from home controller', [
                'application_id' => $application->id,
                'application_status' => $application->application_status,
                'personal_info' => $application->personal_info,
                'deleted_by' => auth()->user()->id ?? null,
                'deleted_at' => now(),
                'deleted_from' => 'home_controller'
            ]);
        }
    }

    /**
     * 상태 텍스트 반환
     */
    private function getStatusText($status)
    {
        $statusTexts = [
            'draft' => '작성중',
            'pending' => '승인 대기',
            'submitted' => '제출됨',
            'under_review' => '검토중',
            'reviewing' => '검토중',
            'interview_scheduled' => '면접 예정',
            'interview_completed' => '면접 완료',
            'approved' => '승인됨',
            'rejected' => '거절됨',
            'cancelled' => '취소됨'
        ];

        return $statusTexts[$status] ?? $status;
    }
}