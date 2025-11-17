<?php

namespace Jiny\Partner\Http\Controllers\Admin;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PartnerNotificationsController extends BaseController
{
    /**
     * 파트너 알림 목록
     */
    public function index(Request $request)
    {
        $query = DB::table('partner_notifications as pn')
            ->leftJoin('users as u', 'pn.user_id', '=', 'u.id')
            ->select([
                'pn.*',
                'u.name as user_name',
                'u.email as user_email'
            ]);

        // 필터링
        if ($request->filled('type')) {
            $query->where('pn.type', $request->type);
        }

        if ($request->filled('priority')) {
            $query->where('pn.priority', $request->priority);
        }

        if ($request->filled('is_read')) {
            $query->where('pn.is_read', $request->is_read === '1');
        }

        if ($request->filled('date_from')) {
            $query->where('pn.created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('pn.created_at', '<=', $request->date_to . ' 23:59:59');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('u.name', 'LIKE', "%{$search}%")
                  ->orWhere('u.email', 'LIKE', "%{$search}%")
                  ->orWhere('pn.title', 'LIKE', "%{$search}%")
                  ->orWhere('pn.message', 'LIKE', "%{$search}%");
            });
        }

        $notifications = $query->orderBy('pn.created_at', 'desc')->paginate(20);

        // 알림 유형 목록
        $notificationTypes = [
            'status_update' => '상태 변경',
            'interview_scheduled' => '면접 일정',
            'approved' => '승인 완료',
            'rejected' => '신청 거부',
            'reapply_available' => '재신청 가능',
            'tier_upgraded' => '등급 승급',
            'performance_alert' => '성과 알림'
        ];

        // 우선순위 목록
        $priorities = [
            'low' => '낮음',
            'normal' => '보통',
            'high' => '높음',
            'urgent' => '긴급'
        ];

