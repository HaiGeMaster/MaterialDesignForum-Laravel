<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topic', function (Blueprint $table) {
            $table->unsignedInteger('topic_id')->comment('话题ID');
            $table->integer('user_id')->comment('话题创建者用户id');
            $table->string('name', 20)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->default('')->comment('话题名称');
            $table->string('cover', 2000)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->nullable()->comment('封面图片token');
            $table->string('description', 1000)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->default('')->comment('话题描述');
            $table->unsignedInteger('article_count')->default(0)->comment('文章数量');
            $table->unsignedInteger('question_count')->default(0)->comment('问题数量');
            $table->unsignedInteger('follower_count')->default(0)->comment('关注者数量');
            $table->timestamp('create_time')->useCurrent()->comment('创建时间');
            $table->timestamp('update_time')->useCurrent()->comment('更新时间');
            $table->timestamp('delete_time')->nullable()->comment('删除时间');
            
            $table->primary('topic_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topic');
    }
};