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

        // Dashboard
        Route::get('/', \Jiny\Partner\Http\Controllers\Home\Dashboard\IndexController::class)
            ->name('index');

        // Registration Routes
        Route::prefix('regist')->name('regist.')->group(function () {
            // 신청 메인 페이지
            Route::get('/', \Jiny\Partner\Http\Controllers\Home\PartnerRegist\IndexController::class)
                ->name('index');

            // 신청서 작성 폼
            Route::get('create', \Jiny\Partner\Http\Controllers\Home\PartnerRegist\CreateController::class)
                ->name('create');

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

            // 신청 상태 확인
            Route::get('{id}/status', \Jiny\Partner\Http\Controllers\Home\PartnerRegist\StatusController::class)
                ->name('status');

            // 재신청 폼
            Route::get('{id}/reapply', \Jiny\Partner\Http\Controllers\Home\PartnerRegist\ReapplyController::class)
                ->name('reapply');

            // 재신청 제출 (전용 ReapplyStoreController 사용)
            Route::post('{id}/reapply', \Jiny\Partner\Http\Controllers\Home\PartnerRegist\ReapplyStoreController::class)
                ->name('reapply.store');
        });

        // Sales Routes
        Route::prefix('sales')->name('sales.')->group(function () {
            Route::get('/', \Jiny\Partner\Http\Controllers\Home\Sales\IndexController::class)->name('index');
            Route::get('/history', \Jiny\Partner\Http\Controllers\Home\Sales\HistoryController::class)->name('history');
            Route::get('/statistics', \Jiny\Partner\Http\Controllers\Home\Sales\StatisticsController::class)->name('statistics');
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

        // Network Routes (향후 확장을 위한 구조)
        Route::prefix('network')->name('network.')->group(function () {
            // Route::get('/', 'NetworkController@index')->name('index');
            // Route::get('tree', 'NetworkController@tree')->name('tree');
        });

        // ====================================================================
        // 상위 파트너 승인 시스템 (Partner Approval System)
        // ====================================================================
        Route::prefix('approval')->name('approval.')->group(function () {
            // 상위 파트너 승인 대시보드
            Route::get('/', \Jiny\Partner\Http\Controllers\Home\PartnerApproval\IndexController::class)->name('index');

            // 승인 가능한 신청서 목록 (권한 기반 필터링)
            Route::get('/pending', \Jiny\Partner\Http\Controllers\Home\PartnerApproval\PendingController::class)->name('pending');

            // 하위 파트너 신청서 검토
            Route::get('/{id}/review', \Jiny\Partner\Http\Controllers\Home\PartnerApproval\ReviewController::class)->name('review');

            // 제한적 승인/거부 (권한 범위 내)
            Route::post('/{id}/approve', \Jiny\Partner\Http\Controllers\Home\PartnerApproval\ApproveController::class)->name('approve');
            Route::post('/{id}/reject', \Jiny\Partner\Http\Controllers\Home\PartnerApproval\RejectController::class)->name('reject');

            // 추천 및 후보 관리
            Route::get('/referrals', \Jiny\Partner\Http\Controllers\Home\PartnerApproval\ReferralsController::class)->name('referrals');
            Route::post('/referrals/recommend', \Jiny\Partner\Http\Controllers\Home\PartnerApproval\RecommendController::class)->name('recommend');

            // 승인 한도 및 권한 확인
            Route::get('/limits', \Jiny\Partner\Http\Controllers\Home\PartnerApproval\LimitsController::class)->name('limits');
            Route::get('/permissions', \Jiny\Partner\Http\Controllers\Home\PartnerApproval\PermissionsController::class)->name('permissions');
        });

        // ====================================================================
        // 추천인 검색 시스템 (Referrer Search System)
        // ====================================================================
        Route::prefix('search')->name('search.')->group(function () {
            // 이메일로 추천인 검색
            Route::get('/referrer', \Jiny\Partner\Http\Controllers\Home\Search\ReferrerController::class)->name('referrer');
            Route::post('/referrer/verify', \Jiny\Partner\Http\Controllers\Home\Search\VerifyReferrerController::class)->name('referrer.verify');
        });

    });

});
