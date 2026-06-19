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
        Schema::create('reply', function (Blueprint $table) {
            $table->unsignedInteger('reply_id')->autoIncrement()->comment('回复ID');
            $table->unsignedInteger('replyable_id')->comment('回复目标的ID:comment_id、reply_id');
            $table->char('replyable_type', 10)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('回复目标类型：comment、reply、评论、回复');
            $table->integer('replyable_comment_id')->comment('回复目标的父项：评论ID');
            $table->integer('replyable_user_id')->default(0)->comment('回复目标用户id');
            $table->unsignedInteger('user_id')->comment('用户ID');
            $table->text('content')->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('原始正文内容');
            $table->integer('reply_count')->default(0)->comment('回复数量');
            $table->integer('vote_count')->default(0)->comment('投票数，赞成票-反对票，可以为负数');
            $table->integer('vote_up_count')->default(0)->comment('赞成票总数');
            $table->integer('vote_down_count')->default(0)->comment('反对票总数');
            $table->timestamp('create_time')->useCurrent()->comment('创建时间');
            $table->timestamp('update_time')->useCurrent()->comment('更新时间');
            $table->timestamp('delete_time')->nullable()->comment('删除时间');
            
            $table->primary('reply_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reply');
    }
};