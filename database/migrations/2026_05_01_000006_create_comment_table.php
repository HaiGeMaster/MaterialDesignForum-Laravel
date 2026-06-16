<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comment', function (Blueprint $table) {
            $table->unsignedInteger('comment_id')->autoIncrement()->comment('评论ID');
            $table->unsignedInteger('commentable_id')->comment('评论目标的ID');
            $table->char('commentable_type', 10)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('评论目标类型：article、question、answer、文章、提问、回答');
            $table->unsignedInteger('user_id')->comment('用户ID');
            $table->text('content')->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('原始正文内容');
            $table->integer('reply_count')->default(0)->comment('回复数量');
            $table->integer('vote_count')->default(0)->comment('投票数，赞成票-反对票，可以为负数');
            $table->integer('vote_up_count')->default(0)->comment('赞成票总数');
            $table->integer('vote_down_count')->default(0)->comment('反对票总数');
            $table->timestamp('create_time')->useCurrent()->comment('创建时间');
            $table->timestamp('update_time')->useCurrent()->comment('更新时间');
            $table->timestamp('delete_time')->nullable()->comment('删除时间');
            
            $table->primary('comment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment');
    }
};