<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Token extends Model
{
    protected $table = 'token';
    protected $primaryKey = 'token';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'token',
        'user_id',
        'device',
        'create_time',
        'update_time',
        'expire_time',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'create_time' => 'timestamp',
        'update_time' => 'timestamp',
        'expire_time' => 'timestamp',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
