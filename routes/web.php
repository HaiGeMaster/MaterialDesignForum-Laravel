<?php

use App\Http\Controllers\OAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// 动态生成 PWA manifest.json：站点名称、描述等配置从数据库 settings 表读取，
// 与后台站点设置保持同步。注意：public/manifest.json 静态文件必须删除，
// 否则 Web 服务器会优先返回静态文件而不会触发此路由。
Route::get('/manifest.json', function () {
    try {
        $rows = \Illuminate\Support\Facades\DB::table('settings')
            ->whereIn('name', ['site_name', 'site_description'])
            ->pluck('value', 'name');
        $siteName = (string) ($rows['site_name'] ?? '');
        $siteDescription = (string) ($rows['site_description'] ?? '');
    } catch (\Throwable $e) {
        $siteName = '';
        $siteDescription = '';
    }

    $name = $siteName !== '' ? $siteName : config('app.name', 'MaterialDesignForum');
    $description = $siteDescription !== '' ? $siteDescription : 'Material Design Forum';

    $manifest = [
        'name' => $name,
        'short_name' => mb_substr($name, 0, 12),
        'description' => $description,
        'start_url' => '/',
        'scope' => '/',
        'display' => 'standalone',
        'background_color' => '#ffffff',
        'theme_color' => '#ffffff',
        'orientation' => 'portrait-primary',
        'icons' => [
            [
                'src' => '/favicon.png',
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any maskable',
            ],
            [
                'src' => '/icon-512.png',
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any maskable',
            ],
        ],
    ];

    return response()->json($manifest, 200, [
        'Content-Type' => 'application/manifest+json',
        'Cache-Control' => 'public, max-age=3600',
    ]);
});

// 第三方 OAuth 登录的浏览器整页跳转路由：回调 URL 不带 /api 前缀，
// 便于在 GitHub / Azure / Google 后台注册更简洁的回调地址。
// providers 列表接口（前端 ajax 用）在 routes/api.php 注册。
// redirect / callback 均为 GET 请求，Laravel 默认 CSRF 中间件只校验状态变更请求，无需排除。
Route::get('/oauth/redirect/{provider}', [OAuthController::class, 'redirect']);
Route::get('/oauth/callback/{provider}', [OAuthController::class, 'callback']);

// 独立后台入口：由 admin.js 挂载，不与前台 Vue Router 共用应用壳
Route::get('/admin/{any?}', function () {
    return view('admin');
})->where('any', '.*');

// SPA 前端路由兜底：所有未匹配路径都返回应用外壳，由 Vue Router 接管
Route::fallback(function () {
    return view('welcome');
});
