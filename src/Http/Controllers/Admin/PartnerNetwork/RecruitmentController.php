<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerNetwork;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Models\PartnerTier;
use Jiny\Partner\Models\PartnerNetworkRelationship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecruitmentController extends Controller
{
    /**
     * 파트너 모집 관리 대시보드
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'all');
        $recruiterId = $request->get('recruiter_id');
        $perPage = $request->get('per_page', 20);

        // 모집 관계 조회
        $query = PartnerNetworkRelationship::with(['parent', 'child', 'recruiter'])
            ->orderBy('recruited_at', 'desc');

        // 상태 필터
        if ($status !== 'all') {
            switch ($status) {
                case 'active':
                    $query->active();
                    break;
                case 'inactive':
                    $query->inactive();
                    break;
                case 'recent':
                    $query->whereDate('recruited_at', '>=', now()->subDays(30));
                    break;
            }
        }

        // 모집자 필터
        if ($recruiterId) {
            $query->recruitedBy($recruiterId);
        }

        $relationships = $query->paginate($perPage);

        // 통계 데이터
        $statistics = $this->getRecruitmentStatistics();

        // 최고 모집자들
        $topRecruiters = $this->getTopRecruiters();

        return view('jiny-partner::admin.partner-network.recruitment', [
            'relationships' => $relationships,
            'statistics' => $statistics,
            'topRecruiters' => $topRecruiters,
            'availableRecruiters' => PartnerUser::query()->canRecruit()->get(),
            'currentFilters' => [
                'status' => $status,
                'recruiter_id' => $recruiterId,
                'per_page' => $perPage
            ],
            'pageTitle' => '파트너 모집 관리'
        ]);
    }

    /**
     * 파트너 모집 처리
     */
    public function recruit(Request $request)
    {
        $request->validate([
            'parent_id' => 'required|exists:partner_users,id',
            'child_id' => 'required|exists:partner_users,id',
            'recruiter_id' => 'nullable|exists:partner_users,id',
            'recruitment_notes' => 'nullable|string|max:1000'
        ]);

        try {
            DB::transaction(function () use ($request) {
                $parent = PartnerUser::findOrFail($request->parent_id);
                $child = PartnerUser::findOrFail($request->child_id);
                $recruiterId = $request->recruiter_id ?? $parent->id;

                // 모집 가능 여부 검증
                $this->validateRecruitment($parent, $child);

                // 모집 처리
                $parent->addChild($child, $recruiterId);

                // 모집 성공 이벤트 처리
                $this->handleRecruitmentSuccess($parent, $child, $recruiterId, $request->recruitment_notes);
            });

            return response()->json([
                'success' => true,
                'message' => '파트너 모집이 성공적으로 완료되었습니다.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '모집 처리 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * 파트너 관계 해제
     */
    public function removeRelationship(Request $request, $relationshipId)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        try {
            DB::transaction(function () use ($relationshipId, $request) {
                $relationship = PartnerNetworkRelationship::findOrFail($relationshipId);

                $parent = $relationship->parent;
                $child = $relationship->child;

                // 관계 해제 처리
                $parent->removeChild($child);

                // 해제 사유 기록
                $relationship->update([
                    'deactivation_reason' => $request->reason
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => '파트너 관계가 성공적으로 해제되었습니다.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '관계 해제 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * 대량 모집 처리
     */
    public function bulkRecruit(Request $request)
    {
        $request->validate([
            'parent_id' => 'required|exists:partner_users,id',
            'child_ids' => 'required|array',
            'child_ids.*' => 'exists:partner_users,id',
            'recruiter_id' => 'nullable|exists:partner_users,id'
        ]);

        $parent = PartnerUser::findOrFail($request->parent_id);
        $recruiterId = $request->recruiter_id ?? $parent->id;
        $successful = 0;
        $failed = 0;
        $errors = [];

        foreach ($request->child_ids as $childId) {
            try {
                DB::transaction(function () use ($parent, $childId, $recruiterId) {
                    $child = PartnerUser::findOrFail($childId);
                    $this->validateRecruitment($parent, $child);
                    $parent->addChild($child, $recruiterId);
                });
                $successful++;
            } catch (\Exception $e) {
                $failed++;
                $errors[] = "파트너 ID {$childId}: " . $e->getMessage();
            }
        }

        return response()->json([
            'success' => $successful > 0,
            'message' => "대량 모집 완료: 성공 {$successful}건, 실패 {$failed}건",
            'successful' => $successful,
            'failed' => $failed,
            'errors' => $errors
        ]);
    }

    /**
     * 모집 가능 여부 검증
     */
    private function validateRecruitment(PartnerUser $parent, PartnerUser $child)
    {
        // 모집 권한 확인
        if (!$parent->canRecruit()) {
            throw new \Exception('모집 권한이 없습니다.');
        }

        // 최대 하위 파트너 수 확인
        if ($parent->hasReachedMaxChildren()) {
            throw new \Exception('관리 가능한 최대 하위 파트너 수에 도달했습니다.');
        }

        // 이미 다른 상위 파트너가 있는지 확인
        if ($child->parent_id) {
            throw new \Exception('이미 다른 상위 파트너에게 소속되어 있습니다.');
        }

        // 자기 자신을 모집하는지 확인
        if ($parent->id === $child->id) {
            throw new \Exception('자기 자신을 모집할 수 없습니다.');
        }

        // 순환 관계 확인 (자식이 부모의 상위 파트너인지)
        if ($this->wouldCreateCircularRelationship($parent, $child)) {
            throw new \Exception('순환 관계가 생성됩니다.');
        }

        // 하위 파트너 상태 확인
        if ($child->status !== 'active') {
            throw new \Exception('활성 상태의 파트너만 모집 가능합니다.');
        }
    }

    /**
     * 순환 관계 생성 여부 확인
     */
    private function wouldCreateCircularRelationship(PartnerUser $parent, PartnerUser $child)
    {
        $currentParent = $parent->parent;
        while ($currentParent) {
            if ($currentParent->id === $child->id) {
                return true;
            }
            $currentParent = $currentParent->parent;
        }
        return false;
    }

    /**
     * 모집 성공 처리
     */
    private function handleRecruitmentSuccess(PartnerUser $parent, PartnerUser $child, $recruiterId, $notes = null)
    {
        // 모집 보너스 지급 (있는 경우)
        $recruiterSettings = $parent->partnerTier->getRecruitmentSettings();

        if ($recruiterSettings['recruitment_bonus'] > 0) {
            \Jiny\Partner\Models\PartnerCommission::create([
                'partner_id' => $recruiterId,
                'source_partner_id' => $child->id,
                'commission_type' => 'recruitment_bonus',
                'level_difference' => 0,
                'original_amount' => $recruiterSettings['recruitment_bonus'],
                'commission_rate' => 100,
                'commission_amount' => $recruiterSettings['recruitment_bonus'],
                'net_amount' => $recruiterSettings['recruitment_bonus'] * 0.9,
                'tree_path_at_time' => $child->tree_path,
                'earned_at' => now(),
                'status' => 'calculated',
                'notes' => '모집 보너스'
            ]);
        }

        // 활동 기록 업데이트
        $parent->update(['last_activity_at' => now()]);

        // 모집 상세 정보 업데이트
        if ($notes) {
            $relationship = PartnerNetworkRelationship::where('parent_id', $parent->id)
                ->where('child_id', $child->id)
                ->first();

            if ($relationship) {
                $details = $relationship->recruitment_details ?? [];
                $details['notes'] = $notes;
                $relationship->update(['recruitment_details' => $details]);
            }
        }
    }

    /**
     * 모집 통계
     */
    private function getRecruitmentStatistics()
    {
        $total = PartnerNetworkRelationship::count();
        $active = PartnerNetworkRelationship::active()->count();
        $thisMonth = PartnerNetworkRelationship::whereMonth('recruited_at', now()->month)->count();
        $thisWeek = PartnerNetworkRelationship::whereBetween('recruited_at', [now()->startOfWeek(), now()->endOfWeek()])->count();

        return [
            'total_relationships' => $total,
            'active_relationships' => $active,
            'inactive_relationships' => $total - $active,
            'this_month_recruits' => $thisMonth,
            'this_week_recruits' => $thisWeek,
            'recruitment_rate' => $total > 0 ? round(($active / $total) * 100, 1) : 0,
            'average_daily_recruits' => round($thisMonth / now()->day, 1)
        ];
    }

    /**
     * 최고 모집자들
     */
    private function getTopRecruiters($limit = 10)
    {
        return PartnerUser::withCount(['networkRelationships as total_recruits' => function($query) {
                $query->where('recruiter_id', '=', DB::raw('partner_users.id'));
            }])
            ->withCount(['networkRelationships as active_recruits' => function($query) {
                $query->where('recruiter_id', '=', DB::raw('partner_users.id'))
                      ->where('is_active', true);
            }])
            ->get()
            ->filter(function($partner) {
                return $partner->total_recruits > 0;
            })
            ->sortByDesc('total_recruits')
            ->take($limit)
            ->map(function($partner) {
                $partner->recruitment_rate = $partner->total_recruits > 0
                    ? round(($partner->active_recruits / $partner->total_recruits) * 100, 1)
                    : 0;
                return $partner;
            })
            ->values();
    }
}