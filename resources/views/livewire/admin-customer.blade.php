<div class="admin-customer-search-component">
    {{-- 사용자 검색 입력 필드 --}}
    <div class="mb-3">
        <label for="admin_customer_search_{{ $componentId }}" class="form-label">
            사용자 이메일 검색
            <span class="text-danger">*</span>
        </label>
        <div class="input-group">
            <input
                type="text"
                class="form-control @error('searchQuery') is-invalid @enderror"
                id="admin_customer_search_{{ $componentId }}"
                value="{{ $selectedCustomer ? $selectedCustomer['name'] : ($searchQuery ?: '') }}"
                placeholder="{{ $selectedCustomer ? $selectedCustomer['name'] : $placeholder }}"
                readonly
            >
            <button
                type="button"
                class="btn btn-outline-primary"
                data-bs-toggle="modal"
                data-bs-target="#customerSearchModal_{{ $componentId }}"
            >
                <i class="fe fe-search me-1"></i>사용자 검색
            </button>
            @if($selectedCustomer)
                <button
                    type="button"
                    class="btn btn-outline-danger"
                    wire:click="clearCustomer"
                >
                    <i class="fe fe-x"></i>
                </button>
            @endif
        </div>

        @error('searchQuery')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror

        {{-- 도움말 텍스트 --}}
        <div class="form-text">
            사용자 검색 버튼을 클릭하여 샤딩된 테이블에서 사용자를 찾아보세요.
        </div>
    </div>

    {{-- 선택된 사용자 정보 표시 --}}
    @if($selectedCustomer)
        <div class="alert alert-success d-flex align-items-center">
            <div class="avatar avatar-sm me-3">
                <span class="avatar-initial bg-success text-white rounded-circle">
                    {{ substr($selectedCustomer['name'] ?? 'U', 0, 1) }}
                </span>
            </div>
            <div class="flex-grow-1">
                <div class="fw-semibold">
                    <i class="fe fe-check-circle me-1"></i>
                    선택된 사용자: {{ $selectedCustomer['name'] }}
                </div>
                @if($selectedCustomer['email'])
                    <div class="small text-muted">
                        {{ $selectedCustomer['email'] }}
                        @if($selectedCustomer['email_verified'] ?? false)
                            <span class="badge bg-success ms-1">인증됨</span>
                        @else
                            <span class="badge bg-secondary ms-1">미인증</span>
                        @endif
                        @if(isset($selectedCustomer['user_table']))
                            <span class="badge bg-info ms-1">{{ $selectedCustomer['user_table'] }}</span>
                        @endif
                        @if(isset($selectedCustomer['shard_number']) && $selectedCustomer['shard_number'] > 0)
                            <span class="badge bg-secondary ms-1">샤드 {{ $selectedCustomer['shard_number'] }}</span>
                        @endif
                    </div>
                @endif
                @if(isset($selectedCustomer['partner_status']) && $selectedCustomer['partner_status'] !== 'not_registered')
                    <div class="small">
                        <span class="text-warning">
                            <i class="fe fe-alert-triangle me-1"></i>
                            @if($selectedCustomer['partner_status'] === 'active')
                                이미 활성 파트너로 등록됨
                            @elseif($selectedCustomer['partner_status'] === 'deleted')
                                이전에 등록되었다가 삭제됨 (재등록 가능)
                            @elseif($selectedCustomer['partner_status'] === 'pending')
                                파트너 가입 대기 중 (덮어쓰기 가능)
                            @elseif($selectedCustomer['partner_status'] === 'inactive')
                                비활성 파트너 (덮어쓰기 가능)
                            @else
                                파트너 상태: {{ $selectedCustomer['partner_status'] }}
                            @endif
                        </span>
                    </div>
                @endif
            </div>
            <button type="button" class="btn btn-outline-danger btn-sm" wire:click="clearCustomer">
                <i class="fe fe-x"></i>
            </button>
        </div>
    @endif

    {{-- Bootstrap 5 모달 팝업 --}}
    <div class="modal fade" id="customerSearchModal_{{ $componentId }}" tabindex="-1" aria-labelledby="customerSearchModalLabel_{{ $componentId }}" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="customerSearchModalLabel_{{ $componentId }}">
                        <i class="fe fe-search me-2"></i>사용자 검색 (관리자용)
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- 검색 입력 필드 --}}
                    <div class="mb-4">
                        <label for="modalSearch_{{ $componentId }}" class="form-label">이메일 또는 이름으로 검색</label>
                        <div class="input-group">
                            <input
                                type="text"
                                class="form-control"
                                id="modalSearch_{{ $componentId }}"
                                wire:model.live.debounce.300ms="searchQuery"
                                placeholder="이메일 또는 이름을 입력하세요 (최소 2자)"
                                autocomplete="off"
                            >
                            <button
                                type="button"
                                class="btn btn-primary"
                                wire:click="searchCustomers"
                                @if($isLoading) disabled @endif
                            >
                                @if($isLoading)
                                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                    검색중...
                                @else
                                    <i class="fe fe-search me-1"></i>검색
                                @endif
                            </button>
                        </div>
                        <div class="form-text">
                            이메일의 일부만 입력해도 검색됩니다. 샤딩된 users_xxx 테이블에서 검색합니다.
                        </div>
                    </div>

                    {{-- 검색 결과 영역 --}}
                    <div class="search-results-container" style="min-height: 400px; max-height: 500px; overflow-y: auto;">
                        @if($searchQuery && strlen($searchQuery) >= $searchMinLength)
                            @if($isLoading)
                                {{-- 로딩 상태 --}}
                                <div class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">검색 중...</span>
                                    </div>
                                    <div class="mt-3 text-muted">샤딩된 테이블에서 검색 중입니다...</div>
                                </div>
                            @elseif(!empty($customers))
                                {{-- 검색 결과 있음 --}}
                                <div class="alert alert-info d-flex align-items-center mb-3">
                                    <i class="fe fe-info me-2"></i>
                                    <div>
                                        총 <strong>{{ count($customers) }}</strong>명의 사용자를 찾았습니다.
                                        @if($filterAvailable)
                                            <span class="text-muted">(등록 가능한 사용자만 표시)</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="list-group">
                                    @foreach($customers as $index => $customer)
                                        <div class="list-group-item list-group-item-action d-flex align-items-center py-3 border rounded mb-2 cursor-pointer hover-shadow @if(!($customer['is_available'] ?? true)) bg-light @endif"
                                             wire:click="selectCustomerByIndex({{ $index }})"
                                             style="cursor: pointer;">
                                            {{-- 아바타 --}}
                                            <div class="avatar avatar-lg me-3">
                                                <span class="avatar-initial
                                                    @if($customer['partner_status'] === 'active') bg-danger
                                                    @elseif($customer['partner_status'] === 'pending') bg-warning
                                                    @elseif($customer['partner_status'] === 'deleted') bg-secondary
                                                    @else bg-primary @endif
                                                    text-white rounded-circle fs-5 fw-bold">
                                                    {{ substr($customer['name'] ?? 'U', 0, 1) }}
                                                </span>
                                            </div>

                                            {{-- 사용자 정보 --}}
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center justify-content-between mb-1">
                                                    <div>
                                                        <h6 class="mb-0 text-dark fw-semibold">{{ $customer['name'] }}</h6>
                                                        @if($customer['email_verified'] ?? false)
                                                            <span class="badge bg-success ms-2">
                                                                <i class="fe fe-check me-1"></i>인증됨
                                                            </span>
                                                        @else
                                                            <span class="badge bg-secondary ms-2">미인증</span>
                                                        @endif
                                                    </div>

                                                    {{-- 파트너 상태 배지 --}}
                                                    <div>
                                                        @if($customer['partner_status'] === 'active')
                                                            <span class="badge bg-danger">활성 파트너</span>
                                                        @elseif($customer['partner_status'] === 'pending')
                                                            <span class="badge bg-warning">파트너 대기</span>
                                                        @elseif($customer['partner_status'] === 'deleted')
                                                            <span class="badge bg-secondary">파트너 삭제됨</span>
                                                        @elseif($customer['partner_status'] === 'inactive')
                                                            <span class="badge bg-warning">파트너 비활성</span>
                                                        @else
                                                            <span class="badge bg-success">등록 가능</span>
                                                        @endif

                                                        @if(!($customer['is_available'] ?? true))
                                                            <span class="badge bg-danger ms-1">등록 불가</span>
                                                        @endif
                                                    </div>
                                                </div>

                                                @if($customer['email'])
                                                    <div class="text-muted mb-1">
                                                        <i class="fe fe-mail me-1"></i>{{ $customer['email'] }}
                                                    </div>
                                                @endif

                                                <div class="row text-muted small">
                                                    <div class="col-md-6">
                                                        <i class="fe fe-calendar me-1"></i>
                                                        가입일: {{ \Carbon\Carbon::parse($customer['created_at'])->format('Y-m-d') }}
                                                    </div>
                                                    <div class="col-md-6">
                                                        @if(isset($customer['user_table']))
                                                            <i class="fe fe-database me-1"></i>{{ $customer['user_table'] }}
                                                            @if(isset($customer['shard_number']) && $customer['shard_number'] > 0)
                                                                <span class="ms-1">(샤드 {{ $customer['shard_number'] }})</span>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </div>

                                                @if(isset($customer['partner_info']) && $customer['partner_info'])
                                                    <div class="mt-2 small text-warning">
                                                        <i class="fe fe-info me-1"></i>
                                                        @if($customer['partner_status'] === 'active')
                                                            이미 활성 파트너로 등록됨 - 덮어쓰기 주의
                                                        @elseif($customer['partner_status'] === 'deleted')
                                                            이전에 등록되었다가 삭제됨 - 재등록 가능
                                                        @elseif($customer['partner_status'] === 'pending')
                                                            파트너 가입 대기 중 - 덮어쓰기 가능
                                                        @else
                                                            파트너 상태: {{ $customer['partner_status'] }}
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- 선택 아이콘 --}}
                                            <div class="text-end">
                                                @if($customer['uuid'] ?? null)
                                                    <div class="small text-muted mb-1">
                                                        <code>{{ substr($customer['uuid'], 0, 8) }}...</code>
                                                    </div>
                                                @endif
                                                <i class="fe fe-arrow-right text-primary fs-5"></i>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                {{-- 검색 결과 없음 --}}
                                <div class="text-center py-5">
                                    <div class="mb-3">
                                        <i class="fe fe-search text-muted" style="font-size: 3rem;"></i>
                                    </div>
                                    <h6 class="text-muted">검색 결과가 없습니다</h6>
                                    <p class="text-muted small">
                                        다른 키워드로 검색해보세요.<br>
                                        이메일의 일부분이나 이름을 입력하면 됩니다.
                                    </p>
                                </div>
                            @endif
                        @else
                            {{-- 초기 상태 --}}
                            <div class="text-center py-5">
                                <div class="mb-3">
                                    <i class="fe fe-users text-muted" style="font-size: 3rem;"></i>
                                </div>
                                <h6 class="text-muted">사용자를 검색해보세요</h6>
                                <p class="text-muted small">
                                    이메일 또는 이름을 입력하고 검색 버튼을 클릭하세요.<br>
                                    샤딩된 users_xxx 테이블에서 검색하며, 최소 2자 이상 입력해야 합니다.
                                </p>
                                @if($filterAvailable)
                                    <div class="alert alert-info text-start mt-3">
                                        <i class="fe fe-info-circle me-2"></i>
                                        <strong>검색 필터 설정:</strong> 파트너 등록이 가능한 사용자만 표시됩니다.
                                        <ul class="mt-2 mb-0 small text-start">
                                            <li>미등록 사용자 - 새로 등록 가능</li>
                                            <li>삭제된 파트너 - 재등록 가능</li>
                                            <li>대기/비활성 파트너 - 덮어쓰기 가능</li>
                                            <li class="text-muted"><s>활성 파트너는 표시되지 않습니다</s></li>
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fe fe-x me-1"></i>취소
                    </button>
                    @if($selectedCustomer)
                        <button type="button" class="btn btn-danger" wire:click="clearCustomer" data-bs-dismiss="modal">
                            <i class="fe fe-trash-2 me-1"></i>선택 해제
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Hidden Input 필드들 --}}
    <input type="hidden" name="user_id" value="{{ $customerId }}">
    <input type="hidden" name="user_table" value="{{ $customerTable }}">
    <input type="hidden" name="user_uuid" value="{{ $customerUuid }}">
    <input type="hidden" name="shard_number" value="{{ $customerShard }}">
    <input type="hidden" name="email" value="{{ $customerEmail }}">
    <input type="hidden" name="name" value="{{ $customerName }}">

    {{-- JavaScript 이벤트 처리 --}}
    <script>
        document.addEventListener('livewire:init', function () {
            // Livewire 3.x 스타일 이벤트 리스너
            Livewire.on('admin-customer-selected', (event) => {
                console.log('Admin customer selected:', event.customer);

                // 폼 필드 업데이트
                const customer = event.customer;
                document.getElementById('search_email').value = customer.email || '';
                document.getElementById('user_id').value = customer.id || '';
                document.getElementById('user_table').value = customer.user_table || '';
                document.getElementById('user_uuid').value = customer.uuid || '';
                document.getElementById('shard_number').value = customer.shard_number || 0;
                document.getElementById('email').value = customer.email || '';
                document.getElementById('name').value = customer.name || '';

                // 전역 알림 표시 (선택적)
                if (typeof showAlert === 'function') {
                    showAlert('success', `${customer.name} 사용자를 선택했습니다.`);
                }

                // 커스텀 이벤트 발생 (다른 컴포넌트에서 사용 가능)
                document.dispatchEvent(new CustomEvent('admin-customer-selected', {
                    detail: { customer: customer }
                }));
            });

            Livewire.on('admin-customer-cleared', (event) => {
                console.log('Admin customer selection cleared');

                // 폼 필드 초기화
                document.getElementById('search_email').value = '';
                document.getElementById('user_id').value = '';
                document.getElementById('user_table').value = '';
                document.getElementById('user_uuid').value = '';
                document.getElementById('shard_number').value = '';
                document.getElementById('email').value = '';
                document.getElementById('name').value = '';

                // 커스텀 이벤트 발생
                document.dispatchEvent(new CustomEvent('admin-customer-cleared'));
            });

            Livewire.on('customer-search-error', (event) => {
                console.error('Admin customer search error:', event.message);

                if (typeof showAlert === 'function') {
                    showAlert('danger', event.message);
                } else {
                    alert('검색 오류: ' + event.message);
                }
            });

            Livewire.on('close-modal-js', (event) => {
                console.log('Close admin modal JS event received:', event);
                const modalId = event.modalId;
                const modal = document.getElementById(modalId);

                if (modal) {
                    // 기존 인스턴스 확인
                    let bsModal = bootstrap.Modal.getInstance(modal);

                    if (!bsModal) {
                        // 인스턴스가 없으면 새로 생성
                        bsModal = new bootstrap.Modal(modal);
                    }

                    console.log('Bootstrap modal instance:', bsModal);
                    bsModal.hide();
                } else {
                    console.error('Modal not found with ID:', modalId);
                }
            });
        });
    </script>

    {{-- 컴포넌트별 스타일 --}}
    <style>
        .admin-customer-search-component .cursor-pointer {
            cursor: pointer;
        }

        .admin-customer-search-component .list-group-item:hover {
            background-color: var(--bs-light) !important;
            transform: translateY(-1px);
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.1);
            transition: all 0.15s ease-in-out;
        }

        .admin-customer-search-component .hover-shadow:hover {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.1);
            transform: translateY(-1px);
            transition: all 0.15s ease-in-out;
        }

        .admin-customer-search-component .avatar-initial {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.125rem;
        }

        .admin-customer-search-component .avatar-sm .avatar-initial {
            width: 32px;
            height: 32px;
            font-size: 0.875rem;
        }

        .admin-customer-search-component .avatar-lg .avatar-initial {
            width: 56px;
            height: 56px;
            font-size: 1.25rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* 등록 불가능한 사용자의 시각적 구분 */
        .admin-customer-search-component .bg-light .cursor-pointer {
            opacity: 0.7;
        }

        .admin-customer-search-component .bg-light:hover {
            background-color: var(--bs-gray-200) !important;
        }
    </style>
</div>