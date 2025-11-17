<?php

namespace Jiny\Partner\Http\Controllers\Admin;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PartnerPerformanceMetricsController extends BaseController
{
    /**
     * 성과 지표 목록
     */
    public function index(Request $request)
    {
        $query = DB::table('partner_performance_metrics as ppm')
            ->leftJoin('partner_users as pu', 'ppm.partner_id', '=', 'pu.id')
            ->leftJoin('users as u', 'pu.user_id', '=', 'u.id')
            ->select([
                'ppm.*',
                'u.name as partner_name',
                'u.email as partner_email',
                'pu.tier_level'
            ]);

        // 필터링
        if ($request->filled('partner_id')) {
            $query->where('ppm.partner_id', $request->partner_id);
        }

        if ($request->filled('period_type')) {
            $query->where('ppm.period_type', $request->period_type);
        }

        if ($request->filled('period_year')) {
            $query->whereYear('ppm.period_start', $request->period_year);
        }

        if ($request->filled('period_month')) {
            $query->whereMonth('ppm.period_start', $request->period_month);
        }

        if ($request->filled('min_sales')) {
            $query->where('ppm.total_sales', '>=', $request->min_sales);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('u.name', 'LIKE', "%{$search}%")
                  ->orWhere('u.email', 'LIKE', "%{$search}%");
            });
        }

        $metrics = $query->orderBy('ppm.period_start', 'desc')
            ->orderBy('ppm.total_sales', 'desc')
            ->paginate(20);

        // 통계 데이터
        $stats = $this->getMetricsStats();

        // 파트너 목록
        $partners = DB::table('partner_users as pu')
            ->leftJoin('users as u', 'pu.user_id', '=', 'u.id')
            ->select(['pu.id', 'u.name', 'u.email'])
            ->orderBy('u.name')
            ->get();

        return view('jiny-partner::admin.partner-performance-metrics.index', compact('metrics', 'stats', 'partners'));
    }

    /**
     * 성과 지표 상세 보기
     */
    public function show($id)
    {
        $metric = DB::table('partner_performance_metrics as ppm')
            ->leftJoin('partner_users as pu', 'ppm.partner_id', '=', 'pu.id')
            ->leftJoin('users as u', 'pu.user_id', '=', 'u.id')
            ->select([
                'ppm.*',
                'u.name as partner_name',
                'u.email as partner_email',
                'pu.tier_level',
                'pu.tier_name'
            ])
            ->where('ppm.id', $id)
            ->first();

        if (!$metric) {
            return redirect()->back()->with('error', '성과 지표를 찾을 수 없습니다.');
        }

        // JSON 필드 파싱
        $metric->detailed_metrics = json_decode($metric->detailed_metrics, true) ?? [];
        $metric->goals_vs_actual = json_decode($metric->goals_vs_actual, true) ?? [];

        // 같은 기간 다른 파트너들과 비교 데이터
        $comparison = $this->getComparisonData($metric);

        // 이전 기간 성과와 비교
        $previousMetric = $this->getPreviousMetric($metric);

        return view('jiny-partner::admin.partner-performance-metrics.show', compact('metric', 'comparison', 'previousMetric'));
    }

    /**
     * 성과 지표 등록 폼
     */
    public function create(Request $request)
    {
        $partnerId = $request->get('partner_id');
        $partner = null;

        if ($partnerId) {
            $partner = DB::table('partner_users as pu')
                ->leftJoin('users as u', 'pu.user_id', '=', 'u.id')
                ->select(['pu.*', 'u.name as partner_name', 'u.email as partner_email'])
                ->where('pu.id', $partnerId)
                ->first();
        }

        // 파트너 목록
        $partners = DB::table('partner_users as pu')
            ->leftJoin('users as u', 'pu.user_id', '=', 'u.id')
            ->select(['pu.id', 'u.name', 'u.email'])
            ->orderBy('u.name')
            ->get();

        return view('jiny-partner::admin.partner-performance-metrics.create', compact('partner', 'partners'));
    }

    /**
     * 성과 지표 저장
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'partner_id' => 'required|exists:partner_users,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'period_type' => 'required|in:weekly,monthly,quarterly,yearly',

            // 매출 메트릭
            'total_sales' => 'nullable|numeric|min:0',
            'commission_earned' => 'nullable|numeric|min:0',
            'deals_closed' => 'nullable|integer|min:0',
            'average_deal_size' => 'nullable|numeric|min:0',

            // 활동 메트릭
            'leads_generated' => 'nullable|integer|min:0',
            'customers_acquired' => 'nullable|integer|min:0',
            'support_tickets_resolved' => 'nullable|integer|min:0',
            'training_sessions_conducted' => 'nullable|integer|min:0',

            // 품질 메트릭
            'customer_satisfaction_score' => 'nullable|numeric|min:0|max:5',
            'response_time_hours' => 'nullable|numeric|min:0',
            'complaints_received' => 'nullable|integer|min:0',
            'task_completion_rate' => 'nullable|numeric|min:0|max:100',

            // 네트워크 메트릭
            'referrals_made' => 'nullable|integer|min:0',
            'team_members_managed' => 'nullable|integer|min:0',
            'team_performance_bonus' => 'nullable|numeric|min:0',

            // JSON 데이터
            'detailed_metrics' => 'nullable|json',
            'goals_vs_actual' => 'nullable|json',
        ]);

        // 자동 계산 필드
        $validatedData['efficiency_score'] = $this->calculateEfficiencyScore($validatedData);
        $validatedData['growth_rate'] = $this->calculateGrowthRate($validatedData);
        $validatedData['rank_in_tier'] = null; // 저장 후 별도로 계산

        $metricId = DB::table('partner_performance_metrics')->insertGetId($validatedData);

        // 등급 내 순위 계산 및 업데이트
        $this->updateRankInTier($metricId, $validatedData);

        return redirect()
            ->route('admin.partner.performance.metrics.show', $metricId)
            ->with('success', '성과 지표가 성공적으로 등록되었습니다.');
    }

    /**
     * 성과 지표 수정 폼
     */
    public function edit($id)
    {
        $metric = DB::table('partner_performance_metrics as ppm')
            ->leftJoin('partner_users as pu', 'ppm.partner_id', '=', 'pu.id')
            ->leftJoin('users as u', 'pu.user_id', '=', 'u.id')
            ->select([
                'ppm.*',
                'u.name as partner_name',
                'u.email as partner_email'
            ])
            ->where('ppm.id', $id)
            ->first();

        if (!$metric) {
            return redirect()->back()->with('error', '성과 지표를 찾을 수 없습니다.');
        }

        // 파트너 목록
        $partners = DB::table('partner_users as pu')
            ->leftJoin('users as u', 'pu.user_id', '=', 'u.id')
            ->select(['pu.id', 'u.name', 'u.email'])
            ->orderBy('u.name')
            ->get();

        return view('jiny-partner::admin.partner-performance-metrics.edit', compact('metric', 'partners'));
    }

    /**
     * 성과 지표 업데이트
     */
    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'partner_id' => 'required|exists:partner_users,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'period_type' => 'required|in:weekly,monthly,quarterly,yearly',

            // 매출 메트릭
            'total_sales' => 'nullable|numeric|min:0',
            'commission_earned' => 'nullable|numeric|min:0',
            'deals_closed' => 'nullable|integer|min:0',
            'average_deal_size' => 'nullable|numeric|min:0',

            // 활동 메트릭
            'leads_generated' => 'nullable|integer|min:0',
            'customers_acquired' => 'nullable|integer|min:0',
            'support_tickets_resolved' => 'nullable|integer|min:0',
            'training_sessions_conducted' => 'nullable|integer|min:0',

            // 품질 메트릭
            'customer_satisfaction_score' => 'nullable|numeric|min:0|max:5',
            'response_time_hours' => 'nullable|numeric|min:0',
            'complaints_received' => 'nullable|integer|min:0',
            'task_completion_rate' => 'nullable|numeric|min:0|max:100',

            // 네트워크 메트릭
            'referrals_made' => 'nullable|integer|min:0',
            'team_members_managed' => 'nullable|integer|min:0',
            'team_performance_bonus' => 'nullable|numeric|min:0',

            // JSON 데이터
            'detailed_metrics' => 'nullable|json',
            'goals_vs_actual' => 'nullable|json',
        ]);

        // 자동 계산 필드
        $validatedData['efficiency_score'] = $this->calculateEfficiencyScore($validatedData);
        $validatedData['growth_rate'] = $this->calculateGrowthRate($validatedData);
        $validatedData['updated_at'] = now();

        DB::table('partner_performance_metrics')
            ->where('id', $id)
            ->update($validatedData);

        // 등급 내 순위 재계산
        $this->updateRankInTier($id, $validatedData);

        return redirect()
            ->route('admin.partner.performance.metrics.show', $id)
            ->with('success', '성과 지표가 성공적으로 수정되었습니다.');
    }

    /**
     * 성과 지표 삭제
     */
    public function destroy($id)
    {
        DB::table('partner_performance_metrics')->where('id', $id)->delete();

        return redirect()
            ->route('admin.partner.performance.metrics.index')
            ->with('success', '성과 지표가 삭제되었습니다.');
    }

    /**
     * 통계 데이터 조회
     */
    private function getMetricsStats()
    {
        $currentMonth = date('Y-m');
        $lastMonth = date('Y-m', strtotime('-1 month'));

        return [
            'total_records' => DB::table('partner_performance_metrics')->count(),
            'current_month_records' => DB::table('partner_performance_metrics')
                ->where('period_start', 'LIKE', $currentMonth . '%')
                ->count(),
            'total_partners' => DB::table('partner_performance_metrics')
                ->distinct('partner_id')
                ->count(),
            'avg_total_sales' => DB::table('partner_performance_metrics')
                ->avg('total_sales'),
            'top_performer' => DB::table('partner_performance_metrics as ppm')
                ->leftJoin('partner_users as pu', 'ppm.partner_id', '=', 'pu.id')
                ->leftJoin('users as u', 'pu.user_id', '=', 'u.id')
                ->select(['u.name', 'ppm.total_sales'])
                ->orderBy('ppm.total_sales', 'desc')
                ->first(),
        ];
    }

    /**
     * 효율성 점수 계산
     */
    private function calculateEfficiencyScore($data)
    {
        $totalSales = $data['total_sales'] ?? 0;
        $totalActivities = ($data['leads_generated'] ?? 0) +
                          ($data['customers_acquired'] ?? 0) +
                          ($data['support_tickets_resolved'] ?? 0);

        return $totalActivities > 0 ? round(($totalSales / $totalActivities), 2) : 0;
    }

    /**
     * 성장률 계산
     */
    private function calculateGrowthRate($data)
    {
        $partnerId = $data['partner_id'];
        $currentPeriodStart = $data['period_start'];
        $periodType = $data['period_type'];

        // 이전 기간 찾기
        $previousPeriod = null;
        switch ($periodType) {
            case 'monthly':
                $previousPeriod = date('Y-m-d', strtotime($currentPeriodStart . ' -1 month'));
                break;
            case 'quarterly':
                $previousPeriod = date('Y-m-d', strtotime($currentPeriodStart . ' -3 months'));
                break;
            case 'yearly':
                $previousPeriod = date('Y-m-d', strtotime($currentPeriodStart . ' -1 year'));
                break;
        }

        if (!$previousPeriod) {
            return 0;
        }

        $previousMetric = DB::table('partner_performance_metrics')
            ->where('partner_id', $partnerId)
            ->where('period_type', $periodType)
            ->where('period_start', $previousPeriod)
            ->first();

        if (!$previousMetric || $previousMetric->total_sales == 0) {
            return 0;
        }

        $currentSales = $data['total_sales'] ?? 0;
        return round((($currentSales - $previousMetric->total_sales) / $previousMetric->total_sales) * 100, 2);
    }

    /**
     * 등급 내 순위 계산 및 업데이트
     */
    private function updateRankInTier($metricId, $data)
    {
        $metric = DB::table('partner_performance_metrics')
            ->where('id', $metricId)
            ->first();

        if (!$metric) return;

        // 같은 기간 유형의 모든 메트릭을 매출순으로 정렬하여 순위 계산
        $rankings = DB::table('partner_performance_metrics as ppm')
            ->leftJoin('partner_users as pu', 'ppm.partner_id', '=', 'pu.id')
            ->where('ppm.period_type', $metric->period_type)
            ->where('ppm.period_start', $metric->period_start)
            ->orderBy('ppm.total_sales', 'desc')
            ->pluck('ppm.id')
            ->toArray();

        $rank = array_search($metricId, $rankings) + 1;

        DB::table('partner_performance_metrics')
            ->where('id', $metricId)
            ->update(['rank_in_tier' => $rank]);
    }

    /**
     * 비교 데이터 조회
     */
    private function getComparisonData($metric)
    {
        return DB::table('partner_performance_metrics')
            ->where('period_type', $metric->period_type)
            ->where('period_start', $metric->period_start)
            ->where('id', '!=', $metric->id)
            ->selectRaw('
                AVG(total_sales) as avg_sales,
                MAX(total_sales) as max_sales,
                MIN(total_sales) as min_sales,
                COUNT(*) as total_partners
            ')
            ->first();
    }

    /**
     * 이전 기간 메트릭 조회
     */
    private function getPreviousMetric($metric)
    {
        $periodType = $metric->period_type;
        $currentPeriodStart = $metric->period_start;

        $previousPeriod = null;
        switch ($periodType) {
            case 'weekly':
                $previousPeriod = date('Y-m-d', strtotime($currentPeriodStart . ' -1 week'));
                break;
            case 'monthly':
                $previousPeriod = date('Y-m-d', strtotime($currentPeriodStart . ' -1 month'));
                break;
            case 'quarterly':
                $previousPeriod = date('Y-m-d', strtotime($currentPeriodStart . ' -3 months'));
                break;
            case 'yearly':
                $previousPeriod = date('Y-m-d', strtotime($currentPeriodStart . ' -1 year'));
                break;
        }

        if (!$previousPeriod) {
            return null;
        }

        return DB::table('partner_performance_metrics')
            ->where('partner_id', $metric->partner_id)
            ->where('period_type', $periodType)
            ->where('period_start', $previousPeriod)
            ->first();
    }
}