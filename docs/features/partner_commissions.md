# 💰 Partner Commissions (파트너 커미션 관리)

## 📋 개요

파트너 커미션 시스템은 MLM(다단계 마케팅) 구조를 기반으로 한 수수료 계산 및 관리 시스템입니다.
개인 판매, 그룹 성과, 리더십 보너스 등 다양한 형태의 커미션을 자동으로 계산하고 지급 관리합니다.

## 🎯 핵심 기능

### 1. 다층 커미션 구조 (MLM)
- **개인 커미션**: 직접 판매로 인한 기본 수수료
- **그룹 커미션**: 하위 조직 매출에 따른 수수료
- **오버라이드 커미션**: 리더십 단계별 추가 수수료
- **매칭 보너스**: 양쪽 다리 균형 달성 시 특별 보너스
- **랭크 보너스**: 등급 달성 시 월별 고정 보너스

### 2. 실시간 커미션 계산
- **즉시 계산**: 매출 발생과 동시에 커미션 자동 계산
- **소급 적용**: 등급 변동 시 과거 거래에 대한 소급 조정
- **배치 처리**: 대량 거래에 대한 효율적인 일괄 처리
- **정확성 검증**: 이중 계산 방지 및 오류 검증 시스템

### 3. 지급 상태 관리
- **보류 상태**: 계산 완료, 지급 대기
- **승인 상태**: 관리자 승인 완료
- **지급 완료**: 실제 지급 처리 완료
- **취소/조정**: 오류 발견 시 취소 또는 금액 조정

### 4. 투명성 및 추적
- **상세 내역**: 커미션 발생 근거 및 계산 과정 기록
- **지급 이력**: 모든 지급 내역 추적 관리
- **세금 처리**: 원천징수 및 세금 계산 자동화
- **리포팅**: 개인별/팀별 커미션 분석 리포트

## 🏗️ 데이터 구조

### 커미션 기본 정보
```sql
id                      -- 고유 식별자
partner_id              -- 커미션 수령 파트너 ID
sales_id               -- 관련 매출 ID (있는 경우)
commission_type         -- 커미션 유형 (personal, group, override, bonus)
calculation_basis       -- 계산 기준 (sales_amount, volume, count)
```

### 금액 및 비율
```sql
base_amount            -- 기준 금액 (매출액 등)
commission_rate        -- 적용된 커미션율 (%)
commission_amount      -- 계산된 커미션 금액
currency              -- 통화 (KRW, USD 등)
exchange_rate         -- 환율 (해외 거래 시)
```

### 지급 관리
```sql
status                -- 상태 (pending, approved, paid, cancelled)
earned_at            -- 발생 일시 (시분초 포함)
approved_at          -- 승인 일시
paid_at              -- 지급 일시
payment_reference    -- 지급 참조번호
```

### 세금 및 공제
```sql
tax_rate             -- 세율 (%)
tax_amount           -- 세금 금액
deduction_amount     -- 기타 공제액
net_amount           -- 실지급액 (총액 - 세금 - 공제)
```

## 💼 비즈니스 로직

### 1. 개인 커미션 계산
```php
function calculatePersonalCommission($sale) {
    $partner = $sale->partner;
    $partnerType = $partner->partnerType;
    $partnerTier = $partner->partnerTier;

    // 기본 커미션율 계산 (유형 + 등급)
    $baseRate = $partnerType->base_commission_rate;
    $tierBonus = $partnerTier->commission_bonus_rate;
    $finalRate = $baseRate + $tierBonus;

    // 성과에 따른 추가 보너스
    if ($partner->monthly_performance_score > 90) {
        $finalRate += 1.0; // 1% 추가
    }

    $commissionAmount = $sale->amount * ($finalRate / 100);

    return $this->createCommission([
        'partner_id' => $partner->id,
        'sales_id' => $sale->id,
        'commission_type' => 'personal',
        'commission_rate' => $finalRate,
        'commission_amount' => $commissionAmount
    ]);
}
```

