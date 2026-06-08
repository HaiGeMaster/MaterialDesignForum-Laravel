<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_option', function (Blueprint $table) {
            $table->unsignedInteger('user_option_id')->comment('索引');
            $table->integer('user_id')->comment('归属用户ID');
            $table->string('name', 255)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('名称');
            $table->text('value')->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('值');
            
            $table->primary('user_option_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_option');
    }
};