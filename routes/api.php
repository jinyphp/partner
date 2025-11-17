<?php

use Illuminate\Support\Facades\Route;
use Jiny\Partner\Http\Controllers\Api\UserSearchController;

/*
|--------------------------------------------------------------------------
| Partner API Routes
|--------------------------------------------------------------------------
|
| 파트너 관련 API 라우트
| 샤딩된 사용자 검색, 파트너 정보 조회 등의 API 제공
|
*/

// API 미들웨어 적용 - v1 API
Route::middleware(['api'])->prefix('api/partner/v1')->name('api.partner.v1.')->group(function () {

    // ====================================================================
    // 샤딩된 사용자 검색 API
    // ====================================================================
    Route::prefix('users')->name('users.')->group(function () {

        // 이메일/이름으로 샤딩된 회원 검색
        // GET /api/partner/v1/users/search?query=test&limit=20
        Route::get('/search', [UserSearchController::class, 'search'])->name('search');

        // UUID로 특정 회원 조회
        // GET /api/partner/v1/users/find-by-uuid?uuid=xxxx-xxxx-xxxx
        Route::get('/find-by-uuid', [UserSearchController::class, 'findByUuid'])->name('find-by-uuid');

        // 샤딩된 테이블 정보 조회
        // GET /api/partner/v1/users/tables
        Route::get('/tables', [UserSearchController::class, 'getTables'])->name('tables');
    });

});

/*
|--------------------------------------------------------------------------
| 관리자 전용 API Routes
|--------------------------------------------------------------------------
|
| 관리자 인증이 필요한 API 라우트
|
*/

// 관리자 API 미들웨어 적용 (웹 세션 기반) - v1 API
Route::middleware(['web', 'admin'])->prefix('api/admin/partner/v1')->name('api.admin.partner.v1.')->group(function () {

    // 관리자용 사용자 검색 (기존 SearchController 재사용)
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/search', \Jiny\Partner\Http\Controllers\Admin\PartnerUsers\SearchController::class)->name('search');

        // 파트너 사용자 상태 변경 API
        Route::put('/{id}/status', [\Jiny\Partner\Http\Controllers\Admin\PartnerUsers\StatusController::class, 'update'])->name('status.update');
        Route::get('/{id}/status', [\Jiny\Partner\Http\Controllers\Admin\PartnerUsers\StatusController::class, 'show'])->name('status.show');

        // 파트너 코드 관리 API (기존 라우트 - 호환성 유지)
        Route::post('/{id}/partner-code', [\Jiny\Partner\Http\Controllers\Api\Partner\CodeController::class, 'generate'])->name('partner-code.generate');
        Route::delete('/{id}/partner-code', [\Jiny\Partner\Http\Controllers\Api\Partner\CodeController::class, 'delete'])->name('partner-code.delete');
    });

    // 파트너 검색 API (재사용 가능)
    Route::prefix('partners')->name('partners.')->group(function () {
        Route::get('/search', \Jiny\Partner\Http\Controllers\Api\Partner\SearchController::class)->name('search');
    });

    // 커미션 관리 API
    Route::prefix('commissions')->name('commissions.')->group(function () {
        Route::post('/calculate/{salesId}', \Jiny\Partner\Http\Controllers\Api\Partner\Commission\CalculateController::class)->name('calculate');
    });

});

/*
|--------------------------------------------------------------------------
| 공개 API Routes (인증 불필요)
|--------------------------------------------------------------------------
|
| 인증이 필요하지 않은 공개 API
| 예: 파트너 프로그램 소개, 통계 등
|
*/

Route::middleware(['api'])->prefix('api/public/partner/v1')->name('api.public.partner.v1.')->group(function () {

    // 파트너 프로그램 기본 정보
    Route::get('/info', function () {
        return response()->json([
            'success' => true,
            'message' => '파트너 프로그램 정보',
            'data' => [
                'name' => '파트너 프로그램',
                'description' => '함께 성장하는 파트너십',
                'version' => '1.0.0'
            ]
        ]);
    })->name('info');

    // 샤딩 테이블 상태 (통계만)
    Route::get('/stats/tables', function () {
        try {
            $stats = [];
            $tables = ['users_001', 'users_002'];

            foreach ($tables as $tableName) {
                try {
                    $count = \Illuminate\Support\Facades\DB::table($tableName)
                        ->whereNull('deleted_at')
                        ->count();
                    $stats[] = [
                        'table' => $tableName,
                        'count' => $count
                    ];
                } catch (\Exception $e) {
                    $stats[] = [
                        'table' => $tableName,
                        'count' => 0,
                        'error' => 'Table not accessible'
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Stats not available'
            ], 500);
        }
    })->name('stats.tables');

});

/*
|--------------------------------------------------------------------------
| 파트너 코드 관리를 위한 간편 API Routes
|--------------------------------------------------------------------------
|
| 뷰에서 사용하는 간편한 API 경로
| 관리자 인증이 필요함
|
*/

// 파트너 코드 관리를 위한 간편 API (웹 세션 기반 인증)
Route::middleware(['web', 'admin'])->prefix('api/partner/code')->group(function () {
    Route::post('/generate/{id}', [\Jiny\Partner\Http\Controllers\Api\Partner\CodeController::class, 'generate'])->name('api.partner.code.generate');
    Route::delete('/delete/{id}', [\Jiny\Partner\Http\Controllers\Api\Partner\CodeController::class, 'delete'])->name('api.partner.code.delete');
    Route::get('/test-generate/{id}', [\Jiny\Partner\Http\Controllers\Api\Partner\CodeController::class, 'testGenerate'])->name('api.partner.code.test-generate');
});