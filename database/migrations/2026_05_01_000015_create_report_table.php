<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report', function (Blueprint $table) {
            $table->unsignedInteger('report_id')->autoIncrement()->comment('举报ID');
            $table->unsignedInteger('reportable_id')->comment('举报目标ID');
            $table->char('reportable_type', 10)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('举报目标类型：question、article、answer、comment、user、reply、topic');
            $table->unsignedInteger('user_id')->comment('用户ID');
            $table->string('reason', 200)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('举报原因');
            $table->integer('report_handle_state')->default(0)->comment('处理状态:未处理0、已处理删除1、已处理对象无违规2');
            $table->timestamp('create_time')->useCurrent()->comment('举报时间');
            $table->timestamp('delete_time')->nullable()->comment('删除时间');
            
            $table->primary('report_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report');
    }
};