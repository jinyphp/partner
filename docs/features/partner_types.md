# 🏷️ Partner Types (파트너 유형 관리)

## 📋 개요

파트너 유형 시스템은 다양한 파트너 카테고리를 정의하고 관리하는 기반 시스템입니다.
각 파트너 유형별로 고유한 특성, 커미션 구조, 권한을 설정할 수 있습니다.

## 🎯 핵심 기능

### 1. 파트너 유형 정의
- **개인사업자** (Individual Business): 개인 단위 파트너
- **법인사업자** (Corporation): 기업 단위 파트너
- **프리랜서** (Freelancer): 독립 계약자
- **기술파트너** (Technical Partner): IT 전문가
- **영업파트너** (Sales Partner): 영업 전문가
- **지역파트너** (Regional Partner): 특정 지역 담당

### 2. 유형별 특성 관리
- **기본 커미션율**: 각 유형의 표준 수수료
- **최소 커미션율**: 보장되는 최소 수수료
- **최대 커미션율**: 적용 가능한 최대 수수료
- **자격 요건**: 해당 유형이 되기 위한 조건
- **필수 문서**: 등록 시 제출해야 할 서류

### 3. 권한 및 제한사항
- **활동 권한**: 수행 가능한 업무 범위
- **지역 제한**: 활동 가능한 지역
- **계약 조건**: 유형별 특별 약관
- **평가 기준**: 성과 평가 방법

## 🏗️ 데이터 구조

### 기본 정보
```sql
id              -- 고유 식별자
type_code       -- 유형 코드 (INDV, CORP, FREE, TECH, SALE, REGION)
type_name       -- 유형 명칭
description     -- 상세 설명
```

### 커미션 설정
```sql
base_commission_rate    -- 기본 커미션율 (%)
min_commission_rate     -- 최소 커미션율 (%)
max_commission_rate     -- 최대 커미션율 (%)
commission_calculation  -- 계산 방식 (percentage, fixed, tiered)
```

## 💼 비즈니스 로직

### 1. 유형별 커미션 계산
```php
// 기본 커미션율 적용
$baseCommission = $salesAmount * ($partnerType->base_commission_rate / 100);

// 성과에 따른 추가 보너스
if ($performanceScore > 90) {
    $bonusRate = min($partnerType->max_commission_rate,
                    $partnerType->base_commission_rate + 2);
    $finalCommission = $salesAmount * ($bonusRate / 100);
}
```

### 2. 자격 요건 검증
```php
// 개인사업자 자격 검증
if ($partnerType->type_code === 'INDV') {
    $requirements = [
        'business_license' => '사업자등록증',
        'bank_account' => '사업자 계좌',
        'id_verification' => '신분증 인증'
    ];
}
```

## 📊 사용 사례

### 1. 신규 파트너 등록
1. 사용자가 원하는 파트너 유형 선택
2. 해당 유형의 자격 요건 확인
3. 필수 문서 업로드
4. 관리자 승인 후 파트너 등록

### 2. 커미션 계산
1. 매출 발생 시 파트너 유형 확인
2. 해당 유형의 커미션율 적용
3. 성과 평가 결과에 따른 보너스 적용
4. 최종 커미션 금액 산출

## 🔗 연관 기능

- **Partner Tiers**: 유형 내에서의 등급 시스템
- **Partner Users**: 실제 파트너 회원 정보
- **Partner Applications**: 지원서 처리 시 유형별 요구사항
- **Partner Commissions**: 커미션 계산의 기준점

---
*파트너 시스템의 기반이 되는 핵심 분류 체계*