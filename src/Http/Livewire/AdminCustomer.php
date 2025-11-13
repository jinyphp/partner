<?php

namespace Jiny\Partner\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Jiny\Partner\Models\PartnerUser;

class AdminCustomer extends Component
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
    public $customerId = '';
    public $customerTable = '';

    // 컴포넌트 설정
    public $placeholder = '이메일 주소로 사용자 검색...';
    public $searchMinLength = 2;
    public $searchLimit = 20;

    // 관리자 전용 설정
    public $showPartnerStatus = true;
    public $filterAvailable = true; // 등록 가능한 사용자만 표시

    protected $listeners = ['clearCustomer'];

    protected $rules = [
        'searchQuery' => 'nullable|string|min:2|max:100',
    ];

    public function mount($value = null, $customerName = null, $customerEmail = null, $customerUuid = null, $customerShard = null, $customerId = null, $customerTable = null, $filterAvailable = true)
    {
        // 컴포넌트 고유 ID 생성
        $this->componentId = 'admin_customer_' . uniqid();

        // 설정 옵션
        $this->filterAvailable = $filterAvailable;

        // 초기값 설정 (수정 페이지에서 기존 데이터 표시용)
        if ($value) {
            $this->searchQuery = $value;
            $this->customerName = $customerName ?: $value;
            $this->customerEmail = $customerEmail ?: '';
            $this->customerUuid = $customerUuid ?: '';
            $this->customerShard = $customerShard ?: '';
            $this->customerId = $customerId ?: '';
            $this->customerTable = $customerTable ?: '';

            if ($this->customerEmail || $this->customerUuid) {
                $this->selectedCustomer = [
                    'id' => $this->customerId,
                    'name' => $this->customerName,
                    'email' => $this->customerEmail,
                    'uuid' => $this->customerUuid,
                    'table_source' => $this->customerShard,
                    'user_table' => $this->customerTable
                ];
            }
        }
    }

    public function updatedSearchQuery()
    {
        Log::info('AdminCustomer search query updated', [
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
        Log::info('AdminCustomer searchCustomers called', [
            'query' => $this->searchQuery,
            'filter_available' => $this->filterAvailable
        ]);

        if (strlen($this->searchQuery) < $this->searchMinLength) {
            Log::info('Search query too short, returning');
            return;
        }

        $this->isLoading = true;
        $this->showDropdown = true;

        try {
            Log::info('Starting admin performShardedUserSearch');

            $results = $this->performAdminShardedUserSearch([
                'query' => $this->searchQuery,
                'limit' => $this->searchLimit
            ]);

            $this->customers = $results['users'];

            Log::info('AdminCustomer search completed', [
                'query' => $this->searchQuery,
                'results_count' => count($this->customers),
                'total_found' => $results['total_found'],
                'available_count' => $results['available_count'],
                'already_registered' => $results['already_registered']
            ]);

        } catch (\Exception $e) {
            Log::error('AdminCustomer search error: ' . $e->getMessage(), [
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
        $this->customerId = $customerData['id'] ?? '';
        $this->customerTable = $customerData['user_table'] ?? '';

        $this->showDropdown = false;
        $this->customers = [];

        // JavaScript 이벤트 발생
        $this->dispatch('admin-customer-selected', [
            'customer' => $this->selectedCustomer
        ]);

        Log::info('AdminCustomer selected', [
            'customer' => $this->selectedCustomer
        ]);
    }

    public function selectCustomerByIndex($index)
    {
        Log::info('AdminCustomer selectCustomerByIndex called', [
            'index' => $index,
            'componentId' => $this->componentId
        ]);

        if (isset($this->customers[$index])) {
            $customerData = $this->customers[$index];

            Log::info('AdminCustomer data from index', [
                'customer' => $customerData
            ]);

            $this->selectCustomer($customerData);

            // JavaScript로 직접 모달 닫기
            $this->dispatch('close-modal-js', [
                'modalId' => "customerSearchModal_{$this->componentId}"
            ]);
        } else {
            Log::error('AdminCustomer not found at index', ['index' => $index]);
        }
    }

    public function clearCustomer()
    {
        $this->selectedCustomer = null;
        $this->searchQuery = '';
        $this->customerName = '';
        $this->customerEmail = '';
        $this->customerUuid = '';
        $this->customerShard = '';
        $this->customerId = '';
        $this->customerTable = '';
        $this->resetSearch();

        $this->dispatch('admin-customer-cleared');
    }

    public function resetSearch()
    {
        $this->customers = [];
        $this->showDropdown = false;
        $this->isLoading = false;
    }

    /**
     * 관리자용 샤딩된 사용자 테이블에서 통합 검색
     */
    private function performAdminShardedUserSearch($searchParams)
    {
        $users = collect();
        $query = $searchParams['query'];
        $limit = $searchParams['limit'];

        $totalFound = 0;
        $alreadyRegistered = 0;
        $deletedRegistered = 0;
        $availableCount = 0;

        Log::info('AdminCustomer PerformShardedUserSearch started', [
            'query' => $query,
            'limit' => $limit
        ]);

        try {
            // 샤딩된 테이블 목록 가져오기
            $shardedTables = $this->getShardedUserTables();

            Log::info('AdminCustomer Sharded tables found', [
                'tables' => $shardedTables
            ]);

            foreach ($shardedTables as $tableName) {
                if ($users->count() >= $limit) break;

                Log::info("AdminCustomer Searching in table: {$tableName}");

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
                $dbQuery->where(function($q) use ($query) {
                    $q->where('email', 'like', '%' . $query . '%')
                      ->orWhere('name', 'like', '%' . $query . '%');
                });

                // 정렬 및 제한
                $dbQuery->orderBy('created_at', 'desc')
                       ->limit($limit - $users->count());

                $results = $dbQuery->get();
                $totalFound += $results->count();

                Log::info("AdminCustomer Search results from {$tableName}", [
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
                        'table_source' => $user->user_table,
                        'user_table' => $user->user_table,
                        'shard_number' => $user->shard_number,
                        'partner_status' => $partnerStatus['status'],
                        'partner_info' => $partnerStatus['info'],
                        'is_available' => $partnerStatus['is_available'],
                        'match_score' => $this->calculateMatchScore($user, $query)
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

                    // 필터링 옵션에 따라 추가 여부 결정
                    if (!$this->filterAvailable || $partnerStatus['is_available']) {
                        $users->push($userData);
                    }
                }
            }

            // 검색 정확도로 정렬
            $sortedUsers = $users->sortBy('match_score')->values()->take($limit)->toArray();

            return [
                'users' => $sortedUsers,
                'total_found' => $totalFound,
                'available_count' => $availableCount,
                'already_registered' => $alreadyRegistered,
                'deleted_registered' => $deletedRegistered
            ];

        } catch (\Exception $e) {
            Log::error('AdminCustomer Sharded user search error: ' . $e->getMessage(), [
                'query' => $query,
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
                'info' => $partner,
                'is_available' => true // 삭제된 경우 재등록 가능
            ];
        }

        $status = $partner->status ?? 'unknown';
        $isAvailable = in_array($status, ['pending', 'inactive']); // 대기, 비활성 상태만 등록 가능

        return [
            'status' => $status,
            'info' => $partner,
            'is_available' => !in_array($status, ['active'])
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
            Log::error('AdminCustomer Failed to get sharded user tables: ' . $e->getMessage());
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

    public function render()
    {
        return view('jiny-partner::livewire.admin-customer');
    }
}