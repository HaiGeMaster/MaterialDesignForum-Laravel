<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('option', function (Blueprint $table) {
            $table->string('name', 40)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->default('')->comment('字段名');
            $table->text('value')->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->comment('字段值');
            
            $table->primary('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('option');
    }
};