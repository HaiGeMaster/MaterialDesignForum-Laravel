<?php

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
