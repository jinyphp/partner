<?php

namespace Jiny\Partner\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class PartnerApplication extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'partner_applications';

    protected $fillable = [
        'user_id',
        'user_uuid',
        'shard_number',
        'application_status',
        'personal_info',
        'experience_info',
        'skills_info',
        'documents',
        'interview_date',
        'interview_notes',
        'interview_feedback',
        'approval_date',
        'approved_by',
        'rejection_date',
        'rejection_reason',
        'rejected_by',
        'admin_notes',
        'expected_hourly_rate',
        'preferred_work_areas',
        'availability_schedule',
        'previous_application_id',
        'reapplication_reason',

        // 재신청 관련 필드들
        'motivation',
        'improvement_plan',
        'project_experience',
        'goals',
        'submitted_at',

        // 추천자 정보 필드들 (MLM 지원)
        'referral_source',
        'referral_code',
        'referrer_name',
        'referrer_contact',
        'referrer_relationship',
        'meeting_date',
        'meeting_location',
        'introduction_method'
    ];

    protected $casts = [
        'personal_info' => 'array',
        'experience_info' => 'array',
        'skills_info' => 'array',
        'documents' => 'array',
        'interview_date' => 'datetime',
        'interview_feedback' => 'array',
        'approval_date' => 'datetime',
        'rejection_date' => 'datetime',
        'expected_hourly_rate' => 'decimal:2',
        'submitted_at' => 'datetime',
        'meeting_date' => 'date',
        'preferred_work_areas' => 'array',
        'availability_schedule' => 'array'
    ];

    /**
     * 지원자 사용자
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 승인 처리자
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * 거절 처리자
     */
    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * 승인된 지원서만 조회
     */
    public function scopeApproved($query)
    {
        return $query->where('application_status', 'approved');
    }

    /**
     * 거절된 지원서만 조회
     */
    public function scopeRejected($query)
    {
        return $query->where('application_status', 'rejected');
    }

    /**
     * 검토 중인 지원서만 조회
     */
    public function scopePending($query)
    {
        return $query->whereIn('application_status', ['submitted', 'reviewing']);
    }

    /**
     * 면접 예정인 지원서만 조회
     */
    public function scopeInterviewScheduled($query)
    {
        return $query->where('application_status', 'interview')
                    ->whereNotNull('interview_date');
    }

    /**
     * 지원서 승인 처리
     */
    public function approve($adminId)
    {
        $this->update([
            'application_status' => 'approved',
            'approval_date' => now(),
            'approved_by' => $adminId
        ]);

        // 파트너 회원 계정 생성
        return $this->createPartnerUser();
    }

    /**
     * 지원서 거절 처리
     */
    public function reject($adminId, $reason)
    {
        $this->update([
            'application_status' => 'rejected',
            'rejection_date' => now(),
            'rejected_by' => $adminId,
            'rejection_reason' => $reason
        ]);
    }

    /**
     * 면접 일정 설정
     */
    public function scheduleInterview($interviewDate, $notes = null)
    {
        $this->update([
            'application_status' => 'interview',
            'interview_date' => $interviewDate,
            'interview_notes' => $notes
        ]);
    }

    /**
     * 면접 피드백 저장
     */
    public function saveInterviewFeedback($feedback)
    {
        $this->update([
            'interview_feedback' => $feedback
        ]);
    }

    /**
     * 파트너 회원 계정 생성
     */
    private function createPartnerUser()
    {
        // 기본 파트너 등급 조회 (가장 낮은 등급)
        $defaultTier = PartnerTier::where('is_active', true)
            ->orderBy('priority_level', 'desc')
            ->first();

        if (!$defaultTier) {
            throw new \Exception('기본 파트너 등급이 설정되지 않았습니다.');
        }

        // 사용자 정보 조회
        $user = $this->user;

        // 프로필 데이터 구성
        $profileData = [
            'application_id' => $this->id,
            'personal_info' => $this->personal_info,
            'experience_info' => $this->experience_info,
            'skills_info' => $this->skills_info,
            'preferred_work_areas' => $this->preferred_work_areas,
            'availability_schedule' => $this->availability_schedule,
            'expected_hourly_rate' => $this->expected_hourly_rate,
            'approved_at' => now()->toISOString(),
            'status_history' => [
                [
                    'status' => 'active',
                    'reason' => '파트너 지원서 승인',
                    'changed_by' => $this->approved_by,
                    'changed_at' => now()->toISOString()
                ]
            ]
        ];

        return PartnerUser::create([
            'user_id' => $this->user_id,
            'user_table' => 'users', // 기본적으로 메인 users 테이블
            'user_uuid' => $user->uuid ?? null,
            'shard_number' => 0, // 메인 테이블이므로 0
            'email' => $user->email,
            'name' => $user->name,
            'partner_tier_id' => $defaultTier->id,
            'status' => 'active',
            'total_completed_jobs' => 0,
            'average_rating' => 0,
            'punctuality_rate' => 0,
            'satisfaction_rate' => 0,
            'partner_joined_at' => now(),
            'tier_assigned_at' => now(),
            'profile_data' => $profileData,
            'admin_notes' => '파트너 지원서 승인을 통해 등록됨',
            'created_by' => $this->approved_by
        ]);
    }


    /**
     * 지원서 완성도 계산
     */
    public function getCompletenessScore()
    {
        $score = 0;
        $maxScore = 100;

        // 개인정보 (25점)
        if ($this->personal_info && count($this->personal_info) >= 4) {
            $score += 25;
        }

        // 경력정보 (25점)
        if ($this->experience_info && isset($this->experience_info['total_years'])) {
            $score += 25;
        }

        // 기술정보 (25점)
        if ($this->skills_info && isset($this->skills_info['skills'])) {
            $score += 25;
        }

        // 제출서류 (25점)
        if ($this->documents && count($this->documents) >= 2) {
            $score += 25;
        }

        return round(($score / $maxScore) * 100);
    }
}