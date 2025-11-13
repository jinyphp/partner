@extends('jiny-partner::layouts.home')

@section('title', $pageTitle ?? '파트너 관리')

@section('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .partner-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .partner-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }

        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
        }

        .tree-item {
            position: relative;
            padding-left: 20px;
            margin-bottom: 10px;
        }

        .tree-item:before {
            content: '';
            position: absolute;
            left: 8px;
            top: 20px;
            bottom: 0;
            width: 1px;
            background: #dee2e6;
        }

        .tree-item:last-child:before {
            height: 20px;
        }

        .tree-item:after {
            content: '';
            position: absolute;
            left: 8px;
            top: 20px;
            width: 12px;
            height: 1px;
            background: #dee2e6;
        }

        .tree-node {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 12px;
            margin-left: 20px;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .tree-node:hover {
            border-color: #007bff;
            transform: translateX(2px);
        }

        .level-indicator {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            margin-right: 10px;
        }

        .level-0 { background: #007bff; color: white; }
        .level-1 { background: #28a745; color: white; }
        .level-2 { background: #ffc107; color: black; }
        .level-3 { background: #dc3545; color: white; }

        .activity-item {
            border-left: 3px solid;
            padding-left: 15px;
            margin-bottom: 15px;
        }

        .activity-sales { border-left-color: #28a745; }
        .activity-commission { border-left-color: #007bff; }
        .activity-network { border-left-color: #6f42c1; }

        .network-path {
            background: linear-gradient(90deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 20px;
            padding: 8px 16px;
            margin: 4px;
            display: inline-block;
            font-size: 0.875rem;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid p-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">파트너 관리</h1>
                <p class="text-muted small">파트너 정보 및 네트워크 관리</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('home.partner.index') }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-speedometer2 me-1"></i>대시보드
                </a>
                <a href="/home" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-house me-1"></i>홈으로
                </a>
            </div>
        </div>

        <!-- 파트너 기본 정보 -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card partner-card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-person-badge me-2"></i>{{ $partner->business_name ?? $user->name }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="level-indicator level-{{ min($networkInfo['level'] ?? 0, 3) }}">
                                        L{{ $networkInfo['level'] ?? 0 }}
                                    </div>
                                    <p class="small text-muted mb-0">파트너 레벨</p>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <div class="row">
                                    <div class="col-md-4">
                                        <p class="mb-1"><strong>파트너 타입</strong></p>
                                        <p class="text-muted">{{ $partner->partnerType->type_name ?? '일반 파트너' }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <p class="mb-1"><strong>파트너 등급</strong></p>
                                        <p class="text-muted">{{ $partner->partnerTier->tier_name ?? '기본 등급' }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <p class="mb-1"><strong>가입일</strong></p>
                                        <p class="text-muted">{{ $partner->created_at ? $partner->created_at->format('Y-m-d') : '정보 없음' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 네트워크 경로 -->
        @if(isset($networkInfo['network_path']) && count($networkInfo['network_path']) > 0)
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bi bi-diagram-3 me-2"></i>네트워크 경로
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap align-items-center">
                                @foreach($networkInfo['network_path'] as $index => $pathItem)
                                    <span class="network-path">
                                        {{ $pathItem['name'] }} (L{{ $pathItem['level'] }})
                                    </span>
                                    @if($index < count($networkInfo['network_path']) - 1)
                                        <i class="bi bi-chevron-right mx-2 text-muted"></i>
                                    @endif
                                @endforeach
                                <i class="bi bi-chevron-right mx-2 text-muted"></i>
                                <span class="network-path bg-primary text-white">
                                    {{ $partner->business_name ?? $user->name }} (현재)
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- 통계 정보 -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card text-center">
                    <div class="card-body">
                        <i class="bi bi-currency-dollar fs-1 mb-2"></i>
                        <h4>{{ number_format($partnerStats['total_sales'] ?? 0) }}</h4>
                        <p class="mb-0">총 매출</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white text-center">
                    <div class="card-body">
                        <i class="bi bi-wallet2 fs-1 mb-2"></i>
                        <h4>{{ number_format($partnerStats['total_commission'] ?? 0) }}</h4>
                        <p class="mb-0">총 커미션</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white text-center">
                    <div class="card-body">
                        <i class="bi bi-people fs-1 mb-2"></i>
                        <h4>{{ $partnerStats['direct_partners'] ?? 0 }}</h4>
                        <p class="mb-0">직접 파트너</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white text-center">
                    <div class="card-body">
                        <i class="bi bi-diagram-2 fs-1 mb-2"></i>
                        <h4>{{ $networkInfo['total_descendants'] ?? 0 }}</h4>
                        <p class="mb-0">전체 네트워크</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- 하위 파트너 트리 -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-diagram-3 me-2"></i>하위 파트너 트리
                        </h5>
                        <span class="badge bg-primary">{{ count($subPartners) }}명</span>
                    </div>
                    <div class="card-body">
                        @if(count($subPartners) > 0)
                            <div class="tree-container">
                                @foreach($subPartners as $partner)
                                    <div class="tree-item">
                                        <div class="tree-node" data-partner-id="{{ $partner->id }}">
                                            <div class="d-flex align-items-center">
                                                <div class="level-indicator level-{{ min($partner->level ?? 1, 3) }} me-3">
                                                    L{{ $partner->level ?? 1 }}
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">{{ $partner->business_name ?? '파트너' }}</h6>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <small class="text-muted">
                                                                <i class="bi bi-award me-1"></i>
                                                                {{ $partner->partnerTier->tier_name ?? '기본 등급' }}
                                                            </small>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <small class="text-muted">
                                                                <i class="bi bi-calendar-event me-1"></i>
                                                                {{ isset($partner->relationship_data['joined_at']) ? $partner->relationship_data['joined_at']->format('Y-m-d') : '가입일 정보 없음' }}
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                                @if($partner->relationship_data['is_active'] ?? true)
                                                    <span class="badge bg-success">활성</span>
                                                @else
                                                    <span class="badge bg-secondary">비활성</span>
                                                @endif
                                            </div>

                                            <!-- 하위 파트너가 있는 경우 재귀 표시 -->
                                            @if(isset($partner->sub_partners) && count($partner->sub_partners) > 0)
                                                <div class="mt-2 ms-3">
                                                    @foreach($partner->sub_partners as $subPartner)
                                                        <div class="tree-item">
                                                            <div class="tree-node" data-partner-id="{{ $subPartner->id }}">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="level-indicator level-{{ min($subPartner->level ?? 2, 3) }} me-2">
                                                                        L{{ $subPartner->level ?? 2 }}
                                                                    </div>
                                                                    <div class="flex-grow-1">
                                                                        <h6 class="mb-1 small">{{ $subPartner->business_name ?? '파트너' }}</h6>
                                                                        <small class="text-muted">{{ $subPartner->partnerTier->tier_name ?? '기본 등급' }}</small>
                                                                    </div>
                                                                    @if($subPartner->relationship_data['is_active'] ?? true)
                                                                        <span class="badge bg-success badge-sm">활성</span>
                                                                    @else
                                                                        <span class="badge bg-secondary badge-sm">비활성</span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="bi bi-people display-4 text-muted mb-3"></i>
                                <h6 class="text-muted">아직 하위 파트너가 없습니다</h6>
                                <p class="text-muted small">파트너를 추천하여 네트워크를 구축해보세요.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- 최근 활동 -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-activity me-2"></i>최근 활동
                        </h5>
                    </div>
                    <div class="card-body">
                        @if(count($recentActivities) > 0)
                            @foreach($recentActivities as $activity)
                                <div class="activity-item activity-{{ $activity['type'] }}">
                                    <div class="d-flex align-items-start">
                                        <i class="{{ $activity['icon'] }} text-{{ $activity['color'] }} me-2 mt-1"></i>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 small">{{ $activity['title'] }}</h6>
                                            <p class="mb-1 small text-muted">{{ $activity['description'] }}</p>
                                            <small class="text-muted">{{ $activity['date']->diffForHumans() }}</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-3">
                                <i class="bi bi-clock-history display-6 text-muted mb-2"></i>
                                <p class="text-muted small mb-0">최근 활동이 없습니다</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- 상위 파트너 정보 -->
                @if(isset($networkInfo['parent_partner']))
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bi bi-arrow-up me-2"></i>상위 파트너
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="level-indicator level-{{ min(($networkInfo['level'] ?? 1) - 1, 3) }} me-3">
                                    L{{ ($networkInfo['level'] ?? 1) - 1 }}
                                </div>
                                <div>
                                    <h6 class="mb-1">{{ $networkInfo['parent_partner']->business_name ?? '상위 파트너' }}</h6>
                                    <p class="mb-0 small text-muted">
                                        {{ $networkInfo['parent_partner']->partnerTier->tier_name ?? '파트너' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 카드 애니메이션
            const cards = document.querySelectorAll('.partner-card, .card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';

                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // 트리 노드 클릭 이벤트
            const treeNodes = document.querySelectorAll('.tree-node');
            treeNodes.forEach(node => {
                node.addEventListener('click', function() {
                    const partnerId = this.dataset.partnerId;
                    if (partnerId) {
                        // 상세 정보 표시 로직
                        console.log('파트너 상세 정보:', partnerId);

                        // 선택된 노드 하이라이트
                        treeNodes.forEach(n => n.classList.remove('bg-light'));
                        this.classList.add('bg-light');
                    }
                });
            });
        });
    </script>
@endsection