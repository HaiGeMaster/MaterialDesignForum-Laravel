<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `user` MODIFY `disable_until` DATETIME NULL COMMENT \'禁用到期时间\'');
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `user` MODIFY `disable_until` TIMESTAMP NULL COMMENT \'禁用到期时间\'');
        }
    }
};
