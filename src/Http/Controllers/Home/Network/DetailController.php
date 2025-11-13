<?php

namespace Jiny\Partner\Http\Controllers\Home\Network;

use Jiny\Auth\Http\Controllers\HomeController;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Models\PartnerApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DetailController extends HomeController
{
    /**
     * 파트너 상세 정보 페이지
     */
    public function __invoke(Request $request, $id)
    {
        // Step1. JWT 인증 확인 (HomeController의 auth 메서드 사용)
        $user = $this->auth($request);
        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'JWT 인증이 필요합니다. 로그인해 주세요.')
                ->with('info', '파트너 서비스는 로그인 후 이용하실 수 있습니다.');
        }

        Log::info('Partner detail access', [
            'user_id' => $user->id,
            'user_uuid' => $user->uuid,
            'target_partner_id' => $id
        ]);

        // Step2. 나의 파트너 정보 확인
        $myPartner = PartnerUser::with(['partnerTier', 'partnerType'])
            ->where('user_uuid', $user->uuid)
            ->first();

        // 파트너 미등록시 intro로 리다이렉션
        if (!$myPartner) {
            return redirect()->route('home.partner.intro')
                ->with('info', '파트너 프로그램에 가입하시면 파트너 정보를 확인하실 수 있습니다.');
        }

        // Step3. 대상 파트너 정보 조회
        $targetPartner = PartnerUser::with(['partnerTier', 'partnerType'])
            ->where('id', $id)
            ->first();

        if (!$targetPartner) {
            return redirect()->route('home.partner.network.index')
                ->with('error', '요청한 파트너 정보를 찾을 수 없습니다.');
        }

        // Step4. 권한 확인 - 내 네트워크에 속한 파트너인지 검증
        if (!$this->isInMyNetwork($myPartner, $targetPartner)) {
            return redirect()->route('home.partner.network.index')
                ->with('error', '해당 파트너 정보에 접근할 권한이 없습니다.');
        }

        // Step5. 상위 파트너 정보 조회
        $parentPartner = null;
        if ($targetPartner->parent_id) {
            $parentPartner = PartnerUser::with(['partnerTier', 'partnerType'])
                ->find($targetPartner->parent_id);
        }

        // Step6. 대상 파트너의 상세 정보 구성
        $partnerDetail = $this->getPartnerDetailInfo($targetPartner);

        // Step7. 대상 파트너의 하위 네트워크 정보
        $subNetwork = $this->getSubNetworkInfo($targetPartner);

        // Step8. 활동 이력 정보
        $activityHistory = $this->getActivityHistory($targetPartner);

        return view('jiny-partner::home.network.detail', [
            'user' => $user,
            'myPartner' => $myPartner,
            'targetPartner' => $targetPartner,
            'parentPartner' => $parentPartner,
            'partnerDetail' => $partnerDetail,
            'subNetwork' => $subNetwork,
            'activityHistory' => $activityHistory,
            'pageTitle' => $targetPartner->name . ' - 파트너 상세정보'
        ]);
    }

    /**
     * 대상 파트너가 내 네트워크에 속하는지 확인
     */
    private function isInMyNetwork(PartnerUser $myPartner, PartnerUser $targetPartner): bool
    {
        // 자신인 경우 허용
        if ($myPartner->id === $targetPartner->id) {
            return true;
        }

        // 직접 하위 파트너인지 확인
        if ($targetPartner->parent_id === $myPartner->id) {
            return true;
        }

        // 재귀적으로 하위 네트워크에 속하는지 확인
        return $this->isDescendantOf($myPartner->id, $targetPartner, 0, 10);
    }

    /**
     * 재귀적으로 하위 파트너인지 확인
     */
    private function isDescendantOf(int $ancestorId, PartnerUser $partner, int $depth = 0, int $maxDepth = 10): bool
    {
        if ($depth >= $maxDepth || !$partner->parent_id) {
            return false;
        }

        if ($partner->parent_id === $ancestorId) {
            return true;
        }

        $parent = PartnerUser::find($partner->parent_id);
        if (!$parent) {
            return false;
        }

        return $this->isDescendantOf($ancestorId, $parent, $depth + 1, $maxDepth);
    }

    /**
     * 파트너 상세 정보 구성
     */
    private function getPartnerDetailInfo(PartnerUser $partner): array
    {
        // 직접 하위 파트너 수
        $directChildrenCount = PartnerUser::where('parent_id', $partner->id)
            ->where('status', 'active')
            ->count();

        // 전체 하위 파트너 수
        $totalChildrenCount = $this->countAllDescendants($partner);

        // 최근 활동일
        $lastActivity = $partner->last_activity_at
            ? $partner->last_activity_at->diffForHumans()
            : '활동 기록 없음';

        // 가입 경로 정보
        $referralInfo = null;
        if ($partner->profile_data && isset($partner->profile_data['referrer_info'])) {
            $referralInfo = $partner->profile_data['referrer_info'];
        }

        return [
            'basic_info' => [
                'id' => $partner->id,
                'name' => $partner->name,
                'email' => $partner->email,
                'partner_code' => $partner->partner_code,
                'status' => $partner->status,
                'tier_name' => $partner->partnerTier->tier_name ?? 'Bronze',
                'type_name' => $partner->partnerType->type_name ?? 'General',
                'joined_at' => $partner->partner_joined_at,
                'last_activity' => $lastActivity,
                'level' => $partner->level ?? 0
            ],
            'network_info' => [
                'direct_children' => $directChildrenCount,
                'total_children' => $totalChildrenCount,
                'max_network_depth' => $this->calculateMaxDepth($partner),
                'can_recruit' => $partner->can_recruit ?? true
            ],
            'performance_info' => [
                'total_sales' => $partner->total_sales ?? 0,
                'monthly_sales' => $partner->monthly_sales ?? 0,
                'earned_commissions' => $partner->earned_commissions ?? 0,
                'average_rating' => $partner->average_rating ?? 0,
                'total_completed_jobs' => $partner->total_completed_jobs ?? 0
            ],
            'referral_info' => $referralInfo
        ];
    }

    /**
     * 하위 네트워크 정보
     */
    private function getSubNetworkInfo(PartnerUser $partner): array
    {
        $directChildren = PartnerUser::with(['partnerTier', 'partnerType'])
            ->where('parent_id', $partner->id)
            ->where('status', 'active')
            ->orderBy('partner_joined_at', 'desc')
            ->get();

        return $directChildren->map(function($child) {
            return [
                'id' => $child->id,
                'name' => $child->name,
                'email' => $child->email,
                'partner_code' => $child->partner_code,
                'tier_name' => $child->partnerTier->tier_name ?? 'Bronze',
                'status' => $child->status,
                'joined_at' => $child->partner_joined_at,
                'total_sales' => $child->total_sales ?? 0,
                'children_count' => PartnerUser::where('parent_id', $child->id)->where('status', 'active')->count()
            ];
        })->toArray();
    }

    /**
     * 활동 이력 정보 (간단한 버전)
     */
    private function getActivityHistory(PartnerUser $partner): array
    {
        $history = [];

        // 가입 정보
        if ($partner->partner_joined_at) {
            $history[] = [
                'type' => 'join',
                'title' => '파트너 가입',
                'description' => '파트너 프로그램에 가입했습니다.',
                'date' => $partner->partner_joined_at,
                'icon' => 'bi-person-check',
                'color' => 'success'
            ];
        }

        // 등급 변경 정보
        if ($partner->tier_assigned_at) {
            $history[] = [
                'type' => 'tier_change',
                'title' => '등급 할당',
                'description' => $partner->partnerTier->tier_name ?? 'Bronze' . ' 등급이 할당되었습니다.',
                'date' => $partner->tier_assigned_at,
                'icon' => 'bi-award',
                'color' => 'primary'
            ];
        }

        // 최근 활동
        if ($partner->last_activity_at) {
            $history[] = [
                'type' => 'activity',
                'title' => '최근 활동',
                'description' => '마지막 활동 기록입니다.',
                'date' => $partner->last_activity_at,
                'icon' => 'bi-clock',
                'color' => 'info'
            ];
        }

        // 날짜순 정렬 (최신순)
        usort($history, function($a, $b) {
            return $b['date'] <=> $a['date'];
        });

        return array_slice($history, 0, 10); // 최근 10개만 반환
    }

    /**
     * 모든 하위 파트너 수 계산 (순환 참조 방지)
     */
    private function countAllDescendants(PartnerUser $partner, int $depth = 0, int $maxDepth = 10, array $visited = []): int
    {
        if ($depth >= $maxDepth || in_array($partner->id, $visited)) {
            return 0;
        }

        $visited[] = $partner->id;

        $directChildren = PartnerUser::where('parent_id', $partner->id)
            ->where('status', 'active')
            ->get();

        $count = $directChildren->count();

        foreach ($directChildren as $child) {
            $count += $this->countAllDescendants($child, $depth + 1, $maxDepth, $visited);
        }

        return $count;
    }

    /**
     * 네트워크 최대 깊이 계산 (순환 참조 방지)
     */
    private function calculateMaxDepth(PartnerUser $partner, int $currentDepth = 0, int $maxDepth = 10, array $visited = []): int
    {
        if ($currentDepth >= $maxDepth || in_array($partner->id, $visited)) {
            return $currentDepth;
        }

        $visited[] = $partner->id;

        $directChildren = PartnerUser::where('parent_id', $partner->id)
            ->where('status', 'active')
            ->get();

        if ($directChildren->isEmpty()) {
            return $currentDepth;
        }

        $maxChildDepth = $currentDepth;
        foreach ($directChildren as $child) {
            $childDepth = $this->calculateMaxDepth($child, $currentDepth + 1, $maxDepth, $visited);
            $maxChildDepth = max($maxChildDepth, $childDepth);
        }

        return $maxChildDepth;
    }
}