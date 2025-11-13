<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerInterview;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerInterview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvaluationController extends Controller
{
    /**
     * 면접 평가 저장
     */
    public function store(Request $request, $id)
    {
        $interview = PartnerInterview::findOrFail($id);

        if ($interview->interview_status !== 'in_progress') {
            return back()->withErrors(['general' => '진행 중인 면접만 평가할 수 있습니다.']);
        }

        $validated = $request->validate([
            'technical_score' => 'required|numeric|min:0|max:5',
            'communication_score' => 'required|numeric|min:0|max:5',
            'experience_score' => 'required|numeric|min:0|max:5',
            'attitude_score' => 'required|numeric|min:0|max:5',
            'strengths' => 'nullable|string|max:1000',
            'weaknesses' => 'nullable|string|max:1000',
            'recommendations' => 'nullable|string|max:1000',
            'interview_feedback' => 'nullable|array'
        ], [
            'technical_score.required' => '기술역량 점수를 입력해주세요.',
            'communication_score.required' => '의사소통 점수를 입력해주세요.',
            'experience_score.required' => '경험평가 점수를 입력해주세요.',
            'attitude_score.required' => '태도평가 점수를 입력해주세요.',
            '*.min' => '점수는 0점 이상이어야 합니다.',
            '*.max' => '점수는 5점 이하여야 합니다.'
        ]);

        try {
            DB::beginTransaction();

            // 점수 업데이트
            $scores = [
                'technical_score' => $validated['technical_score'],
                'communication_score' => $validated['communication_score'],
                'experience_score' => $validated['experience_score'],
                'attitude_score' => $validated['attitude_score']
            ];

            // 종합 점수 계산
            $overallScore = round(array_sum($scores) / count($scores), 2);
            $scores['overall_score'] = $overallScore;

            // 기타 정보 업데이트
            $updateData = array_merge($scores, [
                'strengths' => $validated['strengths'],
                'weaknesses' => $validated['weaknesses'],
                'recommendations' => $validated['recommendations'],
                'interview_feedback' => $validated['interview_feedback'] ?? [],
                'updated_by' => auth()->id()
            ]);

            $interview->update($updateData);

            // 평가 로그 추가
            $interview->addLog('평가 저장', '면접 평가가 저장되었습니다.', [
                'scores' => $scores,
                'overall_score' => $overallScore
            ]);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => '평가가 성공적으로 저장되었습니다.',
                    'overall_score' => $overallScore
                ]);
            }

            return redirect()
                ->route('admin.partner.interview.show', $interview->id)
                ->with('success', '평가가 성공적으로 저장되었습니다.');

        } catch (\Exception $e) {
            DB::rollback();

            \Log::error('면접 평가 저장 실패', [
                'error' => $e->getMessage(),
                'interview_id' => $interview->id,
                'user_id' => auth()->id(),
                'request_data' => $validated
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '평가 저장 중 오류가 발생했습니다.'
                ], 500);
            }

            return back()
                ->withErrors(['general' => '평가 저장 중 오류가 발생했습니다.'])
                ->withInput();
        }
    }

    /**
     * 면접 평가 수정
     */
    public function update(Request $request, $id)
    {
        $interview = PartnerInterview::findOrFail($id);

        if (!in_array($interview->interview_status, ['in_progress', 'completed'])) {
            return back()->withErrors(['general' => '진행 중이거나 완료된 면접만 평가를 수정할 수 있습니다.']);
        }

        $validated = $request->validate([
            'technical_score' => 'required|numeric|min:0|max:5',
            'communication_score' => 'required|numeric|min:0|max:5',
            'experience_score' => 'required|numeric|min:0|max:5',
            'attitude_score' => 'required|numeric|min:0|max:5',
            'strengths' => 'nullable|string|max:1000',
            'weaknesses' => 'nullable|string|max:1000',
            'recommendations' => 'nullable|string|max:1000',
            'interview_feedback' => 'nullable|array'
        ]);

        try {
            DB::beginTransaction();

            // 기존 점수 백업
            $oldScores = [
                'technical_score' => $interview->technical_score,
                'communication_score' => $interview->communication_score,
                'experience_score' => $interview->experience_score,
                'attitude_score' => $interview->attitude_score,
                'overall_score' => $interview->overall_score
            ];

            // 새 점수 계산
            $scores = [
                'technical_score' => $validated['technical_score'],
                'communication_score' => $validated['communication_score'],
                'experience_score' => $validated['experience_score'],
                'attitude_score' => $validated['attitude_score']
            ];

            // 종합 점수 재계산
            $overallScore = round(array_sum($scores) / count($scores), 2);
            $scores['overall_score'] = $overallScore;

            // 업데이트 데이터 준비
            $updateData = array_merge($scores, [
                'strengths' => $validated['strengths'],
                'weaknesses' => $validated['weaknesses'],
                'recommendations' => $validated['recommendations'],
                'interview_feedback' => $validated['interview_feedback'] ?? [],
                'updated_by' => auth()->id()
            ]);

            $interview->update($updateData);

            // 평가 수정 로그 추가
            $interview->addLog('평가 수정', '면접 평가가 수정되었습니다.', [
                'old_scores' => $oldScores,
                'new_scores' => $scores,
                'score_change' => $overallScore - ($oldScores['overall_score'] ?? 0)
            ]);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => '평가가 성공적으로 수정되었습니다.',
                    'overall_score' => $overallScore
                ]);
            }

            return redirect()
                ->route('admin.partner.interview.show', $interview->id)
                ->with('success', '평가가 성공적으로 수정되었습니다.');

        } catch (\Exception $e) {
            DB::rollback();

            \Log::error('면접 평가 수정 실패', [
                'error' => $e->getMessage(),
                'interview_id' => $interview->id,
                'user_id' => auth()->id(),
                'request_data' => $validated
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '평가 수정 중 오류가 발생했습니다.'
                ], 500);
            }

            return back()
                ->withErrors(['general' => '평가 수정 중 오류가 발생했습니다.'])
                ->withInput();
        }
    }
}