<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_sign_summary', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->primary()->comment('用户ID');
            $table->unsignedInteger('total_sign_days')->default(0)->comment('累计签到天数');
            $table->unsignedInteger('continuous_sign_days')->default(0)->comment('连续签到天数');
            $table->date('last_sign_date')->nullable()->comment('最后一次签到日期');
            $table->unsignedInteger('last_reward_points')->default(0)->comment('最后一次签到奖励');
            $table->timestamp('create_time')->useCurrent()->comment('创建时间');
            $table->timestamp('update_time')->useCurrent()->nullable()->comment('更新时间');
        });

        Schema::create('sign_log', function (Blueprint $table) {
            $table->increments('sign_log_id');
            $table->unsignedInteger('user_id')->comment('签到用户ID');
            $table->date('sign_date')->comment('签到日期');
            $table->unsignedInteger('reward_points')->default(0)->comment('获得积分');
            $table->string('ip_address', 45)->nullable()->comment('签到IP');
            $table->timestamp('create_time')->useCurrent()->comment('签到时间');

            $table->unique(['user_id', 'sign_date']);
            $table->index('sign_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sign_log');
        Schema::dropIfExists('user_sign_summary');
    }
};
