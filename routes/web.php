<?php

use App\Http\Controllers\CommonController;
use App\Http\Controllers\OptionController;
use App\Http\Controllers\Auth\SocialiteController;

// use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;


if (config('app.debug')) {
    Route::get('/test', function () {
        // return response()->json(['message' => 'Test route works!']);
        return OptionController::GetAll();
    });

    Route::get('/test/admin/app_base_info', function () {
        $result = CommonController::GetAppBaseInfo();
        return response()->json($result);
    });
}

Route::prefix('install')->group(function () {
    Route::get('/', [InstallController::class, 'index']);
    Route::post('/', [InstallController::class, 'store']);
});


Route::get('/auth/github/redirect', [SocialiteController::class, 'redirectToGithub']);
Route::get('/auth/github/callback',  [SocialiteController::class, 'handleGithubCallback']);// routes/web.php
Route::get('/auth/microsoft/redirect', [SocialiteController::class, 'redirectToMicrosoft']);
Route::get('/auth/microsoft/callback',  [SocialiteController::class, 'handleMicrosoftCallback']);

Route::get('/{any}', function () {
    return response()->file(public_path('themes/MaterialDesignForum-Vuetify4/index.html'));
})->where('any', '.*');
