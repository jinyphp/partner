<?php

namespace Jiny\Partner\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerNotification;
use Illuminate\Support\Facades\DB;
use Exception;

class PartnerNotificationService
{
    /**
     * Send notification for application status change
     */
    public function sendStatusChangeNotification(PartnerApplication $application, string $previousStatus): void
    {
        try {
            $userProfile = DB::table('user_profile')->where('user_uuid', $application->user_uuid)->first();
            if (!$userProfile) {
                Log::warning("User profile not found for UUID: {$application->user_uuid}");
                return;
            }

            $notificationData = $this->prepareStatusNotificationData($application, $previousStatus);

            // Create notification record
            $notification = PartnerNotification::create([
                'user_uuid' => $application->user_uuid,
                'application_id' => $application->id,
                'type' => 'status_change',
                'title' => $notificationData['title'],
                'message' => $notificationData['message'],
                'data' => $notificationData['data'],
                'channel' => 'email',
                'status' => 'pending'
            ]);

            // Send email notification
            $this->sendEmailNotification($userProfile, $notificationData);

            // Update notification status
            $notification->update(['status' => 'sent', 'sent_at' => now()]);

            Log::info("Status change notification sent", [
                'user_uuid' => $application->user_uuid,
                'application_id' => $application->id,
                'status' => $application->application_status,
                'previous_status' => $previousStatus
            ]);

        } catch (Exception $e) {
            Log::error("Failed to send status change notification", [
                'error' => $e->getMessage(),
                'application_id' => $application->id,
                'user_uuid' => $application->user_uuid
            ]);
        }
    }

    /**
     * Send interview scheduled notification
     */
    public function sendInterviewNotification(PartnerApplication $application, array $interviewData): void
    {
        try {
            $userProfile = DB::table('user_profile')->where('user_uuid', $application->user_uuid)->first();
            if (!$userProfile) return;

            $notificationData = [
                'title' => '파트너 면접 일정 안내',
                'message' => "면접이 예정되었습니다. 일정: {$interviewData['interview_date']}",
                'data' => [
                    'interview_date' => $interviewData['interview_date'],
                    'interview_location' => $interviewData['interview_location'] ?? null,
                    'interviewer_notes' => $interviewData['notes'] ?? null,
                    'application_status' => $application->application_status
                ]
            ];

            $notification = PartnerNotification::create([
                'user_uuid' => $application->user_uuid,
                'application_id' => $application->id,
                'type' => 'interview_scheduled',
                'title' => $notificationData['title'],
                'message' => $notificationData['message'],
                'data' => $notificationData['data'],
                'channel' => 'email',
                'status' => 'pending'
            ]);

            $this->sendEmailNotification($userProfile, $notificationData);
            $notification->update(['status' => 'sent', 'sent_at' => now()]);

        } catch (Exception $e) {
            Log::error("Failed to send interview notification", [
                'error' => $e->getMessage(),
                'application_id' => $application->id
            ]);
        }
    }

    /**
     * Send approval notification with partner account creation details
     */
    public function sendApprovalNotification(PartnerApplication $application, array $partnerData): void
    {
        try {
            $userProfile = DB::table('user_profile')->where('user_uuid', $application->user_uuid)->first();
            if (!$userProfile) return;

            $notificationData = [
                'title' => '파트너 승인 완료',
                'message' => '축하합니다! 파트너 신청이 승인되어 계정이 생성되었습니다.',
                'data' => [
                    'partner_tier' => $partnerData['tier_name'] ?? 'Bronze',
                    'partner_type' => $partnerData['type_name'] ?? null,
                    'commission_rate' => $partnerData['commission_rate'] ?? null,
                    'next_steps' => [
                        '파트너 대시보드 접속',
                        '초기 교육 프로그램 수강',
                        '첫 번째 프로젝트 시작'
                    ]
                ]
            ];

            $notification = PartnerNotification::create([
                'user_uuid' => $application->user_uuid,
                'application_id' => $application->id,
                'type' => 'approval',
                'title' => $notificationData['title'],
                'message' => $notificationData['message'],
                'data' => $notificationData['data'],
                'channel' => 'email',
                'status' => 'pending'
            ]);

            $this->sendEmailNotification($userProfile, $notificationData);
            $notification->update(['status' => 'sent', 'sent_at' => now()]);

        } catch (Exception $e) {
            Log::error("Failed to send approval notification", [
                'error' => $e->getMessage(),
                'application_id' => $application->id
            ]);
        }
    }

