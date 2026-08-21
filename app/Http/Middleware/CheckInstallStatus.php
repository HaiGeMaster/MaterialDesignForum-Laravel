<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 安装状态检查（全局最外层中间件，最先执行）
 *
 * 以下两种情况强制进入"安装模式"：
 *   1. storage/app/installed.lock 不存在（从未安装，或锁文件被删除）
 *   2. .env 缺失 / APP_KEY 未配置——此时 Laravel 的 cookie/session 加密
 *      会在 EncryptCookies 等 web 组中间件阶段抛出
 *      Illuminate\Encryption\MissingAppKeyException，导致整个站点 500。
 *
 * 进入安装模式后的行为：
 *   - /api/install/* 放行，安装向导正常工作（InstallerController 内部
 *     再通过 installed.lock 做二次防御，防止已安装后重跑）
 *   - 页面请求 302 引导到 /install（除 /install 本身放行渲染外壳），
 *     绕过需要 APP_KEY 的 web 组中间件，并让用户直达安装向导
 *   - 其余 API 返回 { "installed": false }，结构与 /api/install/status 一致
 */
class CheckInstallStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        $installed = file_exists(storage_path('app/installed.lock'));
        $hasAppKey = ! empty(config('app.key'));

        // 正常状态：已安装且密钥完整，直接放行
        if ($installed && $hasAppKey) {
            return $next($request);
        }

        // 安装接口放行（安装器自身再校验锁文件）
        if ($request->is('api/install/*')) {
            return $next($request);
        }

        // 页面请求：302 引导到安装向导 /install（SPA 路由，由前端渲染）
        if (! $request->is('api/*')) {
            $path = $request->path();
            // 静态资源（/build/...）由 web server 直接服务，不会走到这里
            if ($path === 'install' || str_starts_with($path, 'install/')) {
                // 缺 APP_KEY 时无法走 web 组中间件（EncryptCookies 等会抛
                // MissingAppKeyException），直接渲染外壳，绕过整条中间件栈
                if (! $hasAppKey) {
                    return response()->view('welcome');
                }
                return $next($request);
            }
            return redirect('/install');
        }

        // 其余 API：告知前端未安装
        return response()->json(['installed' => false]);
    }
}
