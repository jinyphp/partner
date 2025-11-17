<section class="card mb-4">
    <form method="GET" action="{{ route('admin.' . $routePrefix . '.index') }}">
        <div class="card-body">

            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="search">검색</label>
                        <input type="text" id="search" name="search" class="form-control"
                            placeholder="이메일, 이름, 메모로 검색..." value="{{ $searchValue }}">
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="form-group">
                        <label for="status">상태</label>
                        <select id="status" name="status" class="form-control">
                            <option value="">전체</option>
                            @foreach ($filterOptions['statuses'] as $value => $label)
                                <option value="{{ $value }}" {{ $selectedStatus === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="partner_type_id">타입</label>
                        <select id="partner_type_id" name="partner_type_id" class="form-control">
                            <option value="">전체</option>
                            @foreach ($filterOptions['types'] as $type)
                                <option value="{{ $type->id }}" {{ $selectedType == $type->id ? 'selected' : '' }}>
                                    {{ $type->type_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="partner_tier_id">등급</label>
                        <select id="partner_tier_id" name="partner_tier_id" class="form-control">
                            <option value="">전체</option>
                            @foreach ($filterOptions['tiers'] as $tier)
                                <option value="{{ $tier->id }}"
                                    {{ $selectedTier == $tier->id || request('tier') == $tier->id || request('partner_tier_id') == $tier->id ? 'selected' : '' }}>
                                    {{ $tier->tier_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="user_table">사용자 테이블</label>
                        <select id="user_table" name="user_table" class="form-control">
                            <option value="">전체</option>
                            @foreach ($filterOptions['userTables'] as $table)
                                <option value="{{ $table }}"
                                    {{ $selectedUserTable === $table ? 'selected' : '' }}>
                                    {{ $table }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-outline-primary me-2">
                        <i class="fe fe-search me-1"></i>검색
                    </button>
                    <a href="{{ route('admin.' . $routePrefix . '.index') }}" class="btn btn-outline-secondary">
                        <i class="fe fe-refresh-cw me-1"></i>초기화
                    </a>
                </div>
            </div>

        </div>
    </form>
</section>
