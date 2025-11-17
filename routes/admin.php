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

        // 파트너 현황 대시보드
        Route::get('/partner-dashboard', [\Jiny\Partner\Http\Controllers\Admin\PartnerDashboardController::class, 'index'])->name('partner-dashboard');

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

            // 파트너 타입별 실적 관리 (Partner Type Target Management)
            // 주의: 구체적인 경로를 먼저 정의해야 {type} 매개변수와 충돌하지 않음
            Route::get('/target', \Jiny\Partner\Http\Controllers\Admin\PartnerTypeTarget\IndexController::class)->name('target');
            Route::get('/target/analytics', \Jiny\Partner\Http\Controllers\Admin\PartnerTypeTarget\AnalyticsController::class)->name('target.analytics');
            Route::get('/target/{type_id}/detail', \Jiny\Partner\Http\Controllers\Admin\PartnerTypeTarget\DetailController::class)->name('target.detail');
            Route::post('/target/{type_id}/update-goal', \Jiny\Partner\Http\Controllers\Admin\PartnerTypeTarget\UpdateGoalController::class)->name('target.update-goal');

            // {type} 매개변수 라우트는 마지막에 배치 (와일드카드 라우트)
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
            Route::patch('/{user}', PartnerUsersUpdateController::class)->name('patch');
            Route::delete('/{user}', PartnerUsersDestroyController::class)->name('destroy');

            // 계층구조 트리 보기
            Route::get('/{user}/tree', \Jiny\Partner\Http\Controllers\Admin\PartnerUsers\TreeViewController::class)->name('tree');

            // 검색 API (샤딩 지원) - 기존 라우트 이름 유지
            Route::get('/search', PartnerUsersSearchController::class)->name('search');
            Route::get('/search/user-info', [PartnerUsersSearchController::class, 'getUserInfo'])->name('search.user-info');
            Route::get('/search/user-tables', [PartnerUsersSearchController::class, 'getUserTables'])->name('search.user-tables');

            // 파트너 코드 관리는 API 경로로 이동됨 (/api/partner/code/)
        });

        // 파트너 코드 전체 관리
        Route::prefix('codes')->name('codes.')->group(function () {
            Route::get('/', [\Jiny\Partner\Http\Controllers\Admin\PartnerCodes\IndexController::class, 'index'])->name('index');
            Route::post('/bulk-generate', [\Jiny\Partner\Http\Controllers\Admin\PartnerCodes\BulkGenerateController::class, 'generate'])->name('bulk-generate');
            Route::post('/bulk-delete', [\Jiny\Partner\Http\Controllers\Admin\PartnerCodes\BulkDeleteController::class, 'delete'])->name('bulk-delete');
            Route::get('/statistics', [\Jiny\Partner\Http\Controllers\Admin\PartnerCodes\StatisticsController::class, 'index'])->name('statistics');
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
        // 파트너 동적 목표 관리 (Partner Dynamic Targets Management)
        // ====================================================================
        Route::prefix('targets')->name('targets.')->group(function () {
            // 기본 CRUD
            Route::get('/', [\Jiny\Partner\Http\Controllers\Admin\PartnerDynamicTargetsController::class, 'index'])->name('index');
            Route::get('/create', [\Jiny\Partner\Http\Controllers\Admin\PartnerDynamicTargetsController::class, 'create'])->name('create');
            Route::post('/', [\Jiny\Partner\Http\Controllers\Admin\PartnerDynamicTargetsController::class, 'store'])->name('store');
            Route::get('/{target}', [\Jiny\Partner\Http\Controllers\Admin\PartnerDynamicTargetsController::class, 'show'])->name('show');
            Route::get('/{target}/edit', [\Jiny\Partner\Http\Controllers\Admin\PartnerDynamicTargetsController::class, 'edit'])->name('edit');
            Route::put('/{target}', [\Jiny\Partner\Http\Controllers\Admin\PartnerDynamicTargetsController::class, 'update'])->name('update');
            Route::delete('/{target}', [\Jiny\Partner\Http\Controllers\Admin\PartnerDynamicTargetsController::class, 'destroy'])->name('destroy');

            // 목표 관리 작업
            Route::prefix('{target}')->group(function () {
                Route::post('/approve', [\Jiny\Partner\Http\Controllers\Admin\PartnerDynamicTargetsController::class, 'approve'])->name('approve');
                Route::post('/activate', [\Jiny\Partner\Http\Controllers\Admin\PartnerDynamicTargetsController::class, 'activate'])->name('activate');
            });
        });

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
            // 기본 CRUD - 관리자가 직접 신청서 등록/수정할 수 있는 기능
            Route::get('/', \Jiny\Partner\Http\Controllers\Admin\PartnerApplicationController::class . '@index')->name('index');
            Route::get('/create', \Jiny\Partner\Http\Controllers\Admin\PartnerApplication\CreateController::class)->name('create');
            Route::post('/', \Jiny\Partner\Http\Controllers\Admin\PartnerApplication\StoreController::class)->name('store');
            Route::get('/{id}', \Jiny\Partner\Http\Controllers\Admin\PartnerApplicationController::class . '@show')->name('show');
            Route::get('/{id}/edit', \Jiny\Partner\Http\Controllers\Admin\PartnerApplicationController::class . '@edit')->name('edit');
            Route::put('/{id}', \Jiny\Partner\Http\Controllers\Admin\PartnerApplication\UpdateController::class)->name('update');
            Route::delete('/{id}', \Jiny\Partner\Http\Controllers\Admin\PartnerApplication\DestroyController::class)->name('destroy');

            // 통계 및 리포트
            Route::get('/statistics', \Jiny\Partner\Http\Controllers\Admin\PartnerApplication\StatisticsController::class)->name('statistics');
            Route::get('/pending', \Jiny\Partner\Http\Controllers\Admin\PartnerApplication\PendingController::class)->name('pending');
            Route::get('/interviews', \Jiny\Partner\Http\Controllers\Admin\PartnerApplication\InterviewsController::class)->name('interviews');

            // 일괄 처리
            Route::post('/bulk-action', \Jiny\Partner\Http\Controllers\Admin\PartnerApplication\BulkActionController::class)->name('bulk-action');

            // 개별 지원서 관리 (승인/거부 프로세스)
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
            Route::delete('/{id}', \Jiny\Partner\Http\Controllers\Admin\PartnerApproval\DestroyController::class)->name('destroy');

            // 승인 프로세스 관리
            Route::post('/{id}/review', \Jiny\Partner\Http\Controllers\Admin\PartnerApproval\ReviewController::class)->name('review');
            Route::post('/{id}/approve', \Jiny\Partner\Http\Controllers\Admin\PartnerApproval\ApproveController::class)->name('approve');
            Route::post('/{id}/reject', \Jiny\Partner\Http\Controllers\Admin\PartnerApproval\RejectController::class)->name('reject');
            Route::post('/{id}/revoke', \Jiny\Partner\Http\Controllers\Admin\PartnerApproval\RevokeController::class)->name('revoke');
            Route::put('/{id}/status', \Jiny\Partner\Http\Controllers\Admin\PartnerApproval\StatusController::class)->name('status');

            // 면접 관리
            Route::post('/{id}/interview/schedule', \Jiny\Partner\Http\Controllers\Admin\PartnerApproval\InterviewController::class)->name('interview.schedule');
            Route::put('/{id}/interview/update', [\Jiny\Partner\Http\Controllers\Admin\PartnerApproval\InterviewController::class, 'update'])->name('interview.update');
            Route::post('/{id}/interview/complete', \Jiny\Partner\Http\Controllers\Admin\PartnerApproval\CompleteInterviewController::class)->name('interview.complete');

            // 대량 처리
            Route::post('/bulk-approve', \Jiny\Partner\Http\Controllers\Admin\PartnerApproval\BulkApproveController::class)->name('bulk-approve');
            Route::post('/bulk-reject', \Jiny\Partner\Http\Controllers\Admin\PartnerApproval\BulkRejectController::class)->name('bulk-reject');
        });

        // ====================================================================
        // 파트너 면접 평가 관리 (Partner Interview Evaluations Management)
        // 주의: interview/{id} 라우트보다 먼저 정의해야 함 (라우트 충돌 방지)
        // ====================================================================
        Route::prefix('interview/evaluations')->name('interview.evaluations.')->group(function () {
            // 기본 CRUD
            Route::get('/', [\Jiny\Partner\Http\Controllers\Admin\PartnerInterviewEvaluationsController::class, 'index'])->name('index');
            Route::get('/create', [\Jiny\Partner\Http\Controllers\Admin\PartnerInterviewEvaluationsController::class, 'create'])->name('create');
            Route::post('/', [\Jiny\Partner\Http\Controllers\Admin\PartnerInterviewEvaluationsController::class, 'store'])->name('store');
            Route::get('/{id}', [\Jiny\Partner\Http\Controllers\Admin\PartnerInterviewEvaluationsController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [\Jiny\Partner\Http\Controllers\Admin\PartnerInterviewEvaluationsController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\Jiny\Partner\Http\Controllers\Admin\PartnerInterviewEvaluationsController::class, 'update'])->name('update');
            Route::delete('/{id}', [\Jiny\Partner\Http\Controllers\Admin\PartnerInterviewEvaluationsController::class, 'destroy'])->name('destroy');
        });

        // ====================================================================
        // 파트너 면접 관리 (Partner Interview Management)
        // ====================================================================
        Route::prefix('interview')->name('interview.')->group(function () {
            // 기본 CRUD
            Route::get('/', \Jiny\Partner\Http\Controllers\Admin\PartnerInterview\IndexController::class)->name('index');
            Route::get('/create', \Jiny\Partner\Http\Controllers\Admin\PartnerInterview\CreateController::class)->name('create');
            Route::post('/', \Jiny\Partner\Http\Controllers\Admin\PartnerInterview\StoreController::class)->name('store');
            Route::get('/{id}', \Jiny\Partner\Http\Controllers\Admin\PartnerInterview\ShowController::class)->name('show');
            Route::get('/{id}/edit', \Jiny\Partner\Http\Controllers\Admin\PartnerInterview\EditController::class)->name('edit');
            Route::put('/{id}', \Jiny\Partner\Http\Controllers\Admin\PartnerInterview\UpdateController::class)->name('update');
            Route::delete('/{id}', \Jiny\Partner\Http\Controllers\Admin\PartnerInterview\DestroyController::class)->name('destroy');

            // 특정 신청서에 대한 면접 생성
            Route::get('/application/{applicationId}/create', \Jiny\Partner\Http\Controllers\Admin\PartnerInterview\CreateController::class)->name('application.create');

            // 면접 상태 관리
            Route::post('/{id}/start', \Jiny\Partner\Http\Controllers\Admin\PartnerInterview\StatusController::class . '@start')->name('start');
            Route::post('/{id}/complete', \Jiny\Partner\Http\Controllers\Admin\PartnerInterview\StatusController::class . '@complete')->name('complete');
            Route::post('/{id}/cancel', \Jiny\Partner\Http\Controllers\Admin\PartnerInterview\StatusController::class . '@cancel')->name('cancel');
            Route::post('/{id}/reschedule', \Jiny\Partner\Http\Controllers\Admin\PartnerInterview\StatusController::class . '@reschedule')->name('reschedule');

            // 면접 평가 관리
            Route::post('/{id}/evaluation', \Jiny\Partner\Http\Controllers\Admin\PartnerInterview\EvaluationController::class . '@store')->name('evaluation.store');
            Route::put('/{id}/evaluation', \Jiny\Partner\Http\Controllers\Admin\PartnerInterview\EvaluationController::class . '@update')->name('evaluation.update');

            // 통계 및 리포트
            Route::get('/statistics', \Jiny\Partner\Http\Controllers\Admin\PartnerInterview\StatisticsController::class)->name('statistics');
            Route::get('/calendar', \Jiny\Partner\Http\Controllers\Admin\PartnerInterview\CalendarController::class)->name('calendar');

            // 일괄 처리
            Route::post('/bulk-action', \Jiny\Partner\Http\Controllers\Admin\PartnerInterview\BulkActionController::class)->name('bulk-action');
        });

        // ====================================================================
        // 파트너 활동 로그 관리 (Partner Activity Logs Management)
        // ====================================================================
        Route::prefix('activity/logs')->name('activity.logs.')->group(function () {
            // 기본 CRUD
            Route::get('/', [\Jiny\Partner\Http\Controllers\Admin\PartnerActivityLogsController::class, 'index'])->name('index');
            Route::get('/{id}', [\Jiny\Partner\Http\Controllers\Admin\PartnerActivityLogsController::class, 'show'])->name('show');
            Route::post('/', [\Jiny\Partner\Http\Controllers\Admin\PartnerActivityLogsController::class, 'store'])->name('store');

            // 특정 파트너/신청서별 로그 조회
            Route::get('/partner/{partnerId}', [\Jiny\Partner\Http\Controllers\Admin\PartnerActivityLogsController::class, 'getPartnerLogs'])->name('partner');
            Route::get('/application/{applicationId}', [\Jiny\Partner\Http\Controllers\Admin\PartnerActivityLogsController::class, 'getApplicationLogs'])->name('application');

            // 통계 및 분석
            Route::get('/statistics', [\Jiny\Partner\Http\Controllers\Admin\PartnerActivityLogsController::class, 'stats'])->name('stats');
            Route::get('/export', [\Jiny\Partner\Http\Controllers\Admin\PartnerActivityLogsController::class, 'export'])->name('export');
        });

        // ====================================================================
        // 파트너 알림 관리 (Partner Notifications Management)
        // ====================================================================
        Route::prefix('notifications')->name('notifications.')->group(function () {
            // 기본 CRUD
            Route::get('/', [\Jiny\Partner\Http\Controllers\Admin\PartnerNotificationsController::class, 'index'])->name('index');
            Route::get('/create', [\Jiny\Partner\Http\Controllers\Admin\PartnerNotificationsController::class, 'create'])->name('create');
            Route::post('/', [\Jiny\Partner\Http\Controllers\Admin\PartnerNotificationsController::class, 'store'])->name('store');
            Route::get('/{id}', [\Jiny\Partner\Http\Controllers\Admin\PartnerNotificationsController::class, 'show'])->name('show');
            Route::delete('/{id}', [\Jiny\Partner\Http\Controllers\Admin\PartnerNotificationsController::class, 'destroy'])->name('destroy');

            // 읽음 처리
            Route::post('/{id}/read', [\Jiny\Partner\Http\Controllers\Admin\PartnerNotificationsController::class, 'markAsRead'])->name('read');
            Route::post('/mark-all-read', [\Jiny\Partner\Http\Controllers\Admin\PartnerNotificationsController::class, 'markAllAsRead'])->name('mark-all-read');

            // 사용자별 알림 조회
            Route::get('/user/{userId}', [\Jiny\Partner\Http\Controllers\Admin\PartnerNotificationsController::class, 'getUserNotifications'])->name('user');

            // 통계 및 분석
            Route::get('/statistics', [\Jiny\Partner\Http\Controllers\Admin\PartnerNotificationsController::class, 'statistics'])->name('statistics');
            Route::get('/export', [\Jiny\Partner\Http\Controllers\Admin\PartnerNotificationsController::class, 'export'])->name('export');
        });

        // ====================================================================
        // 파트너 네트워크 관리 (Partner Network Management) - MLM 구조
        // ====================================================================
        Route::prefix('network')->name('network.')->group(function () {

            // 네트워크 트리 구조 조회 (단계별 드릴다운)
            Route::get('/tree', \Jiny\Partner\Http\Controllers\Admin\PartnerNetwork\TreeController::class . '@index')->name('tree');
            Route::get('/tree/children/{partnerId}', \Jiny\Partner\Http\Controllers\Admin\PartnerNetwork\TreeController::class . '@children')->name('children');


            // 커미션 분배 관리
            Route::prefix('commission')->name('commission.')->group(function () {
                Route::get('/', [\Jiny\Partner\Http\Controllers\Admin\PartnerNetwork\CommissionController::class, 'index'])->name('index');
                Route::get('/{id}', [\Jiny\Partner\Http\Controllers\Admin\PartnerNetwork\CommissionController::class, 'show'])->name('show');
                Route::post('/calculate', [\Jiny\Partner\Http\Controllers\Admin\PartnerNetwork\CommissionController::class, 'calculate'])->name('calculate');
                Route::post('/bulk-process', [\Jiny\Partner\Http\Controllers\Admin\PartnerNetwork\CommissionController::class, 'bulkProcess'])->name('bulk-process');
                Route::get('/partner/{id}/summary', [\Jiny\Partner\Http\Controllers\Admin\PartnerNetwork\CommissionController::class, 'partnerSummary'])->name('partner-summary');

                // 커미션 관리 (Quick Actions)
                Route::put('/{id}/update', [\Jiny\Partner\Http\Controllers\Admin\PartnerNetwork\CommissionController::class, 'update'])->name('update');
                Route::post('/{id}/cancel', [\Jiny\Partner\Http\Controllers\Admin\PartnerNetwork\CommissionController::class, 'cancel'])->name('cancel');
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

            });


        });

        // ====================================================================
        // 파트너 성과 지표 관리 (Partner Performance Metrics Management)
        // ====================================================================
        Route::prefix('performance/metrics')->name('performance.metrics.')->group(function () {
            // 기본 CRUD
            Route::get('/', [\Jiny\Partner\Http\Controllers\Admin\PartnerPerformanceMetricsController::class, 'index'])->name('index');
            Route::get('/create', [\Jiny\Partner\Http\Controllers\Admin\PartnerPerformanceMetricsController::class, 'create'])->name('create');
            Route::post('/', [\Jiny\Partner\Http\Controllers\Admin\PartnerPerformanceMetricsController::class, 'store'])->name('store');
            Route::get('/{id}', [\Jiny\Partner\Http\Controllers\Admin\PartnerPerformanceMetricsController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [\Jiny\Partner\Http\Controllers\Admin\PartnerPerformanceMetricsController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\Jiny\Partner\Http\Controllers\Admin\PartnerPerformanceMetricsController::class, 'update'])->name('update');
            Route::delete('/{id}', [\Jiny\Partner\Http\Controllers\Admin\PartnerPerformanceMetricsController::class, 'destroy'])->name('destroy');
        });

        // ====================================================================
        // 파트너 지급 관리 (Partner Payments Management)
        // ====================================================================
        Route::prefix('payments')->name('payments.')->group(function () {
            // 기본 CRUD
            Route::get('/', [\Jiny\Partner\Http\Controllers\Admin\PartnerPaymentsController::class, 'index'])->name('index');
            Route::get('/create', [\Jiny\Partner\Http\Controllers\Admin\PartnerPaymentsController::class, 'create'])->name('create');
            Route::post('/', [\Jiny\Partner\Http\Controllers\Admin\PartnerPaymentsController::class, 'store'])->name('store');
            Route::get('/{id}', [\Jiny\Partner\Http\Controllers\Admin\PartnerPaymentsController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [\Jiny\Partner\Http\Controllers\Admin\PartnerPaymentsController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\Jiny\Partner\Http\Controllers\Admin\PartnerPaymentsController::class, 'update'])->name('update');
            Route::delete('/{id}', [\Jiny\Partner\Http\Controllers\Admin\PartnerPaymentsController::class, 'destroy'])->name('destroy');

            // 상태 변경 액션
            Route::post('/{id}/approve', [\Jiny\Partner\Http\Controllers\Admin\PartnerPaymentsController::class, 'approve'])->name('approve');
            Route::post('/{id}/process', [\Jiny\Partner\Http\Controllers\Admin\PartnerPaymentsController::class, 'process'])->name('process');
            Route::post('/{id}/complete', [\Jiny\Partner\Http\Controllers\Admin\PartnerPaymentsController::class, 'complete'])->name('complete');
            Route::post('/{id}/cancel', [\Jiny\Partner\Http\Controllers\Admin\PartnerPaymentsController::class, 'cancel'])->name('cancel');
        });

        // ====================================================================
        // Country Analytics - 국가별 파트너 현황 및 매출 분석
        // ====================================================================
        Route::prefix('country')->name('country.')->group(function () {
            // 국가별 파트너 현황 대시보드
            Route::get('/', \Jiny\Partner\Http\Controllers\Admin\PartnerCountry\IndexController::class)
                ->name('index');

            // 국가별 상세 분석 (향후 확장)
            Route::get('/{country}/details', function($country) {
                return view('jiny-partner::admin.country.details', [
                    'country' => $country,
                    'pageTitle' => '국가별 상세 분석'
                ]);
            })->name('details');

            // 데이터 내보내기
            Route::get('/export', function() {
                // Excel 내보내기 로직 구현 예정
                return response()->json(['message' => '데이터 내보내기 기능 구현 중']);
            })->name('export');
        });

    });
});