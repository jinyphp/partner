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
        'max_children',
        'personal_commission_rate',
        'management_bonus_rate',
        'discount_rate',
        'monthly_sales',
        'total_sales',
        'team_sales',
        'earned_commissions',
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
        'personal_commission_rate' => 'decimal:2',
        'management_bonus_rate' => 'decimal:2',
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
        return $this->belongsTo(PartnerTier::class);
    }

    /**
     * 파트너 타입 관계
     */
    public function partnerType()
    {
        return $this->belongsTo(PartnerType::class);
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
    public function ancestors()
    {
        if (!$this->tree_path) {
            return collect();
        }

        $ancestorIds = array_filter(explode('/', $this->tree_path));
        return static::whereIn('id', $ancestorIds)->orderBy('level')->get();
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
        $ancestors = $this->ancestors();

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
     * 레벨별 커미션율 조회
     */
    protected function getCommissionRateForLevel($childLevel)
    {
        $levelDifference = $childLevel - $this->level;

        // 직접 하위 파트너인 경우
        if ($levelDifference === 1) {
            return $this->personal_commission_rate;
        }

        // 관리 보너스
        if ($levelDifference <= ($this->partnerTier->max_depth ?? 3)) {
            return $this->management_bonus_rate;
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
}