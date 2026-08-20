<?php

namespace App\Support;

use App\Models\Token;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * 颁发 API 令牌的通用逻辑
 *
 * AuthController（邮箱密码登录 / 注册 / 重置密码）与 OAuthController（第三方登录）
 * 都需要为用户颁发令牌，提取该 trait 以避免重复实现。
 */
trait IssuesTokens
{
    /**
     * 为用户生成一个唯一的登录令牌（50 位随机串，7 天过期）。
     */
    protected function createToken(User $user, Request $request): Token
    {
        do {
            $tokenString = Str::random(50);
        } while (Token::where('token', $tokenString)->exists());

        return Token::create([
            'token' => $tokenString,
            'user_id' => $user->user_id,
            'device' => mb_substr((string) $request->userAgent(), 0, 600),
            'expire_time' => now()->addDays(7),
        ]);
    }
}
