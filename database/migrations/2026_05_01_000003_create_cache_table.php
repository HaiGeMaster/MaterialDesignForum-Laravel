<?php

/**
 * Author HaiGeMaster
 * @package MaterialDesignForum
 * @link https://github.com/HaiGeMaster
 * @copyright Copyright (c) 2023 HaiGeMaster
 * @start-date 2023/05/20-15:53:29
 */


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cache', function (Blueprint $table) {
            $table->string('name', 180)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('缓存名称');
            $table->text('value')->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('缓存值');
            $table->timestamp('create_time')->useCurrent()->comment('创建时间');
            $table->timestamp('life_time')->nullable()->comment('有效时间');
            
            $table->primary('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cache');
    }
};
