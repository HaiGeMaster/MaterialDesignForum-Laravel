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
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('article', function (Blueprint $table) {
            $table->unsignedInteger('article_id')->autoIncrement()->comment('文章ID');
            $table->unsignedInteger('user_id')->comment('用户ID');
            $table->string('title', 80)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('标题');
            $table->text('content_markdown')->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('原始的正文内容');
            $table->text('content_rendered')->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('过滤渲染后的正文内容');
            $table->unsignedInteger('comment_count')->default(0)->comment('评论数量');
            $table->unsignedInteger('follower_count')->default(0)->comment('关注者数量');
            $table->integer('vote_count')->default(0)->comment('投票数，赞成票-反对票，可以为负数');
            $table->integer('vote_up_count')->default(0)->comment('赞成票总数');
            $table->integer('vote_down_count')->default(0)->comment('反对票总数');
            $table->timestamp('create_time')->useCurrent()->comment('创建时间');
            $table->timestamp('update_time')->useCurrent()->comment('更新时间');
            $table->timestamp('delete_time')->nullable()->comment('删除时间');
            
            $table->primary('article_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article');
    }
};