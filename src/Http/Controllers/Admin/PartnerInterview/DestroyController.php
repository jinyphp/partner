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

        // 완료된 면접은 삭제 제한
        if ($interview->interview_status === 'completed') {
            return back()->withErrors(['general' => '완료된 면접은 삭제할 수 없습니다.']);
        }

        // 진행 중인 면접은 삭제 제한
        if ($interview->interview_status === 'in_progress') {
            return back()->withErrors(['general' => '진행 중인 면접은 삭제할 수 없습니다.']);
        }

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

            return back()
                ->withErrors(['general' => '면접 삭제 중 오류가 발생했습니다.'])
                ->withInput();
        }
    }
}