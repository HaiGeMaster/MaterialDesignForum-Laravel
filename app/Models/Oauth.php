<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Oauth extends Model
{
    protected $table = 'oauth';
    protected $primaryKey = 'oauth_id';
    public $timestamps = false;

    protected $fillable = [
        'oauth_name',
        'oauth_user_id',
        'oauth_user_name',
        'oauth_user_email',
        'oauth_source_response',
        'user_id',
    ];

    protected $casts = [
        'oauth_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
