<?php

/**
 * Author HaiGeMaster
 * @package MaterialDesignForum
 * @link https://github.com/HaiGeMaster
 * @copyright Copyright (c) 2023 HaiGeMaster
 * @start-date 2023/05/20-15:53:29
 */

namespace App\Http\Controllers;

use App\Http\Controllers\AnswerController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CacheController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OauthController;
use App\Http\Controllers\OptionController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\ReplyController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TokenController;
// use App\Http\Controllers\TopicController;
use App\Http\Controllers\TopicAbleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserGroupController;
use App\Http\Controllers\UserOptionController;
use App\Http\Controllers\VoteController;

use App\Models\Answer as AnswerModel;
use App\Models\Article as ArticleModel;
use App\Models\Cache as CacheModel;
use App\Models\Comment as CommentModel;
use App\Models\Follow as FollowModel;
use App\Models\Image as ImageModel;
use App\Models\Inbox as InboxModel;
use App\Models\Notification as NotificationModel;
use App\Models\Oauth as OauthModel;
use App\Models\Option as OptionModel;
use App\Models\Question as QuestionModel;
use App\Models\Reply as ReplyModel;
use App\Models\Report as ReportModel;
use App\Models\Token as TokenModel;
use App\Models\Topic as TopicModel;
use App\Models\TopicAble as TopicAbleModel;
use App\Models\User as UserModel;
use App\Models\UserGroup as UserGroupModel;
use App\Models\UserOption as UserOptionModel;
use App\Models\Vote as VoteModel;
use Illuminate\Http\Request;
use App\Services\Share;

class TopicController extends Controller
{
  /**
   * 获取话题所有者用户id
   * @param int $topic_id 话题ID
   * @return int|null 用户ID
   */
  public static function GetTopicOwnerId($topic_id)
  {
    $topic = TopicModel::find($topic_id);
    if ($topic != null) {
      return $topic->user_id;
    }
    return null;
  }

