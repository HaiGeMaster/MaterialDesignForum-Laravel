<?php

/**
 * Author HaiGeMaster
 * @package MaterialDesignForum
 * @link https://github.com/HaiGeMaster
 * @copyright Copyright (c) 2023 HaiGeMaster
 * @start-date 2023/05/20-15:53:29
 */


use App\Http\Controllers\CommonController;
use App\Http\Controllers\OptionController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\OauthController;
use App\Http\Controllers\UpdateController;
use App\Http\Controllers\UserController;
use App\Services\Share;
use Illuminate\Support\Facades\Route;
// use Illuminate\Support\Facades\App;


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

Route::get('/user/image_captcha/{time?}', [UserController::class, 'GetImageCaptcha']);

Route::get('/auth/github/redirect', [SocialiteController::class, 'redirectToGithub']);
Route::get('/auth/github/callback',  [SocialiteController::class, 'handleGithubCallback']);
Route::get('/auth/microsoft/redirect', [SocialiteController::class, 'redirectToMicrosoft']);
Route::get('/auth/microsoft/callback',  [SocialiteController::class, 'handleMicrosoftCallback']);
Route::get('/auth/google/redirect', [SocialiteController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [SocialiteController::class, 'handleGoogleCallback']);


Route::get('/{any}', function () {
    // $html = file_get_contents(public_path('themes/MaterialDesignForum-Vuetify4/index.html'));
    // $html = str_replace('{lang}', app()->getLocale(), $html);
    $html = Share::GetRouteThemeIndex();
    return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
})->where('any', '.*');
