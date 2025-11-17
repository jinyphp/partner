<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerInterviewEvaluations;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditController extends Controller
{
    /**
     * 면접 평가 수정 폼 표시
     */
    public function edit($id)
    {
        $evaluation = $this->getEvaluationWithDetails($id);

        if (!$evaluation) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '평가를 찾을 수 없습니다.'
                ], 404);
            }
            return redirect()->route('admin.partner.interview.evaluations.index')
                ->with('error', '평가를 찾을 수 없습니다.');
        }

        // JSON 필드 파싱
        $this->parseJsonFields($evaluation);

        return view('jiny-partner::admin.partner-interview-evaluations.edit', compact('evaluation'));
    }

    /**
     * 면접 평가 업데이트 (AJAX 전용)
     */
    public function update(Request $request, $id)
    {
        try {
            // 평가 존재 확인
            $existingEvaluation = DB::table('partner_interview_evaluations')->where('id', $id)->first();
            if (!$existingEvaluation) {
                return response()->json([
                    'success' => false,
                    'message' => '평가를 찾을 수 없습니다.'
                ], 404);
            }

            // 유효성 검사
            $validatedData = $this->validateEvaluationData($request);

            // 종합 점수 계산
            $overallRating = $this->calculateOverallRating($validatedData);

            // 업데이트 데이터 준비
            $updateData = $this->prepareUpdateData($validatedData, $overallRating);

            // 데이터베이스 업데이트
            DB::beginTransaction();

            DB::table('partner_interview_evaluations')
                ->where('id', $id)
                ->update($updateData);

            // 평가 로그 추가 (선택사항)
            $this->logEvaluationUpdate($id, $updateData);

            DB::commit();

            // 성공 응답
            return response()->json([
                'success' => true,
                'message' => '면접 평가가 성공적으로 수정되었습니다.',
                'redirect' => route('admin.partner.interview.evaluations.index'),
                'data' => [
                    'overall_rating' => $overallRating,
                    'updated_at' => now()->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (ValidationException $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => '입력한 정보를 확인해주세요.',
                'errors' => $e->validator->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollback();

            \Log::error('면접 평가 수정 실패', [
                'evaluation_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => '평가 수정 중 오류가 발생했습니다. 다시 시도해주세요.'
            ], 500);
        }
    }

    /**
     * 평가 상세 정보 조회 (관계 데이터 포함)
     */
    private function getEvaluationWithDetails($id)
    {
        return DB::table('partner_interview_evaluations as pie')
            ->leftJoin('partner_interviews as pi', 'pie.interview_id', '=', 'pi.id')
            ->leftJoin('partner_applications as pa', 'pi.application_id', '=', 'pa.id')
            ->leftJoin('users as interviewer', 'pie.interviewer_id', '=', 'interviewer.id')
            ->leftJoin('users as applicant', 'pa.user_id', '=', 'applicant.id')
            ->select([
                'pie.*',
                'pi.id as interview_id',
                'pi.interview_type as interview_type_original',
                'pi.interview_status',
                'pi.interview_round',
                'pi.scheduled_at',
                'pi.started_at',
                'pi.completed_at',
                'pi.duration_minutes as interview_duration',
                'pi.meeting_location',
                'pi.name as interview_name',
                'pi.email as interview_email',
                'pa.id as application_id',
                'pa.personal_info',
                'interviewer.name as interviewer_name',
                'interviewer.email as interviewer_email',
                'applicant.name as applicant_name',
                'applicant.email as applicant_email',
                // personal_info에서 포지션 정보 추출 (SQLite 호환)
                DB::raw('json_extract(pa.personal_info, "$.position_applied") as position_applied'),
                // 지원자 정보 추출 (fallback 용도)
                DB::raw('json_extract(pa.personal_info, "$.name") as applicant_display_name'),
                DB::raw('json_extract(pa.personal_info, "$.email") as applicant_display_email')
            ])
            ->where('pie.id', $id)
            ->first();
    }

    /**
     * JSON 필드 파싱
     */
    private function parseJsonFields($evaluation)
    {
        $jsonFields = ['strengths', 'weaknesses', 'concerns', 'action_items', 'interview_notes'];

        foreach ($jsonFields as $field) {
            if (isset($evaluation->$field)) {
                $evaluation->$field = json_decode($evaluation->$field, true) ?? [];
            }
        }

        // personal_info JSON 파싱
        if ($evaluation->personal_info) {
            $evaluation->personal_info = json_decode($evaluation->personal_info, true) ?? [];
        }
    }

    /**
     * 유효성 검사
     */
    private function validateEvaluationData(Request $request)
    {
        return $request->validate([
            'interview_date' => 'required|date',
            'duration_minutes' => 'nullable|integer|min:1|max:480',
            'interview_type' => 'required|in:video,phone,in_person,online_test',

            // 평가 점수 (0-100점)
            'technical_skills' => 'nullable|integer|min:0|max:100',
            'communication' => 'nullable|integer|min:0|max:100',
            'motivation' => 'nullable|integer|min:0|max:100',
            'experience_relevance' => 'nullable|integer|min:0|max:100',
            'cultural_fit' => 'nullable|integer|min:0|max:100',
            'problem_solving' => 'nullable|integer|min:0|max:100',
            'leadership_potential' => 'nullable|integer|min:0|max:100',

            'recommendation' => 'required|in:strongly_approve,approve,conditional,reject,strongly_reject',
            'detailed_feedback' => 'nullable|string|max:5000',

            // 배열 필드
            'strengths' => 'nullable|array|max:10',
            'strengths.*' => 'nullable|string|max:200',
            'weaknesses' => 'nullable|array|max:10',
            'weaknesses.*' => 'nullable|string|max:200',
            'concerns' => 'nullable|array|max:10',
            'concerns.*' => 'nullable|string|max:200',
            'action_items' => 'nullable|array|max:10',
            'action_items.*' => 'nullable|string|max:200',
        ], [
            'interview_date.required' => '면접 일시를 입력해주세요.',
            'interview_type.required' => '면접 방식을 선택해주세요.',
            'recommendation.required' => '최종 추천 등급을 선택해주세요.',
            'detailed_feedback.max' => '상세 피드백은 5000자 이하로 입력해주세요.',
            '*.min' => '점수는 0점 이상이어야 합니다.',
            '*.max' => '점수나 텍스트 길이를 확인해주세요.',
            'strengths.*.max' => '강점은 각각 200자 이하로 입력해주세요.',
            'weaknesses.*.max' => '약점은 각각 200자 이하로 입력해주세요.',
            'concerns.*.max' => '우려사항은 각각 200자 이하로 입력해주세요.',
            'action_items.*.max' => '액션 아이템은 각각 200자 이하로 입력해주세요.',
        ]);
    }

    /**
     * 종합 점수 계산 (가중 평균)
     */
    private function calculateOverallRating($data)
    {
        $weights = [
            'technical_skills' => 0.25,      // 기술역량 25%
            'communication' => 0.20,         // 의사소통 20%
            'motivation' => 0.15,            // 동기 및 열정 15%
            'experience_relevance' => 0.15,  // 경력 연관성 15%
            'cultural_fit' => 0.10,          // 조직 적합성 10%
            'problem_solving' => 0.10,       // 문제해결 10%
            'leadership_potential' => 0.05,  // 리더십 잠재력 5%
        ];

        $totalScore = 0;
        $totalWeight = 0;

        foreach ($weights as $field => $weight) {
            $score = intval($data[$field] ?? 0);
            if ($score > 0) {
                $totalScore += $score * $weight;
                $totalWeight += $weight;
            }
        }

        return $totalWeight > 0 ? round($totalScore / $totalWeight, 1) : 0;
    }

    /**
     * 업데이트 데이터 준비
     */
    private function prepareUpdateData($validatedData, $overallRating)
    {
        // 배열 필드를 JSON으로 인코딩
        $arrayFields = ['strengths', 'weaknesses', 'concerns', 'action_items'];
        foreach ($arrayFields as $field) {
            if (isset($validatedData[$field])) {
                $validatedData[$field] = json_encode(array_filter($validatedData[$field] ?? []), JSON_UNESCAPED_UNICODE);
            }
        }

        return [
            'interview_date' => $validatedData['interview_date'],
            'duration_minutes' => $validatedData['duration_minutes'],
            'interview_type' => $validatedData['interview_type'],

            // 평가 점수
            'technical_skills' => $validatedData['technical_skills'] ?? 0,
            'communication' => $validatedData['communication'] ?? 0,
            'motivation' => $validatedData['motivation'] ?? 0,
            'experience_relevance' => $validatedData['experience_relevance'] ?? 0,
            'cultural_fit' => $validatedData['cultural_fit'] ?? 0,
            'problem_solving' => $validatedData['problem_solving'] ?? 0,
            'leadership_potential' => $validatedData['leadership_potential'] ?? 0,

            'overall_rating' => $overallRating,
            'recommendation' => $validatedData['recommendation'],
            'detailed_feedback' => $validatedData['detailed_feedback'],

            // JSON 필드
            'strengths' => $validatedData['strengths'] ?? '[]',
            'weaknesses' => $validatedData['weaknesses'] ?? '[]',
            'concerns' => $validatedData['concerns'] ?? '[]',
            'action_items' => $validatedData['action_items'] ?? '[]',

            'updated_at' => now(),
            'updated_by' => auth()->id(),
        ];
    }

    /**
     * 평가 수정 로그 기록
     */
    private function logEvaluationUpdate($evaluationId, $updateData)
    {
        // 로그 테이블이 있다면 기록
        // 예: 평가 변경 히스토리, 감사 로그 등
        \Log::info('면접 평가 수정 완료', [
            'evaluation_id' => $evaluationId,
            'overall_rating' => $updateData['overall_rating'],
            'recommendation' => $updateData['recommendation'],
            'updated_by' => auth()->id(),
            'updated_at' => $updateData['updated_at']
        ]);
    }
}