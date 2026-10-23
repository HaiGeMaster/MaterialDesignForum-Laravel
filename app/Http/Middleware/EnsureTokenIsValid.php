<?php

namespace App\Http\Middleware;

use App\Models\Token;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Token 认证中间件
 *
 * 从请求头 Authorization: Bearer <token> 中解析令牌，
 * 校验 token 表记录、过期时间、用户是否被禁用，
 * 校验通过后通过 auth()->setUser() 注入当前登录用户。
 */
class EnsureTokenIsValid
{
    /**
     * 从请求头中提取 Bearer token 字符串
     */
    private function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');

        if (preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $tokenString = $this->extractToken($request);

        if (! $tokenString) {
            return response()->json(['message' => __('messages.http_ensuretokenisvalid_1')], 401);
        }

        $token = Token::where('token', $tokenString)->first();

        if (! $token) {
            return response()->json(['message' => __('messages.http_ensuretokenisvalid_2')], 401);
        }

        if ($token->expire_time && $token->expire_time->isPast()) {
            return response()->json(['message' => __('messages.http_ensuretokenisvalid_3')], 401);
        }

        /** @var User|null $user */
        $user = $token->user;

        if (! $user) {
            return response()->json(['message' => __('messages.http_ensuretokenisvalid_4')], 401);
        }

        if ($user->isDisabled()) {
            return response()->json(['message' => __('messages.http_ensuretokenisvalid_5')], 401);
        }

        // 用户组 ability_normal_login=false 时拒绝继续会话
        if (! $user->canLoginNormally()) {
            return response()->json(['message' => __('messages.http_ensuretokenisvalid_6')], 401);
        }

        // 将当前用户注入认证体系，后续可用 $request->user() / Auth::user() 获取
        Auth::setUser($user);

        return $next($request);
    }
}
