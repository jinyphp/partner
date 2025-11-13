@extends('jiny-partner::layouts.home')

@section('title', $pageTitle)

@section('content')
    <div class="container-fluid mt-4">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="text-dark fw-bold mb-1">{{ $pageTitle }}</h2>
                        <p class="text-muted mb-0">나의 파트너 코드로 등록된 하위 파트너 네트워크를 확인하세요</p>
                    </div>
                    <div class="d-flex gap-2">

                    </div>
                </div>
            </div>
        </div>

        <!-- My Partner Info -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border shadow-sm bg-white">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="mb-3 fw-bold text-dark">
                                    <i class="bi bi-person-star me-2 text-primary"></i>{{ $myPartner['name'] }}
                                </h5>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <p class="mb-2 text-muted"><strong class="text-dark">파트너 코드:</strong>
                                            {{ $myPartner['partner_code'] }}</p>
                                        <p class="mb-2 text-muted"><strong class="text-dark">등급:</strong> <span
                                                class="badge bg-primary">{{ $myPartner['tier_name'] }}</span></p>
                                    </div>
                                    <div class="col-sm-6">
                                        <p class="mb-2 text-muted"><strong class="text-dark">이메일:</strong>
                                            {{ $myPartner['email'] }}</p>
                                        <p class="mb-0 text-muted"><strong class="text-dark">가입일:</strong>
                                            {{ $myPartner['joined_at'] ? \Carbon\Carbon::parse($myPartner['joined_at'])->format('Y-m-d') : '-' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="d-flex justify-content-end gap-4">
                                    <div class="text-center">
                                        <h4 class="mb-0 fw-bold text-success">
                                            {{ number_format($myPartner['total_sales'] ?? 0) }}</h4>
                                        <small class="text-muted">총 매출</small>
                                    </div>
                                    <div class="text-center">
                                        <h4 class="mb-0 fw-bold text-warning">
                                            {{ number_format($myPartner['earned_commissions'] ?? 0) }}</h4>
                                        <small class="text-muted">수익 커미션</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Network Statistics -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border shadow-sm bg-white h-100">
                    <div class="card-body d-flex align-items-center p-4">
                        <div class="icon-shape bg-success text-white rounded-circle me-3">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-1 text-dark">{{ $networkStats['total_partners'] }}</h4>
                            <p class="mb-0 small text-muted">전체 하위 파트너</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border shadow-sm bg-white h-100">
                    <div class="card-body d-flex align-items-center p-4">
                        <div class="icon-shape bg-info text-white rounded-circle me-3">
                            <i class="bi bi-diagram-3-fill"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-1 text-dark">{{ $networkStats['direct_partners'] }}</h4>
                            <p class="mb-0 small text-muted">직접 하위 파트너</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border shadow-sm bg-white h-100">
                    <div class="card-body d-flex align-items-center p-4">
                        <div class="icon-shape bg-warning text-white rounded-circle me-3">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-1 text-dark">{{ number_format($networkStats['total_network_sales']) }}</h4>
                            <p class="mb-0 small text-muted">네트워크 총매출</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border shadow-sm bg-white h-100">
                    <div class="card-body d-flex align-items-center p-4">
                        <div class="icon-shape bg-secondary text-white rounded-circle me-3">
                            <i class="bi bi-layers-fill"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-1 text-dark">{{ $networkStats['max_depth'] }}</h4>
                            <p class="mb-0 small text-muted">최대 네트워크 깊이</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Network Tree -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header d-flex justify-content-between">
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-diagram-2-fill me-2"></i>파트너 네트워크 트리
                        </h5>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="expandAllBtn">
                                <i class="bi bi-arrows-expand me-2"></i>모두 펼치기
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="collapseAllBtn">
                                <i class="bi bi-arrows-collapse me-2"></i>모두 접기
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        @if ($networkTree)
                            <div id="partnerTree" class="partner-tree">
                                {!! renderPartnerNode($networkTree) !!}
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="bi bi-people text-muted" style="font-size: 3rem;"></i>
                                <h5 class="text-muted mt-3">등록된 하위 파트너가 없습니다</h5>
                                <p class="text-muted">파트너 코드를 공유하여 새로운 파트너를 모집해보세요!</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        function renderPartnerNode($node, $isRoot = true)
        {
            $levelClass = $isRoot ? 'tree-root' : 'tree-child';
            $hasChildren = !empty($node['children']);
            $nodeId = 'node_' . $node['id'];

            $tierBadgeClass = match ($node['tier_name']) {
                'Diamond' => 'bg-primary',
                'Platinum' => 'bg-secondary',
                'Gold' => 'bg-warning',
                'Silver' => 'bg-info',
                default => 'bg-success',
            };

            $statusClass = $node['status'] === 'active' ? 'text-success' : 'text-danger';

            $html = '<div class="tree-node ' . $levelClass . '" data-node-id="' . $node['id'] . '">';

            // Node Header
            $html .=
                '<div class="tree-node-header d-flex align-items-center justify-content-between p-3 border rounded mb-2 bg-white shadow-sm">';

            // Left side - Partner info with toggle
            $html .= '<div class="d-flex align-items-center flex-grow-1">';

            if ($hasChildren) {
                $html .=
                    '<button type="button" class="btn btn-sm btn-outline-primary me-3 tree-toggle" data-target="#' .
                    $nodeId .
                    '_children">';
                $html .= '<i class="bi bi-chevron-down toggle-icon"></i>';
                $html .= '</button>';
            } else {
                $html .= '<div class="me-3" style="width: 36px;"></div>'; // Spacer for alignment
            }

            $html .= '<div class="partner-info flex-grow-1">';

            // Top row - Name, badges, date info, and button
            $html .= '<div class="d-flex align-items-center justify-content-between mb-2">';

            // Left side - Name and badges
            $html .= '<div class="d-flex align-items-center gap-2">';
            $html .= '<h6 class="mb-0 fw-bold text-dark">' . htmlspecialchars($node['name']) . '</h6>';
            $html .= '<span class="badge ' . $tierBadgeClass . ' text-white">' . $node['tier_name'] . '</span>';
            $html .= '<span class="badge ' . $statusClass . ' bg-light border">' . ucfirst($node['status']) . '</span>';
            $html .= '</div>';

            // Right side - Date, children count, and action button
            $html .= '<div class="d-flex align-items-center gap-3">';
            $html .= '<div class="text-end text-muted small">';
            $html .=
                '<div><i class="bi bi-calendar me-1"></i>' .
                ($node['joined_at'] ? \Carbon\Carbon::parse($node['joined_at'])->format('Y-m-d') : '-') .
                '</div>';
            if ($node['children_count'] > 0) {
                $html .=
                    '<div><i class="bi bi-people me-1"></i>하위 파트너 ' . ($node['children_count'] ?? 0) . '명</div>';
            }
            $html .= '</div>';

            // Action button aligned with date info
            $html .= '<div>';
            $html .=
                '<button type="button" class="btn btn-outline-primary btn-sm" onclick="viewPartnerDetail(' .
                $node['id'] .
                ')">';
            $html .= '<i class="bi bi-eye me-1"></i>상세보기';
            $html .= '</button>';
            $html .= '</div>';
            $html .= '</div>';

            $html .= '</div>'; // Close top row

            // Bottom row - Contact info
            $html .= '<div class="text-muted small">';
            $html .= '<div><i class="bi bi-envelope me-1"></i>' . htmlspecialchars($node['email']) . '</div>';
            if ($node['partner_code']) {
                $html .= '<div><i class="bi bi-qr-code me-1"></i>' . htmlspecialchars($node['partner_code']) . '</div>';
            }
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';

            $html .= '</div>'; // Close node header

            // Children
            if ($hasChildren) {
                $html .= '<div id="' . $nodeId . '_children" class="tree-children ms-4 collapse">';
                foreach ($node['children'] as $child) {
                    $html .= renderPartnerNode($child, false);
                }
                $html .= '</div>';
            }

            $html .= '</div>'; // Close tree-node

            return $html;
        }
    @endphp

    <!-- Custom CSS for Tree Structure -->
    <style>
        .partner-tree {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .tree-node {
            margin-bottom: 15px;
        }

        .tree-node-header {
            background: white !important;
            border: 1px solid #dee2e6 !important;
            transition: all 0.3s ease;
        }

        .tree-node-header:hover {
            border-color: #0d6efd !important;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
            transform: translateY(-2px);
        }

        .tree-toggle {
            transition: all 0.3s ease;
        }

        .tree-toggle:hover {
            background-color: #0d6efd !important;
            border-color: #0d6efd !important;
            color: white !important;
        }

        .tree-toggle .toggle-icon {
            transition: transform 0.3s ease;
        }

        .tree-toggle[aria-expanded="true"] .toggle-icon {
            transform: rotate(180deg);
        }

        .tree-children {
            border-left: 2px solid #0d6efd;
            margin-left: 1rem;
            padding-left: 1rem;
            position: relative;
        }

        .tree-children::before {
            content: '';
            position: absolute;
            left: -2px;
            top: 0;
            width: 2px;
            height: 100%;
            background: linear-gradient(to bottom, #0d6efd, transparent);
        }

        .icon-shape {
            width: 3rem;
            height: 3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        @media (max-width: 768px) {
            .tree-node-header {
                flex-direction: column;
                align-items: stretch !important;
            }

            .action-buttons {
                margin-top: 15px;
                text-align: center;
            }

            .tree-children {
                margin-left: 0.5rem;
                padding-left: 0.5rem;
            }

            .icon-shape {
                width: 2.5rem;
                height: 2.5rem;
                font-size: 1rem;
            }
        }
    </style>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tree toggle functionality
            const toggleButtons = document.querySelectorAll('.tree-toggle');

            toggleButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const target = this.getAttribute('data-target');
                    const targetElement = document.querySelector(target);

                    if (targetElement) {
                        const collapse = new bootstrap.Collapse(targetElement);

                        // Update aria-expanded
                        const isExpanded = this.getAttribute('aria-expanded') === 'true';
                        this.setAttribute('aria-expanded', !isExpanded);
                    }
                });
            });

            // Expand all functionality
            document.getElementById('expandAllBtn').addEventListener('click', function() {
                const allCollapses = document.querySelectorAll('.tree-children');
                allCollapses.forEach(element => {
                    const collapse = bootstrap.Collapse.getInstance(element) || new bootstrap
                        .Collapse(element, {
                            show: false
                        });
                    collapse.show();
                });

                // Update all toggle buttons
                toggleButtons.forEach(button => {
                    button.setAttribute('aria-expanded', 'true');
                });
            });

            // Collapse all functionality
            document.getElementById('collapseAllBtn').addEventListener('click', function() {
                const allCollapses = document.querySelectorAll('.tree-children');
                allCollapses.forEach(element => {
                    const collapse = bootstrap.Collapse.getInstance(element) || new bootstrap
                        .Collapse(element, {
                            show: false
                        });
                    collapse.hide();
                });

                // Update all toggle buttons
                toggleButtons.forEach(button => {
                    button.setAttribute('aria-expanded', 'false');
                });
            });

            // Partner detail view function
            window.viewPartnerDetail = function(partnerId) {
                // 파트너 네트워크 상세 페이지로 이동
                window.location.href = `/home/partner/network/${partnerId}`;
            };

            // AJAX loading for dynamic tree expansion (future enhancement)
            // This can be used to load tree nodes on-demand for large networks
            function loadTreeNode(nodeId) {
                fetch(`/home/partner/network/tree?parent_id=${nodeId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.children) {
                            // Handle dynamic loading here
                            console.log('Loaded children:', data.children);
                        }
                    })
                    .catch(error => {
                        console.error('Error loading tree node:', error);
                    });
            }

            // Add smooth animations
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.animation = 'slideInUp 0.5s ease-out';
                    }
                });
            });

            document.querySelectorAll('.tree-node').forEach(node => {
                observer.observe(node);
            });
        });

        // CSS animations
        const style = document.createElement('style');
        style.textContent = `
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
`;
        document.head.appendChild(style);
    </script>
@endpush
