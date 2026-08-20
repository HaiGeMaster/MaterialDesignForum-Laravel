<?php

use App\Http\Controllers\OAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
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
