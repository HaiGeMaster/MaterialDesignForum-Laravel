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
use App\Http\Controllers\InstallerController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OAuthController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\UserOptionController;
use App\Http\Controllers\ReplyController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SignInController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserGroupController;
use App\Http\Controllers\VoteController;
use Illuminate\Support\Facades\Route;

// 首次安装部署接口（不需要认证；controller 内部通过 installed.lock 防止重复安装）
Route::prefix('install')->group(function () {
    Route::get('/status', [InstallerController::class, 'status']);          // 安装状态查询（公开）
    Route::get('/requirements', [InstallerController::class, 'requirements']);
    Route::post('/database', [InstallerController::class, 'database']);
    Route::post('/app', [InstallerController::class, 'app']);
    Route::post('/run', [InstallerController::class, 'run']);
});

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
    Route::get('/user/tokens', [UserController::class, 'tokens']);
    Route::delete('/user/tokens/{token}', [UserController::class, 'revokeToken']);
    Route::delete('/user/tokens', [UserController::class, 'revokeOtherTokens']);

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

    Route::get('/sign-in/status', [SignInController::class, 'status']);
    Route::post('/sign-in/check-in', [SignInController::class, 'checkIn']);

    // 管理后台首页概览：统计数据只允许具备后台登录权限的用户读取
    Route::get('/admin/overview', [AdminDashboardController::class, 'overview'])
        ->middleware('ability:ability_admin_login');

    // 单图数据：切换图表时间范围时仅查询对应一张表，避免重复跑全量 overview
    Route::get('/admin/chart', [AdminDashboardController::class, 'chart'])
        ->middleware('ability:ability_admin_login');

    Route::middleware('ability:ability_admin_manage_option')->group(function () {
        Route::put('/admin/options/theme', [AdminOptionController::class, 'updateTheme']);
        Route::get('/admin/options/mail', [AdminOptionController::class, 'mail']);
        Route::put('/admin/options/mail', [AdminOptionController::class, 'updateMail']);
        Route::get('/admin/options/oauth', [AdminOptionController::class, 'oauth']);
        Route::put('/admin/options/oauth', [AdminOptionController::class, 'updateOAuth']);
        Route::get('/admin/options/site-info', [AdminOptionController::class, 'siteInfo']);
        Route::put('/admin/options/site-info', [AdminOptionController::class, 'updateSiteInfo']);
        Route::get('/admin/options/about', [AdminOptionController::class, 'about']);
        Route::get('/admin/options/update', [AdminOptionController::class, 'checkUpdate']);
        Route::get('/admin/options/update-source', [AdminOptionController::class, 'updateSource']);
        Route::put('/admin/options/update-source', [AdminOptionController::class, 'updateUpdateSource']);
    });

    // 后台模块数据预览 / 详情 / 编辑 / 删除：所有模块共用同一路由
    // 权限由 ability:module:* 中间件按 {module} 路由参数动态映射到对应 ability_admin_manage_* 字段。
    // 注意：不能使用 {module:topics} 这类写法——Laravel 13 中 {param:value} 表示隐式模型绑定字段，
    // 且同 URI 的多个路由会在 RouteCollection 中互相覆盖，导致只有最后一条生效、权限校验错乱。
    Route::get('/admin/modules/{module}', [AdminModuleController::class, 'index'])
        ->middleware('ability:module:index');
    Route::get('/admin/modules/{module}/{id}', [AdminModuleController::class, 'show'])
        ->where('id', '[0-9]+')->middleware('ability:module:show');
    Route::put('/admin/modules/{module}/{id}', [AdminModuleController::class, 'update'])
        ->where('id', '[0-9]+')->middleware('ability:module:update');
    Route::delete('/admin/modules/{module}/{id}', [AdminModuleController::class, 'destroy'])
        ->where('id', '[0-9]+')->middleware('ability:module:destroy');

    // 创建接口：仅用户组模块开放（其他模块创建走前台）
    Route::post('/admin/modules/{module}', [AdminModuleController::class, 'store'])
        ->where('module', 'user-groups')
        ->middleware('ability:ability_admin_manage_user_group');
});

// 批量查询当前用户对若干目标的关注状态（未登录时全部返回 false）
Route::get('/follows/status', [FollowController::class, 'status']);

// 获取关注关系下的用户列表（用户浏览对话框，未登录亦可浏览）
Route::post('/follows/get', [FollowController::class, 'get']);
Route::get('/theme', [AdminOptionController::class, 'theme']);
Route::get('/search', [SearchController::class, 'index']);

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
