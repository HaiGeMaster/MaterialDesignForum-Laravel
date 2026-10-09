<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 根据前端 cookie 中的 locale 临时设置后端语言（API 组全局中间件）。
 *
 * 前端 i18n/index.js 的 syncHtmlLang() 在切换语言时写入 cookie：
 *   document.cookie = `locale=${lang}; path=/; max-age=31536000; samesite=lax`
 *
 * 本中间件在每次请求开始时读取该 cookie 并调用 app()->setLocale()，
 * 使后端 __('messages.xxx') 等翻译按用户首选语言渲染。
 * 设置仅对本次请求生效，不持久化到 config。
 *
 * 行为说明：
 *   - cookie 缺失 / 格式非法 → 回退到 config('app.locale')（默认 zh-CN）
 *   - cookie 为已注册语言（如 zh-CN / en-US）→ 直接使用
 *   - cookie 为未注册语言（如 de-DE / ja-JP）→ Laravel 自动回退到
 *     config('app.fallback_locale')（默认 en-US），即返回英文消息
 */
class SetLocaleFromCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->cookie('locale');

        // 仅接受形如 zh-CN / en-US / de-DE / zh / en 的语言代码，防止任意字符串注入
        if (is_string($locale) && preg_match('/^[a-z]{2}(-[A-Z]{2})?$/', $locale)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
