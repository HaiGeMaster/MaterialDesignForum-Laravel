<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

        // 插入默认设置数据
        DB::table('option')->insert([
            ['name' => 'default_language',  'value' => 'en_US'],
            ['name' => 'site_description',  'value' => 'Material Design Forum · For design, for standards, for a better interface · 为设计，为规范，为更好的界面'],
            ['name' => 'site_gongan_beian', 'value' => ''],
            ['name' => 'site_icp_beian',    'value' => ''],
            ['name' => 'site_keywords',     'value' => 'Material Design Forum,Vuetify,MDUI,MDForum,Forum,论坛,轻量论坛,简洁论坛,材质设计论坛,轻论坛'],
            ['name' => 'site_name',         'value' => 'Material Design Forum'],
            ['name' => 'site_static_url',   'value' => ''],
            ['name' => 'theme',             'value' => 'MaterialDesignForum-Vuetify4'],
            ['name' => 'theme_carousel_param', 'value' => ''],
            ['name' => 'theme_typed_param', 'value' => '{"header":"Message.Components.TextPlay.With","body":["Message.Components.TextPlay.MaterialDesign","Message.Components.TextPlay.VueAsTheCore","Message.Components.TextPlay.MoreElegant","Message.Components.TextPlay.UnlimitedDistance","Message.Components.TextPlay.CrossPlatform","Message.Components.TextPlay.DynamicResponsive"],"footer_header":"Message.Components.TextPlay.TheWay","footer_tail":"Message.Components.TextPlay.EnjoyCommunication"}'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('option');
    }
};