<?php

namespace Jiny\Partner\Http\Controllers\Home\PartnerRegist;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerApplication;
use Jiny\Partner\Models\PartnerTier;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Models\PartnerNetworkRelationship;
use Jiny\Partner\Models\PartnerSales;
use Jiny\Partner\Models\PartnerCommission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Jiny\Partner\Http\Controllers\PartnerController;
//use Jiny\Auth\Http\Controllers\HomeController;
class DashboardController extends PartnerController
{
    /**
     * 파트너 정보 관리 페이지
     * - 파트너 정보 표시
     * - 하위 파트너 트리 표시
     * - 미등록시 intro로 리다이렉션
     */
    public function __invoke(Request $request)
    {
        // Step1. JWT 인증여부 처리
        $user = $this->auth($request);
        if(!$user) {
            return redirect()->route('login')
                ->with('error', 'JWT 인증이 필요합니다. 로그인해 주세요.')
                ->with('info', '파트너 서비스는 로그인 후 이용하실 수 있습니다.');
        }

        // Step2. 파트너 등록 여부 확인 (UUID 기반)
        $partner = PartnerUser::with(['partnerType', 'partnerTier'])
            ->where('user_uuid', $user->uuid)
            ->first();

        // 파트너 미등록시 intro로 리다이렉션
        if (!$partner) {
            return redirect()->route('home.partner.intro')
                ->with('info', '파트너 프로그램에 가입하시면 파트너 관리 기능을 이용하실 수 있습니다.')
                ->with('userInfo', [
                    'name' => $user->name ?? '',
                    'email' => $user->email ?? '',
                    'phone' => $user->profile->phone ?? '',
                    'uuid' => $user->uuid
                ]);
        }

        // Step3. 파트너 정보 통계 계산
        $partnerStats = $this->calculatePartnerStats($partner);

        // Step4. 하위 파트너 트리 조회
        $subPartners = $this->getSubPartnerTree($partner);

        // Step5. 네트워크 정보 조회
        $networkInfo = $this->getNetworkInfo($partner);

        // Step6. 최근 활동 정보
        $recentActivities = $this->getRecentActivities($partner);

        return view('jiny-partner::home.partner-regist.index', [
            'user' => $user,
            'currentUser' => $user,
            'partner' => $partner,
            'partnerStats' => $partnerStats,
            'subPartners' => $subPartners,
            'networkInfo' => $networkInfo,
            'recentActivities' => $recentActivities,
            'pageTitle' => '파트너 관리',
            'userInfo' => [
                'name' => $user->name ?? '',
                'email' => $user->email ?? '',
                'phone' => $user->profile->phone ?? '',
                'uuid' => $user->uuid
            ]
        ]);
    }

    /**
     * 파트너 통계 정보 계산
     */
    private function calculatePartnerStats($partner)
    {
        $currentMonth = now()->format('Y-m');
        $currentYear = now()->format('Y');

        return [
            // 매출 통계
            'total_sales' => $partner->total_sales ?? 0,
            'monthly_sales' => $partner->monthly_sales ?? 0,
            'current_month_sales' => PartnerSales::where('partner_id', $partner->id)
                ->where('status', 'confirmed')
                ->whereRaw("strftime('%Y-%m', sales_date) = ?", [$currentMonth])
                ->sum('amount'),
            'current_year_sales' => PartnerSales::where('partner_id', $partner->id)
                ->where('status', 'confirmed')
                ->whereRaw("strftime('%Y', sales_date) = ?", [$currentYear])
                ->sum('amount'),

            // 커미션 통계
            'total_commission' => PartnerCommission::where('partner_id', $partner->id)
                ->where('status', 'paid')
                ->sum('amount'),
            'pending_commission' => PartnerCommission::where('partner_id', $partner->id)
                ->where('status', 'pending')
                ->sum('amount'),
            'this_month_commission' => PartnerCommission::where('partner_id', $partner->id)
                ->where('status', 'paid')
                ->whereRaw("strftime('%Y-%m', created_at) = ?", [$currentMonth])
                ->sum('amount'),

            // 네트워크 통계
            'direct_partners' => PartnerNetworkRelationship::where('parent_id', $partner->id)
                ->where('is_active', true)
                ->count(),
            'total_network_size' => $this->getTotalNetworkSize($partner->id),
            'active_partners' => PartnerNetworkRelationship::where('parent_id', $partner->id)
                ->where('is_active', true)
                ->whereHas('child', function($query) {
                    $query->where('status', 'active');
                })
                ->count(),
        ];
    }

    /**
     * 하위 파트너 트리 조회
     */
    private function getSubPartnerTree($partner, $level = 0, $maxLevel = 3)
    {
        if ($level >= $maxLevel) {
            return collect();
        }

        $directChildren = PartnerNetworkRelationship::where('parent_id', $partner->id)
            ->where('is_active', true)
            ->with(['child.partnerType', 'child.partnerTier'])
            ->get()
            ->map(function($relationship) use ($level, $maxLevel) {
                $childPartner = $relationship->child;
                $childPartner->level = $level + 1;
                $childPartner->relationship_data = [
                    'joined_at' => $relationship->created_at,
                    'is_active' => $relationship->is_active,
                    'depth' => $relationship->depth ?? $level + 1,
                ];

                // 재귀적으로 하위 파트너 조회
                $childPartner->sub_partners = $this->getSubPartnerTree($childPartner, $level + 1, $maxLevel);

                return $childPartner;
            });

        return $directChildren;
    }

