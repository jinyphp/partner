{{--
    Tree Node Partial Template
    재귀적으로 하위 파트너들을 표시
--}}

@foreach($nodes as $node)
<div class="tree-node child-node" style="margin-left: {{ $node['depth'] * 20 }}px;">
    <div class="d-flex align-items-center">
        <div class="tree-line child-line"></div>
        <div class="node-card bg-white border">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <i class="fe fe-arrow-down text-primary me-2"></i>
                    <div>
                        <div class="fw-bold">{{ $node['user']->name }}</div>
                        <small class="text-muted">{{ $node['user']->email }}</small>
                        <div class="mt-1">
                            <span class="badge bg-info">{{ $node['user']->partnerTier->tier_name ?? 'N/A' }}</span>
                            <span class="badge bg-secondary">Level {{ $node['user']->level }}</span>
                            @if($node['stats']['direct_children'] > 0)
                                <span class="badge bg-primary">{{ $node['stats']['direct_children'] }}명 하위</span>
                            @endif
                            @if($node['user']->can_recruit)
                                <span class="badge bg-success">모집가능</span>
                            @else
                                <span class="badge bg-light text-dark">모집불가</span>
                            @endif
                        </div>
                        <div class="mt-1">
                            <small class="text-muted">
                                @if($node['stats']['monthly_sales'] > 0)
                                    매출: {{ number_format($node['stats']['monthly_sales']) }}원
                                @endif
                                @if($node['stats']['earned_commissions'] > 0)
                                    | 커미션: {{ number_format($node['stats']['earned_commissions']) }}원
                                @endif
                                @if($node['stats']['monthly_sales'] == 0 && $node['stats']['earned_commissions'] == 0)
                                    매출 및 커미션 없음
                                @endif
                            </small>
                        </div>
                    </div>
                </div>

                <!-- 액션 버튼 -->
                <div class="btn-group btn-group-sm">
                    <a href="{{ route('admin.partner.users.show', $node['user']->id) }}"
                       class="btn btn-outline-primary btn-sm" title="상세보기">
                        <i class="fe fe-eye"></i>
                    </a>
                    <a href="{{ route('admin.partner.users.tree', $node['user']->id) }}"
                       class="btn btn-outline-info btn-sm" title="트리보기">
                        <i class="fe fe-git-branch"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- 재귀적으로 하위 노드들 표시 --}}
    @if(count($node['children']) > 0)
        <div class="mt-2">
            @include('jiny-partner::admin.partner-users.partials.tree-node', ['nodes' => $node['children'], 'isRoot' => false])
        </div>
    @endif
</div>
@endforeach