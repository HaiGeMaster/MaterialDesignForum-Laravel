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

    // 如果之后要添加新的 OAuth 提供商（如 Google、GitLab），现在只需写一个 4 行的方法即可接入，无需再复制整段逻辑。

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
     * OAuth 登录成功跳转页（设置 cookie 后 3 秒跳转）
     */
    private static function oauthRedirectHtml(string $provider, string $token): string
    {
        $icon      = $token !== '' ? '✅' : '⚠️';
        $status    = $token !== '' ? '成功' : '异常';
        $safeToken = json_encode($token, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Secure 要求 HTTPS 环境，如果本地是 HTTP 开发，可以去掉 ;Secure。

        // document.cookie = "user_token=" + {$safeToken} + ";path=/;max-age=2592000;SameSite=Lax;Secure";
        return <<<HTML
        <!DOCTYPE html>
        <html lang="zh-CN">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$provider} 登录</title>
            <style>
                body { display:flex; justify-content:center; align-items:center; min-height:100vh; background:#f5f5f5; font-family:system-ui,sans-serif; margin:0; }
                .card { text-align:center; background:#fff; padding:48px 64px; border-radius:16px; box-shadow:0 4px 24px rgba(0,0,0,0.08); }
                .icon { font-size:56px; margin-bottom:16px; }
                h2 { color:#333; margin:0 0 8px; font-size:22px; }
                p { color:#888; font-size:14px; margin:0; }
                #countdown { color:#1890ff; font-weight:700; }
            </style>
        </head>
        <body>
            <div class="card">
                <div class="icon">{$icon}</div>
                <h2>{$provider} 登录{$status}</h2>
                <p>将在 <span id="countdown">3</span> 秒后自动跳转到首页...</p>
            </div>
            <script>
                document.cookie = "user_token=" + {$safeToken} + ";path=/;max-age=2592000;SameSite=Lax";
                let s = 3;
                setInterval(() => { const el = document.getElementById('countdown'); if (--s > 0) el.textContent = s; }, 1000);
                setTimeout(() => { window.location.replace('/'); }, 3000);
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
            <title>{$provider} 登录失败</title>
            <style>
                body { display:flex; justify-content:center; align-items:center; min-height:100vh; background:#fff5f5; font-family:system-ui,sans-serif; margin:0; }
                .card { text-align:center; background:#fff; padding:48px 64px; border-radius:16px; box-shadow:0 4px 24px rgba(0,0,0,0.08); max-width:520px; }
                .icon { font-size:56px; margin-bottom:16px; }
                h2 { color:#e53e3e; margin:0 0 16px; font-size:22px; }
                pre { background:#f7f7f7; padding:16px; border-radius:8px; text-align:left; font-size:12px; color:#666; overflow-x:auto; margin:0; }
            </style>
        </head>
        <body>
            <div class="card">
                <div class="icon">❌</div>
                <h2>{$provider} 登录失败</h2>
                <pre>{$error}</pre>
            </div>
        </body>
        </html>
        HTML;
    }
}
