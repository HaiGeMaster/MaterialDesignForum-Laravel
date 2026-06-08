<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inbox extends Model
{
    protected $table = 'inbox';
    protected $primaryKey = 'inbox_id';
    public $timestamps = false;

    protected $fillable = [
        'sender_id',
        'sender_type',
        'receiver_id',
        'content_markdown',
        'content_rendered',
        'create_time',
        'read_time',
        'delete_time',
    ];

    protected $casts = [
        'inbox_id' => 'integer',
        'receiver_id' => 'integer',
        'create_time' => 'timestamp',
        'read_time' => 'timestamp',
        'delete_time' => 'timestamp',
    ];
}
