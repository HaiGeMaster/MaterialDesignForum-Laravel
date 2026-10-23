<?php

namespace App\Http\Controllers;

use App\Models\OAuth;
use App\Models\Token;
use App\Models\User;
use App\Support\GeoLocation;
use App\Support\IssuesTokens;
use App\Support\RegistrationProfileDefaults;
use GuzzleHttp\Client as GuzzleHttpClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

/**
 * 第三方 OAuth 登录与绑定
 *
 * 支持的提供方：GitHub / Google / Microsoft（由 Laravel Socialite + socialiteproviders/microsoft 驱动）
 *
 * 设计要点：
 * - 与 SPA 前端配合：后端使用 stateless Socialite（API 路由无 session），自行用
 *   HMAC 签名的 state 携带 mode（login/bind）+ user_id，避免引入 session 中间件。
 * - 登录流程：邮箱已存在则绑定到既有用户；否则创建新用户（user_group_id=2）。
 * - 绑定流程：当前登录用户主动发起，要求 Authorization 头携带有效令牌。
 */
class OAuthController extends Controller
{
    use IssuesTokens;

    /** 已支持的 provider 列表 */
    private const PROVIDERS = ['github', 'google', 'microsoft'];

    /** state 有效期（秒），超过即视为过期 */
    private const STATE_TTL = 600;

    /**
     * 公开：返回后端已配置好凭证的 OAuth 提供方列表。
     * 前端据此决定渲染哪些第三方登录图标。
     */
    public function providers()
    {
        $enabled = array_values(array_filter(
            self::PROVIDERS,
            fn ($p) => $this->providerEnabled($p),
        ));

        return response()->json(['providers' => $enabled]);
    }

    /**
     * 重定向到 OAuth 提供方授权页。
     * 默认登录模式；已登录用户传 ?mode=bind 触发绑定流程（user_id 写入 state）。
     *
     * 注意：前端从设置页发起绑定时使用整页跳转（window.location.href），浏览器不会
     * 自动携带 Authorization 头。因此 bind 模式下除 $request->user() 外，
     * 还允许从 ?token=... 兜底解析当前登录用户（仅用于这一次跳转识别身份，
     * 真正的会话延续由后续 OAuth 回调时携带的 signed state 完成）。
     */
    public function redirect(Request $request, string $provider)
    {
        if (! $this->isSupported($provider)) {
            return $this->frontendError('/oauth/callback', 'unsupported_provider');
        }

        $mode = $request->query('mode') === 'bind' ? 'bind' : 'login';
        $userId = null;

        if ($mode === 'bind') {
            $user = $request->user();
            if (! $user && $request->query('token')) {
                $user = $this->resolveUserByToken((string) $request->query('token'));
            }
            if (! $user) {
                return $this->frontendError('/settings', 'auth_required');
            }
            $userId = (int) $user->user_id;
        }

        $state = $this->buildState($mode, $userId);

        // stateless 跳过 Socialite 内置的 session-based CSRF；state 由我们手动追加到 auth URL
        $authUrl = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();
        $sep = str_contains($authUrl, '?') ? '&' : '?';
        $authUrl .= $sep . 'state=' . urlencode($state);

        return redirect($authUrl);
    }

    /**
     * 通过 token 字符串解析用户（用于整页跳转无法携带 Authorization 头的场景）。
     * 校验未通过返回 null。
     */
    private function resolveUserByToken(string $tokenString): ?User
    {
        if ($tokenString === '') {
            return null;
        }

        $token = Token::where('token', $tokenString)->first();
        if (! $token || ($token->expire_time && $token->expire_time->isPast())) {
            return null;
        }

        $user = $token->user;
        if (! $user || $user->isDisabled() || ! $user->canLoginNormally()) {
            return null;
        }

        return $user;
    }

