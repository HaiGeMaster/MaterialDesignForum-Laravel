<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->char('item_type', 20)->nullable()->after('height')->comment('关联类型：question、answer、article、user_cover、user_avatar');
            $table->integer('item_id')->default(0)->after('item_type')->comment('关联ID：问题ID/回答ID/文章ID/用户ID');
            $table->index(['item_type', 'item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropIndex(['item_type', 'item_id']);
            $table->dropColumn(['item_type', 'item_id']);
        });
    }
};
