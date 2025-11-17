<?php

namespace Jiny\Partner\Http\Controllers\Admin;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PartnerActivityLogsController extends BaseController
{
    // 미들웨어는 라우트에서 이미 적용되므로 제거

    /**
     * 파트너 활동 로그 목록
     */
    public function index(Request $request)
    {
        $query = DB::table('partner_activity_logs as pal')
            ->leftJoin('partner_users as pu', 'pal.partner_id', '=', 'pu.id')
            ->leftJoin('partner_applications as pa', 'pal.application_id', '=', 'pa.id')
            ->leftJoin('users as u', 'pal.user_id', '=', 'u.id')
            ->select([
                'pal.*',
                'pu.name as partner_name',
                'pu.email as partner_email',
                'pa.application_status',
                'u.name as user_name'
            ]);

        // 필터링
        if ($request->filled('partner_id')) {
            $query->where('pal.partner_id', $request->partner_id);
        }

        if ($request->filled('activity_type')) {
            $query->where('pal.activity_type', $request->activity_type);
        }

        if ($request->filled('date_from')) {
            $query->where('pal.created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('pal.created_at', '<=', $request->date_to . ' 23:59:59');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('pu.name', 'LIKE', "%{$search}%")
                  ->orWhere('pu.email', 'LIKE', "%{$search}%")
                  ->orWhere('pal.notes', 'LIKE', "%{$search}%")
                  ->orWhere('pal.old_value', 'LIKE', "%{$search}%")
                  ->orWhere('pal.new_value', 'LIKE', "%{$search}%");
            });
        }

        $logs = $query->orderBy('pal.created_at', 'desc')->paginate(20);

        // 활동 유형 목록
        $activityTypes = [
            'application_submitted' => '신청서 제출',
            'status_changed' => '상태 변경',
            'interview_scheduled' => '면접 일정 설정',
            'approved' => '승인 완료',
            'rejected' => '신청 거부',
            'reapplied' => '재신청',
            'tier_changed' => '등급 변경',
            'performance_updated' => '성과 업데이트'
        ];

