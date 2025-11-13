<?php

namespace Jiny\Partner\Http\Controllers\Home\Sales;

use Jiny\Partner\Http\Controllers\PartnerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CustomerSearchController extends PartnerController
{
    /**
     * 고객(회원) 검색 API
     *
     * 매출 등록시 고객명을 직접 입력하거나,
     * 기존 회원을 이메일로 검색하여 선택할 수 있는 기능 제공
     *
     * 검색 조건:
     * - 이메일 부분 일치 검색
     * - 샤딩된 users_xxx 테이블 전체 검색
     * - 활성화된 회원만 검색 (deleted_at IS NULL)
     */
    public function __invoke(Request $request)
    {
        try {
            // ========================================
            // Step 1: 파트너 인증 확인
            // ========================================
            $authResult = $this->authenticateAndGetPartner($request, 'customer_search');
            if (!$authResult['success']) {
                return response()->json([
                    'success' => false,
                    'code' => 401,
                    'message' => '파트너 인증이 필요합니다.'
                ], 401);
            }

            $user = $authResult['user'];
            $partner = $authResult['partner'];

            // ========================================
            // Step 2: 검색 파라미터 검증
            // ========================================
            $request->validate([
                'email' => 'required|string|min:2|max:100',
                'limit' => 'nullable|integer|min:1|max:20'
            ]);

            $email = $request->input('email');
            $limit = $request->input('limit', 10);

            Log::info("Customer search request", [
                'search_email' => $email,
                'limit' => $limit,
                'partner_id' => $partner->id,
                'user_uuid' => $user->uuid
            ]);

            // ========================================
            // Step 3: 샤딩된 사용자 테이블에서 검색
            // ========================================
            $customers = $this->searchCustomersInShardedTables($email, $limit);

            Log::info("Customer search results", [
                'search_email' => $email,
                'found_count' => $customers->count(),
                'partner_id' => $partner->id
            ]);

            // ========================================
            // Step 4: 검색 결과 응답
            // ========================================
            return response()->json([
                'success' => true,
                'code' => 200,
                'message' => "'{$email}'로 검색한 결과입니다.",
                'data' => [
                    'customers' => $customers,
                    'search_query' => $email,
                    'result_count' => $customers->count(),
                    'limit' => $limit
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
            Log::error('Customer search error: ' . $e->getMessage(), [
                'search_email' => $request->input('email', 'unknown'),
                'partner_id' => $partner->id ?? 'unknown',
                'user_uuid' => $user->uuid ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'code' => 500,
                'message' => '고객 검색 중 오류가 발생했습니다.'
            ], 500);
        }
    }

    /**
     * 샤딩된 사용자 테이블에서 이메일로 검색
     *
     * HomeController의 샤딩 로직을 활용하여 모든 users_xxx 테이블에서 검색
     *
     * @param string $email 검색할 이메일 (부분 일치)
     * @param int $limit 검색 결과 제한 개수
     * @return \Illuminate\Support\Collection
     */
    private function searchCustomersInShardedTables($email, $limit)
    {
        $customers = collect();
        $currentLimit = $limit;

        try {
            // 샤딩된 테이블 목록 가져오기 (HomeController 로직 참조)
            $shardedTables = $this->getShardedTableList();

            foreach ($shardedTables as $tableName) {
                if ($currentLimit <= 0) break;

                // 각 샤딩 테이블에서 이메일 검색
                $results = DB::table($tableName)
                    ->select([
                        'uuid',
                        'name',
                        'email',
                        'created_at',
                        'email_verified_at'
                    ])
                    ->where('email', 'like', '%' . $email . '%')
                    ->whereNull('deleted_at') // 삭제되지 않은 회원만
                    ->orderBy('created_at', 'desc')
                    ->limit($currentLimit)
                    ->get();

                // 검색 결과를 전체 컬렉션에 추가
                foreach ($results as $customer) {
                    if ($customers->count() >= $limit) break;

                    $customers->push([
                        'uuid' => $customer->uuid,
                        'name' => $customer->name,
                        'email' => $customer->email,
                        'created_at' => $customer->created_at,
                        'email_verified' => !is_null($customer->email_verified_at),
                        'table_source' => $tableName // 디버깅용
                    ]);
                }

                $currentLimit = $limit - $customers->count();
            }

            // 이메일 일치도로 정렬 (정확한 일치를 우선으로)
            return $customers->sortBy(function ($customer) use ($email) {
                // 정확히 일치하면 0, 시작으로 일치하면 1, 포함되면 2
                if ($customer['email'] === $email) {
                    return 0;
                } elseif (str_starts_with(strtolower($customer['email']), strtolower($email))) {
                    return 1;
                } else {
                    return 2;
                }
            })->values();

        } catch (\Exception $e) {
            Log::error('Sharded table search error: ' . $e->getMessage(), [
                'search_email' => $email,
                'error' => $e->getMessage()
            ]);

            return collect(); // 빈 컬렉션 반환
        }
    }

    /**
     * 샤딩된 사용자 테이블 목록 조회
     *
     * SQLite와 MySQL 환경 모두 지원하도록 실제 존재하는 users_xxx 테이블들을 동적으로 탐지
     *
     * @return array
     */
    private function getShardedTableList()
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

            Log::info("Found sharded user tables", [
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
            Log::error('Failed to get sharded table list: ' . $e->getMessage(), [
                'driver' => DB::getDriverName(),
                'error' => $e->getMessage()
            ]);

            // 기본 테이블들 반환 (fallback)
            return ['users_001', 'users_002', 'users_003'];
        }
    }
}