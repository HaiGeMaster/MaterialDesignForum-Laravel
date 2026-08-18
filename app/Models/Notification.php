<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notification';
    protected $primaryKey = 'notification_id';
    public $timestamps = false;
    protected $guarded = [];

    const CREATED_AT = 'create_time';
    const UPDATED_AT = null;

    protected $casts = [
        'create_time' => 'datetime',
        'read_time' => 'datetime',
        'delete_time' => 'datetime',
    ];
}
