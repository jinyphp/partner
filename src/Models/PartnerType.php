<?php

namespace Jiny\Partner\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartnerType extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\PartnerTypeFactory::new();
    }

    protected $table = 'partner_types';

    protected $fillable = [
        'type_code',
        'type_name',
        'description',
        'icon',
        'is_active',
        'sort_order',
        'color',
        'specialties',
        'required_skills',
        'certifications',
        'target_sales_amount',
        'target_support_cases',
        'commission_bonus_rate',
        'permissions',
        'access_levels',
        'training_requirements',
        'training_hours_required',
        'certification_valid_until',
        'admin_notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'specialties' => 'array',
        'required_skills' => 'array',
        'certifications' => 'array',
        'target_sales_amount' => 'decimal:2',
        'target_support_cases' => 'integer',
        'commission_bonus_rate' => 'decimal:2',
        'permissions' => 'array',
        'access_levels' => 'array',
        'training_requirements' => 'array',
        'training_hours_required' => 'integer',
        'certification_valid_until' => 'date',
        'created_by' => 'integer',
        'updated_by' => 'integer'
    ];

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

    /**
     * 이 타입을 가진 파트너들
     */
    public function partners()
    {
        return $this->hasMany(PartnerUser::class, 'partner_type_id');
    }

    /**
     * 활성 상태인 타입만 조회
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 정렬 순서대로 조회
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('type_name');
    }

    /**
     * 특정 전문 분야를 가진 타입 조회
     */
    public function scopeWithSpecialty($query, $specialty)
    {
        return $query->whereJsonContains('specialties', $specialty);
    }

    /**
     * 특정 권한을 가진 타입 조회
     */
    public function scopeWithPermission($query, $permission)
    {
        return $query->whereJsonContains('permissions', $permission);
    }

    /**
     * 타입별 통계 정보 가져오기
     */
    public function getStatsAttribute()
    {
        $partners = $this->partners();

        return [
            'total_partners' => $partners->count(),
            'active_partners' => $partners->where('status', 'active')->count(),
            'avg_performance' => $partners->avg('monthly_sales') ?? 0,
            'total_sales' => $partners->sum('total_sales') ?? 0
        ];
    }

    /**
     * 전문 분야 목록을 문자열로 반환
     */
    public function getSpecialtiesStringAttribute()
    {
        if (!$this->specialties || !is_array($this->specialties)) {
            return '';
        }

        return implode(', ', $this->specialties);
    }

    /**
     * 필수 스킬 목록을 문자열로 반환
     */
    public function getRequiredSkillsStringAttribute()
    {
        if (!$this->required_skills || !is_array($this->required_skills)) {
            return '';
        }

        return implode(', ', $this->required_skills);
    }

    /**
     * 색상이 설정되지 않은 경우 기본 색상 반환
     */
    public function getColorAttribute($value)
    {
        return $value ?: '#007bff';
    }

    /**
     * 아이콘이 설정되지 않은 경우 기본 아이콘 반환
     */
    public function getIconAttribute($value)
    {
        return $value ?: 'fe-users';
    }
}