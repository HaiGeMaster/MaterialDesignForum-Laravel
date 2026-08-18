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
}
