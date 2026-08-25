<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->increments('media_id')->comment('媒体ID');
            $table->integer('user_id')->comment('上传用户ID');
            $table->char('type', 10)->comment('媒体类型：image / video');
            $table->string('filename', 255)->comment('原始文件名');
            $table->string('path', 500)->comment('存储相对路径');
            $table->string('url', 500)->comment('访问URL');
            $table->string('mime', 100)->nullable()->comment('MIME 类型');
            $table->unsignedBigInteger('size')->default(0)->comment('文件大小(字节)');
            $table->unsignedInteger('width')->default(0)->comment('原始宽度');
            $table->unsignedInteger('height')->default(0)->comment('原始高度');
            $table->char('item_type', 20)->nullable()->comment('关联类型：question、answer、article、user_cover、user_avatar、topic_cover');
            $table->integer('item_id')->default(0)->comment('关联ID：问题ID/回答ID/文章ID/用户ID');
            $table->timestamp('create_time')->useCurrent()->comment('上传时间');

            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'create_time']);
            $table->index(['item_type', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
