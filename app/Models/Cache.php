<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cache extends Model
{
    protected $table = 'cache';
    protected $primaryKey = 'name';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'value',
        'create_time',
        'life_time',
    ];

    protected $casts = [
        'create_time' => 'timestamp',
        'life_time' => 'timestamp',
    ];
}
