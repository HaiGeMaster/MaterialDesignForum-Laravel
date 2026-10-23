<?php

namespace App\Http\Middleware;

use App\Models\Token;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * 可选用户解析中间件（全局，API 组）
 *
 * 从请求头 Authorization: Bearer <token> 中解析令牌并注入当前登录用户。
 * 与 auth.token 的区别：token 缺失或无效时【不】返回 401，
 * 而是以游客身份放行，使公开接口也能通过 $request->user() 感知登录用户。
 *
 * 典型场景：/topics?tab=following、/users?tab=following 等公开列表接口
 * 需要根据当前用户过滤"我关注的"数据。
 */
class ResolveOptionalUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('api/install/*')) {
            return $next($request);
        }

        $header = $request->header('Authorization', '');

        if (preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            $tokenString = trim($matches[1]);
            $token = Token::where('token', $tokenString)->first();

            // 令牌有效（未过期）且用户存在、未被禁用、用户组允许登录时才注入
            if ($token
                && (! $token->expire_time || ! $token->expire_time->isPast())) {
                /** @var User|null $user */
                $user = $token->user;

                if ($user && ! $user->isDisabled() && $user->canLoginNormally()) {
                    Auth::setUser($user);
                }
            }
        }

        return $next($request);
    }
}
