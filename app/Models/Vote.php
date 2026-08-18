<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
    protected $table = 'vote';
    public $timestamps = false;
    protected $guarded = [];
    protected $primaryKey = null;

    protected $casts = [
        'create_time' => 'datetime',
    ];
}
