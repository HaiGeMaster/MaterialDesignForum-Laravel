<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserOption extends Model
{
    protected $table = 'user_option';
    protected $primaryKey = 'user_option_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'name',
        'value',
    ];

    protected $casts = [
        'user_option_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
