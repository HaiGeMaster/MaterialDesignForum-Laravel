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

class Reply extends Model
{
    protected $table = 'reply';
    protected $primaryKey = 'reply_id';
    public $timestamps = false;

    protected $fillable = [
        'replyable_id',
        'replyable_type',
        'replyable_comment_id',
        'replyable_user_id',
        'user_id',
        'content',
        'reply_count',
        'vote_count',
        'vote_up_count',
        'vote_down_count',
        'create_time',
        'update_time',
        'delete_time',
    ];

    protected $casts = [
        'reply_id' => 'integer',
        'replyable_id' => 'integer',
        'replyable_comment_id' => 'integer',
        'replyable_user_id' => 'integer',
        'user_id' => 'integer',
        'reply_count' => 'integer',
        'vote_count' => 'integer',
        'vote_up_count' => 'integer',
        'vote_down_count' => 'integer',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
        'delete_time' => 'datetime',
    ];

    // public function user(): BelongsTo
    // {
    //     return $this->belongsTo(User::class, 'user_id', 'user_id');
    // }

    // public function replyable(): MorphTo
    // {
    //     return $this->morphTo();
    // }

    
  // 搜索字段
  public static array $search_field = ['content'];
  /**
   * 添加回复的回复数量
   * @param int $reply_id 回复ID
   * @param int $count 回复数量
   * @return bool
   */
  public static function AddReplyCount($reply_id, $count = 1): bool
  {
    $reply = self::find($reply_id);
    $reply->reply_count += $count;
    return $reply->save();
  }
  /**
   * 添加回复的赞成票数量
   * @param int $reply_id 回复ID
   * @param int $count 回复数量
   * @return bool
   */
  public static function AddVoteUpCount($reply_id, $count = 1): bool
  {
    $reply = self::find($reply_id);
    $reply->vote_up_count += $count;
    $reply->vote_count += $count;
    return $reply->save();
  }
  /**
   * 添加回复的反对票数量
   * @param int $reply_id 回复ID
   * @param int $count 回复数量
   * @return bool
   */
  public static function AddVoteDownCount($reply_id, $count = 1): bool
  {
    $reply = self::find($reply_id);
    $reply->vote_down_count += $count;
    $reply->vote_count -= $count;
    return $reply->save();
  }
  /**
   * 减少回复的回复数量
   * @param int $reply_id 回复ID
   * @param int $count 回复数量
   * @return bool
   */
  public static function SubReplyCount($reply_id, $count = 1): bool
  {
    $reply = self::find($reply_id);
    if($reply->reply_count <= 0){
      $reply->reply_count = 0;
      return $reply->save();
    }
    $reply->reply_count -= $count;
    return $reply->save();
  }
  /**
   * 减少回复的赞成票数量
   * @param int $reply_id 回复ID
   * @param int $count 回复数量
   * @return bool
   */
  public static function SubVoteUpCount($reply_id, $count = 1): bool
  {
    $reply = self::find($reply_id);
    $reply->vote_up_count -= $count;
    $reply->vote_count -= $count;
    return $reply->save();
  }
  /**
   * 减少回复的反对票数量
   * @param int $reply_id 回复ID
   * @param int $count 回复数量
   * @return bool
   */
  public static function SubVoteDownCount($reply_id, $count = 1): bool
  {
    $reply = self::find($reply_id);
    $reply->vote_down_count -= $count;
    $reply->vote_count += $count;
    return $reply->save();
  }
}
