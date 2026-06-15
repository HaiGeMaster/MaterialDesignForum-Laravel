<?php

use App\Http\Controllers\CommonController;
use App\Http\Controllers\OptionController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\OauthController;

use App\Http\Controllers\UpdateController;
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

    Route::get('/test/GetUserOauthBindings', function () {
        $result = OauthController::GetUserOauthBindings('4b155107d21d43a90cd6bdb0666b2c1a');
        return response()->json($result);
    });
}

Route::prefix('install')->group(function () {
    // Route::get('/', [InstallController::class, 'index']);
    // Route::post('/', [InstallController::class, 'store']);
});

// /update/server/info
Route::get('/update/server/info', [UpdateController::class, 'serveUpdateInfo']);

Route::get('/auth/github/redirect', [SocialiteController::class, 'redirectToGithub']);
Route::get('/auth/github/callback',  [SocialiteController::class, 'handleGithubCallback']);// routes/web.php
Route::get('/auth/microsoft/redirect', [SocialiteController::class, 'redirectToMicrosoft']);
Route::get('/auth/microsoft/callback',  [SocialiteController::class, 'handleMicrosoftCallback']);

Route::get('/{any}', function () {
    return response()->file(public_path('themes/MaterialDesignForum-Vuetify4/index.html'));
})->where('any', '.*');
