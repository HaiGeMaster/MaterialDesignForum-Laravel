<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * 举报模型
 *
 * 对应表：report（迁移：2026_05_01_000015_create_report_table）
 * 处理状态 report_handle_state：0=未处理、1=已处理（对象已删除）、2=已处理（对象无违规）
 */
class Report extends Model
{
    protected $table = 'report';
    protected $primaryKey = 'report_id';
    public $timestamps = false;
    protected $guarded = [];

    const CREATED_AT = 'create_time';
    const UPDATED_AT = null;

    protected $casts = [
        'report_handle_state' => 'integer',  // 处理状态：0未处理、1已处理删除、2无违规
        'create_time'         => 'datetime',
        'delete_time'         => 'datetime',
    ];

    /**
     * 举报目标（多态关联）
     *
     * 可举报的类型：question、article、answer、comment、user、reply、topic
     */
    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 举报人
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
