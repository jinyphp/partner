<?php
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Partner Home Routes
|--------------------------------------------------------------------------
|
| 파트너 홈 관련 라우트들을 계층적으로 정의합니다.
| JWT 인증된 사용자만 접근 가능합니다.
|
*/

Route::middleware(['web'])->prefix('home')->name('home.')->group(function () {

    // Partner Main Routes
    Route::prefix('partner')->name('partner.')->group(function () {

        // Intro (파트너 프로그램 소개)
        Route::get('/intro', \Jiny\Partner\Http\Controllers\Home\Intro\IndexController::class)
            ->name('intro');

        // Dashboard
        Route::get('/', \Jiny\Partner\Http\Controllers\Home\Dashboard\IndexController::class)
            ->name('index');

        // Registration Routes
        Route::prefix('regist')->name('regist.')->group(function () {
            // 파트너 코드 입력 요청 페이지
            Route::get('/', \Jiny\Partner\Http\Controllers\Home\PartnerRegist\PartnerCodeInputController::class)
                ->name('index');

            // 파트너 코드 검증 및 리다이렉션 (AJAX)
            Route::post('/validate-code', [\Jiny\Partner\Http\Controllers\Home\PartnerRegist\PartnerCodeInputController::class, 'validateAndRedirect'])
                ->name('validate-code');

            // 신청서 작성 폼 (파트너 코드 라우트보다 먼저 정의)
            Route::get('/create', \Jiny\Partner\Http\Controllers\Home\PartnerRegist\CreateController::class)
                ->name('create');

            // 파트너 코드와 함께 신청서 작성 폼
            Route::get('/create/{partnerCode}', [\Jiny\Partner\Http\Controllers\Home\PartnerRegist\CreateController::class, 'createWithCode'])
                ->name('create.with-code')
                ->where('partnerCode', '[A-Z0-9]{20}');

            // 파트너 코드를 통한 하위 파트너 가입
            Route::get('/{partnerCode}', [\Jiny\Partner\Http\Controllers\Home\PartnerRegist\ReferralController::class, '__invoke'])
                ->name('referral')
                ->where('partnerCode', '[A-Z0-9]{20}');

            // 신청서 제출
            Route::post('/', \Jiny\Partner\Http\Controllers\Home\PartnerRegist\StoreController::class)
                ->name('store');

            // 디버그 모드 신청서 제출 (CSRF 우회)
            Route::post('/debug', \Jiny\Partner\Http\Controllers\Home\PartnerRegist\StoreController::class)
                ->name('store.debug')
                ->withoutMiddleware(['csrf']);

            // 신청서 수정 폼
            Route::get('{id}/edit', \Jiny\Partner\Http\Controllers\Home\PartnerRegist\EditController::class)
                ->name('edit');

            // 신청서 수정 처리
            Route::put('{id}', \Jiny\Partner\Http\Controllers\Home\PartnerRegist\UpdateController::class)
                ->name('update');

            // 신청 상태 확인 (JWT 사용자 기반)
            Route::get('status', \Jiny\Partner\Http\Controllers\Home\Status\IndexController::class)
                ->name('status');

            // 재신청 폼
            Route::get('{id}/reapply', \Jiny\Partner\Http\Controllers\Home\PartnerRegist\ReapplyController::class)
                ->name('reapply');

            // 재신청 제출 (전용 ReapplyStoreController 사용)
            Route::post('{id}/reapply', \Jiny\Partner\Http\Controllers\Home\PartnerRegist\ReapplyStoreController::class)
                ->name('reapply.store');

            // 신청 완전 삭제 (DELETE 메소드, AJAX 전용)
            Route::delete('{id}/cancel', \Jiny\Partner\Http\Controllers\Home\PartnerRegist\DestroyController::class)
                ->name('cancel');

        });

        // Sales Routes
        Route::prefix('sales')->name('sales.')->group(function () {
            Route::get('/', \Jiny\Partner\Http\Controllers\Home\Sales\IndexController::class)->name('index');
            Route::get('/create', \Jiny\Partner\Http\Controllers\Home\Sales\CreateController::class)->name('create');
            Route::post('/', \Jiny\Partner\Http\Controllers\Home\Sales\StoreController::class)->name('store');
            Route::get('/history', \Jiny\Partner\Http\Controllers\Home\Sales\HistoryController::class)->name('history');
            Route::get('/statistics', \Jiny\Partner\Http\Controllers\Home\Sales\StatisticsController::class)->name('statistics');
            Route::get('/{id}', \Jiny\Partner\Http\Controllers\Home\Sales\ShowController::class)->name('show');
            Route::get('/{id}/edit', \Jiny\Partner\Http\Controllers\Home\Sales\EditController::class)->name('edit');
            Route::put('/{id}', \Jiny\Partner\Http\Controllers\Home\Sales\UpdateController::class)->name('update');
            Route::patch('/{id}/approve', [\Jiny\Partner\Http\Controllers\Home\Sales\SalesApprovalController::class, 'approve'])->name('approve');
            Route::patch('/{id}/reject', [\Jiny\Partner\Http\Controllers\Home\Sales\SalesApprovalController::class, 'reject'])->name('reject');
            Route::patch('/{id}/cancel', \Jiny\Partner\Http\Controllers\Home\Sales\CancelController::class)->name('cancel');
            Route::patch('/{id}/cancel/approve', [\Jiny\Partner\Http\Controllers\Home\Sales\CancelApprovalController::class, 'approve'])->name('cancel.approve');
            Route::patch('/{id}/cancel/reject', [\Jiny\Partner\Http\Controllers\Home\Sales\CancelApprovalController::class, 'reject'])->name('cancel.reject');
            Route::patch('/{id}/restore', [\Jiny\Partner\Http\Controllers\Home\Sales\CancelApprovalController::class, 'restore'])->name('restore');
            Route::delete('/{id}', \Jiny\Partner\Http\Controllers\Home\Sales\DestroyController::class)->name('destroy');

            // 고객 검색 API
            Route::get('/customers/search', \Jiny\Partner\Http\Controllers\Home\Sales\CustomerSearchController::class)->name('customers.search');
        });

        // Commission Routes
        Route::prefix('commission')->name('commission.')->group(function () {
            Route::get('/', \Jiny\Partner\Http\Controllers\Home\Commission\IndexController::class)->name('index');
            Route::get('/history', \Jiny\Partner\Http\Controllers\Home\Commission\HistoryController::class)->name('history');
            Route::get('/calculate', \Jiny\Partner\Http\Controllers\Home\Commission\CalculateController::class)->name('calculate');
        });

        // Reviews Routes
        Route::prefix('reviews')->name('reviews.')->group(function () {
            Route::get('/', \Jiny\Partner\Http\Controllers\Home\Reviews\IndexController::class)->name('index');
            Route::get('/received', \Jiny\Partner\Http\Controllers\Home\Reviews\ReceivedController::class)->name('received');
            Route::get('/given', \Jiny\Partner\Http\Controllers\Home\Reviews\GivenController::class)->name('given');
        });

        // Network Routes (파트너 네트워크 트리 구조)
        Route::prefix('network')->name('network.')->group(function () {
            Route::get('/', \Jiny\Partner\Http\Controllers\Home\Network\IndexController::class)->name('index');
            Route::get('/tree', [\Jiny\Partner\Http\Controllers\Home\Network\IndexController::class, 'tree'])->name('tree');
            Route::get('/{id}', \Jiny\Partner\Http\Controllers\Home\Network\DetailController::class)->name('detail');
        });

        // ====================================================================
        // 상위 파트너 승인 시스템 (Partner Approval System)
        // ====================================================================
        Route::prefix('approval')->name('approval.')->group(function () {
            // 상위 파트너 승인 대시보드
            Route::get('/', \Jiny\Partner\Http\Controllers\Home\PartnerApproval\IndexController::class)->name('index');

            // 승인 가능한 신청서 목록 (권한 기반 필터링)
            Route::get('/pending', \Jiny\Partner\Http\Controllers\Home\PartnerApproval\PendingController::class)->name('pending');

            // 하위 파트너 신청서 상세보기
            Route::get('/{id}/detail', \Jiny\Partner\Http\Controllers\Home\PartnerApproval\DetailController::class)->name('detail');

            // 제한적 승인/거부 (권한 범위 내)
            Route::post('/{id}/approve', \Jiny\Partner\Http\Controllers\Home\PartnerApproval\ApproveController::class)->name('approve');
            Route::post('/{id}/reject', \Jiny\Partner\Http\Controllers\Home\PartnerApproval\RejectController::class)->name('reject');

            // 추천 및 후보 관리
            Route::get('/referrals', \Jiny\Partner\Http\Controllers\Home\PartnerApproval\ReferralsController::class)->name('referrals');
            Route::post('/referrals/recommend', \Jiny\Partner\Http\Controllers\Home\PartnerApproval\RecommendController::class)->name('recommend');

            // 승인 한도 및 권한 확인
            Route::get('/limits', \Jiny\Partner\Http\Controllers\Home\PartnerApproval\LimitsController::class)->name('limits');
            Route::get('/permissions', \Jiny\Partner\Http\Controllers\Home\PartnerApproval\PermissionsController::class)->name('permissions');

            // 신청서 삭제 (Home 컨트롤러)
            Route::delete('/{id}', \Jiny\Partner\Http\Controllers\Home\PartnerApproval\DestroyController::class)->name('destroy');
        });

        // ====================================================================
        // 추천인 검색 시스템 (Referrer Search System)
        // ====================================================================
        Route::prefix('search')->name('search.')->group(function () {
            // 이메일로 추천인 검색
            Route::get('/referrer', \Jiny\Partner\Http\Controllers\Home\Search\ReferrerController::class)->name('referrer');
            Route::post('/referrer/verify', \Jiny\Partner\Http\Controllers\Home\Search\VerifyReferrerController::class)->name('referrer.verify');
        });

        // ====================================================================
        // 공용 회원 검색 시스템 (User Search System)
        // ====================================================================
        Route::prefix('users')->name('users.')->group(function () {
            // 통합 회원 검색 API (재사용 가능)
            Route::get('/search', [\Jiny\Partner\Http\Controllers\Home\UserSearchController::class, 'search'])->name('search');
        });

        // ====================================================================
        // 테스트 페이지 (개발용)
        // ====================================================================
        Route::get('/test-customer', function () {
            return view('jiny-partner::home.test-customer');
        })->name('test.customer');

    });

});
