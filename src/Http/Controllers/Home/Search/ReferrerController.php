<?php

namespace Jiny\Partner\Http\Controllers\Home\Search;

use Jiny\Partner\Http\Controllers\PartnerController;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReferrerController extends PartnerController
{
    /**
     * 이메일로 추천인 검색
     * user_xxx 테이블에서 이메일 기반 검색하여 UUID와 샤딩 정보 조회
     */
    public function __invoke(Request $request)
    {
        // 사용자 인증 (파트너 필수는 아님)
        $authResult = $this->authenticateUser($request, 'referrer_search');
        if (!$authResult['success']) {
            return $authResult['redirect'];
        }

        $email = $request->get('email', '');
        $format = $request->get('format', 'html');

        if (empty($email)) {
            if ($format === 'json') {
                return response()->json([
                    'success' => false,
                    'message' => '이메일을 입력해주세요.',
                    'data' => null
                ]);
            }

            // HTML 요청에서 이메일이 없으면 검색 폼 표시
            return view('jiny-partner::home.search.referrer', [
                'pageTitle' => '추천인 검색'
            ]);
        }

        // 이메일 형식 검증
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if ($format === 'json') {
                return response()->json([
                    'success' => false,
                    'message' => '올바른 이메일 형식이 아닙니다.',
                    'data' => null
                ]);
            }

            return back()->withErrors(['email' => '올바른 이메일 형식이 아닙니다.']);
        }

        try {
            // 사용자 정보 검색
            $userInfo = $this->searchUserByEmail($email);

            if (!$userInfo) {
                if ($format === 'json') {
                    return response()->json([
                        'success' => false,
                        'message' => '해당 이메일로 등록된 사용자를 찾을 수 없습니다.',
                        'data' => null
                    ]);
                }

                return back()->withErrors(['email' => '해당 이메일로 등록된 사용자를 찾을 수 없습니다.']);
            }

            // 파트너 등록 여부 확인
            $partnerInfo = $this->getPartnerInfo($userInfo['user_uuid']);

            // 추천인으로 사용 가능한지 확인
            $eligibility = $this->checkReferrerEligibility($partnerInfo);

            $responseData = [
                'user_info' => $userInfo,
                'partner_info' => $partnerInfo,
                'eligibility' => $eligibility,
                'can_refer' => $eligibility['eligible']
            ];

            if ($format === 'json') {
                return response()->json([
                    'success' => true,
                    'message' => '추천인 정보를 찾았습니다.',
                    'data' => $responseData
                ]);
            }

            return view('jiny-partner::home.search.referrer-result', [
                'searchResult' => $responseData,
                'searchEmail' => $email,
                'pageTitle' => '추천인 검색 결과'
            ]);

        } catch (\Exception $e) {
            Log::error('Referrer search failed', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($format === 'json') {
                return response()->json([
                    'success' => false,
                    'message' => '검색 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.',
                    'data' => null
                ], 500);
            }

            return back()->withErrors(['email' => '검색 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.']);
        }
    }

    /**
     * 이메일로 사용자 검색
     * user_xxx 테이블 구조에서 검색
     */
    private function searchUserByEmail(string $email): ?array
    {
        // user_profile 테이블에서 먼저 검색
        $userProfile = DB::table('user_profile')
            ->where('email', $email)
            ->first();

        if ($userProfile) {
            return [
                'user_uuid' => $userProfile->user_uuid,
                'shard_id' => $userProfile->shard_id,
                'name' => $userProfile->name,
                'email' => $userProfile->email,
                'source_table' => 'user_profile'
            ];
        }

        // user_auth 테이블이 존재하는 경우에만 검색 시도
        try {
            $userAuth = DB::table('user_auth')
                ->where('email', $email)
                ->first();

            if ($userAuth) {
                // 추가 정보를 위해 user_profile 조회
                $additionalInfo = DB::table('user_profile')
                    ->where('user_uuid', $userAuth->user_uuid)
                    ->first();

                return [
                    'user_uuid' => $userAuth->user_uuid,
                    'shard_id' => $additionalInfo->shard_id ?? null,
                    'name' => $additionalInfo->name ?? $userAuth->name ?? 'Unknown',
                    'email' => $email,
                    'source_table' => 'user_auth'
                ];
            }
        } catch (\Exception $e) {
            // user_auth 테이블이 없거나 접근할 수 없음
            Log::debug("user_auth table not accessible: " . $e->getMessage());
        }

        // 샤딩된 users_xxx 테이블에서 검색 시도
        $shardedTables = $this->getShardedUserTables();

        foreach ($shardedTables as $tableName) {
            try {
                $user = DB::table($tableName)
                    ->where('email', $email)
                    ->whereNull('deleted_at')
                    ->first();

                if ($user) {
                    return [
                        'user_uuid' => $user->uuid ?? null,
                        'shard_id' => null, // 샤딩된 테이블에서는 shard_id 없음
                        'name' => $user->name ?? 'Unknown',
                        'email' => $email,
                        'source_table' => $tableName
                    ];
                }
            } catch (\Exception $e) {
                Log::debug("Could not search in sharded table {$tableName}", [
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        // 다른 user_xxx 테이블들에서도 검색 시도
        $userTables = $this->getUserTablesList();

        foreach ($userTables as $tableName) {
            try {
                $user = DB::table($tableName)
                    ->where('email', $email)
                    ->first();

                if ($user) {
                    return [
                        'user_uuid' => $user->user_uuid ?? $user->uuid ?? null,
                        'shard_id' => $user->shard_id ?? null,
                        'name' => $user->name ?? 'Unknown',
                        'email' => $email,
                        'source_table' => $tableName
                    ];
                }
            } catch (\Exception $e) {
                // 테이블이 존재하지 않거나 구조가 다를 수 있음
                Log::debug("Could not search in table {$tableName}", [
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        return null;
    }

    /**
     * 샤딩된 사용자 테이블 목록 조회
     */
    private function getShardedUserTables(): array
    {
        try {
            $tableNames = [];
            $databaseDriver = DB::getDriverName();

            if ($databaseDriver === 'sqlite') {
                $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name LIKE 'users_%'");
                foreach ($tables as $table) {
                    $tableName = $table->name;
                    if (preg_match('/^users_\d{3}$/', $tableName)) {
                        $tableNames[] = $tableName;
                    }
                }
            } else {
                $tables = DB::select("SHOW TABLES LIKE 'users_%'");
                foreach ($tables as $table) {
                    $tableName = array_values((array)$table)[0];
                    if (preg_match('/^users_\d{3}$/', $tableName)) {
                        $tableNames[] = $tableName;
                    }
                }
            }

            sort($tableNames);

            if (empty($tableNames)) {
                // 실제 존재하는 테이블만 반환
                return ['users_001', 'users_002']; // fallback
            }

            return $tableNames;

        } catch (\Exception $e) {
            Log::error('Failed to get sharded user tables: ' . $e->getMessage());
            return ['users_001', 'users_002']; // fallback
        }
    }

    /**
     * 사용 가능한 user_xxx 테이블 목록 조회
     */
    private function getUserTablesList(): array
    {
        $availableTables = [];
        $tables = [
            'user_profile',
            'user_auth',
            'user_admin',
            'user_locale',
            'user_phone',
            'user_address',
            'user_social'
        ];

        foreach ($tables as $table) {
            try {
                // 테이블 존재 여부 확인
                DB::table($table)->limit(1)->get();
                $availableTables[] = $table;
            } catch (\Exception $e) {
                // 테이블이 존재하지 않음
                Log::debug("Table {$table} not available: " . $e->getMessage());
            }
        }

        return $availableTables;
    }

    /**
     * 파트너 정보 조회
     */
    private function getPartnerInfo(?string $userUuid): ?array
    {
        if (!$userUuid) {
            return null;
        }

        $partner = PartnerUser::where('user_uuid', $userUuid)->first();

        if (!$partner) {
            return null;
        }

        return [
            'user_uuid' => $partner->user_uuid,
            'tier_name' => $partner->tier_name,
            'type_name' => $partner->type_name,
            'status' => $partner->status,
            'commission_rate' => $partner->commission_rate,
            'joined_at' => $partner->joined_at,
            'referrer_uuid' => $partner->referrer_uuid,
            'managed_partners_count' => $this->getManagedPartnersCount($partner->user_uuid),
            'total_referrals' => $this->getTotalReferralsCount($partner->user_uuid),
            'approval_permissions' => $this->getApprovalPermissions($partner)
        ];
    }

    /**
     * 관리 중인 파트너 수 조회
     */
    private function getManagedPartnersCount(string $userUuid): int
    {
        return PartnerUser::where('referrer_uuid', $userUuid)
            ->where('status', 'active')
            ->count();
    }

    /**
     * 총 추천 수 조회
     */
    private function getTotalReferralsCount(string $userUuid): int
    {
        return PartnerUser::where('referrer_uuid', $userUuid)->count();
    }

    /**
     * 추천인 자격 확인
     */
    private function checkReferrerEligibility(?array $partnerInfo): array
    {
        if (!$partnerInfo) {
            return [
                'eligible' => false,
                'reason' => '파트너로 등록되지 않은 사용자입니다.',
                'requirements' => [
                    '파트너 등록 필요',
                    '활성 상태 유지 필요'
                ]
            ];
        }

        // 비활성 파트너는 추천 불가
        if ($partnerInfo['status'] !== 'active') {
            return [
                'eligible' => false,
                'reason' => '비활성 상태의 파트너는 추천할 수 없습니다.',
                'requirements' => [
                    '파트너 상태 활성화 필요'
                ]
            ];
        }

        // 등급별 추천 가능 여부 확인
        $tierName = $partnerInfo['tier_name'] ?? 'Bronze';
        $managedCount = $partnerInfo['managed_partners_count'] ?? 0;

        $tierLimits = [
            'Bronze' => ['max_referrals' => 10, 'can_approve' => false],
            'Silver' => ['max_referrals' => 25, 'can_approve' => true],
            'Gold' => ['max_referrals' => 50, 'can_approve' => true],
            'Platinum' => ['max_referrals' => 100, 'can_approve' => true]
        ];

        $limits = $tierLimits[$tierName] ?? $tierLimits['Bronze'];

        if ($managedCount >= $limits['max_referrals']) {
            return [
                'eligible' => false,
                'reason' => "최대 추천 한도({$limits['max_referrals']}명)에 도달했습니다.",
                'requirements' => [
                    '등급 상승 필요',
                    '또는 기존 추천 관리 최적화'
                ]
            ];
        }

        // 모든 조건을 만족하는 경우
        return [
            'eligible' => true,
            'reason' => '추천 가능한 파트너입니다.',
            'benefits' => [
                '추천 보너스 지급',
                $limits['can_approve'] ? '직접 승인 권한 있음' : '관리자 승인 필요',
                "현재 {$managedCount}/{$limits['max_referrals']}명 관리 중"
            ],
            'remaining_slots' => $limits['max_referrals'] - $managedCount
        ];
    }

    /**
     * 파트너 승인 권한 조회 (간소화된 버전)
     */
    private function getApprovalPermissions(PartnerUser $partner): array
    {
        $tierName = $partner->tier_name ?? 'Bronze';

        $permissions = [
            'Bronze' => ['can_approve' => false, 'monthly_limit' => 0],
            'Silver' => ['can_approve' => true, 'monthly_limit' => 2],
            'Gold' => ['can_approve' => true, 'monthly_limit' => 5],
            'Platinum' => ['can_approve' => true, 'monthly_limit' => 15]
        ];

        return $permissions[$tierName] ?? $permissions['Bronze'];
    }
}