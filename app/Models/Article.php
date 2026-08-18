<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $table = 'article';
    protected $primaryKey = 'article_id';
    public $timestamps = false;
    protected $guarded = [];

    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    protected $casts = [
        'create_time' => 'datetime',
        'update_time' => 'datetime',
        'delete_time' => 'datetime',
    ];
}
