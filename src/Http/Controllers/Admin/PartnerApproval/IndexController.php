<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerApproval;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerTier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IndexController extends Controller
{
    /**
     * 파트너 승인 관리 대시보드
     */
    public function __invoke(Request $request)
    {
        // 필터 옵션
        $status = $request->get('status', 'all');
        $search = $request->get('search', '');
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $perPage = $request->get('per_page', 15);

        // 기본 쿼리
        $query = PartnerApplication::with(['user', 'approver', 'rejector', 'referrerPartner']);

        // 상태 필터
        if ($status !== 'all') {
            switch ($status) {
                case 'pending':
                    $query->whereIn('application_status', ['submitted', 'reviewing']);
                    break;
                case 'interview':
                    $query->where('application_status', 'interview');
                    break;
                case 'approved':
                    $query->where('application_status', 'approved');
                    break;
                case 'rejected':
                    $query->where('application_status', 'rejected');
                    break;
                case 'reapplied':
                    $query->where('application_status', 'reapplied');
                    break;
                default:
                    $query->where('application_status', $status);
                    break;
            }
        } else {
            // draft는 관리자 화면에서 제외
            $query->where('application_status', '!=', 'draft');
        }

        // 검색 필터
        if ($search) {
            $query->whereHas('user', function ($userQuery) use ($search) {
                $userQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })->orWhereJsonContains('personal_info->name', $search)
                ->orWhereJsonContains('personal_info->phone', $search);
        }

        // 정렬
        $allowedSorts = ['created_at', 'updated_at', 'application_status'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // 페이지네이션
        $applications = $query->paginate($perPage)->appends($request->query());

        // 통계 데이터
        $statistics = $this->getStatistics();

        // 최근 활동
        $recentActivities = $this->getRecentActivities();

        // 필터 옵션 데이터
        $filterOptions = $this->getFilterOptions();

        return view('jiny-partner::admin.partner-approval.index', [
            'applications' => $applications,
            'statistics' => $statistics,
            'recentActivities' => $recentActivities,
            'filterOptions' => $filterOptions,
            'currentFilters' => [
                'status' => $status,
                'search' => $search,
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
                'per_page' => $perPage
            ],
            'pageTitle' => '파트너 승인 관리'
        ]);
    }

    /**
     * 통계 데이터 생성
     */
    private function getStatistics()
    {
        $stats = [
            'total' => PartnerApplication::where('application_status', '!=', 'draft')->count(),
            'pending' => PartnerApplication::whereIn('application_status', ['submitted', 'reviewing'])->count(),
            'interview' => PartnerApplication::where('application_status', 'interview')->count(),
            'approved' => PartnerApplication::where('application_status', 'approved')->count(),
            'rejected' => PartnerApplication::where('application_status', 'rejected')->count(),
            'reapplied' => PartnerApplication::where('application_status', 'reapplied')->count(),
        ];

        // 이번 달 통계
        $thisMonth = [
            'submitted' => PartnerApplication::where('application_status', '!=', 'draft')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'approved' => PartnerApplication::where('application_status', 'approved')
                ->whereMonth('approval_date', now()->month)
                ->whereYear('approval_date', now()->year)
                ->count(),
            'rejected' => PartnerApplication::where('application_status', 'rejected')
                ->whereMonth('rejection_date', now()->month)
                ->whereYear('rejection_date', now()->year)
                ->count(),
        ];

        // 승인률 계산
        $totalProcessed = $stats['approved'] + $stats['rejected'];
        $approvalRate = $totalProcessed > 0 ? round(($stats['approved'] / $totalProcessed) * 100, 1) : 0;

        // 평균 처리 시간 (일)
        $avgProcessingTime = PartnerApplication::whereIn('application_status', ['approved', 'rejected'])
            ->select(DB::raw('AVG(julianday(COALESCE(approval_date, rejection_date)) - julianday(created_at)) as avg_days'))
            ->value('avg_days');

        return [
            'counts' => $stats,
            'this_month' => $thisMonth,
            'approval_rate' => $approvalRate,
            'avg_processing_days' => $avgProcessingTime ? round($avgProcessingTime, 1) : 0,
            'urgent_count' => PartnerApplication::where('application_status', 'submitted')
                ->where('created_at', '<', now()->subDays(7))
                ->count()
        ];
    }

    /**
     * 최근 활동 조회
     */
    private function getRecentActivities()
    {
        return PartnerApplication::with(['user', 'approver', 'rejector'])
            ->where('application_status', '!=', 'draft')
            ->whereIn('application_status', ['submitted', 'approved', 'rejected', 'interview'])
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($application) {
                $activity = [
                    'id' => $application->id,
                    'user_name' => $application->user->name ?? ($application->personal_info['name'] ?? 'Unknown'),
                    'status' => $application->application_status,
                    'date' => $application->updated_at,
                    'admin' => null
                ];

                switch ($application->application_status) {
                    case 'submitted':
                    case 'reapplied':
                        $activity['action'] = '신청서 제출';
                        $activity['date'] = $application->created_at;
                        break;
                    case 'approved':
                        $activity['action'] = '승인 완료';
                        $activity['date'] = $application->approval_date;
                        $activity['admin'] = $application->approver;
                        break;
                    case 'rejected':
                        $activity['action'] = '신청 반려';
                        $activity['date'] = $application->rejection_date;
                        $activity['admin'] = $application->rejector;
                        break;
                    case 'interview':
                        $activity['action'] = '면접 예정';
                        $activity['date'] = $application->interview_date;
                        break;
                    default:
                        $activity['action'] = '상태 변경';
                        break;
                }

                return $activity;
            });
    }

    /**
     * 필터 옵션 데이터
     */
    private function getFilterOptions()
    {
        return [
            'statuses' => [
                'all' => '전체',
                'pending' => '검토 대기',
                'interview' => '면접 예정',
                'approved' => '승인 완료',
                'rejected' => '반려',
                'reapplied' => '재신청'
            ],
            'sort_options' => [
                'created_at' => '신청일시',
                'updated_at' => '최종 수정일',
                'application_status' => '상태'
            ],
            'per_page_options' => [10, 15, 25, 50],
            'tiers' => PartnerTier::active()->orderBy('priority_level')->get()
        ];
    }
}