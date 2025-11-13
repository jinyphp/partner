<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerApproval;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class DestroyController extends Controller
{
    /**
     * 파트너 신청서 삭제
     * AJAX 요청으로 처리되며, 성공시 이전 페이지로 이동
     */
    public function __invoke(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            // 신청서 찾기
            $application = PartnerApplication::findOrFail($id);

            // 삭제 가능한 상태인지 확인
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
                    'redirect' => $this->getRedirectUrl($request),
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
                if ($filePath && file_exists(storage_path('app/' . $filePath))) {
                    unlink(storage_path('app/' . $filePath));
                }
            }
        }

        // 로그 기록 (activity 함수가 있을 때만)
        if (function_exists('activity')) {
            activity()
                ->performedOn($application)
                ->withProperties([
                    'action' => 'deleted',
                    'application_id' => $application->id,
                    'application_status' => $application->application_status,
                    'personal_info' => $application->personal_info,
                    'deleted_by' => auth()->user()->id ?? null,
                    'deleted_at' => now()
                ])
                ->log('Partner application deleted');
        } else {
            // 대체 로깅 - Laravel Log 사용
            \Log::info('Partner application deleted', [
                'application_id' => $application->id,
                'application_status' => $application->application_status,
                'personal_info' => $application->personal_info,
                'deleted_by' => auth()->user()->id ?? null,
                'deleted_at' => now()
            ]);
        }
    }

    /**
     * 리다이렉트 URL 결정
     */
    private function getRedirectUrl(Request $request)
    {
        // 이전 페이지가 있으면 이전 페이지로
        if ($request->header('referer')) {
            $referer = $request->header('referer');

            // 현재 삭제하려는 신청서의 상세 페이지에서 온 경우
            // 목록 페이지로 리다이렉트
            if (strpos($referer, '/admin/partner/approval/') !== false &&
                preg_match('/\/admin\/partner\/approval\/\d+$/', $referer)) {
                return route('admin.partner.approval.index');
            }

            return $referer;
        }

        // 기본적으로 승인 목록 페이지로
        return route('admin.partner.approval.index');
    }

    /**
     * 상태 텍스트 반환
     */
    private function getStatusText($status)
    {
        $statusTexts = [
            'draft' => '작성중',
            'pending' => '승인 대기',
            'under_review' => '검토중',
            'interview_scheduled' => '면접 예정',
            'interview_completed' => '면접 완료',
            'approved' => '승인됨',
            'rejected' => '거절됨',
            'cancelled' => '취소됨'
        ];

        return $statusTexts[$status] ?? $status;
    }
}