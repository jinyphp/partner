<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerApplication;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Illuminate\Http\Request;

class ScheduleInterviewController extends Controller
{
    /**
     * 면접 일정 설정
     */
    public function __invoke(Request $request, $id)
    {
        $application = PartnerApplication::findOrFail($id);

        $request->validate([
            'interview_date' => 'required|date|after:now',
            'interview_notes' => 'nullable|string|max:500'
        ]);

        $application->scheduleInterview($request->interview_date, $request->interview_notes);

        return response()->json([
            'success' => true,
            'message' => '면접 일정이 설정되었습니다.'
        ]);
    }
}