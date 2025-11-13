<?php

namespace Jiny\Partner\Http\Controllers\Home\Sales;

use Jiny\Partner\Http\Controllers\PartnerController;
use Illuminate\Http\Request;
use Jiny\Partner\Models\PartnerSales;
use Jiny\Partner\Models\PartnerUser;

class IndexController extends PartnerController
{
    /**
     * 파트너 세일즈 대시보드
     */
    public function __invoke(Request $request)
    {
        try {
            // ========================================
            // Step 1: 사용자 JWT/세션 인증 확인
            // ========================================
            // HomeController의 auth() 메소드를 통해 JWT 또는 세션 기반 인증 수행
            // 샤딩된 사용자 테이블에서 UUID 기반으로 사용자 정보 조회
            $user = $this->auth($request);
            if (!$user) {
                // 인증 실패 시: 로그인 페이지로 리다이렉션
                // - 세션 플래시 메시지로 사용자 안내
                // - 파트너 서비스 이용 안내 추가 제공
                return redirect()->route('login')
                    ->with('error', 'JWT 인증이 필요합니다. 로그인해 주세요.')
                    ->with('info', '파트너 서비스는 로그인 후 이용하실 수 있습니다.');
            }
            // ========================================
            // Step 2: 파트너 등록 여부 확인 및 검증
            // ========================================
            // PartnerController의 isPartner() 메소드 사용
            // - UUID 기반으로 파트너 등록 여부 확인
            // - partnerType, partnerTier 관계 데이터 포함 조회
            // - 결과: PartnerUser 모델 객체 또는 null
            $partner = $this->isPartner($user);
            if (!$partner) {
                // 파트너 미등록 시: 파트너 소개 페이지로 리다이렉션
                // - 사용자 기본 정보를 세션에 저장하여 신청 과정에서 활용
                // - 파트너 프로그램 가입 유도 메시지 표시
                return redirect()->route('home.partner.intro')
                    ->with('info', '파트너 프로그램에 가입하시면 더 많은 기능을 이용하실 수 있습니다.')
                    ->with('userInfo', [
                        'name' => $user->name ?? '',           // 사용자 이름
                        'email' => $user->email ?? '',         // 사용자 이메일
                        'phone' => $user->profile->phone ?? '', // 연락처 (프로필 관계에서 조회)
                        'uuid' => $user->uuid                   // 고유 식별자 (샤딩 키)
                    ]);
            }

            // ========================================
            // Step 3: 정상 인증 완료 - 아래 중복 코드 사용
            // ========================================
            $authResult = $this->authenticateAndGetPartner($request, 'sales');
            if (!$authResult['success']) {
                return $authResult['redirect'];
            }

            $user = $authResult['user'];
            $partnerUser = $authResult['partner'];

            // 최근 판매 데이터 조회
            $recentSales = PartnerSales::where('partner_id', $partnerUser->id)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function($sale) {
                    // title 필드를 product_name으로 매핑하여 뷰에서 사용
                    $sale->product_name = $sale->title;
                    return $sale;
                });

            // 판매 통계 계산
            $salesStats = [
                'total_sales' => PartnerSales::where('partner_id', $partnerUser->id)->count(),
                'monthly_sales' => PartnerSales::where('partner_id', $partnerUser->id)
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'total_amount' => PartnerSales::where('partner_id', $partnerUser->id)
                    ->sum('amount'),
                'monthly_amount' => PartnerSales::where('partner_id', $partnerUser->id)
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->sum('amount'),
                'avg_sale_amount' => PartnerSales::where('partner_id', $partnerUser->id)
                    ->avg('amount') ?? 0,
                'success_rate' => $this->calculateSuccessRate($partnerUser->id)
            ];

            // 월별 판매 트렌드 데이터
            $monthlyTrend = $this->getMonthlyTrend($partnerUser->id);

            // 하위 파트너 정보 조회
            $subPartners = $this->getSubPartners($partnerUser->id);

            // 표준 뷰 데이터 구성 (공통 로직 사용)
            $viewData = $this->getStandardViewData($user, $partnerUser, [
                'recentSales' => $recentSales,
                'salesStats' => $salesStats,
                'monthlyTrend' => $monthlyTrend,
                'subPartners' => $subPartners
            ], '판매 대시보드');

            // JSON 응답 처리 (공통 로직 사용)
            $jsonResponse = $this->handleJsonResponse($request, $viewData);
            if ($jsonResponse) {
                return $jsonResponse;
            }

            return view('jiny-partner::home.sales.index', $viewData);

        } catch (\Exception $e) {
            // 공통 에러 처리 로직 사용
            return $this->handlePartnerError(
                $e,
                $user ?? null,
                'sales',
                'home.partner.index',
                '판매 정보를 불러오는 중 오류가 발생했습니다.'
            );
        }
    }

    /**
     * 성공률 계산
     */
    private function calculateSuccessRate($partnerId)
    {
        $totalSales = PartnerSales::where('partner_id', $partnerId)->count();
        $successfulSales = PartnerSales::where('partner_id', $partnerId)
            ->where('status', 'confirmed')
            ->count();

        if ($totalSales === 0) {
            return 0;
        }

        return round(($successfulSales / $totalSales) * 100, 2);
    }

    /**
     * 월별 판매 트렌드 조회
     */
    private function getMonthlyTrend($partnerId)
    {
        $months = [];
        $salesData = [];
        $amountData = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('Y-m');

            $monthlySales = PartnerSales::where('partner_id', $partnerId)
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();

            $monthlyAmount = PartnerSales::where('partner_id', $partnerId)
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('amount');

            $salesData[] = $monthlySales;
            $amountData[] = $monthlyAmount;
        }

        return [
            'months' => $months,
            'sales' => $salesData,
            'amounts' => $amountData
        ];
    }

    /**
     * 하위 파트너 정보 조회
     */
    private function getSubPartners($partnerId)
    {
        try {
            // PartnerNetworkRelationship 모델이 있는지 확인
            if (class_exists('\Jiny\Partner\Models\PartnerNetworkRelationship')) {
                $childrenIds = \Jiny\Partner\Models\PartnerNetworkRelationship::where('parent_id', $partnerId)
                    ->where('is_active', true)
                    ->pluck('child_id');

                return PartnerUser::whereIn('id', $childrenIds)
                    ->with(['partnerType', 'partnerTier'])
                    ->orderBy('created_at', 'desc')
                    ->get();
            }

            return collect(); // 빈 컬렉션 반환
        } catch (\Exception $e) {
            \Log::error('Failed to load sub partners: ' . $e->getMessage());
            return collect(); // 빈 컬렉션 반환
        }
    }
}
