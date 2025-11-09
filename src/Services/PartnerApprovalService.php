<?php

namespace Jiny\Partner\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Models\PartnerApprovalProcess;
use Jiny\Partner\Models\PartnerInterviewEvaluation;
use Exception;

class PartnerApprovalService
{
    protected PartnerActivityLogService $activityLogger;
    protected PartnerNotificationService $notificationService;
    protected PartnerPerformanceService $performanceService;

    public function __construct(
        PartnerActivityLogService $activityLogger,
        PartnerNotificationService $notificationService,
        PartnerPerformanceService $performanceService
    ) {
        $this->activityLogger = $activityLogger;
        $this->notificationService = $notificationService;
        $this->performanceService = $performanceService;
    }

    /**
     * Initiate approval process for application
     */
    public function initiateApprovalProcess(PartnerApplication $application, string $initiatedBy): bool
    {
        try {
            // Check if approval process already exists
            $existingProcess = PartnerApprovalProcess::where('application_id', $application->id)->first();
            if ($existingProcess) {
                Log::info("Approval process already exists", [
                    'application_id' => $application->id,
                    'process_id' => $existingProcess->id
                ]);
                return true;
            }

            // Create approval process record
            $approvalProcess = PartnerApprovalProcess::create([
                'application_id' => $application->id,
                'user_uuid' => $application->user_uuid,
                'current_stage' => 'initial_review',
                'status' => 'pending',
                'initiated_by' => $initiatedBy,
                'initiated_at' => now(),
                'workflow_data' => $this->getInitialWorkflowData(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Update application status
            $application->update(['application_status' => 'reviewing']);

            // Log activity
            $this->activityLogger->logStatusChange($application, 'submitted', $initiatedBy, [
                'approval_process_id' => $approvalProcess->id,
                'stage' => 'initial_review'
            ]);

            // Send notification
            $this->notificationService->sendStatusChangeNotification($application, 'submitted');

            Log::info("Approval process initiated", [
                'application_id' => $application->id,
                'process_id' => $approvalProcess->id,
                'initiated_by' => $initiatedBy
            ]);

            return true;

        } catch (Exception $e) {
            Log::error("Failed to initiate approval process", [
                'error' => $e->getMessage(),
                'application_id' => $application->id,
                'initiated_by' => $initiatedBy
            ]);
            return false;
        }
    }

    /**
     * Complete initial document review
     */
    public function completeDocumentReview(int $applicationId, string $reviewerId, array $reviewData): bool
    {
        try {
            $approvalProcess = PartnerApprovalProcess::where('application_id', $applicationId)->first();
            if (!$approvalProcess || $approvalProcess->current_stage !== 'initial_review') {
                Log::warning("Invalid approval process state for document review", [
                    'application_id' => $applicationId,
                    'current_stage' => $approvalProcess->current_stage ?? 'none'
                ]);
                return false;
            }

            // Update workflow data with review results
            $workflowData = $approvalProcess->workflow_data ?? [];
            $workflowData['document_review'] = [
                'reviewer_id' => $reviewerId,
                'reviewed_at' => now()->toISOString(),
                'review_data' => $reviewData,
                'status' => $reviewData['status'] // 'approved', 'rejected', 'needs_clarification'
            ];

            // Determine next stage based on review result
            $nextStage = $this->determineNextStage($reviewData['status'], 'initial_review');
            $processStatus = $reviewData['status'] === 'rejected' ? 'rejected' : 'pending';

            $approvalProcess->update([
                'current_stage' => $nextStage,
                'status' => $processStatus,
                'workflow_data' => $workflowData,
                'updated_at' => now()
            ]);

            // Update application status
            $application = $approvalProcess->application;
            $newApplicationStatus = $this->getApplicationStatusForStage($nextStage, $reviewData['status']);
            $application->update(['application_status' => $newApplicationStatus]);

            // Log activity
            $this->activityLogger->logGeneralActivity(
                $application->user_uuid,
                'document_review_completed',
                '서류 검토 완료',
                ['stage' => 'initial_review'],
                [
                    'stage' => $nextStage,
                    'review_status' => $reviewData['status'],
                    'review_data' => $reviewData
                ],
                $reviewerId
            );

            // Send notification
            if ($reviewData['status'] === 'rejected') {
                $this->notificationService->sendRejectionNotification($application, [
                    'reason' => $reviewData['reason'] ?? '서류 검토 결과 요구사항을 충족하지 않습니다.',
                    'feedback' => $reviewData['feedback'] ?? null,
                    'suggestions' => $reviewData['suggestions'] ?? []
                ]);
            } else {
                $this->notificationService->sendStatusChangeNotification($application, 'reviewing');
            }

            Log::info("Document review completed", [
                'application_id' => $applicationId,
                'reviewer_id' => $reviewerId,
                'status' => $reviewData['status'],
                'next_stage' => $nextStage
            ]);

            return true;

        } catch (Exception $e) {
            Log::error("Failed to complete document review", [
                'error' => $e->getMessage(),
                'application_id' => $applicationId,
                'reviewer_id' => $reviewerId
            ]);
            return false;
        }
    }

    /**
     * Schedule interview
     */
    public function scheduleInterview(int $applicationId, string $schedulerId, array $interviewData): bool
    {
        try {
            $approvalProcess = PartnerApprovalProcess::where('application_id', $applicationId)->first();
            if (!$approvalProcess || $approvalProcess->current_stage !== 'background_check') {
                Log::warning("Invalid approval process state for interview scheduling", [
                    'application_id' => $applicationId,
                    'current_stage' => $approvalProcess->current_stage ?? 'none'
                ]);
                return false;
            }

            // Update workflow data
            $workflowData = $approvalProcess->workflow_data ?? [];
            $workflowData['interview'] = [
                'scheduled_by' => $schedulerId,
                'scheduled_at' => now()->toISOString(),
                'interview_date' => $interviewData['interview_date'],
                'interview_location' => $interviewData['interview_location'] ?? null,
                'interview_type' => $interviewData['interview_type'] ?? 'video',
                'interviewer_id' => $interviewData['interviewer_id'] ?? $schedulerId,
                'meeting_url' => $interviewData['meeting_url'] ?? null,
                'notes' => $interviewData['notes'] ?? null,
                'status' => 'scheduled'
            ];

            $approvalProcess->update([
                'current_stage' => 'interview',
                'workflow_data' => $workflowData,
                'updated_at' => now()
            ]);

            // Update application status
            $application = $approvalProcess->application;
            $application->update(['application_status' => 'interview']);

            // Log activity
            $this->activityLogger->logInterviewActivity(
                $application,
                'scheduled',
                $interviewData,
                $schedulerId
            );

            // Send notification
            $this->notificationService->sendInterviewNotification($application, $interviewData);

            Log::info("Interview scheduled", [
                'application_id' => $applicationId,
                'scheduler_id' => $schedulerId,
                'interview_date' => $interviewData['interview_date']
            ]);

            return true;

        } catch (Exception $e) {
            Log::error("Failed to schedule interview", [
                'error' => $e->getMessage(),
                'application_id' => $applicationId,
                'scheduler_id' => $schedulerId
            ]);
            return false;
        }
    }

    /**
     * Complete interview evaluation
     */
    public function completeInterviewEvaluation(int $applicationId, string $interviewerId, array $evaluationData): bool
    {
        try {
            $approvalProcess = PartnerApprovalProcess::where('application_id', $applicationId)->first();
            if (!$approvalProcess || $approvalProcess->current_stage !== 'interview') {
                Log::warning("Invalid approval process state for interview evaluation", [
                    'application_id' => $applicationId,
                    'current_stage' => $approvalProcess->current_stage ?? 'none'
                ]);
                return false;
            }

            // Create interview evaluation record
            $evaluation = PartnerInterviewEvaluation::create([
                'application_id' => $applicationId,
                'interviewer_id' => $interviewerId,
                'interview_date' => $evaluationData['interview_date'] ?? now(),
                'technical_skills_score' => $evaluationData['technical_skills_score'] ?? 0,
                'communication_score' => $evaluationData['communication_score'] ?? 0,
                'motivation_score' => $evaluationData['motivation_score'] ?? 0,
                'experience_relevance_score' => $evaluationData['experience_relevance_score'] ?? 0,
                'overall_rating' => $evaluationData['overall_rating'] ?? 0,
                'recommendation' => $evaluationData['recommendation'] ?? 'pending',
                'feedback' => $evaluationData['feedback'] ?? null,
                'concerns' => $evaluationData['concerns'] ?? null,
                'action_items' => $evaluationData['action_items'] ?? null,
                'interview_notes' => $evaluationData['interview_notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Update workflow data
            $workflowData = $approvalProcess->workflow_data ?? [];
            $workflowData['interview']['status'] = 'completed';
            $workflowData['interview']['completed_at'] = now()->toISOString();
            $workflowData['interview']['evaluation_id'] = $evaluation->id;
            $workflowData['interview']['recommendation'] = $evaluationData['recommendation'];

            // Determine next stage
            $nextStage = $evaluationData['recommendation'] === 'rejected'
                ? 'final_decision'
                : 'final_review';

            $processStatus = $evaluationData['recommendation'] === 'rejected' ? 'rejected' : 'pending';

            $approvalProcess->update([
                'current_stage' => $nextStage,
                'status' => $processStatus,
                'workflow_data' => $workflowData,
                'updated_at' => now()
            ]);

            // Update application status
            $application = $approvalProcess->application;
            $newApplicationStatus = $evaluationData['recommendation'] === 'rejected'
                ? 'rejected'
                : 'reviewing';
            $application->update(['application_status' => $newApplicationStatus]);

            // Log activity
            $this->activityLogger->logInterviewActivity(
                $application,
                'conducted',
                [
                    'new_values' => $evaluationData,
                    'evaluation_id' => $evaluation->id
                ],
                $interviewerId
            );

            Log::info("Interview evaluation completed", [
                'application_id' => $applicationId,
                'interviewer_id' => $interviewerId,
                'recommendation' => $evaluationData['recommendation'],
                'overall_rating' => $evaluationData['overall_rating']
            ]);

            return true;

        } catch (Exception $e) {
            Log::error("Failed to complete interview evaluation", [
                'error' => $e->getMessage(),
                'application_id' => $applicationId,
                'interviewer_id' => $interviewerId
            ]);
            return false;
        }
    }

    /**
     * Make final approval decision
     */
    public function makeFinalDecision(int $applicationId, string $deciderId, string $decision, string $reason, ?array $additionalData = null): bool
    {
        try {
            $approvalProcess = PartnerApprovalProcess::where('application_id', $applicationId)->first();
            if (!$approvalProcess || !in_array($approvalProcess->current_stage, ['final_review', 'final_decision'])) {
                Log::warning("Invalid approval process state for final decision", [
                    'application_id' => $applicationId,
                    'current_stage' => $approvalProcess->current_stage ?? 'none'
                ]);
                return false;
            }

            $application = $approvalProcess->application;

            // Update workflow data
            $workflowData = $approvalProcess->workflow_data ?? [];
            $workflowData['final_decision'] = [
                'decided_by' => $deciderId,
                'decided_at' => now()->toISOString(),
                'decision' => $decision,
                'reason' => $reason,
                'additional_data' => $additionalData
            ];

            // Update approval process
            $approvalProcess->update([
                'current_stage' => 'completed',
                'status' => $decision,
                'decided_by' => $deciderId,
                'decided_at' => now(),
                'decision_reason' => $reason,
                'workflow_data' => $workflowData,
                'updated_at' => now()
            ]);

            // Update application
            $application->update([
                'application_status' => $decision,
                'rejection_reason' => $decision === 'rejected' ? $reason : null
            ]);

            // Handle approval
            if ($decision === 'approved') {
                $this->handleApproval($application, $additionalData ?? []);
            }

            // Log activity
            $this->activityLogger->logDecisionActivity(
                $application,
                $decision,
                $reason,
                $deciderId,
                $additionalData
            );

            // Send notification
            if ($decision === 'approved') {
                $this->notificationService->sendApprovalNotification($application, $additionalData ?? []);
            } else {
                $this->notificationService->sendRejectionNotification($application, [
                    'reason' => $reason,
                    'feedback' => $additionalData['feedback'] ?? null,
                    'suggestions' => $additionalData['suggestions'] ?? []
                ]);
            }

            Log::info("Final decision made", [
                'application_id' => $applicationId,
                'decider_id' => $deciderId,
                'decision' => $decision,
                'reason' => $reason
            ]);

            return true;

        } catch (Exception $e) {
            Log::error("Failed to make final decision", [
                'error' => $e->getMessage(),
                'application_id' => $applicationId,
                'decider_id' => $deciderId,
                'decision' => $decision
            ]);
            return false;
        }
    }

    /**
     * Get approval process status
     */
    public function getApprovalProcessStatus(int $applicationId): ?array
    {
        try {
            $approvalProcess = PartnerApprovalProcess::where('application_id', $applicationId)->first();
            if (!$approvalProcess) {
                return null;
            }

            $workflowData = $approvalProcess->workflow_data ?? [];
            $application = $approvalProcess->application;

            return [
                'process_id' => $approvalProcess->id,
                'application_id' => $applicationId,
                'current_stage' => $approvalProcess->current_stage,
                'status' => $approvalProcess->status,
                'initiated_by' => $approvalProcess->initiated_by,
                'initiated_at' => $approvalProcess->initiated_at,
                'decided_by' => $approvalProcess->decided_by,
                'decided_at' => $approvalProcess->decided_at,
                'decision_reason' => $approvalProcess->decision_reason,
                'stages_completed' => $this->getCompletedStages($workflowData),
                'current_stage_info' => $this->getCurrentStageInfo($approvalProcess->current_stage, $workflowData),
                'next_possible_actions' => $this->getNextPossibleActions($approvalProcess->current_stage),
                'workflow_data' => $workflowData,
                'application_info' => [
                    'user_uuid' => $application->user_uuid,
                    'application_status' => $application->application_status,
                    'submitted_at' => $application->created_at
                ]
            ];

        } catch (Exception $e) {
            Log::error("Failed to get approval process status", [
                'error' => $e->getMessage(),
                'application_id' => $applicationId
            ]);
            return null;
        }
    }

    /**
     * Handle successful approval
     */
    private function handleApproval(PartnerApplication $application, array $approvalData): void
    {
        try {
            // Create partner user account
            $partnerData = [
                'user_id' => $application->user_id,
                'user_uuid' => $application->user_uuid,
                'tier_id' => $approvalData['tier_id'] ?? 1, // Default to Bronze
                'tier_name' => $approvalData['tier_name'] ?? 'Bronze',
                'type_id' => $approvalData['type_id'] ?? null,
                'type_name' => $approvalData['type_name'] ?? null,
                'commission_rate' => $approvalData['commission_rate'] ?? 60,
                'status' => 'active',
                'joined_at' => now(),
                'referrer_uuid' => $this->extractReferrerUuid($application),
                'created_at' => now(),
                'updated_at' => now()
            ];

            $partnerUser = PartnerUser::create($partnerData);

            // Log partner account creation
            $this->activityLogger->logPartnerAccountCreation($application, $partnerData);

            // Record initial performance metrics
            $this->performanceService->recordMetric(
                $application->user_uuid,
                'account_created',
                1,
                ['approval_date' => now()->toDateString()]
            );

            Log::info("Partner account created successfully", [
                'application_id' => $application->id,
                'partner_uuid' => $application->user_uuid,
                'tier' => $partnerData['tier_name']
            ]);

        } catch (Exception $e) {
            Log::error("Failed to handle approval", [
                'error' => $e->getMessage(),
                'application_id' => $application->id
            ]);
            throw $e;
        }
    }

    /**
     * Helper methods
     */
    private function getInitialWorkflowData(): array
    {
        return [
            'stages' => [
                'initial_review' => ['status' => 'pending', 'started_at' => now()->toISOString()],
                'background_check' => ['status' => 'pending'],
                'interview' => ['status' => 'pending'],
                'final_review' => ['status' => 'pending'],
                'final_decision' => ['status' => 'pending']
            ]
        ];
    }

    private function determineNextStage(string $reviewStatus, string $currentStage): string
    {
        if ($reviewStatus === 'rejected') {
            return 'final_decision';
        }

        $stageFlow = [
            'initial_review' => 'background_check',
            'background_check' => 'interview',
            'interview' => 'final_review',
            'final_review' => 'final_decision'
        ];

        return $stageFlow[$currentStage] ?? 'final_decision';
    }

    private function getApplicationStatusForStage(string $stage, string $reviewStatus = null): string
    {
        if ($reviewStatus === 'rejected') {
            return 'rejected';
        }

        $statusMap = [
            'initial_review' => 'reviewing',
            'background_check' => 'reviewing',
            'interview' => 'interview',
            'final_review' => 'reviewing',
            'final_decision' => 'reviewing',
            'completed' => 'approved'
        ];

        return $statusMap[$stage] ?? 'reviewing';
    }

    private function getCompletedStages(array $workflowData): array
    {
        $completed = [];
        $stages = $workflowData['stages'] ?? [];

        foreach ($stages as $stageName => $stageData) {
            if (($stageData['status'] ?? 'pending') === 'completed') {
                $completed[] = $stageName;
            }
        }

        return $completed;
    }

    private function getCurrentStageInfo(string $currentStage, array $workflowData): array
    {
        $stageData = $workflowData[$currentStage] ?? [];

        return [
            'stage_name' => $currentStage,
            'stage_status' => $stageData['status'] ?? 'pending',
            'started_at' => $stageData['started_at'] ?? null,
            'description' => $this->getStageDescription($currentStage)
        ];
    }

    private function getNextPossibleActions(string $currentStage): array
    {
        $actions = [
            'initial_review' => ['approve_documents', 'reject_documents', 'request_clarification'],
            'background_check' => ['schedule_interview', 'reject_application'],
            'interview' => ['complete_evaluation', 'reschedule_interview'],
            'final_review' => ['approve_application', 'reject_application'],
            'final_decision' => ['finalize_decision'],
            'completed' => []
        ];

        return $actions[$currentStage] ?? [];
    }

    private function getStageDescription(string $stage): string
    {
        $descriptions = [
            'initial_review' => '서류 검토 및 기본 자격 확인',
            'background_check' => '배경 조사 및 추가 검증',
            'interview' => '면접 진행 및 평가',
            'final_review' => '최종 검토 및 승인 준비',
            'final_decision' => '최종 승인/거부 결정',
            'completed' => '승인 프로세스 완료'
        ];

        return $descriptions[$stage] ?? '알 수 없는 단계';
    }

    private function extractReferrerUuid(PartnerApplication $application): ?string
    {
        $referralDetails = $application->referral_details ?? [];
        return $referralDetails['referrer_uuid'] ?? null;
    }
}