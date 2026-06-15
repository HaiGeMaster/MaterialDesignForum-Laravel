<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckInstalled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 已安装标志（文件 / env / database 都可）
        // if (!file_exists(storage_path('installed.lock'))) {
        //     // 防止死循环
        //     if (!$request->is('install*')) {
        //         return redirect('/install');
        //     }
        // }
        
        return $next($request);
    }
}
