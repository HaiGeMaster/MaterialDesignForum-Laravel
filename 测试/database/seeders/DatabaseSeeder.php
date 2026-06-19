<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     * 默认数据已在迁移文件中插入，此处预留扩展。
     */
    public function run(): void
    {
        // 默认数据在 migrations 中通过 DB::insert() 写入
        // 如需额外测试数据，在此添加
    }
}
