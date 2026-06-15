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
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Article extends Model
{
    protected $table = 'article';
    protected $primaryKey = 'article_id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'title',
        'content_markdown',
        'content_rendered',
        'comment_count',
        'follower_count',
        'vote_count',
        'vote_up_count',
        'vote_down_count',
        'create_time',
        'update_time',
        'delete_time',
    ];

    protected $casts = [
        'article_id' => 'integer',
        'user_id' => 'integer',
        'comment_count' => 'integer',
        'follower_count' => 'integer',
        'vote_count' => 'integer',
        'vote_up_count' => 'integer',
        'vote_down_count' => 'integer',
        'create_time' => 'timestamp',
        'update_time' => 'timestamp',
        'delete_time' => 'timestamp',
    ];

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
   * 添加文章的评论数量
   * @param int $article_id 文章ID
   * @param int $count 文章数量
   * @return bool
   */
  public static function AddCommentCount($article_id, $count = 1): bool
  {
    $article = self::find($article_id);
    $article->comment_count += $count;
    return $article->save();
  }
  /**
   * 添加文章的关注者数量
   * @param int $article_id 文章ID
   * @param int $count 文章数量
   * @return bool
   */
  public static function AddFollowerCount($article_id, $count = 1): bool
  {
    $article = self::find($article_id);
    $article->follower_count += $count;
    return $article->save();
  }
  /**
   * 添加文章的赞成票数量
   * @param int $article_id 文章ID
   * @param int $count 文章数量
   * @return bool
   */
  public static function AddVoteUpCount($article_id, $count = 1): bool
  {
    $article = self::find($article_id);
    $article->vote_up_count += $count;
    $article->vote_count += $count;
    return $article->save();
  }
  /**
   * 添加文章的反对票数量
   * @param int $article_id 文章ID
   * @param int $count 文章数量
   * @return bool
   */
  public static function AddVoteDownCount($article_id, $count = 1): bool
  {
    $article = self::find($article_id);
    $article->vote_down_count += $count;
    $article->vote_count -= $count;
    return $article->save();
  }
  /**
   * 减少文章的评论数量
   * @param int $article_id 文章ID
   * @param int $count 文章数量
   * @return bool
   */
  public static function SubCommentCount($article_id, $count = 1): bool
  {
    $article = self::find($article_id);
    if($article->comment_count <= 0){
      $article->comment_count = 0;
      return $article->save();
    }
    $article->comment_count -= $count;
    return $article->save();
  }
  /**
   * 减少文章的关注者数量
   * @param int $article_id 文章ID
   * @param int $count 文章数量
   * @return bool
   */
  public static function SubFollowerCount($article_id, $count = 1): bool
  {
    $article = self::find($article_id);
    if($article->follower_count <= 0){
      $article->follower_count = 0;
      return $article->save();
    }
    $article->follower_count -= $count;
    return $article->save();
  }
  /**
   * 减少文章的赞成票数量
   * @param int $article_id 文章ID
   * @param int $count 文章数量
   * @return bool
   */
  public static function SubVoteUpCount($article_id, $count = 1): bool
  {
    $article = self::find($article_id);
    $article->vote_up_count -= $count;
    $article->vote_count -= $count;
    return $article->save();
  }
  /**
   * 减少文章的反对票数量
   * @param int $article_id 文章ID
   * @param int $count 文章数量
   * @return bool
   */
  public static function SubVoteDownCount($article_id, $count = 1): bool
  {
    $article = self::find($article_id);
    $article->vote_down_count -= $count;
    $article->vote_count += $count;
    return $article->save();
  }
}
