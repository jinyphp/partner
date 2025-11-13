<?php

namespace Jiny\Partner\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Customer extends Component
{
    // 공개 프로퍼티들
    public $searchQuery = '';
    public $selectedCustomer = null;
    public $customers = [];
    public $showDropdown = false;
    public $isLoading = false;

    // 컴포넌트 ID (Livewire에서 자동 생성)
    public $componentId;

    // 선택된 고객 정보
    public $customerName = '';
    public $customerEmail = '';
    public $customerUuid = '';
    public $customerShard = '';

    // 컴포넌트 설정
    public $placeholder = '고객 이름 또는 이메일을 입력하세요';
    public $searchMinLength = 2;
    public $searchLimit = 10;

    protected $listeners = ['clearCustomer'];

    protected $rules = [
        'searchQuery' => 'nullable|string|min:2|max:100',
    ];

    public function mount($value = null, $customerName = null, $customerEmail = null, $customerUuid = null, $customerShard = null)
    {
        // 컴포넌트 고유 ID 생성
        $this->componentId = 'customer_' . uniqid();

        // 초기값 설정 (수정 페이지에서 기존 데이터 표시용)
        if ($value) {
            $this->searchQuery = $value;
            $this->customerName = $customerName ?: $value;
            $this->customerEmail = $customerEmail ?: '';
            $this->customerUuid = $customerUuid ?: '';
            $this->customerShard = $customerShard ?: '';

            if ($this->customerEmail || $this->customerUuid) {
                $this->selectedCustomer = [
                    'name' => $this->customerName,
                    'email' => $this->customerEmail,
                    'uuid' => $this->customerUuid,
                    'table_source' => $this->customerShard
                ];
            }
        }
    }

    public function updatedSearchQuery()
    {
        Log::info('Livewire search query updated', [
            'query' => $this->searchQuery,
            'query_length' => strlen($this->searchQuery),
            'min_length' => $this->searchMinLength
        ]);


        if (strlen($this->searchQuery) >= $this->searchMinLength) {
            $this->searchCustomers();
        } else {
            $this->resetSearch();
        }
    }

