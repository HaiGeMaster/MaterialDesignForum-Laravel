<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
  protected $table = 'notification';
  protected $primaryKey = 'notification_id';
  public $timestamps = false;

  protected $fillable = [
    'receiver_id',
    'sender_id',
    'type',
    'content_markdown',
    'content_rendered',
    'user_id',
    'topic_id',
    'article_id',
    'question_id',
    'answer_id',
    'comment_id',
    'reply_id',
    'reply_to_reply_id',
    'create_time',
    'read_time',
    'delete_time',
  ];

  protected $casts = [
    'notification_id' => 'integer',
    'receiver_id' => 'integer',
    'sender_id' => 'integer',
    'user_id' => 'integer',
    'topic_id' => 'integer',
    'article_id' => 'integer',
    'question_id' => 'integer',
    'answer_id' => 'integer',
    'comment_id' => 'integer',
    'reply_id' => 'integer',
    'reply_to_reply_id' => 'integer',
    'create_time' => 'timestamp',
    'read_time' => 'timestamp',
    'delete_time' => 'timestamp',
  ];

    
    //类型注释定义
  /**
   * @typedef NotificationType 通知类型
   * @property string user_follow 自己被关注 已做完
   * @property string topic_follow 话题被关注 已做完
   * @property string topic_delete 话题被删除 已做完
   * @property string question_follow 提问被关注 已做完
   * @property string question_comment 提问被评论 已做完
   * @property string question_answer 提问被回答 已做完
   * @property string question_delete 提问被删除 已做完
   * @property string article_follow 文章被关注 已做完
   * @property string article_comment 文章被评论 已做完
   * @property string article_like 文章被点赞 已做完
   * @property string article_delete 文章被删除 已做完
   * @property string answer_comment 回答被评论 已做完
   * @property string answer_like 回答被点赞 已做完
   * @property string answer_delete 回答被删除 已做完
   * @property string comment_like 评论被点赞 已做完
   * @property string comment_reply 评论被回复 已做完
   * @property string comment_delete 评论被删除 已做完
   * @property string reply_like 回复被点赞 已做完
   * @property string reply_reply 回复被回复 已做完
   * @property string reply_delete 回复被删除 已做完
   * @property string follow_user_update 关注的用户更新 已做完
   * @property string follow_topic_update 关注的话题更新 已做完
   * @property string follow_question_update 关注的提问更新 已做完
   * @property string follow_article_update 关注的文章更新 已做完
   */

  public static $types = [
    'user_follow', //自己被关注
    'topic_follow', //话题被关注
    'topic_delete', //话题被删除
    'question_follow', //提问被关注
    'question_comment', //提问被评论
    'question_answer', //提问被回答
    'question_delete', //提问被删除
    'article_follow', //文章被关注
    'article_comment', //文章被评论
    'article_like', //文章被点赞
    'article_delete', //文章被删除
    'answer_comment', //回答被评论
    'answer_like', //回答被点赞
    'answer_delete', //回答被删除
    'comment_like', //评论被点赞
    'comment_reply', //评论被回复
    'comment_delete', //评论被删除
    'reply_like', //回复被点赞
    'reply_reply', //回复被回复
    'reply_delete', //回复被删除
    'follow_user_update', //关注的用户更新
    'follow_topic_update', //关注的话题更新
    'follow_question_update', //关注的提问更新
    'follow_article_update', //关注的文章更新
  ];
  /**
   * 是否是有效的消息类型
   * @param string $type 消息类型
   * @return bool
   */
  public static function IsVaildType($type): bool
  {
    return in_array($type, self::$types);
  }
  /**
   * 设置消息已读
   * @param int $notification_id 消息ID
   * @param int $read_time 阅读时间
   */
  public static function SetReadTime($notification_id, $read_time)
  {
    $notification = Notification::find($notification_id);
    if ($notification) {
      $notification->read_time = $read_time;
      $notification->save();
      return true;
    }
    return false;
  }
}
