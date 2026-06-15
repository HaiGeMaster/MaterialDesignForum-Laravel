<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\{
    AdminController,
    AnswerController,
    ArticleController,
    CommonController,
    CommentController,
    FollowController,
    NotificationController,
    OauthController,
    QuestionController,
    ReplyController,
    ReportController,
    TopicController,
    UserController,
    UserGroupController,
    UserOptionController,
    UpdateController,
    VoteController,
};

// ==================== Test API（开发调试，仅 debug 模式可用） ====================

if (config('app.debug')) {
    // Route::get('/api/test/GetUserOptionNotificationValue', fn() => response()->json(['message' => 'Test endpoint']));
    // Route::get('/api/test/GetServerInfo', fn() => response()->json(['message' => 'Test endpoint']));
}


// ==================== Admin 管理员 ====================

Route::post('/api/admin/data/count', function (Request $request) {
    $result = AdminController::GetDataCount(
        $request->input('user_token', $request->bearerToken())
    );
    return response()->json($result);
});

Route::post('/api/admin/data/between_timestamps', function (Request $request) {
    $result = AdminController::GetDataBetweenTimestamps(
        $request->input('user_token', $request->bearerToken()),
        $request->input('time_type'),
        $request->input('model_type')
    );
    return response()->json($result);
});

Route::post('/api/admin/data/between_timestamps_all', function (Request $request) {
    $result = AdminController::GetDataBetweenTimestampsAll(
        $request->input('user_token', $request->bearerToken()),
        $request->input('time_type')
    );
    return response()->json($result);
});

Route::post('/api/admin/data/server_info', function (Request $request) {
    $result = AdminController::GetServerInfo(
        $request->input('user_token', $request->bearerToken())
    );
    return response()->json($result);
});

Route::post('/api/admin/data/mail_info/get', function (Request $request) {
    $result = AdminController::GetMailConfig(
        $request->input('user_token', $request->bearerToken())
    );
    return response()->json($result);
});

Route::post('/api/admin/data/mail_info/set', function (Request $request) {
    $result = AdminController::SetMailConfig(
        $request->input('user_token', $request->bearerToken()),
        $request->input('mail_info')
    );
    return response()->json($result);
});

Route::post('/api/admin/data/oauth_info/get', function (Request $request) {
    $result = AdminController::GetOauthConfig(
        $request->input('user_token', $request->bearerToken())
    );
    return response()->json($result);
});

Route::post('/api/admin/data/oauth_info/set', function (Request $request) {
    $result = AdminController::SetOauthConfig(
        $request->input('user_token', $request->bearerToken()),
        $request->input('oauth_info')
    );
    return response()->json($result);
});

// ==================== Common 通用 ====================


Route::post('/api/common/app_base_info/get', function (Request $request) {
    $result = CommonController::GetAppBaseInfo(
        $request->input('user_token', $request->bearerToken())
    );
    return response()->json($result);
});

Route::post('/api/common/app_base_info/set', function (Request $request) {
    $result = CommonController::SetAppBaseInfo(
        $request->input('user_token', $request->bearerToken()),
        $request->input('app_base_info')
    );
    return response()->json($result);
});

///api/language/get/${val}
Route::post('/api/common/language/{val}', function (Request $request, $val) {
    $result = CommonController::GetLanguage($val);
    return response()->json($result);
});

// /api/update/check
Route::post('/api/update/check', function (Request $request) {
    $result = UpdateController::checkUpdate($request->input('user_token'));
    return response()->json($result);
});

///api/core/update/server/info
Route::post('/api/update/server/info', function (Request $request) {
    $result = UpdateController::serveUpdateInfo();
    return response()->json($result);
});


// ==================== Answer 回答 ====================

Route::post('/api/answer/add', function (Request $request) {
    $result = AnswerController::AddAnswer(
        $request->input('question_id'),
        urldecode($request->input('content_markdown')),
        urldecode($request->input('content_rendered')),
        $request->input('user_token', $request->bearerToken())
    );
    return response()->json($result);
});

