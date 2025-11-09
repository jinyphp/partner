<?php

namespace Jiny\Partner\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerActivityLog;
use Exception;

class PartnerActivityLogService
{
    /**
     * Log application status change activity
     */
    public function logStatusChange(PartnerApplication $application, string $previousStatus, ?string $changedBy = null, ?array $metadata = null): void
    {
        try {
            $this->createActivityLog([
                'user_uuid' => $application->user_uuid,
                'application_id' => $application->id,
                'activity_type' => 'status_change',
                'activity_description' => "신청 상태가 '{$previousStatus}'에서 '{$application->application_status}'로 변경됨",
                'old_values' => ['status' => $previousStatus],
                'new_values' => ['status' => $application->application_status],
                'changed_by' => $changedBy,
                'metadata' => array_merge([
                    'previous_status' => $previousStatus,
                    'current_status' => $application->application_status,
                    'change_timestamp' => now()->toISOString()
                ], $metadata ?? [])
            ]);

        } catch (Exception $e) {
            Log::error("Failed to log status change activity", [
                'error' => $e->getMessage(),
                'application_id' => $application->id,
                'user_uuid' => $application->user_uuid
            ]);
        }
    }

    /**
     * Log application submission activity
     */
    public function logApplicationSubmission(PartnerApplication $application, string $submissionType = 'new'): void
    {
        try {
            $description = $submissionType === 'reapplication'
                ? '파트너 재신청서 제출'
                : '파트너 신청서 제출';

            $this->createActivityLog([
                'user_uuid' => $application->user_uuid,
                'application_id' => $application->id,
                'activity_type' => 'application_submitted',
                'activity_description' => $description,
                'old_values' => null,
                'new_values' => [
                    'application_status' => $application->application_status,
                    'submission_type' => $submissionType
                ],
                'metadata' => [
                    'submission_type' => $submissionType,
                    'application_data' => [
                        'has_personal_info' => !empty($application->personal_info),
                        'has_experience_info' => !empty($application->experience_info),
                        'has_skills_info' => !empty($application->skills_info),
                        'has_documents' => !empty($application->documents),
                        'referral_source' => $application->referral_source
                    ]
                ]
            ]);

        } catch (Exception $e) {
            Log::error("Failed to log application submission", [
                'error' => $e->getMessage(),
                'application_id' => $application->id,
                'submission_type' => $submissionType
            ]);
        }
    }

    /**
     * Log interview activity
     */
    public function logInterviewActivity(PartnerApplication $application, string $activityType, array $interviewData, ?string $conductedBy = null): void
    {
        try {
            $descriptions = [
                'scheduled' => '면접 일정 설정',
                'conducted' => '면접 진행 완료',
                'rescheduled' => '면접 일정 변경',
                'cancelled' => '면접 취소'
            ];

            $description = $descriptions[$activityType] ?? '면접 관련 활동';

            $this->createActivityLog([
                'user_uuid' => $application->user_uuid,
                'application_id' => $application->id,
                'activity_type' => 'interview_' . $activityType,
                'activity_description' => $description,
                'old_values' => $interviewData['old_values'] ?? null,
                'new_values' => $interviewData['new_values'] ?? null,
                'changed_by' => $conductedBy,
                'metadata' => [
                    'interview_activity' => $activityType,
                    'interview_data' => $interviewData,
                    'conducted_by' => $conductedBy
                ]
            ]);

        } catch (Exception $e) {
            Log::error("Failed to log interview activity", [
                'error' => $e->getMessage(),
                'application_id' => $application->id,
                'activity_type' => $activityType
            ]);
        }
    }

    /**
     * Log approval/rejection activity
     */
    public function logDecisionActivity(PartnerApplication $application, string $decision, string $reason, ?string $decidedBy = null, ?array $additionalData = null): void
    {
        try {
            $description = $decision === 'approved'
                ? '파트너 신청 승인'
                : '파트너 신청 거부';

            $this->createActivityLog([
                'user_uuid' => $application->user_uuid,
                'application_id' => $application->id,
                'activity_type' => 'decision_made',
                'activity_description' => $description,
                'old_values' => ['status' => 'reviewing'],
                'new_values' => [
                    'status' => $decision,
                    'reason' => $reason
                ],
                'changed_by' => $decidedBy,
                'metadata' => array_merge([
                    'decision' => $decision,
                    'reason' => $reason,
                    'decided_by' => $decidedBy,
                    'decision_timestamp' => now()->toISOString()
                ], $additionalData ?? [])
            ]);

        } catch (Exception $e) {
            Log::error("Failed to log decision activity", [
                'error' => $e->getMessage(),
                'application_id' => $application->id,
                'decision' => $decision
            ]);
        }
    }