        return view('jiny-partner::admin.partner-activity-logs.index', compact('logs', 'activityTypes'));
    }

    /**
     * 활동 로그 상세 조회
     */
    public function show($id)
    {
        $log = DB::table('partner_activity_logs as pal')
            ->leftJoin('partner_users as pu', 'pal.partner_id', '=', 'pu.id')
            ->leftJoin('partner_applications as pa', 'pal.application_id', '=', 'pa.id')
            ->leftJoin('users as u', 'pal.user_id', '=', 'u.id')
            ->select([
                'pal.*',
                'pu.name as partner_name',
                'pu.email as partner_email',
                'pu.partner_code',
                'pa.application_status',
                'u.name as user_name',
                'u.email as user_email'
            ])
            ->where('pal.id', $id)
            ->first();

        if (!$log) {
            abort(404);
        }

        // 메타데이터 파싱
        $metadata = null;
        if ($log->metadata) {
            $metadata = json_decode($log->metadata, true);
        }

        return view('jiny-partner::admin.partner-activity-logs.show', compact('log', 'metadata'));
    }

    /**
     * 활동 로그 생성
     */
    public function store(Request $request)
    {
        $request->validate([
            'partner_id' => 'nullable|exists:partner_users,id',
            'application_id' => 'nullable|exists:partner_applications,id',
            'activity_type' => 'required|string|max:50',
            'old_value' => 'nullable|string|max:500',
            'new_value' => 'nullable|string|max:500',
            'metadata' => 'nullable|array',
            'notes' => 'nullable|string'
        ]);

        $logData = [
            'partner_id' => $request->partner_id,
            'application_id' => $request->application_id,
            'user_id' => Auth::id(),
            'user_uuid' => Auth::user()->uuid ?? null,
            'activity_type' => $request->activity_type,
            'old_value' => $request->old_value,
            'new_value' => $request->new_value,
            'metadata' => $request->metadata ? json_encode($request->metadata) : null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'notes' => $request->notes,
            'created_at' => now(),
            'updated_at' => now()
        ];

        $logId = DB::table('partner_activity_logs')->insertGetId($logData);

        return response()->json([
            'success' => true,
            'message' => '활동 로그가 생성되었습니다.',
            'log_id' => $logId
        ]);
    }

    /**
     * 파트너별 활동 로그 조회
     */
    public function getPartnerLogs($partnerId)
    {
        $logs = DB::table('partner_activity_logs as pal')
            ->leftJoin('users as u', 'pal.user_id', '=', 'u.id')
            ->select([
                'pal.*',
                'u.name as user_name'
            ])
            ->where('pal.partner_id', $partnerId)
            ->orderBy('pal.created_at', 'desc')
            ->get();

        return response()->json(['logs' => $logs]);
    }

    /**
     * 신청서별 활동 로그 조회
     */
    public function getApplicationLogs($applicationId)
    {
        $logs = DB::table('partner_activity_logs as pal')
            ->leftJoin('users as u', 'pal.user_id', '=', 'u.id')
            ->select([
                'pal.*',
                'u.name as user_name'
            ])
            ->where('pal.application_id', $applicationId)
            ->orderBy('pal.created_at', 'desc')
            ->get();

        return response()->json(['logs' => $logs]);
    }

    /**
     * 활동 통계 조회
     */
    public function stats(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subDays(30)->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());

        // 활동 유형별 통계
        $activityStats = DB::table('partner_activity_logs')
            ->select('activity_type', DB::raw('count(*) as count'))
            ->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])
            ->groupBy('activity_type')
            ->get();

        // 일별 활동 통계
        $dailyStats = DB::table('partner_activity_logs')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )
            ->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        // 사용자별 활동 통계 (상위 10명)
        $userStats = DB::table('partner_activity_logs as pal')
            ->leftJoin('users as u', 'pal.user_id', '=', 'u.id')
            ->select('u.name', DB::raw('count(*) as count'))
            ->whereBetween('pal.created_at', [$dateFrom, $dateTo . ' 23:59:59'])
            ->groupBy('u.name')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return response()->json([
            'activity_stats' => $activityStats,
            'daily_stats' => $dailyStats,
            'user_stats' => $userStats
        ]);
    }

    /**
     * 로그 내보내기
     */
    public function export(Request $request)
    {
        $query = DB::table('partner_activity_logs as pal')
            ->leftJoin('partner_users as pu', 'pal.partner_id', '=', 'pu.id')
            ->leftJoin('partner_applications as pa', 'pal.application_id', '=', 'pa.id')
            ->leftJoin('users as u', 'pal.user_id', '=', 'u.id')
            ->select([
                'pal.id',
                'pal.created_at',
                'pu.name as partner_name',
                'pu.email as partner_email',
                'pal.activity_type',
                'pal.old_value',
                'pal.new_value',
                'pal.ip_address',
                'u.name as user_name',
                'pal.notes'
            ]);

        // 필터 적용
        if ($request->filled('date_from')) {
            $query->where('pal.created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('pal.created_at', '<=', $request->date_to . ' 23:59:59');
        }
        if ($request->filled('activity_type')) {
            $query->where('pal.activity_type', $request->activity_type);
        }

        $logs = $query->orderBy('pal.created_at', 'desc')->get();

        $filename = 'partner_activity_logs_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM 추가
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // 헤더 행
            fputcsv($file, [
                'ID', '날짜시간', '파트너명', '파트너이메일', '활동유형',
                '이전값', '새로운값', 'IP주소', '작업자', '메모'
            ]);

            // 데이터 행
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->created_at,
                    $log->partner_name,
                    $log->partner_email,
                    $log->activity_type,
                    $log->old_value,
                    $log->new_value,
                    $log->ip_address,
                    $log->user_name,
                    $log->notes
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * 활동 로그 추가 (헬퍼 메서드)
     */
    public static function logActivity($activityType, $partnerId = null, $applicationId = null, $oldValue = null, $newValue = null, $metadata = null, $notes = null)
    {
        $logData = [
            'partner_id' => $partnerId,
            'application_id' => $applicationId,
            'user_id' => Auth::id(),
            'user_uuid' => Auth::user()->uuid ?? null,
            'activity_type' => $activityType,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'metadata' => $metadata ? json_encode($metadata) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'notes' => $notes,
            'created_at' => now(),
            'updated_at' => now()
        ];

        return DB::table('partner_activity_logs')->insert($logData);
    }
}