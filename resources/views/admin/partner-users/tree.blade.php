@extends('jiny-partner::layouts.admin.sidebar')

@section('title', $title)

@section('content')
    <div class="container-fluid">
        <!-- 헤더 -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1">{{ $user->name }}의 계층구조</h3>
                        <p class="text-muted mb-0 small">레벨 {{ $user->level }} • {{ $user->partnerTier->tier_name ?? 'Bronze' }}</p>
                    </div>
                    <div>
                        <a href="{{ route('admin.' . $routePrefix . '.show', $user->id) }}"
                           class="btn btn-sm btn-outline-secondary me-2">상세보기</a>
                        <a href="{{ route('admin.' . $routePrefix . '.index') }}"
                           class="btn btn-sm btn-outline-primary">목록</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 간단 통계 -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="border rounded p-3 text-center">
                    <div class="h4 mb-0 text-primary">{{ $treeStats['user_level'] }}</div>
                    <small class="text-muted">현재 레벨</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-3 text-center">
                    <div class="h4 mb-0 text-success">{{ $treeStats['direct_children'] }}</div>
                    <small class="text-muted">직계 하위</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-3 text-center">
                    <div class="h4 mb-0 text-info">{{ $treeStats['total_descendants'] }}</div>
                    <small class="text-muted">전체 하위</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-3 text-center">
                    <div class="h4 mb-0">{{ $ancestors ? count($ancestors) : 0 }}</div>
                    <small class="text-muted">상위 레벨</small>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- 상위 파트너 -->
            @if($ancestors && count($ancestors) > 0)
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">상위 파트너</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            @foreach($ancestors as $ancestor)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-medium">{{ $ancestor->name }}</div>
                                    <small class="text-muted">{{ $ancestor->email }}</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-primary">레벨 {{ $ancestor->level }}</span>
                                    <div>
                                        <small class="text-muted">{{ $ancestor->partnerTier->tier_name ?? 'Bronze' }}</small>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- 하위 파트너 -->
            @if($descendants && count($descendants) > 0)
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">하위 파트너</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            @foreach($descendants as $descendant)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div style="margin-left: {{ $descendant['depth'] * 20 }}px;">
                                        <div class="fw-medium">{{ $descendant['user']->name }}</div>
                                        <small class="text-muted">{{ $descendant['user']->email }}</small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-success">레벨 {{ $descendant['user']->level }}</span>
                                        <div>
                                            <small class="text-muted">{{ $descendant['user']->partnerTier->tier_name ?? 'Bronze' }}</small>
                                        </div>
                                    </div>
                                </div>
                                @if($descendant['stats']['direct_children'] > 0)
                                <div style="margin-left: {{ $descendant['depth'] * 20 }}px;">
                                    <small class="text-muted">하위 {{ $descendant['stats']['direct_children'] }}명</small>
                                </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        @if((!$ancestors || count($ancestors) === 0) && (!$descendants || count($descendants) === 0))
        <div class="row">
            <div class="col-12">
                <div class="text-center py-5">
                    <div class="text-muted">
                        <i class="fe fe-users" style="font-size: 3rem;"></i>
                        <div class="mt-3">
                            <h5>연결된 파트너가 없습니다</h5>
                            <p class="mb-0">{{ $user->name }}님은 독립적인 파트너입니다.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <style>
    .list-group-item {
        border-left: none;
        border-right: none;
    }
    .list-group-item:first-child {
        border-top: none;
    }
    .list-group-item:last-child {
        border-bottom: none;
    }
    </style>
@endsection