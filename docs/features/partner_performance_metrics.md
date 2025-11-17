# 파트너 성과 지표 시스템

## 📊 개요

파트너 성과 지표 시스템은 파트너의 성과를 다면적으로 측정하고 분석하는 종합 성과 관리 시스템입니다. 매출, 활동, 품질, 네트워크 등 4대 영역의 정량적 지표를 시계열로 관리하여 체계적인 성과 평가와 분석을 지원합니다.

## 🎯 핵심 기능

- ✅ **4대 성과 영역 종합 측정** - 매출, 활동, 품질, 네트워크
- ✅ **기간별 성과 추이 분석** - 주간, 월간, 분기, 연간
- ✅ **파트너별 성과 순위 및 벤치마킹**
- ✅ **목표 대비 실적 달성률 추적**
- ✅ **성장률 및 효율성 지표 자동 계산**
- ✅ **성과 기반 등급 승급 기준 제공**
- ✅ **상세 메트릭 데이터 유연한 확장 지원**

## 📋 데이터베이스 스키마

### 기본 정보

| 필드 | 타입 | 설명 |
|------|------|------|
| id | bigint | 고유 식별자 |
| partner_id | bigint | 파트너 ID (외래키) |
| period_start | date | 측정 시작일 |
| period_end | date | 측정 종료일 |
| period_type | enum | 기간 유형 (weekly/monthly/quarterly/yearly) |
| created_at | timestamp | 생성일시 |
| updated_at | timestamp | 수정일시 |

### 💰 매출 메트릭

| 필드 | 타입 | 설명 |
|------|------|------|
| total_sales | decimal(15,2) | 총 매출액 |
| commission_earned | decimal(15,2) | 수수료 수익 |
| deals_closed | integer | 성사된 거래 수 |
| average_deal_size | decimal(15,2) | 평균 거래 규모 |

### 🚀 활동 메트릭

| 필드 | 타입 | 설명 |
|------|------|------|
| leads_generated | integer | 생성된 리드 수 |
| customers_acquired | integer | 신규 고객 확보 수 |
| support_tickets_resolved | integer | 지원 티켓 해결 수 |
| training_sessions_conducted | integer | 교육 세션 진행 수 |

### ⭐ 품질 메트릭

| 필드 | 타입 | 설명 |
|------|------|------|
| customer_satisfaction_score | decimal(3,2) | 고객 만족도 점수 |
| response_time_hours | decimal(8,2) | 평균 응답 시간 (시간) |
| complaints_received | integer | 접수된 불만 건수 |
| task_completion_rate | decimal(5,2) | 작업 완료율 (%) |

### 🌐 네트워크 메트릭

| 필드 | 타입 | 설명 |
|------|------|------|
| referrals_made | integer | 추천한 파트너 수 |
| team_members_managed | integer | 관리 팀원 수 |
| team_performance_bonus | decimal(15,2) | 팀 성과 보너스 |

### 📈 계산된 지표

| 필드 | 타입 | 설명 |
|------|------|------|
| efficiency_score | decimal(5,2) | 효율성 점수 (매출/활동 비율) |
| growth_rate | decimal(5,2) | 전년 동기 대비 성장률 (%) |
| rank_in_tier | integer | 동일 등급 내 순위 |

### 📊 확장 데이터

| 필드 | 타입 | 설명 |
|------|------|------|
| detailed_metrics | json | 상세 메트릭 데이터 |
| goals_vs_actual | json | 목표 대비 실적 |

## 🔄 기간 유형

시스템은 다양한 기간 단위로 성과를 측정합니다:

- **weekly**: 주간 단위 (7일)
- **monthly**: 월간 단위 (1개월)
- **quarterly**: 분기 단위 (3개월)
- **yearly**: 연간 단위 (12개월)

## 📊 JSON 데이터 구조

### detailed_metrics 예시

```json
{
  "custom_kpis": {
    "client_retention_rate": 95.5,
    "project_success_rate": 87.2,
    "innovation_score": 4.3
  },
  "time_metrics": {
    "average_project_duration": 45.6,
    "client_onboarding_time": 2.1
  },
  "financial_details": {
    "recurring_revenue": 15000.00,
    "one_time_revenue": 5000.00,
    "expense_ratio": 12.5
  }
}
```