    /**
     * Log partner account creation activity
     */
    public function logPartnerAccountCreation(PartnerApplication $application, array $partnerAccountData, ?string $createdBy = null): void
    {
        try {
            $this->createActivityLog([
                'user_uuid' => $application->user_uuid,
                'application_id' => $application->id,
                'activity_type' => 'partner_account_created',
                'activity_description' => '파트너 계정 생성 완료',
                'old_values' => null,
                'new_values' => [
                    'partner_tier' => $partnerAccountData['tier_name'] ?? null,
                    'partner_type' => $partnerAccountData['type_name'] ?? null,
                    'commission_rate' => $partnerAccountData['commission_rate'] ?? null
                ],
                'changed_by' => $createdBy,
                'metadata' => [
                    'partner_account_data' => $partnerAccountData,
                    'created_by' => $createdBy,
                    'account_creation_timestamp' => now()->toISOString()
                ]
            ]);

        } catch (Exception $e) {
            Log::error("Failed to log partner account creation", [
                'error' => $e->getMessage(),
                'application_id' => $application->id,
                'user_uuid' => $application->user_uuid
            ]);
        }
    }

    /**
     * Log document upload activity
     */
    public function logDocumentActivity(PartnerApplication $application, string $activityType, array $documentData): void
    {
        try {
            $descriptions = [
                'uploaded' => '서류 업로드',
                'updated' => '서류 수정',
                'deleted' => '서류 삭제',
                'verified' => '서류 검증 완료'
            ];

            $description = $descriptions[$activityType] ?? '서류 관련 활동';

            $this->createActivityLog([
                'user_uuid' => $application->user_uuid,
                'application_id' => $application->id,
                'activity_type' => 'document_' . $activityType,
                'activity_description' => $description,
                'old_values' => $documentData['old_values'] ?? null,
                'new_values' => $documentData['new_values'] ?? null,
                'metadata' => [
                    'document_activity' => $activityType,
                    'document_data' => $documentData
                ]
            ]);

        } catch (Exception $e) {
            Log::error("Failed to log document activity", [
                'error' => $e->getMessage(),
                'application_id' => $application->id,
                'activity_type' => $activityType
            ]);
        }
    }

    /**
     * Log general partner activity
     */
    public function logGeneralActivity(string $userUuid, string $activityType, string $description, ?array $oldValues = null, ?array $newValues = null, ?string $changedBy = null, ?array $metadata = null): void
    {
        try {
            $this->createActivityLog([
                'user_uuid' => $userUuid,
                'application_id' => null,
                'activity_type' => $activityType,
                'activity_description' => $description,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'changed_by' => $changedBy,
                'metadata' => $metadata
            ]);

        } catch (Exception $e) {
            Log::error("Failed to log general activity", [
                'error' => $e->getMessage(),
                'user_uuid' => $userUuid,
                'activity_type' => $activityType
            ]);
        }
    }

    /**
     * Get activity logs for a specific user
     */
    public function getUserActivityLogs(string $userUuid, int $limit = 50): array
    {
        try {
            return PartnerActivityLog::where('user_uuid', $userUuid)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->toArray();

        } catch (Exception $e) {
            Log::error("Failed to get user activity logs", [
                'error' => $e->getMessage(),
                'user_uuid' => $userUuid
            ]);
            return [];
        }
    }

    /**
     * Get activity logs for a specific application
     */
    public function getApplicationActivityLogs(int $applicationId): array
    {
        try {
            return PartnerActivityLog::where('application_id', $applicationId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray();

        } catch (Exception $e) {
            Log::error("Failed to get application activity logs", [
                'error' => $e->getMessage(),
                'application_id' => $applicationId
            ]);
            return [];
        }
    }

    /**
     * Get activity statistics for administrative purposes
     */
    public function getActivityStatistics(array $filters = []): array
    {
        try {
            $query = PartnerActivityLog::query();

            // Apply date range filter
            if (!empty($filters['start_date'])) {
                $query->where('created_at', '>=', $filters['start_date']);
            }
            if (!empty($filters['end_date'])) {
                $query->where('created_at', '<=', $filters['end_date']);
            }

            // Apply activity type filter
            if (!empty($filters['activity_type'])) {
                $query->where('activity_type', $filters['activity_type']);
            }

            return [
                'total_activities' => $query->count(),
                'activities_by_type' => $query->groupBy('activity_type')
                    ->selectRaw('activity_type, count(*) as count')
                    ->pluck('count', 'activity_type')
                    ->toArray(),
                'recent_activities' => $query->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get(['activity_type', 'activity_description', 'created_at'])
                    ->toArray()
            ];

        } catch (Exception $e) {
            Log::error("Failed to get activity statistics", [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            return [];
        }
    }

    /**
     * Create activity log record
     */
    private function createActivityLog(array $logData): void
    {
        PartnerActivityLog::create(array_merge([
            'created_at' => now(),
            'updated_at' => now()
        ], $logData));
    }

    /**
     * Clean up old activity logs (for maintenance)
     */
    public function cleanupOldLogs(int $daysToKeep = 365): int
    {
        try {
            $cutoffDate = now()->subDays($daysToKeep);

            $deletedCount = PartnerActivityLog::where('created_at', '<', $cutoffDate)->delete();

            Log::info("Cleaned up old partner activity logs", [
                'deleted_count' => $deletedCount,
                'cutoff_date' => $cutoffDate->toDateString(),
                'days_kept' => $daysToKeep
            ]);

            return $deletedCount;

        } catch (Exception $e) {
            Log::error("Failed to cleanup old activity logs", [
                'error' => $e->getMessage(),
                'days_to_keep' => $daysToKeep
            ]);
            return 0;
        }
    }
}