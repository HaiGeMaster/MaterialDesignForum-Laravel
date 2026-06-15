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

class Follow extends Model
{
    protected $table = 'follow';
    protected $primaryKey = 'follow_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'followable_type',
        'followable_id',
        'create_time',
    ];

    protected $casts = [
        'follow_id' => 'integer',
        'user_id' => 'integer',
        'followable_id' => 'integer',
        'create_time' => 'timestamp',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function followable(): MorphTo
    {
        return $this->morphTo();
    }
}
