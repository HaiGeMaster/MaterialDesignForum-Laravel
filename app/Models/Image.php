<?php

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
        'create_time' => 'timestamp',
        'item_id' => 'integer',
        'user_id' => 'integer',
    ];
}
