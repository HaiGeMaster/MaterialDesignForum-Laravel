<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow', function (Blueprint $table) {
            $table->unsignedInteger('follow_id')->comment('关注ID');
            $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
            $table->char('followable_type', 10)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('关注目标类型 user、question、article、topic 用户、提问、文章、话题');
            $table->unsignedInteger('followable_id')->comment('关注目标的ID');
            $table->timestamp('create_time')->useCurrent()->comment('关注时间');
            
            $table->primary('follow_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow');
    }
};