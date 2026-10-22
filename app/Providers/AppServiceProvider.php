<?php

namespace App\Providers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use Overtrue\Flysystem\Qiniu\QiniuAdapter;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\Provider as MicrosoftProvider;
use Xxtime\Flysystem\Aliyun\OssAdapter;
use ZenCodex\Support\Flysystem\Adapter\UpyunAdapter;

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

        Storage::extend('aliyun', function ($app, array $config) {
            $adapter = new OssAdapter([
                'accessId' => $config['key'],
                'accessSecret' => $config['secret'],
                'bucket' => $config['bucket'],
                'endpoint' => $config['endpoint'],
            ]);

            return new FilesystemAdapter(new Filesystem($adapter, $config), $adapter, $config);
        });

        Storage::extend('upyun', function ($app, array $config) {
            $adapter = new UpyunAdapter((object) $config);

            return new FilesystemAdapter(new Filesystem($adapter, $config), $adapter, $config);
        });

        Storage::extend('qiniu', function ($app, array $config) {
            $adapter = new QiniuAdapter(
                $config['key'],
                $config['secret'],
                $config['bucket'],
                $config['domain'],
            );

            return new FilesystemAdapter(new Filesystem($adapter, $config), $adapter, $config);
        });

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
