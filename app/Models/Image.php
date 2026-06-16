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

class Image extends Model
{
    protected $table = 'image';
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'key',
        'filename',
        'width',
        'height',
        'create_time',
        'item_type',
        'item_id',
        'user_id',
    ];

    protected $casts = [
        'width' => 'integer',
        'height' => 'integer',
        'create_time' => 'datetime',
        'item_id' => 'integer',
        'user_id' => 'integer',
    ];
}
