<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserOption extends Model
{
    protected $table = 'user_option';
    protected $primaryKey = 'user_option_id';
    public $timestamps = false;
    protected $guarded = [];
}
