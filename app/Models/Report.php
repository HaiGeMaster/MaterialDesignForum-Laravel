<?php

/**
 * Author HaiGeMaster
 * @package MaterialDesignForum
 * @link https://github.com/HaiGeMaster
 * @copyright Copyright (c) 2023 HaiGeMaster
 * @start-date 2023/05/20-15:53:29
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Report extends Model
{
    protected $table = 'report';
    protected $primaryKey = 'report_id';
    public $timestamps = false;

    protected $fillable = [
        'reportable_id',
        'reportable_type',
        'user_id',
        'reason',
        'report_handle_state',
        'create_time',
        'delete_time',
    ];

    protected $casts = [
        'report_id' => 'integer',
        'reportable_id' => 'integer',
        'user_id' => 'integer',
        'report_handle_state' => 'integer',
        'create_time' => 'timestamp',
        'delete_time' => 'timestamp',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    
  // 搜索字段
  public static array $search_field = ['reason'];
}
