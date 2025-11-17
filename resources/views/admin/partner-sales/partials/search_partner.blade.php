{{-- 선택된 파트너 정보 (숨김 필드) --}}
<input type="hidden" name="partner_id" id="partner_id" value="{{ old('partner_id', $selectedPartnerId ?? '') }}">

{{-- 파트너 정보 카드 --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fe fe-users me-2"></i>파트너 정보
        </h5>
    </div>
    <div class="card-body">

        {{-- 파트너 검색 안내 --}}
        <div class="alert alert-info border-info">
            <div class="d-flex align-items-center">
                <i class="fe fe-search text-info me-2" style="font-size: 1.2rem;"></i>
                <div class="flex-grow-1">
                    <strong class="text-info">파트너 검색:</strong> 이름 또는 이메일을 입력하여 매출을 등록할 파트너를 검색하세요.
                    <br>
                    <small class="text-muted">
                        <i class="fe fe-info-circle me-1"></i>
                        파트너는 검색 기능을 통해서만 선택할 수 있습니다.
                    </small>
                </div>
            </div>
        </div>

        {{-- 파트너 검색 --}}
        <div class="mb-3">
            <label for="search_partner" class="form-label fw-bold">
                <i class="fe fe-search me-1 text-primary"></i>
                파트너 검색 <span class="text-danger">*</span>
                <span class="badge bg-primary ms-2">필수</span>
            </label>
            <div class="input-group">
                <span class="input-group-text bg-primary text-white">
                    <i class="fe fe-search"></i>
                </span>
                <input type="text"
                       class="form-control border-primary"
                       id="search_partner"
                       placeholder="파트너 이름 또는 이메일로 검색... (예: 홍길동, partner@example.com)"
                       autocomplete="off">
                <button class="btn btn-primary" type="button" onclick="searchPartners()">
                    <i class="fe fe-search me-1"></i>검색
                </button>
            </div>
            <small class="text-muted mt-1 d-block">
                <i class="fe fe-info-circle me-1"></i>
                최소 2글자 이상 입력하여 검색하세요.
            </small>
        </div>

        {{-- 검색 결과 영역 --}}
        <div id="partner_search_results" class="d-none">
            <div class="mb-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-primary">
                    <i class="fe fe-list me-1"></i>검색 결과
                </h6>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearPartnerSearch()">
                    <i class="fe fe-x me-1"></i>닫기
                </button>
            </div>

            {{-- 데스크탑 검색 결과 테이블 --}}
            <div class="d-none d-md-block">
                <div class="table-responsive border rounded" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="bg-light sticky-top">
                            <tr>
                                <th width="40">#</th>
                                <th>이름</th>
                                <th>이메일</th>
                                <th>등급</th>
                                <th>타입</th>
                                <th>커미션율</th>
                                <th width="80">선택</th>
                            </tr>
                        </thead>
                        <tbody id="partner_search_table_body">
                            {{-- JavaScript로 채워질 영역 --}}
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 모바일 검색 결과 카드 --}}
            <div class="d-block d-md-none">
                <div id="partner_search_mobile_results" class="border rounded" style="max-height: 300px; overflow-y: auto;">
                    {{-- JavaScript로 채워질 영역 --}}
                </div>
            </div>

            {{-- 페이지네이션 --}}
            <div id="partner_search_pagination" class="d-flex justify-content-between align-items-center mt-3">
                <div class="d-flex align-items-center">
                    <span class="text-muted me-2">페이지:</span>
                    <button type="button" class="btn btn-outline-primary btn-sm me-1" id="partner_prev_btn" onclick="loadPartnerPage('prev')" disabled>
                        <i class="fe fe-chevron-left"></i>
                    </button>
                    <span class="badge bg-primary me-1" id="partner_current_page">1</span>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="partner_next_btn" onclick="loadPartnerPage('next')" disabled>
                        <i class="fe fe-chevron-right"></i>
                    </button>
                </div>
                <div>
                    <small class="text-muted" id="partner_search_info">검색 결과를 확인하세요</small>
                </div>
            </div>
        </div>

        {{-- 선택된 파트너 정보 표시 --}}
        <div id="selected_partner_info" class="d-none">
            <div class="alert alert-success border-success">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="flex-grow-1">
                        <h6 class="mb-1 text-success">
                            <i class="fe fe-check-circle me-1"></i>선택된 파트너
                        </h6>
                        <div id="selected_partner_details">
                            {{-- JavaScript로 채워질 영역 --}}
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-success btn-sm" onclick="clearSelectedPartner()">
                        <i class="fe fe-edit-3 me-1"></i>변경
                    </button>
                </div>
            </div>
        </div>

        {{-- 이름과 이메일 필드 (읽기 전용) --}}
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="partner_name" class="form-label fw-bold">
                        <i class="fe fe-lock me-1 text-muted"></i>파트너 이름
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">
                            <i class="fe fe-lock text-muted"></i>
                        </span>
                        <input type="text"
                               class="form-control bg-light"
                               id="partner_name"
                               name="partner_name"
                               readonly
                               placeholder="검색으로만 입력됩니다"
                               value="{{ old('partner_name', isset($selectedPartner) ? $selectedPartner->name : '') }}"
                               style="cursor: not-allowed;">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="partner_email" class="form-label fw-bold">
                        <i class="fe fe-lock me-1 text-muted"></i>파트너 이메일
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light">
                            <i class="fe fe-lock text-muted"></i>
                        </span>
                        <input type="email"
                               class="form-control bg-light"
                               id="partner_email"
                               name="partner_email"
                               readonly
                               placeholder="검색으로만 입력됩니다"
                               value="{{ old('partner_email', isset($selectedPartner) ? $selectedPartner->email : '') }}"
                               style="cursor: not-allowed;">
                    </div>
                </div>
            </div>
        </div>

        {{-- 추가 파트너 정보 --}}
        <div id="additional_partner_info" class="d-none">
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label text-muted">파트너 등급</label>
                        <div id="partner_tier_display" class="p-2 bg-light border rounded">
                            <span class="badge bg-secondary">선택된 파트너 없음</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label text-muted">파트너 타입</label>
                        <div id="partner_type_display" class="p-2 bg-light border rounded">
                            <span class="badge bg-secondary">선택된 파트너 없음</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label text-muted">총 커미션율</label>
                        <div id="partner_commission_display" class="p-2 bg-light border rounded text-primary fw-bold">
                            -
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- 파트너 검색 관련 JavaScript 변수 --}}
<script>
// 파트너 검색 관련 전역 변수
let partnerSearchState = {
    currentPage: 1,
    totalPages: 1,
    totalResults: 0,
    perPage: 10,
    searchTerm: '',
    selectedPartner: null
};

