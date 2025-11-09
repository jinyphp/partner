<?php

namespace Jiny\Partner\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommissionRecord extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\Jiny\Partner\Models\CommissionRecordFactory::new();
    }

    protected $table = 'partner_commissions';

    protected $fillable = [
        'partner_id',
        'commission_type',
        'amount',
        'currency',
        'status',
        'description',
        'notes',
        'source_type',
        'source_id',
        'source_data',
        'calculation_data',
        'period_year',
        'period_month',
        'period_week',
        'processed_at',
        'processed_by',
        'approved_at',
        'approved_by',
        'paid_at',
        'payment_method',
        'payment_reference',
    ];

    protected $casts = [
        'source_data' => 'array',
        'calculation_data' => 'array',
        'processed_at' => 'datetime',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    /**
     * Get the partner that owns the commission
     */
    public function partner()
    {
        return $this->belongsTo(PartnerUser::class, 'partner_id');
    }

    /**
     * Scope for pending commissions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved commissions
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for paid commissions
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope for active commissions (not cancelled)
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['cancelled']);
    }
}