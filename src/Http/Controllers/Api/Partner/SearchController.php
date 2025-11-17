<?php

namespace Jiny\Partner\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Jiny\Partner\Models\PartnerUser;

class SearchController extends Controller
{
    /**
     * 파트너 검색 API (재사용 가능)
     *
     * 이 컨트롤러는 여러 곳에서 파트너를 검색할 때 사용할 수 있는 공통 API입니다.
     * - 매출 등록 시 파트너 선택
     * - 파트너 관리에서 검색
     * - 기타 파트너 선택이 필요한 모든 곳
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:2|max:255',
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
            'status' => 'string|in:active,inactive,pending,all',
            'include_inactive' => 'boolean'
        ]);

        $searchTerm = trim($request->input('search'));
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);
        $status = $request->input('status', 'active');
        $includeInactive = $request->input('include_inactive', false);

        Log::info('API Partner search request', [
            'search_term' => $searchTerm,
            'page' => $page,
            'per_page' => $perPage,
            'status' => $status,
            'include_inactive' => $includeInactive,
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip()
        ]);

        try {
            // 기본 쿼리 시작
            $partnersQuery = PartnerUser::with(['partnerType', 'partnerTier'])
                ->where(function($query) use ($searchTerm) {
                    $query->where('name', 'like', '%' . $searchTerm . '%')
                          ->orWhere('email', 'like', '%' . $searchTerm . '%');
                });

            // 상태 필터링
            if ($status !== 'all') {
                if ($includeInactive && $status === 'active') {
                    // 활성과 비활성 모두 포함
                    $partnersQuery->whereIn('status', ['active', 'inactive']);
                } else {
                    // 지정된 상태만
                    $partnersQuery->where('status', $status);
                }
            }

            // 정렬: 이름 순, 이메일 순
            $partnersQuery->orderBy('name', 'asc')
                         ->orderBy('email', 'asc');

            // 페이지네이션 적용
            $partners = $partnersQuery->paginate($perPage, ['*'], 'page', $page);

            // 검색 결과 데이터 가공
            $searchResults = $partners->through(function ($partner) {
                return [
                    'id' => $partner->id,
                    'uuid' => $partner->user_uuid,
                    'name' => $partner->name,
                    'email' => $partner->email,
                    'tier_name' => $partner->partnerTier->tier_name ?? 'Bronze',
                    'type_name' => $partner->partnerType->type_name ?? 'Basic',
                    'total_commission_rate' => $partner->getTotalCommissionRate(),
                    'individual_commission_rate' => $partner->individual_commission_rate ?? 0,
                    'status' => $partner->status,
                    'joined_at' => $partner->joined_at?->format('Y-m-d'),
                    'created_at' => $partner->created_at->format('Y-m-d'),
                    'display_name' => $partner->name . ' (' . $partner->email . ')',
                    // 추가 정보
                    'tier_color' => $this->getTierColor($partner->partnerTier->tier_name ?? 'Bronze'),
                    'type_color' => $this->getTypeColor($partner->partnerType->type_name ?? 'Basic'),
                    'is_active' => $partner->status === 'active'
                ];
            });

            // 검색 통계
            $stats = [
                'total_found' => $partners->total(),
                'active_count' => PartnerUser::where('status', 'active')
                    ->where(function($query) use ($searchTerm) {
                        $query->where('name', 'like', '%' . $searchTerm . '%')
                              ->orWhere('email', 'like', '%' . $searchTerm . '%');
                    })->count(),
                'inactive_count' => PartnerUser::where('status', 'inactive')
                    ->where(function($query) use ($searchTerm) {
                        $query->where('name', 'like', '%' . $searchTerm . '%')
                              ->orWhere('email', 'like', '%' . $searchTerm . '%');
                    })->count()
            ];

            Log::info('API Partner search completed', [
                'search_term' => $searchTerm,
                'total_found' => $partners->total(),
                'current_page' => $partners->currentPage(),
                'per_page' => $partners->perPage(),
                'stats' => $stats
            ]);

            return response()->json([
                'success' => true,
                'message' => $partners->total() > 0 ? '파트너를 찾았습니다.' : '검색 결과가 없습니다.',
                'data' => $searchResults->items(),
                'pagination' => [
                    'current_page' => $partners->currentPage(),
                    'last_page' => $partners->lastPage(),
                    'per_page' => $partners->perPage(),
                    'total' => $partners->total(),
                    'from' => $partners->firstItem(),
                    'to' => $partners->lastItem(),
                    'has_more_pages' => $partners->hasMorePages()
                ],
                'search_stats' => $stats,
                'filters' => [
                    'search_term' => $searchTerm,
                    'status' => $status,
                    'include_inactive' => $includeInactive
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('API Partner search failed', [
                'search_term' => $searchTerm,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '검색 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * 파트너 등급별 색상 반환
     */
    private function getTierColor($tierName)
    {
        return match(strtolower($tierName)) {
            'diamond' => 'primary',
            'gold' => 'warning',
            'silver' => 'info',
            'bronze' => 'secondary',
            default => 'secondary'
        };
    }

    /**
     * 파트너 타입별 색상 반환
     */
    private function getTypeColor($typeName)
    {
        return match(strtolower($typeName)) {
            'premium' => 'success',
            'standard' => 'info',
            'basic' => 'secondary',
            default => 'secondary'
        };
    }
}