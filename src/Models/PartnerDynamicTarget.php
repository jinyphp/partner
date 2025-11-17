<?php

namespace Jiny\Partner\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class PartnerDynamicTarget extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        // 대상 파트너
        'partner_user_id',

        // 목표 기간
        'target_period_type',
        'target_year',
        'target_month',
        'target_quarter',

        // 기본 목표
        'base_sales_target',
        'base_cases_target',
        'base_revenue_target',
        'base_clients_target',

        // 조정 계수
        'personal_adjustment_factor',
        'market_condition_factor',
        'seasonal_adjustment_factor',
        'team_performance_factor',

        // 최종 목표
        'final_sales_target',
        'final_cases_target',
        'final_revenue_target',
        'final_clients_target',

        // 추가 목표
        'quality_score_target',
        'customer_satisfaction_target',
        'response_time_target',

        // 현재 성과
        'current_sales_achievement',
        'current_cases_achievement',
        'current_revenue_achievement',
        'current_clients_achievement',

        // 달성률
        'sales_achievement_rate',
        'cases_achievement_rate',
        'overall_achievement_rate',

        // 보너스
        'bonus_tier_config',
        'achieved_bonus_rate',
        'calculated_bonus_amount',

        // JSON 필드
        'special_objectives',
        'achievement_milestones',

        // 상태 및 메모
        'status',
        'setting_notes',
        'approval_notes',
        'completion_notes',

        // 승인 정보
        'created_by',
        'approved_by',
        'approved_at',
        'activated_at',
        'completed_at',

        // 자동 계산
        'auto_calculate_enabled',
        'last_calculated_at',
        'next_review_date'
    ];

    protected $casts = [
        'target_month' => 'integer',
        'target_quarter' => 'integer',
        'target_year' => 'integer',

        // 숫자 필드
        'base_sales_target' => 'decimal:2',
        'base_cases_target' => 'integer',
        'base_revenue_target' => 'decimal:2',
        'base_clients_target' => 'integer',

        // 조정 계수
        'personal_adjustment_factor' => 'decimal:2',
        'market_condition_factor' => 'decimal:2',
        'seasonal_adjustment_factor' => 'decimal:2',
        'team_performance_factor' => 'decimal:2',

        // 최종 목표
        'final_sales_target' => 'decimal:2',
        'final_cases_target' => 'integer',
        'final_revenue_target' => 'decimal:2',
        'final_clients_target' => 'integer',

        // 추가 목표
        'quality_score_target' => 'decimal:2',
        'customer_satisfaction_target' => 'decimal:2',
        'response_time_target' => 'decimal:2',

        // 현재 성과
        'current_sales_achievement' => 'decimal:2',
        'current_cases_achievement' => 'integer',
        'current_revenue_achievement' => 'decimal:2',
        'current_clients_achievement' => 'integer',

        // 달성률
        'sales_achievement_rate' => 'decimal:2',
        'cases_achievement_rate' => 'decimal:2',
        'overall_achievement_rate' => 'decimal:2',

        // 보너스
        'achieved_bonus_rate' => 'decimal:2',
        'calculated_bonus_amount' => 'decimal:2',

        // JSON 필드
        'bonus_tier_config' => 'json',
        'special_objectives' => 'json',
        'achievement_milestones' => 'json',

        // 날짜 필드
        'approved_at' => 'datetime',
        'activated_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_calculated_at' => 'datetime',
        'next_review_date' => 'date',

        // 불린 필드
        'auto_calculate_enabled' => 'boolean'
    ];

    /**
     * 파트너 사용자 관계
     */
    public function partnerUser()
    {
        return $this->belongsTo(PartnerUser::class, 'partner_user_id');
    }

    /**
     * 생성자 관계
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 승인자 관계
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * 스코프: 활성 상태
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * 스코프: 승인된 상태
     */
    public function scopeApproved($query)
    {
        return $query->whereIn('status', ['approved', 'active', 'completed']);
    }

    /**
     * 스코프: 월별 목표
     */
    public function scopeMonthly($query)
    {
        return $query->where('target_period_type', 'monthly');
    }

    /**
     * 스코프: 분기별 목표
     */
    public function scopeQuarterly($query)
    {
        return $query->where('target_period_type', 'quarterly');
    }

    /**
     * 스코프: 연별 목표
     */
    public function scopeYearly($query)
    {
        return $query->where('target_period_type', 'yearly');
    }

    /**
     * 스코프: 특정 기간
     */
    public function scopeForPeriod($query, $year, $month = null, $quarter = null)
    {
        $query->where('target_year', $year);

        if ($month) {
            $query->where('target_month', $month);
        }

        if ($quarter) {
            $query->where('target_quarter', $quarter);
        }

        return $query;
    }

    /**
     * 전체 달성률 계산 (읽기 전용 속성)
     */
    public function getOverallAchievementRateAttribute($value)
    {
        if ($value !== null) {
            return $value;
        }

        // 실시간 계산 (가중평균)
        $salesWeight = 0.7;
        $casesWeight = 0.3;

        return ($this->sales_achievement_rate * $salesWeight) + ($this->cases_achievement_rate * $casesWeight);
    }

    /**
     * 기간 표시 문자열
     */
    public function getPeriodDisplayAttribute()
    {
        switch ($this->target_period_type) {
            case 'monthly':
                return "{$this->target_year}년 {$this->target_month}월";
            case 'quarterly':
                return "{$this->target_year}년 {$this->target_quarter}분기";
            case 'yearly':
                return "{$this->target_year}년";
            default:
                return '';
        }
    }

    /**
     * 상태 표시 문자열
     */
    public function getStatusDisplayAttribute()
    {
        $statuses = [
            'draft' => '초안',
            'pending_approval' => '승인대기',
            'approved' => '승인완료',
            'active' => '활성',
            'completed' => '완료',
            'cancelled' => '취소'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * 목표 달성 여부
     */
    public function getIsAchievedAttribute()
    {
        return $this->overall_achievement_rate >= 100;
    }

    /**
     * 보너스 지급 대상 여부
     */
    public function getIsBonusEligibleAttribute()
    {
        if (!$this->bonus_tier_config) {
            return false;
        }

        $config = is_string($this->bonus_tier_config)
            ? json_decode($this->bonus_tier_config, true)
            : $this->bonus_tier_config;

        if (!$config) {
            return false;
        }

        foreach ($config as $threshold => $bonusConfig) {
            if ($this->overall_achievement_rate >= $threshold) {
                return true;
            }
        }

        return false;
    }

    /**
     * 자동 성과 업데이트 (배치 처리용)
     */
    public function updatePerformanceFromSales()
    {
        if (!$this->auto_calculate_enabled) {
            return false;
        }

        // 실제 매출/건수 데이터는 별도 테이블에서 집계
        // 여기서는 예시 로직
        $salesData = $this->calculateCurrentSales();
        $casesData = $this->calculateCurrentCases();

        $this->update([
            'current_sales_achievement' => $salesData['sales'],
            'current_cases_achievement' => $casesData['cases'],
            'current_revenue_achievement' => $salesData['revenue'],
            'current_clients_achievement' => $casesData['clients'],
            'sales_achievement_rate' => $this->final_sales_target > 0
                ? ($salesData['sales'] / $this->final_sales_target) * 100 : 0,
            'cases_achievement_rate' => $this->final_cases_target > 0
                ? ($casesData['cases'] / $this->final_cases_target) * 100 : 0,
            'last_calculated_at' => now()
        ]);

        return true;
    }

    /**
     * 현재 매출 계산 (실제 구현에서는 관련 테이블 조회)
     */
    private function calculateCurrentSales()
    {
        // 실제 구현에서는 partner_sales 테이블 등에서 데이터 집계
        return [
            'sales' => 0,
            'revenue' => 0
        ];
    }

    /**
     * 현재 처리건수 계산 (실제 구현에서는 관련 테이블 조회)
     */
    private function calculateCurrentCases()
    {
        // 실제 구현에서는 partner_cases 테이블 등에서 데이터 집계
        return [
            'cases' => 0,
            'clients' => 0
        ];
    }

    /**
     * 다음 검토일 자동 설정
     */
    public function setNextReviewDate()
    {
        $nextReview = match($this->target_period_type) {
            'monthly' => now()->addMonth(),
            'quarterly' => now()->addMonths(3),
            'yearly' => now()->addYear(),
            default => now()->addMonth()
        };

        $this->update(['next_review_date' => $nextReview->toDateString()]);
    }
}