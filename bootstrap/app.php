<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // 全局最外层：缺 .env / APP_KEY 或未安装时进入安装模式，避免 MissingAppKeyException 500
        $middleware->prepend(\App\Http\Middleware\CheckInstallStatus::class);

        $middleware->alias([
            'auth.token' => \App\Http\Middleware\EnsureTokenIsValid::class,
            'ability' => \App\Http\Middleware\CheckAbility::class,
        ]);

        // 全局（API 组）：
        // - SetLocaleFromCookie：根据前端 cookie locale 临时设置后端语言，使 __('messages.*') 按用户首选语言渲染
        // - ResolveOptionalUser：可选解析登录用户，公开接口也能通过 $request->user() 感知登录状态
        $middleware->api(append: [
            \App\Http\Middleware\SetLocaleFromCookie::class,
            \App\Http\Middleware\ResolveOptionalUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
