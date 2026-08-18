<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * token 登录令牌模型
 *
 * 对应数据表：token
 * 主键为字符串 token（非自增）
 */
class Token extends Model
{
    protected $table = 'token';

    protected $primaryKey = 'token';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    const CREATED_AT = 'create_time';

    const UPDATED_AT = 'update_time';

    protected $guarded = [];

    protected $casts = [
        'create_time' => 'datetime',
        'update_time' => 'datetime',
        'expire_time' => 'datetime',
    ];

    /**
     * 令牌所属的用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
