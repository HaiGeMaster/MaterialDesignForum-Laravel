<?php

/**
 * Author HaiGeMaster
 * @package MaterialDesignForum
 * @link https://github.com/HaiGeMaster
 * @copyright Copyright (c) 2023 HaiGeMaster
 * @start-date 2023/05/20-15:53:29
 */

namespace App\Http\Controllers;


// use App\Http\Controllers\AnswerController;
// use App\Http\Controllers\ArticleController;
// use App\Http\Controllers\CacheController;
// use App\Http\Controllers\CommentController;
// use App\Http\Controllers\FollowController;
// use App\Http\Controllers\ImageController;
// use App\Http\Controllers\InboxController;
// use App\Http\Controllers\NotificationController;
// use App\Http\Controllers\OauthController;
// use App\Http\Controllers\OptionController;
// use App\Http\Controllers\QuestionController;
// use App\Http\Controllers\ReplyController;
// use App\Http\Controllers\ReportController;
// use App\Http\Controllers\TokenController;
// use App\Http\Controllers\TopicController;
// use App\Http\Controllers\TopicAbleController;
// use App\Http\Controllers\UserController;
// use App\Http\Controllers\UserGroupController;
// use App\Http\Controllers\UserOptionController;
// use App\Http\Controllers\VoteController;

// use App\Models\Answer as AnswerModel;
// use App\Models\Article as ArticleModel;
// use App\Models\Cache as CacheModel;
// use App\Models\Comment as CommentModel;
// use App\Models\Follow as FollowModel;
// use App\Models\Image as ImageModel;
// use App\Models\Inbox as InboxModel;
// use App\Models\Notification as NotificationModel;
// use App\Models\Oauth as OauthModel;
// use App\Models\Option as OptionModel;
// use App\Models\Question as QuestionModel;
// use App\Models\Reply as ReplyModel;
// use App\Models\Report as ReportModel;
// use App\Models\Token as TokenModel;
// use App\Models\Topic as TopicModel;
use App\Models\TopicAble as TopicAbleModel;
// use App\Models\User as UserModel;
// use App\Models\UserGroup as UserGroupModel;
// use App\Models\UserOption as UserOptionModel;
// use App\Models\Vote as VoteModel;
use App\Services\Share;
// use Illuminate\Http\Request;

class TopicAbleController extends Controller
{
  /**
   * 添加话题关联
   * @param int $topic_id 话题ID
   * @param int $topicable_id 关联ID
   * @param string $topicable_type 关联类型 article,question
   * @return bool 是否添加成功
   */
  public static function AddTopicAble($topic_id, $topicable_id, $topicable_type): bool
  {
    $topicable = new TopicAbleModel();
    $topicable->topic_id = $topic_id;
    $topicable->topicable_id = $topicable_id;
    $topicable->topicable_type = $topicable_type;
    $topicable->create_time = Share::ServerTime();
    return $topicable->save();
  }
  /**
   * 删除话题关联
   * @param int $topic_id 话题ID
   * @param int $topicable_id 关联ID
   * @param string $topicable_type 关联类型 article,question
   * @return bool 是否删除成功
   */
  public static function DeleteTopicAble($topic_id, $topicable_id, $topicable_type): bool
  {
    // $topicable = self::where('topic_id', '=', $topic_id)
    //   ->where('topicable_id', '=', $topicable_id)
    //   ->where('topicable_type', '=', $topicable_type)
    //   ->first();
    // if ($topicable != null) {
    //   return $topicable->delete();
    // } else {
    //   return false;
    // }

    //没有id列的表直接返回删除结果
    $result = TopicAbleModel::where('topic_id', '=', $topic_id)
      ->where('topicable_id', '=', $topicable_id)
      ->where('topicable_type', '=', $topicable_type)
      ->delete();
    //如果删除成功，返回true，否则返回false
    if ($result > 0) {
      return true;
    } else {
      return false;
    }
  }
  /**
   * 获取话题关联
   * @param int $topicable_id 关联对象ID
   * @param string $topicable_type 关联类型 article,question
   * @return TopicAbleModel 返回topicable_id 对应的话题 id数组
   */
  public static function GetTopicAbles($topicable_id, $topicable_type)
  {
    //获取topicable_id=$topicable_id且topicable_type=$topicable_type的数据，只需要topic_id字段
    $topic_ids = TopicAbleModel::where('topicable_id', '=', $topicable_id)
      ->where('topicable_type', '=', $topicable_type)
      ->pluck('topic_id');
    // //循环检查topic_id,如果话题不存在，删除该话题关联
    // foreach ($topic_ids as $topic_id) {
    //   $topic = TopicController::GetTopic($topic_id)['topic'];
    //   if($topic!=null){
    //     if ($topic->delete_time != 0) {
    //       //$topic_ids->forget($topic_id);
    //       //删除$topic_ids中的$topic_id
    //       $topic_ids = $topic_ids->forget($topic_id);
    //     }
    //   }
    // }
    return $topic_ids;
  }
  /**
   * 删除话题关联
   * @param TopicAbleModel $topic 话题关联模型
   * @return bool 是否删除成功
   */
  public static function DeleteTopicAbles($topic): bool
  {
    $topicables = TopicAbleModel::where('topic_id', '=', $topic->topic_id)->get();
    foreach ($topicables as $topicable) {
      $topicable->delete();
    }
    //再次查找，如果没有数据，说明删除成功
    $topicables = TopicAbleModel::where('topic_id', '=', $topic->topic_id)->get();
    if ($topicables->count() == 0) {
      return true;
    } else {
      return false;
    }
  }
}
