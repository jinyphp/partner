<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerCodes;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BulkDeleteController extends Controller
{
    /**
     * 대량 파트너 코드 삭제
     */
    public function delete(Request $request)
    {
        try {
            $request->validate([
                'partner_ids' => 'required|array',
                'partner_ids.*' => 'integer|exists:partner_users,id'
            ]);

            $partnerIds = $request->input('partner_ids');
            $successCount = 0;
            $failCount = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($partnerIds as $partnerId) {
                try {
                    $partnerUser = PartnerUser::find($partnerId);

                    if (!$partnerUser) {
                        $failCount++;
                        $errors[] = "파트너 ID {$partnerId}를 찾을 수 없습니다.";
                        continue;
                    }

                    if (!$partnerUser->partner_code) {
                        $failCount++;
                        $errors[] = "파트너 {$partnerUser->name}은 삭제할 코드가 없습니다.";
                        continue;
                    }

                    $partnerUser->update([
                        'partner_code' => null,
                        'updated_by' => auth()->id()
                    ]);

                    $successCount++;

                } catch (\Exception $e) {
                    $failCount++;
                    $errors[] = "파트너 ID {$partnerId}: " . $e->getMessage();
                }
            }

            DB::commit();

            Log::info('Bulk partner code deletion completed', [
                'success_count' => $successCount,
                'fail_count' => $failCount,
                'total_requested' => count($partnerIds),
                'admin_user' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "대량 코드 삭제 완료: 성공 {$successCount}개, 실패 {$failCount}개",
                'data' => [
                    'success_count' => $successCount,
                    'fail_count' => $failCount,
                    'total' => count($partnerIds),
                    'errors' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Bulk partner code deletion failed', [
                'error' => $e->getMessage(),
                'admin_user' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => '대량 코드 삭제 중 오류가 발생했습니다.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}