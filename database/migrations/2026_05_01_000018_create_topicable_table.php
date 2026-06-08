<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topicable', function (Blueprint $table) {
            $table->unsignedInteger('topic_id')->comment('话题ID');
            $table->unsignedInteger('topicable_id')->comment('话题关系对应的ID');
            $table->char('topicable_type', 10)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('话题关系对应的类型 question、article');
            $table->timestamp('create_time')->useCurrent()->comment('创建时间');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topicable');
    }
};