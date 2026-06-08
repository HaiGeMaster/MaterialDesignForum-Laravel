<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('image', function (Blueprint $table) {
            $table->string('key', 50)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('图片键名');
            $table->string('filename', 255)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('原始文件名');
            $table->unsignedInteger('width')->default(0)->comment('原始图片宽度');
            $table->unsignedInteger('height')->default(0)->comment('原始图片高度');
            $table->timestamp('create_time')->useCurrent()->comment('上传时间');
            $table->char('item_type', 10)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->nullable()->comment('关联类型：question、answer、article');
            $table->integer('item_id')->default(0)->comment('关联ID');
            $table->integer('user_id')->comment('用户ID');
            
            $table->primary('key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('image');
    }
};