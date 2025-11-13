<?php

namespace Jiny\Partner\Http\Controllers\Home\Network;

use Jiny\Auth\Http\Controllers\HomeController;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IndexController extends HomeController
{
    /**
     * 파트너 네트워크 트리 구조 페이지
     * 나의 파트너 코드로 등록된 하위 파트너를 계층적으로 표시
     */
    public function __invoke(Request $request)
    {
        // Step1. JWT 인증 확인 (HomeController의 auth 메서드 사용)
        $user = $this->auth($request);
        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'JWT 인증이 필요합니다. 로그인해 주세요.')
                ->with('info', '파트너 서비스는 로그인 후 이용하실 수 있습니다.');
        }

        Log::info('Partner network access', [
            'user_id' => $user->id,
            'user_uuid' => $user->uuid,
            'user_email' => $user->email
        ]);

        // Step2. 나의 파트너 정보 확인
        $myPartner = PartnerUser::with(['partnerTier', 'partnerType'])
            ->where('user_uuid', $user->uuid)
            ->first();

        // 파트너 미등록시 intro로 리다이렉션
        if (!$myPartner) {
            return redirect()->route('home.partner.intro')
                ->with('info', '파트너 프로그램에 가입하시면 네트워크를 확인하실 수 있습니다.')
                ->with('userInfo', [
                    'name' => $user->name ?? '',
                    'email' => $user->email ?? '',
                    'phone' => $user->profile->phone ?? '',
                    'uuid' => $user->uuid
                ]);
        }

        // Step3. 네트워크 트리 데이터 구성
        $networkTree = $this->buildNetworkTree($myPartner);

        // Step4. 네트워크 통계 정보
        $networkStats = $this->getNetworkStats($myPartner);

        return view('jiny-partner::home.network.index', [
            'user' => $user,
            'myPartner' => $myPartner,
            'networkTree' => $networkTree,
            'networkStats' => $networkStats,
            'pageTitle' => '파트너 네트워크'
        ]);
    }

    /**
     * AJAX로 트리 데이터 반환 (펼침/접힘 기능용)
     */
    public function tree(Request $request)
    {
        $user = $this->auth($request);
        if (!$user) {
            return response()->json(['error' => '인증이 필요합니다.'], 401);
        }

        $myPartner = PartnerUser::where('user_uuid', $user->uuid)->first();
        if (!$myPartner) {
            return response()->json(['error' => '파트너 정보를 찾을 수 없습니다.'], 404);
        }

        $parentId = $request->get('parent_id', $myPartner->id);
        $children = $this->getDirectChildren($parentId);

        return response()->json([
            'success' => true,
            'children' => $children
        ]);
    }

    /**
     * 네트워크 트리 구조 구성 (재귀적)
     */
    private function buildNetworkTree(PartnerUser $myPartner, int $maxDepth = 5)
    {
        return $this->buildTreeNode($myPartner, 0, $maxDepth);
    }

    /**
     * 트리 노드 구성 (재귀 함수)
     */
    private function buildTreeNode(PartnerUser $partner, int $currentDepth, int $maxDepth, array $visited = [])
    {
        // 순환 참조 방지: 이미 방문한 노드인지 확인
        if (in_array($partner->id, $visited)) {
            \Log::warning('Circular reference detected', [
                'partner_id' => $partner->id,
                'partner_name' => $partner->name,
                'visited_path' => $visited
            ]);
            return null; // 순환 참조 발견시 null 반환
        }

        // 현재 노드를 방문 목록에 추가
        $visited[] = $partner->id;

        // 하위 파트너 수를 실제 데이터베이스에서 계산
        $actualChildrenCount = PartnerUser::where('parent_id', $partner->id)
            ->where('status', 'active')
            ->count();

        $node = [
            'id' => $partner->id,
            'name' => $partner->name,
            'email' => $partner->email,
            'partner_code' => $partner->partner_code,
            'tier_name' => $partner->partnerTier->tier_name ?? 'Bronze',
            'type_name' => $partner->partnerType->type_name ?? 'General',
            'status' => $partner->status,
            'level' => $currentDepth + 1,
            'joined_at' => $partner->partner_joined_at,
            'total_sales' => $partner->total_sales ?? 0,
            'monthly_sales' => $partner->monthly_sales ?? 0,
            'earned_commissions' => $partner->earned_commissions ?? 0,
            'children_count' => $actualChildrenCount,
            'last_activity_at' => $partner->last_activity_at,
            'children' => []
        ];

        // 최대 깊이 제한 및 하위 파트너가 있는 경우만 재귀 호출
        if ($currentDepth < $maxDepth && $actualChildrenCount > 0) {
            $directChildren = PartnerUser::with(['partnerTier', 'partnerType'])
                ->where('parent_id', $partner->id)
                ->where('status', 'active')
                ->orderBy('partner_joined_at', 'desc')
                ->get();

            foreach ($directChildren as $child) {
                $childNode = $this->buildTreeNode($child, $currentDepth + 1, $maxDepth, $visited);
                if ($childNode !== null) { // null이 아닌 경우만 추가 (순환 참조 제외)
                    $node['children'][] = $childNode;
                }
            }
        }

        return $node;
    }

    /**
     * 직접 하위 파트너들 조회
     */
    private function getDirectChildren(int $parentId)
    {
        // parent_id 기반으로 직접 하위 파트너 검색
        $directChildren = PartnerUser::with(['partnerTier', 'partnerType'])
            ->where('parent_id', $parentId)
            ->where('status', 'active')
            ->orderBy('partner_joined_at', 'desc')
            ->get();

        return $directChildren->map(function($child) {
            // 하위 파트너 수를 실제로 계산
            $childrenCount = PartnerUser::where('parent_id', $child->id)
                ->where('status', 'active')
                ->count();

            return [
                'id' => $child->id,
                'name' => $child->name,
                'email' => $child->email,
                'partner_code' => $child->partner_code ?? '',
                'tier_name' => $child->partnerTier->tier_name ?? 'Bronze',
                'type_name' => $child->partnerType->type_name ?? 'General',
                'status' => $child->status,
                'joined_at' => $child->partner_joined_at,
                'total_sales' => $child->total_sales ?? 0,
                'monthly_sales' => $child->monthly_sales ?? 0,
                'earned_commissions' => $child->earned_commissions ?? 0,
                'children_count' => $childrenCount,
                'has_children' => $childrenCount > 0,
                'last_activity_at' => $child->last_activity_at
            ];
        })->values();
    }

    /**
     * 네트워크 통계 정보
     */
    private function getNetworkStats(PartnerUser $myPartner)
    {
        // 직접 하위 파트너 수
        $directPartners = PartnerUser::where('parent_id', $myPartner->id)
            ->where('status', 'active')
            ->count();

        // 전체 하위 파트너 수 (모든 레벨)
        $totalPartners = $this->countAllDescendants($myPartner);

        // 네트워크 전체 매출 합계
        $totalNetworkSales = $this->calculateTotalNetworkSales($myPartner);

        // 네트워크 전체 커미션 합계
        $totalNetworkCommissions = $this->calculateTotalNetworkCommissions($myPartner);

        // 최대 깊이
        $maxDepth = $this->calculateMaxDepth($myPartner);

        // 월별 네트워크 매출
        $monthlyNetworkSales = $this->calculateMonthlyNetworkSales($myPartner);

        return [
            'total_partners' => $totalPartners,
            'direct_partners' => $directPartners,
            'total_network_sales' => $totalNetworkSales,
            'total_network_commissions' => $totalNetworkCommissions,
            'max_depth' => $maxDepth,
            'active_partners' => $totalPartners, // 이미 active 상태만 계산됨
            'monthly_network_sales' => $monthlyNetworkSales
        ];
    }

    /**
     * 모든 하위 파트너 수 계산 (재귀적)
     */
    private function countAllDescendants(PartnerUser $partner, int $depth = 0, int $maxDepth = 10, array $visited = [])
    {
        if ($depth >= $maxDepth) return 0;

        // 순환 참조 방지
        if (in_array($partner->id, $visited)) {
            \Log::warning('Circular reference detected in countAllDescendants', [
                'partner_id' => $partner->id,
                'visited_path' => $visited
            ]);
            return 0;
        }

        $visited[] = $partner->id;

        // 직접 하위 파트너들 조회
        $directChildren = PartnerUser::where('parent_id', $partner->id)
            ->where('status', 'active')
            ->get();

        $count = $directChildren->count();

        // 각 하위 파트너의 하위 파트너들도 재귀적으로 계산
        foreach ($directChildren as $child) {
            $count += $this->countAllDescendants($child, $depth + 1, $maxDepth, $visited);
        }

        return $count;
    }

    /**
     * 활성 하위 파트너 수 계산
     */
    private function countActiveDescendants(PartnerUser $partner)
    {
        return $this->countAllDescendants($partner); // 이미 active 조건으로 필터링됨
    }

    /**
     * 네트워크 전체 매출 계산
     */
    private function calculateTotalNetworkSales(PartnerUser $partner, int $depth = 0, int $maxDepth = 10, array $visited = [])
    {
        if ($depth >= $maxDepth) return 0;

        // 순환 참조 방지
        if (in_array($partner->id, $visited)) {
            return 0;
        }

        $visited[] = $partner->id;
        $total = 0;

        // 직접 하위 파트너들의 매출 합계
        $directChildren = PartnerUser::where('parent_id', $partner->id)
            ->where('status', 'active')
            ->get();

        foreach ($directChildren as $child) {
            $total += $child->total_sales ?? 0;
            // 재귀적으로 하위 파트너들의 매출도 계산
            $total += $this->calculateTotalNetworkSales($child, $depth + 1, $maxDepth, $visited);
        }

        return $total;
    }

    /**
     * 네트워크 전체 커미션 계산
     */
    private function calculateTotalNetworkCommissions(PartnerUser $partner, int $depth = 0, int $maxDepth = 10, array $visited = [])
    {
        if ($depth >= $maxDepth) return 0;

        // 순환 참조 방지
        if (in_array($partner->id, $visited)) {
            return 0;
        }

        $visited[] = $partner->id;
        $total = 0;

        // 직접 하위 파트너들의 커미션 합계
        $directChildren = PartnerUser::where('parent_id', $partner->id)
            ->where('status', 'active')
            ->get();

        foreach ($directChildren as $child) {
            $total += $child->earned_commissions ?? 0;
            // 재귀적으로 하위 파트너들의 커미션도 계산
            $total += $this->calculateTotalNetworkCommissions($child, $depth + 1, $maxDepth, $visited);
        }

        return $total;
    }

    /**
     * 월 네트워크 매출 계산
     */
    private function calculateMonthlyNetworkSales(PartnerUser $partner, int $depth = 0, int $maxDepth = 10, array $visited = [])
    {
        if ($depth >= $maxDepth) return 0;

        // 순환 참조 방지
        if (in_array($partner->id, $visited)) {
            return 0;
        }

        $visited[] = $partner->id;
        $total = 0;

        // 직접 하위 파트너들의 월매출 합계
        $directChildren = PartnerUser::where('parent_id', $partner->id)
            ->where('status', 'active')
            ->get();

        foreach ($directChildren as $child) {
            $total += $child->monthly_sales ?? 0;
            // 재귀적으로 하위 파트너들의 월매출도 계산
            $total += $this->calculateMonthlyNetworkSales($child, $depth + 1, $maxDepth, $visited);
        }

        return $total;
    }

    /**
     * 네트워크 최대 깊이 계산
     */
    private function calculateMaxDepth(PartnerUser $partner, int $currentDepth = 0, int $maxDepth = 10, array $visited = [])
    {
        if ($currentDepth >= $maxDepth) return $currentDepth;

        // 순환 참조 방지
        if (in_array($partner->id, $visited)) {
            return $currentDepth;
        }

        $visited[] = $partner->id;

        // 직접 하위 파트너들 조회
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