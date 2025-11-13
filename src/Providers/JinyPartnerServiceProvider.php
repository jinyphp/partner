<?php

namespace Jiny\Partner\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class JinyPartnerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 마이그레이션 로드
        $this->loadMigrationsFrom(__DIR__.'/../../databases/migrations');

        // 라우트 로드
        $this->loadRoutesFrom(__DIR__.'/../../routes/admin.php');
        $this->loadRoutesFrom(__DIR__.'/../../routes/home.php');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');

        // 뷰 로드
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'jiny-partner');

        // Livewire 컴포넌트 등록
        Livewire::component('jiny-partner::customer', \Jiny\Partner\Http\Livewire\Customer::class);

        // 설정 파일 발행 (필요시)
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/partner.php' => config_path('partner.php'),
            ], 'partner-config');

            $this->publishes([
                __DIR__.'/../../resources/views' => resource_path('views/vendor/jiny-partner'),
            ], 'partner-views');
        }
    }
}