Route::post('/api/answer/edit', function (Request $request) {
    $result = AnswerController::EditAnswer(
        $request->input('answer_id'),
        $request->input('content_markdown'),
        $request->input('content_rendered'),
        $request->input('user_token', $request->bearerToken())
    );
    return response()->json($result);
});

Route::post('/api/answer/get', function (Request $request) {
    $result = AnswerController::GetAnswer(
        $request->input('answer_id'),
        $request->input('user_token', $request->bearerToken() ?? '')
    );
    return response()->json($result);
});

Route::post('/api/answers/delete', function (Request $request) {
    $result = AnswerController::DeleteAnswers(
        $request->input('answer_ids'),
        $request->input('user_token', $request->bearerToken())
    );
    return response()->json($result);
});

Route::post('/api/answers/get', function (Request $request) {
    $result = AnswerController::GetAnswers(
        $request->input('question_id'),
        $request->input('order'),
        $request->input('page'),
        $request->input('user_token', $request->bearerToken()),
        $request->input('per_page', 20),
        $request->input('search_keywords', ''),
        $request->input('search_field', [])
    );
    return response()->json($result);
});

// ==================== Article 文章 ====================

Route::post('/api/article/add', function (Request $request) {
    $result = ArticleController::AddArticle(
        $request->input('title'),
        $request->input('topics'),
        urldecode($request->input('content_markdown')),
        urldecode($request->input('content_rendered')),
        $request->input('user_token', $request->bearerToken())
    );
    return response()->json($result);
});

Route::post('/api/article/edit', function (Request $request) {
    $result = ArticleController::EditArticle(
        $request->input('article_id'),
        $request->input('title'),
        $request->input('topics'),
        urldecode($request->input('content_markdown')),
        urldecode($request->input('content_rendered')),
        $request->input('user_token', $request->bearerToken())
    );
    return response()->json($result);
});

Route::post('/api/article/get', function (Request $request) {
    $result = ArticleController::GetArticle(
        $request->input('article_id'),
        $request->input('user_token', $request->bearerToken() ?? '')
    );
    return response()->json($result);
});

Route::post('/api/articles/delete', function (Request $request) {
    $result = ArticleController::DeleteArticles(
        $request->input('article_ids'),
        $request->input('user_token', $request->bearerToken())
    );
    return response()->json($result);
});

Route::post('/api/articles/get', function (Request $request) {
    $result = ArticleController::GetArticles(
        $request->input('order'),
        $request->input('page'),
        $request->input('following', false),
        $request->input('user_token', $request->bearerToken() ?? ''),
        $request->input('per_page', 20),
        $request->input('search_keywords', ''),
        $request->input('search_field', []),
        $request->input('specify_topic_id', '')
    );
    return response()->json($result);
});

// ==================== Comment 评论 ====================

Route::post('/api/comment/add', function (Request $request) {
    $result = CommentController::AddComment(
        $request->input('commentable_id'),
        $request->input('commentable_type'),
        urldecode($request->input('content')),
        $request->input('user_token', $request->bearerToken())
    );
    return response()->json($result);
});

Route::post('/api/comment/edit', function (Request $request) {
    $result = CommentController::EditComment(
        $request->input('comment_id'),
        urldecode($request->input('content')),
        $request->input('user_token', $request->bearerToken())
    );
    return response()->json($result);
});

Route::post('/api/comments/delete', function (Request $request) {
    $result = CommentController::DeleteComments(
        $request->input('comment_ids'),
        $request->input('user_token', $request->bearerToken())
    );
    return response()->json($result);
});

