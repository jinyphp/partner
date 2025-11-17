# 🏆 Partner Tiers (파트너 등급 시스템)

## 📋 개요

파트너 등급 시스템은 파트너의 성과와 경력에 따라 차별화된 혜택과 권한을 제공하는 계층형 시스템입니다.
성과에 따른 자동 승급/강등과 등급별 특화 서비스를 통해 파트너의 지속적인 성장을 지원합니다.

## 🎯 핵심 기능

### 1. 계층형 등급 구조
- **Bronze** (브론즈): 신규 파트너, 기본 등급
- **Silver** (실버): 초급 파트너, 안정적 성과
- **Gold** (골드): 중급 파트너, 우수한 성과
- **Platinum** (플래티넘): 고급 파트너, 탁월한 성과
- **Diamond** (다이아몬드): 최상위 파트너, 리더십 발휘

### 2. 등급별 혜택 시스템
- **커미션율 차등**: 등급이 높을수록 더 높은 수수료
- **추가 보너스**: 등급별 성과 달성 시 특별 보상
- **우선 지원**: 고등급 파트너 우선 지원 서비스
- **교육 기회**: 등급별 맞춤 교육 프로그램
- **네트워킹**: 등급별 전용 커뮤니티 및 이벤트

### 3. 자동 등급 관리
- **승급 조건**: 매출, 고객 만족도, 활동 지표 기반
- **강등 조건**: 성과 부진 시 자동 강등 시스템
- **유예 기간**: 등급 변동 시 적응 기간 제공
- **재승급**: 성과 회복 시 빠른 등급 회복 지원

## 🏗️ 데이터 구조

### 등급 기본 정보
```sql
id              -- 고유 식별자
tier_code       -- 등급 코드 (BRONZE, SILVER, GOLD, PLATINUM, DIAMOND)
tier_name       -- 등급 명칭
tier_level      -- 등급 순서 (1: Bronze ~ 5: Diamond)
description     -- 등급 설명
```

### 승급 조건
```sql
min_monthly_sales    -- 최소 월 매출 (원)
min_total_sales      -- 최소 총 매출 (원)
min_months_active    -- 최소 활동 기간 (월)
min_customer_score   -- 최소 고객 만족도
min_team_size        -- 최소 팀 크기 (추천인 수)
```

### 등급별 혜택
```sql
commission_bonus_rate    -- 커미션 보너스율 (%)
monthly_bonus_amount     -- 월 고정 보너스 (원)
referral_bonus_rate      -- 추천 보너스율 (%)
priority_support_level   -- 지원 우선순위 (1-5)
```

## 💼 비즈니스 로직

### 1. 등급 산정 알고리즘
```php
function calculateTierEligibility($partner) {
    $criteria = [
        'monthly_sales' => $partner->getMonthlyAverageSales(6), // 6개월 평균
        'total_sales' => $partner->getTotalSales(),
        'months_active' => $partner->getActiveMonths(),
        'customer_score' => $partner->getAverageCustomerScore(),
        'team_size' => $partner->getReferralCount()
    ];

    foreach (PartnerTier::orderByDesc('tier_level')->get() as $tier) {
        if ($this->meetsCriteria($criteria, $tier)) {
            return $tier;
        }
    }

    return PartnerTier::where('tier_code', 'BRONZE')->first();
}
```

### 2. 등급별 혜택 적용
```php
function applyTierBenefits($partner, $salesAmount) {
    $tier = $partner->currentTier;

    // 기본 커미션 + 등급 보너스
    $baseCommission = $salesAmount * 0.03; // 3% 기본
    $tierBonus = $baseCommission * ($tier->commission_bonus_rate / 100);

    return $baseCommission + $tierBonus;
}
```

## 📊 등급별 상세 기준

### Bronze (브론즈) - 신규 파트너
```json
{
  "requirements": {
    "min_monthly_sales": 0,
    "min_total_sales": 0,
    "min_months_active": 0,
    "min_customer_score": 0,
    "min_team_size": 0
  },
  "benefits": {
    "commission_bonus_rate": 0,
    "monthly_bonus_amount": 0,
    "referral_bonus_rate": 1.0,
    "priority_support_level": 1
  }
}
```

### Silver (실버) - 초급 파트너
```json
{
  "requirements": {
    "min_monthly_sales": 1000000,
    "min_total_sales": 3000000,
    "min_months_active": 3,
    "min_customer_score": 70,
    "min_team_size": 0
  },
  "benefits": {
    "commission_bonus_rate": 10,
    "monthly_bonus_amount": 50000,
    "referral_bonus_rate": 1.5,
    "priority_support_level": 2
  }
}
```

### Gold (골드) - 중급 파트너
```json
{
  "requirements": {
    "min_monthly_sales": 3000000,
    "min_total_sales": 10000000,
    "min_months_active": 6,
    "min_customer_score": 80,
    "min_team_size": 2
  },
  "benefits": {
    "commission_bonus_rate": 20,
    "monthly_bonus_amount": 150000,
    "referral_bonus_rate": 2.0,
    "priority_support_level": 3
  }
}
```

## 🔄 등급 관리 프로세스

### 1. 월별 등급 평가
1. 매월 1일 자동 실행
2. 지난 6개월 성과 데이터 수집
3. 승급/강등 조건 확인
4. 등급 변동 처리 및 통보

### 2. 즉시 승급 시스템
- 목표를 크게 초과 달성한 경우
- 특별 프로젝트 성공 시
- 추천인 수가 급격히 증가한 경우

## 🔗 연관 기능

- **Partner Types**: 유형별 등급 기준 차별화
- **Partner Users**: 개별 파트너의 현재 등급 관리
- **Partner Commissions**: 등급별 차등 커미션 적용
- **Partner Performance Metrics**: 등급 산정 기초 데이터 제공

---
*성과 기반의 공정하고 투명한 파트너 등급 시스템*