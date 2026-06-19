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
        Schema::create('notification', function (Blueprint $table) {
            $table->unsignedInteger('notification_id')->autoIncrement()->comment('通知ID');
            $table->unsignedInteger('receiver_id')->comment('接收者ID');
            $table->integer('sender_id')->comment('发送者ID');
            $table->string('type', 40)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('消息类型：question_answered, question_commented, question_deleted, article_commented, article_deleted, answer_commented, answer_deleted, comment_replied, comment_deleted');
            $table->text('content_markdown')->nullable()->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('内容原文');
            $table->text('content_rendered')->nullable()->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('内容正文');
            $table->integer('user_id')->default(0)->comment('用户ID');
            $table->integer('topic_id')->default(0)->comment('话题ID');
            $table->integer('article_id')->default(0)->comment('文章ID');
            $table->integer('question_id')->default(0)->comment('提问ID');
            $table->integer('answer_id')->default(0)->comment('回答ID');
            $table->integer('comment_id')->default(0)->comment('评论ID');
            $table->integer('reply_id')->default(0)->comment('回复ID');
            $table->integer('reply_to_reply_id')->default(0)->comment('回复到回复的id');
            $table->timestamp('create_time')->useCurrent()->comment('发送时间');
            $table->timestamp('read_time')->useCurrent()->comment('阅读时间');
            $table->timestamp('delete_time')->nullable()->comment('删除时间');
            
            $table->primary('notification_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification');
    }
};