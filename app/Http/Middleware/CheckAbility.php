<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * 用户组权限校验中间件
 *
 * 用法：
 *  - Route::middleware(['ability:ability_create_question'])：直接传用户组权限字段名
 *  - Route::middleware(['ability:module:index'])：按 {module} 路由参数动态解析后台模块管理权限
 *    （{module} 取 topics / questions / answers / articles / comments / replies /
 *      user-groups / users / reports 之一，映射到对应的 ability_admin_manage_* 字段）
 *
 * 检查当前登录用户所属用户组的对应权限字段是否为 true。
 * 可传多个权限名，需同时满足。
 */
class CheckAbility
{
    /**
     * 后台数据模块 → 管理权限字段 映射
     */
    private const MODULE_ABILITY_MAP = [
        'topics'      => 'ability_admin_manage_topic',
        'questions'   => 'ability_admin_manage_question',
        'answers'     => 'ability_admin_manage_answer',
        'articles'    => 'ability_admin_manage_article',
        'comments'    => 'ability_admin_manage_comment',
        'replies'     => 'ability_admin_manage_reply',
        'user-groups' => 'ability_admin_manage_user_group',
        'users'       => 'ability_admin_manage_user',
        'reports'     => 'ability_admin_manage_report',
    ];

    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['message' => __('messages.http_checkability_1')], 401);
        }

        $group = $user->userGroup;

        foreach ($abilities as $ability) {
            $field = $this->resolveAbilityField($request, $ability);

            // module:xxx 形式但 {module} 不在映射中（未知模块）：放行，由控制器返回 404
            if ($field === null && str_starts_with($ability, 'module:')) {
                continue;
            }

            if (! $field || ! $group || ! filter_var($group->{$field}, FILTER_VALIDATE_BOOLEAN)) {
                return response()->json([
                    'message' => __('messages.http_checkability_2'),
                    'ability' => $field ?? $ability,
                ], 403);
            }
        }

        return $next($request);
    }

    /**
     * 将中间件参数解析为实际要检查的用户组权限字段。
     *
     * 支持两种写法：
     *  - ability_admin_manage_topic（直接权限字段名，原样返回）
     *  - module:index（按 {module} 路由参数动态解析，后缀仅为语义标签）
     */
    private function resolveAbilityField(Request $request, string $ability): ?string
    {
        if (! str_starts_with($ability, 'module:')) {
            return $ability;
        }

        $module = $request->route('module');

        return self::MODULE_ABILITY_MAP[$module] ?? null;
    }
}
