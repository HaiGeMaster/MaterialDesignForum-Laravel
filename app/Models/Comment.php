<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Comment extends Model
{
    protected $table = 'comment';
    protected $primaryKey = 'comment_id';
    public $timestamps = false;

    protected $fillable = [
        'commentable_id',
        'commentable_type',
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
        'comment_id' => 'integer',
        'commentable_id' => 'integer',
        'user_id' => 'integer',
        'reply_count' => 'integer',
        'vote_count' => 'integer',
        'vote_up_count' => 'integer',
        'vote_down_count' => 'integer',
        'create_time' => 'timestamp',
        'update_time' => 'timestamp',
        'delete_time' => 'timestamp',
    ];

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    
  // 搜索字段
  public static array $search_field = ['content'];
  /**
   * 添加评论的回复数量
   * @param int $comment_id 评论ID
   * @param int $count 评论数量
   * @return bool
   */
  public static function AddReplyCount($comment_id, $count = 1): bool
  {
    $comment = self::find($comment_id);
    $comment->reply_count += $count;
    return $comment->save();
  }
  /**
   * 添加评论的赞成票数量
   * @param int $comment_id 评论ID
   * @param int $count 评论数量
   * @return bool
   */
  public static function AddVoteUpCount($comment_id, $count = 1): bool
  {
    $comment = self::find($comment_id);
    $comment->vote_up_count += $count;
    $comment->vote_count += $count;
    return $comment->save();
  }
  /**
   * 添加评论的反对票数量
   * @param int $comment_id 评论ID
   * @param int $count 评论数量
   * @return bool
   */
  public static function AddVoteDownCount($comment_id, $count = 1): bool
  {
    $comment = self::find($comment_id);
    $comment->vote_down_count += $count;
    $comment->vote_count -= $count;
    return $comment->save();
  }
  /**
   * 减少评论的回复数量
   * @param int $comment_id 评论ID
   * @param int $count 评论数量
   * @return bool
   */
  public static function SubReplyCount($comment_id, $count = 1): bool
  {
    $comment = self::find($comment_id);
    if($comment->reply_count <= 0){
      $comment->reply_count = 0;
      return $comment->save();
    }
    $comment->reply_count -= $count;

    return $comment->save();
  }
  /**
   * 减少评论的赞成票数量
   * @param int $comment_id 评论ID
   * @param int $count 评论数量
   * @return bool
   */
  public static function SubVoteUpCount($comment_id, $count = 1): bool
  {
    $comment = self::find($comment_id);
    $comment->vote_up_count -= $count;
    $comment->vote_count -= $count;
    return $comment->save();
  }
  /**
   * 减少评论的反对票数量
   * @param int $comment_id 评论ID
   * @param int $count 评论数量
   * @return bool
   */
  public static function SubVoteDownCount($comment_id, $count = 1): bool
  {
    $comment = self::find($comment_id);
    $comment->vote_down_count -= $count;
    $comment->vote_count += $count;
    return $comment->save();
  }
}
