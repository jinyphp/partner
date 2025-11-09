{{-- HTML 기반 트리 노드 partial --}}
@foreach($nodes as $index => $node)
<li class="tree-item child-item">
    <div class="tree-node">
        @if(count($node['children']) > 0)
            <button class="tree-toggle-btn" onclick="toggleNode(this)" data-collapsed="false">
                <i class="fe fe-minus-circle"></i>
            </button>
        @else
            <span class="tree-toggle-placeholder"></span>
        @endif
        <a href="{{ route('admin.partner.users.show', $node['user']->id) }}" class="user-name-link child-node">{{ $node['user']->name }}</a>
        <span class="text-muted">({{ $node['user']->email }})</span>
        <span class="badge bg-{{ $node['user']->partnerTier ? 'info' : 'secondary' }}">{{ $node['user']->partnerTier->tier_name ?? 'N/A' }}</span>
        <span class="level-badge">Level {{ $node['user']->level }}</span>
        @if($node['user']->can_recruit)
            <span class="badge bg-success">모집가능</span>
        @else
            <span class="badge bg-light text-dark">모집불가</span>
        @endif
    </div>

    @if($node['stats']['monthly_sales'] > 0 || $node['stats']['earned_commissions'] > 0)
    <div class="tree-stats">
        💰 매출: {{ number_format($node['stats']['monthly_sales']) }}원 | 커미션: {{ number_format($node['stats']['earned_commissions']) }}원
    </div>
    @endif

    @if($node['stats']['direct_children'] > 0)
    <div class="tree-stats">
        👥 하위 {{ $node['stats']['direct_children'] }}명 | 전체 {{ $node['stats']['total_descendants'] }}명
    </div>
    @endif

    @if(count($node['children']) > 0)
    <ul class="tree-children">
        @include('jiny-partner::admin.partner-users.partials.tree-html-node', ['nodes' => $node['children']])
    </ul>
    @endif
</li>
@endforeach