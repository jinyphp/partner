<?php

namespace Jiny\Partner\Http\Controllers\Home\Dashboard;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Models\PartnerSales;
use Jiny\Partner\Models\PartnerCommission;
use Jiny\Partner\Models\PartnerNetworkRelationship;
use Illuminate\Http\Request;
//use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
//use Jiny\Auth\Http\Controllers\Traits\JWTAuthTrait;

use Jiny\Partner\Http\Controllers\PartnerController;

/**
 * 파트너 대시보드 메인 컨트롤러
 *
 * 기능:
 * - 파트너 활동 현황 종합 대시보드 제공
 * - 매출/커미션 통계 데이터 표시
 * - 파트너 네트워크 정보 관리
 * - 최근 활동 이력 조회
 *
 * 상속구조:
 * HomeController -> PartnerController -> IndexController
 *
 * 인증단계:
 * 1. 사용자 JWT/세션 인증 (HomeController)
 * 2. 파트너 등록 여부 확인 (PartnerController.isPartner)
 *
 * 라우팅: GET /home/partner
 * 뷰파일: jiny-partner::home.dashboard.index
 *
 * @package Jiny\Partner\Http\Controllers\Home\Dashboard
 * @author JinyPHP Partner System
 * @since 1.0.0
 */
class IndexController extends PartnerController
{
    protected $config;

    public function __construct()
    {
        $path = __DIR__ . '/dashboard.json';
        $this->config = json_decode(file_get_contents($path), true);
    }

    /**
     * 파트너 대시보드 메인 페이지 표시
     *
     * 처리 과정:
     * 1. 사용자 JWT/세션 인증 확인
     * 2. 파트너 등록 여부 검증
     * 3. 매출 통계 데이터 계산
     * 4. 커미션 통계 데이터 계산
     * 5. 최근 매출 기록 조회 (최대 10건)
     * 6. 하위 파트너 네트워크 정보 수집
     * 7. 통합 대시보드 뷰 렌더링
     *
     * 실패 시 리다이렉션:
     * - 미인증 사용자: /login (로그인 페이지)
     * - 미등록 파트너: /home/partner/intro (파트너 소개 페이지)
     *
     * @param Request $request HTTP 요청 객체
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     *
     * @throws \Exception 파트너 데이터 조회 실패 시
     */
    public function __invoke(Request $request)
    {
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

        // dd($this->config);

        // ========================================
        // Step 3: 매출 통계 데이터 계산
        // ========================================
        // 파트너의 전체 매출 현황 분석
        // - 월별/연도별 매출액 합계
        // - 확정된 매출만 집계 (status='confirmed')
        // - SQLite 날짜 함수 활용 (strftime)
        $salesStats = $this->calculateSalesStats($partner);

        // ========================================
        // Step 4: 커미션 통계 데이터 계산
        // ========================================
        // 파트너 커미션 수익 현황 분석
        // - 지급 완료/대기 중 커미션 구분 집계
        // - 이번 달 커미션 수익 계산
        // - 총 커미션 건수 카운트
        $commissionStats = $this->calculateCommissionStats($partner);

        // ========================================
        // Step 5: 최근 매출 기록 조회 (대시보드용)
        // ========================================
        // 대시보드에 표시할 최근 매출 활동 이력
        // - 매출 발생일 기준 내림차순 정렬
        // - 최신 10건만 조회 (페이지 로딩 성능 고려)
        // - 전체 매출 상세는 별도 페이지에서 제공
        $recentSales = PartnerSales::where('partner_id', $partner->id)
            ->orderBy('sales_date', 'desc')  // 최신 순 정렬
            ->limit(10)                       // 대시보드용 10건 제한
            ->get();

        // ========================================
        // Step 6: 하위 파트너 네트워크 정보 조회
        // ========================================
        // 현재 파트너가 추천/관리하는 하위 파트너들의 정보
        // - 파트너 네트워크 계층 구조에서 직속 하위 파트너만 조회
        // - 파트너 타입, 티어 정보 포함
        $subPartners = $this->getSubPartners($partner);

        // ========================================
        // Step 7: 파트너 네트워크 구조 정보 수집
        // ========================================
        // 파트너 조직도 및 네트워크 위치 정보
        // - 상위 파트너(추천인) 정보
        // - 하위 파트너 수 통계
        // - 네트워크 레벨 및 경로 정보
        $networkInfo = $this->getNetworkInfo($partner);

        return view($this->config['index']['view'], [
            'user' => $user,
            'currentUser' => $user, // 현재 로그인 사용자 정보 명시적 전달
            'partner' => $partner,
            'salesStats' => $salesStats,
            'commissionStats' => $commissionStats,
            'recentSales' => $recentSales,
            'subPartners' => $subPartners,
            'networkInfo' => $networkInfo,
            'pageTitle' => '파트너 대시보드',
            'userInfo' => [
                'name' => $user->name ?? '',
                'email' => $user->email ?? '',
                'phone' => $user->profile->phone ?? '',
                'uuid' => $user->uuid
            ],
            'partnerCode' => $partner->partner_code // 파트너 코드 추가
        ]);
    }

