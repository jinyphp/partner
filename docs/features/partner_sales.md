# 💰 Partner Sales (파트너 판매 관리)

## 📋 개요

파트너 판매 관리 시스템은 파트너들의 매출 실적을 체계적으로 추적하고 MLM(Multi-Level Marketing) 구조의 커미션을 자동으로 계산하는 핵심 시스템입니다.
매출 발생부터 커미션 분배까지의 전체 프로세스를 투명하고 정확하게 관리합니다.

## 🎯 핵심 기능

### 1. 매출 등록
- **매출 정보**: 파트너별 매출 상세 내역 기록
- **상품 정보**: 판매 상품, 카테고리, 수량 등 상품별 분류
- **승인 처리**: 매출 승인 프로세스 및 검증 시스템
- **상태 관리**: 대기 → 확정 → 취소/환불 등 상태별 워크플로우

### 2. 실시간 커미션 계산
- **즉시 계산**: 매출 확정과 동시에 커미션 자동 계산
- **네트워크 스냅샷**: 커미션 계산 시점의 조직도 정보 저장
- **분배 정보**: 수령자별 커미션 분배 내역 기록
- **투명성 확보**: 계산 과정 및 근거 데이터 완전 보존

### 3. 매출 상태 관리
- **pending**: 매출 등록 (승인 대기)
- **confirmed**: 매출 확정 (커미션 계산 완료)
- **cancelled**: 매출 취소
- **refunded**: 환불 처리 완료

### 4. 승인 시스템
- **승인 조건**: 금액별 승인 권한 설정
- **승인 이력**: 승인자 정보 및 승인 일시 기록
- **메모 관리**: 관리자 메모 및 특이사항 기록
- **예외 처리**: 특수 상황에 대한 수동 처리 지원

## 🏗️ 데이터 구조

### 기본 매출 정보
```sql
id                  -- 고유 식별자
partner_id          -- 매출 파트너 ID
partner_name        -- 파트너 이름 (스냅샷)
partner_email       -- 파트너 이메일 (스냅샷)
title               -- 매출/상품명
description         -- 매출 설명
amount              -- 매출 금액
currency            -- 통화 (KRW, USD 등)
sales_date          -- 매출 발생일 (실제 판매일)
```

### 상품 및 주문
```sql
category            -- 상품 카테고리
product_type        -- 상품 유형
sales_channel       -- 판매 채널 (온라인, 오프라인 등)
order_number        -- 주문번호/참조번호
order_code          -- 주문 코드 (커미션 계산용)
```

### 매출 상태
```sql
status              -- 매출 상태 (pending, confirmed, cancelled, refunded)
status_reason       -- 상태 변경 사유
confirmed_at        -- 확정 일시
cancelled_at        -- 취소 일시
```

### 커미션 계산
```sql
commission_calculated           -- 커미션 계산 여부
commission_calculated_at        -- 커미션 계산 일시
total_commission_amount        -- 총 커미션 금액
commission_recipients_count    -- 커미션 수령자 수
commission_distribution        -- 커미션 분배 상세 정보 (JSON)
```

### 네트워크 스냅샷
```sql
tree_snapshot           -- 계산 시점 조직도 정보 (JSON)
partner_tier_at_time    -- 계산 시점 파트너 등급
partner_type_at_time    -- 계산 시점 파트너 유형
```

### 승인 및 관리
```sql
requires_approval   -- 승인 필요 여부
is_approved        -- 승인 상태
approved_by        -- 승인자 ID
approved_at        -- 승인 일시
approval_notes     -- 승인 메모
created_by         -- 등록자 ID
updated_by         -- 수정자 ID
admin_notes        -- 관리자 메모
```

## 💼 비즈니스 로직

### 1. 매출 등록 프로세스
```php
function createPartnerSale($partnerId, $saleData) {
    // 1. 파트너 유효성 검증
    $partner = PartnerUser::findOrFail($partnerId);

    // 2. 매출 데이터 검증
    $this->validateSaleData($saleData);

    // 3. 승인 필요 여부 판단
    $requiresApproval = $this->needsApproval($saleData['amount'], $partner);

    // 4. 매출 레코드 생성
    $sale = PartnerSale::create([
        'partner_id' => $partnerId,
        'partner_name' => $partner->name,
        'partner_email' => $partner->email,
        'title' => $saleData['title'],
        'amount' => $saleData['amount'],
        'sales_date' => $saleData['sales_date'],
        'status' => $requiresApproval ? 'pending' : 'confirmed',
        'requires_approval' => $requiresApproval,
        'created_by' => $partnerId
    ]);

    // 5. 자동 확정 시 커미션 즉시 계산
    if (!$requiresApproval) {
        $this->confirmSale($sale);
    }

    return $sale;
}
```

### 2. 매출 확정 및 커미션 계산
```php
function confirmSale($sale) {
    DB::transaction(function() use ($sale) {
        // 1. 현재 파트너 네트워크 정보 스냅샷
        $treeSnapshot = $this->captureNetworkSnapshot($sale->partner_id);

        // 2. 매출 상태 확정
        $sale->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'tree_snapshot' => $treeSnapshot,
            'partner_tier_at_time' => $sale->partner->currentTier->tier_code,
            'partner_type_at_time' => $sale->partner->partnerType->type_code
        ]);

        // 3. 커미션 계산 실행
        $commissionResult = $this->calculateCommissions($sale);

        // 4. 커미션 계산 결과 업데이트
        $sale->update([
            'commission_calculated' => true,
            'commission_calculated_at' => now(),
            'total_commission_amount' => $commissionResult['total_amount'],
            'commission_recipients_count' => $commissionResult['recipients_count'],
            'commission_distribution' => $commissionResult['distribution']
        ]);

        // 5. 개별 커미션 레코드 생성
        $this->createCommissionRecords($sale, $commissionResult['commissions']);
    });
}
```

