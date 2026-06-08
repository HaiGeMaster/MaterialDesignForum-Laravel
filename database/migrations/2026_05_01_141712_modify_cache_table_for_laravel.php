<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cache', function (Blueprint $table) {
            // 添加 Laravel 缓存系统所需的字段
            $table->string('key')->after('name')->nullable();
            $table->integer('expiration')->unsigned()->nullable()->after('value');
        });
        
        // 将旧数据迁移到新字段
        \DB::table('cache')->update([
            'key' => \DB::raw('name'),
            'expiration' => \DB::raw('UNIX_TIMESTAMP(life_time)')
        ]);
        
        // 修改 key 字段为非空并设置为主键
        Schema::table('cache', function (Blueprint $table) {
            $table->dropPrimary('name');
            $table->string('key')->nullable(false)->change();
            $table->primary('key');
        });
    }

    public function down(): void
    {
        Schema::table('cache', function (Blueprint $table) {
            $table->dropPrimary('key');
            $table->primary('name');
            $table->dropColumn(['key', 'expiration']);
        });
    }
};
