<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 修正：read_time 原为 NOT NULL DEFAULT CURRENT_TIMESTAMP，
        // 导致新通知插入时被自动填充阅读时间（一创建即"已读"），
        // 未读判断 whereNull('read_time') 永远失效。
        DB::statement("ALTER TABLE notification MODIFY read_time timestamp NULL DEFAULT NULL COMMENT '阅读时间（NULL 表示未读）'");

        // 历史通知的 read_time 全部被默认值污染，且当时系统并无已读逻辑，
        // 统一重置为未读，让徽章数量恢复正常。
        DB::table('notification')->whereNull('delete_time')->update(['read_time' => null]);
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE notification MODIFY read_time timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '阅读时间'");
    }
};
