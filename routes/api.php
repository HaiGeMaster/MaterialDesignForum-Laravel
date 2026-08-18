<?php

use App\Http\Controllers\AnswerController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\UserOptionController;
use App\Http\Controllers\ReplyController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserGroupController;
use App\Http\Controllers\VoteController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// 发送邮箱注册验证码
Route::post('/email-code', [EmailVerificationController::class, 'send']);

// 发送邮箱找回密码验证码
Route::post('/email-code/reset', [EmailVerificationController::class, 'sendReset']);

// 邮箱验证码找回密码（成功后自动登录）
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

// 需要登录的接口（Bearer Token）
Route::middleware('auth.token')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // 个人资料编辑 / 图片上传（需要用户组具备 ability_edit_own_info 权限）
    Route::middleware('ability:ability_edit_own_info')->group(function () {
        Route::put('/user/profile', [UserController::class, 'updateProfile']);
        Route::post('/user/upload', [UserController::class, 'uploadImage']);
    });
    Route::put('/user/password', [UserController::class, 'updatePassword']);

    // 切换关注状态（用户 / 话题 / 提问 / 文章），需登录
    Route::post('/follows/toggle', [FollowController::class, 'toggle']);

    // 切换投票（提问 / 回答 / 文章 / 评论 / 回复），需登录
    Route::post('/votes/toggle', [VoteController::class, 'toggle']);

    // 富文本编辑器媒体上传（图片 / 视频），需登录
    Route::post('/media', [MediaController::class, 'upload']);

    // 用户媒体库（仅本人可查看），需登录
    Route::get('/users/{user}/media', [MediaController::class, 'index']);
});

// 批量查询当前用户对若干目标的关注状态（未登录时全部返回 false）
Route::get('/follows/status', [FollowController::class, 'status']);

// 获取关注关系下的用户列表（用户浏览对话框，未登录亦可浏览）
Route::post('/follows/get', [FollowController::class, 'get']);

Route::apiResource('users', UserController::class);

// 用户主页内容（提问 / 文章 / 回答）
Route::get('/users/{user}/questions', [UserController::class, 'questions']);
Route::get('/users/{user}/articles', [UserController::class, 'articles']);
Route::get('/users/{user}/answers', [UserController::class, 'answers']);

// 提问 / 文章 / 回答：公开读取，写操作需登录
Route::apiResource('questions', QuestionController::class)->only(['index', 'show']);
Route::apiResource('questions', QuestionController::class)
    ->middleware('auth.token')
    ->only(['store', 'update', 'destroy']);
Route::apiResource('articles', ArticleController::class)->only(['index', 'show']);
Route::apiResource('articles', ArticleController::class)
    ->middleware('auth.token')
    ->only(['store', 'update', 'destroy']);
Route::apiResource('answers', AnswerController::class)->only(['index', 'show']);
Route::apiResource('answers', AnswerController::class)
    ->middleware('auth.token')
    ->only(['store', 'update', 'destroy']);
// 评论：列表公开可读，发布 / 编辑 / 删除需登录
Route::apiResource('comments', CommentController::class)->only(['index']);
Route::apiResource('comments', CommentController::class)
    ->middleware('auth.token')
    ->only(['store', 'update', 'destroy']);
Route::apiResource('topics', TopicController::class);
// 回复：列表公开可读，发布 / 编辑 / 删除需登录
Route::apiResource('replies', ReplyController::class)->only(['index']);
Route::apiResource('replies', ReplyController::class)
    ->middleware('auth.token')
    ->only(['store', 'update', 'destroy']);
Route::apiResource('reports', ReportController::class);
Route::apiResource('follows', FollowController::class);
// 通知：列表 / 未读数 / 全部已读 / 清空全部 / 单条删除均需登录
Route::get('/notifications', [NotificationController::class, 'index'])->middleware('auth.token');
Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->middleware('auth.token');
Route::put('/notifications/read', [NotificationController::class, 'markAllRead'])->middleware('auth.token');
Route::delete('/notifications', [NotificationController::class, 'destroyAll'])->middleware('auth.token');
Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->middleware('auth.token');
// 用户选项（通知设置等键值对）：需登录
Route::get('/user-options/{name}', [UserOptionController::class, 'show'])->middleware('auth.token');
Route::put('/user-options/{name}', [UserOptionController::class, 'update'])->middleware('auth.token');
Route::apiResource('user-groups', UserGroupController::class);
Route::apiResource('votes', VoteController::class);
