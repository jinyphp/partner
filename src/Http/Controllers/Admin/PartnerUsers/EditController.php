<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerUsers;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerUser;
use Jiny\Partner\Models\PartnerTier;
use Jiny\Partner\Models\PartnerType;

class EditController extends Controller
{
    protected $model;
    protected $viewPath;
    protected $routePrefix;
    protected $title;

    public function __construct()
    {
        $this->model = PartnerUser::class;
        $this->viewPath = 'jiny-partner::admin.partner-users';
        $this->routePrefix = 'partner.users';
        $this->title = '파트너 회원';
    }

    /**
     * 파트너 회원 수정 폼 표시
     */
    public function __invoke($id)
    {
        $item = $this->model::with(['partnerTier', 'partnerType', 'creator', 'updater'])->findOrFail($id);

        // 활성화된 파트너 타입 목록
        $partnerTypes = PartnerType::active()->orderBy('sort_order', 'asc')->orderBy('type_name', 'asc')->get();

        // 활성화된 파트너 등급 목록
        $partnerTiers = PartnerTier::active()->orderBy('priority_level')->get();

        // 상태 옵션
        $statusOptions = [
            'pending' => '대기',
            'active' => '활성',
            'inactive' => '비활성',
            'suspended' => '정지'
        ];

        // 사용자 테이블 옵션 (샤딩)
        $userTableOptions = [
            'users' => 'users (메인)',
            'user_001' => 'user_001',
            'user_002' => 'user_002',
            'user_003' => 'user_003'
        ];

        // 샤딩된 테이블에서 현재 사용자 정보 조회
        $shardedUserInfo = $item->getUserFromShardedTable();

        // 프로필 데이터 JSON을 문자열로 변환 (수정용)
        $profileDataJson = $item->profile_data ? json_encode($item->profile_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '';

        // 승급 분석 (수정 시 참고용)
        $upgradeAnalysis = $this->getUpgradeAnalysis($item);

        return view("{$this->viewPath}.edit", [
            'item' => $item,
            'title' => $this->title,
            'routePrefix' => $this->routePrefix,
            'partnerTypes' => $partnerTypes,
            'partnerTiers' => $partnerTiers,
            'statusOptions' => $statusOptions,
            'userTableOptions' => $userTableOptions,
            'shardedUserInfo' => $shardedUserInfo,
            'profileDataJson' => $profileDataJson,
            'upgradeAnalysis' => $upgradeAnalysis
        ]);
    }

    /**
     * 승급 분석 데이터 (간소화된 버전)
     */
    protected function getUpgradeAnalysis($item): array
    {
        $nextTier = PartnerTier::where('priority_level', '<', $item->partnerTier->priority_level)
            ->where('is_active', true)
            ->orderBy('priority_level', 'desc')
            ->first();

        if (!$nextTier) {
            return ['has_next_tier' => false];
        }

        $canUpgrade = $item->canUpgradeToTier($nextTier);
        $requirements = [];

        if ($item->total_completed_jobs < $nextTier->min_completed_jobs) {
            $requirements['jobs'] = [
                'current' => $item->total_completed_jobs,
                'required' => $nextTier->min_completed_jobs,
                'missing' => $nextTier->min_completed_jobs - $item->total_completed_jobs
            ];
        }

        if ($item->average_rating < $nextTier->min_rating) {
            $requirements['rating'] = [
                'current' => $item->average_rating,
                'required' => $nextTier->min_rating,
                'missing' => round($nextTier->min_rating - $item->average_rating, 2)
            ];
        }

        if ($item->punctuality_rate < $nextTier->min_punctuality_rate) {
            $requirements['punctuality'] = [
                'current' => $item->punctuality_rate,
                'required' => $nextTier->min_punctuality_rate,
                'missing' => round($nextTier->min_punctuality_rate - $item->punctuality_rate, 2)
            ];
        }

        if ($item->satisfaction_rate < $nextTier->min_satisfaction_rate) {
            $requirements['satisfaction'] = [
                'current' => $item->satisfaction_rate,
                'required' => $nextTier->min_satisfaction_rate,
                'missing' => round($nextTier->min_satisfaction_rate - $item->satisfaction_rate, 2)
            ];
        }

        return [
            'has_next_tier' => true,
            'next_tier' => $nextTier,
            'can_upgrade' => $canUpgrade,
            'missing_requirements' => $requirements
        ];
    }

    protected function getValidationRules($item = null): array
    {
        return [];
    }
}