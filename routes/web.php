<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// SPA 前端路由兜底：所有未匹配路径都返回应用外壳，由 Vue Router 接管
Route::fallback(function () {
    return view('welcome');
});