Route::post('/api/comments/get', function (Request $request) {
    $result = CommentController::GetComments(
        $request->input('commentable_id'),
        $request->input('commentable_type'),
        $request->input('order'),
        $request->input('page'),
        $request->input('user_token', $request->bearerToken()),
        $request->input('per_page', 20),
        $request->input('search_keywords', ''),
        $request->input('search_field', [])
    );
    return response()->json($result);
});

// ==================== Follow 关注 ====================

Route::post('/api/follows/get', function (Request $request) {
    $result = FollowController::GetFollows(
        $request->input('modes'),
        $request->input('followable_type'),
        $request->input('followable_id'),
        $request->input('page', 1),                       // 不传时用 1
        $request->input('user_token', $request->bearerToken() ?? ''),
        $request->input('per_page', 20),
        $request->input('is_admin', false)
    );
    return response()->json($result);
});

// ==================== Question 提问 ====================

Route::post('/api/question/add', function (Request $request) {
    $result = QuestionController::AddQuestion(
        $request->input('title'),
        $request->input('topics'),
        urldecode($request->input('content_markdown')),
        urldecode($request->input('content_rendered')),
        $request->input('user_token', $request->bearerToken())
    );
    return response()->json($result);
});

Route::post('/api/question/edit', function (Request $request) {
    $result = QuestionController::EditQuestion(
        $request->input('question_id'),
        $request->input('title'),
        $request->input('topics'),
        urldecode($request->input('content_markdown')),
        urldecode($request->input('content_rendered')),
        $request->input('user_token', $request->bearerToken())
    );
    return response()->json($result);
});

Route::post('/api/question/get', function (Request $request) {
    $result = QuestionController::GetQuestion(
        $request->input('question_id'),
        $request->input('user_token', $request->bearerToken() ?? '')
    );
    return response()->json($result);
});

Route::post('/api/questions/delete', function (Request $request) {
    $result = QuestionController::DeleteQuestions(
        $request->input('question_ids'),
        $request->input('user_token', $request->bearerToken())
    );
    return response()->json($result);
});

Route::post('/api/questions/get', function (Request $request) {
    $result = QuestionController::GetQuestions(
        $request->input('order'),
        $request->input('page'),
        $request->input('following', false),
        $request->input('user_token', $request->bearerToken() ?? ''),
        $request->input('per_page', 20),
        $request->input('search_keywords', ''),
        $request->input('search_field', []),
        $request->input('specify_topic_id', '')
    );
    return response()->json($result);
});

// ==================== Reply 回复 ====================

Route::post('/api/reply/add', function (Request $request) {
    $result = ReplyController::AddReply(
        $request->input('replyable_id'),
        $request->input('replyable_type'),
        $request->input('replyable_comment_id'),
        urldecode($request->input('content')),
        $request->input('user_token', $request->bearerToken()),
        $request->input('replyable_user_id', 0)
    );
    return response()->json($result);
});

Route::post('/api/reply/edit', function (Request $request) {
    $result = ReplyController::EditReply(
        $request->input('reply_id'),
        urldecode($request->input('content')),
        $request->input('user_token', $request->bearerToken())
    );
    return response()->json($result);
});

Route::post('/api/replys/delete', function (Request $request) {
    $result = ReplyController::DeleteReplys(
        $request->input('reply_ids'),
        $request->input('user_token', $request->bearerToken())
    );
    return response()->json($result);
});

Route::post('/api/replys/get', function (Request $request) {
    $result = ReplyController::GetReplys(
        $request->input('replyable_comment_id'),
        $request->input('order'),
        $request->input('page'),
        $request->input('user_token', $request->bearerToken()),
        $request->input('per_page', 20),
        $request->input('search_keywords', ''),
        $request->input('search_field', [])
    );
    return response()->json($result);
});

// ==================== Report 举报 ====================

Route::post('/api/report/add', function (Request $request) {
    $result = ReportController::AddReport(
        $request->input('reportable_id'),
        $request->input('reportable_type'),
        $request->input('user_token', $request->bearerToken()),
        $request->input('reason')
    );
    return response()->json($result);
});

