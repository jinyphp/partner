# Jiny Partner Management System

## 📋 개요

Jiny Partner는 Laravel 기반의 포괄적인 파트너 및 네트워크 마케팅(MLM) 관리 시스템입니다. 다단계 마케팅, 파트너 모집, 커미션 계산, 매출 관리 등의 기능을 제공합니다.

## 🚀 주요 기능

### 1. 파트너 관리 (Partner Management)
- ✅ **파트너 유저 관리**: 파트너 등록, 정보 관리, 상태 관리
- ✅ **파트너 타입 관리**: 다양한 파트너 유형 설정 및 관리
- ✅ **파트너 등급(Tier) 관리**: 브론즈, 실버, 골드, 플래티넘, 다이아몬드 등급 시스템
- ✅ **파트너 신청 관리**: 신규 파트너 지원 및 승인 프로세스

### 2. 네트워크 마케팅 (MLM System)
- ✅ **계층적 네트워크 구조**: 트리 형태의 파트너 조직 관리
- ✅ **네트워크 모집 관리**: 파트너 간 추천 및 모집 관계 관리
- ✅ **네트워크 분석**: 트리뷰, 성과 분석, 계보 분석
- ✅ **계층 관리**: 파트너 이동, 구조 최적화

### 3. 매출 및 커미션 관리
- ✅ **매출 관리**: 파트너별 매출 등록, 추적, 분석
- ✅ **자동 커미션 계산**: 직접/간접 커미션 자동 분배
- ✅ **커미션 타입**: 직접 판매, 팀 보너스, 관리 보너스, 모집 보너스
- ✅ **실시간 동기화**: 매출 발생 시 파트너 통계 자동 업데이트

### 4. 대시보드 및 분석
- ✅ **관리자 대시보드**: 전체 네트워크 현황 및 통계
- ✅ **성과 분석**: 파트너별, 팀별, 기간별 성과 분석
- ✅ **네트워크 분석**: 네트워크 성장 추이, 티어별 분포

## 🏗️ 시스템 구조

### 데이터베이스 테이블
- `partner_users`: 파트너 사용자 정보
- `partner_types`: 파트너 유형 정보
- `partner_tiers`: 파트너 등급 정보
- `partner_applications`: 파트너 신청 정보
- `partner_sales`: 파트너 매출 정보
- `partner_commissions`: 커미션 정보
- `partner_network_relationships`: 네트워크 관계 정보

### 주요 컨트롤러
- **Admin Controllers**: 관리자 기능
  - DashboardController: 대시보드
  - PartnerUsersController: 파트너 사용자 관리
  - PartnerSalesController: 매출 관리
  - PartnerNetworkController: 네트워크 관리
  - PartnerApplicationController: 파트너 신청 관리

- **Home Controllers**: 일반 사용자 기능
  - PartnerRegistController: 파트너 등록

### 서비스 클래스
- `CommissionCalculationService`: 커미션 계산 서비스

## 📱 사용 가능한 기능

### 관리자 기능 (/admin)
1. **파트너 사용자 관리** (`/admin/partner/users`)
2. **파트너 매출 관리** (`/admin/partner/sales`)
3. **파트너 등급 관리** (`/admin/partner/tiers`)
4. **파트너 타입 관리** (`/admin/partner/types`)
5. **파트너 신청 관리** (`/admin/partner/applications`)
6. **네트워크 트리뷰** (`/admin/partner/network/tree`)
7. **네트워크 모집 관리** (`/admin/partner/network/recruitment`)
8. **커미션 관리** (`/admin/partner/network/commission`)

### 일반 사용자 기능 (/home)
1. **파트너 등록** (`/home/partner/regist`)

## 📊 MLM 네트워크 특징

### 계층 구조
- **레벨 기반**: 루트(레벨 0)부터 시작하는 계층적 구조
- **상하위 관계**: 명확한 스폰서-파트너 관계
- **깊이 제한**: 관리 효율성을 위한 적절한 깊이 제한

### 커미션 시스템
- **직접 커미션**: 본인 매출에 대한 커미션 (등급별 차등)
- **간접 커미션**: 하위 파트너 매출에 대한 관리 보너스
- **자동 분배**: 매출 확정 시 실시간 커미션 분배
- **역계산 지원**: 매출 취소 시 커미션 자동 회수

### 성과 관리
- **월간/총 매출**: 개인 매출 실적 추적
- **팀 매출**: 하위 네트워크 전체 매출 합산
- **승급 시스템**: 성과에 따른 등급 승급

## 🛠️ 기술 스택

- **Framework**: Laravel
- **Database**: SQLite/MySQL 호환
- **Frontend**: Blade Templates + Tailwind CSS
- **Architecture**: MVC Pattern + Service Layer

## 📚 문서

상세한 사용법과 기능 설명은 다음 문서를 참조하세요:

- [`docs/mlm.md`](docs/mlm.md): MLM 시스템 사용 매뉴얼
- [`docs/partner_sales.md`](docs/partner_sales.md): 파트너 매출 관리 시스템
- [`docs/partner_user.md`](docs/partner_user.md): 파트너 사용자 관리
- [`docs/feature.md`](docs/feature.md): 주요 기능 목록

## 🎯 완성도 평가

이 시스템은 파트너 및 네트워크 마케팅에 필요한 **핵심 기능들이 모두 구현**되어 있습니다:

✅ **완전 구현된 기능**:
- 파트너 관리 (등록, 승인, 타입/등급 관리)
- MLM 네트워크 구조 (계층, 모집, 관계 관리)
- 매출 관리 (등록, 추적, 분석)
- 커미션 시스템 (자동 계산, 분배, 관리)
- 분석 및 리포팅 (대시보드, 성과 분석)

**이 시스템은 상용화 가능한 수준의 MLM/파트너 관리 플랫폼입니다.**
