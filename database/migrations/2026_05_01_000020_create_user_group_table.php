<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_group', function (Blueprint $table) {
            $table->unsignedInteger('user_group_id')->comment('用户组ID');
            $table->string('user_group_name', 50)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->default('User')->comment('用户组名称');
            $table->string('user_group_description', 100)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->default('User')->comment('用户组描述');
            $table->string('user_group_icon', 50)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->default('mdi-account')->comment('用户组图标');
            $table->tinyInteger('user_group_icon_show')->default(0)->comment('显示用户组标识');
            $table->string('user_group_color', 20)->charset('utf8mb4')->collate('utf8mb4_unicode_ci')->default('')->comment('用户组颜色');
            $table->unsignedInteger('user_group_user_count')->default(0)->comment('用户组人数');
            $table->timestamp('create_time')->useCurrent()->comment('创建时间');
            $table->timestamp('update_time')->useCurrent()->comment('更新时间');
            $table->timestamp('delete_time')->nullable()->comment('删除时间');
            $table->tinyInteger('is_admin')->default(0)->comment('是否是管理员');
            $table->tinyInteger('ability_normal_login')->default(0)->comment('前台正常登录');
            $table->tinyInteger('ability_admin_login')->default(0)->comment('是否可后台登录');
            $table->tinyInteger('ability_admin_manage_user_group')->default(0)->comment('是否可后台管理用户组（真则显示）');
            $table->tinyInteger('ability_admin_manage_user')->default(0)->comment('是否可后台管理用户（真则显示）');
            $table->tinyInteger('ability_admin_manage_topic')->default(0)->comment('是否可后台管理话题（真则显示）');
            $table->tinyInteger('ability_admin_manage_question')->default(0)->comment('是否可后台管理问题（真则显示）');
            $table->tinyInteger('ability_admin_manage_article')->default(0)->comment('是否可后台管理文章（真则显示）');
            $table->tinyInteger('ability_admin_manage_comment')->default(0)->comment('是否可后台管理评论（真则显示）');
            $table->tinyInteger('ability_admin_manage_answer')->default(0)->comment('是否可后台管理回答（真则显示）');
            $table->tinyInteger('ability_admin_manage_reply')->default(0)->comment('是否可后台管理回复（真则显示）');
            $table->tinyInteger('ability_admin_manage_report')->default(0)->comment('是否可后台管理举报（真则显示）');
            $table->tinyInteger('ability_admin_manage_option')->default(0)->comment('是否可后台管理设置（真则显示）');
            $table->tinyInteger('ability_create_article')->default(0)->comment('是否可发表文章');
            $table->tinyInteger('ability_create_question')->default(0)->comment('是否可发表问题');
            $table->tinyInteger('ability_create_answer')->default(0)->comment('是否可发表回答');
            $table->tinyInteger('ability_create_comment')->default(0)->comment('是否可发表评论');
            $table->tinyInteger('ability_create_reply')->default(0)->comment('是否可发表回复');
            $table->tinyInteger('ability_create_topic')->default(0)->comment('是否可创建话题');
            $table->tinyInteger('ability_edit_own_article')->default(0)->comment('是否可编辑自己的文章');
            $table->tinyInteger('ability_edit_own_question')->default(0)->comment('是否可编辑自己的问题');
            $table->tinyInteger('ability_edit_own_answer')->default(0)->comment('是否可编辑自己的回答');
            $table->tinyInteger('ability_edit_own_comment')->default(0)->comment('是否可编辑自己的评论');
            $table->tinyInteger('ability_edit_own_reply')->default(0)->comment('是否可编辑自己的回复');
            $table->tinyInteger('ability_edit_own_topic')->default(0)->comment('是否可编辑自己的话题');
            $table->tinyInteger('ability_delete_own_article')->default(0)->comment('是否可删除自己的文章');
            $table->tinyInteger('ability_delete_own_question')->default(0)->comment('是否可删除自己的问题');
            $table->tinyInteger('ability_delete_own_answer')->default(0)->comment('是否可删除自己的回答');
            $table->tinyInteger('ability_delete_own_comment')->default(0)->comment('是否可删除自己的评论');
            $table->tinyInteger('ability_delete_own_reply')->default(0)->comment('是否可删除自己的回复');
            $table->tinyInteger('ability_delete_own_topic')->default(0)->comment('是否可删除自己的话题');
            $table->unsignedInteger('time_before_edit_article')->default(5)->comment('在多长时间前可编辑自己的文章（单位：分钟，0无限期）');
            $table->unsignedInteger('time_before_edit_question')->default(5)->comment('在多长时间前可编辑自己的问题（单位：分钟，0无限期）');
            $table->unsignedInteger('time_before_edit_answer')->default(5)->comment('在多长时间前可编辑自己的回答（单位：分钟，0无限期）');
            $table->unsignedInteger('time_before_edit_comment')->default(5)->comment('在多长时间前可编辑自己的评论（单位：分钟，0无限期）');
            $table->unsignedInteger('time_before_edit_reply')->default(5)->comment('在多长时间前可编辑自己的回复（单位：分钟，0无限期）');
            $table->unsignedInteger('time_before_edit_topic')->default(5)->comment('在多长时间前可编辑自己的话题（单位：分钟，0无限期）');
            $table->unsignedInteger('time_before_delete_article')->default(5)->comment('在多长时间前可删除自己的文章（单位：分钟，0无限期）');
            $table->unsignedInteger('time_before_delete_question')->default(5)->comment('在多长时间前可删除自己的问题（单位：分钟，0无限期）');
            $table->unsignedInteger('time_before_delete_answer')->default(5)->comment('在多长时间前可删除自己的回答（单位：分钟，0无限期）');
            $table->unsignedInteger('time_before_delete_comment')->default(5)->comment('在多长时间前可删除自己的评论（单位：分钟，0无限期）');
            $table->unsignedInteger('time_before_delete_reply')->default(5)->comment('在多长时间前可删除自己的回复（单位：分钟，0无限期）');
            $table->unsignedInteger('time_before_delete_topic')->default(5)->comment('在多长时间前可删除自己的话题（单位：分钟，0无限期）');
            $table->tinyInteger('ability_edit_article_only_no_comment')->default(1)->comment('仅限文章没有评论的情况下才能编辑');
            $table->tinyInteger('ability_edit_question_only_no_answer')->default(1)->comment('仅限问题没有回答的情况下才能编辑');
            $table->tinyInteger('ability_edit_answer_only_no_comment')->default(1)->comment('仅限回答没有评论的情况下才能编辑');
            $table->tinyInteger('ability_edit_question_only_no_comment')->default(1)->comment('仅限问题没有评论的情况下才能编辑');
            $table->tinyInteger('ability_edit_comment_only_no_reply')->default(1)->comment('仅限评论没有回复的情况下才能编辑');
            $table->tinyInteger('ability_edit_reply_only_no_reply')->default(1)->comment('仅限回复没有回复的情况下才能编辑');
            $table->tinyInteger('ability_edit_topic_only_no_article_or_question')->default(1)->comment('仅限话题没有文章或问题的情况下才能编辑');
            $table->tinyInteger('ability_delete_article_only_no_comment')->default(1)->comment('仅限文章没有评论的情况下才能删除');
            $table->tinyInteger('ability_delete_question_only_no_answer')->default(1)->comment('仅限问题没有回答的情况下才能删除');
            $table->tinyInteger('ability_delete_answer_only_no_comment')->default(1)->comment('仅限回答没有评论的情况下才能删除');
            $table->tinyInteger('ability_delete_question_only_no_comment')->default(1)->comment('仅限问题没有评论的情况下才能删除');
            $table->tinyInteger('ability_delete_comment_only_no_reply')->default(1)->comment('仅限评论没有回复的情况下才能删除');
            $table->tinyInteger('ability_delete_reply_only_no_reply')->default(1)->comment('仅限回复没有回复的情况下才能删除');
            $table->tinyInteger('ability_delete_topic_only_no_article_or_question')->default(1)->comment('仅限话题没有文章或问题的情况下才能删除');
            $table->tinyInteger('ability_edit_own_info')->default(1)->comment('是否可编辑自己的个人信息');
            $table->tinyInteger('ability_vote')->default(1)->comment('能否投票');
            
            $table->primary('user_group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_group');
    }
};