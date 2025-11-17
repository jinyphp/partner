<?php

namespace Jiny\Partner\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class PartnerUser extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\PartnerUserFactory::new();
    }

    protected $table = 'partner_users';

    protected $fillable = [
        'user_id',
        'user_table',
        'user_uuid',
        'shard_number',
        'email',
        'name',
        'partner_code',
        'partner_tier_id',
        'partner_type_id',
        'status',
        'status_reason',
        'total_completed_jobs',
        'average_rating',
        'punctuality_rate',
        'satisfaction_rate',
        'partner_joined_at',
        'tier_assigned_at',
        'last_performance_review_at',
        'profile_data',
        'admin_notes',
        'created_by',
        'updated_by',

        // 계층구조 관리 필드들
        'parent_id',
        'level',
        'tree_path',
        'children_count',
        'total_children_count',

        // 개인별 수수료 설정
        'individual_commission_type',
        'individual_commission_rate',
        'individual_commission_amount',
        'commission_notes',

        // 네트워크 관리
        'max_children',
        'discount_rate',

        // 실적 관리
        'monthly_sales',
        'total_sales',
        'team_sales',
        'earned_commissions',

        // 상태 관리
        'can_recruit',
        'last_activity_at',
        'network_settings'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'partner_tier_id' => 'integer',
        'partner_type_id' => 'integer',
        'total_completed_jobs' => 'integer',
        'average_rating' => 'decimal:2',
        'punctuality_rate' => 'decimal:2',
        'satisfaction_rate' => 'decimal:2',
        'partner_joined_at' => 'date',
        'tier_assigned_at' => 'date',
        'last_performance_review_at' => 'date',
        'profile_data' => 'array',
        'created_by' => 'integer',
        'updated_by' => 'integer',

        // 계층구조 관리 필드 캐스팅
        'parent_id' => 'integer',
        'level' => 'integer',
        'children_count' => 'integer',
        'total_children_count' => 'integer',
        'max_children' => 'integer',

        // 개인별 수수료 설정 캐스팅
        'individual_commission_rate' => 'decimal:2',
        'individual_commission_amount' => 'decimal:2',

        // 네트워크 및 실적 관리 캐스팅
        'discount_rate' => 'decimal:2',
        'monthly_sales' => 'decimal:2',
        'total_sales' => 'decimal:2',
        'team_sales' => 'decimal:2',
        'earned_commissions' => 'decimal:2',
        'can_recruit' => 'boolean',
        'last_activity_at' => 'datetime',
        'network_settings' => 'array'
    ];

    /**
     * 파트너 등급 관계
     */
    public function partnerTier()
    {
        return $this->belongsTo(PartnerTier::class, 'partner_tier_id');
    }

    /**
     * 파트너 타입 관계
     */
    public function partnerType()
    {
        return $this->belongsTo(PartnerType::class, 'partner_type_id');
    }

    /**
     * 파트너 등급 관계 (별칭)
     */
    public function tier()
    {
        return $this->belongsTo(PartnerTier::class, 'partner_tier_id');
    }

    /**
     * 파트너 타입 관계 (별칭)
     */
    public function type()
    {
        return $this->belongsTo(PartnerType::class, 'partner_type_id');
    }

    /**
     * 등록한 관리자
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * 수정한 관리자
     */
    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    // ====================================================================
    // 계층구조 관리 관계들 (Hierarchy Management Relationships)
    // ====================================================================

    /**
     * 상위 파트너 (부모)
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * 직접 하위 파트너들 (자식들)
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('created_at');
    }

    /**
     * 모든 하위 파트너들 (후손들) - 재귀적 관계
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * 모든 하위 파트너들을 플랫한 컬렉션으로 반환 (계보 분석용)
     * tree_path를 이용하여 모든 후손을 조회
     */
    public function allDescendants()
    {
        return $this->hasMany(self::class, 'parent_id', 'id')
            ->where(function($query) {
                $query->where('tree_path', 'like', '%/' . $this->id . '/%')
                      ->orWhere('tree_path', 'like', '%/' . $this->id);
            });
    }

    /**
     * 모든 하위 파트너들의 개수를 반환 (계산된 속성)
     */
    public function getAllDescendantsCountAttribute()
    {
        if (!$this->id) {
            return 0;
        }

        return static::where(function($query) {
            $query->where('tree_path', 'like', '%/' . $this->id . '/%')
                  ->orWhere('tree_path', 'like', '%/' . $this->id);
        })->where('id', '!=', $this->id)->count();
    }

    /**
     * 모든 상위 파트너들 (조상들) - 트리 경로 기반
     */
    public function getAncestors()
    {
        if (!$this->tree_path) {
            return collect();
        }

        $ancestorIds = array_filter(explode('/', $this->tree_path));
        return static::whereIn('id', $ancestorIds)->orderBy('level')->get();
    }

    /**
     * 상위 파트너들을 위한 속성 접근자
     */
    public function getAncestorsAttribute()
    {
        return $this->getAncestors();
    }

    /**
     * 네트워크 관계들
     */
    public function networkRelationships()
    {
        return $this->hasMany(PartnerNetworkRelationship::class, 'parent_id');
    }

    /**
     * 자신이 하위 파트너로 속한 네트워크 관계들
     */
    public function parentNetworkRelationships()
    {
        return $this->hasMany(PartnerNetworkRelationship::class, 'child_id');
    }

    /**
     * 커미션 내역
     */
    public function commissions()
    {
        return $this->hasMany(PartnerCommission::class, 'partner_id');
    }

    /**
     * 자신으로부터 발생한 커미션 내역
     */
    public function generatedCommissions()
    {
        return $this->hasMany(PartnerCommission::class, 'source_partner_id');
    }

    /**
     * 동적 목표 관계
     */
    public function dynamicTargets()
    {
        return $this->hasMany(PartnerDynamicTarget::class, 'partner_user_id');
    }

    /**
     * 현재 활성 목표
     */
    public function activeTargets()
    {
        return $this->hasMany(PartnerDynamicTarget::class, 'partner_user_id')
            ->where('status', 'active');
    }

    /**
     * 완료된 목표들
     */
    public function completedTargets()
    {
        return $this->hasMany(PartnerDynamicTarget::class, 'partner_user_id')
            ->where('status', 'completed');
    }

    /**
     * 활성 파트너만 조회
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * 특정 등급의 파트너 조회
     */
    public function scopeByTier($query, $tierId)
    {
        return $query->where('partner_tier_id', $tierId);
    }

    /**
     * 특정 타입의 파트너 조회
     */
    public function scopeByType($query, $typeId)
    {
        return $query->where('partner_type_id', $typeId);
    }

    /**
     * 상태별 조회
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * 이메일로 검색
     */
    public function scopeByEmail($query, $email)
    {
        return $query->where('email', 'like', "%{$email}%");
    }

    /**
     * 이름으로 검색
     */
    public function scopeByName($query, $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    /**
     * 샤딩된 사용자 테이블에서 사용자 정보 조회
     */
    public function getUserFromShardedTable()
    {
        try {
            return DB::table($this->user_table)
                ->where('id', $this->user_id)
                ->first();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 샤딩된 테이블에서 이메일로 사용자 검색
     */
    public static function searchUserByEmail($email, $userTables = ['users', 'user_001', 'user_002', 'user_003'])
    {
        $users = collect();

        foreach ($userTables as $table) {
            try {
                $result = DB::table($table)
                    ->where('email', 'like', "%{$email}%")
                    ->select('id', 'email', 'name', DB::raw("'{$table}' as user_table"))
                    ->get();

                $users = $users->merge($result);
            } catch (\Exception $e) {
                // 테이블이 존재하지 않는 경우 무시
                continue;
            }
        }

        return $users;
    }

    /**
     * 등급 승급 가능 여부 확인
     */
    public function canUpgradeToTier(PartnerTier $tier)
    {
        return $this->total_completed_jobs >= $tier->min_completed_jobs
            && $this->average_rating >= $tier->min_rating
            && $this->punctuality_rate >= $tier->min_punctuality_rate
            && $this->satisfaction_rate >= $tier->min_satisfaction_rate;
    }

    /**
     * 성과 데이터 업데이트
     */
    public function updatePerformanceData($jobsCompleted = 0, $rating = null, $punctualityRate = null, $satisfactionRate = null)
    {
        $this->total_completed_jobs += $jobsCompleted;

        if ($rating !== null) {
            // 평균 평점 계산 (간단한 이동 평균)
            $this->average_rating = ($this->average_rating + $rating) / 2;
        }

        if ($punctualityRate !== null) {
            $this->punctuality_rate = $punctualityRate;
        }

        if ($satisfactionRate !== null) {
            $this->satisfaction_rate = $satisfactionRate;
        }

        $this->save();
    }

    /**
     * 상태 변경
     */
    public function changeStatus($status, $reason = null, $adminId = null)
    {
        $this->status = $status;
        $this->status_reason = $reason;
        $this->updated_by = $adminId;
        $this->save();
    }

    /**
     * 등급 변경
     */
    public function changeTier($tierId, $adminId = null)
    {
        $this->partner_tier_id = $tierId;
        $this->tier_assigned_at = now();
        $this->updated_by = $adminId;
        $this->save();
    }

    // ====================================================================
    // 트리 구조 관리 메서드들 (Tree Structure Management Methods)
    // ====================================================================

    /**
     * 하위 파트너 추가 (모집)
     */
    public function addChild(PartnerUser $child, $recruiterId = null)
    {
        DB::transaction(function () use ($child, $recruiterId) {
            // 모집 권한 확인
            if (!$this->canRecruit()) {
                throw new \Exception('모집 권한이 없습니다.');
            }

            // 최대 하위 파트너 수 확인
            if ($this->hasReachedMaxChildren()) {
                throw new \Exception('관리 가능한 최대 하위 파트너 수에 도달했습니다.');
            }

            // 하위 파트너 설정
            $child->parent_id = $this->id;
            $child->level = $this->level + 1;
            $child->tree_path = $this->tree_path . '/' . $this->id;
            $child->save();

            // 부모의 하위 파트너 수 업데이트
            $this->increment('children_count');
            $this->updateTotalChildrenCount();

            // 네트워크 관계 생성
            PartnerNetworkRelationship::create([
                'parent_id' => $this->id,
                'child_id' => $child->id,
                'depth' => 1,
                'relationship_path' => $this->id . '/' . $child->id,
                'recruiter_id' => $recruiterId ?? $this->id,
                'recruited_at' => now(),
                'recruitment_details' => [
                    'recruited_by_tier' => $this->partnerTier->tier_name ?? null,
                    'recruited_to_tier' => $child->partnerTier->tier_name ?? null,
                    'recruitment_date' => now()->toDateString()
                ]
            ]);

            // 상위 계층들의 네트워크 관계도 업데이트
            $this->updateAncestorRelationships($child);
        });
    }

    /**
     * 하위 파트너 제거
     */
    public function removeChild(PartnerUser $child)
    {
        DB::transaction(function () use ($child) {
            // 하위 파트너의 모든 관계 정리
            $child->descendants->each(function ($descendant) {
                $descendant->parent_id = null;
                $descendant->level = 0;
                $descendant->tree_path = null;
                $descendant->save();
            });

            // 하위 파트너 관계 해제
            $child->parent_id = null;
            $child->level = 0;
            $child->tree_path = null;
            $child->save();

            // 부모의 하위 파트너 수 업데이트
            $this->decrement('children_count');
            $this->updateTotalChildrenCount();

            // 네트워크 관계 비활성화
            PartnerNetworkRelationship::where('parent_id', $this->id)
                ->where('child_id', $child->id)
                ->update([
                    'is_active' => false,
                    'deactivated_at' => now(),
                    'deactivation_reason' => '파트너 관계 해제'
                ]);
        });
    }

    /**
     * 모집 권한 확인
     */
    public function canRecruit()
    {
        return $this->can_recruit
            && $this->status === 'active'
            && $this->partnerTier
            && $this->partnerTier->can_recruit
            && !$this->hasReachedMaxChildren();
    }

    /**
     * 최대 하위 파트너 수 도달 여부 확인
     */
    public function hasReachedMaxChildren()
    {
        $maxChildren = $this->max_children ?? $this->partnerTier->max_children ?? 0;
        return $this->children_count >= $maxChildren;
    }

    /**
     * 전체 하위 파트너 수 업데이트
     */
    public function updateTotalChildrenCount()
    {
        $total = $this->calculateTotalDescendants();
        $this->update(['total_children_count' => $total]);

        // 상위 파트너들도 업데이트
        if ($this->parent) {
            $this->parent->updateTotalChildrenCount();
        }
    }

    /**
     * 전체 후손 수 계산
     */
    public function calculateTotalDescendants()
    {
        return static::where('tree_path', 'like', '%/' . $this->id . '/%')
            ->orWhere('tree_path', 'like', '%/' . $this->id)
            ->count();
    }

    /**
     * 상위 계층들의 네트워크 관계 업데이트
     */
    protected function updateAncestorRelationships(PartnerUser $newChild)
    {
        $ancestors = $this->getAncestors();

        foreach ($ancestors as $ancestor) {
            $depth = $newChild->level - $ancestor->level;

            PartnerNetworkRelationship::create([
                'parent_id' => $ancestor->id,
                'child_id' => $newChild->id,
                'depth' => $depth,
                'relationship_path' => $ancestor->tree_path . '/' . $ancestor->id . '/' . $this->id . '/' . $newChild->id,
                'relationship_type' => 'inherited',
                'recruiter_id' => $this->id, // 실제 모집자
                'recruited_at' => now()
            ]);
        }
    }

    /**
     * 팀 매출 계산 및 업데이트
     */
    public function updateTeamSales()
    {
        $teamSales = $this->monthly_sales; // 자신의 매출

        // 모든 하위 파트너들의 매출 합산
        $this->descendants->each(function ($descendant) use (&$teamSales) {
            $teamSales += $descendant->monthly_sales;
        });

        $this->update(['team_sales' => $teamSales]);

        // 상위 파트너들도 업데이트
        if ($this->parent) {
            $this->parent->updateTeamSales();
        }
    }

    /**
     * 커미션 분배 계산
     */
    public function calculateCommissionDistribution($saleAmount, $orderData = [])
    {
        $commissions = [];
        $currentPartner = $this;
        $remainingAmount = $saleAmount;

        // 상위 계층으로 올라가면서 커미션 계산
        while ($currentPartner->parent && $remainingAmount > 0) {
            $parent = $currentPartner->parent;

            // 상위 파트너의 커미션율 확인
            $commissionRate = $parent->getCommissionRateForLevel($currentPartner->level);

            if ($commissionRate > 0) {
                $commissionAmount = $saleAmount * ($commissionRate / 100);

                $commissions[] = [
                    'partner_id' => $parent->id,
                    'source_partner_id' => $this->id,
                    'commission_type' => $this->getCommissionType($parent, $currentPartner),
                    'level_difference' => $parent->level - $this->level,
                    'original_amount' => $saleAmount,
                    'commission_rate' => $commissionRate,
                    'commission_amount' => $commissionAmount,
                    'net_amount' => $commissionAmount * 0.9, // 10% 세금 가정
                    'tree_path_at_time' => $this->tree_path,
                    'earned_at' => now()
                ];

                $remainingAmount -= $commissionAmount;
            }

            $currentPartner = $parent;
        }

        return $commissions;
    }

    /**
     * 개별 파트너의 총 수수료율 계산 (type + tier + individual)
     */
    public function getTotalCommissionRate()
    {
        $typeRate = $this->partnerType ? $this->partnerType->default_commission_rate : 0;
        $tierRate = $this->partnerTier ? $this->partnerTier->commission_rate : 0;
        $individualRate = $this->individual_commission_type === 'percentage'
            ? $this->individual_commission_rate
            : 0;

        return $typeRate + $tierRate + $individualRate;
    }

    /**
     * 개별 파트너의 총 수수료 금액 계산 (type + tier + individual)
     */
    public function getTotalCommissionAmount($baseAmount = 100000)
    {
        $typeAmount = $this->partnerType ? $this->partnerType->default_commission_amount : 0;
        $tierAmount = $this->partnerTier ? $this->partnerTier->commission_amount : 0;
        $individualAmount = $this->individual_commission_type === 'fixed_amount'
            ? $this->individual_commission_amount
            : 0;

        // 퍼센트 기반 수수료 계산
        $percentageCommission = ($baseAmount * $this->getTotalCommissionRate()) / 100;

        return $typeAmount + $tierAmount + $individualAmount + $percentageCommission;
    }

    /**
     * 수수료 구성 요소 상세 조회
     */
    public function getCommissionBreakdown($baseAmount = 100000)
    {
        $breakdown = [
            'partner_type' => [
                'rate' => $this->partnerType ? $this->partnerType->default_commission_rate : 0,
                'amount' => $this->partnerType ? $this->partnerType->default_commission_amount : 0,
                'calculated_amount' => 0
            ],
            'partner_tier' => [
                'rate' => $this->partnerTier ? $this->partnerTier->commission_rate : 0,
                'amount' => $this->partnerTier ? $this->partnerTier->commission_amount : 0,
                'calculated_amount' => 0
            ],
            'individual' => [
                'type' => $this->individual_commission_type,
                'rate' => $this->individual_commission_type === 'percentage' ? $this->individual_commission_rate : 0,
                'amount' => $this->individual_commission_type === 'fixed_amount' ? $this->individual_commission_amount : 0,
                'calculated_amount' => 0
            ],
            'total' => [
                'total_rate' => 0,
                'total_fixed_amount' => 0,
                'total_commission' => 0
            ]
        ];

        // 각 구성요소별 계산된 금액 산출
        if ($breakdown['partner_type']['rate'] > 0) {
            $breakdown['partner_type']['calculated_amount'] = ($baseAmount * $breakdown['partner_type']['rate']) / 100;
        }
        if ($breakdown['partner_tier']['rate'] > 0) {
            $breakdown['partner_tier']['calculated_amount'] = ($baseAmount * $breakdown['partner_tier']['rate']) / 100;
        }
        if ($breakdown['individual']['rate'] > 0) {
            $breakdown['individual']['calculated_amount'] = ($baseAmount * $breakdown['individual']['rate']) / 100;
        }

        // 총계 계산
        $breakdown['total']['total_rate'] = $breakdown['partner_type']['rate'] + $breakdown['partner_tier']['rate'] + $breakdown['individual']['rate'];
        $breakdown['total']['total_fixed_amount'] = $breakdown['partner_type']['amount'] + $breakdown['partner_tier']['amount'] + $breakdown['individual']['amount'];
        $breakdown['total']['total_commission'] = $breakdown['partner_type']['calculated_amount'] +
                                                 $breakdown['partner_tier']['calculated_amount'] +
                                                 $breakdown['individual']['calculated_amount'] +
                                                 $breakdown['total']['total_fixed_amount'];

        return $breakdown;
    }

    /**
     * 개별 수수료 설정 업데이트
     */
    public function updateIndividualCommission($type, $value, $notes = null, $adminId = null)
    {
        $this->individual_commission_type = $type;

        if ($type === 'percentage') {
            $this->individual_commission_rate = $value;
            $this->individual_commission_amount = 0;
        } else {
            $this->individual_commission_amount = $value;
            $this->individual_commission_rate = 0;
        }

        $this->commission_notes = $notes;
        $this->updated_by = $adminId;
        $this->save();
    }

    /**
     * 레벨별 커미션율 조회 (기존 로직 유지하되 새로운 구조 반영)
     */
    protected function getCommissionRateForLevel($childLevel)
    {
        $levelDifference = $childLevel - $this->level;

        // 직접 하위 파트너인 경우 - 총 수수료율 사용
        if ($levelDifference === 1) {
            return $this->getTotalCommissionRate();
        }

        // 관리 보너스 - 개별 수수료만 적용
        if ($levelDifference <= ($this->partnerTier->max_depth ?? 3)) {
            return $this->individual_commission_type === 'percentage'
                ? $this->individual_commission_rate
                : 0;
        }

        return 0;
    }

    /**
     * 커미션 타입 결정
     */
    protected function getCommissionType($recipient, $source)
    {
        $levelDifference = $source->level - $recipient->level;

        if ($levelDifference === 1) {
            return 'direct_sales';
        } else if ($levelDifference <= 3) {
            return 'team_bonus';
        } else {
            return 'management_bonus';
        }
    }

    // ====================================================================
    // 스코프 메서드들 - 계층구조 관련
    // ====================================================================

    /**
     * 최상위 파트너들만 조회 (루트 노드들)
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * 특정 레벨의 파트너들 조회
     */
    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    /**
     * 모집 가능한 파트너들 조회
     */
    public function scopeCanRecruit($query)
    {
        return $query->where('partner_users.can_recruit', true)
            ->where('partner_users.status', 'active')
            ->whereRaw('partner_users.children_count < COALESCE(partner_users.max_children, 999)');
    }

    /**
     * 특정 파트너의 하위 트리 조회
     */
    public function scopeDescendantsOf($query, $partnerId)
    {
        return $query->where('tree_path', 'like', '%/' . $partnerId . '/%')
            ->orWhere('tree_path', 'like', '%/' . $partnerId);
    }

    // ====================================================================
    // 실시간 통계 계산 메서드들 (Real-time Statistics Calculation Methods)
    // ====================================================================

    /**
     * 총 매출액 계산 (실시간)
     * partner_sales 테이블에서 실시간 집계
     */
    public function getTotalSalesAmount()
    {
        return $this->sales()
            ->where('status', 'confirmed')
            ->sum('amount') ?? 0;
    }

    /**
     * 총 커미션 계산 (실시간)
     * partner_commissions 테이블에서 실시간 집계
     */
    public function getTotalCommissionsEarned()
    {
        return $this->commissions()
            ->where('status', '!=', 'cancelled')
            ->sum('commission_amount') ?? 0;
    }

    /**
     * 지급된 커미션 계산
     */
    public function getPaidCommissions()
    {
        return $this->commissions()
            ->where('status', 'paid')
            ->sum('commission_amount') ?? 0;
    }

    /**
     * 미지급 커미션 계산
     */
    public function getUnpaidCommissions()
    {
        return $this->commissions()
            ->whereIn('status', ['calculated', 'pending'])
            ->sum('commission_amount') ?? 0;
    }

    /**
     * 커미션 잔액 (Balance) 계산
     * 지급 예정 커미션 - 지급된 커미션
     */
    public function getCommissionBalance()
    {
        $totalEarned = $this->getTotalCommissionsEarned();
        $totalPaid = $this->getPaidCommissions();

        return $totalEarned - $totalPaid;
    }

    /**
     * 팀 총 매출액 계산 (자신 + 모든 하위 파트너)
     */
    public function getTeamTotalSales()
    {
        $ownSales = $this->getTotalSalesAmount();

        // 하위 파트너들의 매출 합계
        $teamSales = static::descendantsOf($this->id)
            ->get()
            ->sum(function($partner) {
                return $partner->getTotalSalesAmount();
            });

        return $ownSales + $teamSales;
    }

    /**
     * 월별 매출 통계 (최근 12개월)
     */
    public function getMonthlyStats($months = 12)
    {
        $stats = [];

        for ($i = 0; $i < $months; $i++) {
            $startOfMonth = now()->subMonths($i)->startOfMonth();
            $endOfMonth = now()->subMonths($i)->endOfMonth();

            $monthSales = $this->sales()
                ->where('status', 'confirmed')
                ->whereBetween('sales_date', [$startOfMonth, $endOfMonth])
                ->sum('amount') ?? 0;

            $monthCommissions = $this->commissions()
                ->where('status', '!=', 'cancelled')
                ->whereBetween('earned_at', [$startOfMonth, $endOfMonth])
                ->sum('commission_amount') ?? 0;

            $stats[] = [
                'month' => $startOfMonth->format('Y-m'),
                'month_name' => $startOfMonth->format('Y년 n월'),
                'sales_amount' => $monthSales,
                'commission_amount' => $monthCommissions,
                'commission_rate' => $monthSales > 0 ? round(($monthCommissions / $monthSales) * 100, 2) : 0
            ];
        }

        return array_reverse($stats); // 오래된 순서부터
    }

    /**
     * 이번 달 성과
     */
    public function getThisMonthStats()
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        return [
            'sales_amount' => $this->sales()
                ->where('status', 'confirmed')
                ->whereBetween('sales_date', [$startOfMonth, $endOfMonth])
                ->sum('amount') ?? 0,

            'commission_amount' => $this->commissions()
                ->where('status', '!=', 'cancelled')
                ->whereBetween('earned_at', [$startOfMonth, $endOfMonth])
                ->sum('commission_amount') ?? 0,

            'sales_count' => $this->sales()
                ->where('status', 'confirmed')
                ->whereBetween('sales_date', [$startOfMonth, $endOfMonth])
                ->count(),

            'commission_count' => $this->commissions()
                ->where('status', '!=', 'cancelled')
                ->whereBetween('earned_at', [$startOfMonth, $endOfMonth])
                ->count()
        ];
    }

    /**
     * 전체 성과 요약 (실시간 계산)
     */
    public function getPerformanceSummary()
    {
        return [
            // 매출 정보
            'total_sales' => $this->getTotalSalesAmount(),
            'team_total_sales' => $this->getTeamTotalSales(),
            'sales_count' => $this->sales()->where('status', 'confirmed')->count(),

            // 커미션 정보
            'total_commissions' => $this->getTotalCommissionsEarned(),
            'paid_commissions' => $this->getPaidCommissions(),
            'unpaid_commissions' => $this->getUnpaidCommissions(),
            'commission_balance' => $this->getCommissionBalance(),

            // 비율 계산
            'commission_rate' => $this->getTotalSalesAmount() > 0
                ? round(($this->getTotalCommissionsEarned() / $this->getTotalSalesAmount()) * 100, 2)
                : 0,

            // 이번 달 성과
            'this_month' => $this->getThisMonthStats(),

            // 네트워크 정보
            'direct_children' => $this->children_count ?? 0,
            'total_network_size' => $this->getAllDescendantsCountAttribute(),

            // 기간 정보
            'calculated_at' => now()->toISOString()
        ];
    }

    /**
     * 매출 관계 (PartnerSales와의 연결)
     */
    public function sales()
    {
        return $this->hasMany(\Jiny\Partner\Models\PartnerSales::class, 'partner_id');
    }

    // ====================================================================
    // 캐시된 통계 업데이트 메서드들 (Cached Statistics Update Methods)
    // ====================================================================

    /**
     * 캐시된 통계 데이터 업데이트
     * 성능을 위해 주기적으로 실행하여 데이터베이스 필드에 저장
     */
    public function updateCachedStatistics()
    {
        $stats = $this->getPerformanceSummary();

        $this->update([
            'monthly_sales' => $stats['total_sales'], // 총 매출액으로 업데이트
            'earned_commissions' => $stats['total_commissions'],
            'team_sales' => $stats['team_total_sales'],
            'statistics_updated_at' => now()
        ]);

        return $stats;
    }

    /**
     * 통계 데이터가 최신인지 확인
     */
    public function hasRecentStatistics($cacheMinutes = 60)
    {
        if (!$this->statistics_updated_at) {
            return false;
        }

        return $this->statistics_updated_at->diffInMinutes(now()) < $cacheMinutes;
    }

    /**
     * 실시간 또는 캐시된 통계 반환
     * 최근에 업데이트된 경우 캐시된 값 사용, 그렇지 않으면 실시간 계산
     */
    public function getStatistics($useCache = true, $cacheMinutes = 60)
    {
        if ($useCache && $this->hasRecentStatistics($cacheMinutes)) {
            return [
                'total_sales' => $this->monthly_sales ?? 0,
                'total_commissions' => $this->earned_commissions ?? 0,
                'team_sales' => $this->team_sales ?? 0,
                'commission_balance' => $this->getCommissionBalance(), // 항상 실시간
                'cached' => true,
                'updated_at' => $this->statistics_updated_at
            ];
        }

        return array_merge($this->getPerformanceSummary(), ['cached' => false]);
    }
}