    /**
     * OAuth 提供方回调入口。
     * 根据 state 中的 mode 派发到登录或绑定流程。
     */
    public function callback(Request $request, string $provider)
    {
        if (! $this->isSupported($provider)) {
            return $this->frontendError('/oauth/callback', 'unsupported_provider');
        }

        if ($request->query('error')) {
            return $this->frontendError('/oauth/callback', 'provider_error');
        }

        $payload = $this->verifyState((string) $request->query('state', ''));
        if (! $payload) {
            return $this->frontendError('/oauth/callback', 'invalid_state');
        }

        try {
            $driver = Socialite::driver($provider)->stateless();

            // Windows 本地开发环境常见：PHP 缺 CA 证书导致 cURL error 60
            // 仅 local 环境注入 verify=false 的 Guzzle，不影响生产
            if (app()->environment('local')) {
                $driver->setHttpClient(new GuzzleHttpClient(['verify' => false]));
            }

            $socialiteUser = $driver->user();
        } catch (\Throwable $e) {
            // 把实际异常打到日志便于排查（前端只展示通用文案，不泄露细节）
            Log::error('OAuth fetch user failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'exception' => $e,
                'request' => [
                    'code' => $request->query('code'),
                    'error' => $request->query('error'),
                    'state' => $request->query('state'),
                ],
            ]);
            return $this->frontendError('/oauth/callback', 'fetch_failed');
        }

        $oauthUserId = (string) $socialiteUser->getId();
        $email = $socialiteUser->getEmail();
        $name = $socialiteUser->getName() ?: $socialiteUser->getNickname() ?: '';
        $raw = $socialiteUser->getRaw() ?: [];

        if ($oauthUserId === '') {
            return $this->frontendError('/oauth/callback', 'no_provider_id');
        }

        if (($payload['m'] ?? null) === 'bind' && ! empty($payload['u'])) {
            return $this->handleBind($provider, $oauthUserId, $email, $name, $raw, (int) $payload['u']);
        }

