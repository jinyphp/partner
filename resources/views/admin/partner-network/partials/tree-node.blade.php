@php
    $partner = $node['partner'];
    $children = $node['children'];
    $level = $node['level'];
    $hasChildren = $children && $children->count() > 0;
@endphp

<div class="tree-node {{ $isRoot ?? false ? 'root-node' : '' }}" data-level="{{ $level }}">
    <div class="partner-card">
        <!-- Tier Badge -->
        @if($node['tier'])
            <span class="badge bg-{{ $node['tier']->tier_color ?? 'primary' }} tier-badge">
                {{ $node['tier']->tier_name }}
            </span>
        @endif

        <!-- Collapse/Expand Button -->
        @if($hasChildren)
            <button type="button" class="btn btn-sm btn-outline-secondary collapse-btn float-end"
                    onclick="toggleNode(this.closest('.partner-card'))">
                <i class="fe fe-minus-circle"></i>
            </button>
        @endif

        <!-- Partner Info -->
        <div class="row">
            <div class="col-md-8">
                <div class="d-flex align-items-center mb-2">
                    <div class="avatar avatar-sm me-2">
                        @if($partner->avatar)
                            <img src="{{ $partner->avatar }}" alt="{{ $partner->name }}" class="rounded-circle">
                        @else
                            <span class="avatar-initials rounded-circle bg-primary">
                                {{ strtoupper(substr($partner->name, 0, 1)) }}
                            </span>
                        @endif
                    </div>
                    <div>
                        <h6 class="mb-0">
                            <a href="{{ route('admin.partner.users.show', $partner->id) }}"
                               class="text-decoration-none">
                                {{ $partner->name }}
                            </a>
                        </h6>
                        <small class="text-muted">{{ $partner->email }}</small>
                    </div>
                </div>

                <div class="row g-2">
                    <div class="col-6">
                        <small class="text-muted d-block">가입일</small>
                        <span class="small">{{ $partner->created_at->format('Y-m-d') }}</span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">상태</small>
                        <span class="badge bg-{{ $partner->status === 'active' ? 'success' : 'secondary' }}">
                            {{ $partner->status === 'active' ? '활성' : '비활성' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Performance Metrics -->
                <div class="text-end">
                    <div class="mb-1">
                        <small class="text-muted">월 매출</small>
                        <div class="fw-bold text-primary">
                            {{ number_format($node['performance']['monthly_sales']) }}원
                        </div>
                    </div>
                    <div class="mb-1">
                        <small class="text-muted">하위 파트너</small>
                        <div class="fw-bold">
                            {{ number_format($node['performance']['children_count']) }}명
                        </div>
                    </div>
                    <div class="mb-1">
                        <small class="text-muted">성과 점수</small>
                        <div class="fw-bold text-{{ $node['performance']['performance_score'] >= 80 ? 'success' : ($node['performance']['performance_score'] >= 60 ? 'warning' : 'danger') }}">
                            {{ $node['performance']['performance_score'] }}점
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Network Stats -->
        <div class="mt-2 pt-2 border-top">
            <div class="row g-2 text-center">
                <div class="col-3">
                    <div class="text-muted small">직접 모집</div>
                    <div class="fw-bold">{{ $node['network_stats']['direct_recruits'] }}</div>
                </div>
                <div class="col-3">
                    <div class="text-muted small">네트워크 규모</div>
                    <div class="fw-bold">{{ $node['network_stats']['total_network_size'] }}</div>
                </div>
                <div class="col-3">
                    <div class="text-muted small">네트워크 매출</div>
                    <div class="fw-bold">{{ number_format($node['network_stats']['network_sales']) }}</div>
                </div>
                <div class="col-3">
                    <div class="text-muted small">커미션</div>
                    <div class="fw-bold text-success">{{ number_format($node['network_stats']['network_commissions']) }}</div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-2 pt-2 border-top">
            <div class="btn-group btn-group-sm" role="group">
                <a href="{{ route('admin.partner.users.show', $partner->id) }}"
                   class="btn btn-outline-primary btn-sm">
                    <i class="fe fe-eye"></i> 상세
                </a>
                <a href="{{ route('admin.partner.users.edit', $partner->id) }}"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="fe fe-edit"></i> 수정
                </a>
                <a href="{{ route('admin.partner.network.commission.partner-summary', $partner->id) }}"
                   class="btn btn-outline-success btn-sm">
                    <i class="fe fe-dollar-sign"></i> 커미션
                </a>
                @if($partner->canRecruit())
                    <button type="button" class="btn btn-outline-info btn-sm"
                            onclick="showRecruitModal({{ $partner->id }}, '{{ $partner->name }}')">
                        <i class="fe fe-user-plus"></i> 모집
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Children Nodes -->
    @if($hasChildren)
        <div class="children-container">
            @foreach($children as $childNode)
                @include('jiny-partner::admin.partner-network.partials.tree-node', ['node' => $childNode, 'isRoot' => false])
            @endforeach
        </div>
    @endif
</div>

@if($isRoot ?? false)
<!-- Recruit Modal (only include once for root) -->
<div class="modal fade" id="recruitModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">파트너 모집</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="recruitForm">
                <div class="modal-body">
                    <input type="hidden" name="parent_id" id="parentId">

                    <div class="mb-3">
                        <label class="form-label">모집할 파트너</label>
                        <select name="child_id" class="form-select" required>
                            <option value="">파트너를 선택하세요</option>
                            @foreach(\Jiny\Partner\Models\PartnerUser::where('parent_id', null)->where('status', 'active')->get() as $available)
                                <option value="{{ $available->id }}">{{ $available->name }} ({{ $available->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">모집 메모</label>
                        <textarea name="recruitment_notes" class="form-control" rows="3"
                                  placeholder="모집에 대한 추가 정보를 입력하세요"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">모집하기</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showRecruitModal(parentId, parentName) {
    $('#parentId').val(parentId);
    $('#recruitModal .modal-title').text(parentName + ' - 파트너 모집');
    $('#recruitModal').modal('show');
}

$('#recruitForm').on('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    $.ajax({
        url: '{{ route("admin.partner.network.recruitment.recruit") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                alert('파트너 모집이 완료되었습니다.');
                location.reload();
            } else {
                alert('오류: ' + response.message);
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            alert('오류: ' + (response?.message || '알 수 없는 오류가 발생했습니다.'));
        }
    });
});
</script>
@endif