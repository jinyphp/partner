<section class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.' . $routePrefix . '.index') }}">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="search">검색</label>
                        <input type="text" id="search" name="search" class="form-control"
                            placeholder="제목, 주문번호, 파트너명으로 검색..." value="{{ $searchValue }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="status">상태</label>
                        <select id="status" name="status" class="form-control">
                            <option value="">전체</option>
                            @foreach ($filterOptions['statuses'] as $value => $label)
                                <option value="{{ $value }}" {{ $selectedStatus == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="partner_id">파트너</label>
                        <select id="partner_id" name="partner_id" class="form-control">
                            <option value="">전체</option>
                            @foreach ($filterOptions['partners'] as $id => $name)
                                <option value="{{ $id }}" {{ $selectedPartnerId == $id ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="start_date">시작일</label>
                        <input type="date" id="start_date" name="start_date" class="form-control"
                            value="{{ $startDate }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="end_date">종료일</label>
                        <input type="date" id="end_date" name="end_date" class="form-control"
                            value="{{ $endDate }}">
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-outline-primary me-2">
                        <i class="fe fe-search me-1"></i>검색
                    </button>
                    <a href="{{ route('admin.' . $routePrefix . '.index') }}" class="btn btn-outline-secondary">
                        <i class="fe fe-refresh-cw me-1"></i>초기화
                    </a>
                </div>
            </div>
        </form>
    </div>
</section>
