<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerInterview;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerInterview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DestroyController extends Controller
{
    /**
     * 면접 기록 삭제
     */
    public function __invoke(Request $request, $id)
    {
        $interview = PartnerInterview::findOrFail($id);

        // 모든 상태의 면접 삭제 허용 (상태 검증 없음)

        try {
            DB::beginTransaction();

            // 면접 삭제 로그 추가
            $interview->addLog('면접 삭제', '면접 일정이 삭제되었습니다.', [
                'deleted_by' => auth()->user()->name ?? '알 수 없음',
                'reason' => $request->input('reason', '관리자에 의한 삭제')
            ]);

            // 신청서 상태 업데이트 (면접 상태에서 되돌리기)
            if ($interview->application) {
                $interview->application->update([
                    'application_status' => 'submitted',
                    'interview_date' => null,
                    'interview_notes' => null
                ]);
            }

            // 소프트 삭제 실행
            $interview->delete();

            DB::commit();

            // AJAX 요청인 경우 JSON 응답
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => '면접 일정이 성공적으로 삭제되었습니다.',
                    'redirect' => route('admin.partner.interview.index')
                ]);
            }

            return redirect()
                ->route('admin.partner.interview.index')
                ->with('success', '면접 일정이 성공적으로 삭제되었습니다.');

        } catch (\Exception $e) {
            DB::rollback();

            \Log::error('면접 삭제 실패', [
                'error' => $e->getMessage(),
                'interview_id' => $interview->id,
                'user_id' => auth()->id()
            ]);

            // AJAX 요청인 경우 JSON 응답
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => '면접 삭제 중 오류가 발생했습니다.'
                ], 500);
            }

            return back()
                ->withErrors(['general' => '면접 삭제 중 오류가 발생했습니다.'])
                ->withInput();
        }
    }
}