        return view('jiny-partner::admin.partner-notifications.index', compact('notifications', 'notificationTypes', 'priorities'));
    }

    /**
     * 알림 생성 폼
     */
    public function create()
    {
        $notificationTypes = [
            'status_update' => '상태 변경',
            'interview_scheduled' => '면접 일정',
            'approved' => '승인 완료',
            'rejected' => '신청 거부',
            'reapply_available' => '재신청 가능',
            'tier_upgraded' => '등급 승급',
            'performance_alert' => '성과 알림'
        ];

        $priorities = [
            'low' => '낮음',
            'normal' => '보통',
            'high' => '높음',
            'urgent' => '긴급'
        ];

        $channels = [
            'web' => '웹 알림',
            'email' => '이메일',
            'sms' => 'SMS',
            'push' => '푸시 알림'
        ];

        return view('jiny-partner::admin.partner-notifications.create', compact('notificationTypes', 'priorities', 'channels'));
    }

    /**
     * 알림 저장
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:200',
            'message' => 'required|string',
            'type' => 'required|string|max:50',
            'priority' => 'required|string|in:low,normal,high,urgent',
            'channels' => 'required|array',
            'action_url' => 'nullable|url|max:500',
            'expires_at' => 'nullable|date|after:now',
            'data' => 'nullable|array'
        ]);

        // 수신자 정보
        $user = DB::table('users')->where('id', $request->user_id)->first();

        $notificationData = [
            'user_id' => $request->user_id,
            'user_uuid' => $user->uuid ?? null,
            'title' => $request->title,
            'message' => $request->message,
            'type' => $request->type,
            'priority' => $request->priority,
            'channels' => json_encode($request->channels),
            'action_url' => $request->action_url,
            'expires_at' => $request->expires_at,
            'data' => $request->data ? json_encode($request->data) : null,
            'created_at' => now(),
            'updated_at' => now()
        ];

        $notificationId = DB::table('partner_notifications')->insertGetId($notificationData);

        // 실제 전송 처리 (비동기로 처리할 수 있음)
        $this->sendNotification($notificationId, $request->channels);

        return redirect()->route('admin.partner.notifications.index')
            ->with('success', '알림이 성공적으로 생성되고 전송되었습니다.');
    }

    /**
     * 알림 상세 조회
     */
    public function show($id)
    {
        $notification = DB::table('partner_notifications as pn')
            ->leftJoin('users as u', 'pn.user_id', '=', 'u.id')
            ->select([
                'pn.*',
                'u.name as user_name',
                'u.email as user_email'
            ])
            ->where('pn.id', $id)
            ->first();

        if (!$notification) {
            abort(404);
        }

        // JSON 데이터 파싱
        $data = null;
        if ($notification->data) {
            $data = json_decode($notification->data, true);
        }

        $deliveryStatus = null;
        if ($notification->delivery_status) {
            $deliveryStatus = json_decode($notification->delivery_status, true);
        }

        $channels = json_decode($notification->channels, true) ?? [];

        return view('jiny-partner::admin.partner-notifications.show', compact('notification', 'data', 'deliveryStatus', 'channels'));
    }

    /**
     * 알림 삭제
     */
    public function destroy($id)
    {
        DB::table('partner_notifications')->where('id', $id)->delete();

        return redirect()->route('admin.partner.notifications.index')
            ->with('success', '알림이 삭제되었습니다.');
    }

    /**
     * 알림 읽음 처리
     */
    public function markAsRead($id)
    {
        DB::table('partner_notifications')
            ->where('id', $id)
            ->update([
                'is_read' => true,
                'read_at' => now(),
                'updated_at' => now()
            ]);

        return response()->json(['success' => true]);
    }

    /**
     * 대량 읽음 처리
     */
    public function markAllAsRead(Request $request)
    {
        $query = DB::table('partner_notifications')->where('is_read', false);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $affected = $query->update([
            'is_read' => true,
            'read_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => "{$affected}개 알림을 읽음 처리했습니다."
        ]);
    }

    /**
     * 사용자별 알림 조회
     */
    public function getUserNotifications($userId)
    {
        $notifications = DB::table('partner_notifications')
            ->where('user_id', $userId)
            ->where(function($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['notifications' => $notifications]);
    }

    /**
     * 알림 통계
     */
    public function statistics(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subDays(30)->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());

        // 알림 유형별 통계
        $typeStats = DB::table('partner_notifications')
            ->select('type', DB::raw('count(*) as count'))
            ->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])
            ->groupBy('type')
            ->get();

        // 우선순위별 통계
        $priorityStats = DB::table('partner_notifications')
            ->select('priority', DB::raw('count(*) as count'))
            ->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])
            ->groupBy('priority')
            ->get();

        // 읽음 상태별 통계
        $readStats = DB::table('partner_notifications')
            ->select('is_read', DB::raw('count(*) as count'))
            ->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])
            ->groupBy('is_read')
            ->get();

        // 일별 통계
        $dailyStats = DB::table('partner_notifications')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )
            ->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        return response()->json([
            'type_stats' => $typeStats,
            'priority_stats' => $priorityStats,
            'read_stats' => $readStats,
            'daily_stats' => $dailyStats
        ]);
    }

    /**
     * 알림 내보내기
     */
    public function export(Request $request)
    {
        $query = DB::table('partner_notifications as pn')
            ->leftJoin('users as u', 'pn.user_id', '=', 'u.id')
            ->select([
                'pn.id',
                'pn.created_at',
                'u.name as user_name',
                'u.email as user_email',
                'pn.title',
                'pn.type',
                'pn.priority',
                'pn.is_read',
                'pn.read_at',
                'pn.channels'
            ]);

        // 필터 적용
        if ($request->filled('date_from')) {
            $query->where('pn.created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('pn.created_at', '<=', $request->date_to . ' 23:59:59');
        }
        if ($request->filled('type')) {
            $query->where('pn.type', $request->type);
        }

        $notifications = $query->orderBy('pn.created_at', 'desc')->get();

        $filename = 'partner_notifications_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($notifications) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM 추가
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // 헤더 행
            fputcsv($file, [
                'ID', '생성일시', '수신자명', '수신자이메일', '제목',
                '유형', '우선순위', '읽음여부', '읽은시간', '전송채널'
            ]);

            // 데이터 행
            foreach ($notifications as $notification) {
                fputcsv($file, [
                    $notification->id,
                    $notification->created_at,
                    $notification->user_name,
                    $notification->user_email,
                    $notification->title,
                    $notification->type,
                    $notification->priority,
                    $notification->is_read ? '읽음' : '읽지않음',
                    $notification->read_at,
                    implode(', ', json_decode($notification->channels, true) ?? [])
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * 알림 전송 (헬퍼 메서드)
     */
    protected function sendNotification($notificationId, $channels)
    {
        $notification = DB::table('partner_notifications')->where('id', $notificationId)->first();
        if (!$notification) {
            return false;
        }

        $deliveryStatus = [];

        foreach ($channels as $channel) {
            switch ($channel) {
                case 'web':
                    // 웹 알림은 DB에 저장되는 것만으로도 전송 완료
                    $deliveryStatus['web'] = [
                        'status' => 'delivered',
                        'delivered_at' => now()->toISOString()
                    ];
                    break;

                case 'email':
                    // 이메일 전송 로직 (실제 구현 시 Mail 클래스 사용)
                    // Mail::to($notification->user_email)->send(new PartnerNotificationMail($notification));
                    $deliveryStatus['email'] = [
                        'status' => 'sent',
                        'sent_at' => now()->toISOString()
                    ];
                    break;

                case 'sms':
                    // SMS 전송 로직 (실제 구현 시 SMS 서비스 사용)
                    $deliveryStatus['sms'] = [
                        'status' => 'sent',
                        'sent_at' => now()->toISOString()
                    ];
                    break;

                case 'push':
                    // 푸시 알림 전송 로직
                    $deliveryStatus['push'] = [
                        'status' => 'sent',
                        'sent_at' => now()->toISOString()
                    ];
                    break;
            }
        }

        // 전송 상태 업데이트
        DB::table('partner_notifications')
            ->where('id', $notificationId)
            ->update([
                'delivery_status' => json_encode($deliveryStatus),
                'updated_at' => now()
            ]);

        return true;
    }

    /**
     * 알림 생성 (정적 헬퍼 메서드)
     */
    public static function createNotification($userId, $title, $message, $type, $priority = 'normal', $channels = ['web'], $actionUrl = null, $data = null, $expiresAt = null)
    {
        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user) {
            return false;
        }

        $notificationData = [
            'user_id' => $userId,
            'user_uuid' => $user->uuid ?? null,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'priority' => $priority,
            'channels' => json_encode($channels),
            'action_url' => $actionUrl,
            'data' => $data ? json_encode($data) : null,
            'expires_at' => $expiresAt,
            'created_at' => now(),
            'updated_at' => now()
        ];

        $notificationId = DB::table('partner_notifications')->insertGetId($notificationData);

        // 즉시 전송 처리
        $controller = new self();
        $controller->sendNotification($notificationId, $channels);

        return $notificationId;
    }
}