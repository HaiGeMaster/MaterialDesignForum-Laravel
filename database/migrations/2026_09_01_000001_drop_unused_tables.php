<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * 清理未使用的旧表：聊天组（chat_group）、聊天组成员（chat_groupable）、收件箱（inbox）。
 *
 * 这三张表在代码中没有任何引用（无模型、无控制器、无路由），属于原版遗留功能，
 * 对应的建表迁移文件已一并移除。此迁移用于删除已在旧环境中创建的物理表。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('chat_groupable');
        Schema::dropIfExists('chat_group');
        Schema::dropIfExists('inbox');
    }

    public function down(): void
    {
        // 表结构定义已随旧迁移文件移除，无法在此自动重建
    }
};
