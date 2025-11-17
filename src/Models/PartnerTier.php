<?php

namespace Jiny\Partner\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * =======================================================================
 * 🏆 파트너 등급(Tier) 시스템 모델 (리팩터링 v3.0)
 * =======================================================================
 *
 * 📖 모델 개요
 * -----------------------------------------------------------------------
 * 파트너의 성과와 경험에 따른 6단계 등급 분류 및 관리를 담당하는 핵심 모델입니다.
 * Starter → Bronze → Silver → Gold → Platinum → Diamond 단계별 승급 체계를 지원하며,
 * 등급별 차등 수수료, 혜택, 요구사항, 비용 등을 포괄적으로 관리합니다.
 *
 * 💰 등급별 수수료 구조
 * -----------------------------------------------------------------------
 * • Starter(스타터)   : 3% 수수료 + 무료 (신규 입문)
 * • Bronze(브론즈)    : 5% 수수료 + 무료 (기초)
 * • Silver(실버)      : 6% 수수료 + 유료 (중급)
 * • Gold(골드)        : 7% 수수료 + 유료 (고급)
 * • Platinum(플래)    : 8% 수수료 + 유료 (프리미엄)
 * • Diamond(다이아)   : 10% 수수료 + 유료 (최상급)
 *
 * 📝 수수료 계산 정책
 * -----------------------------------------------------------------------
 * • 총 수수료 = 파트너 타입 수수료 + 파트너 등급 수수료
 * • 실제 데이터베이스 값 기반 순수 합산 (코드 상한선 없음)
 * • 현재 샘플 데이터: 타입 최대 ~10%, 등급 최대 10% = 합산 최대 ~20%
 *
 * 🔗 주요 관계
 * -----------------------------------------------------------------------
 * • PartnerType (다대일) - 상위 파트너 타입과의 연동
 * • PartnerUser (일대다) - 해당 등급에 속한 파트너 유저들
 * • CommissionLog (일대다) - 등급별 수수료 기록
 *
 * @property int $id 등급 고유 식별자
 * @property string $tier_code 등급 고유 코드 (starter, bronze, silver 등)
 * @property string $tier_name 등급 표시명 (브론즈 파트너, 실버 파트너 등)
 * @property string|null $description 등급 상세 설명
 * @property string $commission_type 수수료 타입 (percentage|fixed_amount)
 * @property float|null $commission_rate 수수료율 (%)
 * @property float|null $commission_amount 고정 수수료 금액
 * @property int $priority_level 우선순위 (1=최고)
 * @property int|null $parent_partner_type_id 연동 파트너 타입 ID
 * @property bool $restrict_to_parent_type 타입 제한 여부
 * @property array $requirements 등급 달성 요구사항 (JSON)
 * @property array $benefits 등급별 혜택 (JSON)
 * @property float $registration_fee 가입비
 * @property float $monthly_fee 월 유지비
 * @property float $annual_fee 연 유지비
 * @property bool $fee_waiver_available 비용 면제 가능 여부
 * @property string|null $fee_structure_notes 비용 구조 특별 조건
 * @property bool $is_active 활성 상태
 * @property int $sort_order 정렬 순서
 * @property \Carbon\Carbon $created_at 생성일시
 * @property \Carbon\Carbon $updated_at 수정일시
 * @property \Carbon\Carbon|null $deleted_at 삭제일시
 */