    /**
     * Send rejection notification with feedback and reapplication guidance
     */
    public function sendRejectionNotification(PartnerApplication $application, array $rejectionData): void
    {
        try {
            $userProfile = DB::table('user_profile')->where('user_uuid', $application->user_uuid)->first();
            if (!$userProfile) return;

            $notificationData = [
                'title' => '파트너 신청 검토 결과',
                'message' => '신청 검토가 완료되었습니다. 상세 내용을 확인해 주세요.',
                'data' => [
                    'rejection_reason' => $rejectionData['reason'] ?? '추가 검토가 필요합니다.',
                    'feedback' => $rejectionData['feedback'] ?? null,
                    'improvement_suggestions' => $rejectionData['suggestions'] ?? [],
                    'reapplication_available' => true,
                    'reapplication_url' => route('home.partner.regist.reapply', $application->id)
                ]
            ];

            $notification = PartnerNotification::create([
                'user_uuid' => $application->user_uuid,
                'application_id' => $application->id,
                'type' => 'rejection',
                'title' => $notificationData['title'],
                'message' => $notificationData['message'],
                'data' => $notificationData['data'],
                'channel' => 'email',
                'status' => 'pending'
            ]);

            $this->sendEmailNotification($userProfile, $notificationData);
            $notification->update(['status' => 'sent', 'sent_at' => now()]);

        } catch (Exception $e) {
            Log::error("Failed to send rejection notification", [
                'error' => $e->getMessage(),
                'application_id' => $application->id
            ]);
        }
    }

    /**
     * Prepare notification data based on status change
     */
    private function prepareStatusNotificationData(PartnerApplication $application, string $previousStatus): array
    {
        $status = $application->application_status;

        $messages = [
            'submitted' => [
                'title' => '파트너 신청 접수 완료',
                'message' => '파트너 신청이 성공적으로 접수되었습니다. 검토 후 연락드리겠습니다.',
            ],
            'reviewing' => [
                'title' => '파트너 신청 검토 시작',
                'message' => '신청서 검토가 시작되었습니다. 담당자가 배정되어 상세 검토를 진행합니다.',
            ],
            'interview' => [
                'title' => '면접 단계 진입',
                'message' => '서류 검토가 완료되어 면접 단계로 진입했습니다. 곧 면접 일정을 안내드리겠습니다.',
            ],
            'reapplied' => [
                'title' => '재신청 접수 완료',
                'message' => '재신청이 접수되었습니다. 이전 피드백을 반영한 개선사항을 중심으로 검토하겠습니다.',
            ]
        ];

        $defaultMessage = [
            'title' => '파트너 신청 상태 변경',
            'message' => "신청 상태가 '{$previousStatus}'에서 '{$status}'로 변경되었습니다."
        ];

        $messageData = $messages[$status] ?? $defaultMessage;

        return [
            'title' => $messageData['title'],
            'message' => $messageData['message'],
            'data' => [
                'application_id' => $application->id,
                'current_status' => $status,
                'previous_status' => $previousStatus,
                'status_updated_at' => now()->toISOString(),
                'next_steps' => $this->getNextStepsForStatus($status)
            ]
        ];
    }

    /**
     * Get next steps based on current status
     */
    private function getNextStepsForStatus(string $status): array
    {
        $steps = [
            'submitted' => [
                '서류 검토가 진행됩니다',
                '추가 서류 요청시 빠른 제출 부탁드립니다',
                '상태 변경시 알림을 받으실 수 있습니다'
            ],
            'reviewing' => [
                '담당자가 상세 검토를 진행합니다',
                '필요시 추가 정보를 요청할 수 있습니다',
                '검토 완료까지 3-5일 소요됩니다'
            ],
            'interview' => [
                '면접 일정 안내를 기다려 주세요',
                '포트폴리오와 경력서류를 준비해 주세요',
                '화상 또는 대면 면접으로 진행됩니다'
            ],
            'reapplied' => [
                '개선사항을 중심으로 재검토가 진행됩니다',
                '이전 피드백 내용을 참고해 주세요',
                '재검토 완료까지 2-3일 소요됩니다'
            ]
        ];

        return $steps[$status] ?? ['상태 변경에 따른 후속 절차를 안내드리겠습니다'];
    }

    /**
     * Send email notification (placeholder for actual email implementation)
     */
    private function sendEmailNotification($userProfile, array $notificationData): void
    {
        // TODO: Implement actual email sending using Laravel Mail
        // This is a placeholder that logs the email content
        Log::info("Email notification would be sent", [
            'recipient' => $userProfile->email,
            'title' => $notificationData['title'],
            'message' => $notificationData['message']
        ]);
    }

    /**
     * Get unread notifications for user
     */
    public function getUnreadNotifications(string $userUuid): array
    {
        return PartnerNotification::where('user_uuid', $userUuid)
            ->where('read_at', null)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $notificationId, string $userUuid): bool
    {
        $notification = PartnerNotification::where('id', $notificationId)
            ->where('user_uuid', $userUuid)
            ->first();

        if ($notification) {
            $notification->update(['read_at' => now()]);
            return true;
        }

        return false;
    }
}