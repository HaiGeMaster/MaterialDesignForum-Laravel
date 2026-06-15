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
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Answer extends Model
{
    protected $table = 'answer';
    protected $primaryKey = 'answer_id';
    public $timestamps = false;

    protected $fillable = [
        'question_id',
        'user_id',
        'content_markdown',
        'content_rendered',
        'comment_count',
        'vote_count',
        'vote_up_count',
        'vote_down_count',
        'create_time',
        'update_time',
        'delete_time',
    ];

    protected $casts = [
        'answer_id' => 'integer',
        'question_id' => 'integer',
        'user_id' => 'integer',
        'comment_count' => 'integer',
        'vote_count' => 'integer',
        'vote_up_count' => 'integer',
        'vote_down_count' => 'integer',
        'create_time' => 'timestamp',
        'update_time' => 'timestamp',
        'delete_time' => 'timestamp',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id', 'question_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function votes(): MorphMany
    {
        return $this->morphMany(Vote::class, 'votable');
    }

    // 搜索字段
  public static array $search_field = ['content_markdown'];
  /**
   * 添加回答的评论数量
   * @param int $answer_id 回答ID
   * @param int $count 回答数量
   * @return bool
   */
  public static function AddCommentCount($answer_id, $count = 1): bool
  {
    $answer = self::find($answer_id);
    $answer->comment_count += $count;
    return $answer->save();
  }
  /**
   * 添加回答的赞成票数量
   * @param int $answer_id 回答ID
   * @param int $count 回答数量
   * @return bool
   */
  public static function AddVoteUpCount($answer_id, $count = 1): bool
  {
    $answer = self::find($answer_id);
    $answer->vote_up_count += $count;
    $answer->vote_count += $count;
    return $answer->save();
  }
  /**
   * 添加回答的反对票数量
   * @param int $answer_id 回答ID
   * @param int $count 回答数量
   * @return bool
   */
  public static function AddVoteDownCount($answer_id, $count = 1): bool
  {
    $answer = self::find($answer_id);
    $answer->vote_down_count += $count;
    $answer->vote_count -= $count;
    return $answer->save();
  }
  /**
   * 减少回答的评论数量
   * @param int $answer_id 回答ID
   * @param int $count 回答数量
   * @return bool
   */
  public static function SubCommentCount($answer_id, $count = 1): bool
  {
    $answer = self::find($answer_id);
    if($answer->comment_count <= 0){
      $answer->comment_count = 0;
      return $answer->save();
    }
    $answer->comment_count -= $count;
    return $answer->save();
  }
  /**
   * 减少回答的赞成票数量
   * @param int $answer_id 回答ID
   * @param int $count 回答数量
   * @return bool
   */
  public static function SubVoteUpCount($answer_id, $count = 1): bool
  {
    $answer = self::find($answer_id);
    $answer->vote_up_count -= $count;
    $answer->vote_count -= $count;
    return $answer->save();
  }
  /**
   * 减少回答的反对票数量
   * @param int $answer_id 回答ID
   * @param int $count 回答数量
   * @return bool
   */
  public static function SubVoteDownCount($answer_id, $count = 1): bool
  {
    $answer = self::find($answer_id);
    $answer->vote_down_count -= $count;
    $answer->vote_count += $count;
    return $answer->save();
  }
}
