<?php

use Illuminate\Support\Facades\Route;
use Jiny\Partner\Http\Controllers\Admin\PartnerTiers\IndexController;
use Jiny\Partner\Http\Controllers\Admin\PartnerTiers\CreateController;
use Jiny\Partner\Http\Controllers\Admin\PartnerTiers\StoreController;
use Jiny\Partner\Http\Controllers\Admin\PartnerTiers\ShowController;
use Jiny\Partner\Http\Controllers\Admin\PartnerTiers\EditController;
use Jiny\Partner\Http\Controllers\Admin\PartnerTiers\UpdateController;
use Jiny\Partner\Http\Controllers\Admin\PartnerTiers\DestroyController;
use Jiny\Partner\Http\Controllers\Admin\PartnerType\IndexController as PartnerTypeIndexController;
use Jiny\Partner\Http\Controllers\Admin\PartnerType\CreateController as PartnerTypeCreateController;
use Jiny\Partner\Http\Controllers\Admin\PartnerType\StoreController as PartnerTypeStoreController;
use Jiny\Partner\Http\Controllers\Admin\PartnerType\ShowController as PartnerTypeShowController;
use Jiny\Partner\Http\Controllers\Admin\PartnerType\EditController as PartnerTypeEditController;
use Jiny\Partner\Http\Controllers\Admin\PartnerType\UpdateController as PartnerTypeUpdateController;
use Jiny\Partner\Http\Controllers\Admin\PartnerType\DestroyController as PartnerTypeDestroyController;
use Jiny\Partner\Http\Controllers\Admin\PartnerUsers\IndexController as PartnerUsersIndexController;
use Jiny\Partner\Http\Controllers\Admin\PartnerUsers\CreateController as PartnerUsersCreateController;
use Jiny\Partner\Http\Controllers\Admin\PartnerUsers\StoreController as PartnerUsersStoreController;
use Jiny\Partner\Http\Controllers\Admin\PartnerUsers\ShowController as PartnerUsersShowController;
use Jiny\Partner\Http\Controllers\Admin\PartnerUsers\EditController as PartnerUsersEditController;
use Jiny\Partner\Http\Controllers\Admin\PartnerUsers\UpdateController as PartnerUsersUpdateController;
use Jiny\Partner\Http\Controllers\Admin\PartnerUsers\DestroyController as PartnerUsersDestroyController;
use Jiny\Partner\Http\Controllers\Admin\PartnerUsers\SearchController as PartnerUsersSearchController;
use Jiny\Partner\Http\Controllers\Admin\DashboardController;

// Partner Sales Controllers
use Jiny\Partner\Http\Controllers\Admin\PartnerSales\IndexController as PartnerSalesIndexController;
use Jiny\Partner\Http\Controllers\Admin\PartnerSales\CreateController as PartnerSalesCreateController;
use Jiny\Partner\Http\Controllers\Admin\PartnerSales\StoreController as PartnerSalesStoreController;
use Jiny\Partner\Http\Controllers\Admin\PartnerSales\ShowController as PartnerSalesShowController;
use Jiny\Partner\Http\Controllers\Admin\PartnerSales\EditController as PartnerSalesEditController;
use Jiny\Partner\Http\Controllers\Admin\PartnerSales\UpdateController as PartnerSalesUpdateController;
use Jiny\Partner\Http\Controllers\Admin\PartnerSales\DestroyController as PartnerSalesDestroyController;
use Jiny\Partner\Http\Controllers\Admin\PartnerSales\StatusController as PartnerSalesStatusController;

/*
|--------------------------------------------------------------------------
| Partner Admin Routes - Hierarchical Structure with Compatibility
|--------------------------------------------------------------------------
|
| 파트너 관리를 위한 계층적 관리자 라우트
| 기존 라우트 이름 호환성을 유지하면서 기능별로 체계적 그룹화
|
*/

