{{-- 텍스트 기반 트리 노드 partial --}}
@foreach($nodes as $index => $node)
@php
    $isLastChild = ($index === count($nodes) - 1);
    $currentPrefix = $isLastChild ? '└─' : '├─';
    $childPrefix = $prefix . ($isLastChild ? '   ' : '│  ');
@endphp
{{ $prefix }}{{ $currentPrefix }}<span class="child-node">{{ $node['user']->name }}</span> (<span class="text-muted">{{ $node['user']->email }}</span>) [<span class="badge bg-{{ $node['user']->partnerTier ? 'info' : 'secondary' }}">{{ $node['user']->partnerTier->tier_name ?? 'N/A' }}</span>] [Level {{ $node['user']->level }}]@if($node['user']->can_recruit) <span class="badge bg-success">모집가능</span>@endif
@if($node['stats']['monthly_sales'] > 0 || $node['stats']['earned_commissions'] > 0)
{{ $childPrefix }}💰 매출: {{ number_format($node['stats']['monthly_sales']) }}원 | 커미션: {{ number_format($node['stats']['earned_commissions']) }}원
@endif
@if($node['stats']['direct_children'] > 0)
{{ $childPrefix }}👥 하위 {{ $node['stats']['direct_children'] }}명 | 전체 {{ $node['stats']['total_descendants'] }}명
@endif
@if(count($node['children']) > 0)
@include('jiny-partner::admin.partner-users.partials.tree-text-node', ['nodes' => $node['children'], 'prefix' => $childPrefix])
@endif
@endforeach