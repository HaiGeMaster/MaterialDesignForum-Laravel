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

class TopicAble extends Model
{
    //不需要主键
    protected $table = 'topicable';
    public $timestamps = false;

    protected $fillable = [
        'topic_id',
        'topicable_id',
        'topicable_type',
        'create_time',
    ];

    protected $casts = [
        'topic_id' => 'integer',
        'topicable_id' => 'integer',
        'create_time' => 'timestamp',
    ];
}
