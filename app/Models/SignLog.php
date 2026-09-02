<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignLog extends Model
{
    protected $table = 'sign_log';

    protected $primaryKey = 'sign_log_id';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'sign_date' => 'date',
        'create_time' => 'datetime',
    ];

    const CREATED_AT = 'create_time';

    const UPDATED_AT = null;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