    /**
     * 네트워크 정보 조회
     */
    private function getNetworkInfo($partner)
    {
        // 상위 파트너 조회
        $parentPartner = null;
        $parentRelationship = PartnerNetworkRelationship::where('child_id', $partner->id)
            ->where('is_active', true)
            ->with(['parent.partnerType', 'parent.partnerTier'])
            ->first();

        if ($parentRelationship) {
            $parentPartner = $parentRelationship->parent;
        }

        // 네트워크 경로 조회
        $networkPath = $this->getNetworkPath($partner->id);

        // 레벨 정보
        $level = $partner->level ?? 0;
        $depth = $this->calculateNetworkDepth($partner->id);

        return [
            'parent_partner' => $parentPartner,
            'level' => $level,
            'depth' => $depth,
            'network_path' => $networkPath,
            'total_descendants' => $this->getTotalNetworkSize($partner->id),
            'active_descendants' => $this->getActiveNetworkSize($partner->id),
        ];
    }

    /**
     * 최근 활동 정보 조회
     */
    private function getRecentActivities($partner, $limit = 10)
    {
        $activities = collect();

        // 최근 매출 기록
        $recentSales = PartnerSales::where('partner_id', $partner->id)
            ->orderBy('sales_date', 'desc')
            ->limit(5)
            ->get()
            ->map(function($sale) {
                return [
                    'type' => 'sales',
                    'title' => '매출 기록',
                    'description' => number_format($sale->amount) . '원 매출 달성',
                    'date' => $sale->sales_date,
                    'icon' => 'bi-currency-dollar',
                    'color' => 'success'
                ];
            });

        // 최근 커미션 지급
        $recentCommissions = PartnerCommission::where('partner_id', $partner->id)
            ->where('status', 'paid')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($commission) {
                return [
                    'type' => 'commission',
                    'title' => '커미션 지급',
                    'description' => number_format($commission->amount) . '원 커미션 지급',
                    'date' => $commission->created_at,
                    'icon' => 'bi-wallet2',
                    'color' => 'info'
                ];
            });

        // 최근 네트워크 추가
        $recentNetworkAdditions = PartnerNetworkRelationship::where('parent_id', $partner->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->with(['child'])
            ->get()
            ->map(function($relationship) {
                return [
                    'type' => 'network',
                    'title' => '새 파트너 추가',
                    'description' => ($relationship->child->business_name ?? '신규 파트너') . ' 가입',
                    'date' => $relationship->created_at,
                    'icon' => 'bi-people',
                    'color' => 'primary'
                ];
            });

        // 모든 활동 병합 및 정렬
        $activities = $recentSales
            ->concat($recentCommissions)
            ->concat($recentNetworkAdditions)
            ->sortByDesc('date')
            ->take($limit)
            ->values();

        return $activities;
    }

    /**
     * 전체 네트워크 사이즈 계산
     */
    private function getTotalNetworkSize($partnerId)
    {
        return PartnerNetworkRelationship::where('parent_id', $partnerId)
            ->count();
    }

    /**
     * 활성 네트워크 사이즈 계산
     */
    private function getActiveNetworkSize($partnerId)
    {
        return PartnerNetworkRelationship::where('parent_id', $partnerId)
            ->where('is_active', true)
            ->whereHas('child', function($query) {
                $query->where('status', 'active');
            })
            ->count();
    }

    /**
     * 네트워크 경로 계산
     */
    private function getNetworkPath($partnerId)
    {
        $path = [];
        $currentId = $partnerId;

        while ($currentId && count($path) < 10) { // 무한 루프 방지
            $relationship = PartnerNetworkRelationship::where('child_id', $currentId)
                ->with('parent')
                ->first();

            if ($relationship && $relationship->parent) {
                $path[] = [
                    'id' => $relationship->parent->id,
                    'name' => $relationship->parent->business_name ?? '파트너',
                    'level' => count($path) + 1
                ];
                $currentId = $relationship->parent->id;
            } else {
                break;
            }
        }

        return array_reverse($path);
    }

    /**
     * 네트워크 깊이 계산
     */
    private function calculateNetworkDepth($partnerId)
    {
        $maxDepth = 0;

        $this->calculateDepthRecursive($partnerId, 0, $maxDepth);

        return $maxDepth;
    }

    /**
     * 재귀적 깊이 계산
     */
    private function calculateDepthRecursive($partnerId, $currentDepth, &$maxDepth)
    {
        $maxDepth = max($maxDepth, $currentDepth);

        $children = PartnerNetworkRelationship::where('parent_id', $partnerId)
            ->where('is_active', true)
            ->pluck('child_id');

        foreach ($children as $childId) {
            $this->calculateDepthRecursive($childId, $currentDepth + 1, $maxDepth);
        }
    }
}
