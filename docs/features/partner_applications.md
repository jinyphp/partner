# 📋 Partner Applications (파트너 지원서 관리)

## 📋 개요

파트너 지원서 관리 시스템은 파트너 가입 신청부터 최종 승인까지의 전체 프로세스를 체계적으로 관리합니다.
추천인 추적, 서류 검토, 면접 진행, 승인 처리 등 파트너 온보딩의 모든 단계를 지원합니다.

## 🎯 핵심 기능

### 1. 지원서 접수 및 관리
- **온라인 지원**: 웹 기반 지원서 작성 시스템
- **단계별 진행**: 초안 → 제출 → 검토 → 면접 → 승인
- **자동 저장**: 작성 중인 지원서 자동 임시 저장
- **첨부 문서**: 사업자등록증, 이력서, 포트폴리오 등

### 2. 추천인 시스템
- **추천 코드**: 고유한 추천인 코드로 연결
- **추천 경로**: 온라인, 오프라인, 이벤트 등 다양한 경로
- **만남 기록**: 추천인과의 미팅 일정 및 장소
- **추천 보상**: 성공적인 추천 시 보상 체계

### 3. 검토 및 평가 프로세스
- **담당자 배정**: 지역/분야별 전담 검토자 배정
- **우선순위 관리**: 긴급/일반/낮음 등 우선순위 설정
- **진행 상황 추적**: 각 단계별 처리 현황 모니터링
- **피드백 관리**: 검토 의견 및 개선 요청 사항

### 4. 승인 워크플로우
- **다단계 승인**: 1차 검토 → 2차 심사 → 최종 승인
- **자동화 규칙**: 조건에 따른 자동 승인/거절
- **예외 처리**: 특수 상황에 대한 수동 처리
- **승인 후 절차**: 계약서 발송, 교육 안내 등

## 🏗️ 데이터 구조

### 기본 지원 정보
```sql
id                      -- 고유 식별자
user_id                 -- 지원자 사용자 ID
application_status      -- 진행 상태 (draft, submitted, reviewing, etc.)
submitted_at            -- 제출 일시
country                 -- 지원 국가
```

### 추천인 정보
```sql
referrer_partner_id     -- 추천인 파트너 ID
referral_code          -- 추천 코드
referral_source        -- 추천 경로 (online_link, offline_meeting, etc.)
referrer_name          -- 추천인 이름
referrer_contact       -- 추천인 연락처
meeting_date           -- 만남 날짜
meeting_location       -- 만남 장소
referral_details       -- 추천 상세 내용
```

### 지원자 정보
```sql
personal_info          -- 개인 정보 (JSON)
experience_info        -- 경력 정보 (JSON)
skills_info           -- 보유 기술 (JSON)
documents             -- 제출 서류 목록 (JSON)
motivation            -- 지원 동기
goals                 -- 목표 및 계획
```

### 검토 및 처리
```sql
assigned_reviewer_id    -- 담당 검토자 ID
review_started_at      -- 검토 시작일
review_deadline        -- 검토 마감일
priority              -- 우선순위 (low, normal, high, urgent)
tags                  -- 태그 (JSON 배열)
admin_notes           -- 관리자 메모
```

## 💼 비즈니스 로직

### 1. 지원서 제출 프로세스
```php
function submitApplication($userId, $applicationData) {
    // 1. 기본 정보 검증
    $this->validateApplicationData($applicationData);

    // 2. 추천인 정보 확인
    $referrer = $this->validateReferrer($applicationData['referral_code']);

    // 3. 중복 지원 확인
    if ($this->hasPendingApplication($userId)) {
        throw new DuplicateApplicationException();
    }

    // 4. 지원서 생성
    $application = PartnerApplication::create([
        'user_id' => $userId,
        'application_status' => 'submitted',
        'submitted_at' => now(),
        'referrer_partner_id' => $referrer?->id,
        // ... 기타 정보
    ]);

    // 5. 자동 검토자 배정
    $this->autoAssignReviewer($application);

    return $application;
}
```

### 2. 자동 검토자 배정
```php
function autoAssignReviewer($application) {
    $criteria = [
        'location' => $application->user->address,
        'specialty' => $application->expected_specialization,
        'workload' => 'min', // 업무량이 적은 순서
        'expertise' => $application->getRequiredExpertise()
    ];

    $reviewer = $this->findBestReviewer($criteria);

    $application->update([
        'assigned_reviewer_id' => $reviewer->id,
        'assigned_at' => now(),
        'review_deadline' => now()->addDays(7)
    ]);
}
```

### 3. 승인 프로세스 자동화
```php
function processAutoApproval($application) {
    $autoApprovalRules = [
        'has_referrer' => $application->referrer_partner_id !== null,
        'experience_sufficient' => $application->getExperienceYears() >= 2,
        'documents_complete' => $application->hasAllRequiredDocuments(),
        'background_clear' => $this->checkBackground($application->user_id),
        'referrer_rating' => $application->referrer?->rating >= 4.0
    ];

    $passedRules = array_filter($autoApprovalRules);

    if (count($passedRules) >= 4) {
        return $this->approveApplication($application, 'auto_approved');
    }

    return false; // 수동 검토 필요
}
```

## 📊 지원서 상태 관리

### 상태별 의미
- **draft**: 작성 중 (임시저장)
- **submitted**: 제출 완료
- **reviewing**: 검토 진행 중
- **interview**: 면접 단계
- **approved**: 승인 완료
- **rejected**: 거절
- **reapplied**: 재지원

### 상태 전환 규칙
```
draft → submitted → reviewing → interview → approved
                          ↓         ↓
                       rejected ← rejected
                          ↓
                       reapplied → reviewing
```

## 🎯 품질 관리 시스템

### 1. 지원서 품질 평가
- **완성도**: 필수 정보 입력 비율
- **서류 품질**: 제출 서류의 적절성
- **동기 명확성**: 지원 동기의 구체성
- **실현 가능성**: 목표의 현실성

### 2. 추천인 품질 관리
- **추천 성공률**: 추천한 파트너의 성공률
- **지속 활동률**: 추천 파트너의 장기 활동률
- **추천 품질 점수**: 전반적인 추천 품질 평가
- **우수 추천인 인센티브**: 품질 높은 추천에 대한 특별 보상

## 🔄 재지원 관리

### 재지원 조건
- 이전 거절로부터 3개월 경과
- 거절 사유 개선 증빙 제출
- 새로운 추천인 또는 추가 자격 획득

### 개선 지원 프로그램
- **스킬업 교육**: 부족한 부분에 대한 무료 교육 제공
- **멘토링**: 기존 파트너와의 멘토링 연결
- **준비 가이드**: 재지원을 위한 상세 가이드 제공

## 🔗 연관 기능

- **Partner Users**: 승인 후 파트너 계정 생성
- **Partner Interviews**: 면접 일정 및 진행 관리
- **Partner Approval Processes**: 승인 워크플로우 상세 관리
- **Partner Network Relationships**: 추천인 관계 구축

---
*체계적이고 투명한 파트너 모집 및 승인 시스템*