class PartnerTier extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * 팩토리 인스턴스 생성
     */
    protected static function newFactory()
    {
        return \Database\Factories\PartnerTierFactory::new();
    }

    /**
     * 테이블명 지정
     */
    protected $table = 'partner_tiers';

    /**
     * 대량 할당 가능한 필드들
     * 마이그레이션 구조와 정확히 일치하도록 구성
     */
    protected $fillable = [
        'tier_code',                    // 등급 고유 코드
        'tier_name',                    // 등급 표시명
        'description',                  // 등급 설명
        'commission_type',              // 수수료 타입
        'commission_rate',              // 수수료율
        'commission_amount',            // 고정 수수료 금액
        'priority_level',               // 우선순위 레벨
        'parent_partner_type_id',       // 연동 파트너 타입 ID
        'restrict_to_parent_type',      // 타입 제한 여부
        'requirements',                 // 등급 요구사항 (JSON)
        'benefits',                     // 등급 혜택 (JSON)
        'registration_fee',             // 가입비
        'monthly_fee',                  // 월 유지비
        'annual_fee',                   // 연 유지비
        'fee_waiver_available',         // 비용 면제 가능 여부
        'fee_structure_notes',          // 비용 구조 특별 조건
        'is_active',                    // 활성 상태
        'sort_order',                   // 정렬 순서
    ];

    /**
     * 속성 캐스팅 설정
     * 데이터베이스와 PHP 타입 간 자동 변환 처리
     */
    protected $casts = [
        'commission_rate' => 'decimal:2',          // 수수료율 소수점 2자리
        'commission_amount' => 'decimal:2',        // 수수료 금액 소수점 2자리
        'priority_level' => 'integer',             // 우선순위 정수형
        'parent_partner_type_id' => 'integer',     // 파트너 타입 ID 정수형
        'restrict_to_parent_type' => 'boolean',    // 타입 제한 불린형
        'requirements' => 'array',                 // 요구사항 배열형
        'benefits' => 'array',                     // 혜택 배열형
        'registration_fee' => 'decimal:2',         // 가입비 소수점 2자리
        'monthly_fee' => 'decimal:2',              // 월 유지비 소수점 2자리
        'annual_fee' => 'decimal:2',               // 연 유지비 소수점 2자리
        'fee_waiver_available' => 'boolean',       // 비용 면제 가능 불린형
        'is_active' => 'boolean',                  // 활성 상태 불린형
        'sort_order' => 'integer',                 // 정렬 순서 정수형
    ];

    /**
     * 기본 속성값 설정
     */
    protected $attributes = [
        'commission_type' => 'percentage',         // 기본 수수료 타입은 퍼센트
        'is_active' => true,                       // 기본적으로 활성 상태
        'restrict_to_parent_type' => false,        // 기본적으로 타입 제한 없음
        'fee_waiver_available' => false,           // 기본적으로 비용 면제 불가
        'priority_level' => 99,                    // 기본 우선순위는 낮음
        'sort_order' => 0,                         // 기본 정렬 순서
    ];

    // ====================================================================
    // 🔗 Eloquent 관계 정의
    // ====================================================================


    /**
     * 해당 등급에 속한 파트너 유저들과의 일대다 관계
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function partnerUsers()
    {
        return $this->hasMany(\Jiny\Partner\Models\PartnerUser::class, 'partner_tier_id');
    }

    /**
     * 등급별 수수료 기록과의 일대다 관계
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function commissionLogs()
    {
        return $this->hasMany(\Jiny\Partner\Models\CommissionLog::class, 'partner_tier_id');
    }

    // ====================================================================
    // 📋 Query Scope 메서드들 (데이터 조회 최적화)
    // ====================================================================

    /**
     * 활성화된 등급만 조회하는 스코프
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 우선순위 순으로 정렬하는 스코프 (낮은 숫자가 높은 우선순위)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction 정렬 방향 (asc|desc)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByPriority($query, $direction = 'asc')
    {
        return $query->orderBy('priority_level', $direction);
    }

    /**
     * 정렬 순서별로 정렬하는 스코프
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction 정렬 방향 (asc|desc)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderBySortOrder($query, $direction = 'asc')
    {
        return $query->orderBy('sort_order', $direction);
    }

    /**
     * 수수료율 순으로 정렬하는 스코프
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction 정렬 방향 (asc|desc)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByCommission($query, $direction = 'desc')
    {
        return $query->orderBy('commission_rate', $direction);
    }

    /**
     * 최소 커미션율 이상의 등급들을 조회하는 스코프
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $rate 최소 커미션율
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMinCommissionRate($query, $rate)
    {
        return $query->where('commission_rate', '>=', $rate);
    }

    /**
     * 특정 파트너 타입에 연동된 등급들을 조회하는 스코프
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $partnerTypeId 파트너 타입 ID
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForPartnerType($query, $partnerTypeId)
    {
        return $query->where('parent_partner_type_id', $partnerTypeId);
    }

    /**
     * 파트너 타입에 제한된 등급들을 조회하는 스코프
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRestrictedToParentType($query)
    {
        return $query->where('restrict_to_parent_type', true);
    }

    /**
     * 비용 면제 가능한 등급들을 조회하는 스코프
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFeeWaiverAvailable($query)
    {
        return $query->where('fee_waiver_available', true);
    }

    /**
     * 등급 코드로 조회하는 스코프
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $code 등급 코드
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCode($query, $code)
    {
        return $query->where('tier_code', $code);
    }

    /**
     * 특정 수수료 타입의 등급들을 조회하는 스코프
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type 수수료 타입 (percentage|fixed_amount)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCommissionType($query, $type)
    {
        return $query->where('commission_type', $type);
    }

    // ====================================================================
    // 💰 수수료 계산 및 관리 메서드
    // ====================================================================

    /**
     * 거래 금액에 따른 수수료를 계산
     *
     * @param float $amount 거래 금액
     * @return float 계산된 수수료
     */
    public function calculateCommission($amount)
    {
        // 금액이 0 이하인 경우 수수료 없음
        if ($amount <= 0) {
            return 0;
        }

        // 고정 금액 방식인 경우
        if ($this->commission_type === 'fixed_amount') {
            return $this->commission_amount ?? 0;
        }

        // 퍼센트 방식인 경우
        if ($this->commission_type === 'percentage') {
            $rate = $this->commission_rate ?? 0;
            return ($amount * $rate) / 100;
        }

        return 0;
    }

    /**
     * 파트너 타입과 합산된 총 수수료율을 계산 (실제 데이터 기반 합산)
     *
     * @param \Jiny\Partner\Models\PartnerType|null $partnerType 파트너 타입
     * @return float 총 수수료율 (타입 수수료율 + 등급 수수료율)
     */
    public function calculateTotalCommissionRate($partnerType = null)
    {
        $tierRate = $this->commission_rate ?? 0;

        // 파트너 타입이 없거나 퍼센트 방식이 아닌 경우 등급 수수료율만 반환
        if (!$partnerType || $partnerType->commission_type !== 'percentage') {
            return $tierRate;
        }

        $typeRate = $partnerType->commission_rate ?? 0;

        // 실제 데이터 기반 순수 합산 (상한선 제거)
        return $tierRate + $typeRate;
    }

    /**
     * 수수료율 표시용 텍스트를 반환
     *
     * @return string 수수료율 표시 텍스트
     */
    public function getCommissionDisplayText()
    {
        if ($this->commission_type === 'fixed_amount') {
            return number_format($this->commission_amount ?? 0) . '원';
        }

        return ($this->commission_rate ?? 0) . '%';
    }

    /**
     * 수수료율 유효성을 검증
     *
     * @param float|null $newRate 새로운 수수료율 (null인 경우 현재 수수료율 사용)
     * @return array 검증 결과
     */
    public function validateCommissionRate($newRate = null)
    {
        $rate = $newRate ?? $this->commission_rate;

        // 기본 범위 검증 (0% 이상 100% 이하)
        if ($this->commission_type === 'percentage' && ($rate < 0 || $rate > 100)) {
            return [
                'valid' => false,
                'message' => '수수료율은 0%에서 100% 사이여야 합니다.',
                'min_allowed' => 0,
                'max_allowed' => 100,
                'current_rate' => $rate
            ];
        }

        return [
            'valid' => true,
            'message' => '수수료율이 유효합니다.',
            'current_rate' => $rate
        ];
    }

    // ====================================================================
    // 🎯 등급 달성 및 요구사항 검증 메서드
    // ====================================================================

    /**
     * 파트너가 해당 등급을 달성할 수 있는지 확인
     *
     * @param \Jiny\Partner\Models\PartnerUser $partner 파트너 객체
     * @return bool 달성 가능 여부
     */
    public function canAchieveTier($partner)
    {
        $requirements = $this->getRequirements();

        // 온보딩 완료 확인
        if ($this->getRequirement('onboarding_completed', false)) {
            if (!$partner->onboarding_completed) {
                return false;
            }
        }

        // 최소 경험 개월 확인
        $minExperienceMonths = $this->getRequirement('min_experience_months');
        if ($minExperienceMonths > 0) {
            $partnerExperience = $partner->getExperienceMonths();
            if ($partnerExperience < $minExperienceMonths) {
                return false;
            }
        }

        // 최소 완료 업무 수 확인
        $minCompletedJobs = $this->getRequirement('min_completed_jobs');
        if ($minCompletedJobs > 0) {
            if ($partner->completed_jobs_count < $minCompletedJobs) {
                return false;
            }
        }

        // 최소 평점 확인
        $minRating = $this->getRequirement('min_rating');
        if ($minRating > 0) {
            if ($partner->average_rating < $minRating) {
                return false;
            }
        }

        // 리더십 경험 확인
        if ($this->getRequirement('leadership_experience', false)) {
            if (!$partner->has_leadership_experience) {
                return false;
            }
        }

        // 전문화 분야 확인
        if ($this->getRequirement('expert_specialization', false)) {
            if (!$partner->has_expert_specialization) {
                return false;
            }
        }

        return true;
    }

    /**
     * 등급 달성을 위해 부족한 요구사항들을 반환
     *
     * @param \Jiny\Partner\Models\PartnerUser $partner 파트너 객체
     * @return array 부족한 요구사항 목록
     */
    public function getMissingRequirements($partner)
    {
        $missing = [];
        $requirements = $this->getRequirements();

        // 온보딩 미완료
        if ($this->getRequirement('onboarding_completed', false) && !$partner->onboarding_completed) {
            $missing[] = '온보딩 과정 완료 필요';
        }

        // 경험 부족
        $minExperienceMonths = $this->getRequirement('min_experience_months');
        if ($minExperienceMonths > 0) {
            $currentExperience = $partner->getExperienceMonths();
            if ($currentExperience < $minExperienceMonths) {
                $needed = $minExperienceMonths - $currentExperience;
                $missing[] = "추가 경험 {$needed}개월 필요";
            }
        }

        // 완료 업무 수 부족
        $minCompletedJobs = $this->getRequirement('min_completed_jobs');
        if ($minCompletedJobs > 0) {
            if ($partner->completed_jobs_count < $minCompletedJobs) {
                $needed = $minCompletedJobs - $partner->completed_jobs_count;
                $missing[] = "추가 완료 업무 {$needed}건 필요";
            }
        }

        // 평점 부족
        $minRating = $this->getRequirement('min_rating');
        if ($minRating > 0) {
            if ($partner->average_rating < $minRating) {
                $needed = $minRating - $partner->average_rating;
                $missing[] = "평점 {$needed}점 상승 필요";
            }
        }

        // 리더십 경험 부족
        if ($this->getRequirement('leadership_experience', false) && !$partner->has_leadership_experience) {
            $missing[] = '리더십 경험 필요';
        }

        // 전문화 분야 부족
        if ($this->getRequirement('expert_specialization', false) && !$partner->has_expert_specialization) {
            $missing[] = '전문 분야 인증 필요';
        }

        return $missing;
    }

    // ====================================================================
    // 🎁 혜택 및 비용 관리 메서드
    // ====================================================================

    /**
     * 등급별 혜택 정보를 반환
     *
     * @return array 혜택 배열
     */
    public function getBenefits()
    {
        return $this->benefits ?? [];
    }

    /**
     * 등급별 요구사항 정보를 반환
     *
     * @return array 요구사항 배열
     */
    public function getRequirements()
    {
        return $this->requirements ?? [];
    }

    /**
     * 특정 혜택 값을 조회
     *
     * @param string $key 혜택 키
     * @param mixed $default 기본값
     * @return mixed 혜택 값
     */
    public function getBenefit($key, $default = null)
    {
        $benefits = $this->getBenefits();
        return $benefits[$key] ?? $default;
    }

    /**
     * 특정 요구사항 값을 조회
     *
     * @param string $key 요구사항 키
     * @param mixed $default 기본값
     * @return mixed 요구사항 값
     */
    public function getRequirement($key, $default = null)
    {
        $requirements = $this->getRequirements();
        return $requirements[$key] ?? $default;
    }

    /**
     * 최대 동시 진행 가능한 업무 수를 반환
     *
     * @return int 최대 동시 업무 수
     */
    public function getMaxConcurrentJobs()
    {
        return (int) $this->getBenefit('maximum_concurrent_jobs', 1);
    }

    /**
     * 지원팀 응답 시간을 반환
     *
     * @return string 응답 시간
     */
    public function getSupportResponseTime()
    {
        return $this->getBenefit('support_response_time', '24시간');
    }

    /**
     * 보너스 지급 대상 여부를 확인
     *
     * @return bool 보너스 지급 대상 여부
     */
    public function isBonusEligible()
    {
        return (bool) $this->getBenefit('bonus_eligibility', false);
    }

    /**
     * 프리미엄 프로젝트 접근 가능 여부를 확인
     *
     * @return bool 프리미엄 프로젝트 접근 가능 여부
     */
    public function hasPremiumProjectsAccess()
    {
        return (bool) $this->getBenefit('premium_projects_access', false);
    }

    /**
     * VIP 고객 접근 가능 여부를 확인
     *
     * @return bool VIP 고객 접근 가능 여부
     */
    public function hasVipCustomerAccess()
    {
        return (bool) $this->getBenefit('vip_customer_access', false);
    }

    /**
     * 독점 프로젝트 접근 가능 여부를 확인
     *
     * @return bool 독점 프로젝트 접근 가능 여부
     */
    public function hasExclusiveProjectsAccess()
    {
        return (bool) $this->getBenefit('exclusive_projects_access', false);
    }

    // ====================================================================
    // 💳 비용 관리 메서드
    // ====================================================================

    /**
     * 비용 면제 가능 여부를 확인
     *
     * @return bool 비용 면제 가능 여부
     */
    public function isFeeWaiverAvailable()
    {
        return $this->fee_waiver_available ?? false;
    }

    /**
     * 총 월간 비용을 계산 (가입비는 월할 계산)
     *
     * @return float 총 월간 비용
     */
    public function getTotalMonthlyCost()
    {
        $registrationMonthly = ($this->registration_fee ?? 0) / 12; // 가입비 월할
        $monthlyFee = $this->monthly_fee ?? 0;

        return $registrationMonthly + $monthlyFee;
    }

    /**
     * 총 연간 비용을 계산
     *
     * @return float 총 연간 비용
     */
    public function getTotalAnnualCost()
    {
        $registrationFee = $this->registration_fee ?? 0;
        $annualFee = $this->annual_fee ?? 0;

        return $registrationFee + $annualFee;
    }

    /**
     * 첫 해 총 비용을 계산 (가입비 + 연회비)
     *
     * @return float 첫 해 총 비용
     */
    public function getFirstYearCost()
    {
        return $this->getTotalAnnualCost();
    }

    /**
     * 비용 구조 요약 정보를 반환
     *
     * @return array 비용 구조 배열
     */
    public function getCostStructure()
    {
        return [
            'registration_fee' => $this->registration_fee ?? 0,
            'monthly_fee' => $this->monthly_fee ?? 0,
            'annual_fee' => $this->annual_fee ?? 0,
            'total_monthly' => $this->getTotalMonthlyCost(),
            'total_annual' => $this->getTotalAnnualCost(),
            'first_year_cost' => $this->getFirstYearCost(),
            'fee_waiver_available' => $this->isFeeWaiverAvailable(),
            'notes' => $this->fee_structure_notes,
        ];
    }

    // ====================================================================
    // 🔗 파트너 타입 연동 관리 메서드
    // ====================================================================

    /**
     * 파트너 타입에 제한되어 있는지 확인
     *
     * @return bool 타입 제한 여부
     */
    public function isRestrictedToParentType()
    {
        return $this->restrict_to_parent_type ?? false;
    }


    /**
     * 연동된 파트너 타입의 개별 고정 수수료 금액을 반환
     *
     * @return float 파트너 타입 고정 수수료 금액
     */
    public function getParentTypeCommissionAmount()
    {
        if (!$this->parentPartnerType) {
            return 0;
        }

        if ($this->parentPartnerType->commission_type !== 'fixed_amount') {
            return 0;
        }

        return $this->parentPartnerType->commission_amount ?? 0;
    }

    /**
     * 연동된 파트너 타입의 개별 가입비를 반환
     *
     * @return float 파트너 타입 가입비
     */
    public function getParentTypeRegistrationFee()
    {
        if (!$this->parentPartnerType) {
            return 0;
        }

        return $this->parentPartnerType->registration_fee ?? 0;
    }

    /**
     * 연동된 파트너 타입의 개별 월 유지비를 반환
     *
     * @return float 파트너 타입 월 유지비
     */
    public function getParentTypeMonthlyFee()
    {
        if (!$this->parentPartnerType) {
            return 0;
        }

        return $this->parentPartnerType->monthly_fee ?? 0;
    }

    /**
     * 연동된 파트너 타입의 개별 연 유지비를 반환
     *
     * @return float 파트너 타입 연 유지비
     */
    public function getParentTypeAnnualFee()
    {
        if (!$this->parentPartnerType) {
            return 0;
        }

        return $this->parentPartnerType->annual_fee ?? 0;
    }

    /**
     * 연동된 파트너 타입의 개별 비용 구조를 반환
     *
     * @return array 파트너 타입 비용 구조
     */
    public function getParentTypeCostStructure()
    {
        if (!$this->parentPartnerType) {
            return [
                'commission_type' => null,
                'commission_rate' => 0,
                'commission_amount' => 0,
                'registration_fee' => 0,
                'monthly_fee' => 0,
                'annual_fee' => 0,
                'type_name' => null,
                'type_code' => null,
            ];
        }

        return [
            'commission_type' => $this->parentPartnerType->commission_type,
            'commission_rate' => $this->getParentTypeCommissionRate(),
            'commission_amount' => $this->getParentTypeCommissionAmount(),
            'registration_fee' => $this->getParentTypeRegistrationFee(),
            'monthly_fee' => $this->getParentTypeMonthlyFee(),
            'annual_fee' => $this->getParentTypeAnnualFee(),
            'type_name' => $this->parentPartnerType->type_name,
            'type_code' => $this->parentPartnerType->type_code,
        ];
    }

    // ====================================================================
    // 🔄 합산 비용 계산 메서드 (PartnerType + PartnerTier)
    // ====================================================================

    /**
     * 파트너 타입과 등급의 합산 수수료율을 계산 (실제 데이터 기반 합산)
     * 이미 구현된 calculateTotalCommissionRate()와 동일하지만 명확성을 위해 별칭 추가
     *
     * @param \Jiny\Partner\Models\PartnerType|null $partnerType 파트너 타입 (null인 경우 연동된 타입 사용)
     * @return float 합산 수수료율 (타입 + 등급 수수료율 순수 합산)
     */
    public function getCombinedCommissionRate($partnerType = null)
    {
        $partnerType = $partnerType ?? $this->parentPartnerType;
        return $this->calculateTotalCommissionRate($partnerType);
    }

    /**
     * 파트너 타입과 등급의 합산 고정 수수료 금액을 계산
     * 고정 금액 방식인 경우에만 적용
     *
     * @param \Jiny\Partner\Models\PartnerType|null $partnerType 파트너 타입 (null인 경우 연동된 타입 사용)
     * @return float 합산 고정 수수료 금액
     */
    public function getCombinedCommissionAmount($partnerType = null)
    {
        $partnerType = $partnerType ?? $this->parentPartnerType;

        $tierAmount = ($this->commission_type === 'fixed_amount') ? ($this->commission_amount ?? 0) : 0;
        $typeAmount = 0;

        if ($partnerType && $partnerType->commission_type === 'fixed_amount') {
            $typeAmount = $partnerType->commission_amount ?? 0;
        }

        return $tierAmount + $typeAmount;
    }

    /**
     * 파트너 타입과 등급의 합산 가입비를 계산
     *
     * @param \Jiny\Partner\Models\PartnerType|null $partnerType 파트너 타입 (null인 경우 연동된 타입 사용)
     * @return float 합산 가입비
     */
    public function getCombinedRegistrationFee($partnerType = null)
    {
        $partnerType = $partnerType ?? $this->parentPartnerType;

        $tierFee = $this->registration_fee ?? 0;
        $typeFee = $partnerType ? ($partnerType->registration_fee ?? 0) : 0;

        return $tierFee + $typeFee;
    }

    /**
     * 파트너 타입과 등급의 합산 월 유지비를 계산
     *
     * @param \Jiny\Partner\Models\PartnerType|null $partnerType 파트너 타입 (null인 경우 연동된 타입 사용)
     * @return float 합산 월 유지비
     */
    public function getCombinedMonthlyFee($partnerType = null)
    {
        $partnerType = $partnerType ?? $this->parentPartnerType;

        $tierFee = $this->monthly_fee ?? 0;
        $typeFee = $partnerType ? ($partnerType->monthly_fee ?? 0) : 0;

        return $tierFee + $typeFee;
    }

    /**
     * 파트너 타입과 등급의 합산 연 유지비를 계산
     *
     * @param \Jiny\Partner\Models\PartnerType|null $partnerType 파트너 타입 (null인 경우 연동된 타입 사용)
     * @return float 합산 연 유지비
     */
    public function getCombinedAnnualFee($partnerType = null)
    {
        $partnerType = $partnerType ?? $this->parentPartnerType;

        $tierFee = $this->annual_fee ?? 0;
        $typeFee = $partnerType ? ($partnerType->annual_fee ?? 0) : 0;

        return $tierFee + $typeFee;
    }

    /**
     * 파트너 타입과 등급의 합산 총 월간 비용을 계산 (가입비 월할 포함)
     *
     * @param \Jiny\Partner\Models\PartnerType|null $partnerType 파트너 타입 (null인 경우 연동된 타입 사용)
     * @return float 합산 총 월간 비용
     */
    public function getCombinedTotalMonthlyCost($partnerType = null)
    {
        $combinedRegistrationFee = $this->getCombinedRegistrationFee($partnerType);
        $combinedMonthlyFee = $this->getCombinedMonthlyFee($partnerType);

        return ($combinedRegistrationFee / 12) + $combinedMonthlyFee;
    }

    /**
     * 파트너 타입과 등급의 합산 총 연간 비용을 계산
     *
     * @param \Jiny\Partner\Models\PartnerType|null $partnerType 파트너 타입 (null인 경우 연동된 타입 사용)
     * @return float 합산 총 연간 비용
     */
    public function getCombinedTotalAnnualCost($partnerType = null)
    {
        return $this->getCombinedRegistrationFee($partnerType) + $this->getCombinedAnnualFee($partnerType);
    }

    /**
     * 파트너 타입과 등급의 합산 첫 해 총 비용을 계산
     *
     * @param \Jiny\Partner\Models\PartnerType|null $partnerType 파트너 타입 (null인 경우 연동된 타입 사용)
     * @return float 합산 첫 해 총 비용
     */
    public function getCombinedFirstYearCost($partnerType = null)
    {
        return $this->getCombinedTotalAnnualCost($partnerType);
    }

    // ====================================================================
    // 📊 포괄적인 비용 구조 요약 메서드
    // ====================================================================

    /**
     * 파트너 타입과 등급의 포괄적인 비용 구조를 반환
     * 개별 비용과 합산 비용을 모두 포함
     *
     * @param \Jiny\Partner\Models\PartnerType|null $partnerType 파트너 타입 (null인 경우 연동된 타입 사용)
     * @return array 포괄적인 비용 구조
     */
    public function getComprehensiveCostStructure($partnerType = null)
    {
        $partnerType = $partnerType ?? $this->parentPartnerType;

        return [
            // 등급별 개별 비용
            'tier' => [
                'tier_code' => $this->tier_code,
                'tier_name' => $this->tier_name,
                'commission_type' => $this->commission_type,
                'commission_rate' => $this->commission_rate ?? 0,
                'commission_amount' => $this->commission_amount ?? 0,
                'registration_fee' => $this->registration_fee ?? 0,
                'monthly_fee' => $this->monthly_fee ?? 0,
                'annual_fee' => $this->annual_fee ?? 0,
                'fee_waiver_available' => $this->isFeeWaiverAvailable(),
            ],

            // 파트너 타입별 개별 비용
            'partner_type' => $this->getParentTypeCostStructure(),

            // 합산 비용
            'combined' => [
                'commission_rate' => $this->getCombinedCommissionRate($partnerType),
                'commission_amount' => $this->getCombinedCommissionAmount($partnerType),
                'registration_fee' => $this->getCombinedRegistrationFee($partnerType),
                'monthly_fee' => $this->getCombinedMonthlyFee($partnerType),
                'annual_fee' => $this->getCombinedAnnualFee($partnerType),
                'total_monthly_cost' => $this->getCombinedTotalMonthlyCost($partnerType),
                'total_annual_cost' => $this->getCombinedTotalAnnualCost($partnerType),
                'first_year_cost' => $this->getCombinedFirstYearCost($partnerType),
            ],

            // 비용 절감 정보
            'savings' => [
                'tier_fee_waiver' => $this->isFeeWaiverAvailable(),
                'type_fee_waiver' => $partnerType ? ($partnerType->fee_waiver_available ?? false) : false,
                'any_fee_waiver_available' => $this->isFeeWaiverAvailable() || ($partnerType ? ($partnerType->fee_waiver_available ?? false) : false),
            ],

            // 추가 메타데이터
            'metadata' => [
                'combined_commission_rate' => $this->getCombinedCommissionRate($partnerType),
                'has_parent_type' => !is_null($partnerType),
                'is_restricted_to_parent_type' => $this->isRestrictedToParentType(),
                'fee_structure_notes' => $this->fee_structure_notes,
                'calculated_at' => now()->toISOString(),
            ]
        ];
    }

    /**
     * 특정 거래 금액에 대한 수수료 계산 상세 정보를 반환
     *
     * @param float $transactionAmount 거래 금액
     * @param \Jiny\Partner\Models\PartnerType|null $partnerType 파트너 타입 (null인 경우 연동된 타입 사용)
     * @return array 수수료 계산 상세 정보
     */
    public function calculateDetailedCommission($transactionAmount, $partnerType = null)
    {
        if ($transactionAmount <= 0) {
            return [
                'transaction_amount' => 0,
                'tier_commission' => 0,
                'type_commission' => 0,
                'total_commission' => 0,
                'effective_rate' => 0,
                'cap_applied' => false,
                'calculation_method' => 'zero_amount'
            ];
        }

        $partnerType = $partnerType ?? $this->parentPartnerType;

        // 등급 수수료 계산
        $tierCommission = $this->calculateCommission($transactionAmount);

        // 타입 수수료 계산
        $typeCommission = 0;
        if ($partnerType) {
            $typeCommission = $partnerType->calculateCommission($transactionAmount);
        }

        // 실제 데이터 기반 순수 합산 (상한선 제거)
        $totalCommission = $tierCommission + $typeCommission;
        $capApplied = false;

        $effectiveRate = ($transactionAmount > 0) ? ($totalCommission / $transactionAmount) * 100 : 0;

        return [
            'transaction_amount' => $transactionAmount,
            'tier_commission' => $tierCommission,
            'type_commission' => $typeCommission,
            'total_commission' => $totalCommission,
            'effective_rate' => round($effectiveRate, 2),
            'cap_applied' => $capApplied,
            'calculation_method' => 'additive',
            'breakdown' => [
                'tier' => [
                    'name' => $this->tier_name,
                    'type' => $this->commission_type,
                    'rate' => $this->commission_rate ?? 0,
                    'amount' => $this->commission_amount ?? 0,
                    'commission' => $tierCommission,
                ],
                'partner_type' => $partnerType ? [
                    'name' => $partnerType->type_name,
                    'type' => $partnerType->commission_type,
                    'rate' => $partnerType->commission_rate ?? 0,
                    'amount' => $partnerType->commission_amount ?? 0,
                    'commission' => $typeCommission,
                ] : null
            ]
        ];
    }

    /**
     * 다양한 거래 금액에 대한 수수료 시뮬레이션을 제공
     *
     * @param array $amounts 시뮬레이션할 거래 금액 배열
     * @param \Jiny\Partner\Models\PartnerType|null $partnerType 파트너 타입 (null인 경우 연동된 타입 사용)
     * @return array 수수료 시뮬레이션 결과
     */
    public function simulateCommissions(array $amounts = [100000, 500000, 1000000, 5000000, 10000000], $partnerType = null)
    {
        $results = [];

        foreach ($amounts as $amount) {
            $results[] = $this->calculateDetailedCommission($amount, $partnerType);
        }

        return [
            'tier_info' => [
                'tier_code' => $this->tier_code,
                'tier_name' => $this->tier_name,
            ],
            'partner_type_info' => $partnerType ? [
                'type_code' => $partnerType->type_code,
                'type_name' => $partnerType->type_name,
            ] : null,
            'simulations' => $results,
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * 특정 파트너 타입이 이 등급과 호환되는지 확인
     *
     * @param \Jiny\Partner\Models\PartnerType $partnerType 파트너 타입
     * @return bool 호환 여부
     */
    public function isCompatibleWithPartnerType($partnerType)
    {
        // 타입 제한이 없으면 모든 타입과 호환
        if (!$this->isRestrictedToParentType()) {
            return true;
        }

        // 연동된 상위 타입이 있는 경우 해당 타입만 허용
        if ($this->parent_partner_type_id) {
            return $this->parent_partner_type_id === $partnerType->id;
        }

        return false;
    }

    /**
     * 선택 가능한 상위 파트너 타입 목록을 반환
     *
     * @return \Illuminate\Database\Eloquent\Collection 파트너 타입 컬렉션
     */
    public function getAvailableParentPartnerTypes()
    {
        return Cache::remember("partner_tier_{$this->id}_available_parent_types", 3600, function () {
            return \Jiny\Partner\Models\PartnerType::active()
                ->orderBy('priority_level')
                ->get();
        });
    }

    /**
     * 상위 파트너 타입을 설정
     *
     * @param int|null $partnerTypeId 파트너 타입 ID
     * @param bool $restrictToType 해당 타입으로만 제한할지 여부
     * @return bool 설정 성공 여부
     */
    public function setParentPartnerType($partnerTypeId = null, $restrictToType = true)
    {
        try {
            $this->update([
                'parent_partner_type_id' => $partnerTypeId,
                'restrict_to_parent_type' => $restrictToType
            ]);

            // 캐시 무효화
            Cache::forget("partner_tier_{$this->id}_available_parent_types");

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    // ====================================================================
    // 🔍 유틸리티 및 검색 메서드
    // ====================================================================

    /**
     * 등급 코드로 등급을 조회
     *
     * @param string $code 등급 코드
     * @return \Jiny\Partner\Models\PartnerTier|null
     */
    public static function findByCode($code)
    {
        return Cache::remember("partner_tier_code_{$code}", 3600, function () use ($code) {
            return static::where('tier_code', $code)->first();
        });
    }

    /**
     * 등급명으로 등급을 조회
     *
     * @param string $name 등급명
     * @return \Jiny\Partner\Models\PartnerTier|null
     */
    public static function findByName($name)
    {
        return static::where('tier_name', $name)->first();
    }

    /**
     * 활성화된 등급들을 우선순위별로 조회
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getActiveTiersByPriority()
    {
        return Cache::remember('active_partner_tiers_by_priority', 3600, function () {
            return static::active()
                ->orderByPriority()
                ->orderBySortOrder()
                ->get();
        });
    }

    /**
     * 등급별 통계 정보를 반환
     *
     * @return array 통계 정보
     */
    public function getStatistics()
    {
        return Cache::remember("partner_tier_{$this->id}_statistics", 1800, function () {
            return [
                'total_partners' => $this->partnerUsers()->count(),
                'active_partners' => $this->partnerUsers()->where('is_active', true)->count(),
                'average_rating' => $this->partnerUsers()->avg('average_rating') ?? 0,
                'total_completed_jobs' => $this->partnerUsers()->sum('completed_jobs_count') ?? 0,
                'total_commission_earned' => $this->commissionLogs()->sum('commission_amount') ?? 0,
            ];
        });
    }

    /**
     * 등급 순서를 재정렬
     */
    public static function reorderTiers()
    {
        $tiers = static::orderBy('priority_level')
                      ->orderBy('sort_order')
                      ->get();

        DB::transaction(function () use ($tiers) {
            foreach ($tiers as $index => $tier) {
                $tier->update(['sort_order' => ($index + 1) * 10]);
            }
        });

        // 캐시 무효화
        Cache::forget('active_partner_tiers_by_priority');
    }

    // ====================================================================
    // 📝 검증 및 규칙 메서드
    // ====================================================================

    /**
     * 등급별 유효성 검증 규칙을 반환
     *
     * @param int|null $tierId 수정 시 현재 등급 ID (유니크 검증 제외용)
     * @return array 검증 규칙 배열
     */
    public static function getValidationRules($tierId = null)
    {
        $rules = [
            'tier_code' => 'required|string|max:20',
            'tier_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'commission_type' => 'required|in:percentage,fixed_amount',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'commission_amount' => 'nullable|numeric|min:0',
            'priority_level' => 'required|integer|min:1|max:99',
            'parent_partner_type_id' => 'nullable|exists:partner_types,id',
            'restrict_to_parent_type' => 'boolean',
            'requirements' => 'nullable|array',
            'benefits' => 'nullable|array',
            'registration_fee' => 'nullable|numeric|min:0',
            'monthly_fee' => 'nullable|numeric|min:0',
            'annual_fee' => 'nullable|numeric|min:0',
            'fee_waiver_available' => 'boolean',
            'fee_structure_notes' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ];

        // 수정하는 경우 현재 등급 제외하고 유니크 검증
        if ($tierId) {
            $rules['tier_code'] = "required|string|max:20|unique:partner_tiers,tier_code,{$tierId}";
            $rules['tier_name'] = "required|string|max:100|unique:partner_tiers,tier_name,{$tierId}";
        } else {
            $rules['tier_code'] .= '|unique:partner_tiers';
            $rules['tier_name'] .= '|unique:partner_tiers';
        }

        return $rules;
    }

    /**
     * 커스텀 유효성 검증 메시지를 반환
     *
     * @return array 검증 메시지 배열
     */
    public static function getValidationMessages()
    {
        return [
            'tier_code.required' => '등급 코드는 필수입니다.',
            'tier_code.unique' => '이미 존재하는 등급 코드입니다.',
            'tier_name.required' => '등급명은 필수입니다.',
            'tier_name.unique' => '이미 존재하는 등급명입니다.',
            'commission_rate.max' => '수수료율은 최대 100%까지 설정할 수 있습니다.',
            'priority_level.required' => '우선순위 레벨은 필수입니다.',
            'priority_level.min' => '우선순위 레벨은 1 이상이어야 합니다.',
            'parent_partner_type_id.exists' => '존재하지 않는 파트너 타입입니다.',
        ];
    }

    /**
     * 등급 데이터 무결성을 검증
     *
     * @return array 검증 결과
     */
    public function validateIntegrity()
    {
        $errors = [];

        // 수수료 설정 검증
        if ($this->commission_type === 'percentage' && !$this->commission_rate) {
            $errors[] = '퍼센트 방식에서는 수수료율이 필요합니다.';
        }

        if ($this->commission_type === 'fixed_amount' && !$this->commission_amount) {
            $errors[] = '고정 금액 방식에서는 수수료 금액이 필요합니다.';
        }

        // 타입 제한 검증
        if ($this->restrict_to_parent_type && !$this->parent_partner_type_id) {
            $errors[] = '타입 제한이 설정된 경우 상위 파트너 타입이 필요합니다.';
        }

        // 요구사항 및 혜택 JSON 구조 검증
        try {
            json_encode($this->requirements);
            json_encode($this->benefits);
        } catch (\Exception $e) {
            $errors[] = '요구사항 또는 혜택 데이터 형식이 올바르지 않습니다.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}