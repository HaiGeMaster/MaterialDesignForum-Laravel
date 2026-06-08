<?php

use App\Http\Controllers\CommonController;
use App\Http\Controllers\OptionController;

use Illuminate\Support\Facades\App;
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

Route::get('/{any}', function () {
    return response()->file(public_path('themes/MaterialDesignForum-Vuetify4/index.html'));
})->where('any', '.*');
