<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbox', function (Blueprint $table) {
            $table->unsignedInteger('inbox_id')->autoIncrement()->comment('私信ID');
            $table->string('sender_id', 10)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('发送者ID：system、user_id');
            $table->string('sender_type', 30)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('发送者类型 user_to_user、user_to_chat_group、system_to_user、system_to_user_group');
            $table->unsignedInteger('receiver_id')->comment('接收者ID：user_id、chat_group_id');
            $table->text('content_markdown')->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('原始的私信内容');
            $table->text('content_rendered')->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('过滤渲染后的私信内容');
            $table->timestamp('create_time')->useCurrent()->comment('发送时间');
            $table->timestamp('read_time')->useCurrent()->comment('阅读时间');
            $table->timestamp('delete_time')->nullable()->comment('删除时间');
            
            $table->primary('inbox_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbox');
    }
};