<?php

namespace Jiny\Partner\Http\Controllers\Admin;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PartnerInterviewEvaluationsController extends BaseController
{
    /**
     * 면접 평가 목록
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = DB::table('partner_interview_evaluations as pie')
            ->leftJoin('partner_interviews as pi', 'pie.interview_id', '=', 'pi.id')
            ->leftJoin('partner_applications as pa', 'pi.application_id', '=', 'pa.id')
            ->leftJoin('users as interviewer', 'pie.interviewer_id', '=', 'interviewer.id')
            ->leftJoin('users as applicant', 'pa.user_id', '=', 'applicant.id')
            ->select([
                'pie.*',
                'pi.id as interview_id',
                'pi.interview_type',
                'pi.interview_status',
                'pi.scheduled_at',
                'pi.completed_at',
                'pi.name as interview_name',
                'pi.email as interview_email',
                'pa.id as application_id',
                'pa.personal_info',
                DB::raw('"파트너 신청" as position_applied'),
                'interviewer.name as interviewer_name',
                'interviewer.email as interviewer_email',
                'applicant.name as applicant_name',
                'applicant.email as applicant_email',
                // 면접 정보가 우선, 없으면 application 정보 사용
                DB::raw('COALESCE(pi.name, applicant.name) as applicant_display_name'),
                DB::raw('COALESCE(pi.email, applicant.email) as applicant_display_email')
            ]);

        // 필터링
        if ($request->filled('recommendation')) {
            $query->where('pie.recommendation', $request->recommendation);
        }

        if ($request->filled('interview_type')) {
            $query->where('pi.interview_type', $request->interview_type);
        }

        if ($request->filled('interview_status')) {
            $query->where('pi.interview_status', $request->interview_status);
        }

        if ($request->filled('overall_rating_min')) {
            $query->where('pie.overall_rating', '>=', $request->overall_rating_min);
        }

        if ($request->filled('overall_rating_max')) {
            $query->where('pie.overall_rating', '<=', $request->overall_rating_max);
        }

        if ($request->filled('date_from')) {
            $query->where('pi.scheduled_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('pi.scheduled_at', '<=', $request->date_to . ' 23:59:59');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('pi.name', 'LIKE', "%{$search}%")
                  ->orWhere('pi.email', 'LIKE', "%{$search}%")
                  ->orWhere('interviewer.name', 'LIKE', "%{$search}%");
            });
        }

        // 정렬
        $sortBy = $request->get('sort_by', 'interview_date');
        $sortOrder = $request->get('sort_order', 'desc');

        // 정렬 컬럼별로 적절한 테이블 선택
        switch ($sortBy) {
            case 'interview_date':
            case 'scheduled_at':
                $query->orderBy('pi.scheduled_at', $sortOrder);
                break;
            case 'interview_status':
            case 'interview_type':
                $query->orderBy("pi.{$sortBy}", $sortOrder);
                break;
            case 'interviewer_name':
                $query->orderBy('interviewer.name', $sortOrder);
                break;
            case 'overall_rating':
            case 'recommendation':
            case 'created_at':
            default:
                $query->orderBy("pie.{$sortBy}", $sortOrder);
                break;
        }

        // 페이지네이션
        $evaluations = $query->paginate(20);

        // 통계 데이터
        $stats = $this->getEvaluationStats();

        return view('jiny-partner::admin.partner-interview-evaluations.index', compact('evaluations', 'stats'));
    }

    /**
     * 면접 평가 상세 보기
     *
     * @param int $id 평가 ID 또는 면접 ID (interview_id 파라미터가 있는 경우)
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show($id)
    {
        // interview_id가 전달된 경우 해당 면접의 평가를 찾음
        $evaluation = DB::table('partner_interview_evaluations as pie')
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
                'pi.meeting_url',
                'pi.preparation_notes',
                'pi.interviewer_notes',
                'pi.name as interview_name',
                'pi.email as interview_email',
                'pa.id as application_id',
                'pa.personal_info',
                'pa.application_status',
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
            ->where(function($query) use ($id) {
                // interview_id 또는 evaluation_id로 검색
                $query->where('pie.interview_id', $id)
                      ->orWhere('pie.id', $id);
            })
            ->first();

        if (!$evaluation) {
            return redirect()->back()->with('error', '평가를 찾을 수 없습니다.');
        }

        // JSON 필드 파싱
        $evaluation->strengths = json_decode($evaluation->strengths, true) ?? [];
        $evaluation->weaknesses = json_decode($evaluation->weaknesses, true) ?? [];
        $evaluation->concerns = json_decode($evaluation->concerns, true) ?? [];
        $evaluation->action_items = json_decode($evaluation->action_items, true) ?? [];
        $evaluation->interview_notes = json_decode($evaluation->interview_notes, true) ?? [];
        $evaluation->attachments = json_decode($evaluation->attachments, true) ?? [];

        // personal_info JSON 파싱
        if ($evaluation->personal_info) {
            $evaluation->personal_info = json_decode($evaluation->personal_info, true) ?? [];
        }

        return view('jiny-partner::admin.partner-interview-evaluations.show', compact('evaluation'));
    }

    /**
     * 면접 평가 등록 폼
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function create(Request $request)
    {
        $interviewId = $request->get('interview_id');
        $applicationId = $request->get('application_id');
        $interview = null;
        $application = null;

        // 면접 ID가 있는 경우 면접 정보와 연결된 신청서 정보를 가져옴
        if ($interviewId) {
            $interview = DB::table('partner_interviews as pi')
                ->leftJoin('partner_applications as pa', 'pi.application_id', '=', 'pa.id')
                ->leftJoin('users as interviewer', 'pi.interviewer_id', '=', 'interviewer.id')
                ->select([
                    'pi.*',
                    'pa.id as application_id',
                    'pa.personal_info',
                    'pa.user_uuid',
                    'pa.application_status',
                    'interviewer.name as interviewer_name',
                    'interviewer.email as interviewer_email'
                ])
                ->where('pi.id', $interviewId)
                ->first();

            if ($interview) {
                $applicationId = $interview->application_id;
                // personal_info JSON 파싱
                if ($interview->personal_info) {
                    $interview->personal_info = json_decode($interview->personal_info, true) ?? [];
                }
            }
        }

        // 신청서 정보 조회 (면접이 없는 경우에만)
        if ($applicationId && !$interview) {
            $application = DB::table('partner_applications as pa')
                ->leftJoin('users as u', 'pa.user_id', '=', 'u.id')
                ->select([
                    'pa.*',
                    'u.name as user_name',
                    'u.email as user_email',
                    DB::raw('json_extract(pa.personal_info, "$.name") as applicant_name')
                ])
                ->where('pa.id', $applicationId)
                ->first();
        }

        // 평가가 없는 완료된 면접 목록
        $availableInterviews = DB::table('partner_interviews as pi')
            ->leftJoin('partner_interview_evaluations as pie', 'pi.id', '=', 'pie.interview_id')
            ->leftJoin('partner_applications as pa', 'pi.application_id', '=', 'pa.id')
            ->select([
                'pi.id',
                'pi.name',
                'pi.email',
                'pi.interview_type',
                'pi.interview_round',
                'pi.scheduled_at',
                'pi.completed_at',
                'pa.id as application_id'
            ])
            ->whereNull('pie.id')
            ->whereIn('pi.interview_status', ['completed', 'in_progress'])
            ->orderBy('pi.scheduled_at', 'desc')
            ->get();

        // 기존 애플리케이션 목록 (호환성 유지)
        $applications = DB::table('partner_applications as pa')
            ->leftJoin('users as u', 'pa.user_id', '=', 'u.id')
            ->leftJoin('partner_interview_evaluations as pie', 'pa.id', '=', 'pie.application_id')
            ->select([
                'pa.id',
                'pa.personal_info',
                'u.email',
                DB::raw('json_extract(pa.personal_info, "$.name") as applicant_name'),
                DB::raw('"파트너 신청" as position_applied')
            ])
            ->whereNull('pie.id')
            ->where('pa.application_status', 'interview')
            ->orderBy('pa.created_at', 'desc')
            ->get();

        return view('jiny-partner::admin.partner-interview-evaluations.create',
            compact('interview', 'application', 'applications', 'availableInterviews'));
    }

    /**
     * 면접 평가 저장
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        try {
            // 빈 문자열을 null로 변환 (validation 전에 처리)
            $inputData = $request->all();
            foreach ($inputData as $key => $value) {
                if ($value === '') {
                    $inputData[$key] = null;
                }
            }
            $request->merge($inputData);

            $validatedData = $request->validate([
                'interview_id' => 'nullable|exists:partner_interviews,id',
                'application_id' => 'required|exists:partner_applications,id',
                'interview_date' => 'required|date',
                'duration_minutes' => 'nullable|integer|min:0|max:480',
                'interview_type' => 'required|in:video,phone,in_person,online_test',

                // 평가 점수 (0-100)
                'technical_skills' => 'nullable|integer|min:0|max:100',
                'communication' => 'nullable|integer|min:0|max:100',
                'motivation' => 'nullable|integer|min:0|max:100',
                'experience_relevance' => 'nullable|integer|min:0|max:100',
                'cultural_fit' => 'nullable|integer|min:0|max:100',
                'problem_solving' => 'nullable|integer|min:0|max:100',
                'leadership_potential' => 'nullable|integer|min:0|max:100',

                'recommendation' => 'required|in:strongly_approve,approve,conditional,reject,strongly_reject',
                'detailed_feedback' => 'nullable|string',

                // 배열 필드들
                'strengths' => 'nullable|array',
                'weaknesses' => 'nullable|array',
                'concerns' => 'nullable|array',
                'action_items' => 'nullable|array',
            ], [
                // 사용자 친화적인 오류 메시지
                'application_id.required' => '지원서 ID가 필요합니다.',
                'application_id.exists' => '존재하지 않는 지원서입니다.',
                'interview_date.required' => '면접 일시를 입력해주세요.',
                'interview_date.date' => '올바른 날짜 형식을 입력해주세요.',
                'interview_type.required' => '면접 방식을 선택해주세요.',
                'interview_type.in' => '올바른 면접 방식을 선택해주세요.',
                'recommendation.required' => '최종 추천을 선택해주세요.',
                'recommendation.in' => '올바른 추천 등급을 선택해주세요.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // AJAX 요청인 경우 JSON 형태로 validation 오류 반환
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }

            // 일반 요청인 경우 기존 처리
            throw $e;
        }

        DB::beginTransaction();
        try {
            // 종합 점수 계산
            $scores = [
                'technical_skills' => $validatedData['technical_skills'] ?? 0,
                'communication' => $validatedData['communication'] ?? 0,
                'motivation' => $validatedData['motivation'] ?? 0,
                'experience_relevance' => $validatedData['experience_relevance'] ?? 0,
                'cultural_fit' => $validatedData['cultural_fit'] ?? 0,
                'problem_solving' => $validatedData['problem_solving'] ?? 0,
                'leadership_potential' => $validatedData['leadership_potential'] ?? 0,
            ];

            $overallRating = $this->calculateOverallRating($scores);

            $data = [
                'interview_id' => $validatedData['interview_id'],
                'application_id' => $validatedData['application_id'],
                'interviewer_id' => Auth::id() ?: 0,
                'interviewer_uuid' => Auth::user()->uuid ?? null,
                'interview_date' => $validatedData['interview_date'],
                'duration_minutes' => $validatedData['duration_minutes'],
                'interview_type' => $validatedData['interview_type'],

                'technical_skills' => $validatedData['technical_skills'],
                'communication' => $validatedData['communication'],
                'motivation' => $validatedData['motivation'],
                'experience_relevance' => $validatedData['experience_relevance'],
                'cultural_fit' => $validatedData['cultural_fit'],
                'problem_solving' => $validatedData['problem_solving'],
                'leadership_potential' => $validatedData['leadership_potential'],

                'overall_rating' => $overallRating,
                'recommendation' => $validatedData['recommendation'],
                'detailed_feedback' => $validatedData['detailed_feedback'],

                'strengths' => json_encode($validatedData['strengths'] ?? []),
                'weaknesses' => json_encode($validatedData['weaknesses'] ?? []),
                'concerns' => json_encode($validatedData['concerns'] ?? []),
                'action_items' => json_encode($validatedData['action_items'] ?? []),

                'created_at' => now(),
                'updated_at' => now(),
            ];

            $evaluationId = DB::table('partner_interview_evaluations')->insertGetId($data);

            // 면접 상태 업데이트 (completed로 설정 및 평가 결과 반영)
            if ($validatedData['interview_id']) {
                $this->updateInterviewStatus($validatedData['interview_id'], $validatedData['recommendation'], $overallRating);
            }

            // 지원서 상태 업데이트 - 자동 승인 비활성화 (승인은 별도 승인 페이지에서 수동 처리)
            // $this->updateApplicationStatus($validatedData['application_id'], $validatedData['recommendation']);

            DB::commit();

            $message = '면접 평가가 성공적으로 등록되었습니다.';

            // AJAX 요청인 경우 JSON 응답
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => [
                        'evaluation_id' => $evaluationId,
                        'interview_id' => $validatedData['interview_id'] ?? null,
                        'application_id' => $validatedData['application_id']
                    ],
                    'redirect' => route('admin.partner.interview.evaluations.index')
                ]);
            }

            // 일반 요청인 경우 기존 리다이렉트
            if ($validatedData['interview_id']) {
                return redirect()->route('admin.partner.interview.evaluations.show', $evaluationId)
                    ->with('success', $message);
            }

            return redirect()->route('admin.partner.interview.evaluations.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();

            $errorMessage = '면접 평가 등록 중 오류가 발생했습니다: ' . $e->getMessage();

            // 로그에 상세 오류 기록
            \Log::error('면접 평가 저장 오류', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id() ?? 'guest'
            ]);

            // AJAX 요청인 경우 JSON 응답
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'errors' => [
                        'general' => [$errorMessage]
                    ]
                ], 422);
            }

            // 일반 요청인 경우 기존 리다이렉트
            return redirect()->back()
                ->withInput()
                ->with('error', $errorMessage);
        }
    }

    /**
     * 면접 평가 수정 폼
     *
     * @param int $id 평가 ID 또는 면접 ID (interview_id 파라미터가 있는 경우)
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit($id)
    {
        // interview_id가 전달된 경우 해당 면접의 평가를 찾음
        $evaluation = DB::table('partner_interview_evaluations as pie')
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
            ->where(function($query) use ($id) {
                // interview_id 또는 evaluation_id로 검색
                $query->where('pie.interview_id', $id)
                      ->orWhere('pie.id', $id);
            })
            ->first();

        if (!$evaluation) {
            return redirect()->back()->with('error', '평가를 찾을 수 없습니다.');
        }

        // JSON 필드 파싱
        $evaluation->strengths = json_decode($evaluation->strengths, true) ?? [];
        $evaluation->weaknesses = json_decode($evaluation->weaknesses, true) ?? [];
        $evaluation->concerns = json_decode($evaluation->concerns, true) ?? [];
        $evaluation->action_items = json_decode($evaluation->action_items, true) ?? [];

        // personal_info JSON 파싱
        if ($evaluation->personal_info) {
            $evaluation->personal_info = json_decode($evaluation->personal_info, true) ?? [];
        }

        return view('jiny-partner::admin.partner-interview-evaluations.edit', compact('evaluation'));
    }

    /**
     * 면접 평가 업데이트
     */
    public function update(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'interview_date' => 'required|date',
                'duration_minutes' => 'nullable|integer|min:1|max:480',
                'interview_type' => 'required|in:video,phone,in_person,online_test',

                'technical_skills' => 'nullable|integer|min:0|max:100',
                'communication' => 'nullable|integer|min:0|max:100',
                'motivation' => 'nullable|integer|min:0|max:100',
                'experience_relevance' => 'nullable|integer|min:0|max:100',
                'cultural_fit' => 'nullable|integer|min:0|max:100',
                'problem_solving' => 'nullable|integer|min:0|max:100',
                'leadership_potential' => 'nullable|integer|min:0|max:100',

                'recommendation' => 'required|in:strongly_approve,approve,conditional,reject,strongly_reject',
                'detailed_feedback' => 'nullable|string',

                'strengths' => 'nullable|array',
                'weaknesses' => 'nullable|array',
                'concerns' => 'nullable|array',
                'action_items' => 'nullable|array',
            ], [
                'interview_date.required' => '면접 일시를 입력해주세요.',
                'interview_type.required' => '면접 방식을 선택해주세요.',
                'recommendation.required' => '최종 추천 등급을 선택해주세요.',
                '*.min' => '점수는 0점 이상이어야 합니다.',
                '*.max' => '점수는 100점 이하여야 합니다.'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => '입력한 정보를 확인해주세요.',
                    'errors' => $e->validator->errors()
                ], 422);
            }
            throw $e;
        }

        // 종합 점수 재계산
        $scores = [
            'technical_skills' => $validatedData['technical_skills'] ?? 0,
            'communication' => $validatedData['communication'] ?? 0,
            'motivation' => $validatedData['motivation'] ?? 0,
            'experience_relevance' => $validatedData['experience_relevance'] ?? 0,
            'cultural_fit' => $validatedData['cultural_fit'] ?? 0,
            'problem_solving' => $validatedData['problem_solving'] ?? 0,
            'leadership_potential' => $validatedData['leadership_potential'] ?? 0,
        ];

        $overallRating = $this->calculateOverallRating($scores);

        $data = [
            'interview_date' => $validatedData['interview_date'],
            'duration_minutes' => $validatedData['duration_minutes'],
            'interview_type' => $validatedData['interview_type'],

            'technical_skills' => $validatedData['technical_skills'],
            'communication' => $validatedData['communication'],
            'motivation' => $validatedData['motivation'],
            'experience_relevance' => $validatedData['experience_relevance'],
            'cultural_fit' => $validatedData['cultural_fit'],
            'problem_solving' => $validatedData['problem_solving'],
            'leadership_potential' => $validatedData['leadership_potential'],

            'overall_rating' => $overallRating,
            'recommendation' => $validatedData['recommendation'],
            'detailed_feedback' => $validatedData['detailed_feedback'],

            'strengths' => json_encode($validatedData['strengths'] ?? []),
            'weaknesses' => json_encode($validatedData['weaknesses'] ?? []),
            'concerns' => json_encode($validatedData['concerns'] ?? []),
            'action_items' => json_encode($validatedData['action_items'] ?? []),

            'updated_at' => now(),
        ];

        DB::table('partner_interview_evaluations')->where('id', $id)->update($data);

        // AJAX 요청인 경우 JSON 응답
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => '면접 평가가 성공적으로 수정되었습니다.',
                'redirect' => route('admin.partner.interview.evaluations.index')
            ]);
        }

        return redirect()->route('admin.partner.interview.evaluations.index')
            ->with('success', '면접 평가가 성공적으로 수정되었습니다.');
    }

    /**
     * 면접 평가 삭제
     */
    public function destroy($id)
    {
        $evaluation = DB::table('partner_interview_evaluations')->where('id', $id)->first();

        if (!$evaluation) {
            return redirect()->back()->with('error', '평가를 찾을 수 없습니다.');
        }

        DB::table('partner_interview_evaluations')->where('id', $id)->delete();

        return redirect()->route('admin.partner.interview.evaluations.index')
            ->with('success', '면접 평가가 삭제되었습니다.');
    }

    /**
     * 종합 점수 계산 (가중 평균)
     */
    private function calculateOverallRating($scores)
    {
        $weights = [
            'technical_skills' => 0.25,
            'communication' => 0.20,
            'motivation' => 0.15,
            'experience_relevance' => 0.15,
            'cultural_fit' => 0.10,
            'problem_solving' => 0.10,
            'leadership_potential' => 0.05,
        ];

        $totalScore = 0;
        $totalWeight = 0;

        foreach ($scores as $skill => $score) {
            if ($score > 0 && isset($weights[$skill])) {
                $totalScore += $score * $weights[$skill];
                $totalWeight += $weights[$skill];
            }
        }

        return $totalWeight > 0 ? round($totalScore / $totalWeight) : 0;
    }

    /**
     * 면접 상태 업데이트
     *
     * @param int $interviewId 면접 ID
     * @param string $recommendation 추천 결과
     * @param float $overallRating 종합 평점
     * @return void
     */
    private function updateInterviewStatus($interviewId, $recommendation, $overallRating)
    {
        // 추천 결과에 따른 면접 결과 매핑
        $resultMap = [
            'strongly_approve' => 'pass',
            'approve' => 'pass',
            'conditional' => 'pending',
            'reject' => 'fail',
            'strongly_reject' => 'fail',
        ];

        $interviewResult = $resultMap[$recommendation] ?? 'pending';

        // 면접 테이블 업데이트
        DB::table('partner_interviews')
            ->where('id', $interviewId)
            ->update([
                'interview_status' => 'completed',
                'interview_result' => $interviewResult,
                'overall_score' => $overallRating,
                'completed_at' => now(),
                'updated_at' => now()
            ]);
    }

    /**
     * 지원서 상태 업데이트
     *
     * @param int $applicationId 지원서 ID
     * @param string $recommendation 추천 결과
     * @return void
     */
    private function updateApplicationStatus($applicationId, $recommendation)
    {
        // 허용된 application_status 값들: 'draft', 'submitted', 'reviewing', 'interview', 'approved', 'rejected', 'reapplied'
        $statusMap = [
            'strongly_approve' => 'approved',
            'approve' => 'approved',
            'conditional' => 'reviewing',  // 조건부 승인은 추가 검토 상태로 설정
            'reject' => 'rejected',
            'strongly_reject' => 'rejected',
        ];

        $newStatus = $statusMap[$recommendation] ?? 'interview';

        DB::table('partner_applications')
            ->where('id', $applicationId)
            ->update(['application_status' => $newStatus, 'updated_at' => now()]);
    }

    /**
     * 평가 통계 데이터
     */
    private function getEvaluationStats()
    {
        return [
            'total' => DB::table('partner_interview_evaluations')->count(),
            'by_recommendation' => DB::table('partner_interview_evaluations')
                ->select('recommendation', DB::raw('COUNT(*) as count'))
                ->groupBy('recommendation')
                ->pluck('count', 'recommendation')
                ->toArray(),
            'average_rating' => DB::table('partner_interview_evaluations')->avg('overall_rating'),
            'recent_count' => DB::table('partner_interview_evaluations')
                ->where('created_at', '>=', now()->subWeek())
                ->count(),
        ];
    }
}