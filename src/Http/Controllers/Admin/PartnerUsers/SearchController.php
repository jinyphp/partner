<?php

namespace Jiny\Partner\Http\Controllers\Admin\PartnerUsers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Jiny\Partner\Models\PartnerUser;

class SearchController extends Controller
{
    /**
     * 샤딩된 사용자 테이블에서 사용자 검색 (관리자용)
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'email' => 'required|string|min:2|max:255',
        ]);

        $searchEmail = trim($request->input('email'));

        Log::info('Admin user search request', [
            'search_email' => $searchEmail,
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip()
        ]);

        try {
            $results = $this->performShardedUserSearch($searchEmail);

            Log::info('Admin user search completed', [
                'search_email' => $searchEmail,
                'total_found' => $results['total_found'],
                'available_count' => $results['available_count'],
                'already_registered' => $results['already_registered']
            ]);

            return response()->json([
                'success' => true,
                'message' => count($results['users']) > 0 ? '사용자를 찾았습니다.' : '검색 결과가 없습니다.',
                'users' => $results['users'],
                'total_found' => $results['total_found'],
                'available_count' => $results['available_count'],
                'already_registered' => $results['already_registered'],
                'deleted_registered' => $results['deleted_registered']
            ]);

        } catch (\Exception $e) {
            Log::error('Admin user search failed', [
                'search_email' => $searchEmail,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '검색 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 샤딩된 사용자 테이블에서 통합 검색
     */
    private function performShardedUserSearch($searchQuery)
    {
        $users = collect();
        $totalFound = 0;
        $alreadyRegistered = 0;
        $deletedRegistered = 0;
        $availableCount = 0;
        $limit = 50; // 최대 50명까지

        Log::info('Performing sharded user search', [
            'query' => $searchQuery,
            'limit' => $limit
        ]);

        try {
            // 샤딩된 테이블 목록 가져오기
            $shardedTables = $this->getShardedUserTables();

            Log::info('Sharded tables found', [
                'tables' => $shardedTables
            ]);

            foreach ($shardedTables as $tableName) {
                if ($users->count() >= $limit) break;

                Log::info("Searching in table: {$tableName}");

                // 각 샤딩 테이블에서 검색
                $dbQuery = DB::table($tableName)
                    ->select([
                        'id',
                        'uuid',
                        'name',
                        'email',
                        'created_at',
                        'updated_at',
                        'email_verified_at',
                        DB::raw("'{$tableName}' as user_table"),
                        DB::raw("'" . $this->getShardNumber($tableName) . "' as shard_number")
                    ])
                    ->whereNull('deleted_at'); // 삭제되지 않은 회원만

                // 이메일과 이름 모두에서 검색
                $dbQuery->where(function($q) use ($searchQuery) {
                    $q->where('email', 'like', '%' . $searchQuery . '%')
                      ->orWhere('name', 'like', '%' . $searchQuery . '%');
                });

                // 정렬 및 제한
                $dbQuery->orderBy('created_at', 'desc')
                       ->limit($limit - $users->count());

                $results = $dbQuery->get();
                $totalFound += $results->count();

                Log::info("Search results from {$tableName}", [
                    'count' => $results->count()
                ]);

                // 검색 결과를 전체 컬렉션에 추가
                foreach ($results as $user) {
                    if ($users->count() >= $limit) break;

                    // 파트너 등록 상태 확인
                    $partnerStatus = $this->checkPartnerStatus($user->uuid);

                    $userData = [
                        'id' => $user->id,
                        'uuid' => $user->uuid,
                        'name' => $user->name,
                        'email' => $user->email,
                        'created_at' => $user->created_at,
                        'updated_at' => $user->updated_at,
                        'email_verified' => !is_null($user->email_verified_at),
                        'email_verified_at' => $user->email_verified_at,
                        'user_table' => $user->user_table,
                        'shard_number' => (int)$user->shard_number,
                        'partner_status' => $partnerStatus['status'],
                        'partner_info' => $partnerStatus['info'],
                        'is_available' => $partnerStatus['is_available'],
                        'match_score' => $this->calculateMatchScore($user, $searchQuery)
                    ];

                    // 통계 업데이트
                    if ($partnerStatus['status'] === 'active') {
                        $alreadyRegistered++;
                    } elseif ($partnerStatus['status'] === 'deleted') {
                        $deletedRegistered++;
                    }

                    if ($partnerStatus['is_available']) {
                        $availableCount++;
                    }

                    $users->push($userData);
                }
            }

            // 검색 정확도로 정렬
            $sortedUsers = $users->sortBy('match_score')->values()->toArray();

            return [
                'users' => $sortedUsers,
                'total_found' => $totalFound,
                'available_count' => $availableCount,
                'already_registered' => $alreadyRegistered,
                'deleted_registered' => $deletedRegistered
            ];

        } catch (\Exception $e) {
            Log::error('Sharded user search error: ' . $e->getMessage(), [
                'query' => $searchQuery,
                'error' => $e->getMessage()
            ]);

            return [
                'users' => [],
                'total_found' => 0,
                'available_count' => 0,
                'already_registered' => 0,
                'deleted_registered' => 0
            ];
        }
    }

    /**
     * 파트너 등록 상태 확인
     */
    private function checkPartnerStatus($userUuid)
    {
        if (!$userUuid) {
            return [
                'status' => 'not_found',
                'info' => null,
                'is_available' => false
            ];
        }

        $partner = PartnerUser::withTrashed()
            ->where('user_uuid', $userUuid)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$partner) {
            return [
                'status' => 'not_registered',
                'info' => null,
                'is_available' => true
            ];
        }

        if ($partner->trashed()) {
            return [
                'status' => 'deleted',
                'info' => [
                    'id' => $partner->id,
                    'deleted_at' => $partner->deleted_at,
                    'tier_name' => $partner->tier_name ?? 'Unknown'
                ],
                'is_available' => true // 삭제된 경우 재등록 가능
            ];
        }

        $status = $partner->status ?? 'unknown';
        $isAvailable = in_array($status, ['pending', 'inactive']); // 대기, 비활성 상태만 덮어쓰기 가능

        return [
            'status' => $status,
            'info' => [
                'id' => $partner->id,
                'tier_name' => $partner->tier_name ?? 'Unknown',
                'status' => $partner->status,
                'joined_at' => $partner->joined_at
            ],
            'is_available' => $isAvailable
        ];
    }

    /**
     * 검색 매칭 점수 계산
     */
    private function calculateMatchScore($user, $query)
    {
        $score = 0;
        $queryLower = strtolower($query);

        // 이메일 매칭 점수
        if ($user->email) {
            $email = strtolower($user->email);
            if ($email === $queryLower) {
                $score += 0; // 정확 일치
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
     */
    private function getShardedUserTables()
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
     * 테이블명에서 샤드 번호 추출
     */
    private function getShardNumber($tableName)
    {
        if (preg_match('/users_(\d{3})/', $tableName, $matches)) {
            return (int)$matches[1];
        }
        return 0;
    }
}