Route::post('/api/reports/get', function (Request $request) {
    $result = ReportController::GetReports(
        $request->input('order'),
        $request->input('page'),
        $request->input('user_token', $request->bearerToken()),
        $request->input('per_page', 20),
        $request->input('search_keywords', ''),
        $request->input('search_field', [])
    );
    return response()->json($result);
});

// ==================== Topic 话题 ====================

Route::post('/api/topic/add', function (Request $request) {
    $result = TopicController::AddTopic(
        $request->input('name'),
        $request->input('description'),
        $request->input('cover', ''),
        $request->input('user_token', $request->bearerToken() ?? '')
    );
    return response()->json($result);
});

Route::post('/api/topic/edit', function (Request $request) {
    $result = TopicController::EditTopic(
        $request->input('topic_id'),
        $request->input('name'),
        $request->input('description'),
        $request->input('cover', ''),
        $request->input('user_token', $request->bearerToken())
    );
    return response()->json($result);
});

Route::post('/api/topic/get', function (Request $request) {
    $result = TopicController::GetTopic(
        $request->input('topic_id'),
        $request->input('user_token', $request->bearerToken() ?? '')
    );
    return response()->json($result);
});

Route::post('/api/topics/delete', function (Request $request) {
    $result = TopicController::DeleteTopics(
        $request->input('topic_ids'),
        $request->input('user_token', $request->bearerToken())
    );
    return response()->json($result);
});

Route::post('/api/topics/get', function (Request $request) {
    $result = TopicController::GetTopics(
        $request->input('order'),
        $request->input('page'),
        $request->input('following', false),
        $request->input('user_token', $request->bearerToken() ?? ''),
        $request->input('per_page', 20),
        $request->input('search_keywords', ''),
        $request->input('search_field', [])
    );
    return response()->json($result);
});

// ==================== UserGroup 用户组 ====================

Route::post('/api/user_group/add', function (Request $request) {
    $result = UserGroupController::AddUserGroup(
        $request->input('user_group_data'),
        $request->input('user_token', $request->bearerToken())
    );
    return response()->json($result);
});

Route::post('/api/user_group/edit', function (Request $request) {
    $result = UserGroupController::EditUserGroup(
        $request->input('user_group_id'),
        $request->input('user_group_data'),
        $request->input('user_token', $request->bearerToken())
    );
    return response()->json($result);
});

Route::post('/api/user_group/get', function (Request $request) {
    $result = UserGroupController::GetUserGroup(
        $request->input('user_group_id'),
        $request->input('user_token', $request->bearerToken())
    );
    return response()->json($result);
});

Route::post('/api/user_groups/delete', function (Request $request) {
    $result = UserGroupController::DeleteUserGroups(
        $request->input('user_group_ids'),
        $request->input('user_token', $request->bearerToken())
    );
    return response()->json($result);
});

Route::post('/api/user_groups/get', function (Request $request) {
    $result = UserGroupController::GetUserGroups(
        $request->input('order'),
        $request->input('page'),
        $request->input('user_token', $request->bearerToken()),
        $request->input('per_page', 20),
        $request->input('search_keywords', ''),
        $request->input('search_field', [])
    );
    return response()->json($result);
});

// ==================== User 用户 ====================

Route::get('/api/user/image_captcha/{time?}', [UserController::class, 'GetImageCaptcha']);


Route::post('/api/user/oauths/get', function (Request $request) {
    $result = OauthController::GetUserOauthBindings(
        $request->input('user_token', $request->bearerToken()),
    );
    return response()->json($result);
});


Route::post('/api/user/oauth/delete', function (Request $request) {
    $result = OauthController::DeleteOauth(
        $request->input('user_token', $request->bearerToken()),
        $request->input('oauth_id')
    );
    return response()->json($result);
});


