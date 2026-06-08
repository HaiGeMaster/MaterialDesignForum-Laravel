<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vote', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->comment('用户ID');
            $table->unsignedInteger('votable_id')->comment('投票目标ID');
            $table->char('votable_type', 10)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('投票目标类型 question、answer、article、comment、reply');
            $table->char('type', 10)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('投票类型 up、down');
            $table->timestamp('create_time')->useCurrent()->comment('投票时间');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vote');
    }
};