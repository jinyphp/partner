<?php

namespace Jiny\Partner\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * 파트너 타입 모델
 *
 * =======================================================================
 * 🏷️ 파트너 분류 시스템의 핵심 모델
 * =======================================================================
 *
 * 파트너들을 역할과 전문성에 따라 분류하는 타입 시스템을 정의합니다.
 * 각 타입은 고유한 특성, 수수료 구조, 성과 기준을 가집니다.
 *
 * 📋 주요 기능:
 * - 파트너 분류 및 관리
 * - 타입별 수수료 체계 설정
 * - 성과 평가 기준 정의
 * - 비용 구조 관리
 * - UI 표시 설정
 *
 * 🔗 연관 관계:
 * - PartnerUser (1:N) - 이 타입을 가진 파트너들
 * - 파트너 티어 시스템과의 연동
 *
 * @package Jiny\Partner\Models
 * @author Jiny Framework Team
 * @since 2025-11-02
 */
class PartnerType extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * 모델 팩토리 지정
     */
    protected static function newFactory()
    {
        return \Jiny\Partner\Database\Factories\PartnerTypeFactory::new();
    }

    /**
     * 테이블 명시적 지정
     */
    protected $table = 'partner_types';

    // =============================================================
    // 🔧 모델 기본 설정
    // =============================================================

    /**
     * 대량 할당 가능한 필드들
     *
     * 📌 구조:
     * - 기본 정보: type_code, type_name, description
     * - UI 설정: icon, color, sort_order, is_active
     * - 전문성: specialties, required_skills
     * - 수수료: commission_type, commission_rate, commission_amount
     * - 비용: registration_fee, maintenance_fee 등
     * - 성과 기준: min_baseline_* 필드들
     * - 관리: admin_notes, created_by, updated_by
     */
    protected $fillable = [
        // =============================================================
        // 🏷️ 타입 기본 정보
        // =============================================================
        'type_code',                    // 타입 코드 (SALES, TECH_SUPPORT 등)
        'type_name',                    // 타입 표시명 (한글)
        'description',                  // 타입 상세 설명 및 역할

        // =============================================================
        // 🎨 UI 표시 설정
        // =============================================================
        'icon',                         // 아이콘 클래스명 (fe-users 등)
        'color',                        // 브랜드 색상 (HEX 코드)
        'sort_order',                   // 목록 정렬 순서
        'is_active',                    // 활성 상태
        'partner_tiers_count',          // 이 타입을 허용하는 파트너 티어 수 (캐시)

        // =============================================================
        // 🎯 전문성 및 역량 정의
        // =============================================================
        'specialties',                  // 전문 분야 목록 (JSON 배열)
        'required_skills',              // 필수 스킬 목록 (JSON 배열)

        // =============================================================
        // 💰 수수료 체계 설정
        // =============================================================
        'default_commission_type',      // 기본 수수료 타입 (percentage/fixed_amount)
        'default_commission_rate',      // 기본 수수료율 (퍼센트)
        'default_commission_amount',    // 고정 수수료 금액
        'commission_notes',             // 수수료 관련 특별 조건

        // =============================================================
        // 💳 파트너십 비용 구조
        // =============================================================
        'registration_fee',             // 파트너 등록비
        'monthly_maintenance_fee',      // 월 유지비
        'annual_maintenance_fee',       // 연 유지비
        'fee_waiver_available',         // 비용 면제 가능 여부
        'fee_structure_notes',          // 비용 구조 특별 조건

        // =============================================================
        // 📈 성과 평가 기준 (최소 요구 수준)
        // =============================================================
        'min_baseline_sales',           // 최소 매출 기준 (월별)
        'min_baseline_cases',           // 최소 처리 건수 (월별)
        'min_baseline_revenue',         // 최소 순수익 기준 (월별)
        'min_baseline_clients',         // 최소 고객 수
        'baseline_quality_score',       // 최소 품질 점수 (고객 만족도)

        // =============================================================
        // 🔧 관리 정보
        // =============================================================
        'admin_notes',                  // 관리자 전용 내부 메모
        'created_by',                   // 타입 생성자 (관리자 ID)
        'updated_by'                    // 최종 수정자 (관리자 ID)
    ];

    /**
     * 필드별 타입 캐스팅 설정
     *
     * 📌 JSON 필드 자동 변환:
     * - specialties: 전문 분야 배열
     * - required_skills: 필수 스킬 배열
     *
     * 📌 숫자형 필드 정밀도:
     * - decimal:2 → 소수점 둘째자리까지
     * - decimal:4 → 소수점 넷째자리까지 (수수료율 등)
     */
    protected $casts = [
        // =============================================================
        // 🎨 UI 설정 캐스팅
        // =============================================================
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'partner_tiers_count' => 'integer',

        // =============================================================
        // 🎯 전문성 JSON 캐스팅
        // =============================================================
        'specialties' => 'array',       // 전문 분야 JSON 배열
        'required_skills' => 'array',   // 필수 스킬 JSON 배열

        // =============================================================
        // 💰 수수료 관련 캐스팅
        // =============================================================
        'default_commission_rate' => 'decimal:2',    // 수수료율 (0.00%)
        'default_commission_amount' => 'decimal:2',  // 고정 금액

        // =============================================================
        // 💳 비용 관련 캐스팅
        // =============================================================
        'registration_fee' => 'decimal:2',           // 등록비
        'monthly_maintenance_fee' => 'decimal:2',    // 월 유지비
        'annual_maintenance_fee' => 'decimal:2',     // 연 유지비
        'fee_waiver_available' => 'boolean',         // 비용 면제 가능 여부

        // =============================================================
        // 📈 성과 기준 캐스팅
        // =============================================================
        'min_baseline_sales' => 'decimal:2',         // 최소 매출
        'min_baseline_cases' => 'integer',           // 최소 건수
        'min_baseline_revenue' => 'decimal:2',       // 최소 순수익
        'min_baseline_clients' => 'integer',         // 최소 고객 수
        'baseline_quality_score' => 'decimal:2',     // 품질 점수

        // =============================================================
        // 🔧 관리 정보 캐스팅
        // =============================================================
        'created_by' => 'integer',
        'updated_by' => 'integer'
    ];

    // =============================================================
    // 🔗 Eloquent 관계 정의
    // =============================================================

    /**
     * 타입 생성자 (관리자)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * 타입 최종 수정자 (관리자)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    /**
     * 이 타입을 가진 파트너들
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function partners()
    {
        return $this->hasMany(PartnerUser::class, 'partner_type_id');
    }

    // =============================================================
    // 🔍 쿼리 스코프 (편의 메소드)
    // =============================================================

    /**
     * 활성 상태인 타입만 조회
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 정렬 순서대로 조회 (낮은 숫자 우선)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')
                    ->orderBy('id', 'asc');
    }

    /**
     * 특정 수수료 타입으로 조회
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type percentage|fixed_amount
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCommissionType($query, $type)
    {
        return $query->where('default_commission_type', $type);
    }

    /**
     * 수수료율 범위로 조회 (퍼센트 타입만)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $min 최소 수수료율
     * @param float $max 최대 수수료율
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCommissionRate($query, $min, $max = null)
    {
        $query = $query->where('default_commission_type', 'percentage')
                      ->where('default_commission_rate', '>=', $min);

        if ($max !== null) {
            $query->where('default_commission_rate', '<=', $max);
        }

        return $query;
    }

    /**
     * 무료 타입 조회 (등록비 없음)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFreeRegistration($query)
    {
        return $query->where('registration_fee', 0);
    }

    // =============================================================
    // 🛠️ 유틸리티 메소드
    // =============================================================

    /**
     * 파트너 티어 수 캐시 업데이트
     *
     * partner_tiers 테이블에서 이 타입을 허용하는 티어 수를 계산하여
     * 캐시 필드에 저장합니다.
     *
     * @return int 업데이트된 티어 수
     */
    public function updatePartnerTiersCount()
    {
        $count = \DB::table('partner_tiers')
            ->where('is_active', true)
            ->where('allowed_types', 'LIKE', '%"' . $this->id . '"%')
            ->count();

        $this->update(['partner_tiers_count' => $count]);

        return $count;
    }

    /**
     * 모든 파트너 타입의 티어 수 캐시 업데이트
     *
     * 전체 파트너 타입에 대해 티어 수를 재계산합니다.
     * 파트너 티어 설정 변경 후 실행해야 합니다.
     *
     * @return int 업데이트된 타입 수
     */
    public static function updateAllPartnerTiersCounts()
    {
        $types = self::all();

        foreach ($types as $type) {
            $type->updatePartnerTiersCount();
        }

        return $types->count();
    }

    /**
     * 타입별 수수료 정보 요약
     *
     * @return array
     */
    public function getCommissionSummary()
    {
        return [
            'type' => $this->default_commission_type,
            'rate' => $this->default_commission_rate,
            'amount' => $this->default_commission_amount,
            'display' => $this->default_commission_type === 'percentage'
                ? $this->default_commission_rate . '%'
                : number_format($this->default_commission_amount) . '원',
            'notes' => $this->commission_notes
        ];
    }

    /**
     * 타입별 비용 구조 요약
     *
     * @return array
     */
    public function getFeeSummary()
    {
        return [
            'registration' => $this->registration_fee,
            'monthly' => $this->monthly_maintenance_fee,
            'annual' => $this->annual_maintenance_fee,
            'waiver_available' => $this->fee_waiver_available,
            'total_first_year' => $this->registration_fee + $this->annual_maintenance_fee,
            'notes' => $this->fee_structure_notes
        ];
    }

    /**
     * 성과 기준 요약
     *
     * @return array
     */
    public function getPerformanceCriteria()
    {
        return [
            'sales' => [
                'amount' => $this->min_baseline_sales,
                'display' => number_format($this->min_baseline_sales) . '원/월'
            ],
            'cases' => [
                'count' => $this->min_baseline_cases,
                'display' => number_format($this->min_baseline_cases) . '건/월'
            ],
            'revenue' => [
                'amount' => $this->min_baseline_revenue,
                'display' => number_format($this->min_baseline_revenue) . '원/월'
            ],
            'clients' => [
                'count' => $this->min_baseline_clients,
                'display' => number_format($this->min_baseline_clients) . '명'
            ],
            'quality' => [
                'score' => $this->baseline_quality_score,
                'display' => $this->baseline_quality_score . '점/100점'
            ]
        ];
    }

    /**
     * 타입 표시용 전체 정보
     *
     * 관리자 페이지나 API 응답에서 사용할 포맷팅된 정보를 반환합니다.
     *
     * @return array
     */
    public function getDisplayInfo()
    {
        return [
            'id' => $this->id,
            'code' => $this->type_code,
            'name' => $this->type_name,
            'description' => $this->description,
            'icon' => $this->icon,
            'color' => $this->color,
            'is_active' => $this->is_active,
            'partners_count' => $this->partners()->count(),
            'tiers_count' => $this->partner_tiers_count,
            'specialties' => $this->specialties ?? [],
            'required_skills' => $this->required_skills ?? [],
            'commission' => $this->getCommissionSummary(),
            'fees' => $this->getFeeSummary(),
            'criteria' => $this->getPerformanceCriteria(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }

    // =============================================================
    // 🎨 접근자 (Accessors)
    // =============================================================

    /**
     * 컬러 코드 기본값 보장
     *
     * @param string|null $value
     * @return string
     */
    public function getColorAttribute($value)
    {
        return $value ?: '#007bff';
    }

    /**
     * 아이콘 기본값 보장
     *
     * @param string|null $value
     * @return string
     */
    public function getIconAttribute($value)
    {
        return $value ?: 'fe-users';
    }

    /**
     * 전문 분야 안전 접근
     *
     * @param string|null $value
     * @return array
     */
    public function getSpecialtiesAttribute($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($value) ? $value : [];
    }

    /**
     * 필수 스킬 안전 접근
     *
     * @param string|null $value
     * @return array
     */
    public function getRequiredSkillsAttribute($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($value) ? $value : [];
    }

    // =============================================================
    // 🔄 설정자 (Mutators)
    // =============================================================

    /**
     * 수수료 타입 변경시 자동 조정
     *
     * @param string $value
     */
    public function setDefaultCommissionTypeAttribute($value)
    {
        $this->attributes['default_commission_type'] = $value;

        // 타입에 따라 반대 필드 초기화
        if ($value === 'percentage') {
            $this->attributes['default_commission_amount'] = 0;
        } elseif ($value === 'fixed_amount') {
            $this->attributes['default_commission_rate'] = 0;
        }
    }

    /**
     * 타입 코드 대문자 변환
     *
     * @param string $value
     */
    public function setTypeCodeAttribute($value)
    {
        $this->attributes['type_code'] = strtoupper($value);
    }

    /**
     * 컬러 코드 검증 및 보정
     *
     * @param string $value
     */
    public function setColorAttribute($value)
    {
        // HEX 코드 검증 및 # 접두사 추가
        if (!empty($value)) {
            $value = ltrim($value, '#');
            if (preg_match('/^[a-fA-F0-9]{6}$/', $value)) {
                $this->attributes['color'] = '#' . $value;
            } else {
                $this->attributes['color'] = '#007bff'; // 기본값
            }
        } else {
            $this->attributes['color'] = '#007bff';
        }
    }
}