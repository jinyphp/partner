@extends('jiny-partner::layouts.admin.sidebar')

@section('title', $pageTitle)

@section('content')
    <div class="container-fluid">
        <!-- 헤더 -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1">{{ $pageTitle }}</h3>
                        <p class="text-muted mb-0 small">파트너 네트워크를 단계별로 탐색하세요</p>
                    </div>
                    <div>
                        <button class="btn btn-sm btn-outline-success me-2" onclick="expandAll()">
                            <i class="fe fe-plus-circle me-1"></i>전체 펼치기
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="collapseAll()">
                            <i class="fe fe-minus-circle me-1"></i>전체 접기
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 네트워크 통계 -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="border rounded p-3 text-center bg-white">
                    <div class="h5 mb-0 text-primary">{{ $networkStats['total_partners'] }}</div>
                    <small class="text-muted">전체 파트너</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-3 text-center bg-white">
                    <div class="h5 mb-0 text-success">{{ $networkStats['root_partners'] }}</div>
                    <small class="text-muted">최상위 파트너</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-3 text-center bg-white">
                    <div class="h5 mb-0 text-info">{{ $networkStats['network_depth'] }}</div>
                    <small class="text-muted">최대 깊이</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-3 text-center bg-white">
                    <div class="h5 mb-0 text-warning">{{ $networkStats['active_partners'] }}</div>
                    <small class="text-muted">활성 파트너</small>
                </div>
            </div>
        </div>

        <!-- 브레드크럼 -->
        @if(count($breadcrumbs) > 1)
        <div class="row mb-3">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        @foreach($breadcrumbs as $crumb)
                        <li class="breadcrumb-item {{ $crumb['url'] ? '' : 'active' }}">
                            @if($crumb['url'])
                                <a href="{{ $crumb['url'] }}" class="text-decoration-none">
                                    {{ $crumb['name'] }}
                                </a>
                            @else
                                {{ $crumb['name'] }}
                            @endif
                            <small class="text-muted ms-1">(Level {{ $crumb['level'] }})</small>
                        </li>
                        @endforeach
                    </ol>
                </nav>
            </div>
        </div>
        @endif

        <!-- 파트너 목록 -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            @if($parentId)
                                Level {{ $currentLevel }} 파트너 목록
                            @else
                                최상위 파트너 목록
                            @endif
                            <span class="badge bg-secondary ms-2">{{ count($partners) }}명</span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        @if(count($partners) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50">확장</th>
                                        <th width="300">파트너 정보</th>
                                        <th width="100">등급</th>
                                        <th width="100">하위 수</th>
                                        <th width="100">상태</th>
                                        <th width="120">작업</th>
                                    </tr>
                                </thead>
                                <tbody id="partner-list">
                                    @foreach($partners as $partner)
                                    <tr data-partner-id="{{ $partner->id }}" data-level="{{ $partner->level }}">
                                        <td>
                                            @if($partner->children_count > 0)
                                            <button class="btn btn-sm btn-link p-1 expand-btn"
                                                    onclick="toggleChildren({{ $partner->id }})"
                                                    data-expanded="false">
                                                <i class="fe fe-chevron-right text-primary" style="font-size: 18px;"></i>
                                            </button>
                                            @else
                                            <span class="text-muted">
                                                <i class="fe fe-user" style="font-size: 14px;"></i>
                                            </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div>
                                                <div class="fw-medium">{{ $partner->name }}</div>
                                                <small class="text-muted">{{ $partner->email }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $partner->partnerTier->tier_name === 'Diamond' ? 'primary' : ($partner->partnerTier->tier_name === 'Gold' ? 'warning' : 'secondary') }}">
                                                {{ $partner->partnerTier->tier_name ?? 'Bronze' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($partner->children_count > 0)
                                            <span class="badge bg-success">{{ $partner->children_count }}명</span>
                                            @else
                                            <span class="text-muted">0명</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $partner->status === 'active' ? 'success' : 'secondary' }}">
                                                {{ $partner->status === 'active' ? '활성' : '비활성' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                @if($partner->children_count > 0)
                                                <a href="{{ route($routePrefix . '.tree', ['parent' => $partner->id]) }}"
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="fe fe-eye"></i>
                                                </a>
                                                @endif
                                                <a href="{{ route('admin.partner.users.show', $partner->id) }}"
                                                   class="btn btn-outline-secondary btn-sm">
                                                    <i class="fe fe-user"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- 하위 파트너 로드 영역 -->
                                    <tr id="children-{{ $partner->id }}" class="children-row d-none">
                                        <td colspan="6">
                                            <div class="loading-placeholder p-3 text-center text-muted">
                                                <i class="fe fe-loader"></i> 로딩 중...
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="text-center py-5">
                            <div class="text-muted">
                                <i class="fe fe-users" style="font-size: 3rem;"></i>
                                <div class="mt-3">
                                    <h5>파트너가 없습니다</h5>
                                    <p class="mb-0">
                                        @if($parentId)
                                            이 파트너에게는 하위 파트너가 없습니다.
                                        @else
                                            등록된 최상위 파트너가 없습니다.
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // 하위 파트너 토글 기능
    function toggleChildren(partnerId) {
        const childrenRow = document.getElementById(`children-${partnerId}`);
        const expandBtn = document.querySelector(`tr[data-partner-id="${partnerId}"] .expand-btn`);
        const isExpanded = expandBtn.dataset.expanded === 'true';

        if (isExpanded) {
            // 접기
            childrenRow.classList.add('d-none');
            expandBtn.innerHTML = '<i class="fe fe-chevron-right text-primary" style="font-size: 18px;"></i>';
            expandBtn.dataset.expanded = 'false';
        } else {
            // 펼치기
            loadChildren(partnerId);
        }
    }

    // AJAX로 하위 파트너 로드
    function loadChildren(partnerId) {
        const childrenRow = document.getElementById(`children-${partnerId}`);
        const expandBtn = document.querySelector(`tr[data-partner-id="${partnerId}"] .expand-btn`);

        fetch(`/admin/partner/network/tree/children/${partnerId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const children = data.data.children;
                    let childrenHtml = '';

                    if (children.length > 0) {
                        childrenHtml = `
                            <div class="ps-4 pe-2 py-2" style="background-color: #f8f9fa;">
                                <div class="small text-muted mb-2">
                                    <i class="fe fe-corner-down-right"></i> ${data.data.parent.name}의 하위 파트너 (${children.length}명)
                                </div>
                                <div class="row g-2">
                        `;

                        children.forEach(child => {
                            const tierBadge = child.tier === 'Diamond' ? 'primary' : (child.tier === 'Gold' ? 'warning' : 'secondary');
                            const statusBadge = child.status === 'active' ? 'success' : 'secondary';
                            const statusText = child.status === 'active' ? '활성' : '비활성';

                            childrenHtml += `
                                <div class="col-md-6 col-lg-4">
                                    <div class="card card-sm">
                                        <div class="card-body p-2">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <div class="fw-medium small">${child.name}</div>
                                                    <div class="small text-muted">${child.email}</div>
                                                    <div class="mt-1">
                                                        <span class="badge bg-${tierBadge} badge-sm">${child.tier}</span>
                                                        <span class="badge bg-${statusBadge} badge-sm">${statusText}</span>
                                                    </div>
                                                </div>
                                                <div class="text-end">
                                                    ${child.children_count > 0 ?
                                                        `<a href="/admin/partner/network/tree?parent=${child.id}" class="btn btn-outline-primary btn-sm">
                                                            <i class="fe fe-eye"></i> ${child.children_count}
                                                        </a>` :
                                                        '<small class="text-muted">0명</small>'
                                                    }
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });

                        childrenHtml += `
                                </div>
                            </div>
                        `;
                    } else {
                        childrenHtml = `
                            <div class="ps-4 pe-2 py-3 text-center text-muted" style="background-color: #f8f9fa;">
                                <small>하위 파트너가 없습니다.</small>
                            </div>
                        `;
                    }

                    childrenRow.innerHTML = `<td colspan="6">${childrenHtml}</td>`;
                    childrenRow.classList.remove('d-none');
                    expandBtn.innerHTML = '<i class="fe fe-chevron-down text-primary" style="font-size: 18px;"></i>';
                    expandBtn.dataset.expanded = 'true';
                }
            })
            .catch(error => {
                console.error('Error loading children:', error);
                childrenRow.innerHTML = `<td colspan="6"><div class="p-3 text-center text-danger">로딩 중 오류가 발생했습니다.</div></td>`;
                childrenRow.classList.remove('d-none');
            });
    }

    // 전체 펼치기
    function expandAll() {
        document.querySelectorAll('.expand-btn[data-expanded="false"]').forEach(btn => {
            const partnerId = btn.closest('tr').dataset.partnerId;
            if (partnerId) {
                loadChildren(partnerId);
            }
        });
    }

    // 전체 접기
    function collapseAll() {
        document.querySelectorAll('.children-row').forEach(row => {
            row.classList.add('d-none');
        });
        document.querySelectorAll('.expand-btn').forEach(btn => {
            btn.innerHTML = '<i class="fe fe-chevron-right text-primary" style="font-size: 18px;"></i>';
            btn.dataset.expanded = 'false';
        });
    }
    </script>

    <style>
    .card-sm .card-body {
        padding: 0.5rem;
    }
    .badge-sm {
        font-size: 0.75rem;
    }
    .children-row td {
        padding: 0;
    }
    .expand-btn {
        border: none;
        background: none;
    }
    .expand-btn:hover {
        background: none;
    }
    </style>
@endsection