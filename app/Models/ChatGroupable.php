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

class ChatGroupable extends Model
{
    protected $table = 'chat_groupable';
    protected $primaryKey = 'chat_groupable_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'chat_group_id',
        'create_time',
        'delete_time',
    ];

    protected $casts = [
        'chat_groupable_id' => 'integer',
        'user_id' => 'integer',
        'chat_group_id' => 'integer',
        'create_time' => 'timestamp',
        'delete_time' => 'timestamp',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function chatGroup(): BelongsTo
    {
        return $this->belongsTo(ChatGroup::class, 'chat_group_id', 'chat_group_id');
    }
}
