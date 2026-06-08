<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_groupable', function (Blueprint $table) {
            $table->unsignedInteger('chat_groupable_id')->comment('索引ID');
            $table->unsignedInteger('user_id')->comment('用户ID');
            $table->unsignedInteger('chat_group_id')->comment('加入的聊天组ID');
            $table->timestamp('create_time')->useCurrent()->comment('创建时间');
            $table->timestamp('delete_time')->nullable()->comment('删除时间');
            
            $table->primary('chat_groupable_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_groupable');
    }
};