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
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Topic extends Model
{
  protected $table = 'topic';
  protected $primaryKey = 'topic_id';
  public $timestamps = false;

  protected $fillable = [
    'user_id',
    'name',
    'cover',
    'description',
    'article_count',
    'question_count',
    'follower_count',
    'create_time',
    'update_time',
    'delete_time',
  ];

  protected $casts = [
    'topic_id' => 'integer',
    'cover' => 'array',
    'user_id' => 'integer',
    'article_count' => 'integer',
    'question_count' => 'integer',
    'follower_count' => 'integer',
    'create_time' => 'datetime',
    'update_time' => 'datetime',
    'delete_time' => 'datetime',
  ];

  // public function articles(): MorphToMany
  // {
  //   return $this->morphedByMany(Article::class, 'topicable', 'topicable', 'topic_id', 'topicable_id');
  // }

  // public function questions(): MorphToMany
  // {
  //   return $this->morphedByMany(Question::class, 'topicable', 'topicable', 'topic_id', 'topicable_id');
  // }

  // public function followers(): MorphToMany
  // {
  //   return $this->morphedByMany(User::class, 'followable', 'follow', 'followable_id', 'user_id');
  // }


  // 搜索字段
  public static array $search_field = ['name', 'description'];
  /**
   * 增加文章数量
   * @param int $topic_id 话题ID
   * @param int $count 增加数量
   * @return bool 是否成功
   */
  public static function AddArticleCount($topic_id, $count = 1): bool
  {
    $topic = self::find($topic_id);
    if ($topic) {
      $topic->article_count = $topic->article_count + $count;
      return $topic->save();
    } else {
      return false;
    }
  }
  /**
   * 增加问题数量
   * @param int $topic_id 话题ID
   * @param int $count 增加数量
   * @return bool 是否成功
   */
  public static function AddQuestionCount($topic_id, $count = 1): bool
  {
    $topic = self::find($topic_id);
    if ($topic) {
      $topic->question_count = $topic->question_count + $count;
      return $topic->save();
    } else {
      return false;
    }
  }
  /**
   * 增加关注者数量
   * @param int $topic_id 话题ID
   * @param int $count 增加数量
   * @return bool 是否成功
   */
  public static function AddFollowerCount($topic_id, $count = 1): bool
  {
    $topic = self::find($topic_id);
    if ($topic) {
      if ($topic->follower_count <= 0) {
        $topic->follower_count = 0; // 如果关注者数量小于0，重置为0
      }
      $topic->follower_count = $topic->follower_count + $count;
      return $topic->save();
    } else {
      return false;
    }
  }
  /**
   * 减少文章数量
   * @param int $topic_id 话题ID
   * @param int $count 减少数量
   * @return bool 是否成功
   */
  public static function SubArticleCount($topic_id, $count = 1): bool
  {
    $topic = self::find($topic_id);
    if ($topic) {
      if ($topic->article_count <= 0) {
        $topic->article_count = 0;
        return $topic->save();
      }
      $topic->article_count -= $count;
      return $topic->save();
    } else {
      return false;
    }
  }
  /**
   * 减少问题数量
   * @param int $topic_id 话题ID
   * @param int $count 减少数量
   * @return bool 是否成功
   */
  public static function SubQuestionCount($topic_id, $count = 1): bool
  {
    $topic = self::find($topic_id);
    if ($topic) {
      if ($topic->question_count <= 0) {
        $topic->question_count = 0;
        return $topic->save();
      }
      $topic->question_count -= $count;
      return $topic->save();
    } else {
      return false;
    }
  }
  /**
   * 减少关注者数量
   * @param int $topic_id 话题ID
   * @param int $count 减少数量
   * @return bool 是否成功
   */
  public static function SubFollowerCount($topic_id, $count = 1): bool
  {
    $topic = self::find($topic_id);
    if ($topic) {
      if ($topic->follower_count <= 0) {
        $topic->follower_count = 0;
        return $topic->save();
      }
      $topic->follower_count -= $count;
      return $topic->save();
    } else {
      return false;
    }
  }
}
