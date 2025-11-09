<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerUsers;

use App\Http\Controllers\Controller;
use Jiny\Partner\Models\PartnerUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    /**
     * 샤딩된 사용자 테이블에서 이메일로 사용자 검색
     */
    public function __invoke(Request $request)
    {
        $email = $request->get('email', '');

        if (empty($email)) {
            return response()->json([
                'success' => false,
                'message' => '검색할 이메일을 입력해주세요.',
                'users' => []
            ]);
        }

        // 검색할 사용자 테이블 목록
        $userTables = [
            'users',
            'users_001',
            'users_002',
            'users_003'
        ];

        $users = collect();

        foreach ($userTables as $table) {
            try {
                // 테이블 존재 여부 확인
                if (!$this->tableExists($table)) {
                    continue;
                }

                $result = DB::table($table)
                    ->where('email', 'like', "%{$email}%")
                    ->select(
                        'id',
                        'email',
                        'name',
                        'uuid',
                        DB::raw("'{$table}' as user_table"),
                        DB::raw($this->getShardNumber($table) . " as shard_number")
                    )
                    ->limit(10) // 각 테이블에서 최대 10개
                    ->get();

                $users = $users->merge($result);
            } catch (\Exception $e) {
                // 테이블 접근 오류 시 무시하고 계속
                \Log::warning("Failed to search in table {$table}: " . $e->getMessage());
                continue;
            }
        }

        // 이미 등록된 사용자들 제외 (soft delete된 레코드 포함)
        $activeRegisteredUsers = PartnerUser::select('user_id', 'user_table', 'status')->get();
        $trashedRegisteredUsers = PartnerUser::onlyTrashed()->select('user_id', 'user_table')->get();

        $activeKeys = $activeRegisteredUsers->map(function($item) {
            return $item->user_table . '_' . $item->user_id;
        })->toArray();

        $trashedKeys = $trashedRegisteredUsers->map(function($item) {
            return $item->user_table . '_' . $item->user_id;
        })->toArray();

        $availableUsers = $users->filter(function($user) use ($activeKeys, $trashedKeys) {
            $key = $user->user_table . '_' . $user->id;
            // active나 trashed 상태 모두 제외
            return !in_array($key, $activeKeys) && !in_array($key, $trashedKeys);
        });

        // 등록되었지만 현재 활성 상태인 사용자들 카운트 (정보 제공용)
        $activeRegisteredCount = $users->filter(function($user) use ($activeKeys) {
            $key = $user->user_table . '_' . $user->id;
            return in_array($key, $activeKeys);
        })->count();

        // 삭제된 상태의 사용자들 카운트 (정보 제공용)
        $trashedRegisteredCount = $users->filter(function($user) use ($trashedKeys) {
            $key = $user->user_table . '_' . $user->id;
            return in_array($key, $trashedKeys);
        })->count();

        // 결과 제한 (최대 20개)
        $limitedUsers = $availableUsers->take(20)->values();

        return response()->json([
            'success' => true,
            'message' => count($limitedUsers) > 0
                ? count($limitedUsers) . '명의 등록 가능한 사용자를 찾았습니다.'
                : '등록 가능한 사용자가 없습니다.',
            'users' => $limitedUsers,
            'total_found' => count($users),
            'available_count' => count($limitedUsers),
            'already_registered' => $activeRegisteredCount,
            'deleted_registered' => $trashedRegisteredCount,
            'summary' => [
                'total' => count($users),
                'available' => count($limitedUsers),
                'active_registered' => $activeRegisteredCount,
                'deleted_registered' => $trashedRegisteredCount
            ]
        ]);
    }

    /**
     * 특정 사용자 정보 조회 (등록 시 사용)
     */
    public function getUserInfo(Request $request)
    {
        $userId = $request->get('user_id');
        $userTable = $request->get('user_table', 'users');

        if (!$userId || !$userTable) {
            return response()->json([
                'success' => false,
                'message' => '사용자 ID와 테이블명이 필요합니다.'
            ]);
        }

        try {
            if (!$this->tableExists($userTable)) {
                return response()->json([
                    'success' => false,
                    'message' => '지정된 사용자 테이블이 존재하지 않습니다.'
                ]);
            }

            $user = DB::table($userTable)
                ->where('id', $userId)
                ->select('id', 'email', 'name', 'created_at')
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => '사용자를 찾을 수 없습니다.'
                ]);
            }

            // 이미 등록된 사용자인지 확인
            $existingPartner = PartnerUser::where('user_id', $userId)
                ->where('user_table', $userTable)
                ->first();

            if ($existingPartner) {
                return response()->json([
                    'success' => false,
                    'message' => '이미 파트너로 등록된 사용자입니다.',
                    'existing_partner' => [
                        'id' => $existingPartner->id,
                        'status' => $existingPartner->status,
                        'tier' => $existingPartner->partnerTier->tier_name ?? 'N/A'
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => '사용자 정보를 가져왔습니다.',
                'data' => [
                    'user_id' => $user->id,
                    'user_table' => $userTable,
                    'email' => $user->email,
                    'name' => $user->name,
                    'user_created_at' => $user->created_at
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '사용자 정보 조회 중 오류가 발생했습니다.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ]);
        }
    }

    /**
     * 테이블 존재 여부 확인
     */
    private function tableExists($tableName): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($tableName);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 테이블명에서 샤드 번호 추출
     */
    private function getShardNumber($tableName): int
    {
        if ($tableName === 'users') {
            return 0; // 메인 테이블은 0번
        }

        // users_001, users_002 등에서 번호 추출
        if (preg_match('/users_(\d+)/', $tableName, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    /**
     * 사용자 테이블 목록 조회
     */
    public function getUserTables()
    {
        $tables = [
            'users' => 'users (메인)',
            'user_001' => 'user_001',
            'user_002' => 'user_002',
            'user_003' => 'user_003'
        ];

        $availableTables = [];

        foreach ($tables as $table => $label) {
            if ($this->tableExists($table)) {
                try {
                    $count = DB::table($table)->count();
                    $availableTables[$table] = [
                        'label' => $label,
                        'count' => $count
                    ];
                } catch (\Exception $e) {
                    // 테이블 접근 권한이 없는 경우
                    continue;
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $availableTables
        ]);
    }
}