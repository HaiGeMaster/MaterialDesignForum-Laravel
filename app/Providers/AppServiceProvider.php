<?php

namespace App\Providers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\Provider as MicrosoftProvider;

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
        $locale = $this->app['request']->cookie('ui-locale');
        if (is_string($locale) && in_array($locale, ['zh', 'en'], true)) {
            $this->app->setLocale($locale);
        }

        // 注册 socialiteproviders/microsoft 自定义 driver
        // 注意：必须传入 Provider 实现类，而不是 *ExtendSocialite 包装类
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('microsoft', MicrosoftProvider::class);
        });
    }
}
