<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerApplication;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Jiny\Partner\Models\PartnerApplication;

/**
 * 파트너 신청서 삭제 처리 컨트롤러
 */
class DestroyController extends Controller
{
    /**
     * 신청서 삭제 처리
     */
    public function __invoke(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $application = PartnerApplication::findOrFail($id);

            // 로그용으로 삭제 전 정보 저장
            $applicationInfo = [
                'id' => $application->id,
                'user_id' => $application->user_id,
                'name' => $application->personal_info['name'] ?? 'Unknown',
                'email' => $application->personal_info['email'] ?? 'Unknown',
                'status' => $application->application_status,
                'created_at' => $application->created_at,
            ];

            // 업로드된 파일들 삭제
            if ($application->documents && is_array($application->documents)) {
                foreach ($application->documents as $document) {
                    if (isset($document['path']) && $document['path']) {
                        try {
                            Storage::disk('public')->delete($document['path']);
                        } catch (\Exception $e) {
                            // 파일 삭제 실패는 로그만 남기고 계속 진행
                            Log::warning('Failed to delete file during application deletion', [
                                'file_path' => $document['path'],
                                'application_id' => $application->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }

                // 신청서 폴더도 삭제 시도
                try {
                    $folderPath = "partner-applications/{$application->id}";
                    if (Storage::disk('public')->exists($folderPath)) {
                        Storage::disk('public')->deleteDirectory($folderPath);
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to delete application folder', [
                        'folder_path' => "partner-applications/{$application->id}",
                        'application_id' => $application->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // 신청서 삭제
            $application->delete();

            DB::commit();

            Log::info('Partner application deleted by admin', [
                'application_info' => $applicationInfo,
                'admin_user' => auth()->id(),
                'deleted_at' => now()
            ]);

            // AJAX 요청인지 확인
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => '파트너 신청서가 성공적으로 삭제되었습니다.',
                    'redirect' => route('admin.partner.applications.index')
                ]);
            }

            return redirect()
                ->route('admin.partner.applications.index')
                ->with('success', '파트너 신청서가 성공적으로 삭제되었습니다.');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollback();

            Log::warning('Attempt to delete non-existent partner application', [
                'application_id' => $id,
                'admin_user' => auth()->id()
            ]);

            // AJAX 요청인지 확인
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => '존재하지 않는 신청서입니다.'
                ], 404);
            }

            return redirect()
                ->route('admin.partner.applications.index')
                ->with('error', '존재하지 않는 신청서입니다.');

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Partner application deletion failed', [
                'application_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'admin_user' => auth()->id()
            ]);

            // AJAX 요청인지 확인
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => '신청서 삭제 중 오류가 발생했습니다: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', '신청서 삭제 중 오류가 발생했습니다: ' . $e->getMessage());
        }
    }
}