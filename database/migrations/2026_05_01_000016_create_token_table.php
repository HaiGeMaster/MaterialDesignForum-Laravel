<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('token', function (Blueprint $table) {
            $table->string('token', 50)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->default('')->comment('token 字符串');
            $table->unsignedInteger('user_id')->comment('用户ID');
            $table->string('device', 600)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->default('')->comment('登陆设备，浏览器 UA 等信息');
            $table->timestamp('create_time')->useCurrent()->comment('创建时间');
            $table->timestamp('update_time')->useCurrent()->comment('更新时间');
            $table->timestamp('expire_time')->nullable()->comment('过期时间');
            
            $table->primary('token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token');
    }
};