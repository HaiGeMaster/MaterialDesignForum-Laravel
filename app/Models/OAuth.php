<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 第三方 OAuth 绑定模型
 *
 * 对应数据表：oauth
 * 用于记录论坛用户与第三方 OAuth 账号（GitHub / Google / Microsoft）的绑定关系。
 */
class OAuth extends Model
{
    protected $table = 'oauth';

    protected $primaryKey = 'oauth_id';

    public $timestamps = false;

    protected $guarded = [];

    protected $hidden = [
        'oauth_source_response',
    ];

    protected $casts = [
        'oauth_source_response' => 'array',
    ];

    /**
     * 该 OAuth 绑定所属的论坛用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
