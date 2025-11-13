<div class="customer-search-component">
    {{-- 고객명 입력 필드 --}}
    <div class="mb-3">
        <label for="customer_search_{{ $componentId }}" class="form-label">고객명</label>
        <div class="input-group">
            <input
                type="text"
                class="form-control @error('searchQuery') is-invalid @enderror"
                id="customer_search_{{ $componentId }}"
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
                <i class="fe fe-search me-1"></i>회원 검색
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
            회원 검색 버튼을 클릭하여 등록된 회원을 찾아보세요.
        </div>
    </div>

    {{-- 선택된 고객 정보 표시 --}}
    @if($selectedCustomer)
        <div class="card bg-light border-success mb-3">
            <div class="card-body py-2">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-sm me-3">
                        <span class="avatar-initial bg-success text-white rounded-circle">
                            {{ substr($selectedCustomer['name'] ?? 'U', 0, 1) }}
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold text-success">
                            <i class="fe fe-check-circle me-1"></i>
                            선택된 고객: {{ $selectedCustomer['name'] }}
                        </div>
                        @if($selectedCustomer['email'])
                            <div class="small text-muted">
                                {{ $selectedCustomer['email'] }}
                                @if($selectedCustomer['email_verified'])
                                    <span class="badge bg-success ms-1">인증됨</span>
                                @else
                                    <span class="badge bg-secondary ms-1">미인증</span>
                                @endif
                            </div>
                        @endif
                    </div>
                    <button type="button" class="btn btn-outline-danger btn-sm" wire:click="clearCustomer">
                        <i class="fe fe-x"></i>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Bootstrap 5 모달 팝업 --}}
    <div class="modal fade" id="customerSearchModal_{{ $componentId }}" tabindex="-1" aria-labelledby="customerSearchModalLabel_{{ $componentId }}" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="customerSearchModalLabel_{{ $componentId }}">
                        <i class="fe fe-search me-2"></i>회원 검색
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
                            이메일의 일부만 입력해도 검색됩니다. (예: "test029")
                        </div>
                    </div>

                    {{-- 검색 결과 영역 --}}
                    <div class="search-results-container" style="min-height: 300px; max-height: 400px; overflow-y: auto;">
                        @if($searchQuery && strlen($searchQuery) >= $searchMinLength)
                            @if($isLoading)
                                {{-- 로딩 상태 --}}
                                <div class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">검색 중...</span>
                                    </div>
                                    <div class="mt-3 text-muted">검색 중입니다...</div>
                                </div>
                            @elseif(!empty($customers))
                                {{-- 검색 결과 있음 --}}
                                <div class="alert alert-info d-flex align-items-center mb-3">
                                    <i class="fe fe-info me-2"></i>
                                    <div>
                                        총 <strong>{{ count($customers) }}</strong>명의 회원을 찾았습니다.
                                    </div>
                                </div>

                                <div class="list-group">
                                    @foreach($customers as $index => $customer)
                                        <div class="list-group-item list-group-item-action d-flex align-items-center py-3 border rounded mb-2 cursor-pointer hover-shadow"
                                             wire:click="selectCustomerByIndex({{ $index }})"
                                             style="cursor: pointer;">
                                            {{-- 아바타 --}}
                                            <div class="avatar avatar-md me-3">
                                                <span class="avatar-initial bg-primary text-white rounded-circle fs-5 fw-bold">
                                                    {{ substr($customer['name'] ?? 'U', 0, 1) }}
                                                </span>
                                            </div>

                                            {{-- 고객 정보 --}}
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center mb-1">
                                                    <h6 class="mb-0 text-dark fw-semibold">{{ $customer['name'] }}</h6>
                                                    @if($customer['email_verified'])
                                                        <span class="badge bg-success ms-2">
                                                            <i class="fe fe-check me-1"></i>인증됨
                                                        </span>
                                                    @else
                                                        <span class="badge bg-secondary ms-2">미인증</span>
                                                    @endif
                                                </div>

                                                @if($customer['email'])
                                                    <div class="text-muted mb-1">
                                                        <i class="fe fe-mail me-1"></i>{{ $customer['email'] }}
                                                    </div>
                                                @endif

                                                <div class="small text-muted">
                                                    <i class="fe fe-calendar me-1"></i>
                                                    가입일: {{ \Carbon\Carbon::parse($customer['created_at'])->format('Y년 m월 d일') }}
                                                    @if(isset($customer['table_source']))
                                                        <span class="ms-2">
                                                            <i class="fe fe-database me-1"></i>{{ $customer['table_source'] }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>

                                            {{-- UUID 및 선택 아이콘 --}}
                                            <div class="text-end">
                                                @if($customer['uuid'])
                                                    <div class="small text-muted mb-1">
                                                        <code>{{ substr($customer['uuid'], 0, 8) }}...</code>
                                                    </div>
                                                @endif
                                                <i class="fe fe-arrow-right text-primary"></i>
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
                                <h6 class="text-muted">회원을 검색해보세요</h6>
                                <p class="text-muted small">
                                    이메일 또는 이름을 입력하고 검색 버튼을 클릭하세요.<br>
                                    최소 2자 이상 입력해야 검색할 수 있습니다.
                                </p>
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
    <input type="hidden" name="customer_name" value="{{ $customerName }}">
    <input type="hidden" name="customer_email" value="{{ $customerEmail }}">
    <input type="hidden" name="customer_uuid" value="{{ $customerUuid }}">
    <input type="hidden" name="customer_shard" value="{{ $customerShard }}">

    {{-- JavaScript 이벤트 처리 --}}
    <script>
        document.addEventListener('livewire:init', function () {
            // Livewire 3.x 스타일 이벤트 리스너
            Livewire.on('hide-dropdown-delayed', (event) => {
                setTimeout(() => {
                    @this.set('showDropdown', false);
                }, 150);
            });

            Livewire.on('customer-selected', (event) => {
                console.log('Customer selected:', event.customer);

                // 전역 알림 표시 (선택적)
                if (typeof showAlert === 'function') {
                    showAlert('success', `${event.customer.name} 고객을 선택했습니다.`);
                }

                // 커스텀 이벤트 발생 (다른 컴포넌트에서 사용 가능)
                document.dispatchEvent(new CustomEvent('livewire-customer-selected', {
                    detail: { customer: event.customer }
                }));
            });

            Livewire.on('customer-cleared', (event) => {
                console.log('Customer selection cleared');

                // 커스텀 이벤트 발생
                document.dispatchEvent(new CustomEvent('livewire-customer-cleared'));
            });

            Livewire.on('customer-search-error', (event) => {
                console.error('Customer search error:', event.message);

                if (typeof showAlert === 'function') {
                    showAlert('danger', event.message);
                }
            });

            Livewire.on('close-customer-modal', (event) => {
                const modalId = event.modalId;
                const modal = document.getElementById(modalId);
                if (modal) {
                    const bsModal = bootstrap.Modal.getInstance(modal);
                    if (bsModal) {
                        bsModal.hide();
                    }
                }
            });

            Livewire.on('close-modal-js', (event) => {
                console.log('Close modal JS event received:', event);
                const modalId = event.modalId;
                const modal = document.getElementById(modalId);

                console.log('Modal element:', modal);

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
        .customer-search-component .cursor-pointer {
            cursor: pointer;
        }

        .customer-search-component .dropdown-item:hover {
            background-color: var(--bs-gray-100);
        }

        .customer-search-component .hover-shadow:hover {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.1);
            transform: translateY(-1px);
            transition: all 0.15s ease-in-out;
        }

        .customer-search-component .avatar-initial {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.875rem;
        }

        .customer-search-component .avatar-sm .avatar-initial {
            width: 28px;
            height: 28px;
            font-size: 0.75rem;
        }

        .customer-search-component .avatar-md .avatar-initial {
            width: 48px;
            height: 48px;
            font-size: 1.125rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</div>