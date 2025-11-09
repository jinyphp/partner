<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerApplication;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Illuminate\Http\Request;

class InterviewFeedbackController extends Controller
{
    /**
     * 면접 피드백 저장
     */
    public function __invoke(Request $request, $id)
    {
        $application = PartnerApplication::findOrFail($id);

        $request->validate([
            'technical_score' => 'required|integer|min:1|max:10',
            'communication_score' => 'required|integer|min:1|max:10',
            'attitude_score' => 'required|integer|min:1|max:10',
            'strengths' => 'nullable|array',
            'weaknesses' => 'nullable|array',
            'recommendation' => 'required|in:approve,reject,reconsider',
            'notes' => 'nullable|string|max:1000'
        ]);

        $feedback = [
            'technical_score' => $request->technical_score,
            'communication_score' => $request->communication_score,
            'attitude_score' => $request->attitude_score,
            'overall_score' => round(($request->technical_score + $request->communication_score + $request->attitude_score) / 3, 1),
            'strengths' => $request->strengths ?? [],
            'weaknesses' => $request->weaknesses ?? [],
            'recommendation' => $request->recommendation,
            'notes' => $request->notes,
            'interviewer' => auth()->user()->name,
            'interview_completed_at' => now()
        ];

        $application->saveInterviewFeedback($feedback);

        return response()->json([
            'success' => true,
            'message' => '면접 피드백이 저장되었습니다.'
        ]);
    }
}