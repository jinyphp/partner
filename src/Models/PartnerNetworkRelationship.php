<?php

namespace Jiny\Partner\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartnerNetworkRelationship extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\Jiny\Partner\Models\PartnerNetworkRelationshipFactory::new();
    }

    protected $table = 'partner_network_relationships';

    protected $fillable = [
        'parent_id',
        'child_id',
        'depth',
        'relationship_path',
        'relationship_type',
        'recruiter_id',
        'recruited_at',
        'recruitment_details',
        'is_active',
        'activated_at',
        'deactivated_at',
        'deactivation_reason',
        'total_generated_sales',
        'total_commissions_paid',
        'sub_partners_recruited'
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'child_id' => 'integer',
        'depth' => 'integer',
        'recruiter_id' => 'integer',
        'recruited_at' => 'datetime',
        'recruitment_details' => 'array',
        'is_active' => 'boolean',
        'activated_at' => 'datetime',
        'deactivated_at' => 'datetime',
        'total_generated_sales' => 'decimal:2',
        'total_commissions_paid' => 'decimal:2',
        'sub_partners_recruited' => 'integer'
    ];

    /**
     * 상위 파트너
     */
    public function parent()
    {
        return $this->belongsTo(PartnerUser::class, 'parent_id');
    }

    /**
     * 하위 파트너
     */
    public function child()
    {
        return $this->belongsTo(PartnerUser::class, 'child_id');
    }

    /**
     * 실제 모집한 파트너
     */
    public function recruiter()
    {
        return $this->belongsTo(PartnerUser::class, 'recruiter_id');
    }

    // ====================================================================
    // 스코프 메서드들
    // ====================================================================

    /**
     * 활성 관계만 조회
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 비활성 관계만 조회
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * 직접 관계만 조회 (1단계)
     */
    public function scopeDirect($query)
    {
        return $query->where('depth', 1);
    }

    /**
     * 특정 깊이의 관계 조회
     */
    public function scopeByDepth($query, $depth)
    {
        return $query->where('depth', $depth);
    }

    /**
     * 특정 타입의 관계 조회
     */
    public function scopeByType($query, $type)
    {
        return $query->where('relationship_type', $type);
    }

    /**
     * 특정 상위 파트너의 모든 하위 관계
     */
    public function scopeChildrenOf($query, $parentId)
    {
        return $query->where('parent_id', $parentId);
    }

    /**
     * 특정 하위 파트너의 모든 상위 관계
     */
    public function scopeParentsOf($query, $childId)
    {
        return $query->where('child_id', $childId);
    }

    /**
     * 특정 모집자가 모집한 관계들
     */
    public function scopeRecruitedBy($query, $recruiterId)
    {
        return $query->where('recruiter_id', $recruiterId);
    }

    /**
     * 특정 기간에 모집된 관계들
     */
    public function scopeRecruitedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('recruited_at', [$startDate, $endDate]);
    }

    // ====================================================================
    // 헬퍼 메서드들
    // ====================================================================

    /**
     * 관계 활성화
     */
    public function activate()
    {
        $this->is_active = true;
        $this->activated_at = now();
        $this->deactivated_at = null;
        $this->deactivation_reason = null;

        return $this->save();
    }

    /**
     * 관계 비활성화
     */
    public function deactivate($reason = null)
    {
        $this->is_active = false;
        $this->deactivated_at = now();
        $this->deactivation_reason = $reason;

        return $this->save();
    }

    /**
     * 매출 추가
     */
    public function addSales($amount)
    {
        $this->total_generated_sales += $amount;
        return $this->save();
    }

    /**
     * 커미션 지급 기록
     */
    public function addCommissionPaid($amount)
    {
        $this->total_commissions_paid += $amount;
        return $this->save();
    }

    /**
     * 하위 파트너 모집 수 증가
     */
    public function incrementSubPartnersRecruited()
    {
        $this->sub_partners_recruited++;
        return $this->save();
    }

    /**
     * 관계 타입별 설명
     */
    public function getTypeDescription()
    {
        $descriptions = [
            'direct' => '직접 모집',
            'inherited' => '상속된 관계',
            'transferred' => '이전된 관계'
        ];

        return $descriptions[$this->relationship_type] ?? $this->relationship_type;
    }

    /**
     * 관계 성과 요약
     */
    public function getPerformanceSummary()
    {
        return [
            'depth' => $this->depth,
            'relationship_type' => $this->getTypeDescription(),
            'is_active' => $this->is_active,
            'duration_days' => $this->recruited_at->diffInDays($this->deactivated_at ?? now()),
            'total_sales' => $this->total_generated_sales,
            'total_commissions' => $this->total_commissions_paid,
            'commission_rate' => $this->total_generated_sales > 0
                ? round(($this->total_commissions_paid / $this->total_generated_sales) * 100, 2)
                : 0,
            'sub_partners_recruited' => $this->sub_partners_recruited
        ];
    }

    /**
     * 수익성 분석
     */
    public function isProfitable()
    {
        return $this->total_generated_sales > $this->total_commissions_paid;
    }

    /**
     * ROI 계산 (커미션 대비 매출)
     */
    public function getROI()
    {
        if ($this->total_commissions_paid <= 0) {
            return $this->total_generated_sales > 0 ? 100 : 0;
        }

        return round(($this->total_generated_sales / $this->total_commissions_paid) * 100, 2);
    }

    /**
     * 평균 월간 매출
     */
    public function getAverageMonthlySales()
    {
        $months = $this->recruited_at->diffInMonths($this->deactivated_at ?? now());

        if ($months <= 0) {
            return $this->total_generated_sales;
        }

        return round($this->total_generated_sales / $months, 2);
    }
}