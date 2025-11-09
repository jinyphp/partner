<?php

namespace Jiny\Partner\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartnerCommission extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'partner_commissions';

    protected $fillable = [
        'partner_id',
        'source_partner_id',
        'order_id',
        'commission_type',
        'level_difference',
        'tree_path_at_time',
        'original_amount',
        'commission_rate',
        'commission_amount',
        'tax_amount',
        'net_amount',
        'status',
        'earned_at',
        'calculated_at',
        'paid_at',
        'calculation_details',
        'notes'
    ];

    protected $casts = [
        'partner_id' => 'integer',
        'source_partner_id' => 'integer',
        'order_id' => 'integer',
        'level_difference' => 'integer',
        'original_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'earned_at' => 'datetime',
        'calculated_at' => 'datetime',
        'paid_at' => 'datetime',
        'calculation_details' => 'array'
    ];

    /**
     * 커미션을 받을 파트너
     */
    public function partner()
    {
        return $this->belongsTo(PartnerUser::class, 'partner_id');
    }

    /**
     * 매출을 발생시킨 파트너
     */
    public function sourcePartner()
    {
        return $this->belongsTo(PartnerUser::class, 'source_partner_id');
    }

    /**
     * 관련 주문 (있는 경우)
     */
    public function order()
    {
        return $this->belongsTo(\App\Models\Order::class, 'order_id');
    }

    // ====================================================================
    // 스코프 메서드들
    // ====================================================================

    /**
     * 대기 중인 커미션
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * 계산 완료된 커미션
     */
    public function scopeCalculated($query)
    {
        return $query->where('status', 'calculated');
    }

    /**
     * 지급 완료된 커미션
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * 특정 타입의 커미션
     */
    public function scopeByType($query, $type)
    {
        return $query->where('commission_type', $type);
    }

    /**
     * 특정 기간의 커미션
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('earned_at', [$startDate, $endDate]);
    }

    /**
     * 이번 달 커미션
     */
    public function scopeThisMonth($query)
    {
        return $query->whereYear('earned_at', now()->year)
            ->whereMonth('earned_at', now()->month);
    }

    // ====================================================================
    // 헬퍼 메서드들
    // ====================================================================

    /**
     * 커미션 계산 및 상태 업데이트
     */
    public function calculate()
    {
        if ($this->status !== 'pending') {
            return false;
        }

        // 세금 계산 (간단한 10% 가정)
        $this->tax_amount = $this->commission_amount * 0.1;
        $this->net_amount = $this->commission_amount - $this->tax_amount;
        $this->status = 'calculated';
        $this->calculated_at = now();

        return $this->save();
    }

    /**
     * 커미션 지급 처리
     */
    public function markAsPaid($notes = null)
    {
        if ($this->status !== 'calculated') {
            return false;
        }

        $this->status = 'paid';
        $this->paid_at = now();

        if ($notes) {
            $this->notes = $notes;
        }

        return $this->save();
    }

    /**
     * 커미션 취소
     */
    public function cancel($reason = null)
    {
        $this->status = 'cancelled';
        $this->notes = $reason;

        return $this->save();
    }

    /**
     * 커미션 타입별 설명
     */
    public function getTypeDescription()
    {
        $descriptions = [
            'direct_sales' => '직접 판매 커미션',
            'team_bonus' => '팀 보너스',
            'management_bonus' => '관리 보너스',
            'override_bonus' => '오버라이드 보너스',
            'recruitment_bonus' => '모집 보너스',
            'rank_bonus' => '등급 보너스'
        ];

        return $descriptions[$this->commission_type] ?? $this->commission_type;
    }

    /**
     * 상태별 한글 설명
     */
    public function getStatusDescription()
    {
        $descriptions = [
            'pending' => '대기중',
            'calculated' => '계산완료',
            'paid' => '지급완료',
            'cancelled' => '취소됨',
            'disputed' => '분쟁중'
        ];

        return $descriptions[$this->status] ?? $this->status;
    }
}