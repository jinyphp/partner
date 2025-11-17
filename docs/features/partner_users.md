# 👥 Partner Users (파트너 회원 관리)

## 📋 개요

파트너 회원 관리는 파트너 시스템의 핵심으로, 모든 파트너의 기본 정보부터 성과 추적까지 통합 관리하는 중앙 시스템입니다.
계층 구조, 성과 관리, 계약 관리 등 파트너 라이프사이클 전반을 체계적으로 지원합니다.

## 🎯 핵심 기능

### 1. 파트너 프로필 관리
- **기본 정보**: 개인/법인 정보, 연락처, 주소
- **비즈니스 정보**: 사업자 정보, 전문 분야, 활동 지역
- **계약 정보**: 계약 조건, 커미션 구조, 활동 권한
- **상태 관리**: 활성/비활성, 승인 상태, 제재 여부

### 2. 계층 구조 관리 (MLM)
- **추천인 관계**: 상위 파트너(스폰서) 연결
- **조직 트리**: 다단계 조직 구조 형성
- **팀 관리**: 직속 하위 파트너들 관리
- **경로 추적**: 전체 상위 라인 경로 기록

### 3. 성과 추적 시스템
- **매출 성과**: 월별/연도별 매출 실적
- **팀 성과**: 하위 조직의 통합 성과
- **등급 관리**: 현재 등급 및 승급/강등 이력
- **목표 관리**: 개인별 목표 설정 및 달성률

### 4. 교육 및 인증
- **교육 이수**: 완료한 교육 프로그램 목록
- **자격 인증**: 취득한 자격증 및 인증서
- **성과 평가**: 종합 성과 점수 및 평가 등급
- **활동 기록**: 로그인, 영업 활동 등 활동 이력

## 🏗️ 데이터 구조

### 기본 정보
```sql
id                  -- 고유 식별자
user_id             -- 연결된 사용자 계정 ID
partner_code        -- 고유 파트너 코드
partner_type_id     -- 파트너 유형 ID (개인/법인/기술 등)
partner_tier_id     -- 현재 등급 ID
name                -- 파트너명 (개인명 또는 상호)
email               -- 이메일 주소
phone               -- 연락처
```

### 비즈니스 정보
```sql
business_name       -- 사업체명
business_license    -- 사업자등록번호
business_address    -- 사업장 주소
specialization      -- 전문 분야
service_areas       -- 서비스 가능 지역 (JSON)
working_hours       -- 업무 시간 (JSON)
```

### 계층 구조
```sql
parent_id           -- 상위 파트너 (추천인) ID
sponsor_id          -- 스폰서 ID (MLM에서 후원자)
tree_path           -- 전체 상위 경로 (1,5,12,15 형태)
depth_level         -- 조직 깊이 (1단계, 2단계 등)
left_node           -- 좌측 노드 번호 (중첩 집합 모델)
right_node          -- 우측 노드 번호 (중첩 집합 모델)
```

### 성과 관리
```sql
current_month_sales     -- 이번 달 매출
last_month_sales        -- 지난 달 매출
ytd_sales              -- 연간 누적 매출
total_sales            -- 총 누적 매출
team_sales             -- 팀 전체 매출
performance_grade      -- 성과 등급 (A, B, C, D)
achievement_rate       -- 목표 달성률 (%)
```

## 💼 비즈니스 로직

### 1. 파트너 등록 프로세스
```php
function registerPartner($applicationData) {
    // 1. 기본 정보 검증
    $this->validateBasicInfo($applicationData);

    // 2. 추천인 정보 확인
    $sponsor = $this->validateSponsor($applicationData['sponsor_code']);

    // 3. 파트너 코드 생성
    $partnerCode = $this->generatePartnerCode();

    // 4. 조직 트리 위치 계산
    $treePosition = $this->calculateTreePosition($sponsor);

    // 5. 파트너 생성
    return PartnerUser::create([
        'partner_code' => $partnerCode,
        'parent_id' => $sponsor->id,
        'tree_path' => $sponsor->tree_path . ',' . $sponsor->id,
        'depth_level' => $sponsor->depth_level + 1,
        // ... 기타 정보
    ]);
}
```

### 2. 성과 계산 및 업데이트
```php
function updatePerformanceMetrics($partnerId, $period = 'monthly') {
    $partner = PartnerUser::find($partnerId);

    // 개인 매출 집계
    $personalSales = $this->calculatePersonalSales($partner, $period);

    // 팀 매출 집계 (하위 조직 포함)
    $teamSales = $this->calculateTeamSales($partner, $period);

    // 성과 등급 산정
    $performanceGrade = $this->calculatePerformanceGrade($partner);

    // 목표 달성률 계산
    $achievementRate = $this->calculateAchievementRate($partner);

    $partner->update([
        'current_month_sales' => $personalSales,
        'team_sales' => $teamSales,
        'performance_grade' => $performanceGrade,
        'achievement_rate' => $achievementRate
    ]);
}
```

### 3. 조직도 조회
```php
function getOrganizationTree($partnerId, $depth = 3) {
    $partner = PartnerUser::find($partnerId);

    // 현재 파트너를 루트로 하는 하위 조직 조회
    return PartnerUser::where('tree_path', 'like', $partner->tree_path . '%')
        ->where('depth_level', '<=', $partner->depth_level + $depth)
        ->with(['partnerType', 'partnerTier'])
        ->orderBy('tree_path')
        ->get()
        ->groupBy('depth_level');
}
```

## 📊 파트너 분류 및 관리

### 상태별 분류
- **Active**: 정상 활동 중인 파트너
- **Inactive**: 일시적 활동 중단
- **Suspended**: 규정 위반으로 정지
- **Terminated**: 계약 해지

### 성과 등급별 관리
- **A등급**: 목표 150% 이상 달성
- **B등급**: 목표 100-149% 달성
- **C등급**: 목표 70-99% 달성
- **D등급**: 목표 70% 미만

### 활동 유형별 구분
- **Seller**: 직접 판매 중심
- **Recruiter**: 파트너 모집 중심
- **Leader**: 팀 관리 및 리더십
- **Trainer**: 교육 및 멘토링

## 🎯 KPI 및 성과 지표

### 개인 성과 지표
- **월 매출액**: 개인별 월간 매출 실적
- **고객 만족도**: 서비스 품질 평가 점수
- **활동 지수**: 로그인, 교육 참여 등 활동 수준
- **목표 달성률**: 설정된 목표 대비 달성 비율

### 팀 성과 지표
- **팀 매출액**: 직속 하위 파트너들의 총 매출
- **팀 성장률**: 전월 대비 팀 성과 증가율
- **신규 영입**: 새로운 파트너 영입 수
- **팀 유지율**: 하위 파트너의 지속 활동 비율

## 🔗 연관 기능

- **Partner Applications**: 파트너 지원 및 승인 과정
- **Partner Commissions**: 개별 커미션 계산 및 지급
- **Partner Performance Metrics**: 상세 성과 분석
- **Partner Network Relationships**: 조직 관계 상세 관리

---
*파트너 생태계의 중심이 되는 통합 회원 관리 시스템*