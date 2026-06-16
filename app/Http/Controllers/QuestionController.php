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
// use App\Http\Controllers\QuestionController;
use App\Http\Controllers\ReplyController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\TopicController;
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

class QuestionController extends Controller
{
  /**
   * 获取问题所有者用户id
   * @param int $question_id 问题ID
   * @return int|null 用户ID
   */
  public static function GetQuestionOwnerId($question_id)
  {
    $question = QuestionModel::find($question_id);
    if ($question != null) {
      return $question->user_id;
    }
    return null;
  }
  /**
   * 添加提问
   * @param string $title 标题
   * @param array $topics 话题ID数组
   * @param string $content_markdown 纯文本
   * @param string $content_rendered 渲染后的HTML
   * @param string $user_token 用户Token
   * @return array [is_add=>bool,question_id=>int|null] 是否添加成功，问题ID或null
   */
  public static function AddQuestion($title, $topics, $content_markdown, $content_rendered, $user_token)
  {
    $is_valid_content =
      $title != null &&
      $topics != null &&
      $content_markdown != null &&
      $content_rendered != null &&
      $user_token != '' &&
      $title != '' &&
      $topics != [] &&
      $content_markdown != '' &&
      $content_rendered != '' &&
      $user_token != '';
    $is_add = false;
    $question_id = null;
    $user_id = TokenController::GetUserId($user_token);
    if (
      $user_id != null
      && $is_valid_content
      && (
        UserGroupController::Ability($user_token, 'ability_create_question') ||
        UserGroupController::IsAdmin($user_token)
      )
    ) {
      $content_markdown = preg_replace('/\s+/', '', $content_markdown);//去除回车和空格
      
      $question = new QuestionModel;
      $question->user_id = $user_id;
      $question->title = $title;
      $question->content_markdown = $content_markdown;
      $question->content_rendered = $content_rendered;
      $question->create_time = Share::ServerTime();
      $question->update_time = Share::ServerTime();
      $question->last_answer_time = Share::ServerTime();
      $is_add = $question->save();
      if ($is_add) {
        UserModel::AddQuestionCount($user_id);
        $following_id_array = FollowController::GetFollowingObjectUserIds('user', $user_id);
        if ($following_id_array != null) {
          //遍历$following_id_array数组，为每个用户添加关注的提问更新通知
          foreach ($following_id_array as $key => $value) {
            NotificationController::AddInteractionNotification(
              $value,
              $user_id,
              'follow_user_update',
              null,
              null,
              0,
              0,
              0,
              $question->question_id
            );
          }
        }
      }
      foreach ($topics as $topic_id) {
        if (TopicAbleController::AddTopicAble($topic_id, $question->question_id, 'question')) {
          TopicModel::AddQuestionCount($topic_id);
        }
      }
      //根据$topics查询话题的关注者
      $follower_id_array = FollowModel::where('followable_type', '=', 'topic')
      ->whereIn('followable_id', $topics)
      ->get()->pluck('user_id')->toArray();
      if($follower_id_array != null){
        //遍历$follower_id_array数组，为每个用户添加关注的提问更新通知
        foreach ($follower_id_array as $key => $value) {
          NotificationController::AddInteractionNotification(
            $value,
            $user_id,
            'follow_topic_update',
            null,
            null,
            0,
            $topic_id,
            0,
            $question->question_id,
          );
        }
      }

      $question_id = $question->question_id;
    }
    return [
      'is_add' => $is_add,
      'question' => self::GetQuestion($question_id, $user_token)['question']
      // 'question_id' => $question_id
    ];
  }
  /**
   * 获取提问
   * @param int $question_id 问题ID
   * @param string $user_token 用户Token
   * @return array is_get:是否获取 question:问题信息
   */
  public static function GetQuestion($question_id, $user_token = '')
  {
    $question = QuestionModel::where('question_id', '=', $question_id)
      ->whereNull('delete_time')
      ->first();
    if ($question) {
      $question->topics = TopicController::GetAblesTopic($question_id, 'question');
      $question->user = UserController::GetUserInfo($question->user_id, $user_token)['user'];
      $question->is_follow = FollowController::IsFollow($user_token, 'question', $question_id, true);
      $question->vote = VoteController::GetVote($question->question_id, 'question', $user_token);
    }
    return [
      'is_get' => $question != null,
      'question' => $question,
    ];
  }
  /**
   * 获取提问列表
   * @param string $order 排序
   * @param int $page 页数
   * @param bool $following 是否获取关注的提问
   * @param int $user_token 用户token
   * @param int $per_page 每页数量
   * @param string $search_keywords 搜索关键词 不可与$specify_topic_id同时使用
   * @param array $search_field 搜索字段
   * @param int $specify_topic_id 指定ID 不可与$search_keywords同时使用
   * @return array is_get:是否获取 data:提问列表
   */
  public static function GetQuestions(
    $order,
    $page,
    $following = false,
    $user_token = '',
    $per_page = 20,
    $search_keywords = '',
    $search_field = [],
    $specify_topic_id = ''
  ) {
    if($search_field == []){
      $search_field = QuestionModel::$search_field;
    }

    $data = Share::HandleDataAndPagination(null);
    $orders = Share::HandleArrayField($order);

    $field = $orders['field'];
    $sort = $orders['sort'];
    if ($following == 'false' || $following == false) {
      if ($specify_topic_id != '') {
        $question_ids = TopicAbleModel::where('topic_id', '=', $specify_topic_id)
          ->where('topicable_type', '=', 'question')
          ->pluck('topicable_id'); //获取指定话题下的所有问题id
        $data = QuestionModel::whereNull('delete_time')
          ->whereIn(
            'question_id',
            $question_ids
          )
          ->orderBy($field, $sort)
          ->paginate($per_page, ['*'], 'page', $page);
      } else if ($search_keywords != '') {
        $data = QuestionModel::whereNull('delete_time')
          //->where($search_field, 'like', '%' . $search_keywords . '%')
          ->where(function ($query) use ($search_field, $search_keywords) {
            foreach ($search_field as $key => $value) {
              $query->orWhere($value, 'like', '%' . $search_keywords . '%');
            }
          })
          ->orderBy($field, $sort)
          ->paginate($per_page, ['*'], 'page', $page);
      } else {
        $data = QuestionModel::whereNull('delete_time')
          ->orderBy($field, $sort)
          ->paginate($per_page, ['*'], 'page', $page);
      }
      $data = Share::HandleDataAndPagination($data);
    } else if ($following == 'true' || $following == true) {
      $user_id = TokenController::GetUserId($user_token);
      $following_id_object = FollowModel::where('user_id', '=', $user_id)->where('followable_type', '=', 'question')->paginate($per_page, ['*'], 'page', $page);
      $following_id_array = [];
      foreach ($following_id_object->items() as $key => $value) {
        array_push($following_id_array, $value->followable_id);
      }
      if ($specify_topic_id != '') {
        $question_ids = TopicAbleModel::where('topic_id', '=', $specify_topic_id)
          ->where('topicable_type', '=', 'question')
          ->pluck('topicable_id'); //获取指定话题下的所有问题id
        //将following_id_array和question_ids取交集，找出这两个数组中共同的元素，并将其存储到新的数组中
        $following_id_array = array_intersect($following_id_array, $question_ids->toArray());
        $data = QuestionModel::whereNull('delete_time')
          ->whereIn(
            'question_id',
            $following_id_array
          )
          ->orderBy($field, $sort)
          ->paginate($per_page, ['*'], 'page', $page)
          ->items();
      } else if ($search_keywords != '') {
        $data = QuestionModel::whereNull('delete_time')
          //->where($search_field, 'like', '%' . $search_keywords . '%')
          ->where(function ($query) use ($search_field, $search_keywords) {
            foreach ($search_field as $key => $value) {
              $query->orWhere($value, 'like', '%' . $search_keywords . '%');
            }
          })
          ->whereIn(
            'question_id',
            $following_id_array
          )->orderBy($field, $sort)
          ->paginate($per_page, ['*'], 'page', $page)
          ->items();
      } else {
        $data = QuestionModel::whereNull('delete_time')->whereIn(
          'question_id',
          $following_id_array
        )->orderBy($field, $sort)->paginate($per_page, ['*'], 'page', $page)->items();
      }

      $data = Share::HandleMergeDataAndPagination($data, $following_id_object);
    }
    if ($data['data'] != null) {
      foreach ($data['data'] as $key => $value) {
        $data['data'][$key]->topics = TopicController::GetAblesTopic($value->question_id, 'question');
        $data['data'][$key]->user = UserController::GetUserInfo($value->user_id, $user_token)['user'];
        $data['data'][$key]->is_follow = FollowController::IsFollow($user_token, 'question', $value->question_id, true);
      }
    }

    return $data;
  }
  /**
   * 编辑提问
   * @param int $question_id 问题ID
   * @param string $title 标题
   * @param array $topics 话题ID数组
   * @param string $content_markdown 纯文本
   * @param string $content_rendered 渲染后的HTML
   * @param string $user_token 用户Token
   * @return array is_edit:是否编辑
   */
  public static function EditQuestion($question_id, $title, $topics, $content_markdown, $content_rendered, $user_token)
  {
    $is_valid_content =
      $question_id != null &&
      $title != null &&
      $topics != null &&
      $content_markdown != null &&
      $content_rendered != null &&
      $user_token != '' &&
      $question_id != '' &&
      $title != '' &&
      $topics != [] &&
      $content_markdown != '' &&
      $content_rendered != '' &&
      $user_token != '';
    $is_edit = false;
    $user_id = TokenController::GetUserId($user_token);
    $question = QuestionModel::where('question_id', '=', $question_id)
      ->whereNull('delete_time')
      ->first();
    if ($question != null && $is_valid_content && $user_id != null) {
      if (
        (
          TokenController::IsUserSelf($user_token, $question->user_id) &&
          UserGroupController::Ability($user_token, 'ability_edit_own_question') &&
          (
            UserGroupController::Ability($user_token, 'ability_edit_question_only_no_answer') ? ($question->answer_count == 0 ? true : false) : true
          ) &&
          (
            UserGroupController::Ability($user_token, 'ability_edit_question_only_no_comment') ? ($question->comment_count == 0 ? true : false) : true
          ) &&
          UserGroupController::BeforeTime($user_token, 'time_before_edit_question', $question->create_time)
        )
        ||
        (UserGroupController::IsAdmin($user_token)&&UserGroupController::Ability($user_token,'ability_admin_manage_question'))
         // UserGroupController::IsAdmin($user_token)
      ) {
        $content_markdown = preg_replace('/\s+/', '', $content_markdown);//去除回车和空格
        
        $question->title = $title;
        $question->content_markdown = $content_markdown;
        $question->content_rendered = $content_rendered;
        $question->update_time = Share::ServerTime();
        $is_edit = $question->save();
        if($is_edit){
          // NotificationController::AddInteractionNotification()
          //从关注关系中获取所有关注此问题的用户id
          $following_id_array = FollowController::GetFollowingObjectUserIds('question', $question_id);
          if($following_id_array!=null){
            //遍历$following_id_array数组，为每个用户添加关注的提问更新通知
            foreach ($following_id_array as $key => $value) {
              NotificationController::AddInteractionNotification(
                $value,
                $question->user_id,
                'follow_question_update',
                null,
                null,
                0,
                0,
                0,
                $question_id
              );
            }
          }
        }
        //首先从TopicAbleController中删除所有的topicable_id为$question_id的数据
        // TopicAbleController::where('topicable_id', '=', $question_id)->where('topicable_type', '=', 'question')->delete();

        //首先从TopicAbleController获取所有的topicable_id为$question_id的数据的topic_id数组
        $old_topics = TopicController::GetAblesTopic($question_id, 'question');
        // $old_topic_ids = [];
        if ($old_topics != null) {
          foreach ($old_topics as $old_topic) {
            TopicAbleController::DeleteTopicAble($old_topic->topic_id, $question_id, 'question');
            TopicModel::SubQuestionCount($old_topic->topic_id);
          }
        }
        //然后再添加新的数据
        foreach ($topics as $topic_id) {
          TopicAbleController::AddTopicAble($topic_id, $question->question_id, 'question');
          TopicModel::AddQuestionCount($topic_id);
        }
      }
    }
    return [
      'is_edit' => $is_edit,
      'question' => self::GetQuestion($question_id, $user_token)['question']
    ];
  }
  /**
   * 删除提问
   * @param int[] $question_ids 问题ID数组
   * @param string $user_token 用户Token
   * @return array is_delete:是否删除,delete_ids:删除成功的IDID数组
   */
  public static function DeleteQuestions($question_ids, $user_token)
  {
    $is_valid_content =
      $question_ids != null &&
      $user_token != '' &&
      $question_ids != [] &&
      $user_token != '';
    $is_delete = false;
    $user_id = TokenController::GetUserId($user_token);
    $delete_ids = [];
    $questions = [];
    if (
      $user_id != null &&
      $is_valid_content
    ) {
      $questions = QuestionModel::whereIn('question_id', $question_ids)->get();
      foreach ($questions as $key => $question) {
        if (
          (
            TokenController::IsUserSelf($user_token, $question->user_id) &&
            UserGroupController::Ability($user_token, 'ability_delete_own_question') &&
            (
              UserGroupController::Ability($user_token, 'ability_delete_question_only_no_answer') ? ($question->answer_count == 0 ? true : false) : true
            ) &&
            (
              UserGroupController::Ability($user_token, 'ability_delete_question_only_no_comment') ? ($question->comment_count == 0 ? true : false) : true
            ) &&
            UserGroupController::BeforeTime($user_token, 'time_before_delete_question', $question->create_time)
          )
          ||
          (UserGroupController::IsAdmin($user_token)&&UserGroupController::Ability($user_token,'ability_admin_manage_question'))
           // UserGroupController::IsAdmin($user_token)
        ) {
          UserModel::SubQuestionCount($question->user_id);
          NotificationController::AddInteractionNotification(
            $question->user_id,
            $user_id,
            'question_delete',
            null,
            null,
            0,
            0,
            0,
            $question->question_id,
            0,
            0,
            0
          );

          //联动删除此问题下的所有回答、评论、回复
          //将该问题下的所有回答的delete_time设置为当前时间
          // $answers = AnswerController::where('question_id', '=', $question->question_id)->get();
          // if($answers != null){
          //   foreach ($answers as $key => $answer) {
  
          //     //将该回答下的所有评论的delete_time设置为当前时间
          //     $comments = CommentController::where('commentable_id', '=', $answer->answer_id)
          //       ->where('commentable_type', '=', 'answer')
          //       ->get();
          //     if($comments != null){
          //       foreach ($comments as $key => $comment) {
          //         //将该评论下的所有回复的delete_time设置为当前时间
          //         $replys = ReplyController::where('replyable_comment_id', '=', $comment->comment_id)
          //         ->get();
          //         if($replys != null){
          //           foreach ($replys as $key => $reply) {
          //             $reply->delete_time = Share::ServerTime();
          //             $reply->save();
      
          //             //从用户的回复数中减去1
          //             UserController::SubReplyCount($reply->user_id);
          //           }
          //         }
    
          //         $comment->delete_time = Share::ServerTime();
          //         $comment->save();
    
          //         //从用户的评论数中减去1
          //         UserController::SubCommentCount($comment->user_id);
          //       }
          //     }
  
          //     $answer->delete_time = Share::ServerTime();
          //     $answer->save();
  
          //     //从用户的回答数中减去1
          //     UserController::SubAnswerCount($answer->user_id);
          //   }
          // }

          //将该问题下的所有评论的delete_time设置为当前时间
          // $comments = CommentController::where('commentable_id', '=', $question->question_id)
          //   ->where('commentable_type', '=', 'question')
          //   ->get();
          // if($comments != null){
          //   foreach ($comments as $key => $comment) {
          //     //将该评论下的所有回复的delete_time设置为当前时间
          //     $replys = ReplyController::where('replyable_comment_id', '=', $comment->comment_id)
          //     ->get();
          //     if($replys != null){
          //       foreach ($replys as $key => $reply) {
          //         $reply->delete_time = Share::ServerTime();
          //         $reply->save();
  
          //         //从用户的回复数中减去1
          //         UserController::SubReplyCount($reply->user_id);
          //       }
          //     }
  
          //     $comment->delete_time = Share::ServerTime();
          //     $comment->save();
  
          //     //从用户的评论数中减去1
          //     UserController::SubCommentCount($comment->user_id);
          //   }
          // }

          //减少对应话题的文章数量
          $topics = TopicController::GetAblesTopic($question->question_id, 'question');
          if ($topics != null) {
            foreach ($topics as $topic) {
              TopicModel::SubQuestionCount($topic->topic_id);
            }
          }
          //删除提问
          $question->delete_time = Share::ServerTime();

          $is_delete = $question->save();
          array_push($delete_ids, $question->question_id);
        }
      }
    }
    return [
      'is_delete' => $is_delete,
      'delete_ids' => $delete_ids,
      'data' => $questions
    ];
    // $user_id = TokenController::GetUserId($user_token);
    // if (
    //   $user_id != null
    //   && $is_valid_content
    //   && (
    //     UserGroupController::Ability($user_token, 'ability_delete_own_question') ||
    //     UserGroupController::IsAdmin($user_token)
    //   )
    // ) {
    //   $is_delete = QuestionModel::whereIn('question_id', $question_ids)->update(['delete_time' => Share::ServerTime()]);
    // }
    // return [
    //   'is_delete' => $is_delete,
    // ];
  }
}
