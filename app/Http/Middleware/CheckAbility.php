<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * 用户组权限校验中间件
 *
 * 用法：Route::middleware(['ability:ability_create_question'])
 * 检查当前登录用户所属用户组的对应权限字段是否为 true。
 * 可传多个权限名，需同时满足。
 */
class CheckAbility
{
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['message' => '未认证：请先登录。'], 401);
        }

        $group = $user->userGroup;

        foreach ($abilities as $ability) {
            if (! $group || ! filter_var($group->{$ability}, FILTER_VALIDATE_BOOLEAN)) {
                return response()->json([
                    'message' => '无权限：当前用户组不具备该操作权限。',
                    'ability' => $ability,
                ], 403);
            }
        }

        return $next($request);
    }
}
