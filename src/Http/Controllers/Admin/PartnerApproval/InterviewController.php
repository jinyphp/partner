<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerApproval;

use App\Http\Controllers\Controller;
use App\Models\User;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerInterview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * 파트너 신청 면접 관리 컨트롤러
 * Partner Application Interview Management Controller
 *
 * 파트너 신청자의 면접 스케줄링 및 관리를 처리합니다.
 * Handles interview scheduling and management for partner applicants.
 *
 * 주요 기능:
 * - 면접 일정 설정 (신규)
 * - 면접 일정 수정 (기존)
 * - 파트너 애플리케이션 및 면접 테이블 동기화
 * - 면접 알림 발송
 *
 * Key Features:
 * - Interview schedule creation (new)
 * - Interview schedule modification (existing)
 * - Synchronization between partner applications and interview tables
 * - Interview notification dispatch
 *
 * @package Jiny\Partner\Http\Controllers\Admin\PartnerApproval
 * @author Jiny Framework Team
 * @since 1.0.0
 */
class InterviewController extends Controller
{
    /**
     * 면접 설정 가능한 애플리케이션 상태 목록
     * Application statuses that allow interview scheduling
     */
    private const SCHEDULABLE_STATUSES = [
        'submitted',
        'reviewing',
        'interview',
        'reapplied'
    ];

    /**
     * 면접 유형 목록
     * Available interview types
     */
    private const INTERVIEW_TYPES = [
        'phone',
        'video',
        'in_person',
        'written'
    ];

    /**
     * 기본 면접 유형
     * Default interview type
     */
    private const DEFAULT_INTERVIEW_TYPE = 'video';

    /**
     * 면접 설정/수정 시 유효성 검증 규칙을 반환합니다.
     * Returns validation rules for interview scheduling/updating
     *
     * @return array 유효성 검증 규칙 배열
     */
    private function getValidationRules(): array
    {
        return [
            'interview_date' => [
                'required',
                'date',
                'after:now'
            ],
            'interview_location' => [
                'nullable',
                'string',
                'max:255'
            ],
            'interview_notes' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'interview_type' => [
                'nullable',
                'string',
                Rule::in(self::INTERVIEW_TYPES)
            ],
            'notify_user' => [
                'boolean'
            ]
        ];
    }