  /**
   * 获取关联的话题，来自TopicAbleController
   * @param int $topicable_id 话题关联ID
   * @param string $topicable_type 话题关联类型
   * @return array|null 话题列表
   */
  public static function GetAblesTopic($topicable_id, $topicable_type)
  {
    $topic_ids = TopicAbleController::GetTopicAbles($topicable_id, $topicable_type);
    if ($topic_ids != null) {
      // $topics = TopicModel::whereIn('topic_id', $topic_ids)
      //   ->where('delete_time', '=', 0)//只获取删除时间为0的
      //   ->get();
      //获取id列表到topic_ids
      $topic_ids = TopicModel::whereIn('topic_id', $topic_ids)
        ->whereNull('delete_time') //只获取删除时间为0的
        ->pluck('topic_id')
        ->toArray();
      $topics = [];
      foreach ($topic_ids as $key => $value) {
        $topics[$key] = self::GetTopic($value)['topic'];
      }
      return $topics;
    }
  }
  /**
   * 增加话题
   * @param string $name 话题名称
   * @param string $description 话题描述
   * @param string $cover 话题封面
   * @param string $user_token 用户token
   * @return array is_add:是否增加成功 topic:话题 json
   */
  public static function AddTopic($name, $description, $cover, $user_token = '')
  {
    // $is_valid_content =
    //   $name != null &&
    //   $description != null &&
    //   // $cover != null &&
    //   $user_token != '' &&
    //   $name != '' &&
    //   $description != '' &&
    //   // $cover != '' &&
    //   $user_token != '';

    $is_valid_content = $name != '' && $description != '' && $cover != '' && $user_token != '';
    $is_add = false;
    $topic_id = null;
    if (
      $is_valid_content && (
        UserGroupController::Ability($user_token, 'ability_create_topic') ||
        UserGroupController::IsAdmin($user_token)
      )
    ) {
      $topic = new TopicModel();
      $user_id = TokenController::GetUserId($user_token);
      $topic->user_id = $user_id;
      $topic->name = $name;
      if ($cover != '') {
        $topic->cover = ImageController::SaveUploadImage('topic_cover', $cover, $user_id);
      } else {
        $topic->cover = [
          'original' => null,
          'small' => null,
          'middle' => null,
          'large' => null,
        ];
      }
      $topic->description = $description;
      $topic->article_count = 0;
      $topic->question_count = 0;
      $topic->follower_count = 0;
      $topic->create_time = Share::ServerTime();
      $topic->update_time = Share::ServerTime();
      $is_add = $topic->save();
      if ($is_add) {
        $topic_id = $topic->topic_id;
        UserModel::AddTopicCount($topic->user_id);

        // return [
        //   'ability_create_topic' => UserGroupController::Ability($user_token, 'ability_create_topic'),
        //   'IsAdmin' => UserGroupController::IsAdmin($user_token),
        //   'is_add' => $is_add,
        //   'topic' => self::GetTopic($topic_id, $user_token)['topic'],
        //   // 'topic_id' => $topic_id
        // ];
      }
    } else {
    }

    return [
      // 'name'=>$name,
      // 'description'=>$description,
      // 'cover'=>$cover,
      // 'user_token'=>$user_token,
      // 'is_valid_content'=>$is_valid_content,
      // 'ability_create_topic' => UserGroupController::Ability($user_token, 'ability_create_topic'),
      // 'IsAdmin' => UserGroupController::IsAdmin($user_token),
      'is_add' => $is_add,
      'topic' => self::GetTopic($topic_id, $user_token)['topic'],
      // 'topic_id' => $topic_id
    ];
  }
  /**
   * 获取话题
   * @param int $topic_id 话题ID
   * @param int $user_token 用户token
   * @return array is_get:是否获取 topic:话题 json
   */
  public static function GetTopic($topic_id, $user_token = '')
  {
    $topic = TopicModel::where('topic_id', '=', $topic_id)
      ->whereNull('delete_time')
      ->first();
    if ($topic) {
      // $topic['is_follow'] = false;
      // $topic['user'] = User::GetUserInfo($topic['user_id'], $user_token)['user'];
      $topic->is_follow = false;
      $topic->user = UserController::GetUserInfo($topic['user_id'], $user_token)['user'];
      if ($user_token != '') {
        //$topic['is_follow'] = FollowController::IsFollow($user_token, 'topic', $topic['topic_id'], true);
        $topic->is_follow = FollowController::IsFollow($user_token, 'topic', $topic->topic_id, true);
      }
      return [
        'is_get' => true,
        'topic' => $topic,
      ];
    } else {
      return [
        'is_get' => false,
        'topic' => null,
      ];
    }
  }
  /**
   * 获取话题列表
   * @param string $order 排序
   * @param int $page 页数
   * @param bool $following 是否获取关注的话题
   * @param int $user_token 用户token
   * @param int $per_page 每页数量
   * @param string $search_keywords 搜索关键词
   * @param array $search_field 搜索字段
   * @return array is_get:是否获取 data:话题列表
   */
  public static function GetTopics(
    $order,
    $page,
    $following = false,
    $user_token = '',
    $per_page = 20,
    $search_keywords = '',
    $search_field = []
  ) {
    if ($search_field == []) {
      $search_field = TopicModel::$search_field;
    }

    $data = Share::HandleDataAndPagination(null);
    $orders = Share::HandleArrayField($order);
    $field = $orders['field'];
    $sort = $orders['sort'];
    if ($following == 'false' || $following == false) {
      if ($search_keywords != '') {
        $data = TopicModel::whereNull('delete_time')
          //->where($search_field, 'like', '%' . $search_keywords . '%')
          ->where(function ($query) use ($search_field, $search_keywords) {
            foreach ($search_field as $key => $value) {
              $query->orWhere($value, 'like', '%' . $search_keywords . '%');
            }
          })
          ->orderBy($field, $sort)
          ->paginate($per_page, ['*'], 'page', $page);
      } else {
        $data = TopicModel::whereNull('delete_time')->orderBy($field, $sort)->paginate($per_page, ['*'], 'page', $page);
      }
      $data = Share::HandleDataAndPagination($data);
    } else if ($following == 'true' || $following == true && $user_token != '') {
      $user_id = TokenController::GetUserId($user_token);
      $following_id_object = FollowModel::where('user_id', '=', $user_id)->where('followable_type', '=', 'topic')->paginate($per_page, ['*'], 'page', $page);
      $following_id_array = [];
      foreach ($following_id_object->items() as $key => $value) {
        array_push($following_id_array, $value->followable_id);
      }
      if ($search_keywords != '') {
        $data = TopicModel::whereNull('delete_time')
          //->where($search_field, 'like', '%' . $search_keywords . '%')
          ->where(function ($query) use ($search_field, $search_keywords) {
            foreach ($search_field as $key => $value) {
              $query->orWhere($value, 'like', '%' . $search_keywords . '%');
            }
          })
          ->whereIn(
            'topic_id',
            $following_id_array
          )->orderBy($field, $sort)
          ->paginate($per_page, ['*'], 'page', $page)
          ->items();
      } else {
        $data = TopicModel::whereNull('delete_time')->whereIn(
          'topic_id',
          $following_id_array
        )->orderBy($field, $sort)->paginate($per_page, ['*'], 'page', $page)->items();
      }
      $data = Share::HandleMergeDataAndPagination($data, $following_id_object);
    }
    if ($data['data'] != null) {
      foreach ($data['data'] as $key => $value) {
        $data['data'][$key]->is_follow = FollowController::IsFollow($user_token, 'topic', $value->topic_id, true);
        $data['data'][$key]->user = UserController::GetUserInfo($value->user_id, $user_token)['user'];
      }
    }
    return $data;
  }
  /**
   * 编辑话题
   * @param int $topic_id 话题ID
   * @param string $name 话题名称
   * @param string $description 话题描述
   * @param string $cover 话题封面
   * @param int $user_token 用户token
   * @return array is_edit:是否编辑
   */
  public static function EditTopic($topic_id, $name, $description, $cover, $user_token)
  {
    $is_valid_content =
      $topic_id != null &&
      $name != null &&
      $description != null &&
      //$cover != null &&
      $user_token != '' &&
      $topic_id != '' &&
      $name != '' &&
      $description != '' &&
      //$cover != '' &&
      $user_token != '';
    $is_edit = false;
    $user_id = TokenController::GetUserId($user_token);
    $topic = TopicModel::where('topic_id', '=', $topic_id)
      ->whereNull('delete_time')
      ->first();
    if ($topic != null && $is_valid_content && $user_id != null) {
      if (
        (
          TokenController::IsUserSelf($user_token, $topic->user_id) &&
          UserGroupController::Ability($user_token, 'ability_edit_own_topic') &&
          (
            UserGroupController::Ability($user_token, 'ability_edit_topic_only_no_article_or_question') ? ($topic->article_count == 0 && $topic->question_count == 0 ? true : false) : true
          ) &&
          UserGroupController::BeforeTime($user_token, 'time_before_edit_topic', $topic->create_time)
        )
        ||
        (UserGroupController::IsAdmin($user_token) && UserGroupController::Ability($user_token, 'ability_admin_manage_topic'))
        // UserGroupController::IsAdmin($user_token)
      ) {
        $topic->name = $name;
        if ($cover != '' && $cover != null) {
          //先将$topic->cover删除
          if (ImageController::DeleteUploadImage($topic->cover)) {
            $topic->cover = ImageController::SaveUploadImage('topic_cover', $cover, $user_id);
          }
        }
        $topic->description = $description;
        $topic->update_time = Share::ServerTime();
        $is_edit = $topic->save();

        if ($is_edit) {
          // NotificationController::AddInteractionNotification()
          //从关注关系中获取所有关注此话题的用户id
          // $following_id_array = FollowController::GetFollowingObjectUserIds('topic', $topic_id);
          // if ($following_id_array != null) {
          //   //遍历$following_id_array数组，为每个用户添加关注的提问更新通知
          //   foreach ($following_id_array as $key => $value) {
          //     NotificationController::AddInteractionNotification(
          //       $value,
          //       $topic->user_id,
          //       'follow_topic_update',
          //       null,
          //       null,
          //       0,
          //       $topic_id
          //     );
          //   }
          // }
        }
      }
    }
    return [
      'is_edit' => $is_edit,
      'topic' => self::GetTopic($topic_id, $user_token)['topic']
    ];
  }
  /**
   * 删除话题
   * @param array $topic_ids 话题ID数组
   * @param int $user_token 用户token
   * @return array is_delete:是否删除
   */
  public static function DeleteTopics($topic_ids, $user_token)
  {
    //return TopicModel::whereIn('topic_id', $topic_ids)->get();//$topic_ids;
    $is_valid_content =
      $topic_ids != null &&
      $user_token != '' &&
      $topic_ids != '' &&
      $user_token != '';
    $is_delete = false;
    $user_id = TokenController::GetUserId($user_token);
    $delete_ids = [];
    $topics = [];
    if (
      $user_id != null &&
      $is_valid_content
    ) {
      $topics = TopicModel::whereIn('topic_id', $topic_ids)->get();
      foreach ($topics as $key => $topic) {
        if (
          (
            TokenController::IsUserSelf($user_token, $topic->user_id) &&
            UserGroupController::Ability($user_token, 'ability_delete_own_question') &&
            (
              UserGroupController::Ability($user_token, 'ability_delete_topic_only_no_article_or_question') ? ($topic->article_count == 0 && $topic->question_count == 0 ? true : false) : true
            ) &&
            UserGroupController::BeforeTime($user_token, 'time_before_delete_topic', $topic->create_time)
          )
          ||
          (UserGroupController::IsAdmin($user_token) && UserGroupController::Ability($user_token, 'ability_admin_manage_topic'))
          // UserGroupController::IsAdmin($user_token)
        ) {

          //删除话题关系 暂时进行伪删除
          //TopicAbleController::DeleteTopicAbles($value);
          //减少用户话题数
          UserModel::SubTopicCount($topic->user_id);
          //删除通知
          NotificationController::AddInteractionNotification(
            $topic->user_id,
            $user_id,
            'topic_delete',
            null,
            null,
            0,
            $topic->topic_id,
          );
          //删除话题
          $topic->delete_time = Share::ServerTime();

          $is_delete = $topic->save();

          array_push($delete_ids, $topic->topic_id);
        }
      }
    }
    return [
      'is_delete' => $is_delete,
      'delete_ids' => $delete_ids,
      'data' => $topics
    ];
  }
}
