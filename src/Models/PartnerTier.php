<?php

namespace Jiny\Partner\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartnerTier extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\PartnerTierFactory::new();
    }

    protected $table = 'partner_tiers';

    protected $fillable = [
        'tier_code',
        'tier_name',
        'description',
        'commission_rate',
        'commission_type',
        'commission_amount',
        'priority_level',
        'display_order',
        'parent_tier_id',
        'inherit_parent_commission',
        'max_commission_rate',
        'requirements',
        'benefits',
        'is_active',
        'sort_order',
        'min_completed_jobs',
        'min_rating',
        'min_punctuality_rate',
        'min_satisfaction_rate',

        // 계층 관리 권한 설정
        'max_children',
        'max_depth',
        'can_recruit',

        // 커미션 및 할인율 설정
        'base_commission_rate',
        'management_bonus_rate',
        'discount_rate',
        'override_commission_rate',

        // 승급 조건 설정
        'required_monthly_sales',
        'required_team_sales',
        'required_team_size',
        'required_active_children',

        // 혜택 및 제한 설정
        'recruitment_settings',
        'commission_settings',
        'network_limitations',

        // 비용 관리 설정
        'registration_fee',
        'activation_fee',
        'upgrade_fee',
        'monthly_maintenance_fee',
        'annual_maintenance_fee',
        'renewal_fee',
        'service_fee_rate',
        'platform_fee_rate',
        'transaction_fee_rate',
        'security_deposit',
        'performance_bond',
        'early_payment_discount_rate',
        'loyalty_discount_rate',
        'volume_discount_rate',
        'cost_policy',
        'fee_exemptions',
        'promotional_pricing',
        'cost_management_enabled',
        'cost_policy_updated_at'
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'priority_level' => 'integer',
        'display_order' => 'integer',
        'parent_tier_id' => 'integer',
        'inherit_parent_commission' => 'boolean',
        'max_commission_rate' => 'decimal:4',
        'requirements' => 'array',
        'benefits' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'min_completed_jobs' => 'integer',
        'min_rating' => 'decimal:2',
        'min_punctuality_rate' => 'decimal:2',
        'min_satisfaction_rate' => 'decimal:2',

        // 계층 관리 권한 캐스팅
        'max_children' => 'integer',
        'max_depth' => 'integer',
        'can_recruit' => 'boolean',

        // 커미션 및 할인율 캐스팅
        'base_commission_rate' => 'decimal:2',
        'management_bonus_rate' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'override_commission_rate' => 'decimal:2',

        // 승급 조건 캐스팅
        'required_monthly_sales' => 'decimal:2',
        'required_team_sales' => 'decimal:2',
        'required_team_size' => 'integer',
        'required_active_children' => 'integer',

        // 설정 JSON 캐스팅
        'recruitment_settings' => 'array',
        'commission_settings' => 'array',
        'network_limitations' => 'array',

        // 비용 관리 캐스팅
        'registration_fee' => 'decimal:2',
        'activation_fee' => 'decimal:2',
        'upgrade_fee' => 'decimal:2',
        'monthly_maintenance_fee' => 'decimal:2',
        'annual_maintenance_fee' => 'decimal:2',
        'renewal_fee' => 'decimal:2',
        'service_fee_rate' => 'decimal:4',
        'platform_fee_rate' => 'decimal:4',
        'transaction_fee_rate' => 'decimal:4',
        'security_deposit' => 'decimal:2',
        'performance_bond' => 'decimal:2',
        'early_payment_discount_rate' => 'decimal:4',
        'loyalty_discount_rate' => 'decimal:4',
        'volume_discount_rate' => 'decimal:4',
        'cost_policy' => 'array',
        'fee_exemptions' => 'array',
        'promotional_pricing' => 'array',
        'cost_management_enabled' => 'boolean',
        'cost_policy_updated_at' => 'datetime'
    ];

    /**
     * 이 등급에 속하는 파트너 엔지니어들
     */
    public function partnerEngineers()
    {
        return $this->hasMany(PartnerEngineer::class, 'current_tier', 'tier_code');
    }

    /**
     * 이 등급에 속하는 파트너 유저들
     */
    public function partnerUsers()
    {
        return $this->hasMany(PartnerUser::class);
    }

    /**
     * 활성화된 등급만 조회
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 우선순위 순으로 정렬
     */
    public function scopeOrderByPriority($query)
    {
        return $query->orderBy('priority_level');
    }

    /**
     * 등급 달성 가능 여부 확인
     */
    public function canAchieveTier($engineer)
    {
        return $engineer->total_completed_jobs >= $this->min_completed_jobs
            && $engineer->average_rating >= $this->min_rating
            && $engineer->punctuality_rate >= $this->min_punctuality_rate
            && $engineer->customer_satisfaction >= $this->min_satisfaction_rate;
    }

    // ====================================================================
    // 계층구조 관리 메서드들 (Hierarchy Management Methods)
    // ====================================================================

    /**
     * 파트너 유저가 이 등급의 조건을 만족하는지 확인
     */
    public function canAchieveTierByPartnerUser(PartnerUser $partner)
    {
        // 기본 조건 확인
        $basicRequirements = $partner->total_completed_jobs >= $this->min_completed_jobs
            && $partner->average_rating >= $this->min_rating
            && $partner->punctuality_rate >= $this->min_punctuality_rate
            && $partner->satisfaction_rate >= $this->min_satisfaction_rate;

        // 네트워크 관련 조건 확인
        $networkRequirements = true;

        if ($this->required_monthly_sales > 0) {
            $networkRequirements = $networkRequirements && ($partner->monthly_sales >= $this->required_monthly_sales);
        }

        if ($this->required_team_sales > 0) {
            $networkRequirements = $networkRequirements && ($partner->team_sales >= $this->required_team_sales);
        }

        if ($this->required_team_size > 0) {
            $networkRequirements = $networkRequirements && ($partner->total_children_count >= $this->required_team_size);
        }

        if ($this->required_active_children > 0) {
            $activeChildren = $partner->children()->where('status', 'active')->count();
            $networkRequirements = $networkRequirements && ($activeChildren >= $this->required_active_children);
        }

        return $basicRequirements && $networkRequirements;
    }

    /**
     * 모집 권한이 있는 등급인지 확인
     */
    public function hasRecruitmentRights()
    {
        return $this->can_recruit && $this->max_children > 0;
    }

    /**
     * 최대 관리 가능한 하위 파트너 수 확인
     */
    public function getMaxChildrenLimit()
    {
        return $this->max_children ?? 0;
    }

    /**
     * 최대 계층 깊이 확인
     */
    public function getMaxDepthLimit()
    {
        return $this->max_depth ?? 1;
    }

    /**
     * 기본 커미션율 조회
     */
    public function getBaseCommissionRate()
    {
        return $this->base_commission_rate ?? $this->commission_rate ?? 0;
    }

    /**
     * 관리 보너스율 조회
     */
    public function getManagementBonusRate()
    {
        return $this->management_bonus_rate ?? 0;
    }

    /**
     * 할인율 조회
     */
    public function getDiscountRate()
    {
        return $this->discount_rate ?? 0;
    }

    /**
     * 오버라이드 커미션율 조회
     */
    public function getOverrideCommissionRate()
    {
        return $this->override_commission_rate ?? 0;
    }

    /**
     * 모집 설정 조회
     */
    public function getRecruitmentSettings()
    {
        $defaults = [
            'auto_approval' => false,
            'require_interview' => true,
            'max_daily_recruits' => 5,
            'commission_on_recruitment' => 0,
            'recruitment_bonus' => 0
        ];

        return array_merge($defaults, $this->recruitment_settings ?? []);
    }

    /**
     * 커미션 설정 조회
     */
    public function getCommissionSettings()
    {
        $defaults = [
            'direct_commission_rate' => $this->getBaseCommissionRate(),
            'team_bonus_rate' => $this->getManagementBonusRate(),
            'override_rate' => $this->getOverrideCommissionRate(),
            'max_levels' => $this->getMaxDepthLimit(),
            'monthly_cap' => null,
            'performance_multiplier' => 1.0
        ];

        return array_merge($defaults, $this->commission_settings ?? []);
    }

    /**
     * 네트워크 제한 설정 조회
     */
    public function getNetworkLimitations()
    {
        $defaults = [
            'max_children' => $this->getMaxChildrenLimit(),
            'max_depth' => $this->getMaxDepthLimit(),
            'territory_restrictions' => [],
            'product_restrictions' => [],
            'time_restrictions' => []
        ];

        return array_merge($defaults, $this->network_limitations ?? []);
    }

    /**
     * 등급별 혜택 계산
     */
    public function calculateBenefits(PartnerUser $partner)
    {
        $benefits = [];

        // 기본 커미션 혜택
        $benefits['base_commission'] = $this->getBaseCommissionRate();

        // 할인 혜택
        if ($this->getDiscountRate() > 0) {
            $benefits['discount_rate'] = $this->getDiscountRate();
        }

        // 모집 권한
        if ($this->hasRecruitmentRights()) {
            $benefits['recruitment_rights'] = [
                'can_recruit' => true,
                'max_children' => $this->getMaxChildrenLimit(),
                'max_depth' => $this->getMaxDepthLimit()
            ];
        }

        // 관리 보너스
        if ($this->getManagementBonusRate() > 0) {
            $benefits['management_bonus'] = $this->getManagementBonusRate();
        }

        // 성과 기반 추가 혜택
        if ($partner) {
            $benefits['performance_multiplier'] = $this->calculatePerformanceMultiplier($partner);
        }

        return $benefits;
    }

    /**
     * 성과 기반 배수 계산
     */
    protected function calculatePerformanceMultiplier(PartnerUser $partner)
    {
        $multiplier = 1.0;

        // 팀 규모에 따른 보너스
        if ($partner->total_children_count >= 50) {
            $multiplier += 0.5; // 50명 이상 시 50% 보너스
        } elseif ($partner->total_children_count >= 20) {
            $multiplier += 0.3; // 20명 이상 시 30% 보너스
        } elseif ($partner->total_children_count >= 10) {
            $multiplier += 0.1; // 10명 이상 시 10% 보너스
        }

        // 매출 성과에 따른 보너스
        if ($partner->monthly_sales >= 10000000) { // 1천만원 이상
            $multiplier += 0.3;
        } elseif ($partner->monthly_sales >= 5000000) { // 500만원 이상
            $multiplier += 0.2;
        } elseif ($partner->monthly_sales >= 1000000) { // 100만원 이상
            $multiplier += 0.1;
        }

        return min($multiplier, 2.0); // 최대 200%로 제한
    }

    // ====================================================================
    // 스코프 메서드들 - 계층구조 관련
    // ====================================================================

    /**
     * 모집 권한이 있는 등급들 조회
     */
    public function scopeCanRecruit($query)
    {
        return $query->where('can_recruit', true)->where('max_children', '>', 0);
    }

    /**
     * 커미션율 순으로 정렬
     */
    public function scopeOrderByCommission($query, $direction = 'desc')
    {
        return $query->orderBy('base_commission_rate', $direction);
    }

    /**
     * 특정 커미션율 이상의 등급들 조회
     */
    public function scopeMinCommissionRate($query, $rate)
    {
        return $query->where('base_commission_rate', '>=', $rate);
    }

    /**
     * 네트워크 제한이 없는 등급들 조회
     */
    public function scopeNoNetworkLimitations($query)
    {
        return $query->whereNull('network_limitations')
            ->orWhere('max_children', '>=', 999)
            ->orWhere('max_depth', '>=', 10);
    }

    // ====================================================================
    // 등급 계층구조 및 수수료 관리 메서드들
    // ====================================================================

    /**
     * 상위 등급 관계
     */
    public function parentTier()
    {
        return $this->belongsTo(PartnerTier::class, 'parent_tier_id');
    }

    /**
     * 하위 등급들 관계
     */
    public function childTiers()
    {
        return $this->hasMany(PartnerTier::class, 'parent_tier_id');
    }

    /**
     * 모든 상위 등급들 조회 (재귀적)
     */
    public function getAncestors()
    {
        $ancestors = collect();
        $current = $this->parentTier;

        while ($current) {
            $ancestors->push($current);
            $current = $current->parentTier;
        }

        return $ancestors;
    }

    /**
     * 모든 하위 등급들 조회 (재귀적)
     */
    public function getDescendants()
    {
        $descendants = collect();

        foreach ($this->childTiers as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getDescendants());
        }

        return $descendants;
    }

    /**
     * 실제 수수료 값 계산 (타입에 따라)
     */
    public function getActualCommissionValue($salesAmount = null)
    {
        if ($this->commission_type === 'fixed_amount') {
            return $this->commission_amount ?? 0;
        }

        if ($salesAmount && $this->commission_rate) {
            return ($salesAmount * $this->commission_rate) / 100;
        }

        return $this->commission_rate ?? 0;
    }

    /**
     * 수수료율이 상위 등급 제한을 위반하는지 확인
     */
    public function validateCommissionRate($newRate = null, $newType = null)
    {
        $rate = $newRate ?? $this->commission_rate;
        $type = $newType ?? $this->commission_type;

        // 고정 금액인 경우 별도 검증 필요
        if ($type === 'fixed_amount') {
            return $this->validateFixedAmountCommission($newRate);
        }

        // 상위 등급이 있는 경우 제한 확인
        if ($this->parentTier) {
            $maxAllowed = $this->getMaxAllowedCommissionRate();

            if ($rate > $maxAllowed) {
                return [
                    'valid' => false,
                    'message' => "수수료율은 상위 등급({$this->parentTier->tier_name})의 {$maxAllowed}%를 초과할 수 없습니다.",
                    'max_allowed' => $maxAllowed
                ];
            }
        }

        return ['valid' => true];
    }

    /**
     * 고정 금액 수수료 검증
     */
    protected function validateFixedAmountCommission($amount)
    {
        if ($this->parentTier && $this->parentTier->commission_type === 'percentage') {
            return [
                'valid' => false,
                'message' => "상위 등급이 퍼센트 방식인 경우 고정 금액 방식을 사용할 수 없습니다.",
                'parent_type' => $this->parentTier->commission_type
            ];
        }

        return ['valid' => true];
    }

    /**
     * 허용 가능한 최대 수수료율 계산
     */
    public function getMaxAllowedCommissionRate()
    {
        if (!$this->parentTier) {
            return 100; // 상위 등급이 없으면 100% 까지 허용
        }

        // 명시적으로 설정된 최대 수수료율이 있는 경우
        if ($this->max_commission_rate) {
            return $this->max_commission_rate;
        }

        // 상위 등급의 수수료율보다 낮아야 함
        $parentRate = $this->parentTier->commission_rate ?? 0;

        // 상위 등급이 상속을 허용하는 경우
        if ($this->inherit_parent_commission) {
            return $parentRate;
        }

        // 기본적으로 상위 등급보다 0.5% 낮아야 함
        return max(0, $parentRate - 0.5);
    }

    /**
     * 등급 순서 재정렬
     */
    public static function reorderTiers()
    {
        $tiers = static::orderBy('priority_level')
                      ->orderBy('display_order')
                      ->get();

        foreach ($tiers as $index => $tier) {
            $tier->update(['display_order' => ($index + 1) * 10]);
        }
    }

    /**
     * 표시용 수수료 정보
     */
    public function getCommissionDisplayText()
    {
        if ($this->commission_type === 'fixed_amount') {
            return number_format($this->commission_amount ?? 0) . '원';
        }

        return ($this->commission_rate ?? 0) . '%';
    }

    /**
     * 수수료 타입별 검증 규칙
     */
    public static function getCommissionValidationRules($tierId = null)
    {
        $rules = [
            'commission_type' => 'required|in:percentage,fixed_amount',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'commission_amount' => 'nullable|numeric|min:0',
        ];

        // 수정하는 경우 현재 등급 제외하고 검증
        if ($tierId) {
            $rules['tier_code'] = "required|unique:partner_tiers,tier_code,{$tierId}";
            $rules['tier_name'] = "required|unique:partner_tiers,tier_name,{$tierId}";
        }

        return $rules;
    }

    /**
     * 우선순위별 정렬 (개선된 버전)
     */
    public function scopeOrderByHierarchy($query)
    {
        return $query->orderBy('priority_level')
                    ->orderBy('display_order')
                    ->orderBy('created_at');
    }

    /**
     * 특정 등급의 하위에 속할 수 있는지 확인
     */
    public function canBeChildOf(PartnerTier $potentialParent)
    {
        // 자기 자신을 상위로 설정할 수 없음
        if ($this->id === $potentialParent->id) {
            return false;
        }

        // 순환 참조 방지
        if ($potentialParent->getAncestors()->contains('id', $this->id)) {
            return false;
        }

        // 우선순위 검증 (하위는 상위보다 우선순위가 낮아야 함)
        if ($this->priority_level <= $potentialParent->priority_level) {
            return false;
        }

        return true;
    }
}