### 2. 그룹 커미션 계산
```php
function calculateGroupCommissions($sale) {
    $partner = $sale->partner;
    $upline = $this->getUplinePartners($partner, 7); // 7세대까지
    $commissions = [];

    foreach ($upline as $level => $uplinePartner) {
        // 세대별 커미션율 적용
        $generationRates = [
            1 => 2.0,  // 1세대: 2%
            2 => 1.5,  // 2세대: 1.5%
            3 => 1.0,  // 3세대: 1%
            4 => 0.7,  // 4-7세대: 0.7-0.4%
            5 => 0.6,
            6 => 0.5,
            7 => 0.4
        ];

        $rate = $generationRates[$level] ?? 0;
        if ($rate > 0 && $this->isQualified($uplinePartner, $level)) {
            $amount = $sale->amount * ($rate / 100);

            $commissions[] = $this->createCommission([
                'partner_id' => $uplinePartner->id,
                'sales_id' => $sale->id,
                'commission_type' => 'group',
                'commission_rate' => $rate,
                'commission_amount' => $amount,
                'generation_level' => $level
            ]);
        }
    }

    return $commissions;
}
```

### 3. 매칭 보너스 계산
```php
function calculateMatchingBonus($partner, $period) {
    // 양쪽 다리의 매출 확인
    $leftLeg = $this->getTeamVolume($partner, 'left', $period);
    $rightLeg = $this->getTeamVolume($partner, 'right', $period);

    // 매칭 가능 볼륨 (작은 쪽 다리 기준)
    $matchingVolume = min($leftLeg, $rightLeg);

    // 파트너 등급에 따른 매칭 비율
    $matchingRates = [
        'BRONZE' => 0.05,   // 5%
        'SILVER' => 0.08,   // 8%
        'GOLD' => 0.12,     // 12%
        'PLATINUM' => 0.15, // 15%
        'DIAMOND' => 0.20   // 20%
    ];

    $rate = $matchingRates[$partner->currentTier->tier_code] ?? 0;
    $bonusAmount = $matchingVolume * $rate;

    if ($bonusAmount > 0) {
        return $this->createCommission([
            'partner_id' => $partner->id,
            'commission_type' => 'matching_bonus',
            'commission_rate' => $rate * 100,
            'commission_amount' => $bonusAmount,
            'calculation_details' => [
                'left_leg_volume' => $leftLeg,
                'right_leg_volume' => $rightLeg,
                'matching_volume' => $matchingVolume
            ]
        ]);
    }

    return null;
}
```

## 📊 커미션 유형별 상세

### Personal Commission (개인 커미션)
- **대상**: 직접 판매한 파트너
- **비율**: 파트너 유형별 3-7%
- **추가 보너스**: 등급별 0-2% 추가
- **지급 시기**: 매출 발생 즉시

### Group Commission (그룹 커미션)
- **대상**: 상위 라인 파트너 (7세대까지)
- **비율**: 세대별 차등 (2% → 0.4%)
- **자격 조건**: 월 최소 매출 조건 충족
- **지급 시기**: 월말 일괄 정산

### Override Commission (오버라이드)
- **대상**: 리더급 파트너 (Gold 이상)
- **비율**: 등급별 0.5-2%
- **적용 범위**: 전체 하위 조직
- **자격 조건**: 팀 규모 및 리더십 요건

### Matching Bonus (매칭 보너스)
- **대상**: 양쪽 다리 균형 달성 파트너
- **비율**: 등급별 5-20%
- **계산 기준**: 작은 쪽 다리 매출
- **지급 시기**: 월별 정산

## 🎯 커미션 최적화

### 1. 성능 최적화
- **인덱스 최적화**: 파트너별, 날짜별 빠른 조회
- **배치 처리**: 대량 데이터 효율적 처리
- **캐싱**: 자주 조회되는 상위 라인 정보 캐싱
- **비동기 처리**: 복잡한 계산의 비동기 처리

### 2. 정확성 보장
- **트랜잭션**: 원자성 보장으로 데이터 일관성
- **검증 로직**: 계산 결과 자동 검증
- **이중 계산 방지**: 중복 처리 방지 메커니즘
- **감사 로그**: 모든 계산 과정 기록

### 3. 유연성 확보
- **규칙 엔진**: 커미션 규칙의 동적 변경
- **A/B 테스팅**: 새로운 커미션 구조 테스트
- **예외 처리**: 특수 상황에 대한 수동 조정
- **버전 관리**: 규칙 변경 이력 관리

## 🔗 연관 기능

- **Partner Sales**: 커미션 계산의 기초 데이터
- **Partner Users**: 파트너별 등급 및 자격 정보
- **Partner Payments**: 계산된 커미션의 실제 지급 처리
- **Partner Performance Metrics**: 성과 기반 추가 보너스

---
*투명하고 공정한 MLM 커미션 시스템의 핵심*