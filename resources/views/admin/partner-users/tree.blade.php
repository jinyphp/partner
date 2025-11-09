@extends('jiny-partner::layouts.admin.sidebar')

@section('title', $title)

@section('content')
    <div class="container-fluid">
        <!-- 헤더 -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1">{{ $title }}</h2>
                        <p class="text-muted mb-0">{{ $user->name }}님의 파트너 네트워크 구조</p>
                    </div>
                    <div>
                        <a href="{{ route('admin.' . $routePrefix . '.show', $user->id) }}"
                            class="btn btn-outline-secondary me-2">
                            <i class="fe fe-arrow-left me-2"></i>상세보기로
                        </a>
                        <a href="{{ route('admin.' . $routePrefix . '.index') }}" class="btn btn-outline-primary">
                            <i class="fe fe-list me-2"></i>목록으로
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 트리 통계 -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="display-6 fw-bold text-info">{{ $treeStats['user_level'] }}</div>
                        <div class="text-muted">현재 깊이</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="display-6 fw-bold text-primary">{{ $treeStats['direct_children'] }}</div>
                        <div class="text-muted">직계 하위</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="display-6 fw-bold text-success">{{ $treeStats['total_descendants'] }}</div>
                        <div class="text-muted">전체 하위</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="display-6 fw-bold text-warning">{{ number_format($treeStats['team_sales']) }}</div>
                        <div class="text-muted">팀 매출</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 트리 구조 -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">네트워크 트리 구조</h5>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="copyTreeStructure()">
                            <i class="fe fe-copy me-1"></i>복사
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="tree-container">
                    <pre class="tree-text"><span class="current-node">🎯 {{ $user->name }} (현재)</span> (<span class="text-muted">{{ $user->email }}</span>) [<span class="badge bg-primary">{{ $user->partnerTier->tier_name ?? 'N/A' }}</span>] [Level {{ $user->level }}]@if ($user->can_recruit)
<span class="badge bg-success">모집가능</span>
@endif
@if ($user->monthly_sales > 0 || $user->earned_commissions > 0)
💰 매출: {{ number_format($user->monthly_sales) }}원 | 커미션: {{ number_format($user->earned_commissions) }}원
@endif
@if ($user->direct_children_count > 0)
👥 직계 하위 {{ $user->direct_children_count }}명 | 전체 하위 {{ $user->total_descendants_count }}명
@endif
@if (count($descendants) > 0)
@include('jiny-partner::admin.partner-users.partials.tree-text-node', [
    'nodes' => $descendants,
    'prefix' => '',
])
@else
📂 하위 파트너 없음
@endif
</pre>
                </div>

                <!-- 범례 -->
                <div class="mt-4 p-3 bg-light rounded">
                    <h6 class="mb-2">범례</h6>
                    <div class="row">
                        <div class="col-md-3">
                            <span class="badge bg-info">Bronze</span> 등급
                        </div>
                        <div class="col-md-3">
                            <span class="badge bg-success">모집가능</span> 상태
                        </div>
                        <div class="col-md-3">
                            💰 매출/커미션 정보
                        </div>
                        <div class="col-md-3">
                            [Level X] 계층 깊이
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* 텍스트 기반 트리 구조 스타일 */
        .tree-container {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            overflow-x: auto;
        }

        .tree-text {
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background: transparent;
            border: none;
            color: #495057;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        /* 노드 타입별 스타일 */
        .current-node {
            color: #007bff;
            font-weight: bold;
        }

        .child-node {
            color: #495057;
            font-weight: 500;
        }

        /* 배지 스타일 */
        .badge {
            display: inline-block;
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 6px;
            font-weight: 500;
        }

        .bg-primary {
            background-color: #007bff !important;
            color: white !important;
        }

        .bg-info {
            background-color: #17a2b8 !important;
            color: white !important;
        }

        .bg-secondary {
            background-color: #6c757d !important;
            color: white !important;
        }

        .bg-success {
            background-color: #28a745 !important;
            color: white !important;
        }

        .text-muted {
            color: #6c757d !important;
        }

        /* 통계 카드 스타일 */
        .display-6 {
            font-size: 2rem;
        }

        /* 액션 버튼 스타일 */
        .btn-outline-primary:hover,
        .btn-outline-secondary:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* 스크롤바 스타일 */
        .tree-container::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }

        .tree-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .tree-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .tree-container::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* 반응형 처리 */
        @media (max-width: 768px) {
            .tree-container {
                padding: 15px;
            }

            .tree-text {
                font-size: 12px;
            }

            .badge {
                font-size: 9px;
                padding: 1px 4px;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        // 트리 구조 복사 기능
        function copyTreeStructure() {
            const treeContainer = document.querySelector('.tree-text');
            if (treeContainer) {
                const textContent = treeContainer.innerText;
                navigator.clipboard.writeText(textContent).then(function() {
                    showToast('트리 구조가 클립보드에 복사되었습니다.', 'success');
                }, function(err) {
                    showToast('복사에 실패했습니다: ' + err, 'error');
                });
            }
        }

        // 토스트 메시지 표시 함수
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast-message toast-${type}`;
            toast.textContent = message;
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#28a745' : '#dc3545'};
                color: white;
                padding: 12px 20px;
                border-radius: 4px;
                z-index: 10000;
                opacity: 0;
                transition: opacity 0.3s ease;
            `;

            document.body.appendChild(toast);

            requestAnimationFrame(() => {
                toast.style.opacity = '1';
            });

            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, 3000);
        }

        // 우클릭 컨텍스트 메뉴
        document.addEventListener('DOMContentLoaded', function() {
            const treeContainer = document.querySelector('.tree-container');
            if (treeContainer) {
                treeContainer.addEventListener('contextmenu', function(e) {
                    e.preventDefault();
                    if (confirm('트리 구조를 클립보드에 복사하시겠습니까?')) {
                        copyTreeStructure();
                    }
                });
            }
        });
    </script>
@endpush
