<?php

namespace Jiny\Partner\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class PartnerSales extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\PartnerSalesFactory::new();
    }

    protected $table = 'partner_sales';

    protected $fillable = [
        'partner_id',
        'partner_name',
        'partner_email',
        'title',
        'description',
        'amount',
        'currency',
        'sales_date',
        'order_number',
        'category',
        'product_type',
        'sales_channel',
        'status',
        'status_reason',
        'confirmed_at',
        'cancelled_at',
        'commission_calculated',
        'commission_calculated_at',
        'total_commission_amount',
        'commission_recipients_count',
        'commission_distribution',
        'tree_snapshot',
        'partner_tier_at_time',
        'partner_type_at_time',
        'requires_approval',
        'is_approved',
        'approved_by',
        'approved_at',
        'approval_notes',
        'created_by',
        'updated_by',
        'admin_notes',
        'external_reference',
        'external_data'
    ];

    protected $casts = [
        'partner_id' => 'integer',
        'amount' => 'decimal:2',
        'sales_date' => 'date',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'commission_calculated' => 'boolean',
        'commission_calculated_at' => 'datetime',
        'total_commission_amount' => 'decimal:2',
        'commission_recipients_count' => 'integer',
        'commission_distribution' => 'array',
        'requires_approval' => 'boolean',
        'is_approved' => 'boolean',
        'approved_by' => 'integer',
        'approved_at' => 'datetime',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'external_data' => 'array'
    ];

    // ====================================================================
    // 이벤트 리스너 (Event Listeners)
    // ====================================================================

    /**
     * 모델 부팅 - 파트너 매출 동기화 이벤트 등록
     */
    protected static function boot()
    {
        parent::boot();

        // 매출 생성 시: 파트너 매출 정보 동기화
        static::created(function ($sales) {
            $sales->syncPartnerSales('created');
        });

        // 매출 수정 시: 파트너 매출 정보 재동기화
        static::updated(function ($sales) {
            // 매출 금액이나 상태가 변경된 경우에만 동기화
            if ($sales->wasChanged(['amount', 'status', 'sales_date', 'partner_id'])) {
                $sales->syncPartnerSales('updated');
            }
        });

        // 매출 삭제 시: 파트너 매출 정보에서 차감
        static::deleted(function ($sales) {
            $sales->syncPartnerSales('deleted');
        });
    }

    // ====================================================================
    // 관계 (Relationships)
    // ====================================================================

    /**
     * 파트너 관계
     */
    public function partner()
    {
        return $this->belongsTo(PartnerUser::class, 'partner_id');
    }

    /**
     * 승인자 관계
     */
    public function approver()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    /**
     * 등록자 관계
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * 수정자 관계
     */
    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    /**
     * 커미션 내역들
     */
    public function commissions()
    {
        return $this->hasMany(PartnerCommission::class, 'order_id', 'id');
    }

    // ====================================================================
    // 스코프 (Scopes)
    // ====================================================================

    /**
     * 확정된 매출만 조회
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * 취소된 매출만 조회
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * 대기중인 매출만 조회
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * 커미션 계산이 완료된 매출만 조회
     */
    public function scopeCommissionCalculated($query)
    {
        return $query->where('commission_calculated', true);
    }

    /**
     * 커미션 계산이 필요한 매출만 조회
     */
    public function scopeNeedsCommissionCalculation($query)
    {
        return $query->where('status', 'confirmed')
                     ->where('commission_calculated', false);
    }

    /**
     * 특정 파트너의 매출 조회
     */
    public function scopeByPartner($query, $partnerId)
    {
        return $query->where('partner_id', $partnerId);
    }

    /**
     * 특정 기간의 매출 조회
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('sales_date', [$startDate, $endDate]);
    }

    /**
     * 특정 카테고리의 매출 조회
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * 최소 금액 이상의 매출 조회
     */
    public function scopeMinAmount($query, $amount)
    {
        return $query->where('amount', '>=', $amount);
    }

    // ====================================================================
    // 메서드 (Methods)
    // ====================================================================

    /**
     * 매출 확정 처리
     */
    public function confirm($approver = null)
    {
        $this->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'is_approved' => true,
            'approved_by' => $approver,
            'approved_at' => now(),
        ]);

        // 커미션 계산 큐에 추가 (비동기 처리)
        // dispatch(new CalculateCommissionJob($this));

        return $this;
    }

    /**
     * 매출 취소 처리
     */
    public function cancel($reason = null)
    {
        // 이미 커미션이 계산된 경우 역계산 수행
        if ($this->commission_calculated) {
            $this->reverseCommissionCalculation();
        }

        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'status_reason' => $reason,
        ]);

        return $this;
    }

    /**
     * 커미션 역계산 (매출 취소 시)
     */
    public function reverseCommissionCalculation()
    {
        if (!$this->commission_calculated) {
            return false;
        }

        // 기존 커미션 내역을 취소 상태로 변경
        $this->commissions()->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'notes' => '매출 취소로 인한 커미션 회수'
        ]);

        // 파트너들의 매출 실적 차감
        if ($this->commission_distribution) {
            foreach ($this->commission_distribution as $distribution) {
                if (isset($distribution['partner_id'])) {
                    $partner = PartnerUser::find($distribution['partner_id']);
                    if ($partner) {
                        $partner->decrement('total_sales', $distribution['sales_impact'] ?? 0);
                        $partner->decrement('earned_commissions', $distribution['commission_amount'] ?? 0);
                    }
                }
            }
        }

        $this->update([
            'commission_calculated' => false,
            'commission_calculated_at' => null,
            'total_commission_amount' => 0,
            'commission_recipients_count' => 0,
        ]);

        return true;
    }

    /**
     * 트리 구조 스냅샷 생성
     */
    public function createTreeSnapshot()
    {
        $partner = $this->partner;
        if (!$partner) {
            return null;
        }

        // 현재 트리 구조와 상위 파트너들 정보 수집
        $ancestors = $partner->ancestors();
        $snapshot = [
            'partner_id' => $partner->id,
            'partner_name' => $partner->name,
            'partner_tier' => $partner->tier->tier_code ?? null,
            'partner_type' => $partner->type->type_code ?? null,
            'level' => $partner->level,
            'tree_path' => $partner->tree_path,
            'ancestors' => $ancestors->map(function ($ancestor) {
                return [
                    'id' => $ancestor->id,
                    'name' => $ancestor->name,
                    'tier' => $ancestor->tier->tier_code ?? null,
                    'type' => $ancestor->type->type_code ?? null,
                    'level' => $ancestor->level,
                    'commission_rate' => $ancestor->personal_commission_rate ?? 0,
                ];
            })->toArray(),
            'created_at' => now()->toISOString(),
        ];

        $this->update([
            'tree_snapshot' => json_encode($snapshot),
            'partner_tier_at_time' => $partner->tier->tier_code ?? null,
            'partner_type_at_time' => $partner->type->type_code ?? null,
        ]);

        return $snapshot;
    }

    /**
     * 커미션 분배 상세 정보 반환
     */
    public function getCommissionDistributionDetailsAttribute()
    {
        if (!$this->commission_distribution) {
            return [];
        }

        return collect($this->commission_distribution)->map(function ($item) {
            return (object) $item;
        });
    }

    /**
     * 매출 상태 한글명 반환
     */
    public function getStatusKoreanAttribute()
    {
        $statuses = [
            'pending' => '대기중',
            'confirmed' => '확정',
            'cancelled' => '취소',
            'refunded' => '환불',
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * 포맷된 금액 반환
     */
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount) . '원';
    }

    /**
     * 포맷된 커미션 금액 반환
     */
    public function getFormattedCommissionAmountAttribute()
    {
        return number_format($this->total_commission_amount) . '원';
    }

    /**
     * 파트너 매출 정보 동기화
     *
     * @param string $eventType 이벤트 타입 (created, updated, deleted)
     * @return void
     */
    public function syncPartnerSales($eventType = 'created')
    {
        $partner = $this->partner;
        if (!$partner) {
            return;
        }

        // 확정된 매출만 파트너 통계에 반영
        $shouldCount = $this->status === 'confirmed';
        $originalAmount = $this->getOriginal('amount') ?? 0;
        $currentAmount = $this->amount ?? 0;

        try {
            DB::beginTransaction();

            // 이벤트에 따른 동기화 처리
            switch ($eventType) {
                case 'created':
                    if ($shouldCount) {
                        $this->incrementPartnerSales($partner, $currentAmount);
                        $this->incrementTeamSales($partner, $currentAmount);
                    }
                    break;

                case 'updated':
                    $wasConfirmed = $this->getOriginal('status') === 'confirmed';

                    if ($wasConfirmed && $shouldCount) {
                        // 상태는 확정으로 유지, 금액만 변경
                        $amountDiff = $currentAmount - $originalAmount;
                        if ($amountDiff != 0) {
                            $this->incrementPartnerSales($partner, $amountDiff);
                            $this->incrementTeamSales($partner, $amountDiff);
                        }
                    } elseif ($wasConfirmed && !$shouldCount) {
                        // 확정 -> 취소/대기: 기존 금액 차감
                        $this->decrementPartnerSales($partner, $originalAmount);
                        $this->decrementTeamSales($partner, $originalAmount);
                    } elseif (!$wasConfirmed && $shouldCount) {
                        // 대기/취소 -> 확정: 현재 금액 추가
                        $this->incrementPartnerSales($partner, $currentAmount);
                        $this->incrementTeamSales($partner, $currentAmount);
                    }
                    break;

                case 'deleted':
                    if ($shouldCount) {
                        $this->decrementPartnerSales($partner, $currentAmount);
                        $this->decrementTeamSales($partner, $currentAmount);
                    }
                    break;
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            // 로그 기록 (선택사항)
            \Log::error("파트너 매출 동기화 실패: " . $e->getMessage(), [
                'sales_id' => $this->id,
                'partner_id' => $partner->id,
                'event_type' => $eventType,
                'amount' => $currentAmount
            ]);
        }
    }

    /**
     * 파트너 개인 매출 증가
     *
     * @param PartnerUser $partner
     * @param float $amount
     * @return void
     */
    private function incrementPartnerSales(PartnerUser $partner, $amount)
    {
        if ($amount <= 0) return;

        // 현재 월 매출 업데이트
        if ($this->isCurrentMonth()) {
            $partner->increment('monthly_sales', $amount);
        }

        // 전체 매출 업데이트
        $partner->increment('total_sales', $amount);
    }

    /**
     * 파트너 개인 매출 차감
     *
     * @param PartnerUser $partner
     * @param float $amount
     * @return void
     */
    private function decrementPartnerSales(PartnerUser $partner, $amount)
    {
        if ($amount <= 0) return;

        // 현재 월 매출 업데이트
        if ($this->isCurrentMonth()) {
            $partner->decrement('monthly_sales', $amount);
        }

        // 전체 매출 업데이트
        $partner->decrement('total_sales', $amount);
    }

    /**
     * 상위 파트너들의 team_sales 증가
     *
     * @param PartnerUser $partner
     * @param float $amount
     * @return void
     */
    private function incrementTeamSales(PartnerUser $partner, $amount)
    {
        if ($amount <= 0) return;

        // 상위 파트너들 조회
        $ancestors = $this->getPartnerAncestors($partner);

        foreach ($ancestors as $ancestor) {
            $ancestor->increment('team_sales', $amount);
        }
    }

    /**
     * 상위 파트너들의 team_sales 차감
     *
     * @param PartnerUser $partner
     * @param float $amount
     * @return void
     */
    private function decrementTeamSales(PartnerUser $partner, $amount)
    {
        if ($amount <= 0) return;

        // 상위 파트너들 조회
        $ancestors = $this->getPartnerAncestors($partner);

        foreach ($ancestors as $ancestor) {
            $ancestor->decrement('team_sales', $amount);
        }
    }

    /**
     * 파트너의 상위 파트너들 조회
     *
     * @param PartnerUser $partner
     * @return \Illuminate\Support\Collection
     */
    private function getPartnerAncestors(PartnerUser $partner)
    {
        $ancestors = collect();

        if (!$partner->tree_path) {
            return $ancestors;
        }

        // tree_path에서 상위 파트너 ID들 추출
        $ancestorIds = array_filter(explode('/', trim($partner->tree_path, '/')));

        if (empty($ancestorIds)) {
            return $ancestors;
        }

        // 상위 파트너들 조회
        return PartnerUser::whereIn('id', $ancestorIds)->get();
    }

    /**
     * 현재 월의 매출인지 확인
     *
     * @return bool
     */
    private function isCurrentMonth()
    {
        if (!$this->sales_date) {
            return false;
        }

        $salesDate = \Carbon\Carbon::parse($this->sales_date);
        $now = now();

        return $salesDate->year === $now->year && $salesDate->month === $now->month;
    }

    // ====================================================================
    // 정적 메서드 (Static Methods)
    // ====================================================================

    /**
     * 파트너별 매출 통계
     */
    public static function getPartnerSalesStats($partnerId, $startDate = null, $endDate = null)
    {
        $query = static::byPartner($partnerId)->confirmed();

        if ($startDate && $endDate) {
            $query->byDateRange($startDate, $endDate);
        }

        return [
            'total_sales' => $query->sum('amount'),
            'total_count' => $query->count(),
            'total_commission' => $query->sum('total_commission_amount'),
            'average_sales' => $query->avg('amount'),
            'last_sales_date' => $query->max('sales_date'),
        ];
    }

    /**
     * 월별 매출 통계 (SQLite/MySQL 호환)
     */
    public static function getMonthlySalesStats($year = null, $month = null)
    {
        $year = $year ?: now()->year;
        $month = $month ?: now()->month;

        // SQLite와 MySQL 호환성을 위한 동적 쿼리
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}.driver");

        if ($connection === 'sqlite') {
            $yearMonth = sprintf('%04d-%02d', $year, $month);
            return static::confirmed()
                         ->whereRaw("strftime('%Y-%m', sales_date) = ?", [$yearMonth])
                         ->selectRaw('
                             COUNT(*) as sales_count,
                             SUM(amount) as total_amount,
                             SUM(total_commission_amount) as total_commission,
                             AVG(amount) as average_amount,
                             COUNT(DISTINCT partner_id) as unique_partners
                         ')
                         ->first();
        } else {
            return static::confirmed()
                         ->whereYear('sales_date', $year)
                         ->whereMonth('sales_date', $month)
                         ->selectRaw('
                             COUNT(*) as sales_count,
                             SUM(amount) as total_amount,
                             SUM(total_commission_amount) as total_commission,
                             AVG(amount) as average_amount,
                             COUNT(DISTINCT partner_id) as unique_partners
                         ')
                         ->first();
        }
    }

    /**
     * 카테고리별 매출 통계
     */
    public static function getCategorySalesStats($startDate = null, $endDate = null)
    {
        $query = static::confirmed()
                       ->select('category')
                       ->selectRaw('
                           COUNT(*) as sales_count,
                           SUM(amount) as total_amount,
                           AVG(amount) as average_amount
                       ')
                       ->groupBy('category');

        if ($startDate && $endDate) {
            $query->byDateRange($startDate, $endDate);
        }

        return $query->get();
    }
}