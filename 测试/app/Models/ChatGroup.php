<?php

/**
 * Author HaiGeMaster
 * @package MaterialDesignForum
 * @link https://github.com/HaiGeMaster
 * @copyright Copyright (c) 2023 HaiGeMaster
 * @start-date 2023/05/20-15:53:29
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatGroup extends Model
{
    protected $table = 'chat_group';
    protected $primaryKey = 'chat_group_id';
    public $timestamps = false;

    protected $fillable = [
        'chat_group_name',
        'chat_group_avatar',
        'chat_group_user_count',
        'chat_group_info',
        'chat_group_owner_user_id',
        'create_time',
        'update_time',
        'delete_time',
    ];

    protected $casts = [
        'chat_group_id' => 'integer',
        'chat_group_user_count' => 'integer',
        'chat_group_owner_user_id' => 'integer',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
        'delete_time' => 'datetime',
    ];

    // public function owner(): BelongsTo
    // {
    //     return $this->belongsTo(User::class, 'chat_group_owner_user_id', 'user_id');
    // }

    // public function members(): HasMany
    // {
    //     return $this->hasMany(ChatGroupable::class, 'chat_group_id', 'chat_group_id');
    // }
}
