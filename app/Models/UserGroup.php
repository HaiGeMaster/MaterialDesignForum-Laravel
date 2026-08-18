<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 用户组模型
 *
 * 对应表：user_group（迁移：2026_05_01_000020_create_user_group_table）
 * 内置两组默认数据：1=管理员（全权限）、2=普通用户
 */
class UserGroup extends Model
{
    protected $table = 'user_group';
    protected $primaryKey = 'user_group_id';
    public $timestamps = false;
    protected $guarded = [];

    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    protected $casts = [
        // ===== 基础信息 =====
        'user_group_icon_show'  => 'boolean',  // 是否显示用户组标识
        'user_group_user_count' => 'integer',  // 用户组人数
        'is_admin'              => 'boolean',  // 是否是管理员

        // ===== 时间戳 =====
        'create_time' => 'datetime',
        'update_time' => 'datetime',
        'delete_time' => 'datetime',

        // ===== 登录与后台管理权限 =====
        'ability_normal_login'            => 'boolean',  // 前台正常登录
        'ability_admin_login'             => 'boolean',  // 是否可后台登录
        'ability_admin_manage_user_group' => 'boolean',  // 后台管理用户组
        'ability_admin_manage_user'       => 'boolean',  // 后台管理用户
        'ability_admin_manage_topic'      => 'boolean',  // 后台管理话题
        'ability_admin_manage_question'   => 'boolean',  // 后台管理问题
        'ability_admin_manage_article'    => 'boolean',  // 后台管理文章
        'ability_admin_manage_comment'    => 'boolean',  // 后台管理评论
        'ability_admin_manage_answer'     => 'boolean',  // 后台管理回答
        'ability_admin_manage_reply'      => 'boolean',  // 后台管理回复
        'ability_admin_manage_report'     => 'boolean',  // 后台管理举报
        'ability_admin_manage_option'     => 'boolean',  // 后台管理设置

        // ===== 内容创作权限 =====
        'ability_create_article'  => 'boolean',  // 发表文章
        'ability_create_question' => 'boolean',  // 发表问题
        'ability_create_answer'   => 'boolean',  // 发表回答
        'ability_create_comment'  => 'boolean',  // 发表评论
        'ability_create_reply'    => 'boolean',  // 发表回复
        'ability_create_topic'    => 'boolean',  // 创建话题

        // ===== 编辑自己的内容 =====
        'ability_edit_own_article'  => 'boolean',
        'ability_edit_own_question' => 'boolean',
        'ability_edit_own_answer'   => 'boolean',
        'ability_edit_own_comment'  => 'boolean',
        'ability_edit_own_reply'    => 'boolean',
        'ability_edit_own_topic'    => 'boolean',

        // ===== 删除自己的内容 =====
        'ability_delete_own_article'  => 'boolean',
        'ability_delete_own_question' => 'boolean',
        'ability_delete_own_answer'   => 'boolean',
        'ability_delete_own_comment'  => 'boolean',
        'ability_delete_own_reply'    => 'boolean',
        'ability_delete_own_topic'    => 'boolean',

        // ===== 编辑时限（单位：分钟，0=无限期）=====
        'time_before_edit_article'  => 'integer',
        'time_before_edit_question' => 'integer',
        'time_before_edit_answer'   => 'integer',
        'time_before_edit_comment'  => 'integer',
        'time_before_edit_reply'    => 'integer',
        'time_before_edit_topic'    => 'integer',

        // ===== 删除时限（单位：分钟，0=无限期）=====
        'time_before_delete_article'  => 'integer',
        'time_before_delete_question' => 'integer',
        'time_before_delete_answer'   => 'integer',
        'time_before_delete_comment'  => 'integer',
        'time_before_delete_reply'    => 'integer',
        'time_before_delete_topic'    => 'integer',

        // ===== 编辑前置条件（无子内容才可编辑）=====
        'ability_edit_article_only_no_comment'    => 'boolean',
        'ability_edit_question_only_no_answer'    => 'boolean',
        'ability_edit_answer_only_no_comment'     => 'boolean',
        'ability_edit_question_only_no_comment'   => 'boolean',
        'ability_edit_comment_only_no_reply'      => 'boolean',
        'ability_edit_reply_only_no_reply'        => 'boolean',
        'ability_edit_topic_only_no_article_or_question' => 'boolean',

        // ===== 删除前置条件（无子内容才可删除）=====
        'ability_delete_article_only_no_comment'  => 'boolean',
        'ability_delete_question_only_no_answer'  => 'boolean',
        'ability_delete_answer_only_no_comment'   => 'boolean',
        'ability_delete_question_only_no_comment' => 'boolean',
        'ability_delete_comment_only_no_reply'    => 'boolean',
        'ability_delete_reply_only_no_reply'      => 'boolean',
        'ability_delete_topic_only_no_article_or_question' => 'boolean',

        // ===== 其他权限 =====
        'ability_edit_own_info' => 'boolean',  // 编辑自己的个人信息
        'ability_vote'          => 'boolean',  // 能否投票
    ];

    /**
     * 该用户组下的所有用户
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'user_group_id', 'user_group_id');
    }
}
