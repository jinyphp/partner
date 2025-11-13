<?php

namespace Jiny\Partner\Http\Controllers\Home\PartnerRegist;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

use Jiny\Partner\Http\Controllers\PartnerController;

class DestroyController extends PartnerController
{
    /**
     * 파트너 신청 완전 삭제 처리 (AJAX 전용)
     */
    public function __invoke(Request $request, $id)
    {
        // 가장 먼저 로그 출력
        error_log("DestroyController: Controller reached with ID: $id");
        \Log::emergency('DestroyController: CONTROLLER REACHED!', [
            'application_id' => $id,
            'timestamp' => now(),
            'method' => $request->method(),
            'is_ajax' => $request->ajax(),
            'expects_json' => $request->expectsJson(),
            'headers' => $request->headers->all(),
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
            'all_input' => $request->all()
        ]);

        \Log::info('DestroyController: Starting destroy process', [
            'application_id' => $id,
            'method' => $request->method(),
            'is_ajax' => $request->ajax(),
            'expects_json' => $request->expectsJson(),
            'headers' => $request->headers->all(),
            'ip' => $request->ip(),
            'url' => $request->fullUrl()
        ]);

        // AJAX 요청만 허용
        if (!$request->expectsJson() && !$request->ajax()) {
            \Log::warning('DestroyController: Non-AJAX request rejected', [
                'application_id' => $id
            ]);

            return redirect()->route('home.partner.regist')
                ->with('error', '잘못된 요청입니다.');
        }

        // Step1. JWT 인증여부 처리
        $user = $this->auth($request);
        if(!$user) {
            \Log::warning('DestroyController: Unauthorized access attempt', [
                'application_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'JWT 인증이 필요합니다. 로그인해 주세요.'
            ], 401);
        }

        \Log::info('DestroyController: User authenticated', [
            'user_id' => $user->id,
            'user_uuid' => $user->uuid
        ]);

        // Step2. 신청서 조회 (본인의 신청서만, UUID 기반)
        $application = PartnerApplication::where('id', $id)
            ->where('user_uuid', $user->uuid)
            ->first();

        if (!$application) {
            \Log::warning('DestroyController: Application not found', [
                'application_id' => $id,
                'user_uuid' => $user->uuid
            ]);

            return response()->json([
                'success' => false,
                'message' => '신청서를 찾을 수 없습니다.'
            ], 404);
        }

        \Log::info('DestroyController: Application found', [
            'application_id' => $application->id,
            'application_status' => $application->application_status,
            'user_uuid' => $application->user_uuid
        ]);

        // Step3. 삭제 가능한 상태인지 확인 (승인된 것은 삭제 불가)
        $deletableStatuses = ['draft', 'submitted', 'reviewing', 'rejected', 'reapplied'];

        if (!in_array($application->application_status, $deletableStatuses)) {
            \Log::warning('DestroyController: Invalid status for deletion', [
                'application_status' => $application->application_status,
                'deletable_statuses' => $deletableStatuses
            ]);

            return response()->json([
                'success' => false,
                'message' => '현재 상태에서는 신청을 취소할 수 없습니다.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            \Log::info('DestroyController: About to delete application permanently', [
                'application_id' => $application->id
            ]);

            // Step4. 업로드된 파일들 물리적 삭제
            $this->deleteApplicationFiles($application);

            // Step5. 신청서 완전 삭제 (forceDelete - 물리적 삭제)
            $application->forceDelete();

            DB::commit();

            \Log::info('DestroyController: Application destroyed successfully', [
                'application_id' => $id,
                'destroyed_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => '신청이 성공적으로 취소되었습니다.',
                'redirect' => route('home.partner.regist')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Partner application destroy failed: ' . $e->getMessage(), [
                'application_id' => $id,
                'exception' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '신청 취소 중 오류가 발생했습니다. 다시 시도해주세요.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * 신청서에 업로드된 파일들 완전 삭제
     */
    private function deleteApplicationFiles($application)
    {
        if (!$application->documents) {
            \Log::info('DestroyController: No documents found to delete', [
                'application_id' => $application->id
            ]);
            return;
        }

        $documents = $application->documents;
        $deletedFiles = [];

        // 이력서 파일 삭제
        if (isset($documents['resume']['stored_path'])) {
            $filePath = $documents['resume']['stored_path'];
            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
                $deletedFiles[] = $filePath;
                \Log::info('DestroyController: Resume file deleted', [
                    'file_path' => $filePath
                ]);
            }
        }

        // 포트폴리오 파일 삭제
        if (isset($documents['portfolio']['stored_path'])) {
            $filePath = $documents['portfolio']['stored_path'];
            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
                $deletedFiles[] = $filePath;
                \Log::info('DestroyController: Portfolio file deleted', [
                    'file_path' => $filePath
                ]);
            }
        }

        // 기타 서류 파일들 삭제
        if (isset($documents['other']) && is_array($documents['other'])) {
            foreach ($documents['other'] as $index => $file) {
                if (isset($file['stored_path'])) {
                    $filePath = $file['stored_path'];
                    if (Storage::disk('public')->exists($filePath)) {
                        Storage::disk('public')->delete($filePath);
                        $deletedFiles[] = $filePath;
                        \Log::info('DestroyController: Other document deleted', [
                            'file_path' => $filePath,
                            'index' => $index
                        ]);
                    }
                }
            }
        }

        \Log::info('DestroyController: All files deleted for application', [
            'application_id' => $application->id,
            'deleted_files_count' => count($deletedFiles),
            'deleted_files' => $deletedFiles
        ]);
    }
}