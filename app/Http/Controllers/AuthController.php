<?php

namespace App\Http\Controllers;

use App\Models\Token;
use App\Models\User;
use App\Support\GeoLocation;
use App\Support\IssuesTokens;
use App\Support\RegistrationProfileDefaults;
use App\Support\UserContentCounts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use IssuesTokens;

    /**
     * 注册并自动登录
     *
     * 注册成功后将默认加入普通用户组（user_group_id = 2），
     * 并直接颁发登录令牌，前端无需再手动登录。
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:30', 'unique:user,username'],
            'email' => ['required', 'email', 'max:320', 'unique:user,email'],
            'password' => ['required', 'string', 'min:6'],
            'email_code' => ['required', 'string', 'size:6'],
        ]);

        // 邮箱验证码校验通过后，验证码即被消耗（一次性使用）
        EmailVerificationController::verify($data['email'], $data['email_code']);

        $profileDefaults = app(RegistrationProfileDefaults::class);
        $user = User::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'avatar' => $profileDefaults->avatar($data['username']),
            'cover' => $profileDefaults->cover(),
            'password' => Hash::make($data['password']),
            'user_group_id' => 2, // 普通用户组
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
            'headline' => null,
            'bio' => null,
            'blog' => null,
            'company' => null,
            'location' => null,
            'language' => 'en-US',
            'create_time' => now(),
            'update_time' => now(),
            'disable_time' => null,
        ]);

        $token = $this->createToken($user, $request);

        return response()->json([
            'message' => __('messages.http_auth_1'),
            'token' => $token->token,
            'token_expire_at' => $token->expire_time?->toDateTimeString(),
            'user' => $user->load('userGroup'),
        ], 201);
    }

    /**
     * 登录
     *
     * 校验邮箱密码，通过后颁发新的登录令牌并更新最后登录信息。
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('messages.http_auth_5')],
            ]);
        }

        if ($user->isDisabled()) {
            throw ValidationException::withMessages([
                'email' => [__('messages.http_auth_6')],
            ]);
        }

        // 用户组 ability_normal_login=false 时禁止登录
        if (! $user->canLoginNormally()) {
            throw ValidationException::withMessages([
                'email' => [__('messages.http_auth_7')],
            ]);
        }

        $user->last_login_time = now();
        $user->last_login_ip = $request->ip() ?: '127.0.0.1';
        // $user->last_login_location = GeoLocation::location($request->ip());
        $user->last_login_location = GeoLocation::location(GeoLocation::getClientIP());
        $user->save();

        // 登录时强制校准内容计数，修正历史数据或并发写入造成的漂移
        $this->syncUserContentCounts($user, true);

        $token = $this->createToken($user, $request);

        return response()->json([
            'message' => __('messages.http_auth_2'),
            'token' => $token->token,
            'token_expire_at' => $token->expire_time?->toDateTimeString(),
            'user' => $user->load('userGroup'),
        ]);
    }

    /**
     * 通过邮箱验证码找回密码
     *
     * 校验验证码与邮箱后重置密码，并直接颁发登录令牌（自动登录）。
     */
    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:320'],
            'email_code' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => [__('messages.http_auth_8')],
            ]);
        }

        if ($user->isDisabled()) {
            throw ValidationException::withMessages([
                'email' => [__('messages.http_auth_6')],
            ]);
        }

        // 用户组 ability_normal_login=false 时禁止登录（即使找回密码也不自动登录）
        if (! $user->canLoginNormally()) {
            throw ValidationException::withMessages([
                'email' => [__('messages.http_auth_7')],
            ]);
        }

        // 找回密码验证码校验通过后即被消耗（一次性使用）
        EmailVerificationController::verifyReset($data['email'], $data['email_code']);

        $user->password = Hash::make($data['password']);
        $user->update_time = now();
        $user->last_login_time = now();
        $user->last_login_ip = $request->ip() ?: '127.0.0.1';
        $user->last_login_location = GeoLocation::location(GeoLocation::getClientIP());
        $user->save();

        // 自动登录前强制校准内容计数
        $this->syncUserContentCounts($user, true);

        // 重置成功后直接颁发令牌，自动登录
        $token = $this->createToken($user, $request);

        return response()->json([
            'message' => __('messages.http_auth_3'),
            'token' => $token->token,
            'token_expire_at' => $token->expire_time?->toDateTimeString(),
            'user' => $user->load('userGroup'),
        ]);
    }

    /**
     * 退出登录
     *
     * 删除当前请求使用的令牌，使该令牌立即失效。
     */
    public function logout(Request $request)
    {
        $header = $request->header('Authorization', '');

        if (preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            Token::where('token', trim($matches[1]))->delete();
        }

        return response()->json(['message' => __('messages.http_auth_4')]);
    }

    /**
     * 获取当前登录用户信息（需认证）
     */
    public function me(Request $request)
    {
        $user = $request->user();

        // 已登录用户刷新页面会自动走到这里：按内容表校准一次计数，
        // 10 分钟内不重复执行，避免每次刷新都产生额外查询。
        $this->syncUserContentCounts($user);

        $user->load('userGroup');

        $this->attachUserCounts(collect([$user]));

        return response()->json([
            'user' => $user,
        ]);
    }

    /**
     * 按内容表校准用户的内容计数（topic/article/question/answer/comment/reply）。
     *
     * 写入路径本身已维护计数，这里作为自愈手段：登录 / 自动登录时校正
     * 历史数据或并发写入造成的漂移。默认做 10 分钟节流，避免每次刷新都执行。
     *
     * @param  bool  $force  为 true 时忽略节流（登录成功后使用）
     */
    private function syncUserContentCounts(User $user, bool $force = false): void
    {
        $cacheKey = 'user-content-counts-synced:'.$user->user_id;

        if (! $force && ! Cache::add($cacheKey, true, now()->addMinutes(10))) {
            return;
        }

        $user->forceFill(UserContentCounts::syncUser((int) $user->user_id));

        Cache::put($cacheKey, true, now()->addMinutes(10));
    }
}