### goals_vs_actual 예시

```json
{
  "sales_target": {
    "goal": 50000.00,
    "actual": 47500.00,
    "achievement_rate": 95.0
  },
  "customer_target": {
    "goal": 20,
    "actual": 18,
    "achievement_rate": 90.0
  },
  "satisfaction_target": {
    "goal": 4.5,
    "actual": 4.7,
    "achievement_rate": 104.4
  }
}
```

## 🔗 데이터베이스 관계

- **partner_users → partner_performance_metrics** (1:N)
  - 한 파트너는 여러 기간의 성과 기록을 가질 수 있음
  - 외래키: `partner_id`
  - 관계: `CASCADE DELETE`

## 📈 인덱스 최적화

### 복합 인덱스

1. **기본 조회 인덱스**
   ```sql
   INDEX (partner_id, period_start, period_end)
   ```

2. **기간별 조회 인덱스**
   ```sql
   INDEX (period_type, period_start)
   ```

3. **매출 기준 정렬 인덱스**
   ```sql
   INDEX (total_sales, period_start)
   ```

### 유니크 제약조건

```sql
UNIQUE (partner_id, period_start, period_end, period_type)
```
- 동일 파트너의 동일 기간/유형 중복 방지

## 💡 활용 사례

### 1. 파트너 성과 대시보드

```php
// 월간 성과 조회
$monthlyMetrics = DB::table('partner_performance_metrics')
    ->where('partner_id', $partnerId)
    ->where('period_type', 'monthly')
    ->orderBy('period_start', 'desc')
    ->limit(12)
    ->get();
```

### 2. 등급별 순위 계산

```php
// 분기별 매출 순위
$rankings = DB::table('partner_performance_metrics')
    ->where('period_type', 'quarterly')
    ->where('period_start', '2025-01-01')
    ->orderBy('total_sales', 'desc')
    ->get();
```

### 3. 성장률 분석

```php
// 전년 동기 대비 성장률
$growthAnalysis = DB::table('partner_performance_metrics as current')
    ->join('partner_performance_metrics as previous', function($join) {
        $join->on('current.partner_id', '=', 'previous.partner_id')
             ->whereRaw('YEAR(current.period_start) = YEAR(previous.period_start) + 1');
    })
    ->select([
        'current.partner_id',
        'current.total_sales as current_sales',
        'previous.total_sales as previous_sales',
        DB::raw('((current.total_sales - previous.total_sales) / previous.total_sales * 100) as growth_rate')
    ])
    ->get();
```

## 🎯 성과 평가 기준

### 매출 성과 등급

- **S급**: 월 5,000만원 이상
- **A급**: 월 3,000만원 이상
- **B급**: 월 1,000만원 이상
- **C급**: 월 500만원 이상

### 효율성 점수 계산

```
효율성 점수 = (총 매출 / (리드 생성 + 고객 확보 + 지원 해결)) × 100
```

### 품질 평가 기준

- **고객 만족도**: 5.0점 만점
- **응답 시간**: 24시간 이내 권장
- **작업 완료율**: 95% 이상 우수

## 🔧 관리 기능

### 자동 집계

시스템은 다음 데이터를 자동으로 집계합니다:

- 일별 거래 데이터 → 주간/월간 매출 메트릭
- 고객 지원 활동 → 품질 메트릭
- 팀 관리 활동 → 네트워크 메트릭

### 수동 입력

관리자가 직접 입력하는 데이터:

- 고객 만족도 조사 결과
- 교육 세션 진행 기록
- 특별 프로젝트 성과

## 📊 리포트 생성

### 월간 성과 보고서

- 4대 영역별 성과 요약
- 목표 대비 달성률
- 동료 대비 순위
- 개선 제안 사항

### 분기별 트렌드 분석

- 성장률 추이
- 효율성 변화
- 품질 지표 개선도

---

> **참고**: 이 시스템은 파트너의 종합적인 성과를 객관적으로 평가하고, 지속적인 성장을 지원하기 위한 데이터 기반 의사결정을 가능하게 합니다.