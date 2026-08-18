<?php

namespace App\Providers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
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
        Schema::defaultStringLength(191);

        // 前端通过 locale cookie 同步语言，后端据此渲染 <html lang>（welcome 模板）
        $locale = $this->app['request']->cookie('locale');
        if (is_string($locale) && in_array($locale, ['zh', 'en'], true)) {
            $this->app->setLocale($locale);
        }
    }
}