    public function searchCustomers()
    {
        Log::info('Livewire searchCustomers called', [
            'query' => $this->searchQuery,
            'query_length' => strlen($this->searchQuery),
            'min_length' => $this->searchMinLength
        ]);

        //dump("a");
        if (strlen($this->searchQuery) < $this->searchMinLength) {
            Log::info('Search query too short, returning');
            return;
        }

        //dd("b");

        $this->isLoading = true;
        $this->showDropdown = true;

        try {
            Log::info('Starting performShardedUserSearch');

            $this->customers = $this->performShardedUserSearch([
                'query' => $this->searchQuery,
                'limit' => $this->searchLimit
            ]);

            Log::info('Livewire customer search completed', [
                'query' => $this->searchQuery,
                'results_count' => count($this->customers),
                'results' => $this->customers
            ]);

        } catch (\Exception $e) {
            Log::error('Livewire customer search error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            $this->customers = [];
            $this->dispatch('customer-search-error', [
                'message' => '검색 중 오류가 발생했습니다.'
            ]);
        }

        $this->isLoading = false;
    }

    public function selectCustomer($customerData)
    {
        $this->selectedCustomer = $customerData;
        $this->searchQuery = $customerData['name'];

        // Hidden 필드용 데이터 설정
        $this->customerName = $customerData['name'];
        $this->customerEmail = $customerData['email'] ?? '';
        $this->customerUuid = $customerData['uuid'] ?? '';
        $this->customerShard = $customerData['table_source'] ?? '';

        $this->showDropdown = false;
        $this->customers = [];

        // JavaScript 이벤트 발생
        $this->dispatch('customer-selected', [
            'customer' => $this->selectedCustomer
        ]);

        Log::info('Livewire customer selected', [
            'customer' => $this->selectedCustomer
        ]);
    }

    public function selectCustomerByIndex($index)
    {
        Log::info('selectCustomerByIndex called', [
            'index' => $index,
            'componentId' => $this->componentId
        ]);

        if (isset($this->customers[$index])) {
            $customerData = $this->customers[$index];

            Log::info('Customer data from index', [
                'customer' => $customerData
            ]);

            $this->selectCustomer($customerData);

            // JavaScript로 직접 모달 닫기
            $this->dispatch('close-modal-js', [
                'modalId' => "customerSearchModal_{$this->componentId}"
            ]);
        } else {
            Log::error('Customer not found at index', ['index' => $index]);
        }
    }

    public function selectCustomerFromModal($customerData)
    {
        Log::info('selectCustomerFromModal called', [
            'customer' => $customerData,
            'componentId' => $this->componentId
        ]);

        $this->selectCustomer($customerData);

        // JavaScript로 직접 모달 닫기
        $this->dispatch('close-modal-js', [
            'modalId' => "customerSearchModal_{$this->componentId}"
        ]);
    }

    public function clearCustomer()
    {
        $this->selectedCustomer = null;
        $this->searchQuery = '';
        $this->customerName = '';
        $this->customerEmail = '';
        $this->customerUuid = '';
        $this->customerShard = '';
        $this->resetSearch();

        $this->dispatch('customer-cleared');
    }

    public function resetSearch()
    {
        $this->customers = [];
        $this->showDropdown = false;
        $this->isLoading = false;
    }

    public function hideDropdown()
    {
        // 약간의 지연을 두어 클릭 이벤트가 먼저 처리되도록 함
        $this->dispatch('hide-dropdown-delayed');
    }

    /**
     * 샤딩된 사용자 테이블에서 통합 검색
     */
    private function performShardedUserSearch($searchParams)
    {
        $users = collect();
        $query = $searchParams['query'];
        $limit = $searchParams['limit'];

        Log::info('PerformShardedUserSearch started', [
            'query' => $query,
            'limit' => $limit
        ]);

        try {
            // 샤딩된 테이블 목록 가져오기
            $shardedTables = $this->getShardedUserTables();
            //dd($shardedTables);

            Log::info('Sharded tables found', [
                'tables' => $shardedTables
            ]);

            foreach ($shardedTables as $tableName) {
                if ($users->count() >= $limit) break;

                Log::info("Searching in table: {$tableName}");

                // 각 샤딩 테이블에서 검색
                $dbQuery = DB::table($tableName)
                    ->select([
                        'uuid',
                        'name',
                        'email',
                        'created_at',
                        'updated_at',
                        'email_verified_at',
                        DB::raw("'{$tableName}' as table_source")
                    ])
                    ->whereNull('deleted_at'); // 삭제되지 않은 회원만

                // 이메일과 이름 모두에서 검색
                $dbQuery->where(function($q) use ($query) {
                    $q->where('email', 'like', '%' . $query . '%')
                      ->orWhere('name', 'like', '%' . $query . '%');
                });

                // 실제 실행할 쿼리를 로그로 확인
                $sql = $dbQuery->toSql();
                Log::info("Generated SQL: {$sql} with bindings: " . json_encode($dbQuery->getBindings()));

                // 정렬 및 제한
                $dbQuery->orderBy('created_at', 'desc')
                       ->limit($limit - $users->count());

                $results = $dbQuery->get();

                Log::info("Search results from {$tableName}", [
                    'count' => $results->count(),
                    'results' => $results->toArray()
                ]);

                // 검색 결과를 전체 컬렉션에 추가
                foreach ($results as $user) {
                    if ($users->count() >= $limit) break;

                    $users->push([
                        'uuid' => $user->uuid,
                        'name' => $user->name,
                        'email' => $user->email,
                        'created_at' => $user->created_at,
                        'updated_at' => $user->updated_at,
                        'email_verified' => !is_null($user->email_verified_at),
                        'email_verified_at' => $user->email_verified_at,
                        'table_source' => $user->table_source,
                        'match_score' => $this->calculateMatchScore($user, $query)
                    ]);
                }
            }

            // 검색 정확도로 정렬
            return $users->sortBy('match_score')->values()->take($limit)->toArray();

        } catch (\Exception $e) {
            Log::error('Sharded user search error: ' . $e->getMessage(), [
                'query' => $query,
                'error' => $e->getMessage()
            ]);

            return [];
        }
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

    public function render()
    {
        return view('jiny-partner::livewire.customer');
    }
}
