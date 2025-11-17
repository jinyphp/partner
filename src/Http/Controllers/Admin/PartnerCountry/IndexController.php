<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerCountry;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Models\PartnerSale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * 국가별 파트너 현황 및 매출 분석 컨트롤러
 *
 * 주요 기능:
 * - 국가별 파트너 등록 현황 통계
 * - 국가별 매출액 비교 분석
 * - 파트너 분포도 및 성과 지표
 * - 시계열 데이터 분석
 */
class IndexController extends Controller
{
    /**
     * 국가별 파트너 현황 대시보드
     */
    public function __invoke(Request $request)
    {
        try {
            // 기본 기간 설정 (최근 12개월)
            $startDate = $request->input('start_date', Carbon::now()->subMonths(12)->format('Y-m-01'));
            $endDate = $request->input('end_date', Carbon::now()->format('Y-m-t'));
            $period = $request->input('period', 'month'); // month, quarter, year

            Log::info('국가별 파트너 현황 조회', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'period' => $period
            ]);

            // 1. 국가별 파트너 등록 현황
            $partnerStats = $this->getPartnerStatsByCountry($startDate, $endDate);

            // 2. 국가별 매출 현황
            $salesStats = $this->getSalesStatsByCountry($startDate, $endDate);

            // 3. 국가별 시계열 데이터
            $timeSeriesData = $this->getTimeSeriesData($startDate, $endDate, $period);

            // 4. 성과 지표 요약
            $performanceMetrics = $this->getPerformanceMetrics($startDate, $endDate);

            // 5. Top 10 국가
            $topCountries = $this->getTopCountries($startDate, $endDate);

            return view('jiny-partner::admin.country.index', [
                'partnerStats' => $partnerStats,
                'salesStats' => $salesStats,
                'timeSeriesData' => $timeSeriesData,
                'performanceMetrics' => $performanceMetrics,
                'topCountries' => $topCountries,
                'filters' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'period' => $period
                ],
                'pageTitle' => '국가별 파트너 현황 분석'
            ]);

        } catch (\Exception $e) {
            Log::error('국가별 파트너 현황 조회 실패', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return view('jiny-partner::admin.country.index', [
                'error' => '데이터를 불러오는 중 오류가 발생했습니다.',
                'pageTitle' => '국가별 파트너 현황 분석'
            ]);
        }
    }

    /**
     * 국가별 파트너 등록 현황 통계 (partner_users 기반)
     */
    private function getPartnerStatsByCountry($startDate, $endDate)
    {
        try {
            return DB::table('partner_users as pu')
                ->leftJoin('partner_applications as pa', function($join) {
                    $join->on('pu.user_uuid', '=', 'pa.user_uuid')
                         ->where('pa.application_status', '=', 'approved');
                })
                ->select([
                    DB::raw('COALESCE(NULLIF(pa.country, ""), "ETC") as country'),
                    DB::raw('COUNT(pu.id) as total_applications'),
                    DB::raw('COUNT(CASE WHEN pu.status = "active" THEN 1 END) as approved_count'),
                    DB::raw('COUNT(CASE WHEN pu.status = "inactive" THEN 1 END) as rejected_count'),
                    DB::raw('COUNT(CASE WHEN pu.status = "pending" THEN 1 END) as pending_count'),
                    DB::raw('COUNT(CASE WHEN pu.status IN ("reviewing", "interview") THEN 1 END) as in_progress_count'),
                    DB::raw('ROUND(COUNT(CASE WHEN pu.status = "active" THEN 1 END) * 100.0 / COUNT(pu.id), 2) as approval_rate')
                ])
                ->whereBetween('pu.created_at', [$startDate, $endDate])
                ->groupBy(DB::raw('COALESCE(NULLIF(pa.country, ""), "ETC")'))
                ->orderByDesc('total_applications')
                ->get()
                ->map(function ($item) {
                    $item->country_name = $this->getCountryName($item->country);
                    return $item;
                });
        } catch (\Exception $e) {
            // 오류 발생 시 기본 데이터 반환
            return collect([
                (object) [
                    'country' => 'ETC',
                    'country_name' => '기타지역',
                    'total_applications' => 0,
                    'approved_count' => 0,
                    'rejected_count' => 0,
                    'pending_count' => 0,
                    'in_progress_count' => 0,
                    'approval_rate' => 0
                ]
            ]);
        }
    }

    /**
     * 국가별 매출 현황 통계 (partner_users 기반)
     */
    private function getSalesStatsByCountry($startDate, $endDate)
    {
        try {
            return DB::table('partner_users as pu')
                ->leftJoin('partner_applications as pa', function($join) {
                    $join->on('pu.user_uuid', '=', 'pa.user_uuid')
                         ->where('pa.application_status', '=', 'approved');
                })
                ->select([
                    DB::raw('COALESCE(NULLIF(pa.country, ""), "ETC") as country'),
                    DB::raw('COUNT(pu.id) as partner_count'),
                    DB::raw('SUM(pu.ytd_deals) as total_sales_count'),
                    DB::raw('SUM(pu.total_sales) as confirmed_sales'),
                    DB::raw('SUM(pu.current_month_sales) as pending_sales'),
                    DB::raw('AVG(NULLIF(pu.total_sales, 0)) as avg_sale_amount'),
                    DB::raw('SUM(pu.total_sales) as total_sales'),
                    DB::raw('MAX(pu.total_sales) as max_sale_amount'),
                    DB::raw('MIN(NULLIF(pu.total_sales, 0)) as min_sale_amount')
                ])
                ->where('pu.status', '=', 'active')
                ->whereBetween('pu.created_at', [$startDate, $endDate])
                ->groupBy(DB::raw('COALESCE(NULLIF(pa.country, ""), "ETC")'))
                ->orderByDesc('confirmed_sales')
                ->get()
                ->map(function ($item) {
                    $item->country_name = $this->getCountryName($item->country);
                    $item->avg_per_partner = $item->partner_count > 0 ? round(($item->confirmed_sales ?? 0) / $item->partner_count, 2) : 0;
                    return $item;
                });
        } catch (\Exception $e) {
            // 오류 발생 시 기본 데이터 반환
            return collect([
                (object) [
                    'country' => 'ETC',
                    'country_name' => '기타지역',
                    'partner_count' => 0,
                    'total_sales_count' => 0,
                    'confirmed_sales' => 0,
                    'pending_sales' => 0,
                    'avg_sale_amount' => 0,
                    'total_sales' => 0,
                    'max_sale_amount' => 0,
                    'min_sale_amount' => 0,
                    'avg_per_partner' => 0
                ]
            ]);
        }
    }

    /**
     * 시계열 데이터 (월별/분기별/연도별)
     */
    private function getTimeSeriesData($startDate, $endDate, $period)
    {
        $dateFormat = match($period) {
            'month' => '%Y-%m',
            'quarter' => '%Y-Q%q',
            'year' => '%Y',
            default => '%Y-%m'
        };

        // 파트너 등록 추이 (partner_users 기반)
        try {
            $partnerTrend = DB::table('partner_users as pu')
                ->leftJoin('partner_applications as pa', 'pu.user_uuid', '=', 'pa.user_uuid')
                ->select([
                    DB::raw('COALESCE(NULLIF(pa.country, ""), "ETC") as country'),
                    DB::raw("DATE_FORMAT(pu.created_at, '$dateFormat') as period"),
                    DB::raw('COUNT(pu.id) as applications'),
                    DB::raw('COUNT(CASE WHEN pu.status = "active" THEN 1 END) as approvals')
                ])
                ->whereBetween('pu.created_at', [$startDate, $endDate])
                ->groupBy(DB::raw('COALESCE(NULLIF(pa.country, ""), "ETC")'), 'period')
                ->orderBy('period')
                ->get();
        } catch (\Exception $e) {
            $partnerTrend = collect();
        }

        // 매출 추이 (partner_users 기반)
        try {
            $salesTrend = DB::table('partner_users as pu')
                ->leftJoin('partner_applications as pa', 'pu.user_uuid', '=', 'pa.user_uuid')
                ->select([
                    DB::raw('COALESCE(NULLIF(pa.country, ""), "ETC") as country'),
                    DB::raw("DATE_FORMAT(pu.created_at, '$dateFormat') as period"),
                    DB::raw('SUM(pu.ytd_deals) as sales_count'),
                    DB::raw('SUM(pu.total_sales) as sales_amount')
                ])
                ->where('pu.status', '=', 'active')
                ->whereBetween('pu.created_at', [$startDate, $endDate])
                ->groupBy(DB::raw('COALESCE(NULLIF(pa.country, ""), "ETC")'), 'period')
                ->orderBy('period')
                ->get();
        } catch (\Exception $e) {
            $salesTrend = collect();
        }

        return [
            'partner_trend' => $partnerTrend,
            'sales_trend' => $salesTrend
        ];
    }

    /**
     * 성과 지표 요약 (partner_users 기반)
     */
    private function getPerformanceMetrics($startDate, $endDate)
    {
        try {
            // partner_users 기반 전체 요약 지표
            $totalApplications = DB::table('partner_users')
                ->whereBetween('created_at', [$startDate, $endDate])->count();

            $totalApproved = DB::table('partner_users')
                ->where('status', 'active')
                ->whereBetween('created_at', [$startDate, $endDate])->count();

            $totalSales = DB::table('partner_users')
                ->where('status', 'active')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('total_sales') ?? 0;

            $activeCountries = DB::table('partner_users as pu')
                ->leftJoin('partner_applications as pa', 'pu.user_uuid', '=', 'pa.user_uuid')
                ->whereBetween('pu.created_at', [$startDate, $endDate])
                ->select(DB::raw('COALESCE(NULLIF(pa.country, ""), "ETC") as normalized_country'))
                ->distinct('normalized_country')->count('normalized_country');

            return [
                'total_applications' => $totalApplications,
                'total_approved' => $totalApproved,
                'approval_rate' => $totalApplications > 0 ? round(($totalApproved / $totalApplications) * 100, 2) : 0,
                'total_sales' => $totalSales,
                'active_countries' => $activeCountries,
                'avg_sales_per_country' => $activeCountries > 0 ? round($totalSales / $activeCountries, 2) : 0
            ];
        } catch (\Exception $e) {
            // 오류 발생 시 기본 데이터 반환
            return [
                'total_applications' => 0,
                'total_approved' => 0,
                'approval_rate' => 0,
                'total_sales' => 0,
                'active_countries' => 1,
                'avg_sales_per_country' => 0
            ];
        }
    }

    /**
     * Top 10 국가 (매출액 기준, partner_users 기반)
     */
    private function getTopCountries($startDate, $endDate)
    {
        try {
            return DB::table('partner_users as pu')
                ->leftJoin('partner_applications as pa', 'pu.user_uuid', '=', 'pa.user_uuid')
                ->select([
                    DB::raw('COALESCE(NULLIF(pa.country, ""), "ETC") as country'),
                    DB::raw('SUM(pu.total_sales) as total_sales'),
                    DB::raw('COUNT(pu.id) as partner_count'),
                    DB::raw('SUM(pu.ytd_deals) as sales_count')
                ])
                ->where('pu.status', '=', 'active')
                ->whereBetween('pu.created_at', [$startDate, $endDate])
                ->groupBy(DB::raw('COALESCE(NULLIF(pa.country, ""), "ETC")'))
                ->orderByDesc('total_sales')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    $item->country_name = $this->getCountryName($item->country);
                    return $item;
                });
        } catch (\Exception $e) {
            // 오류 발생 시 기본 데이터 반환
            return collect([
                (object) [
                    'country' => 'ETC',
                    'country_name' => '기타지역',
                    'total_sales' => 0,
                    'partner_count' => 0,
                    'sales_count' => 0
                ]
            ]);
        }
    }

    /**
     * 국가 코드를 국가명으로 변환
     */
    private function getCountryName($countryCode)
    {
        $countries = [
            'KR' => '대한민국',
            'US' => '미국',
            'JP' => '일본',
            'CN' => '중국',
            'GB' => '영국',
            'DE' => '독일',
            'FR' => '프랑스',
            'CA' => '캐나다',
            'AU' => '호주',
            'SG' => '싱가포르',
            'TH' => '태국',
            'VN' => '베트남',
            'ID' => '인도네시아',
            'MY' => '말레이시아',
            'PH' => '필리핀',
            'IN' => '인도',
            'RU' => '러시아',
            'BR' => '브라질',
            'MX' => '멕시코',
            'ES' => '스페인',
            'IT' => '이탈리아',
            'NL' => '네덜란드',
            'SE' => '스웨덴',
            'NO' => '노르웨이',
            'DK' => '덴마크',
            'ETC' => '기타지역',
        ];

        // NULL이나 빈 값, 알 수 없는 국가 코드는 모두 "기타지역"으로 처리
        if (empty($countryCode) || $countryCode === 'ETC') {
            return '기타지역';
        }

        return $countries[$countryCode] ?? '기타지역';
    }
}