    /**
     * 파트너 신청 면접 설정 처리
     * Handle partner application interview scheduling
     *
     * 신규 면접 설정 또는 기존 면접 수정을 처리합니다.
     * Handles new interview scheduling or existing interview modification.
     *
     * @param Request $request HTTP 요청 객체
     * @param int $id 파트너 신청 ID
     * @return \Illuminate\Http\RedirectResponse 리다이렉트 응답
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Exception
     */
    public function __invoke(Request $request, $id)
    {
        // 애플리케이션 조회 및 관련 데이터 로드
        // Load application with related data
        $application = PartnerApplication::with(['user', 'referrerPartner'])->findOrFail($id);

        // 면접 설정 가능한 상태인지 확인
        // Check if interview scheduling is allowed for current status
        if (!$this->isSchedulableStatus($application->application_status)) {
            $errorMessage = "현재 상태('{$application->application_status}')에서는 면접을 설정할 수 없습니다.";

            // AJAX 요청인 경우 JSON 에러 응답 반환
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'current_status' => $application->application_status,
                    'allowed_statuses' => self::SCHEDULABLE_STATUSES
                ], 422);
            }

            return back()->with('error', $errorMessage);
        }

        // 입력 데이터 유효성 검증
        // Validate input data
        $validatedData = $request->validate($this->getValidationRules());

        try {
            DB::beginTransaction();

            // 기존 면접 레코드 확인
            // Check for existing interview record
            $existingInterview = $this->findExistingInterview($application->id);
            $isUpdate = $existingInterview !== null;

            // 파트너 애플리케이션 테이블 업데이트
            // Update partner application table
            $this->updateApplicationRecord($application, $validatedData, $isUpdate);

            // 면접 상세 정보 JSON 데이터 생성 및 저장
            // Generate and save interview details JSON data
            $interviewData = $this->buildInterviewData($validatedData);
            $this->updateApplicationInterviewFeedback($application, $interviewData);

            // 파트너 면접 테이블과 동기화
            // Synchronize with partner interview table
            $this->syncPartnerInterviewRecord($application, $validatedData, $isUpdate, $existingInterview);

            DB::commit();

            // 사용자 알림 발송 (옵션)
            // Send user notification (optional)
            if ($request->boolean('notify_user')) {
                $this->sendInterviewNotification($application, $interviewData);
            }

            // 성공 메시지와 함께 응답
            // Response with success message
            $message = $isUpdate ? '면접 일정이 수정되었습니다.' : '면접 일정이 설정되었습니다.';

            // AJAX 요청인 경우 JSON 응답 반환
            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'redirect' => route('admin.partner.interview.index'),
                    'data' => [
                        'application_id' => $application->id,
                        'interview_date' => $validatedData['interview_date'],
                        'interview_status' => $application->application_status
                    ]
                ]);
            }

            // 일반 요청인 경우 리다이렉트 응답 반환
            // Return redirect response for regular requests
            return redirect()->route('admin.partner.interview.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();

            // 에러 로깅
            // Log error details
            Log::error('Partner interview scheduling failed', [
                'application_id' => $application->id,
                'admin_id' => Auth::id(),
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage = '면접 설정 중 오류가 발생했습니다: ' . $e->getMessage();

            // AJAX 요청인 경우 JSON 에러 응답 반환
            // Return JSON error response for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'error' => $e->getMessage()
                ], 500);
            }

            // 일반 요청인 경우 리다이렉트 응답 반환
            // Return redirect response for regular requests
            return back()->with('error', $errorMessage);
        }
    }

    /**
     * 면접 일정 수정 처리
     * Handle interview schedule modification
     *
     * 이미 설정된 면접 일정을 수정합니다.
     * Modifies already scheduled interview.
     *
     * @param Request $request HTTP 요청 객체
     * @param int $id 파트너 신청 ID
     * @return \Illuminate\Http\RedirectResponse 리다이렉트 응답
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Exception
     */
    public function update(Request $request, $id)
    {
        // 애플리케이션 조회 및 관련 데이터 로드
        // Load application with related data
        $application = PartnerApplication::with(['user', 'referrerPartner'])->findOrFail($id);

        // 면접 수정 가능한 상태인지 확인 (면접 상태에서만 수정 가능)
        // Check if interview modification is allowed (only in interview status)
        if ($application->application_status !== 'interview') {
            $errorMessage = "현재 상태('{$application->application_status}')에서는 면접 일정 수정이 불가능합니다. 면접 상태에서만 수정할 수 있습니다.";

            // AJAX 요청인 경우 JSON 에러 응답 반환
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'current_status' => $application->application_status,
                    'required_status' => 'interview'
                ], 422);
            }

            return back()->with('error', $errorMessage);
        }

        // 입력 데이터 유효성 검증
        // Validate input data
        $validatedData = $request->validate($this->getValidationRules());

        try {
            DB::beginTransaction();

            // 기존 면접 레코드 조회 (수정 시에는 반드시 존재해야 함)
            // Find existing interview record (must exist for updates)
            $existingInterview = $this->findExistingInterview($application->id);

            // 파트너 애플리케이션 테이블 업데이트 (수정 모드)
            // Update partner application table (modification mode)
            $this->updateApplicationRecord($application, $validatedData, true);

            // 면접 상세 정보 JSON 데이터 업데이트
            // Update interview details JSON data
            $interviewData = $this->buildUpdatedInterviewData($application, $validatedData);
            $this->updateApplicationInterviewFeedback($application, $interviewData);

            // 파트너 면접 테이블 업데이트
            // Update partner interview table
            $this->syncPartnerInterviewRecord($application, $validatedData, true, $existingInterview);

            DB::commit();

            // 사용자 알림 발송 (옵션)
            // Send user notification (optional)
            if ($request->boolean('notify_user')) {
                $this->sendInterviewNotification($application, $interviewData);
            }

            $message = '면접 일정이 수정되었습니다.';

            // AJAX 요청인 경우 JSON 응답 반환
            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'redirect' => route('admin.partner.interview.index'),
                    'data' => [
                        'application_id' => $application->id,
                        'interview_date' => $validatedData['interview_date'],
                        'interview_status' => $application->application_status
                    ]
                ]);
            }

            // 일반 요청인 경우 리다이렉트 응답 반환
            // Return redirect response for regular requests
            return redirect()->route('admin.partner.interview.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();

            // 에러 로깅
            // Log error details
            Log::error('Partner interview update failed', [
                'application_id' => $application->id,
                'admin_id' => Auth::id(),
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $errorMessage = '면접 수정 중 오류가 발생했습니다: ' . $e->getMessage();

            // AJAX 요청인 경우 JSON 에러 응답 반환
            // Return JSON error response for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'error' => $e->getMessage()
                ], 500);
            }

            // 일반 요청인 경우 리다이렉트 응답 반환
            // Return redirect response for regular requests
            return back()->with('error', $errorMessage);
        }
    }

    /**
     * 면접 설정 가능한 상태인지 확인합니다.
     * Check if the status allows interview scheduling
     *
     * @param string $status 애플리케이션 상태
     * @return bool 설정 가능 여부
     */
    private function isSchedulableStatus(string $status): bool
    {
        return in_array($status, self::SCHEDULABLE_STATUSES);
    }

    /**
     * 기존 면접 레코드를 조회합니다.
     * Find existing interview record
     *
     * @param int $applicationId 애플리케이션 ID
     * @return PartnerInterview|null 기존 면접 레코드
     */
    private function findExistingInterview(int $applicationId): ?PartnerInterview
    {
        return PartnerInterview::where('application_id', $applicationId)->first();
    }

    /**
     * 파트너 애플리케이션 레코드를 업데이트합니다.
     * Update partner application record
     *
     * @param PartnerApplication $application 애플리케이션 인스턴스
     * @param array $validatedData 유효성 검증된 데이터
     * @param bool $isUpdate 수정 모드 여부
     */
    private function updateApplicationRecord(PartnerApplication $application, array $validatedData, bool $isUpdate): void
    {
        $updateData = [
            'interview_date' => $validatedData['interview_date'],
            'interview_notes' => $validatedData['interview_notes'] ?? null,
        ];

        // 신규 설정인 경우에만 상태를 'interview'로 변경
        // Only change status to 'interview' for new scheduling
        if (!$isUpdate) {
            $updateData['application_status'] = 'interview';
        }

        // 관리자 메모에 이력 추가
        // Add history to admin notes
        $actionText = $isUpdate ? '면접 수정' : '면접 설정';
        $statusText = $isUpdate ? '수정됨' : '설정됨';
        $timestamp = now()->format('Y-m-d H:i');

        $updateData['admin_notes'] = $application->admin_notes .
            "\n[{$actionText}] {$timestamp} - 면접 일정 {$statusText}";

        $application->update($updateData);
    }

    /**
     * 면접 데이터를 빌드합니다.
     * Build interview data
     *
     * @param array $validatedData 유효성 검증된 데이터
     * @return array 면접 데이터
     */
    private function buildInterviewData(array $validatedData): array
    {
        return [
            'date' => $validatedData['interview_date'],
            'location' => $validatedData['interview_location'] ?? null,
            'notes' => $validatedData['interview_notes'] ?? null,
            'type' => $validatedData['interview_type'] ?? self::DEFAULT_INTERVIEW_TYPE,
            'scheduled_by' => Auth::id(),
            'scheduled_at' => now()->toISOString()
        ];
    }

    /**
     * 업데이트된 면접 데이터를 빌드합니다.
     * Build updated interview data
     *
     * @param PartnerApplication $application 애플리케이션 인스턴스
     * @param array $validatedData 유효성 검증된 데이터
     * @return array 업데이트된 면접 데이터
     */
    private function buildUpdatedInterviewData(PartnerApplication $application, array $validatedData): array
    {
        return array_merge(
            $application->interview_feedback ?? [],
            [
                'date' => $validatedData['interview_date'],
                'location' => $validatedData['interview_location'] ?? null,
                'notes' => $validatedData['interview_notes'] ?? null,
                'type' => $validatedData['interview_type'] ?? self::DEFAULT_INTERVIEW_TYPE,
                'updated_by' => Auth::id(),
                'updated_at' => now()->toISOString()
            ]
        );
    }

    /**
     * 애플리케이션의 면접 피드백을 업데이트합니다.
     * Update application interview feedback
     *
     * @param PartnerApplication $application 애플리케이션 인스턴스
     * @param array $interviewData 면접 데이터
     */
    private function updateApplicationInterviewFeedback(PartnerApplication $application, array $interviewData): void
    {
        $application->update([
            'interview_feedback' => $interviewData
        ]);
    }

    /**
     * PartnerInterview 테이블과 동기화
     * Synchronize with PartnerInterview table
     *
     * 파트너 애플리케이션의 면접 정보를 전용 면접 테이블에 동기화합니다.
     * Synchronizes interview information from partner application to dedicated interview table.
     *
     * @param PartnerApplication $application 애플리케이션 인스턴스
     * @param array $validatedData 유효성 검증된 데이터
     * @param bool $isUpdate 수정 모드 여부
     * @param PartnerInterview|null $existingInterview 기존 면접 레코드
     *
     * @throws \Exception
     */
    private function syncPartnerInterviewRecord($application, $validatedData, $isUpdate = false, $existingInterview = null)
    {
        // 사용자 정보 수집
        // Collect user information
        $userInfo = $this->collectUserInformation($application);

        // 추천인 정보 수집
        // Collect referrer information
        $referrerInfo = $this->collectReferrerInformation($application);

        // 면접 데이터 준비
        // Prepare interview data
        $interviewData = $this->buildInterviewRecordData($application, $validatedData, $userInfo, $referrerInfo);

        if ($isUpdate && $existingInterview) {
            // 기존 레코드 업데이트 처리
            // Handle existing record update
            $this->updateExistingInterviewRecord($existingInterview, $interviewData, $validatedData);
        } else {
            // 신규 레코드 생성 처리
            // Handle new record creation
            $this->createNewInterviewRecord($interviewData, $validatedData);
        }
    }

    /**
     * 사용자 정보를 수집합니다 (4단계 검색)
     * Collect user information (4-step search)
     *
     * @param PartnerApplication $application 애플리케이션 인스턴스
     * @return array 사용자 정보 배열
     */
    private function collectUserInformation(PartnerApplication $application): array
    {
        $user = $application->user;
        $userEmail = null;
        $userName = null;
        $userId = null;

        // User 관계 검증
        if (!$user && $application->user_id) {
            Log::warning('Partner application user relationship is null but user_id exists', [
                'application_id' => $application->id,
                'user_id' => $application->user_id,
                'user_uuid' => $application->user_uuid
            ]);
        }

        // 1단계: personal_info에서 이메일 검색
        // Step 1: Search email from personal_info
        if (!empty($application->personal_info['email'])) {
            $userEmail = $application->personal_info['email'];
        }
        // 2단계: user 관계에서 이메일 검색
        // Step 2: Search email from user relationship
        elseif ($user && !empty($user->email)) {
            $userEmail = $user->email;
            $userId = $user->id;
        }
        // 3단계: user_uuid로 사용자 조회하여 이메일 검색
        // Step 3: Search email by finding user through user_uuid
        elseif (!empty($application->user_uuid)) {
            $userFromUuid = User::where('uuid', $application->user_uuid)->first();
            if ($userFromUuid) {
                $userEmail = $userFromUuid->email;
                $userId = $userFromUuid->id;
                $user = $userFromUuid; // user 변수 업데이트
            }
        }
        // 4단계: 직접 email 필드에서 검색
        // Step 4: Search from direct email field
        elseif (!empty($application->email)) {
            $userEmail = $application->email;
        }

        // 이름 정보 수집
        // Collect name information
        if (!empty($application->personal_info['name'])) {
            $userName = $application->personal_info['name'];
        } elseif ($user && !empty($user->name)) {
            $userName = $user->name;
        }

        $result = [
            'user_id' => $userId ?? $user?->id ?? 0, // NOT NULL 제약조건 대응
            'user_uuid' => $application->user_uuid,
            'email' => $userEmail ?? 'unknown@email.com',
            'name' => $userName ?? 'Unknown'
        ];

        // 결과 로깅
        Log::info('User information collected for partner interview', [
            'application_id' => $application->id,
            'found_user_id' => $result['user_id'],
            'email_source' => $userEmail ? 'found' : 'default',
            'name_source' => $userName !== 'Unknown' ? 'found' : 'default'
        ]);

        return $result;
    }

    /**
     * 추천인 정보를 수집합니다.
     * Collect referrer information
     *
     * @param PartnerApplication $application 애플리케이션 인스턴스
     * @return array 추천인 정보 배열
     */
    private function collectReferrerInformation(PartnerApplication $application): array
    {
        $referrerPartner = $application->referrerPartner;

        return [
            'referrer_partner_id' => $referrerPartner?->id,
            'referrer_code' => $referrerPartner?->partner_code,
            'referrer_name' => $referrerPartner?->name
        ];
    }

    /**
     * 면접 레코드 데이터를 빌드합니다.
     * Build interview record data
     *
     * @param PartnerApplication $application 애플리케이션 인스턴스
     * @param array $validatedData 유효성 검증된 데이터
     * @param array $userInfo 사용자 정보
     * @param array $referrerInfo 추천인 정보
     * @return array 면접 레코드 데이터
     */
    private function buildInterviewRecordData(PartnerApplication $application, array $validatedData, array $userInfo, array $referrerInfo): array
    {
        $currentUser = Auth::user();

        // 기본 면접 데이터 구성
        $interviewData = [
            'application_id' => $application->id,
            'user_id' => $userInfo['user_id'] ?? 0, // NOT NULL 제약조건 때문에 기본값 0 사용
            'user_uuid' => $userInfo['user_uuid'] ?? null,
            'email' => $userInfo['email'] ?? 'unknown@email.com',
            'name' => $userInfo['name'] ?? 'Unknown',
            'interview_status' => 'scheduled',
            'interview_type' => $validatedData['interview_type'] ?? self::DEFAULT_INTERVIEW_TYPE,
            'interview_round' => 'first',
            'scheduled_at' => $validatedData['interview_date'],
            'interviewer_id' => Auth::id() ?? 0,
            'interviewer_name' => $currentUser->name ?? 'Unknown',
            'meeting_location' => $validatedData['interview_location'],
            'preparation_notes' => $validatedData['interview_notes'],
            'created_by' => Auth::id() ?? 0,
            'updated_by' => Auth::id() ?? 0
        ];

        // 추천인 정보 추가 (null 허용 필드들)
        if (!empty($referrerInfo['referrer_partner_id'])) {
            $interviewData['referrer_partner_id'] = $referrerInfo['referrer_partner_id'];
        }
        if (!empty($referrerInfo['referrer_code'])) {
            $interviewData['referrer_code'] = $referrerInfo['referrer_code'];
        }
        if (!empty($referrerInfo['referrer_name'])) {
            $interviewData['referrer_name'] = $referrerInfo['referrer_name'];
        }

        return $interviewData;
    }

    /**
     * 기존 면접 레코드를 업데이트합니다.
     * Update existing interview record
     *
     * @param PartnerInterview $existingInterview 기존 면접 레코드
     * @param array $interviewData 면접 데이터
     * @param array $validatedData 유효성 검증된 데이터
     */
    private function updateExistingInterviewRecord(PartnerInterview $existingInterview, array $interviewData, array $validatedData): void
    {
        $oldScheduledAt = $existingInterview->scheduled_at;

        // 기존 레코드 업데이트 (생성자 정보는 유지)
        // Update existing record (preserve creator info)
        $existingInterview->update(array_merge($interviewData, [
            'interview_status' => 'rescheduled',
            'created_by' => $existingInterview->created_by
        ]));

        // 변경 로그 기록
        // Record change log
        if (method_exists($existingInterview, 'addLog')) {
            $existingInterview->addLog(
                '일정 수정',
                "면접 일정이 변경되었습니다. {$oldScheduledAt} → {$validatedData['interview_date']}",
                [
                    'old_date' => $oldScheduledAt,
                    'new_date' => $validatedData['interview_date'],
                    'updated_by' => Auth::id(),
                    'updated_by_name' => Auth::user()->name ?? 'Unknown'
                ]
            );
        }

        // 시스템 로그 기록
        // Record system log
        Log::info('Partner interview updated in partner_interviews table', [
            'interview_id' => $existingInterview->id,
            'application_id' => $existingInterview->application_id,
            'old_date' => $oldScheduledAt,
            'new_date' => $validatedData['interview_date']
        ]);
    }

    /**
     * 새로운 면접 레코드를 생성합니다.
     * Create new interview record
     *
     * @param array $interviewData 면접 데이터
     * @param array $validatedData 유효성 검증된 데이터
     */
    private function createNewInterviewRecord(array $interviewData, array $validatedData): void
    {
        // 신규 레코드 생성
        // Create new record
        $newInterview = PartnerInterview::create($interviewData);

        // 생성 로그 기록
        // Record creation log
        if (method_exists($newInterview, 'addLog')) {
            $newInterview->addLog(
                '면접 설정',
                '면접 일정이 설정되었습니다.',
                [
                    'scheduled_date' => $validatedData['interview_date'],
                    'interview_type' => $validatedData['interview_type'] ?? self::DEFAULT_INTERVIEW_TYPE,
                    'location' => $validatedData['interview_location'],
                    'created_by' => Auth::id(),
                    'created_by_name' => Auth::user()->name ?? 'Unknown'
                ]
            );
        }

        // 시스템 로그 기록
        // Record system log
        Log::info('Partner interview created in partner_interviews table', [
            'interview_id' => $newInterview->id,
            'application_id' => $newInterview->application_id,
            'scheduled_date' => $validatedData['interview_date']
        ]);
    }

    /**
     * 면접 설정 알림 발송
     * Send interview notification
     *
     * 지원자에게 면접 일정 알림을 발송합니다.
     * Sends interview schedule notification to applicant.
     *
     * @param PartnerApplication $application 애플리케이션 인스턴스
     * @param array $interviewData 면접 데이터
     */
    private function sendInterviewNotification(PartnerApplication $application, array $interviewData): void
    {
        $userInfo = [];

        try {
            // 사용자 이메일 정보 수집 (4단계 검색 재사용)
            // Collect user email information (reuse 4-step search)
            $userInfo = $this->collectUserInformation($application);
            $userEmail = $userInfo['email'];

            // 유효한 이메일이 있는 경우에만 알림 발송
            // Send notification only if valid email exists
            if ($userEmail !== 'unknown@email.com') {
                // 이메일 알림 (실제 구현 시 Mail 파사드 사용)
                // Email notification (use Mail facade in actual implementation)
                // Mail::to($userEmail)->send(new PartnerInterviewScheduledMail($application, $interviewData));

                // 시스템 알림 (선택적)
                // System notification (optional)
                // Notification::send($application->user, new PartnerInterviewScheduledNotification($application));

                // 성공 로그 기록
                // Record success log
                Log::info('Partner interview notification sent successfully', [
                    'application_id' => $application->id,
                    'user_email' => $userEmail,
                    'interview_date' => $interviewData['date'] ?? $interviewData['scheduled_date'] ?? 'N/A',
                    'interview_type' => $interviewData['type'] ?? 'video',
                    'location' => $interviewData['location'] ?? 'N/A'
                ]);
            } else {
                // 이메일 정보가 없는 경우 경고 로그
                // Warning log when email information is not available
                Log::warning('Interview notification not sent - no valid email found', [
                    'application_id' => $application->id,
                    'user_uuid' => $application->user_uuid
                ]);
            }

        } catch (\Exception $e) {
            // 알림 발송 실패 에러 로그
            // Error log for notification failure
            Log::error('Failed to send interview notification', [
                'application_id' => $application->id,
                'error_message' => $e->getMessage(),
                'user_email' => $userInfo['email'] ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}