        return $this->handleLogin($request, $provider, $oauthUserId, $email, $name, $raw);
    }

    /**
     * 登录流程（仅按 oauth 表的 user_id 关联，不通过邮箱自动认领既有账号）：
     * 1. oauth 表已存在绑定 → 登录该绑定记录指向的用户
     * 2. 未绑定 → 创建新用户（user_group_id=2）+ 写入绑定 + 登录
     *
     * 设计取舍：不通过邮箱匹配自动绑定既有用户，可避免攻击者通过控制第三方账号
     * 邮箱（例如把 GitHub 邮箱改成受害者的论坛邮箱）直接登入受害者账号。
     * 想用第三方账号登录到既有论坛账号，必须先以论坛账号登录，再到设置页发起绑定。
     */
    private function handleLogin(Request $request, string $provider, string $oauthUserId, ?string $email, ?string $name, array $raw)
    {
        return DB::transaction(function () use ($request, $provider, $oauthUserId, $email, $name, $raw) {
            // 1. 已有绑定 → 直接登录该 user_id 对应的用户
            $binding = OAuth::where('oauth_name', $provider)
                ->where('oauth_user_id', $oauthUserId)
                ->first();

            if ($binding) {
                $user = $binding->user;
                if (! $user || $user->isDisabled()) {
                    return $this->frontendError('/oauth/callback', 'user_disabled');
                }

                // 用户组 ability_normal_login=false 时禁止 OAuth 登录
                if (! $user->canLoginNormally()) {
                    return $this->frontendError('/oauth/callback', 'user_group_login_disabled');
                }

                $this->touchLogin($user, $request);
                $token = $this->createToken($user, $request);

                return $this->frontendToken($token->token);
            }

            // 2. 无邮箱无法创建新用户
            if (! $email) {
                return $this->frontendError('/oauth/callback', 'no_email');
            }

            // 3. 创建新用户 + 写入绑定 + 登录
            $user = $this->createNewUserFromOAuth($request, $email, $name);

            // 默认用户组若被关闭登录权限，新建的 OAuth 用户也不能登录
            if (! $user->canLoginNormally()) {
                return $this->frontendError('/oauth/callback', 'user_group_login_disabled');
            }

            $this->createBinding($user->user_id, $provider, $oauthUserId, $email, $name, $raw);
            $this->touchLogin($user, $request);
            $token = $this->createToken($user, $request);

            return $this->frontendToken($token->token);
        });
    }

    /**
     * 绑定流程：把当前登录用户与第三方账号绑定（要求请求中已携带有效令牌）。
     *
     * 注意：不校验第三方账号邮箱是否与当前论坛用户邮箱一致——绑定时仅以
     * user_id 作为关联键，后续登录通过 oauth 表的 user_id 找到对应用户。
     * 第三方账号在同一 provider 下全局唯一绑定：已被其他用户绑过的第三方账号
     * 不能再绑给当前用户。
     */
    private function handleBind(string $provider, string $oauthUserId, ?string $email, ?string $name, array $raw, int $userId)
    {
        $user = User::find($userId);
        if (! $user) {
            return $this->frontendError('/settings', 'auth_required');
        }

        // 该第三方账号已被其他用户绑定 → 不允许重复绑定
        $existing = OAuth::where('oauth_name', $provider)
            ->where('oauth_user_id', $oauthUserId)
            ->first();

        if ($existing && (int) $existing->user_id !== $userId) {
            return $this->frontendError('/settings', 'already_bound');
        }

        // 已绑定给当前用户 → 直接视为成功
        if ($existing && (int) $existing->user_id === $userId) {
            return $this->frontendSuccess('/settings', $provider);
        }

        $this->createBinding($userId, $provider, $oauthUserId, $email, $name, $raw);

        return $this->frontendSuccess('/settings', $provider);
    }

    /**
     * 已登录用户：获取自己已绑定的第三方账号列表。
     */
    public function index(Request $request)
    {
        $bindings = OAuth::where('user_id', $request->user()->user_id)
            ->select(['oauth_id', 'oauth_name', 'oauth_user_id', 'oauth_user_name', 'oauth_user_email'])
            ->get();

        return response()->json(['data' => $bindings]);
    }

    /**
     * 已登录用户：解除某条第三方账号绑定（仅允许解绑自己的）。
     */
    public function unbind(Request $request, OAuth $oauth)
    {
        if ((int) $oauth->user_id !== (int) $request->user()->user_id) {
            return response()->json(['message' => __('messages.http_oauth_1')], 403);
        }

        $oauth->delete();

        return response()->json(['message' => __('messages.http_oauth_2')]);
    }

    // ============================ 内部辅助方法 ============================

    /**
     * provider 在支持列表内且凭证已配置。
     */
    private function isSupported(string $provider): bool
    {
        return in_array($provider, self::PROVIDERS, true) && $this->providerEnabled($provider);
    }

    private function providerEnabled(string $provider): bool
    {
        return ! empty(config("services.{$provider}.client_id"))
            && ! empty(config("services.{$provider}.client_secret"));
    }

    /**
     * 创建新用户（来自 OAuth 注册，user_group_id 固定为 2：普通用户组）。
     */
    private function createNewUserFromOAuth(Request $request, string $email, ?string $name): User
    {
        $username = $this->generateUsername($name, $email);
        $profileDefaults = app(RegistrationProfileDefaults::class);

        return User::create([
            'username' => $username,
            'email' => $email,
            'avatar' => $profileDefaults->avatar($username),
            'cover' => $profileDefaults->cover(),
            'password' => Hash::make(Str::random(40)), // OAuth 用户无需密码登录；随机串防止猜测
            'user_group_id' => 2,
            'create_ip' => $request->ip() ?: '127.0.0.1',
            'create_location' => GeoLocation::location(GeoLocation::getClientIP()),
            'last_login_time' => now(),
            'last_login_ip' => $request->ip() ?: '127.0.0.1',
            'last_login_location' => GeoLocation::location(GeoLocation::getClientIP()),
            'follower_count' => 0,
            'followee_count' => 0,
            'following_topic_count' => 0,
            'following_article_count' => 0,
            'following_question_count' => 0,
            'topic_count' => 0,
            'article_count' => 0,
            'question_count' => 0,
            'answer_count' => 0,
            'comment_count' => 0,
            'reply_count' => 0,
            'notification_unread' => 0,
            'inbox_system' => 0,
            'inbox_user_group' => 0,
            'inbox_private_message' => 0,
            'language' => 'en-US',
            'create_time' => now(),
            'update_time' => now(),
            'disable_time' => null,
        ]);
    }

    /**
     * 基于第三方用户名 / 邮箱前缀生成唯一 username。
     * 同名时自动追加 _1 / _2 ...；超长截断并保留后缀空间。
     */
    private function generateUsername(?string $name, string $email): string
    {
        $base = trim((string) $name);
        if ($base === '') {
            $base = (string) strstr($email, '@', true) ?: 'user';
        }

        // 仅保留字母、数字、下划线、汉字，其余替换为下划线
        $base = preg_replace('/[^\p{L}\p{N}_]+/u', '_', $base);
        $base = trim((string) $base, '_');
        if ($base === '') {
            $base = 'user';
        }

        if (mb_strlen($base) > 25) {
            $base = mb_substr($base, 0, 25);
        }

        $candidate = $base;
        $i = 1;
        while (User::where('username', $candidate)->exists()) {
            $suffix = '_' . $i;
            $candidate = mb_substr($base, 0, max(1, 30 - mb_strlen($suffix))) . $suffix;
            $i++;
            if ($i > 999) {
                $candidate = mb_substr($base, 0, 24) . '_' . Str::random(5);
                break;
            }
        }

        return $candidate;
    }

    /**
     * 写入一条 OAuth 绑定记录到 oauth 表。
     */
    private function createBinding(int $userId, string $provider, string $oauthUserId, ?string $email, ?string $name, array $raw): OAuth
    {
        return OAuth::create([
            'oauth_name' => $provider,
            'oauth_user_id' => $oauthUserId,
            'oauth_user_name' => $name ?: '',
            'oauth_user_email' => $email ?: '',
            'oauth_source_response' => $raw ?: [],
            'user_id' => $userId,
        ]);
    }

    /**
     * 更新用户最后登录信息（IP / 时间 / 归属地）。
     */
    private function touchLogin(User $user, Request $request): void
    {
        $user->last_login_time = now();
        $user->last_login_ip = $request->ip() ?: '127.0.0.1';
        $user->last_login_location = GeoLocation::location(GeoLocation::getClientIP());
        $user->save();
    }

    // ----------------------- state 编解码 -----------------------

    /**
     * 构造 HMAC 签名的 state 字符串：base64url(payload) + '.' + hmac
     */
    private function buildState(string $mode, ?int $userId): string
    {
        $payload = json_encode([
            'm' => $mode,
            'u' => $userId,
            'n' => Str::random(16),
            't' => time(),
        ], JSON_UNESCAPED_SLASHES);

        $b64 = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
        $sig = hash_hmac('sha256', $b64, config('app.key'));

        return $b64 . '.' . $sig;
    }

    /**
     * 校验并解析 state；失败返回 null。
     */
    private function verifyState(string $state): ?array
    {
        $parts = explode('.', $state, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$b64, $sig] = $parts;
        $expected = hash_hmac('sha256', $b64, config('app.key'));
        if (! hash_equals($expected, $sig)) {
            return null;
        }

        $payload = json_decode(base64_decode(strtr($b64, '-_', '+/')), true);
        if (! is_array($payload)) {
            return null;
        }

        if (($payload['t'] ?? 0) < time() - self::STATE_TTL) {
            return null;
        }

        return $payload;
    }

    // ----------------------- 前端跳转辅助 -----------------------

    /**
     * 成功登录后跳转到前端 SPA 的 /oauth/callback，前端从 query 读取 token 落地登录。
     */
    private function frontendToken(string $token)
    {
        return redirect('/oauth/callback?token=' . urlencode($token));
    }

    /**
     * 绑定成功后跳转回设置页，前端读取 ?bound=provider 显示成功提示。
     */
    private function frontendSuccess(string $path, string $provider)
    {
        return redirect($path . '?bound=' . urlencode($provider));
    }

    /**
     * 出错时跳转到前端，通过 ?error=reason 让前端展示对应文案。
     */
    private function frontendError(string $path, string $reason)
    {
        return redirect($path . '?error=' . urlencode($reason));
    }
}