// 파트너 검색 기능
function searchPartners(page = 1) {
    const searchTerm = document.getElementById('search_partner').value.trim();

    if (searchTerm.length < 2) {
        alert('최소 2글자 이상 입력해주세요.');
        return;
    }

    // 검색 중 표시
    const searchBtn = document.querySelector('#search_partner').nextElementSibling;
    const originalBtnText = searchBtn.innerHTML;
    searchBtn.innerHTML = '<i class="fe fe-loader spin me-1"></i>검색 중...';
    searchBtn.disabled = true;

    // 검색 상태 업데이트
    partnerSearchState.searchTerm = searchTerm;
    partnerSearchState.currentPage = page;

    // API 호출
    fetch(`{{ route('api.admin.partner.v1.partners.search') }}?search=${encodeURIComponent(searchTerm)}&page=${page}&per_page=${partnerSearchState.perPage}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayPartnerSearchResults(data);
        } else {
            alert('검색 중 오류가 발생했습니다: ' + (data.message || '알 수 없는 오류'));
        }
    })
    .catch(error => {
        console.error('Search error:', error);
        alert('검색 중 네트워크 오류가 발생했습니다.');
    })
    .finally(() => {
        // 검색 버튼 원상복구
        searchBtn.innerHTML = originalBtnText;
        searchBtn.disabled = false;
    });
}

// 검색 결과 표시
function displayPartnerSearchResults(apiResponse) {
    // 검색 상태 업데이트
    partnerSearchState.totalPages = apiResponse.pagination.last_page || 1;
    partnerSearchState.totalResults = apiResponse.pagination.total || 0;
    partnerSearchState.currentPage = apiResponse.pagination.current_page || 1;

    const data = apiResponse.data;

    // 검색 결과 영역 표시
    document.getElementById('partner_search_results').classList.remove('d-none');

    // 데스크탑 테이블 채우기
    const tableBody = document.getElementById('partner_search_table_body');
    let tableHtml = '';

    if (data && data.length > 0) {
        data.forEach((partner, index) => {
            const startIndex = ((partnerSearchState.currentPage - 1) * partnerSearchState.perPage) + 1;
            const tierBadgeClass = getTierBadgeClass(partner.tier_name);
            const typeBadgeClass = getTypeBadgeClass(partner.type_name);

            tableHtml += `
                <tr class="partner-search-row" data-partner='${JSON.stringify(partner)}' onclick="selectPartner(this)">
                    <td>${startIndex + index}</td>
                    <td>
                        <div class="fw-medium">${partner.name}</div>
                    </td>
                    <td>
                        <small class="text-muted">${partner.email}</small>
                    </td>
                    <td>
                        <span class="badge ${tierBadgeClass}">${partner.tier_name}</span>
                    </td>
                    <td>
                        <span class="badge ${typeBadgeClass}">${partner.type_name}</span>
                    </td>
                    <td>
                        <span class="text-primary fw-bold">${partner.total_commission_rate}%</span>
                    </td>
                    <td>
                        <button type="button" class="btn btn-primary btn-sm" onclick="event.stopPropagation(); selectPartner(this.closest('tr'))">
                            <i class="fe fe-check"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
    } else {
        tableHtml = `
            <tr>
                <td colspan="7" class="text-center text-muted py-3">
                    <i class="fe fe-search me-2"></i>검색 결과가 없습니다.
                </td>
            </tr>
        `;
    }
    tableBody.innerHTML = tableHtml;

    // 모바일 카드 채우기
    const mobileResults = document.getElementById('partner_search_mobile_results');
    let mobileHtml = '';

    if (data && data.length > 0) {
        data.forEach(partner => {
            const tierBadgeClass = getTierBadgeClass(partner.tier_name);
            const typeBadgeClass = getTypeBadgeClass(partner.type_name);

            mobileHtml += `
                <div class="card mb-2 partner-search-card" data-partner='${JSON.stringify(partner)}' onclick="selectPartner(this)">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">${partner.name}</h6>
                                <small class="text-muted d-block">${partner.email}</small>
                                <div class="mt-1">
                                    <span class="badge ${tierBadgeClass} me-1">${partner.tier_name}</span>
                                    <span class="badge ${typeBadgeClass}">${partner.type_name}</span>
                                    <span class="text-primary fw-bold ms-2">${partner.total_commission_rate}%</span>
                                </div>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" onclick="event.stopPropagation(); selectPartner(this.closest('.partner-search-card'))">
                                <i class="fe fe-check"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
    } else {
        mobileHtml = `
            <div class="text-center text-muted py-3">
                <i class="fe fe-search me-2"></i>검색 결과가 없습니다.
            </div>
        `;
    }
    mobileResults.innerHTML = mobileHtml;

    // 페이지네이션 업데이트
    updatePartnerPagination();
}

// 파트너 선택
function selectPartner(element) {
    const partnerData = JSON.parse(element.dataset.partner);

    // 선택된 파트너 정보 저장
    partnerSearchState.selectedPartner = partnerData;

    // 히든 필드 업데이트
    document.getElementById('partner_id').value = partnerData.id;

    // 이름, 이메일 필드 업데이트
    document.getElementById('partner_name').value = partnerData.name;
    document.getElementById('partner_email').value = partnerData.email;

    // 선택된 파트너 정보 표시
    const selectedInfo = document.getElementById('selected_partner_details');
    const tierBadgeClass = getTierBadgeClass(partnerData.tier_name);
    const typeBadgeClass = getTypeBadgeClass(partnerData.type_name);

    selectedInfo.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <strong>${partnerData.name}</strong><br>
                <small class="text-muted">${partnerData.email}</small>
            </div>
            <div class="col-md-6">
                <span class="badge ${tierBadgeClass} me-1">${partnerData.tier_name}</span>
                <span class="badge ${typeBadgeClass} me-1">${partnerData.type_name}</span>
                <br>
                <small class="text-success">총 커미션율: <strong>${partnerData.total_commission_rate}%</strong></small>
            </div>
        </div>
    `;

    // 추가 파트너 정보 표시
    document.getElementById('partner_tier_display').innerHTML = `<span class="badge ${tierBadgeClass}">${partnerData.tier_name}</span>`;
    document.getElementById('partner_type_display').innerHTML = `<span class="badge ${typeBadgeClass}">${partnerData.type_name}</span>`;
    document.getElementById('partner_commission_display').textContent = partnerData.total_commission_rate + '%';

    // UI 상태 변경
    document.getElementById('selected_partner_info').classList.remove('d-none');
    document.getElementById('additional_partner_info').classList.remove('d-none');
    document.getElementById('partner_search_results').classList.add('d-none');

    // 커미션 미리보기 업데이트 (기존 함수 호출)
    if (typeof updateCommissionPreview === 'function') {
        updateCommissionPreview();
    }

    // 파트너 정보 카드 표시 (기존 코드와의 호환성)
    if (typeof showPartnerInfo === 'function') {
        showPartnerInfo(partnerData);
    }

    console.log('Selected partner:', partnerData);
}

// 선택된 파트너 초기화
function clearSelectedPartner() {
    // 선택된 파트너 정보 초기화
    partnerSearchState.selectedPartner = null;

    // 히든 필드 초기화
    document.getElementById('partner_id').value = '';

    // 이름, 이메일 필드 초기화
    document.getElementById('partner_name').value = '';
    document.getElementById('partner_email').value = '';

    // UI 상태 변경
    document.getElementById('selected_partner_info').classList.add('d-none');
    document.getElementById('additional_partner_info').classList.add('d-none');

    // 검색 필드 초기화
    document.getElementById('search_partner').value = '';
}

// 파트너 검색 영역 닫기
function clearPartnerSearch() {
    document.getElementById('partner_search_results').classList.add('d-none');
}

// 페이지 로드
function loadPartnerPage(direction) {
    let newPage = partnerSearchState.currentPage;

    if (direction === 'prev' && partnerSearchState.currentPage > 1) {
        newPage = partnerSearchState.currentPage - 1;
    } else if (direction === 'next' && partnerSearchState.currentPage < partnerSearchState.totalPages) {
        newPage = partnerSearchState.currentPage + 1;
    }

    if (newPage !== partnerSearchState.currentPage) {
        searchPartners(newPage);
    }
}

// 페이지네이션 UI 업데이트
function updatePartnerPagination() {
    document.getElementById('partner_current_page').textContent = partnerSearchState.currentPage;

    const prevBtn = document.getElementById('partner_prev_btn');
    const nextBtn = document.getElementById('partner_next_btn');

    prevBtn.disabled = partnerSearchState.currentPage <= 1;
    nextBtn.disabled = partnerSearchState.currentPage >= partnerSearchState.totalPages;

    const searchInfo = document.getElementById('partner_search_info');
    if (partnerSearchState.totalResults > 0) {
        const start = ((partnerSearchState.currentPage - 1) * partnerSearchState.perPage) + 1;
        const end = Math.min(partnerSearchState.currentPage * partnerSearchState.perPage, partnerSearchState.totalResults);
        searchInfo.textContent = `${start}-${end} / ${partnerSearchState.totalResults}개`;
    } else {
        searchInfo.textContent = '검색 결과 없음';
    }
}

// 배지 클래스 헬퍼 함수들
function getTierBadgeClass(tierName) {
    switch(tierName) {
        case 'Diamond': return 'bg-primary';
        case 'Gold': return 'bg-warning';
        case 'Silver': return 'bg-info';
        case 'Bronze': return 'bg-secondary';
        default: return 'bg-secondary';
    }
}

function getTypeBadgeClass(typeName) {
    switch(typeName) {
        case 'Premium': return 'bg-success';
        case 'Standard': return 'bg-info';
        case 'Basic': return 'bg-secondary';
        default: return 'bg-secondary';
    }
}

// Enter 키로 검색
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('search_partner').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchPartners();
        }
    });

    // 기존 선택된 파트너가 있는 경우 초기화
    const partnerId = document.getElementById('partner_id').value;
    if (partnerId) {
        // 페이지 로드 시 선택된 파트너 정보 표시
        const partnerName = document.getElementById('partner_name').value;
        if (partnerName) {
            document.getElementById('selected_partner_info').classList.remove('d-none');
            document.getElementById('additional_partner_info').classList.remove('d-none');
        }
    }
});
</script>