Route::post('/api/user/answers/get', function (Request $request) {
    $result = UserController::GetUserAnswers(
        $request->input('user_id'),
        $request->input('order'),
        $request->input('page'),
        $request->input('user_token', $request->bearerToken()),
        $request->input('per_page', 20)
    );
    return response()->json($result);
});

Route::post('/api/user/articles/get', function (Request $request) {
    $result = UserController::GetUserArticles(
        $request->input('user_id'),
        $request->input('order'),
        $request->input('page'),
        $request->input('user_token', $request->bearerToken()),
        $request->input('per_page', 20)
    );
    return response()->json($result);
});

Route::post('/api/user/auto_login', function (Request $request) {
    $result = UserController::Auto_Login(
        $request->input('user_token', $request->bearerToken())
    );
    return response()->json($result);
});

Route::post('/api/user/avatar/reset', function (Request $request) {
    $result = UserController::ResetAvatar(
        $request->input('user_id'),
        $request->input('user_token', $request->bearerToken())
    );
    return response()->json($result);
});

Route::post('/api/user/avatar/upload', function (Request $request) {
    $result = UserController::UploadAvatar(
        $request->input('user_token', $request->bearerToken()),
        $request->input('avatar')
    );
    return response()->json($result);
});

Route::post('/api/user/cover/reset', function (Request $request) {
    $result = UserController::ResetCover(
        $request->input('user_id'),
        $request->input('user_token', $request->bearerToken())
    );
    return response()->json($result);
});

Route::post('/api/user/cover/upload', function (Request $request) {
    $result = UserController::UploadCover(
        $request->input('user_token', $request->bearerToken()),
        $request->input('cover')
    );
    return response()->json($result);
});

Route::post('/api/user/editinfo', function (Request $request) {
    $result = UserController::EditInfo(
        $request->input('email'),
        $request->input('username'),
        $request->input('user_group_id'),
        $request->input('headline'),
        $request->input('blog'),
        $request->input('company'),
        $request->input('location'),
        $request->input('bio'),
        $request->input('user_token', $request->bearerToken()),
        $request->input('edit_target_user_id')
    );
    return response()->json($result);
});

Route::post('/api/user/email_captcha', function (Request $request) {
    $result = UserController::GetEmailCaptcha(
        $request->input('email'),
        $request->input('lang', '')
    );
    return response()->json($result);
});

Route::post('/api/user/follow', function (Request $request) {
    $result = FollowController::Follow(
        $request->input('user_token', $request->bearerToken()),
        $request->input('followable_type'),
        $request->input('followable_id')
    );
    return response()->json($result);
});

Route::post('/api/user/follow/contacts', function (Request $request) {
    $result = FollowController::GetFollowMutualAttentionList(
        $request->input('user_token', $request->bearerToken()),
        $request->input('page', 1),
        $request->input('per_page', 20)
    );
    return response()->json($result);
});

Route::post('/api/user/get', function (Request $request) {
    $result = UserController::GetUser(
        $request->input('user_id'),
        $request->input('user_token', $request->bearerToken() ?? ''),
        $request->input('is_admin', false)
    );
    return response()->json($result);
});

Route::post('/api/user/login', function (Request $request) {
    $result = UserController::Login(
        $request->input('username_or_email'),
        $request->input('password'),
        $request->input('image_captcha', ''),
        $request->input('captcha_key', '')
    );
    return response()->json($result);
});

Route::post('/api/user/notification/delete', function (Request $request) {
    $result = NotificationController::DeleteNotification(
        $request->input('user_token', $request->bearerToken()),
        $request->input('notification_id')
    );
    return response()->json($result);
});

Route::post('/api/user/notifications/delete/all', function (Request $request) {
    $result = NotificationController::DeleteAllNotifications(
        $request->input('user_token', $request->bearerToken())
    );
    return response()->json($result);
});

