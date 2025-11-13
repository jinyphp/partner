<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerApplication;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    /**
     * 파트너 지원서 목록
     */
    public function __invoke(Request $request)
    {
        $query = PartnerApplication::with(['user', 'approver', 'rejector', 'referrerPartner']);

        // 검색 기능
        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('admin_notes', 'like', '%' . $request->search . '%')
                  ->orWhereHas('user', function($userQuery) use ($request) {
                      $userQuery->where('name', 'like', '%' . $request->search . '%')
                               ->orWhere('email', 'like', '%' . $request->search . '%');
                  });
            });
        }

        // 상태 필터
        if ($request->has('status') && $request->status) {
            $query->where('application_status', $request->status);
        }

        // 면접 일정 필터
        if ($request->has('interview_filter') && $request->interview_filter) {
            switch ($request->interview_filter) {
                case 'scheduled':
                    $query->where('application_status', 'interview')
                          ->whereNotNull('interview_date');
                    break;
                case 'pending_schedule':
                    $query->where('application_status', 'interview')
                          ->whereNull('interview_date');
                    break;
                case 'today':
                    $query->whereDate('interview_date', today());
                    break;
                case 'this_week':
                    $query->whereBetween('interview_date', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
            }
        }

        // 날짜 필터
        if ($request->has('date_filter') && $request->date_filter) {
            switch ($request->date_filter) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'this_week':
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'this_month':
                    $query->whereMonth('created_at', now()->month);
                    break;
            }
        }

        $items = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('jiny-partner::admin.partner-applications.index', [
            'items' => $items,
            'title' => '파트너 지원서',
            'routePrefix' => 'applications',
            'searchValue' => $request->search,
            'selectedStatus' => $request->status,
            'selectedInterviewFilter' => $request->interview_filter,
            'selectedDateFilter' => $request->date_filter
        ]);
    }
}