    /**
     * 파트너 매출 통계 데이터 계산
     *
     * 기능:
     * - 파트너의 전체 매출 현황 및 성과 지표 계산
     * - 월별/연도별 매출 집계 (확정된 매출만 포함)
     * - SQLite 날짜 함수 활용한 기간별 필터링
     * - 대시보드 표시용 핵심 통계 데이터 구성
     *
     * 집계 데이터:
     * - monthly_sales: 파트너 모델에 저장된 월 매출 (캐시된 값)
     * - total_sales: 파트너 모델에 저장된 총 매출 (캐시된 값)
     * - team_sales: 팀 전체 매출 (하위 파트너 포함)
     * - current_month_sales: 이번 달 확정 매출 실시간 계산
     * - current_year_sales: 올해 확정 매출 실시간 계산
     * - total_sales_count: 전체 확정 매출 건수
     *
     * SQLite 날짜 처리:
     * - strftime('%Y-%m', sales_date): 년-월 형식으로 변환
     * - strftime('%Y', sales_date): 년도 추출
     * - 'confirmed' 상태의 매출만 집계하여 정확한 실적 계산
     *
     * @param PartnerUser $partner 통계를 계산할 파트너 모델 객체
     * @return array 매출 통계 배열
     *               - monthly_sales: 월간 매출 (모델 캐시값)
     *               - total_sales: 총 매출 (모델 캐시값)
     *               - team_sales: 팀 매출 (모델 캐시값)
     *               - current_month_sales: 이번 달 실제 매출
     *               - current_year_sales: 올해 실제 매출
     *               - total_sales_count: 총 매출 건수
     *
     * @throws \Exception 데이터베이스 쿼리 오류 시
     *
     * @since 1.0.0
     */
    private function calculateSalesStats($partner)
    {
        // 현재 날짜 기준으로 월/년도 문자열 생성 (SQLite 비교용)
        $currentMonth = now()->format('Y-m');  // 예: "2024-11"
        $currentYear = now()->format('Y');     // 예: "2024"

        return [
            // 파트너 모델에 캐시된 통계 값들 (성능 최적화)
            'monthly_sales' => $partner->monthly_sales ?? 0,
            'total_sales' => $partner->total_sales ?? 0,
            'team_sales' => $partner->team_sales ?? 0,

            // 실시간 계산 통계 (정확한 현재 상태)
            'current_month_sales' => PartnerSales::where('partner_id', $partner->id)
                ->where('status', 'confirmed')
                ->whereRaw("strftime('%Y-%m', sales_date) = ?", [$currentMonth])
                ->sum('amount'),
            'current_year_sales' => PartnerSales::where('partner_id', $partner->id)
                ->where('status', 'confirmed')
                ->whereRaw("strftime('%Y', sales_date) = ?", [$currentYear])
                ->sum('amount'),
            'total_sales_count' => PartnerSales::where('partner_id', $partner->id)
                ->where('status', 'confirmed')
                ->count(),
        ];
    }

