<?php

namespace Jiny\Partner\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 샤딩된 회원 정보 검색 API
 *
 * 이메일 또는 이름으로 샤딩된 users_xxx 테이블에서 회원을 검색하고
 * 샤딩 번호, 사용자 ID, 이메일, 이름, UUID, 테이블 이름을 JSON으로 반환
 */
class UserSearchController extends Controller
{
    /**
     * 샤딩된 회원 검색 API
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        // 입력 유효성 검사
        $request->validate([
            'query' => 'required|string|min:2|max:100',
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        $searchQuery = trim($request->input('query'));
        $limit = (int) $request->input('limit', 20); // 기본 20개 제한

        Log::info('Sharded user search API request', [
            'query' => $searchQuery,
            'limit' => $limit,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        try {
            $results = $this->performShardedSearch($searchQuery, $limit);

            Log::info('Sharded user search API completed', [
                'query' => $searchQuery,
                'total_found' => count($results),
                'limit' => $limit
            ]);

            return response()->json([
                'success' => true,
                'message' => count($results) > 0 ? '회원 정보를 찾았습니다.' : '검색 결과가 없습니다.',
                'query' => $searchQuery,
                'total_found' => count($results),
                'limit' => $limit,
                'users' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Sharded user search API failed', [
                'query' => $searchQuery,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '검색 중 오류가 발생했습니다.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 특정 UUID로 회원 정보 조회
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function findByUuid(Request $request)
    {
        $request->validate([
            'uuid' => 'required|string|size:36'
        ]);

        $uuid = $request->input('uuid');

        Log::info('Find user by UUID API request', [
            'uuid' => $uuid,
            'ip' => $request->ip()
        ]);

        try {
            $user = $this->findUserByUuid($uuid);

            if ($user) {
                Log::info('User found by UUID', [
                    'uuid' => $uuid,
                    'user_id' => $user['id'],
                    'table' => $user['table_name']
                ]);

                return response()->json([
                    'success' => true,
                    'message' => '회원 정보를 찾았습니다.',
                    'user' => $user
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => '해당 UUID의 회원을 찾을 수 없습니다.'
                ], 404);
            }

        } catch (\Exception $e) {
            Log::error('Find user by UUID failed', [
                'uuid' => $uuid,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => '조회 중 오류가 발생했습니다.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 샤딩된 테이블에서 회원 검색 수행
     *
     * @param string $query
     * @param int $limit
     * @return array
     */
    private function performShardedSearch(string $query, int $limit): array
    {
        $results = collect();

        // 샤딩된 테이블 목록 가져오기
        $shardedTables = $this->getShardedUserTables();

        Log::info('Starting sharded search', [
            'query' => $query,
            'tables' => $shardedTables,
            'limit' => $limit
        ]);

        foreach ($shardedTables as $tableName) {
            if ($results->count() >= $limit) break;

            try {
                Log::info("Searching in table: {$tableName}");

                // 각 샤딩 테이블에서 검색
                $users = DB::table($tableName)
                    ->select([
                        'id',
                        'uuid',
                        'name',
                        'email',
                        'created_at',
                        'updated_at',
                        'email_verified_at'
                    ])
                    ->whereNull('deleted_at') // 삭제되지 않은 회원만
                    ->where(function($q) use ($query) {
                        $q->where('email', 'like', '%' . $query . '%')
                          ->orWhere('name', 'like', '%' . $query . '%');
                    })
                    ->orderBy('created_at', 'desc')
                    ->limit($limit - $results->count())
                    ->get();

                Log::info("Found {$users->count()} users in {$tableName}");

                // 결과를 표준 형태로 변환
                foreach ($users as $user) {
                    if ($results->count() >= $limit) break;

                    $results->push([
                        'id' => $user->id,
                        'uuid' => $user->uuid,
                        'name' => $user->name,
                        'email' => $user->email,
                        'email_verified' => !is_null($user->email_verified_at),
                        'email_verified_at' => $user->email_verified_at,
                        'created_at' => $user->created_at,
                        'updated_at' => $user->updated_at,
                        'table_name' => $tableName,
                        'shard_number' => $this->getShardNumber($tableName),
                        'match_score' => $this->calculateMatchScore($user, $query)
                    ]);
                }

            } catch (\Exception $e) {
                Log::warning("Could not search in table {$tableName}", [
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        // 매칭 점수로 정렬하여 반환
        return $results->sortBy('match_score')->values()->toArray();
    }

    /**
     * UUID로 특정 회원 찾기
     *
     * @param string $uuid
     * @return array|null
     */
    private function findUserByUuid(string $uuid): ?array
    {
        $shardedTables = $this->getShardedUserTables();

        foreach ($shardedTables as $tableName) {
            try {
                $user = DB::table($tableName)
                    ->select([
                        'id',
                        'uuid',
                        'name',
                        'email',
                        'created_at',
                        'updated_at',
                        'email_verified_at'
                    ])
                    ->whereNull('deleted_at')
                    ->where('uuid', $uuid)
                    ->first();

                if ($user) {
                    return [
                        'id' => $user->id,
                        'uuid' => $user->uuid,
                        'name' => $user->name,
                        'email' => $user->email,
                        'email_verified' => !is_null($user->email_verified_at),
                        'email_verified_at' => $user->email_verified_at,
                        'created_at' => $user->created_at,
                        'updated_at' => $user->updated_at,
                        'table_name' => $tableName,
                        'shard_number' => $this->getShardNumber($tableName)
                    ];
                }

            } catch (\Exception $e) {
                Log::warning("Could not search UUID in table {$tableName}", [
                    'uuid' => $uuid,
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        return null;
    }

    /**
     * 검색 매칭 점수 계산
     *
     * @param object $user
     * @param string $query
     * @return int
     */
    private function calculateMatchScore($user, string $query): int
    {
        $score = 0;
        $queryLower = strtolower($query);

        // 이메일 매칭 점수
        if ($user->email) {
            $email = strtolower($user->email);
            if ($email === $queryLower) {
                $score += 0; // 정확 일치 (가장 높은 우선순위)
            } elseif (str_starts_with($email, $queryLower)) {
                $score += 1; // 시작 일치
            } else {
                $score += 2; // 포함 일치
            }
        }

        // 이름 매칭 점수
        if ($user->name) {
            $name = strtolower($user->name);
            if ($name === $queryLower) {
                $score += 0; // 정확 일치
            } elseif (str_starts_with($name, $queryLower)) {
                $score += 1; // 시작 일치
            } else {
                $score += 2; // 포함 일치
            }
        }

        return $score;
    }

    /**
     * 샤딩된 사용자 테이블 목록 조회
     *
     * @return array
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
                // 기본 테이블 반환 (fallback)
                return ['users_001', 'users_002'];
            }

            return $tableNames;

        } catch (\Exception $e) {
            Log::error('Failed to get sharded user tables: ' . $e->getMessage());
            return ['users_001', 'users_002']; // fallback
        }
    }

    /**
     * 테이블명에서 샤드 번호 추출
     *
     * @param string $tableName
     * @return int
     */
    private function getShardNumber(string $tableName): int
    {
        if (preg_match('/users_(\d{3})/', $tableName, $matches)) {
            return (int)$matches[1];
        }
        return 0;
    }

    /**
     * 샤딩된 테이블 정보 조회 API
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTables()
    {
        try {
            $tables = $this->getShardedUserTables();
            $tableInfo = [];

            foreach ($tables as $tableName) {
                try {
                    $count = DB::table($tableName)->whereNull('deleted_at')->count();
                    $tableInfo[] = [
                        'table_name' => $tableName,
                        'shard_number' => $this->getShardNumber($tableName),
                        'user_count' => $count
                    ];
                } catch (\Exception $e) {
                    $tableInfo[] = [
                        'table_name' => $tableName,
                        'shard_number' => $this->getShardNumber($tableName),
                        'user_count' => 0,
                        'error' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => '샤딩된 테이블 정보를 조회했습니다.',
                'tables' => $tableInfo,
                'total_tables' => count($tableInfo)
            ]);

        } catch (\Exception $e) {
            Log::error('Get sharded tables info failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => '테이블 정보 조회 중 오류가 발생했습니다.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}