// 최상위 Admin 그룹 - 공통 미들웨어 적용
Route::middleware(['web', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::prefix('partner')->name('partner.')->group(function () {

        // ====================================================================
        // Dashboard - 파트너 관리 대시보드
        // ====================================================================
        Route::get('/', DashboardController::class)->name('index');
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        // ====================================================================
        // CRUD Operations - 기본 CRUD 작업들 (기존 라우트 이름 유지)
        // ====================================================================

        // 파트너 등급 CRUD
        Route::prefix('tiers')->name('tiers.')->group(function () {
            Route::get('/', IndexController::class)->name('index');
            Route::get('/create', CreateController::class)->name('create');
            Route::post('/', StoreController::class)->name('store');
            Route::get('/{tier}', ShowController::class)->name('show');
            Route::get('/{tier}/edit', EditController::class)->name('edit');
            Route::put('/{tier}', UpdateController::class)->name('update');
            Route::delete('/{tier}', DestroyController::class)->name('destroy');
        });

        // 파트너 타입 CRUD
        Route::prefix('type')->name('type.')->group(function () {
            Route::get('/', PartnerTypeIndexController::class)->name('index');
            Route::get('/create', PartnerTypeCreateController::class)->name('create');
            Route::post('/', PartnerTypeStoreController::class)->name('store');
            Route::get('/{type}', PartnerTypeShowController::class)->name('show');
            Route::get('/{type}/edit', PartnerTypeEditController::class)->name('edit');
            Route::put('/{type}', PartnerTypeUpdateController::class)->name('update');
            Route::delete('/{type}', PartnerTypeDestroyController::class)->name('destroy');
        });

        // 파트너 회원 CRUD & Search
        Route::prefix('users')->name('users.')->group(function () {
            // 기본 CRUD
            Route::get('/', PartnerUsersIndexController::class)->name('index');
            Route::get('/create', PartnerUsersCreateController::class)->name('create');
            Route::post('/', PartnerUsersStoreController::class)->name('store');
            Route::get('/{user}', PartnerUsersShowController::class)->name('show');
            Route::get('/{user}/edit', PartnerUsersEditController::class)->name('edit');
            Route::put('/{user}', PartnerUsersUpdateController::class)->name('update');
            Route::delete('/{user}', PartnerUsersDestroyController::class)->name('destroy');

            // 계층구조 트리 보기
            Route::get('/{user}/tree', \Jiny\Partner\Http\Controllers\Admin\PartnerUsers\TreeViewController::class)->name('tree');

            // 검색 API (샤딩 지원) - 기존 라우트 이름 유지
            Route::get('/search', PartnerUsersSearchController::class)->name('search');
            Route::get('/search/user-info', [PartnerUsersSearchController::class, 'getUserInfo'])->name('search.user-info');
            Route::get('/search/user-tables', [PartnerUsersSearchController::class, 'getUserTables'])->name('search.user-tables');
        });

        // 파트너 엔지니어 CRUD & Management (현재 미구현)
        /*
        Route::prefix('engineers')->name('engineers.')->group(function () {
            // 기본 CRUD
            Route::get('/', [PartnerEngineerController::class, 'index'])->name('index');
            Route::get('/create', [PartnerEngineerController::class, 'create'])->name('create');
            Route::post('/', [PartnerEngineerController::class, 'store'])->name('store');
            Route::get('/{engineer}', [PartnerEngineerController::class, 'show'])->name('show');
            Route::get('/{engineer}/edit', [PartnerEngineerController::class, 'edit'])->name('edit');
            Route::put('/{engineer}', [PartnerEngineerController::class, 'update'])->name('update');
            Route::delete('/{engineer}', [PartnerEngineerController::class, 'destroy'])->name('destroy');

            // 통계 및 리포트
            Route::get('/statistics', [PartnerEngineerController::class, 'statistics'])->name('statistics');

            // 개별 엔지니어 관리 작업
            Route::prefix('{id}')->group(function () {
                Route::post('/promote-tier', [PartnerEngineerController::class, 'promoteTier'])->name('promote-tier');
                Route::post('/change-status', [PartnerEngineerController::class, 'changeStatus'])->name('change-status');
                Route::get('/performance', [PartnerEngineerController::class, 'performance'])->name('performance');
                Route::get('/service-types', [PartnerEngineerController::class, 'manageServiceTypes'])->name('service-types');
                Route::post('/service-types', [PartnerEngineerController::class, 'updateServiceType'])->name('update-service-type');
            });
        });
        */

        // ====================================================================
        // 파트너 매출 관리 (Partner Sales Management)
        // ====================================================================
        Route::prefix('sales')->name('sales.')->group(function () {
            // 기본 CRUD
            Route::get('/', PartnerSalesIndexController::class)->name('index');
            Route::get('/create', PartnerSalesCreateController::class)->name('create');
            Route::post('/', PartnerSalesStoreController::class)->name('store');
            Route::get('/{sales}', PartnerSalesShowController::class)->name('show');
            Route::get('/{sales}/edit', PartnerSalesEditController::class)->name('edit');
            Route::put('/{sales}', PartnerSalesUpdateController::class)->name('update');
            Route::delete('/{sales}', PartnerSalesDestroyController::class)->name('destroy');

            // 대량 작업
            Route::post('/bulk-destroy', [PartnerSalesDestroyController::class, 'bulkDestroy'])->name('bulk-destroy');
            Route::post('/bulk-commission', function() {
                // 대량 커미션 계산 로직 추가 예정
                return response()->json(['message' => '대량 커미션 계산 기능을 구현 중입니다.']);
            })->name('bulk-commission');

            // 복원 및 완전 삭제 (관리자 전용)
            Route::post('/{id}/restore', [PartnerSalesDestroyController::class, 'restore'])->name('restore');
            Route::delete('/{id}/force-delete', [PartnerSalesDestroyController::class, 'forceDestroy'])->name('force-destroy');

            // 커미션 관련 작업
            Route::prefix('{sales}/commission')->name('commission.')->group(function () {
                Route::post('/calculate', [PartnerSalesStatusController::class, 'calculateCommission'])->name('calculate');

                Route::post('/recalculate', function($salesId) {
                    // 커미션 재계산 로직 추가 예정
                    return redirect()->back()->with('success', '커미션 재계산 기능을 구현 중입니다.');
                })->name('recalculate');

                Route::post('/reverse', function($salesId) {
                    // 커미션 역계산 로직 추가 예정
                    return redirect()->back()->with('success', '커미션 역계산 기능을 구현 중입니다.');
                })->name('reverse');
            });

            // 매출 상태 변경
            Route::prefix('{sales}/status')->name('status.')->group(function () {
                Route::post('/confirm', [PartnerSalesStatusController::class, 'confirm'])->name('confirm');
                Route::post('/cancel', [PartnerSalesStatusController::class, 'cancel'])->name('cancel');
                Route::post('/approve', [PartnerSalesStatusController::class, 'approve'])->name('approve');
                Route::get('/history', [PartnerSalesStatusController::class, 'getStatusHistory'])->name('history');
            });

            // 매출 통계 및 리포트
            Route::prefix('reports')->name('reports.')->group(function () {
                Route::get('/dashboard', function() {
                    return view('jiny-partner::admin.partner-sales.reports.dashboard', [
                        'pageTitle' => '매출 대시보드'
                    ]);
                })->name('dashboard');

                Route::get('/partner/{partner}', function($partnerId) {
                    return view('jiny-partner::admin.partner-sales.reports.partner', [
                        'pageTitle' => '파트너별 매출 리포트',
                        'partnerId' => $partnerId
                    ]);
                })->name('partner');

                Route::get('/commission', function() {
                    return view('jiny-partner::admin.partner-sales.reports.commission', [
                        'pageTitle' => '커미션 리포트'
                    ]);
                })->name('commission');

                Route::get('/export', function() {
                    // 매출 데이터 엑셀 내보내기 로직 추가 예정
                    return response()->json(['message' => '매출 데이터 내보내기 기능을 구현 중입니다.']);
                })->name('export');
            });
        });

        // ====================================================================
        // 파트너 지원서 관리 (Partner Application Management)
        // ====================================================================
        Route::prefix('applications')->name('applications.')->group(function () {
            // 기본 CRUD
            Route::get('/', \Jiny\Partner\Http\Controllers\Admin\PartnerApplication\IndexController::class)->name('index');
            Route::get('/{id}', \Jiny\Partner\Http\Controllers\Admin\PartnerApplication\ShowController::class)->name('show');

            // 통계 및 리포트
            Route::get('/statistics', \Jiny\Partner\Http\Controllers\Admin\PartnerApplication\StatisticsController::class)->name('statistics');
            Route::get('/pending', \Jiny\Partner\Http\Controllers\Admin\PartnerApplication\PendingController::class)->name('pending');
            Route::get('/interviews', \Jiny\Partner\Http\Controllers\Admin\PartnerApplication\InterviewsController::class)->name('interviews');

            // 일괄 처리
            Route::post('/bulk-action', \Jiny\Partner\Http\Controllers\Admin\PartnerApplication\BulkActionController::class)->name('bulk-action');

            // 개별 지원서 관리
            Route::prefix('{id}')->group(function () {
                Route::post('/approve', \Jiny\Partner\Http\Controllers\Admin\PartnerApplication\ApproveController::class)->name('approve');
                Route::post('/reject', \Jiny\Partner\Http\Controllers\Admin\PartnerApplication\RejectController::class)->name('reject');
                Route::post('/schedule-interview', \Jiny\Partner\Http\Controllers\Admin\PartnerApplication\ScheduleInterviewController::class)->name('schedule-interview');
                Route::post('/interview-feedback', \Jiny\Partner\Http\Controllers\Admin\PartnerApplication\InterviewFeedbackController::class)->name('interview-feedback');
                Route::post('/change-status', \Jiny\Partner\Http\Controllers\Admin\PartnerApplication\ChangeStatusController::class)->name('change-status');
            });
        });

        // ====================================================================
        // 파트너 승인 관리 (Partner Approval Management)
        // ====================================================================
        Route::prefix('approval')->name('approval.')->group(function () {
            // 기본 CRUD 및 통계
            Route::get('/', \Jiny\Partner\Http\Controllers\Admin\PartnerApproval\IndexController::class)->name('index');
            Route::get('/statistics', \Jiny\Partner\Http\Controllers\Admin\PartnerApproval\StatisticsController::class)->name('statistics');
            Route::get('/reports', \Jiny\Partner\Http\Controllers\Admin\PartnerApproval\ReportsController::class)->name('reports');

            // 개별 신청서 관리
            Route::get('/{id}', \Jiny\Partner\Http\Controllers\Admin\PartnerApproval\ShowController::class)->name('show');
            Route::get('/{id}/documents', \Jiny\Partner\Http\Controllers\Admin\PartnerApproval\DocumentsController::class)->name('documents');
            Route::get('/{id}/referrer', \Jiny\Partner\Http\Controllers\Admin\PartnerApproval\ReferrerController::class)->name('referrer');

            // 승인 프로세스 관리
            Route::post('/{id}/review', \Jiny\Partner\Http\Controllers\Admin\PartnerApproval\ReviewController::class)->name('review');
            Route::post('/{id}/approve', \Jiny\Partner\Http\Controllers\Admin\PartnerApproval\ApproveController::class)->name('approve');
            Route::post('/{id}/reject', \Jiny\Partner\Http\Controllers\Admin\PartnerApproval\RejectController::class)->name('reject');
            Route::put('/{id}/status', \Jiny\Partner\Http\Controllers\Admin\PartnerApproval\StatusController::class)->name('status');

            // 면접 관리
            Route::post('/{id}/interview/schedule', \Jiny\Partner\Http\Controllers\Admin\PartnerApproval\ScheduleInterviewController::class)->name('interview.schedule');
            Route::put('/{id}/interview/update', \Jiny\Partner\Http\Controllers\Admin\PartnerApproval\UpdateInterviewController::class)->name('interview.update');
            Route::post('/{id}/interview/complete', \Jiny\Partner\Http\Controllers\Admin\PartnerApproval\CompleteInterviewController::class)->name('interview.complete');

            // 대량 처리
            Route::post('/bulk-approve', \Jiny\Partner\Http\Controllers\Admin\PartnerApproval\BulkApproveController::class)->name('bulk-approve');
            Route::post('/bulk-reject', \Jiny\Partner\Http\Controllers\Admin\PartnerApproval\BulkRejectController::class)->name('bulk-reject');
        });

        // ====================================================================
        // 파트너 네트워크 관리 (Partner Network Management) - MLM 구조
        // ====================================================================
        Route::prefix('network')->name('network.')->group(function () {

            // 네트워크 트리 구조 조회
            Route::get('/tree', \Jiny\Partner\Http\Controllers\Admin\PartnerNetwork\TreeViewController::class)->name('tree');

            // 파트너 모집 관리
            Route::prefix('recruitment')->name('recruitment.')->group(function () {
                Route::get('/', [\Jiny\Partner\Http\Controllers\Admin\PartnerNetwork\RecruitmentController::class, 'index'])->name('index');
                Route::post('/recruit', [\Jiny\Partner\Http\Controllers\Admin\PartnerNetwork\RecruitmentController::class, 'recruit'])->name('recruit');
                Route::post('/bulk-recruit', [\Jiny\Partner\Http\Controllers\Admin\PartnerNetwork\RecruitmentController::class, 'bulkRecruit'])->name('bulk-recruit');
                Route::delete('/relationship/{id}', [\Jiny\Partner\Http\Controllers\Admin\PartnerNetwork\RecruitmentController::class, 'removeRelationship'])->name('remove-relationship');
            });

            // 커미션 분배 관리
            Route::prefix('commission')->name('commission.')->group(function () {
                Route::get('/', [\Jiny\Partner\Http\Controllers\Admin\PartnerNetwork\CommissionController::class, 'index'])->name('index');
                Route::get('/{id}', [\Jiny\Partner\Http\Controllers\Admin\PartnerNetwork\CommissionController::class, 'show'])->name('show');
                Route::post('/calculate', [\Jiny\Partner\Http\Controllers\Admin\PartnerNetwork\CommissionController::class, 'calculate'])->name('calculate');
                Route::post('/bulk-process', [\Jiny\Partner\Http\Controllers\Admin\PartnerNetwork\CommissionController::class, 'bulkProcess'])->name('bulk-process');
                Route::get('/partner/{id}/summary', [\Jiny\Partner\Http\Controllers\Admin\PartnerNetwork\CommissionController::class, 'partnerSummary'])->name('partner-summary');
            });

            // 네트워크 통계 및 분석
            Route::prefix('analytics')->name('analytics.')->group(function () {
                Route::get('/dashboard', function() {
                    return view('jiny-partner::admin.partner-network.analytics.dashboard', [
                        'pageTitle' => '네트워크 분석 대시보드'
                    ]);
                })->name('dashboard');

                Route::get('/performance', function() {
                    return view('jiny-partner::admin.partner-network.analytics.performance', [
                        'pageTitle' => '성과 분석'
                    ]);
                })->name('performance');

                Route::get('/genealogy', function() {
                    return view('jiny-partner::admin.partner-network.analytics.genealogy', [
                        'pageTitle' => '계보 분석'
                    ]);
                })->name('genealogy');
            });

            // 계층별 관리 도구
            Route::prefix('hierarchy')->name('hierarchy.')->group(function () {
                Route::get('/management', function() {
                    return view('jiny-partner::admin.partner-network.hierarchy.management', [
                        'pageTitle' => '계층 관리'
                    ]);
                })->name('management');

                Route::post('/move-partner', function() {
                    // 파트너 이동 로직
                })->name('move-partner');

                Route::post('/restructure', function() {
                    // 구조 재편 로직
                })->name('restructure');
            });

        });

    });
});