    /**
     * 파트너 커미션 통계 데이터 계산
     *
     * 기능:
     * - 파트너의 커미션 수익 현황 및 지급 상태별 집계
     * - 지급 완료/대기 중 커미션 구분 계산
     * - 월별 커미션 수익 트렌드 분석
     * - 대시보드용 커미션 핵심 지표 제공
     *
     * 커미션 상태 분류:
     * - 'paid': 이미 지급 완료된 커미션 (실제 수익)
     * - 'pending': 지급 대기 중인 커미션 (예상 수익)
     * - 기타 상태는 집계에서 제외
     *
     * 집계 항목:
     * - total_commission: 총 지급 완료 커미션 금액
     * - pending_commission: 지급 대기 중인 커미션 금액
     * - this_month_commission: 이번 달 지급된 커미션
     * - commission_count: 전체 커미션 발생 건수 (상태 무관)
     *
     * 날짜 기준:
     * - created_at 기준으로 월별 집계 (커미션 발생일 기준)
     * - SQLite strftime 함수 활용한 월 단위 필터링
     *
     * 수익 분석 활용:
     * - 실제 수익 vs 예상 수익 비교
     * - 월별 커미션 성장 추이 파악
     * - 수익 안정성 지표 계산
     *
     * @param PartnerUser $partner 커미션 통계를 계산할 파트너 모델 객체
     * @return array 커미션 통계 배열
     *               - total_commission: 총 지급 완료 커미션 금액
     *               - pending_commission: 지급 대기 중 커미션 금액
     *               - this_month_commission: 이번 달 지급 커미션
     *               - commission_count: 총 커미션 건수
     *
     * @throws \Exception 데이터베이스 쿼리 오류 시
     *
     * @since 1.0.0
     */
    private function calculateCommissionStats($partner)
    {
        return [
            'total_commission' => PartnerCommission::where('partner_id', $partner->id)
                ->where('status', 'paid')
                ->sum('amount'),
            'pending_commission' => PartnerCommission::where('partner_id', $partner->id)
                ->where('status', 'pending')
                ->sum('amount'),
            'this_month_commission' => PartnerCommission::where('partner_id', $partner->id)
                ->where('status', 'paid')
                ->whereRaw("strftime('%Y-%m', created_at) = ?", [now()->format('Y-m')])
                ->sum('amount'),
            'commission_count' => PartnerCommission::where('partner_id', $partner->id)
                ->count(),
        ];
    }

