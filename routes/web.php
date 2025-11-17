<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Partner Web Routes
|--------------------------------------------------------------------------
|
| 파트너 시스템의 공개 웹 페이지 라우트입니다.
|
*/

// 파트너 공개 웹사이트 라우트
Route::prefix('partners')->name('partner.www.')->group(function () {

    // 메인 홈페이지
    Route::get('/', function () {
        return view('jiny-partner::www.index');
    })->name('index');

    // 파트너 소개
    Route::get('/about', function () {
        return view('jiny-partner::www.about');
    })->name('about');

    // 파트너 등급 시스템
    Route::get('/tiers', function () {
        return view('jiny-partner::www.tiers');
    })->name('tiers');

    // 성과 관리 시스템
    Route::get('/performance', function () {
        return view('jiny-partner::www.performance');
    })->name('performance');

    // 커미션 시스템
    Route::get('/commission', function () {
        return view('jiny-partner::www.commission');
    })->name('commission');

    // 네트워크 관계
    Route::get('/network', function () {
        return view('jiny-partner::www.network');
    })->name('network');

    // FAQ
    Route::get('/faq', function () {
        return view('jiny-partner::www.faq');
    })->name('faq');

    // 연락처
    Route::get('/contact', function () {
        return view('jiny-partner::www.contact');
    })->name('contact');

    // 파트너 지원서
    Route::get('/application', function () {
        return view('jiny-partner::www.application');
    })->name('application');

    // 파트너 지원서 제출 (POST)
    Route::post('/application', function () {
        // TODO: 실제 지원서 처리 로직 구현
        return redirect()->route('partner.www.contact')->with('success', '지원서가 성공적으로 제출되었습니다.');
    })->name('application.store');

    // 문의 폼 제출 (POST)
    Route::post('/contact', function () {
        // TODO: 실제 문의 처리 로직 구현
        return back()->with('success', '문의가 성공적으로 접수되었습니다.');
    })->name('contact.store');

});

// 추가 라우트들 (필요시)
Route::prefix('partners')->name('partner.www.')->group(function () {

    // 동적 목표 관리 (별칭)
    Route::get('/targets', function () {
        return view('jiny-partner::www.performance'); // 성과 관리 페이지로 리다이렉트
    })->name('targets');

    // 면접 평가 시스템 (별칭)
    Route::get('/interview', function () {
        return view('jiny-partner::www.about'); // 파트너 소개 페이지로 리다이렉트
    })->name('interview');

    // 지급 관리 시스템 (별칭)
    Route::get('/payment', function () {
        return view('jiny-partner::www.commission'); // 커미션 페이지로 리다이렉트
    })->name('payment');

});