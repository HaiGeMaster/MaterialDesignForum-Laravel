<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminModuleController;
use App\Http\Controllers\AdminOptionController;
use App\Http\Controllers\AnswerController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OAuthController;
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

// 第三方 OAuth 登录：返回已配置的 provider 列表，前端 ajax 拉取决定渲染哪些图标按钮
// redirect / callback 这两个会触发整页跳转的路由在 routes/web.php 注册（不带 /api 前缀）
Route::get('/oauth/providers', [OAuthController::class, 'providers']);

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

    // 已登录用户：第三方账号绑定 / 解绑 / 列表
    Route::get('/user/oauth', [OAuthController::class, 'index']);
    Route::delete('/user/oauth/{oauth}', [OAuthController::class, 'unbind']);

    // 富文本编辑器媒体上传（图片 / 视频），需登录
    Route::post('/media', [MediaController::class, 'upload']);

    // 用户媒体库（仅本人可查看），需登录
    Route::get('/users/{user}/media', [MediaController::class, 'index']);

    // 批量删除媒体（仅本人），需登录
    Route::delete('/media', [MediaController::class, 'destroy']);

    // 管理后台首页概览：统计数据只允许具备后台登录权限的用户读取
    Route::get('/admin/overview', [AdminDashboardController::class, 'overview'])
        ->middleware('ability:ability_admin_login');

    Route::middleware('ability:ability_admin_manage_option')->group(function () {
        Route::put('/admin/options/theme', [AdminOptionController::class, 'updateTheme']);
        Route::get('/admin/options/mail', [AdminOptionController::class, 'mail']);
        Route::put('/admin/options/mail', [AdminOptionController::class, 'updateMail']);
        Route::get('/admin/options/oauth', [AdminOptionController::class, 'oauth']);
        Route::put('/admin/options/oauth', [AdminOptionController::class, 'updateOAuth']);
        Route::get('/admin/options/site-info', [AdminOptionController::class, 'siteInfo']);
        Route::put('/admin/options/site-info', [AdminOptionController::class, 'updateSiteInfo']);
    });

    // 后台模块数据预览：每个模块对应自身的管理权限中间件
    Route::get('/admin/modules/{module:topics}', [AdminModuleController::class, 'index'])
        ->middleware('ability:ability_admin_manage_topic');
    Route::get('/admin/modules/{module:questions}', [AdminModuleController::class, 'index'])
        ->middleware('ability:ability_admin_manage_question');
    Route::get('/admin/modules/{module:answers}', [AdminModuleController::class, 'index'])
        ->middleware('ability:ability_admin_manage_answer');
    Route::get('/admin/modules/{module:articles}', [AdminModuleController::class, 'index'])
        ->middleware('ability:ability_admin_manage_article');
    Route::get('/admin/modules/{module:comments}', [AdminModuleController::class, 'index'])
        ->middleware('ability:ability_admin_manage_comment');
    Route::get('/admin/modules/{module:replies}', [AdminModuleController::class, 'index'])
        ->middleware('ability:ability_admin_manage_reply');
    Route::get('/admin/modules/{module:user-groups}', [AdminModuleController::class, 'index'])
        ->middleware('ability:ability_admin_manage_user_group');
    Route::get('/admin/modules/{module:users}', [AdminModuleController::class, 'index'])
        ->middleware('ability:ability_admin_manage_user');
    Route::get('/admin/modules/{module:reports}', [AdminModuleController::class, 'index'])
        ->middleware('ability:ability_admin_manage_report');

    // 后台模块单条详情（用于举报对话框展示举报对象内容等）
    Route::get('/admin/modules/{module:topics}/{id}', [AdminModuleController::class, 'show'])
        ->where('id', '[0-9]+')->middleware('ability:ability_admin_manage_topic');
    Route::get('/admin/modules/{module:questions}/{id}', [AdminModuleController::class, 'show'])
        ->where('id', '[0-9]+')->middleware('ability:ability_admin_manage_question');
    Route::get('/admin/modules/{module:answers}/{id}', [AdminModuleController::class, 'show'])
        ->where('id', '[0-9]+')->middleware('ability:ability_admin_manage_answer');
    Route::get('/admin/modules/{module:articles}/{id}', [AdminModuleController::class, 'show'])
        ->where('id', '[0-9]+')->middleware('ability:ability_admin_manage_article');
    Route::get('/admin/modules/{module:comments}/{id}', [AdminModuleController::class, 'show'])
        ->where('id', '[0-9]+')->middleware('ability:ability_admin_manage_comment');
    Route::get('/admin/modules/{module:replies}/{id}', [AdminModuleController::class, 'show'])
        ->where('id', '[0-9]+')->middleware('ability:ability_admin_manage_reply');
    Route::get('/admin/modules/{module:user-groups}/{id}', [AdminModuleController::class, 'show'])
        ->where('id', '[0-9]+')->middleware('ability:ability_admin_manage_user_group');
    Route::get('/admin/modules/{module:users}/{id}', [AdminModuleController::class, 'show'])
        ->where('id', '[0-9]+')->middleware('ability:ability_admin_manage_user');
    Route::get('/admin/modules/{module:reports}/{id}', [AdminModuleController::class, 'show'])
        ->where('id', '[0-9]+')->middleware('ability:ability_admin_manage_report');

    // 后台模块单条编辑 / 删除：每个模块用对应的 ability 中间件
    // reports 模块暂时只读（路由也注册但 controller 内会拒绝），便于前端统一处理
    Route::put('/admin/modules/{module:topics}/{id}', [AdminModuleController::class, 'update'])
        ->where('id', '[0-9]+')->middleware('ability:ability_admin_manage_topic');
    Route::delete('/admin/modules/{module:topics}/{id}', [AdminModuleController::class, 'destroy'])
        ->where('id', '[0-9]+')->middleware('ability:ability_admin_manage_topic');

    Route::put('/admin/modules/{module:questions}/{id}', [AdminModuleController::class, 'update'])
        ->where('id', '[0-9]+')->middleware('ability:ability_admin_manage_question');
    Route::delete('/admin/modules/{module:questions}/{id}', [AdminModuleController::class, 'destroy'])
        ->where('id', '[0-9]+')->middleware('ability:ability_admin_manage_question');

    Route::put('/admin/modules/{module:answers}/{id}', [AdminModuleController::class, 'update'])
        ->where('id', '[0-9]+')->middleware('ability:ability_admin_manage_answer');
    Route::delete('/admin/modules/{module:answers}/{id}', [AdminModuleController::class, 'destroy'])
        ->where('id', '[0-9]+')->middleware('ability:ability_admin_manage_answer');

    Route::put('/admin/modules/{module:articles}/{id}', [AdminModuleController::class, 'update'])
        ->where('id', '[0-9]+')->middleware('ability:ability_admin_manage_article');
    Route::delete('/admin/modules/{module:articles}/{id}', [AdminModuleController::class, 'destroy'])
        ->where('id', '[0-9]+')->middleware('ability:ability_admin_manage_article');

    Route::put('/admin/modules/{module:comments}/{id}', [AdminModuleController::class, 'update'])
        ->where('id', '[0-9]+')->middleware('ability:ability_admin_manage_comment');
    Route::delete('/admin/modules/{module:comments}/{id}', [AdminModuleController::class, 'destroy'])
        ->where('id', '[0-9]+')->middleware('ability:ability_admin_manage_comment');

    Route::put('/admin/modules/{module:replies}/{id}', [AdminModuleController::class, 'update'])
        ->where('id', '[0-9]+')->middleware('ability:ability_admin_manage_reply');
    Route::delete('/admin/modules/{module:replies}/{id}', [AdminModuleController::class, 'destroy'])
        ->where('id', '[0-9]+')->middleware('ability:ability_admin_manage_reply');

    Route::post('/admin/modules/{module:user-groups}', [AdminModuleController::class, 'store'])
        ->middleware('ability:ability_admin_manage_user_group');
    Route::put('/admin/modules/{module:user-groups}/{id}', [AdminModuleController::class, 'update'])
        ->where('id', '[0-9]+')->middleware('ability:ability_admin_manage_user_group');
    Route::delete('/admin/modules/{module:user-groups}/{id}', [AdminModuleController::class, 'destroy'])
        ->where('id', '[0-9]+')->middleware('ability:ability_admin_manage_user_group');

    Route::put('/admin/modules/{module:users}/{id}', [AdminModuleController::class, 'update'])
        ->where('id', '[0-9]+')->middleware('ability:ability_admin_manage_user');
    Route::delete('/admin/modules/{module:users}/{id}', [AdminModuleController::class, 'destroy'])
        ->where('id', '[0-9]+')->middleware('ability:ability_admin_manage_user');

    // 举报处理：仅允许更新处理状态，不支持删除
    Route::put('/admin/modules/{module:reports}/{id}', [AdminModuleController::class, 'update'])
        ->where('id', '[0-9]+')->middleware('ability:ability_admin_manage_report');
});

// 批量查询当前用户对若干目标的关注状态（未登录时全部返回 false）
Route::get('/follows/status', [FollowController::class, 'status']);

// 获取关注关系下的用户列表（用户浏览对话框，未登录亦可浏览）
Route::post('/follows/get', [FollowController::class, 'get']);
Route::get('/theme', [AdminOptionController::class, 'theme']);

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