Route::post('/api/user/notifications/get', function (Request $request) {
    $result = NotificationController::GetUserInteractionNotifications(
        $request->input('user_token', $request->bearerToken()),
        $request->input('order'),
        $request->input('page'),
        $request->input('per_page', 20)
    );
    return response()->json($result);
});

Route::post('/api/user/option/delete', function (Request $request) {
    $result = UserOptionController::DeleteUserOption(
        $request->input('user_token', $request->bearerToken()),
        $request->input('name')
    );
    return response()->json($result);
});

Route::post('/api/user/option/get', function (Request $request) {
    $result = UserOptionController::GetUserOption(
        $request->input('user_token', $request->bearerToken()),
        $request->input('name')
    );
    return response()->json($result);
});

Route::post('/api/user/option/set', function (Request $request) {
    $result = UserOptionController::SetUserOption(
        $request->input('user_token', $request->bearerToken()),
        $request->input('name'),
        $request->input('value', [])
    );
    return response()->json($result);
});

Route::post('/api/user/questions/get', function (Request $request) {
    $result = UserController::GetUserQuestions(
        $request->input('user_id'),
        $request->input('order'),
        $request->input('page'),
        $request->input('user_token', $request->bearerToken()),
        $request->input('per_page', 20)
    );
    return response()->json($result);
});

Route::post('/api/user/register', function (Request $request) {
    $result = UserController::AddUser(
        $request->input('email'),
        $request->input('password'),
        $request->input('email_captcha'),
        $request->input('username', ''),
        $request->input('language', '')
    );
    return response()->json($result);
});

Route::post('/api/user/reset', function (Request $request) {
    $result = UserController::Reset(
        $request->input('email'),
        $request->input('password'),
        $request->input('email_captcha')
    );
    return response()->json($result);
});

Route::post('/api/user/set/language', function (Request $request) {
    $result = UserController::SetUserLanguage(
        $request->input('user_token', $request->bearerToken()),
        $request->input('lang')
    );
    return response()->json($result);
});

Route::post('/api/user/signin/add', function (Request $request) {
    // TODO: 签到功能 - 需要创建 SignInController
    return response()->json([
        'is_add' => false,
        'message' => 'Sign-in feature not yet implemented',
    ]);
});

Route::post('/api/user/signin/get', function (Request $request) {
    // TODO: 签到功能 - 需要创建 SignInController
    return response()->json([
        'is_get' => false,
        'message' => 'Sign-in feature not yet implemented',
    ]);
});

Route::post('/api/user/upload/image', function (Request $request) {
    $result = UserController::UploadImage(
        $request->input('user_token', $request->bearerToken()),
        $request->input('type'),
        $request->input('file')
    );
    return response()->json($result);
});

Route::post('/api/users/delete', function (Request $request) {
    $result = UserController::SetUsersDisableTime(
        $request->input('user_token', $request->bearerToken()),
        $request->input('user_ids'),
        $request->input('disable_time', 0)
    );
    return response()->json($result);
});

Route::post('/api/users/get', function (Request $request) {
    $result = UserController::GetUsers(
        $request->input('order'),
        $request->input('page', 1),
        $request->input('type', 'recommended'),
        $request->input('user_token', $request->bearerToken() ?? ''),
        $request->input('per_page', 20),
        $request->input('search_keywords', ''),
        $request->input('search_field', []),
        $request->input('is_admin', false)
    );
    return response()->json($result);
});

Route::post('/api/users/user_group/set', function (Request $request) {
    $result = UserController::SetUsersUserGroup(
        $request->input('user_token', $request->bearerToken()),
        $request->input('user_group_id'),
        $request->input('old_user_group_id'),
        $request->input('user_ids')
    );
    return response()->json($result);
});

// ==================== Vote 投票 ====================

Route::post('/api/vote', function (Request $request) {
    $result = VoteController::Vote(
        $request->input('user_token', $request->bearerToken()),
        $request->input('votable_id'),
        $request->input('votable_type'),
        $request->input('type')
    );
    return response()->json($result);
});