    /**
     * 하위 파트너 네트워크 정보 조회
     *
     * 기능:
     * - 현재 파트너가 직접 추천/관리하는 하위 파트너들의 정보 수집
     * - 파트너 네트워크 계층 구조에서 1단계 하위 파트너만 조회
     * - 대시보드 표시용 최신 가입 파트너 우선 정렬
     * - 파트너 타입 및 등급 정보 포함 조회
     *
     * 조회 과정:
     * 1. PartnerNetworkRelationship 테이블에서 부모-자식 관계 조회
     * 2. 현재 파트너를 parent_id로 하는 활성 관계만 필터링
     * 3. 관계 테이블에서 하위 파트너 ID 목록 추출
     * 4. PartnerUser 테이블에서 실제 파트너 정보 조회
     * 5. Eager Loading으로 파트너 타입/등급 관계 데이터 포함
     *
     * 네트워크 관계 조건:
     * - parent_id: 현재 파트너 ID (추천인)
     * - is_active: true (활성 관계만)
     * - 비활성화된 관계는 제외하여 현재 유효한 네트워크만 표시
     *
     * 정렬 및 제한:
     * - created_at 내림차순 (최신 가입 파트너 우선)
     * - 10건 제한 (대시보드 성능 최적화)
     * - 전체 하위 파트너 목록은 별도 페이지에서 제공
     *
     * 관계 데이터:
     * - partnerType: 파트너 유형 (개인, 기업 등)
     * - partnerTier: 파트너 등급 (브론즈, 실버, 골드 등)
     *
     * @param PartnerUser $partner 하위 파트너를 조회할 상위 파트너 모델 객체
     * @return \Illuminate\Database\Eloquent\Collection 하위 파트너 컬렉션
     *         각 요소는 파트너 타입과 등급 정보를 포함한 PartnerUser 모델
     *
     * @throws \Exception 데이터베이스 관계 조회 오류 시
     *
     * @since 1.0.0
     */
    private function getSubPartners($partner)
    {
        // 네트워크 관계에서 현재 파트너가 parent인 관계들을 조회
        $childrenIds = PartnerNetworkRelationship::where('parent_id', $partner->id)
            ->where('is_active', true)
            ->pluck('child_id');

        return PartnerUser::whereIn('id', $childrenIds)
            ->with(['partnerType', 'partnerTier'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * 파트너 네트워크 구조 및 위치 정보 조회
     *
     * 기능:
     * - 파트너 조직도 내에서의 현재 파트너 위치 정보 수집
     * - 상위 파트너(추천인) 및 하위 파트너 관계 분석
     * - 네트워크 계층 구조 및 경로 정보 계산
     * - 대시보드용 조직도 표시 데이터 구성
     *
     * 수집 정보:
     * 1. 상위 파트너 정보 (추천인)
     *    - PartnerNetworkRelationship에서 child_id로 현재 파트너 조회
     *    - 상위 파트너의 타입 및 등급 정보 포함 조회
     *    - 추천인이 없는 경우 null 반환 (최상위 파트너)
     *
     * 2. 하위 파트너 수 통계
     *    - 현재 파트너를 parent_id로 하는 관계 수 계산
     *    - 직속 하위 파트너만 카운트 (1단계)
     *    - 전체 하위 네트워크 크기 파악
     *
     * 3. 네트워크 레벨 및 경로
     *    - level: 파트너 모델에 저장된 네트워크 단계
     *    - path: 최상위부터 현재까지의 경로 정보
     *    - 조직도에서의 위치 표시에 활용
     *
     * 네트워크 구조 활용:
     * - 조직도 시각화 데이터 제공
     * - 파트너 권한 및 접근 범위 결정
     * - 수수료 분배 계산 기준
     * - 성과 관리 및 평가 체계
     *
     * 관계 데이터 조회:
     * - 상위 파트너: partnerType, partnerTier 관계 포함
     * - 지연 로딩 방지를 위한 Eager Loading 적용
     * - 존재하지 않는 관계에 대한 안전한 처리
     *
     * @param PartnerUser $partner 네트워크 정보를 조회할 파트너 모델 객체
     * @return array 네트워크 정보 배열
     *               - parent_partner: 상위 파트너 객체 (PartnerUser|null)
     *               - children_count: 직속 하위 파트너 수 (int)
     *               - level: 네트워크 레벨 (int, 기본값: 0)
     *               - path: 네트워크 경로 (string, 기본값: '/')
     *
     * @throws \Exception 네트워크 관계 조회 오류 시
     *
     * @since 1.0.0
     */
    private function getNetworkInfo($partner)
    {
        // 상위 파트너 조회
        $parentPartner = null;
        $parentRelationship = PartnerNetworkRelationship::where('child_id', $partner->id)->first();
        if ($parentRelationship) {
            $parentPartner = PartnerUser::with(['partnerType', 'partnerTier'])
                ->find($parentRelationship->parent_id);
        }

        // 하위 파트너 수 계산
        $childrenCount = PartnerNetworkRelationship::where('parent_id', $partner->id)->count();

        // 레벨 계산
        $level = $partner->level ?? 0;

        // 네트워크 경로 계산
        $path = $partner->path ?? '/';

        return [
            'parent_partner' => $parentPartner,
            'children_count' => $childrenCount,
            'level' => $level,
            'path' => $path,
        ];
    }
}
