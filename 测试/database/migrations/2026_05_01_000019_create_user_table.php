<?php

/**
 * Author HaiGeMaster
 * @package MaterialDesignForum
 * @link https://github.com/HaiGeMaster
 * @copyright Copyright (c) 2023 HaiGeMaster
 * @start-date 2023/05/20-15:53:29
 */


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->autoIncrement()->comment('用户ID');
            $table->unsignedInteger('user_group_id')->default(2)->comment('用户组ID');
            $table->string('username', 30)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('用户名');
            $table->string('email', 320)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('邮箱');
            $table->string('avatar', 2000)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->nullable()->comment('头像token');
            $table->string('cover', 2000)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->nullable()->comment('封面图片token');
            $table->string('password', 255)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('密码');
            $table->string('create_ip', 80)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->nullable()->comment('注册IP');
            $table->string('create_location', 500)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->nullable()->comment('注册地址');
            $table->timestamp('last_login_time')->useCurrent()->comment('最后登录时间');
            $table->string('last_login_ip', 80)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->nullable()->comment('最后登陆IP');
            $table->string('last_login_location', 500)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->nullable()->comment('最后登录地址');
            $table->unsignedInteger('follower_count')->default(0)->comment('关注我的人数');
            $table->unsignedInteger('followee_count')->default(0)->comment('我关注的人数');
            $table->unsignedInteger('following_topic_count')->default(0)->comment('我关注的话题数量');
            $table->unsignedInteger('following_article_count')->default(0)->comment('我关注的文章数量');
            $table->unsignedInteger('following_question_count')->default(0)->comment('我关注的问题数量');
            $table->unsignedInteger('topic_count')->default(0)->comment('我发表的话题数量');
            $table->unsignedInteger('article_count')->default(0)->comment('我发表的文章数量');
            $table->unsignedInteger('question_count')->default(0)->comment('我发表的问题数量');
            $table->unsignedInteger('answer_count')->default(0)->comment('我发表的回答数量');
            $table->unsignedInteger('comment_count')->default(0)->comment('我发表的评论数量');
            $table->unsignedInteger('reply_count')->default(0)->comment('我发表的回复数量');
            $table->unsignedInteger('notification_unread')->default(0)->comment('未读通知数量');
            $table->unsignedInteger('inbox_system')->default(0)->comment('（暂停用）未读系统信息数量');
            $table->unsignedInteger('inbox_user_group')->default(0)->comment('（暂停用）未读用户组信息数量');
            $table->unsignedInteger('inbox_private_message')->default(0)->comment('（暂停用）未读私信数');
            $table->string('headline', 40)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->nullable()->comment('一句话介绍');
            $table->string('bio', 160)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->nullable()->comment('个人简介');
            $table->string('blog', 255)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->nullable()->comment('个人主页');
            $table->string('company', 255)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->nullable()->comment('公司名称');
            $table->string('location', 255)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->nullable()->comment('地址');
            $table->string('language', 30)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->nullable()->comment('使用的语言');
            $table->timestamp('create_time')->useCurrent()->comment('注册时间');
            $table->timestamp('update_time')->useCurrent()->comment('更新时间');
            $table->timestamp('disable_time')->nullable()->comment('禁用时间');
            
            $table->primary('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user');
    }
};