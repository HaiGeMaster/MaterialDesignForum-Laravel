<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * 用户模型
 *
 * 对应数据表：user
 * 继承 Authenticatable 以支持 Laravel 认证体系（auth()->user() / setUser()）
 */
class User extends Authenticatable
{
    protected $table = 'user';

    protected $primaryKey = 'user_id';

    public $timestamps = false;

    protected $guarded = [];

    protected $hidden = [
        'email',
        'password',
    ];

    const CREATED_AT = 'create_time';

    const UPDATED_AT = 'update_time';

    protected $casts = [
        'create_time' => 'datetime',
        'update_time' => 'datetime',
        'last_login_time' => 'datetime',
        'disable_time' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (self $user): void {
            self::syncUserGroupCount($user->user_group_id);
        });

        static::updated(function (self $user): void {
            if (! $user->wasChanged('user_group_id')) {
                return;
            }

            $oldGroupId = (int) $user->getOriginal('user_group_id');
            $newGroupId = (int) $user->user_group_id;

            if ($oldGroupId > 0) {
                self::syncUserGroupCount($oldGroupId);
            }

            if ($newGroupId > 0) {
                self::syncUserGroupCount($newGroupId);
            }
        });
    }

    public static function syncUserGroupCount(?int $userGroupId): void
    {
        if ($userGroupId === null || $userGroupId <= 0) {
            return;
        }

        $count = self::query()
            ->where('user_group_id', $userGroupId)
            ->whereNull('disable_time')
            ->count();

        UserGroup::query()
            ->where('user_group_id', $userGroupId)
            ->update(['user_group_user_count' => $count, 'update_time' => now()]);
    }

    /**
     * 序列化时条件性地暴露 email 字段。
     *
     * email 默认在 $hidden 中对所有人隐藏，仅当当前认证用户
     * 为用户本人或属于管理员用户组时才在 toArray() 中返回。
     */
    public function toArray()
    {
        $array = parent::toArray();

        if ($this->shouldRevealEmail()) {
            $array['email'] = $this->email;
        }

        return $array;
    }

    /**
     * 判断当前认证用户是否可以查看本用户的邮箱。
     *
     * 仅当访问者为用户本人或管理员用户组时返回 true。
     */
    protected function shouldRevealEmail(): bool
    {
        $currentUser = auth()->user();

        if (! $currentUser) {
            return false;
        }

        // 用户本人
        if ($currentUser->user_id === $this->user_id) {
            return true;
        }

        // 管理员用户组
        if (! $currentUser->relationLoaded('userGroup')) {
            $currentUser->load('userGroup');
        }

        return (bool) ($currentUser->userGroup->is_admin ?? false);
    }

    /**
     * 用户所属的用户组
     */
    public function userGroup(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class, 'user_group_id', 'user_group_id');
    }

    /**
     * 用户的登录令牌
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(Token::class, 'user_id', 'user_id');
    }

    /**
     * 该用户所在用户组是否允许前台登录。
     *
     * 取自 user_group.ability_normal_login 字段：
     * - true  → 允许登录
     * - false → 不允许登录（即使密码/token 正确也会被拒绝）
     *
     * 内部会按需加载 userGroup 关联，避免外部漏 load 导致误判。
     */
    public function canLoginNormally(): bool
    {
        if (! $this->relationLoaded('userGroup')) {
            $this->load('userGroup');
        }

        return (bool) ($this->userGroup->ability_normal_login ?? false);
    }

    /**
     * 通用用户组权限检查（用于 ability_create_* / ability_edit_own_* 等字段）。
     *
     * 注意：本方法不与 Laravel Gate 系统互通，仅为 user_group 表的布尔字段提供便捷访问，
     * 因此不占用 Authenticatable 自带的 can() 方法名。
     *
     * @param  string  $ability  user_group 表字段名，如 'ability_create_article'
     */
    public function hasAbility(string $ability): bool
    {
        if (! $this->relationLoaded('userGroup')) {
            $this->load('userGroup');
        }

        return (bool) ($this->userGroup->{$ability} ?? false);
    }
}
