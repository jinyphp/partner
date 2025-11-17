# 파트너 동적 목표 관리 시스템

## 🎯 시스템 개요

파트너별 개인 맞춤 목표 설정 및 성과 추적을 위한 스마트 목표 관리 시스템입니다.
기간별, 카테고리별 유연한 목표 설정으로 파트너의 성장을 체계적으로 지원합니다.

## 🎯 핵심 기능

### 주요 특징
- ✅ **개인별 맞춤 목표 설정** (매출, 고객, 활동 등)
- ✅ **다기간 목표 관리** (월별, 분기별, 연별)
- ✅ **실시간 진행률 추적** (달성률 자동 계산)
- ✅ **목표 대비 실적 분석** 및 리포팅
- ✅ **목표 달성 시 자동 리워드** 지급
- ✅ **목표 수정 및 조정** 이력 관리
- ✅ **상위/하위 목표 연계** 시스템
- ✅ **목표별 가중치 및 우선순위** 설정

## 📅 목표 기간 타입

| 타입 | 설명 | 적용 범위 |
|------|------|-----------|
| `monthly` | 월별 목표 | 가장 일반적, 단기 집중 목표 |
| `quarterly` | 분기별 목표 | 중기 전략 목표 |
| `yearly` | 연별 목표 | 장기 비전 목표 |

## 📊 목표 카테고리

### 기본 카테고리
- **`sales_revenue`**: 매출액 목표
- **`customer_acquisition`**: 신규 고객 확보
- **`activity_count`**: 활동 건수 (상담, 미팅 등)
- **`team_building`**: 팀 구성 (하위 파트너 모집)
- **`skill_development`**: 역량 개발 (교육 이수 등)
- **`custom`**: 맞춤형 목표

### 목표 계산 구조

#### 1. 기본 목표 산정
- **기본 매출 목표**: `타입 최소기준 × 등급승수`
- **기본 처리건수**: 파트너 타입별 최소 처리 기준
- **기본 수익 목표**: 매출 대비 수익률 적용
- **기본 고객 관리**: 신규/기존 고객 관리 건수

#### 2. 조정 계수 적용
- **개인별 조정 계수** (`personal_adjustment_factor`): 1.0=기본, 1.2=20% 증가
- **시장 상황 계수** (`market_condition_factor`): 1.5=성수기, 0.8=비수기
- **계절성 조정 계수** (`seasonal_adjustment_factor`): 계절별 특성 반영
- **팀 성과 계수** (`team_performance_factor`): 리더인 경우 팀 전체 성과 고려

#### 3. 최종 목표 계산
```
최종 목표 = 기본 목표 × 개인조정 × 시장상황 × 계절성 × 팀성과
```

## 🏆 목표 상태 관리

| 상태 | 설명 | 비고 |
|------|------|------|
| `draft` | 초안 | 아직 활성화 안됨 |
| `pending_approval` | 승인 대기 | 관리자 승인 필요 |
| `approved` | 승인 완료 | 활성화 대기 중 |
| `active` | 활성 | 진행 중인 목표 |
| `completed` | 완료 | 목표 달성 |
| `failed` | 실패 | 기간 만료, 미달성 |
| `cancelled` | 취소 | 중도 포기 |
| `paused` | 일시정지 | 특별한 사유 |

## 💰 리워드 시스템

### 보너스 계층 설정
```json
{
  "150": {
    "rate": 3.0,
    "description": "초과달성 (150% 이상)"
  },
  "120": {
    "rate": 2.0,
    "description": "우수달성 (120-149%)"
  },
  "100": {
    "rate": 1.0,
    "description": "목표달성 (100-119%)"
  },
  "80": {
    "rate": 0.5,
    "description": "부분달성 (80-99%)"
  }
}
```

### 리워드 유형
- **보너스 지급**: 달성률에 따른 차등 보너스
- **포인트 적립**: 성과 포인트 누적
- **등급 승진**: 우수 성과자 등급 조정
- **특별 혜택**: 인센티브 여행, 교육 기회 등

## 📈 진행률 추적

### 실시간 성과 모니터링
- **현재 달성 상황**: 실시간 업데이트
- **달성률 계산**: 자동 계산 (0-100%+)
- **종합 성과**: 가중평균 방식으로 산정
- **마일스톤 추적**: 단계별 진행 상황 체크

