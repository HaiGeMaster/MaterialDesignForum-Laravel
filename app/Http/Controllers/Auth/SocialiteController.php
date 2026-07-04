<?php

/**
 * Author HaiGeMaster
 * @package MaterialDesignForum
 * @link https://github.com/HaiGeMaster
 * @copyright Copyright (c) 2023 HaiGeMaster
 * @start-date 2023/05/20-15:53:29
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    /**
     * 获取 Guzzle HTTP 客户端。
     * 优先使用本地 CA 证书；若文件不存在则回退到系统证书（生产环境推荐）。
     */
    private static function httpClient(): Client
    {
        $certPath = storage_path('certs/cacert.pem');

        return new Client([
            'verify' => file_exists($certPath) ? $certPath : true,
        ]);
    }

    // 重定向到 GitHub 授权页
    public function redirectToGithub()
    {
        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver('github');
        return $driver->setScopes(['read:user', 'user:email'])->redirect();
    }

    // GitHub 回调处理
    public function handleGithubCallback(Request $request)
    {
        return $this->handleCallback('github', $request, function ($user) {
            return $user->getName() ?? $user->getNickname();
        });
    }

    // 重定向到 Microsoft 授权页
    public function redirectToMicrosoft()
    {
        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver('microsoft');
        return $driver->setScopes(['User.Read', 'offline_access'])->redirect();
    }

    // Microsoft 回调处理
    public function handleMicrosoftCallback(Request $request)
    {
        return $this->handleCallback('microsoft', $request, function ($user) {
            return $user->getName();
        });
    }

    // 重定向到 Google 授权页
    public function redirectToGoogle()
    {
        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver('google');
        return $driver->setScopes(['openid', 'profile', 'email'])->redirect();
    }

    // Google 回调处理
    public function handleGoogleCallback(Request $request)
    {
        return $this->handleCallback('google', $request, function ($user) {
            return $user->getName();
        });
    }

    // 如果之后要添加新的 OAuth 提供商（如 GitLab），现在只需写一个 4 行的方法即可接入，无需再复制整段逻辑。

    /**
     * 统一 OAuth 回调处理（消除 GitHub/Microsoft 重复代码）。
     *
     * @param  string   $provider   'github' | 'microsoft'
     * @param  Request  $request
     * @param  callable $nameResolver  从 Socialite user 中提取显示名称
     */
    private function handleCallback(string $provider, Request $request, callable $nameResolver)
    {
        try {
            /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
            $driver = Socialite::driver($provider);
            $driver->setHttpClient(self::httpClient());
            $socialUser = $driver->stateless()->user();

            $result = UserController::OauthLoginOrRegister(
                $provider,
                $socialUser->getId(),
                $nameResolver($socialUser),
                $socialUser->getEmail(),
                // $socialUser,
                json_encode($socialUser, JSON_UNESCAPED_UNICODE),
                $request->cookie('user_token') ?? $request->input('user_token', $request->bearerToken()),
            );

            $token = $result['token'] ?? '';
            $isLogin = $result['is_login'] ?? false;

            Log::info("{$provider} OAuth callback", [
                'token'    => $token ? substr($token, 0, 20) . '...' : 'EMPTY',
                'is_login' => $isLogin,
            ]);

            return response(self::oauthRedirectHtml($provider, $token));
        } catch (\Exception $e) {
            Log::error("{$provider} OAuth callback failed: " . $e->getMessage());
            return response(self::oauthErrorHtml($provider, $e->getMessage()), 500);
        }
    }

    /**
     * OAuth 登录成功跳转页（设置 cookie 后跳转，debug 模式下显示倒计时）
     */
    private static function oauthRedirectHtml(string $provider, string $token): string
    {
        $isDebug   = config('app.debug');
        $success   = $token !== '';
        $safeToken = json_encode($token, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $delay     = $isDebug ? 3000 : 0;

        // 状态文字、图标名称和 CSS 类名
        $statusText = $success ? '登录成功' : '登录异常';
        $iconClass  = $success ? 'success' : 'warning';
        $iconName   = $success ? 'check_circle' : 'warning';

        // debug 模式显示倒计时文字，否则直接跳转
        $countdownHtml = $isDebug
            ? '<p class="body-medium">将在 <span id="countdown">3</span> 秒后自动跳转到首页...</p>'
            : '';

        return <<<HTML
        <!DOCTYPE html>
        <html lang="zh-CN">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$provider} 登录</title>
            <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
            <style>
                :root {
                    --md-sys-color-primary: #6750a4;
                    --md-sys-color-on-primary: #ffffff;
                    --md-sys-color-primary-container: #eaddff;
                    --md-sys-color-on-primary-container: #21005d;
                    --md-sys-color-surface: #fef7ff;
                    --md-sys-color-on-surface: #1d1b20;
                    --md-sys-color-on-surface-variant: #49454f;
                    --md-sys-color-outline: #79747e;
                    --md-sys-color-error: #b3261e;
                    --md-sys-color-error-container: #f9dedc;
                    --md-sys-color-on-error: #ffffff;
                    --md-sys-elevation-2: 0 1px 2px rgba(0,0,0,0.3), 0 1px 3px 1px rgba(0,0,0,0.15);
                    --md-sys-elevation-3: 0 1px 3px rgba(0,0,0,0.3), 0 4px 8px 3px rgba(0,0,0,0.15);
                }
                * { margin:0; padding:0; box-sizing:border-box; }
                body {
                    display:flex; justify-content:center; align-items:center;
                    min-height:100vh;
                    background: var(--md-sys-color-surface);
                    font-family: 'Roboto', system-ui, -apple-system, sans-serif;
                    margin:0;
                }
                .card {
                    text-align:center;
                    background:#fff;
                    padding:40px 48px;
                    border-radius:28px;
                    box-shadow: var(--md-sys-elevation-3);
                    max-width:400px;
                    width:90vw;
                }
                .icon-wrap {
                    display:inline-flex;
                    align-items:center;
                    justify-content:center;
                    width:64px; height:64px;
                    border-radius:50%;
                    margin-bottom:20px;
                }
                .icon-wrap.success {
                    background: var(--md-sys-color-primary-container);
                }
                .icon-wrap.warning {
                    background: var(--md-sys-color-error-container);
                }
                .icon-wrap .material-symbols-outlined {
                    font-size: 36px;
                    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
                }
                .icon-wrap.success .material-symbols-outlined {
                    color: var(--md-sys-color-primary);
                }
                .icon-wrap.warning .material-symbols-outlined {
                    color: var(--md-sys-color-error);
                }
                .headline-small {
                    font-size:24px; font-weight:400; line-height:32px;
                    color: var(--md-sys-color-on-surface);
                    margin-bottom:4px;
                }
                .body-medium {
                    font-size:14px; font-weight:400; line-height:20px;
                    color: var(--md-sys-color-on-surface-variant);
                    letter-spacing:0.25px;
                }
                #countdown { color:var(--md-sys-color-primary); font-weight:500; }
                .progress-bar {
                    margin-top: 24px;
                    width:100%; height:4px;
                    background: #e7e0ec;
                    border-radius:2px;
                    overflow:hidden;
                }
                .progress-bar .fill {
                    height:100%;
                    background: var(--md-sys-color-primary);
                    border-radius:2px;
                    animation: shrink 3s linear forwards;
                }
                @keyframes shrink { from { width:100%; } to { width:0%; } }
            </style>
        </head>
        <body>
            <div class="card">
                <div class="icon-wrap {$iconClass}">
                    <span class="material-symbols-outlined">{$iconName}</span>
                </div>
                <h2 class="headline-small">{$provider} {$statusText}</h2>
                {$countdownHtml}
                <div class="progress-bar"><div class="fill"></div></div>
            </div>
            <script>
                document.cookie = "user_token=" + {$safeToken} + ";path=/;max-age=2592000;SameSite=Lax";
                localStorage.setItem('user_token', {$safeToken});
                var delay = {$delay};
                if (delay > 0) {
                    var el = document.getElementById('countdown');
                    var s = 3;
                    setInterval(function() { if (--s > 0) el.textContent = s; }, 1000);
                }
                setTimeout(function() { window.location.replace('/'); }, delay);
            </script>
        </body>
        </html>
        HTML;
    }

    /**
     * OAuth 登录失败页
     */
    private static function oauthErrorHtml(string $provider, string $error): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="zh-CN">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$provider} 登录失败</title>
            <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
            <style>
                :root {
                    --md-sys-color-surface: #fef7ff;
                    --md-sys-color-on-surface: #1d1b20;
                    --md-sys-color-on-surface-variant: #49454f;
                    --md-sys-color-error: #b3261e;
                    --md-sys-color-error-container: #f9dedc;
                    --md-sys-color-on-error-container: #410e0b;
                    --md-sys-elevation-3: 0 1px 3px rgba(0,0,0,0.3), 0 4px 8px 3px rgba(0,0,0,0.15);
                }
                * { margin:0; padding:0; box-sizing:border-box; }
                body {
                    display:flex; justify-content:center; align-items:center;
                    min-height:100vh;
                    background: var(--md-sys-color-error-container);
                    font-family: 'Roboto', system-ui, -apple-system, sans-serif;
                    margin:0;
                }
                .card {
                    text-align:center;
                    background:#fff;
                    padding:40px 48px;
                    border-radius:28px;
                    box-shadow: var(--md-sys-elevation-3);
                    max-width:480px;
                    width:90vw;
                }
                .icon-wrap {
                    display:inline-flex;
                    align-items:center;
                    justify-content:center;
                    width:64px; height:64px;
                    border-radius:50%;
                    background: var(--md-sys-color-error-container);
                    margin-bottom:20px;
                }
                .icon-wrap .material-symbols-outlined {
                    font-size:36px;
                    color: var(--md-sys-color-error);
                    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
                }
                .headline-small {
                    font-size:24px; font-weight:400; line-height:32px;
                    color: var(--md-sys-color-on-surface);
                    margin-bottom:16px;
                }
                .error-box {
                    background: var(--md-sys-color-error-container);
                    padding:16px;
                    border-radius:12px;
                    text-align:left;
                }
                .error-box pre {
                    font-family: 'Roboto Mono', 'Cascadia Code', monospace;
                    font-size:12px; line-height:1.6;
                    color: var(--md-sys-color-on-error-container);
                    white-space:pre-wrap; word-break:break-all;
                    margin:0;
                }
            </style>
        </head>
        <body>
            <div class="card">
                <div class="icon-wrap">
                    <span class="material-symbols-outlined">error</span>
                </div>
                <h2 class="headline-small">{$provider} 登录失败</h2>
                <div class="error-box">
                    <pre>{$error}</pre>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }
}
