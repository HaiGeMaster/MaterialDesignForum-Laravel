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
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Vote extends Model
{
    //不需要主键
    protected $table = 'vote';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'votable_id',
        'votable_type',
        'type',
        'create_time',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'votable_id' => 'integer',
        'create_time' => 'datetime',
    ];

    // public function user(): BelongsTo
    // {
    //     return $this->belongsTo(User::class, 'user_id', 'user_id');
    // }

    // public function votable(): MorphTo
    // {
    //     return $this->morphTo();
    // }
}
