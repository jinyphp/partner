<?php

namespace Jiny\Partner\Http\Controllers\Home;

use Jiny\Partner\Http\Controllers\PartnerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * 공용 회원 검색 컨트롤러
 *
 * 다양한 파트너 기능에서 재사용 가능한 회원 검색 API 제공
 * - 샤딩된 users_xxx 테이블에서 통합 검색
 * - 이메일, 이름, UUID 등 다양한 검색 조건 지원
 * - 페이지네이션 및 정렬 기능 제공
 */
class UserSearchController extends PartnerController
{
    /**
     * 회원 검색 API
     *
     * 검색 파라미터:
     * - email: 이메일 부분 일치 검색
     * - name: 이름 부분 일치 검색
     * - uuid: UUID 정확 일치 검색
     * - verified_only: 인증된 회원만 검색 (true/false)
     * - limit: 검색 결과 제한 (기본값: 10, 최대: 50)
     * - sort: 정렬 방식 (created_at, name, email) 기본값: created_at
     * - order: 정렬 순서 (asc, desc) 기본값: desc
     */
    public function search(Request $request)
    {
        try {
            // ========================================
            // Step 1: 단순 사용자 인증 확인
            // ========================================
            $user = $this->auth($request);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'code' => 401,
                    'message' => '로그인이 필요합니다.'
                ], 401);
            }

            // Step 2: 파트너 등록 여부 확인 (선택적)
            $partner = $this->isPartner($user);
            if (!$partner) {
                return response()->json([
                    'success' => false,
                    'code' => 403,
                    'message' => '파트너 등록이 필요합니다.'
                ], 403);
            }

            // ========================================
            // Step 3: 검색 파라미터 검증
            // ========================================
            $request->validate([
                'email' => 'nullable|string|min:2|max:100',
                'name' => 'nullable|string|min:2|max:50',
                'uuid' => 'nullable|string|size:36',
                'verified_only' => 'nullable|boolean',
                'limit' => 'nullable|integer|min:1|max:50',
                'sort' => 'nullable|string|in:created_at,name,email',
                'order' => 'nullable|string|in:asc,desc'
            ]);

            // 검색 조건 설정
            $searchParams = [
                'email' => $request->input('email'),
                'name' => $request->input('name'),
                'uuid' => $request->input('uuid'),
                'verified_only' => $request->boolean('verified_only', false),
                'limit' => $request->input('limit', 10),
                'sort' => $request->input('sort', 'created_at'),
                'order' => $request->input('order', 'desc')
            ];

            // 최소 검색 조건 확인
            if (empty($searchParams['email']) && empty($searchParams['name']) && empty($searchParams['uuid'])) {
                return response()->json([
                    'success' => false,
                    'code' => 422,
                    'message' => '이메일, 이름, 또는 UUID 중 하나는 입력해주세요.'
                ], 422);
            }

            Log::info("User search request", [
                'search_params' => $searchParams,
                'partner_id' => $partner->id,
                'user_uuid' => $user->uuid
            ]);

            // ========================================
            // Step 4: 샤딩된 사용자 테이블에서 검색
            // ========================================
            $searchResults = $this->performShardedUserSearch($searchParams);

            Log::info("User search results", [
                'search_params' => $searchParams,
                'found_count' => $searchResults->count(),
                'partner_id' => $partner->id
            ]);

            // ========================================
            // Step 5: 검색 결과 응답
            // ========================================
            return response()->json([
                'success' => true,
                'code' => 200,
                'message' => "검색 결과 {$searchResults->count()}명의 회원을 찾았습니다.",
                'data' => [
                    'users' => $searchResults,
                    'search_params' => $searchParams,
                    'result_count' => $searchResults->count(),
                    'pagination' => [
                        'limit' => $searchParams['limit'],
                        'has_more' => $searchResults->count() >= $searchParams['limit']
                    ]
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'code' => 422,
                'message' => '검색 조건이 올바르지 않습니다.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('User search error: ' . $e->getMessage(), [
                'search_params' => $request->all(),
                'partner_id' => $partner->id ?? 'unknown',
                'user_uuid' => $user->uuid ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'code' => 500,
                'message' => '회원 검색 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 샤딩된 사용자 테이블에서 통합 검색
     *
     * @param array $searchParams 검색 파라미터
     * @return \Illuminate\Support\Collection
     */
    private function performShardedUserSearch($searchParams)
    {
        $users = collect();
        $currentLimit = $searchParams['limit'];

        try {
            // 샤딩된 테이블 목록 가져오기
            $shardedTables = $this->getShardedUserTables();

            foreach ($shardedTables as $tableName) {
                if ($currentLimit <= 0) break;

                // 각 샤딩 테이블에서 검색
                $query = DB::table($tableName)
                    ->select([
                        'uuid',
                        'name',
                        'email',
                        'created_at',
                        'updated_at',
                        'email_verified_at',
                        DB::raw("'{$tableName}' as table_source") // 디버깅용
                    ])
                    ->whereNull('deleted_at'); // 삭제되지 않은 회원만

                // 검색 조건 적용
                if (!empty($searchParams['email'])) {
                    $query->where('email', 'like', '%' . $searchParams['email'] . '%');
                }

                if (!empty($searchParams['name'])) {
                    $query->where('name', 'like', '%' . $searchParams['name'] . '%');
                }

                if (!empty($searchParams['uuid'])) {
                    $query->where('uuid', $searchParams['uuid']);
                }

                if ($searchParams['verified_only']) {
                    $query->whereNotNull('email_verified_at');
                }

                // 정렬 적용
                $query->orderBy($searchParams['sort'], $searchParams['order']);

                // 결과 제한
                $query->limit($currentLimit);

                $results = $query->get();

                // 검색 결과를 전체 컬렉션에 추가
                foreach ($results as $user) {
                    if ($users->count() >= $searchParams['limit']) break;

                    $users->push([
                        'uuid' => $user->uuid,
                        'name' => $user->name,
                        'email' => $user->email,
                        'created_at' => $user->created_at,
                        'updated_at' => $user->updated_at,
                        'email_verified' => !is_null($user->email_verified_at),
                        'email_verified_at' => $user->email_verified_at,
                        'table_source' => $user->table_source,
                        // 검색 매칭 점수 계산 (정확도 순 정렬용)
                        'match_score' => $this->calculateMatchScore($user, $searchParams)
                    ]);
                }

                $currentLimit = $searchParams['limit'] - $users->count();
            }

            // 검색 정확도로 재정렬 (가장 정확한 매치 우선)
            return $users->sortBy('match_score')->values();

        } catch (\Exception $e) {
            Log::error('Sharded user search error: ' . $e->getMessage(), [
                'search_params' => $searchParams,
                'error' => $e->getMessage()
            ]);

            return collect(); // 빈 컬렉션 반환
        }
    }

    /**
     * 검색 매칭 점수 계산
     *
     * 낮은 점수일수록 더 정확한 매치
     */
    private function calculateMatchScore($user, $searchParams)
    {
        $score = 0;

        // 이메일 매칭 점수
        if (!empty($searchParams['email'])) {
            $email = strtolower($user->email);
            $searchEmail = strtolower($searchParams['email']);

            if ($email === $searchEmail) {
                $score += 0; // 정확 일치
            } elseif (str_starts_with($email, $searchEmail)) {
                $score += 1; // 시작 일치
            } else {
                $score += 2; // 포함 일치
            }
        }

        // 이름 매칭 점수
        if (!empty($searchParams['name'])) {
            $name = strtolower($user->name ?? '');
            $searchName = strtolower($searchParams['name']);

            if ($name === $searchName) {
                $score += 0; // 정확 일치
            } elseif (str_starts_with($name, $searchName)) {
                $score += 1; // 시작 일치
            } else {
                $score += 2; // 포함 일치
            }
        }

        // UUID는 정확 일치만 가능하므로 점수 변화 없음

        return $score;
    }

    /**
     * 샤딩된 사용자 테이블 목록 조회
     *
     * SQLite와 MySQL 환경 모두 지원
     *
     * @return array
     */
    private function getShardedUserTables()
    {
        try {
            $tableNames = [];
            $databaseDriver = DB::getDriverName();

            if ($databaseDriver === 'sqlite') {
                // SQLite: sqlite_master 테이블에서 조회
                $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name LIKE 'users_%'");
                foreach ($tables as $table) {
                    $tableName = $table->name;
                    if (preg_match('/^users_\d{3}$/', $tableName)) {
                        $tableNames[] = $tableName;
                    }
                }
            } else {
                // MySQL: SHOW TABLES 사용
                $tables = DB::select("SHOW TABLES LIKE 'users_%'");
                foreach ($tables as $table) {
                    $tableName = array_values((array)$table)[0];
                    if (preg_match('/^users_\d{3}$/', $tableName)) {
                        $tableNames[] = $tableName;
                    }
                }
            }

            // 테이블 번호순으로 정렬
            sort($tableNames);

            Log::debug("Found sharded user tables", [
                'database_driver' => $databaseDriver,
                'tables' => $tableNames,
                'count' => count($tableNames)
            ]);

            // 테이블이 하나도 없으면 fallback 사용
            if (empty($tableNames)) {
                Log::warning("No sharded tables found, using fallback tables");
                return ['users_001', 'users_002', 'users_003'];
            }

            return $tableNames;

        } catch (\Exception $e) {
            Log::error('Failed to get sharded user tables: ' . $e->getMessage(), [
                'driver' => DB::getDriverName(),
                'error' => $e->getMessage()
            ]);

            // 기본 테이블들 반환 (fallback)
            return ['users_001', 'users_002', 'users_003'];
        }
    }
}