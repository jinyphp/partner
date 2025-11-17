<?php

namespace Jiny\Partner\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

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
        'introduction_method',

        // 추천 파트너 및 계층 구조 관련 필드들 (StoreController에서 사용)
        'referrer_partner_id',
        'referral_details',
        'expected_tier_level',
        'expected_tier_path',
        'expected_commission_rate',
        'referral_bonus_eligible',
        'referral_bonus_amount',
        'referral_registered_at'
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
        'availability_schedule' => 'array',

        // 추천 관련 필드 캐스팅
        'referral_details' => 'array',
        'expected_commission_rate' => 'decimal:4',
        'referral_bonus_amount' => 'decimal:2',
        'referral_bonus_eligible' => 'boolean',
        'referral_registered_at' => 'datetime'
    ];

    /**
     * 지원자 사용자 관계 (Eloquent 관계)
     */
    public function user()
    {
        // 기본 users 테이블에서 조회
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /**
     * 지원자 사용자 (샤드 테이블에서 동적 조회)
     */
    public function getUser()
    {
        $userTable = $this->shard_number ? 'user_' . str_pad($this->shard_number, 3, '0', STR_PAD_LEFT) : 'users';

        try {
            return DB::table($userTable)->where('id', $this->user_id)->first();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 승인 처리자 관계 (Eloquent 관계)
     */
    public function approver()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    /**
     * 거절 처리자 관계 (Eloquent 관계)
     */
    public function rejector()
    {
        return $this->belongsTo(\App\Models\User::class, 'rejected_by');
    }

    /**
     * 승인 처리자 (관리자)
     */
    public function getApprover()
    {
        if (!$this->approved_by) {
            return null;
        }

        try {
            return DB::table('users')->where('id', $this->approved_by)->first();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 거절 처리자 (관리자)
     */
    public function getRejector()
    {
        if (!$this->rejected_by) {
            return null;
        }

        try {
            return DB::table('users')->where('id', $this->rejected_by)->first();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 추천 파트너
     */
    public function referrerPartner()
    {
        return $this->belongsTo(PartnerUser::class, 'referrer_partner_id');
    }

    /**
     * 면접 기록들
     */
    public function interviews()
    {
        return $this->hasMany(PartnerInterview::class, 'application_id');
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
        $userTable = $this->shard_number ? 'user_' . str_pad($this->shard_number, 3, '0', STR_PAD_LEFT) : 'users';

        // 이미 파트너 계정이 존재하는지 먼저 확인 (승인 상태와 관계없이)
        $existingPartner = PartnerUser::where('user_id', $this->user_id)
            ->where('user_table', $userTable)
            ->first();

        if ($existingPartner) {
            // 이미 파트너가 존재하는 경우, 신청서만 승인 상태로 업데이트
            $this->update([
                'application_status' => 'approved',
                'approval_date' => now(),
                'approved_by' => $adminId
            ]);

            \Log::info('기존 파트너 계정이 존재하여 신청서만 승인 처리', [
                'application_id' => $this->id,
                'existing_partner_id' => $existingPartner->id,
                'user_id' => $this->user_id,
                'user_table' => $userTable
            ]);

            return $existingPartner;
        }

        // 이미 승인된 경우이지만 파트너 계정이 없다면 재생성
        if ($this->application_status === 'approved') {
            return $this->createPartnerUser();
        }

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

        // 사용자 정보 조회 (샤드 테이블에서 직접 조회 시도, 실패 시 지원서 정보 사용)
        $user = null;
        $userTable = $this->shard_number ? 'user_' . str_pad($this->shard_number, 3, '0', STR_PAD_LEFT) : 'users';

        try {
            $user = DB::table($userTable)->where('id', $this->user_id)->first();
        } catch (\Exception $e) {
            // 샤드 테이블이 존재하지 않거나 접근 실패 시 지원서 정보 사용
            \Log::info('샤드 테이블 조회 실패, 지원서 정보 사용', [
                'user_table' => $userTable,
                'user_id' => $this->user_id,
                'error' => $e->getMessage()
            ]);
        }

        // 폴백으로 getUser 메서드를 통한 사용자 조회 시도
        if (!$user) {
            $user = $this->getUser();
        }

        // 추천인 파트너 정보 조회
        $referrerPartner = null;
        $parentId = null;
        $level = 0;
        $treePath = '';
        $canRecruit = true;

        if ($this->referrer_partner_id) {
            $referrerPartner = PartnerUser::find($this->referrer_partner_id);

            if ($referrerPartner) {
                $parentId = $referrerPartner->id;
                $level = $referrerPartner->level + 1;
                $treePath = $referrerPartner->tree_path ? $referrerPartner->tree_path . '/' . $referrerPartner->id : '/' . $referrerPartner->id;

                // 하위 파트너의 모집 권한은 상위 파트너의 등급과 설정에 따라 결정
                $canRecruit = $referrerPartner->partnerTier && $referrerPartner->partnerTier->can_recruit;
            }
        }

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
            'referrer_info' => $referrerPartner ? [
                'referrer_id' => $referrerPartner->id,
                'referrer_code' => $referrerPartner->partner_code,
                'referrer_name' => $referrerPartner->name,
                'referral_date' => now()->toISOString()
            ] : null,
            'status_history' => [
                [
                    'status' => 'active',
                    'reason' => '파트너 지원서 승인',
                    'changed_by' => $this->approved_by,
                    'changed_at' => now()->toISOString()
                ]
            ]
        ];

        // 기존 파트너 계정이 있는지 확인
        $userTable = $this->shard_number ? 'user_' . str_pad($this->shard_number, 3, '0', STR_PAD_LEFT) : 'users';
        $existingPartner = PartnerUser::where('user_id', $this->user_id)
            ->where('user_table', $userTable)
            ->first();

        if ($existingPartner) {
            // 이미 존재하는 파트너가 있다면 해당 파트너 반환
            \Log::info('기존 파트너 계정 발견, 새로 생성하지 않고 기존 계정 반환', [
                'application_id' => $this->id,
                'existing_partner_id' => $existingPartner->id,
                'user_id' => $this->user_id,
                'user_table' => $userTable
            ]);
            return $existingPartner;
        }

        // 새 파트너 계정 생성 (안전한 생성)
        try {
            $newPartner = PartnerUser::create([
                'user_id' => $this->user_id,
                'user_table' => $this->shard_number ? 'user_' . str_pad($this->shard_number, 3, '0', STR_PAD_LEFT) : 'users',
                'user_uuid' => $this->user_uuid ?? ($user ? $user->uuid ?? null : null),
                'shard_number' => $this->shard_number ?? 0,
                'email' => $this->personal_info['email'] ?? ($user ? $user->email ?? null : null),
                'name' => $this->personal_info['name'] ?? ($user ? $user->name ?? null : null),
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
                'created_by' => $this->approved_by,

                // 계층 구조 관련 필드
                'parent_id' => $parentId,
                'level' => $level,
                'tree_path' => $treePath,
                'children_count' => 0,
                'total_children_count' => 0,
                'max_children' => 10,
                'individual_commission_rate' => 0,
                'discount_rate' => 0,
                'monthly_sales' => 0,
                'total_sales' => 0,
                'team_sales' => 0,
                'earned_commissions' => 0,
                'can_recruit' => true,
                'last_activity_at' => now(),
                'network_settings' => [
                    'recruitment_enabled' => true,
                    'commission_sharing' => true,
                    'auto_tier_upgrade' => true
                ]
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // UNIQUE 제약 위반 시 기존 파트너 조회하여 반환
            if (str_contains($e->getMessage(), 'UNIQUE constraint failed')) {
                \Log::warning('파트너 생성 중 UNIQUE 제약 위반, 기존 파트너 조회', [
                    'application_id' => $this->id,
                    'user_id' => $this->user_id,
                    'user_table' => $userTable,
                    'error' => $e->getMessage()
                ]);

                $existingPartner = PartnerUser::where('user_id', $this->user_id)
                    ->where('user_table', $userTable)
                    ->first();

                if ($existingPartner) {
                    return $existingPartner;
                }
            }

            // 다른 데이터베이스 오류는 그대로 throw
            throw $e;
        }

        // 추천인이 있는 경우 계층 구조 설정
        if ($referrerPartner && $newPartner) {
            try {
                // 추천인의 하위 파트너 수 증가
                $referrerPartner->increment('children_count');
                $referrerPartner->updateTotalChildrenCount();

                // 관리자 노트에 추천 관계 기록
                $adminNote = $newPartner->admin_notes . "\n추천인: {$referrerPartner->name} ({$referrerPartner->partner_code})";
                $newPartner->update(['admin_notes' => $adminNote]);

                // 네트워크 관계 생성 (만약 PartnerNetworkRelationship 모델이 존재한다면)
                if (class_exists('Jiny\\Partner\\Models\\PartnerNetworkRelationship')) {
                    \Jiny\Partner\Models\PartnerNetworkRelationship::create([
                        'parent_id' => $referrerPartner->id,
                        'child_id' => $newPartner->id,
                        'depth' => 1,
                        'relationship_path' => $referrerPartner->id . '/' . $newPartner->id,
                        'recruiter_id' => $this->approved_by,
                        'recruited_at' => now(),
                        'recruitment_details' => [
                            'recruited_by_tier' => $referrerPartner->partnerTier->tier_name ?? null,
                            'recruited_to_tier' => $newPartner->partnerTier->tier_name ?? null,
                            'recruitment_date' => now()->toDateString()
                        ]
                    ]);
                }

            } catch (\Exception $e) {
                // 계층 구조 설정 실패 시 로그 기록하지만 파트너 생성은 유지
                \Log::warning('파트너 계층 구조 설정 실패', [
                    'new_partner_id' => $newPartner->id,
                    'referrer_partner_id' => $referrerPartner->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $newPartner;
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