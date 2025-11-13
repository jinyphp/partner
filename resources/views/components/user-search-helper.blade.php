{{--
회원 검색 헬퍼 JavaScript 컴포넌트 (재사용 가능)

이 컴포넌트는 순수 JavaScript만 포함하며, UI는 포함하지 않습니다.
다른 페이지에서 회원 검색 기능이 필요할 때 이 컴포넌트만 포함하면 됩니다.

사용법:
@include('jiny-partner::components.user-search-helper', [
    'functionPrefix' => 'customPrefix', // 함수명 중복 방지 (선택사항)
])

이후 다음 함수들을 사용할 수 있습니다:
- searchUsers(params) - 회원 검색
- createUserSearchComponent(config) - 검색 컴포넌트 생성
--}}

@props([
    'functionPrefix' => 'userSearch'
])

<script>
/**
 * 회원 검색 헬퍼 함수들
 *
 * 재사용 가능한 JavaScript 함수들을 제공합니다.
 * 여러 페이지에서 일관된 회원 검색 기능을 사용할 수 있습니다.
 */
(function() {
    'use strict';

    // API 엔드포인트
    const USER_SEARCH_API = '{{ route('home.partner.users.search') }}';

    /**
     * 회원 검색 API 호출
     *
     * @param {Object} params 검색 파라미터
     * @param {string} params.email - 이메일 검색
     * @param {string} params.name - 이름 검색
     * @param {string} params.uuid - UUID 검색
     * @param {boolean} params.verifiedOnly - 인증된 회원만
     * @param {number} params.limit - 결과 제한
     * @param {string} params.sort - 정렬 기준
     * @param {string} params.order - 정렬 순서
     * @returns {Promise}
     */
    window.{{ $functionPrefix }}Search = async function(params) {
        try {
            const searchParams = new URLSearchParams();

            Object.keys(params).forEach(key => {
                if (params[key] !== undefined && params[key] !== null && params[key] !== '') {
                    searchParams.append(key, params[key]);
                }
            });

            const response = await fetch(`${USER_SEARCH_API}?${searchParams.toString()}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || '검색 요청이 실패했습니다.');
            }

            return data;

        } catch (error) {
            console.error('User search error:', error);
            throw error;
        }
    };

    /**
     * 이메일 형식 검증
     */
    window.{{ $functionPrefix }}ValidateEmail = function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    };

    /**
     * 검색 결과 HTML 생성
     */
    window.{{ $functionPrefix }}CreateResultHTML = function(users, onClickCallback, showEmailBadge = true) {
        if (!users || users.length === 0) {
            return '<div class="text-center py-4 text-muted"><i class="fe fe-search me-2"></i>검색 결과가 없습니다.</div>';
        }

        let html = '<div class="list-group list-group-flush">';

        users.forEach(user => {
            const emailBadge = showEmailBadge ?
                (user.email_verified ?
                    '<span class="badge bg-success ms-2">인증됨</span>' :
                    '<span class="badge bg-secondary ms-2">미인증</span>') : '';

            const userDataJson = JSON.stringify(user).replace(/"/g, '&quot;');

            html += `
                <div class="list-group-item list-group-item-action border-0" style="cursor: pointer;"
                     onclick="${onClickCallback}('${user.uuid}', '${user.name}', '${user.email}', ${userDataJson})">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-3">
                            <span class="avatar-initial bg-primary text-white rounded-circle">
                                ${user.name ? user.name.charAt(0).toUpperCase() : 'U'}
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">${user.name || '이름 없음'}</h6>
                            <small class="text-muted">${user.email}</small>
                            ${emailBadge}
                        </div>
                        <div class="text-end">
                            <small class="text-muted">가입일</small><br>
                            <small class="text-muted">${new Date(user.created_at).toLocaleDateString('ko-KR')}</small>
                        </div>
                    </div>
                </div>
            `;
        });

        html += '</div>';
        return html;
    };

    /**
     * 알림 메시지 생성
     */
    window.{{ $functionPrefix }}CreateAlert = function(type, message, dismissible = true) {
        const alertClass = type === 'success' ? 'alert-success' :
                         type === 'error' ? 'alert-danger' :
                         type === 'warning' ? 'alert-warning' : 'alert-info';

        const iconClass = type === 'success' ? 'check-circle' :
                         type === 'error' ? 'alert-circle' :
                         type === 'warning' ? 'alert-triangle' : 'info';

        const dismissButton = dismissible ?
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' : '';

        return `
            <div class="alert ${alertClass} ${dismissible ? 'alert-dismissible' : ''} fade show" role="alert">
                <i class="fe fe-${iconClass} me-2"></i>
                ${message}
                ${dismissButton}
            </div>
        `;
    };

    /**
     * 검색 컴포넌트 팩토리 함수
     *
     * 검색 UI를 동적으로 생성하고 관리하는 객체를 반환합니다.
     */
    window.{{ $functionPrefix }}CreateComponent = function(config) {
        const defaultConfig = {
            searchType: 'email', // 'email', 'name', 'both'
            verifiedOnly: false,
            limit: 10,
            showEmailBadge: true,
            onSelect: null, // 필수 콜백
            onSearch: null, // 선택적 콜백
            onError: null   // 선택적 콜백
        };

        const finalConfig = Object.assign({}, defaultConfig, config);

        return {
            config: finalConfig,

            // 검색 실행
            async search(query) {
                try {
                    if (finalConfig.onSearch) {
                        finalConfig.onSearch('start', query);
                    }

                    const params = {
                        limit: finalConfig.limit,
                        verified_only: finalConfig.verifiedOnly
                    };

                    // 검색 타입에 따른 파라미터 설정
                    if (finalConfig.searchType === 'email') {
                        params.email = query;
                    } else if (finalConfig.searchType === 'name') {
                        params.name = query;
                    } else {
                        // 'both' - 이메일 형식이면 이메일로, 아니면 이름으로 검색
                        if (window.{{ $functionPrefix }}ValidateEmail(query)) {
                            params.email = query;
                        } else {
                            params.name = query;
                        }
                    }

                    const result = await window.{{ $functionPrefix }}Search(params);

                    if (finalConfig.onSearch) {
                        finalConfig.onSearch('success', query, result);
                    }

                    return result;

                } catch (error) {
                    if (finalConfig.onError) {
                        finalConfig.onError(error);
                    } else if (finalConfig.onSearch) {
                        finalConfig.onSearch('error', query, error);
                    }
                    throw error;
                }
            },

            // 검색 결과 HTML 생성
            createResultsHTML(users) {
                return window.{{ $functionPrefix }}CreateResultHTML(
                    users,
                    finalConfig.onSelect,
                    finalConfig.showEmailBadge
                );
            },

            // 설정 업데이트
            updateConfig(newConfig) {
                Object.assign(finalConfig, newConfig);
            }
        };
    };

    console.log('{{ $functionPrefix }} helper functions loaded');
})();
</script>