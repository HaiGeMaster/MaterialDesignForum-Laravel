<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Question extends Model
{
    protected $table = 'question';
    protected $primaryKey = 'question_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'title',
        'content_markdown',
        'content_rendered',
        'comment_count',
        'answer_count',
        'follower_count',
        'vote_count',
        'vote_up_count',
        'vote_down_count',
        'last_answer_time',
        'create_time',
        'update_time',
        'delete_time',
    ];

    protected $casts = [
        'question_id' => 'integer',
        'user_id' => 'integer',
        'comment_count' => 'integer',
        'answer_count' => 'integer',
        'follower_count' => 'integer',
        'vote_count' => 'integer',
        'vote_up_count' => 'integer',
        'vote_down_count' => 'integer',
        'last_answer_time' => 'timestamp',
        'create_time' => 'timestamp',
        'update_time' => 'timestamp',
        'delete_time' => 'timestamp',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class, 'question_id', 'question_id');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function votes(): MorphMany
    {
        return $this->morphMany(Vote::class, 'votable');
    }

    public function followers(): MorphMany
    {
        return $this->morphMany(Follow::class, 'followable');
    }

    public function topics(): MorphToMany
    {
        return $this->morphToMany(Topic::class, 'topicable', 'topicable', 'topicable_id', 'topic_id');
    }

    
  // 搜索字段
  public static array $search_field = ['title','content_markdown'];
  /**
   * 添加评论数
   * @param int $question_id 问题ID
   * @param int $count 数量
   * @return bool
   */
  public static function AddCommentCount($question_id, $count = 1): bool
  {
    $question = self::find($question_id);
    $question->comment_count += $count;
    return $question->save();
  }
  /**
   * 添加回答数
   * @param int $question_id 问题ID
   * @param int $count 数量
   * @return bool
   */
  public static function AddAnswerCount($question_id, $count = 1): bool
  {
    $question = self::find($question_id);
    $question->answer_count += $count;
    return $question->save();
  }
  /**
   * 添加关注数
   * @param int $question_id 问题ID
   * @param int $count 数量
   * @return bool
   */
  public static function AddFollowerCount($question_id, $count = 1): bool
  {
    $question = self::find($question_id);
    $question->follower_count += $count;
    return $question->save();
  }
  /**
   * 添加赞成投票数
   * @param int $question_id 问题ID
   * @param int $count 数量
   * @return bool
   */
  public static function AddVoteUpCount($question_id, $count = 1): bool
  {
    $question = self::find($question_id);
    $question->vote_up_count += $count;
    $question->vote_count += $count;
    return $question->save();
  }
  /**
   * 添加反对投票数
   * @param int $question_id 问题ID
   * @param int $count 数量
   * @return bool
   */
  public static function AddVoteDownCount($question_id, $count = 1): bool
  {
    $question = self::find($question_id);
    $question->vote_down_count += $count;
    $question->vote_count -= $count;
    return $question->save();
  }
  /**
   * 减少评论数
   * @param int $question_id 问题ID
   * @param int $count 数量
   * @return bool
   */
  public static function SubCommentCount($question_id, $count = 1): bool
  {
    $question = self::find($question_id);
    if($question->comment_count <= 0){
      $question->comment_count = 0;
      return $question->save();
    }
    $question->comment_count -= $count;
    return $question->save();
  }
  /**
   * 减少回答数
   * @param int $question_id 问题ID
   * @param int $count 数量
   * @return bool
   */
  public static function SubAnswerCount($question_id, $count = 1): bool
  {
    $question = self::find($question_id);
    if($question->answer_count <= 0){
      $question->answer_count = 0;
      return $question->save();
    }
    $question->answer_count -= $count;
    return $question->save();
  }
  /**
   * 减少关注数
   * @param int $question_id 问题ID
   * @param int $count 数量
   * @return bool
   */
  public static function SubFollowerCount($question_id, $count = 1): bool
  {
    $question = self::find($question_id);
    if($question->follower_count <= 0){
      $question->follower_count = 0;
      return $question->save();
    }
    $question->follower_count -= $count;
    return $question->save();
  }
  /**
   * 减少赞成投票数
   * @param int $question_id 问题ID
   * @param int $count 数量
   * @return bool
   */
  public static function SubVoteUpCount($question_id, $count = 1): bool
  {
    $question = self::find($question_id);
    $question->vote_up_count -= $count;
    $question->vote_count -= $count;
    return $question->save();
  }
  /**
   * 减少反对投票数
   * @param int $question_id 问题ID
   * @param int $count 数量
   * @return bool
   */
  public static function SubVoteDownCount($question_id, $count = 1): bool
  {
    $question = self::find($question_id);
    $question->vote_down_count -= $count;
    $question->vote_count += $count;
    return $question->save();
  }
}
