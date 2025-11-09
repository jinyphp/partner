<?php

namespace Jiny\Partner\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class PartnerEngineer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'partner_engineers';

    protected $fillable = [
        'user_id',
        'engineer_code',
        'current_tier',
        'status',
        'hire_date',
        'total_earnings',
        'current_month_earnings',
        'last_month_earnings',
        'average_rating',
        'total_completed_jobs',
        'current_month_jobs',
        'punctuality_rate',
        'customer_satisfaction',
        'last_tier_evaluation',
        'next_tier_eligible_date',
        'specializations',
        'certifications',
        'bio',
        'availability',
        'hourly_rate',
        'preferred_region',
        'max_travel_distance_km'
    ];

    protected $casts = [
        'hire_date' => 'date',
        'total_earnings' => 'decimal:2',
        'current_month_earnings' => 'decimal:2',
        'last_month_earnings' => 'decimal:2',
        'average_rating' => 'decimal:2',
        'total_completed_jobs' => 'integer',
        'current_month_jobs' => 'integer',
        'punctuality_rate' => 'decimal:2',
        'customer_satisfaction' => 'decimal:2',
        'last_tier_evaluation' => 'datetime',
        'next_tier_eligible_date' => 'date',
        'specializations' => 'array',
        'certifications' => 'array',
        'availability' => 'array',
        'hourly_rate' => 'decimal:2',
        'max_travel_distance_km' => 'decimal:2'
    ];

    /**
     * 연결된 사용자
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 현재 등급 정보
     */
    public function tier()
    {
        return $this->belongsTo(PartnerTier::class, 'current_tier', 'tier_code');
    }

    /**
     * 파트너 지원서 (역방향)
     */
    public function application()
    {
        return $this->hasOne(PartnerApplication::class, 'user_id', 'user_id');
    }

    /**
     * 파트너가 제공 가능한 서비스 타입들
     */
    public function serviceTypes()
    {
        return $this->hasMany(PartnerServiceType::class);
    }

    /**
     * 활성 파트너만 조회
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * 등급별 조회
     */
    public function scopeByTier($query, $tier)
    {
        return $query->where('current_tier', $tier);
    }

    /**
     * 평점 기준 조회
     */
    public function scopeHighRated($query, $minRating = 4.0)
    {
        return $query->where('average_rating', '>=', $minRating);
    }

    /**
     * 지역별 조회
     */
    public function scopeByRegion($query, $region)
    {
        return $query->where('preferred_region', $region);
    }

    /**
     * 완전 검증된 파트너 여부
     */
    public function getIsVerifiedAttribute()
    {
        return $this->status === 'active'
            && $this->average_rating >= 4.0
            && $this->total_completed_jobs >= 10;
    }

    /**
     * 다음 등급 승급 가능 여부
     */
    public function canUpgradeTier()
    {
        $nextTier = PartnerTier::where('priority_level', '<', $this->tier->priority_level)
                                ->orderByDesc('priority_level')
                                ->first();

        if (!$nextTier) {
            return false; // 이미 최고 등급
        }

        return $nextTier->canAchieveTier($this);
    }

    /**
     * 이번달 성과 요약
     */
    public function getCurrentMonthPerformance()
    {
        return [
            'jobs_completed' => $this->current_month_jobs,
            'earnings' => $this->current_month_earnings,
            'average_rating' => $this->average_rating,
            'tier' => $this->current_tier
        ];
    }

    /**
     * 특정 서비스 타입을 제공할 수 있는지 확인
     */
    public function canProvideServiceType($serviceType)
    {
        return $this->serviceTypes()
                   ->where('service_type', $serviceType)
                   ->where('is_available', true)
                   ->exists();
    }

    /**
     * 특정 서비스 타입에서의 숙련도 조회
     */
    public function getServiceTypeProficiency($serviceType)
    {
        return $this->serviceTypes()
                   ->where('service_type', $serviceType)
                   ->first();
    }

    /**
     * 서비스 타입별 전문 점수 계산
     */
    public function getServiceTypeScore($serviceType)
    {
        $serviceTypeProficiency = $this->getServiceTypeProficiency($serviceType);

        if (!$serviceTypeProficiency) {
            return 0;
        }

        $proficiencyScore = $serviceTypeProficiency->getProficiencyScore();
        $tierScore = $this->tier ? $this->tier->priority_level * 10 : 0;
        $ratingScore = $this->average_rating * 10;
        $experienceScore = min($this->total_completed_jobs / 10, 20);

        return $proficiencyScore + $tierScore + $ratingScore + $experienceScore;
    }

    /**
     * 서비스 타입 추가/업데이트
     */
    public function addOrUpdateServiceType($serviceType, $data = [])
    {
        return $this->serviceTypes()->updateOrCreate(
            ['service_type' => $serviceType],
            array_merge([
                'proficiency_level' => 'beginner',
                'experience_years' => 0,
                'is_available' => true,
                'max_concurrent_jobs' => 1,
                'success_rate' => 100.0
            ], $data)
        );
    }

    /**
     * 서비스 타입별 가용성 확인
     */
    public function isAvailableForServiceType($serviceType)
    {
        if ($this->status !== 'active') {
            return false;
        }

        $serviceTypeProficiency = $this->getServiceTypeProficiency($serviceType);

        if (!$serviceTypeProficiency || !$serviceTypeProficiency->is_available) {
            return false;
        }

        // 현재 진행 중인 동일 서비스 타입 작업 수 확인
        // (실제 구현에서는 task_assignments 테이블과 연동 필요)

        return true;
    }

    /**
     * 전문 서비스 타입 목록 조회
     */
    public function getExpertServiceTypes()
    {
        return $this->serviceTypes()
                   ->where('proficiency_level', 'expert')
                   ->where('is_available', true)
                   ->get()
                   ->pluck('service_type')
                   ->toArray();
    }

    /**
     * 서비스 타입별 추천 점수 계산 (자동 배정용)
     */
    public function calculateMatchingScore($serviceType, $requiredTier = 'bronze')
    {
        // 기본 점수
        $score = 0;

        // 1. 서비스 타입 제공 가능 여부 (0점 또는 기본 점수)
        if (!$this->canProvideServiceType($serviceType)) {
            return 0; // 제공 불가능하면 0점
        }
        $score += 20;

        // 2. 등급 점수 (bronze: 10, silver: 20, gold: 30, platinum: 40)
        $tierScores = ['bronze' => 10, 'silver' => 20, 'gold' => 30, 'platinum' => 40];
        $score += $tierScores[$this->current_tier] ?? 0;

        // 3. 서비스 타입별 숙련도 점수 (0-25점)
        $proficiency = $this->getServiceTypeProficiency($serviceType);
        if ($proficiency) {
            $proficiencyScores = ['beginner' => 5, 'intermediate' => 10, 'advanced' => 20, 'expert' => 25];
            $score += $proficiencyScores[$proficiency->proficiency_level] ?? 0;
        }

        // 4. 평점 점수 (0-20점)
        $score += ($this->average_rating / 5) * 20;

        // 5. 경험 점수 (0-15점)
        if ($proficiency) {
            $score += min($proficiency->experience_years * 3, 15);
        }

        // 6. 성공률 점수 (0-10점)
        if ($proficiency) {
            $score += ($proficiency->success_rate / 100) * 10;
        }

        // 7. 활동성 점수 (0-10점)
        $score += min($this->current_month_jobs * 2, 10);

        return round($score, 2);
    }
}