### 성과 지표
```json
{
  "milestones": [
    {
      "date": "2025-01-15",
      "milestone": "25%",
      "achieved": true,
      "notes": "1분기 목표 달성"
    },
    {
      "date": "2025-02-15",
      "milestone": "50%",
      "achieved": false,
      "notes": "진행 중"
    }
  ]
}
```

## 🎖️ 특별 목표 및 도전 과제

### 맞춤형 목표 설정
```json
{
  "special_objectives": {
    "new_client_acquisition": 5,
    "innovation_project": true,
    "mentoring_target": 2,
    "training_completion": 3,
    "customer_satisfaction": 90
  }
}
```

## 🔗 시스템 연계

### 테이블 관계
- **partner_users → partner_dynamic_targets** (1:N): 파트너별 목표
- **partner_dynamic_targets → partner_performance_metrics**: 성과 연계

### 연관 시스템
- **성과 관리 시스템**: 목표 달성률 기반 성과 평가
- **보상 시스템**: 자동 보너스 계산 및 지급
- **알림 시스템**: 목표 진행 상황 알림

## 📊 데이터 분석 및 리포팅

### 월별 성과 요약 뷰
```sql
-- partner_monthly_performance 뷰 활용
SELECT
    partner_name,
    partner_type,
    partner_tier,
    final_sales_target,
    current_sales_achievement,
    sales_achievement_rate,
    overall_achievement_rate
FROM partner_monthly_performance
WHERE target_year = 2025 AND target_month = 11;
```

### 팀 성과 요약 뷰
```sql
-- partner_team_performance 뷰 활용
SELECT
    manager_name,
    team_size,
    avg_team_achievement,
    total_team_sales,
    total_team_bonus
FROM partner_team_performance
WHERE avg_team_achievement >= 80;
```

## 🚀 성능 최적화

### 인덱스 구조
- **복합 인덱스**: `partner_user_id + target_year + target_month`
- **상태별 인덱스**: `target_period_type + status`
- **활성 목표**: `status + activated_at`
- **달성률 정렬**: `overall_achievement_rate`

### 자동화 기능
- **달성률 자동 계산**: 실시간 성과 데이터 반영
- **보너스 자동 산정**: 설정된 계층에 따른 자동 계산
- **알림 자동 발송**: 마일스톤 달성 시 자동 알림

## 📋 운영 가이드

### 목표 설정 프로세스
1. **초안 작성** (`draft`): 파트너별 기본 목표 설정
2. **승인 요청** (`pending_approval`): 관리자 검토 요청
3. **승인 완료** (`approved`): 관리자 승인 후 활성화 준비
4. **목표 활성화** (`active`): 실제 목표 추진 시작
5. **성과 추적**: 실시간 진행률 모니터링
6. **목표 완료** (`completed`): 기간 종료 및 최종 평가

### 주의사항
- **중복 목표 방지**: 동일 기간 동일 타입 목표 설정 불가
- **데이터 일관성**: 성과 데이터와 목표 데이터 동기화 필수
- **승인 권한**: 목표 수정 시 적절한 승인 프로세스 준수

## 🔧 기술적 구현

### 데이터베이스 스키마
- **테이블명**: `partner_dynamic_targets`
- **주요 필드**: 목표 설정, 진행률 추적, 리워드 계산
- **제약조건**: 유니크 제약으로 중복 방지

### 자동 계산 로직
- **스케줄러**: 일정 주기로 성과 데이터 동기화
- **트리거**: 관련 데이터 변경 시 자동 재계산
- **캐시**: 자주 조회되는 성과 데이터 캐싱

## 📈 향후 개선 계획

### 기능 확장
- **AI 기반 목표 추천**: 과거 성과 데이터 분석을 통한 최적 목표 제안
- **예측 분석**: 현재 진행률 기반 최종 성과 예측
- **벤치마킹**: 동일 등급 파트너 간 성과 비교
- **모바일 대시보드**: 실시간 성과 확인 앱

### 시스템 고도화
- **다차원 분석**: OLAP 기반 다각도 성과 분석
- **실시간 알림**: WebSocket 기반 즉시 알림
- **API 통합**: 외부 시스템과의 데이터 연동
- **보안 강화**: 민감한 성과 데이터 암호화