/*
|--------------------------------------------------------------------------
| Future API Routes
|--------------------------------------------------------------------------
|
| 파트너 앱이나 외부 시스템과의 연동을 위한 API 라우트
| RESTful API 패턴으로 구성될 예정
|
*/

// Route::middleware(['api', 'auth:sanctum'])->prefix('api/partner')->name('api.partner.')->group(function () {
//
//     // ====================================================================
//     // Authentication & Profile Management
//     // ====================================================================
//     Route::prefix('auth')->name('auth.')->group(function () {
//         Route::post('/login', [AuthController::class, 'login'])->name('login');
//         Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
//         Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
//         Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
//     });
//
//     // ====================================================================
//     // Job Management
//     // ====================================================================
//     Route::prefix('jobs')->name('jobs.')->group(function () {
//         Route::get('/', [JobController::class, 'index'])->name('index');
//         Route::get('/{id}', [JobController::class, 'show'])->name('show');
//         Route::post('/{id}/accept', [JobController::class, 'accept'])->name('accept');
//         Route::post('/{id}/complete', [JobController::class, 'complete'])->name('complete');
//         Route::post('/{id}/cancel', [JobController::class, 'cancel'])->name('cancel');
//     });
//
//     // ====================================================================
//     // Performance & Statistics
//     // ====================================================================
//     Route::prefix('performance')->name('performance.')->group(function () {
//         Route::get('/dashboard', [PerformanceController::class, 'dashboard'])->name('dashboard');
//         Route::get('/earnings', [PerformanceController::class, 'earnings'])->name('earnings');
//         Route::get('/ratings', [PerformanceController::class, 'ratings'])->name('ratings');
//     });
//
//     // ====================================================================
//     // Notifications & Messages
//     // ====================================================================
//     Route::prefix('notifications')->name('notifications.')->group(function () {
//         Route::get('/', [NotificationController::class, 'index'])->name('index');
//         Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
//         Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
//     });
// });