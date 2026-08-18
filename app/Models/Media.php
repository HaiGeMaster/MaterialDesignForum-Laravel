<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 媒体模型
 *
 * 对应数据表：media
 * 记录用户通过富文本编辑器上传的图片 / 视频。
 */
class Media extends Model
{
    protected $table = 'media';

    protected $primaryKey = 'media_id';

    public $timestamps = false;

    protected $guarded = [];

    const CREATED_AT = 'create_time';

    protected $casts = [
        'create_time' => 'datetime',
    ];

    /**
     * 上传该媒体的用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