### 3. 커미션 계산 알고리즘
```php
function calculateCommissions($sale) {
    $commissions = [];
    $totalAmount = 0;
    $recipientsCount = 0;

    // 1. 직접 커미션 (개인)
    $directCommission = $this->calculateDirectCommission($sale);
    if ($directCommission > 0) {
        $commissions[] = [
            'partner_id' => $sale->partner_id,
            'type' => 'direct',
            'amount' => $directCommission,
            'rate' => $this->getDirectCommissionRate($sale->partner)
        ];
        $totalAmount += $directCommission;
        $recipientsCount++;
    }

    // 2. 상위 라인 커미션 (MLM 구조)
    $uplineCommissions = $this->calculateUplineCommissions($sale);
    foreach ($uplineCommissions as $commission) {
        $commissions[] = $commission;
        $totalAmount += $commission['amount'];
        $recipientsCount++;
    }

    // 3. 매칭 보너스 (팀균형 보너스)
    $matchingBonuses = $this->calculateMatchingBonuses($sale);
    foreach ($matchingBonuses as $bonus) {
        $commissions[] = $bonus;
        $totalAmount += $bonus['amount'];
        $recipientsCount++;
    }

    return [
        'commissions' => $commissions,
        'total_amount' => $totalAmount,
        'recipients_count' => $recipientsCount,
        'distribution' => $this->formatDistribution($commissions)
    ];
}
```

### 4. 매출 취소 및 환불 처리
```php
function processCancellation($sale, $reason = null) {
    DB::transaction(function() use ($sale, $reason) {
        // 1. 매출 상태 변경
        $sale->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'status_reason' => $reason
        ]);

        // 2. 기존 지급된 커미션 회수 처리
        if ($sale->commission_calculated) {
            $this->reverseCommissions($sale);
        }

        // 3. 관련 성과지표 재계산 트리거
        $this->recalculatePerformanceMetrics($sale->partner_id);
    });
}
```

### 5. 네트워크 스냅샷 생성
```php
function captureNetworkSnapshot($partnerId) {
    $partner = PartnerUser::with(['parent', 'children', 'partnerTier', 'partnerType'])
        ->find($partnerId);

    $snapshot = [
        'partner_info' => [
            'id' => $partner->id,
            'name' => $partner->name,
            'tier' => $partner->partnerTier->tier_code,
            'type' => $partner->partnerType->type_code
        ],
        'upline' => $this->getUplineSnapshot($partner),
        'downline' => $this->getDownlineSnapshot($partner),
        'captured_at' => now()->toISOString()
    ];

    return json_encode($snapshot);
}
```

## 📊 매출 성과 및 분석

### 파트너별 매출 통계
```php
function getPartnerSalesStats($partnerId, $period = 'monthly') {
    $query = PartnerSale::where('partner_id', $partnerId)
        ->where('status', 'confirmed');

    if ($period === 'monthly') {
        $query->whereMonth('sales_date', now()->month)
              ->whereYear('sales_date', now()->year);
    }

    return $query->selectRaw('
        COUNT(*) as total_sales_count,
        SUM(amount) as total_sales_amount,
        AVG(amount) as average_sale_amount,
        SUM(total_commission_amount) as total_commission_generated,
        MAX(amount) as highest_sale,
        MIN(amount) as lowest_sale
    ')->first();
}
```

### 매출 트렌드 분석
```php
function getSalesTrends($partnerId, $days = 30) {
    return PartnerSale::where('partner_id', $partnerId)
        ->where('status', 'confirmed')
        ->where('sales_date', '>=', now()->subDays($days))
        ->groupByRaw('DATE(sales_date)')
        ->selectRaw('
            DATE(sales_date) as sale_date,
            COUNT(*) as daily_count,
            SUM(amount) as daily_amount
        ')
        ->orderBy('sale_date')
        ->get();
}
```

## 🎯 성과 지표

### 매출 핵심 KPI
- **일/월/연 매출액**: 기간별 매출 실적 추이
- **매출 건수**: 거래 빈도 및 활동성 지표
- **평균 단가**: 거래당 평균 매출 금액
- **커미션 생성액**: 총 네트워크 창출 커미션 금액

### 품질 지표
- **승인율**: 총 매출 건수 대비 승인 비율
- **환불율**: 확정 매출 대비 환불 비율
- **승인 소요시간**: 매출 등록부터 승인까지의 시간
- **정확도**: 수정/정정이 필요한 매출 비율

## 🔗 연관 시스템

- **Partner Users**: 매출 파트너 기본 정보와 연동
- **Partner Commissions**: 매출 기반 커미션 자동 생성
- **Partner Performance Metrics**: 매출 기반 성과 측정
- **Partner Dynamic Targets**: 동적 목표와 달성 추적
- **Orders System**: 주문 시스템과의 연계 처리

## 🔮 향후 확장 방향

1. **예측형 매출 분석**: 예측 매출 데이터 분석 및 목표 추천
2. **AI 기반 패턴분석**: 매출 패턴 분석을 통한 개선 방안 제시
3. **실시간 알림**: 매출 목표 달성 및 이벤트 알림
4. **자동승인 확대**: 조건부 매출 자동승인 범위 확대

---
*MLM 커미션 시스템의 핵심이 되는 투명한 매출 관리*