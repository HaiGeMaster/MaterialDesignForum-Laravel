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
        Schema::create('oauth', function (Blueprint $table) {
            $table->unsignedInteger('oauth_id')->autoIncrement()->comment('索引ID');
            $table->string('oauth_name', 255)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('第三方平台标识符');
            $table->string('oauth_user_id', 255)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('第三方平台用户ID');
            $table->string('oauth_user_name', 255)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('第三方平台用户名');
            $table->string('oauth_user_email', 255)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('第三方用户邮箱');
            $table->text('oauth_source_response')->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('第三方平台源响应');
            $table->integer('user_id')->comment('对应用户id');
            
            $table->primary('oauth_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oauth');
    }
};