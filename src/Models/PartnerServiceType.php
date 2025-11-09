<?php

namespace Jiny\Partner\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerServiceType extends Model
{
    use HasFactory;

    protected $table = 'partner_service_types';

    protected $fillable = [
        'partner_engineer_id',
        'service_type',
        'proficiency_level',
        'experience_years',
        'completed_jobs',
        'average_rating',
        'certifications',
        'equipment_owned',
        'hourly_rate',
        'minimum_charge',
        'is_available',
        'max_concurrent_jobs',
        'last_job_date',
        'success_rate'
    ];

    protected $casts = [
        'experience_years' => 'integer',
        'completed_jobs' => 'integer',
        'average_rating' => 'decimal:2',
        'certifications' => 'array',
        'equipment_owned' => 'array',
        'hourly_rate' => 'decimal:2',
        'minimum_charge' => 'decimal:2',
        'is_available' => 'boolean',
        'max_concurrent_jobs' => 'integer',
        'last_job_date' => 'datetime',
        'success_rate' => 'decimal:2'
    ];

    /**
     * 연결된 파트너 엔지니어
     */
    public function partnerEngineer()
    {
        return $this->belongsTo(PartnerEngineer::class);
    }

    /**
     * 가용한 서비스 타입만 조회
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * 숙련도별 조회
     */
    public function scopeByProficiency($query, $level)
    {
        return $query->where('proficiency_level', $level);
    }

    /**
     * 최소 숙련도 이상 조회
     */
    public function scopeMinProficiency($query, $level)
    {
        $levels = ['beginner', 'intermediate', 'advanced', 'expert'];
        $minIndex = array_search($level, $levels);
        $allowedLevels = array_slice($levels, $minIndex);

        return $query->whereIn('proficiency_level', $allowedLevels);
    }

    /**
     * 평점 기준 조회
     */
    public function scopeHighRated($query, $minRating = 4.0)
    {
        return $query->where('average_rating', '>=', $minRating);
    }

    /**
     * 서비스 타입별 조회
     */
    public function scopeByType($query, $serviceType)
    {
        return $query->where('service_type', $serviceType);
    }

    /**
     * 경험 년수 기준 조회
     */
    public function scopeExperienced($query, $minYears = 1)
    {
        return $query->where('experience_years', '>=', $minYears);
    }

    /**
     * 숙련도 점수 계산
     */
    public function getProficiencyScore()
    {
        $proficiencyScores = [
            'beginner' => 25,
            'intermediate' => 50,
            'advanced' => 75,
            'expert' => 100
        ];

        $baseScore = $proficiencyScores[$this->proficiency_level] ?? 0;
        $experienceBonus = min($this->experience_years * 5, 25); // 최대 25점
        $ratingBonus = ($this->average_rating / 5) * 20; // 최대 20점
        $successBonus = ($this->success_rate / 100) * 10; // 최대 10점

        return round($baseScore + $experienceBonus + $ratingBonus + $successBonus, 2);
    }

    /**
     * 해당 서비스 타입에서 전문가 여부
     */
    public function getIsExpertAttribute()
    {
        return $this->proficiency_level === 'expert'
            && $this->experience_years >= 3
            && $this->average_rating >= 4.5
            && $this->completed_jobs >= 50;
    }

    /**
     * 자격증 보유 여부 확인
     */
    public function hasCertification($certificationName)
    {
        if (!$this->certifications) {
            return false;
        }

        foreach ($this->certifications as $cert) {
            if (isset($cert['name']) && str_contains($cert['name'], $certificationName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 특정 장비 보유 여부 확인
     */
    public function hasEquipment($equipmentName)
    {
        if (!$this->equipment_owned) {
            return false;
        }

        foreach ($this->equipment_owned as $equipment) {
            if (isset($equipment['name']) && str_contains($equipment['name'], $equipmentName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 작업 완료 후 성과 업데이트
     */
    public function updatePerformance($rating, $wasSuccessful = true)
    {
        $this->increment('completed_jobs');

        // 평균 평점 업데이트
        $totalJobs = $this->completed_jobs;
        $currentTotal = $this->average_rating * ($totalJobs - 1);
        $this->average_rating = ($currentTotal + $rating) / $totalJobs;

        // 성공률 업데이트
        if ($wasSuccessful) {
            $successCount = round($this->success_rate * ($totalJobs - 1) / 100) + 1;
            $this->success_rate = ($successCount / $totalJobs) * 100;
        } else {
            $successCount = round($this->success_rate * ($totalJobs - 1) / 100);
            $this->success_rate = ($successCount / $totalJobs) * 100;
        }

        $this->last_job_date = now();
        $this->save();
    }

    /**
     * 서비스 타입별 추천 파트너 찾기
     */
    public static function findBestMatches($serviceType, $requiredTier = 'bronze', $limit = 5)
    {
        return self::byType($serviceType)
                  ->available()
                  ->whereHas('partnerEngineer', function($query) use ($requiredTier) {
                      $query->active()
                            ->where(function($q) use ($requiredTier) {
                                $tierLevels = ['bronze' => 4, 'silver' => 3, 'gold' => 2, 'platinum' => 1];
                                $requiredLevel = $tierLevels[$requiredTier] ?? 4;

                                $q->whereHas('tier', function($tierQuery) use ($requiredLevel) {
                                    $tierQuery->where('priority_level', '<=', $requiredLevel);
                                });
                            });
                  })
                  ->orderByRaw('
                      (CASE proficiency_level
                          WHEN "expert" THEN 4
                          WHEN "advanced" THEN 3
                          WHEN "intermediate" THEN 2
                          ELSE 1 END) * 25 +
                      average_rating * 15 +
                      LEAST(experience_years * 5, 25) +
                      success_rate * 0.2 DESC
                  ')
                  ->limit($limit)
                  ->get();
    }
}