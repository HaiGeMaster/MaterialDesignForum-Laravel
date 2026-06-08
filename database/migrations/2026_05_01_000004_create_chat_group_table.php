<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_group', function (Blueprint $table) {
            $table->unsignedInteger('chat_group_id')->comment('聊天组ID');
            $table->string('chat_group_name', 10)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('聊天组名称');
            $table->string('chat_group_avatar', 255)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('聊天组头像');
            $table->unsignedInteger('chat_group_user_count')->default(0)->comment('聊天组人数');
            $table->string('chat_group_info', 255)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('聊天组简介');
            $table->unsignedInteger('chat_group_owner_user_id')->default(0)->comment('聊天组创建者用户ID');
            $table->timestamp('create_time')->useCurrent()->comment('创建时间');
            $table->timestamp('update_time')->useCurrent()->comment('更新时间');
            $table->timestamp('delete_time')->nullable()->comment('删除时间');
            
            $table->primary('chat_group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_group');
    }
};