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
use App\Http\Controllers\TopicController;
use App\Http\Controllers\TopicAbleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserGroupController;
use App\Http\Controllers\UserOptionController;
// use App\Http\Controllers\VoteController;

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

class VoteController extends Controller
{
  /**
   * 投票
   * @param string $user_token 用户Token
   * @param int $votable_id 投票对象ID
   * @param string $votable_type 投票对象类型 question、answer、article、comment、reply
   * @param int $type 投票类型 up down
   * @return array is_add_vote:是否增加投票 is_sub_vote:是否减少投票 vote:投票模型json
   */
  public static function Vote($user_token, $votable_id, $votable_type, $type)
  {
    $is_valid_content =
      $user_token != '' &&
      $votable_id != null &&
      $votable_type != null &&
      $type != null &&
      $user_token != '' &&
      $votable_id != '' &&
      $votable_type != '' &&
      $type != '';
    $user_id = TokenController::GetUserId($user_token);
    $is_add_vote = false;
    $is_sub_vote = false;
    $vote = null;
    if (
      $user_id != null
      && $is_valid_content
      && (
        UserGroupController::Ability($user_token, 'ability_vote') ||
        UserGroupController::IsAdmin($user_token)
      )
    ) {
      $vote = VoteModel::where('user_id', '=', $user_id)
        ->where('votable_id', '=', $votable_id)
        ->where('votable_type', '=', $votable_type)
        ->where('type', '=', $type)
        ->first();
      if ($vote != null) {
        $is_sub_vote = VoteModel::where('user_id', '=', $user_id)
          ->where('votable_id', '=', $votable_id)
          ->where('votable_type', '=', $votable_type)
          ->where('type', '=', $type)
          ->delete() != 0;
        if ($is_sub_vote) {
          if ($type == 'up') {
            switch ($votable_type) {
              case 'answer':
                AnswerModel::SubVoteUpCount($votable_id);
                NotificationController::AddInteractionNotification(
                  AnswerController::GetAnswerOwnerId($votable_id),
                  $user_id,
                  'answer_like',
                  null,
                  null,
                  0,
                  0,
                  0,
                  AnswerController::GetAnswer($votable_id)['answer']['question_id'],
                  $votable_id,
                );
                break;
              case 'article':
                ArticleModel::SubVoteUpCount($votable_id);
                NotificationController::AddInteractionNotification(
                  ArticleController::GetArticleOwnerId($votable_id),
                  $user_id,
                  'article_like',
                  null,
                  null,
                  0,
                  0,
                  $votable_id,
                );

                break;
              case 'comment':
                CommentModel::SubVoteUpCount($votable_id);
                $comment = CommentController::GetComment($votable_id)['comment'];
                NotificationController::AddInteractionNotification(
                  CommentController::GetCommentOwnerId($votable_id),
                  $user_id,
                  'comment_like',
                  null,
                  null,
                  0,
                  0,
                  $comment->commentable_type == 'article' ? $comment->commentable_id : 0,
                  $comment->commentable_type == 'question' ? $comment->commentable_id : 0,
                  $comment->commentable_type == 'answer' ? $comment->commentable_id : 0,
                  $votable_id,
                );
                break;
              case 'reply':
                ReplyModel::SubVoteUpCount($votable_id);
                $reply = ReplyController::GetReply($votable_id)['reply'];
                $comment = CommentController::GetComment($reply->replyable_comment_id)['comment'];
                NotificationController::AddInteractionNotification(
                  $reply->user_id,
                  $user_id,
                  'reply_like',
                  null,
                  null,
                  0,
                  0,
                  $comment->commentable_type == 'article' ? $comment->commentable_id : 0,
                  $comment->commentable_type == 'question' ? $comment->commentable_id : 0,
                  $comment->commentable_type == 'answer' ? $comment->commentable_id : 0,
                  $comment->comment_id,
                  $reply->reply_id,
                  $reply->replyable_type == 'reply' ? $reply->reply_id : 0,
                  // $reply->replyable_type == 'comment' ? $reply->replyable_id : 0,
                  // $reply->replyable_type == 'reply' ? $reply->replyable_id : 0,
                );
                break;
            }
          } else if ($type == 'down') {
            switch ($votable_type) {
              case 'answer':
                AnswerModel::SubVoteDownCount($votable_id);
                break;
              case 'article':
                ArticleModel::SubVoteDownCount($votable_id);
                break;
              case 'comment':
                CommentModel::SubVoteDownCount($votable_id);
                break;
              case 'reply':
                ReplyModel::SubVoteDownCount($votable_id);
                break;
            }
          }
        }
      } else {
        $vote = new VoteModel;
        $vote->user_id = $user_id;
        $vote->votable_id = $votable_id;
        $vote->votable_type = $votable_type;
        $vote->type = $type;
        $vote->create_time = Share::ServerTime();
        $is_add_vote = $vote->save();
        if ($is_add_vote) {
          if ($type == 'up') {
            switch ($votable_type) {
              case 'answer':
                AnswerModel::AddVoteUpCount($votable_id);
                break;
              case 'article':
                ArticleModel::AddVoteUpCount($votable_id);
                break;
              case 'comment':
                CommentModel::AddVoteUpCount($votable_id);
                break;
              case 'reply':
                ReplyModel::AddVoteUpCount($votable_id);
                break;
            }
          } else if ($type == 'down') {
            switch ($votable_type) {
              case 'answer':
                AnswerModel::AddVoteDownCount($votable_id);
                break;
              case 'article':
                ArticleModel::AddVoteDownCount($votable_id);
                break;
              case 'comment':
                CommentModel::AddVoteDownCount($votable_id);
                break;
              case 'reply':
                ReplyModel::AddVoteDownCount($votable_id);
                break;
            }
          }
        }
      }
    }
    return [
      'is_add_vote' => $is_add_vote,
      'is_sub_vote' => $is_sub_vote,
      'vote' => self::GetVote($votable_id, $votable_type, $user_token)['vote'],
    ];
  }
  /**
   * 获取投票
   * @param int $votable_id 投票对象ID
   * @param string $votable_type 投票对象类型 question、answer、article、comment、reply
   * @param string $user_token 用户Token
   * @return array is_get:是否获取 vote:投票信息[]
   */
  public static function GetVote($votable_id, $votable_type, $user_token)
  {
    $is_valid_content =
      $votable_id != null &&
      $user_token != '' &&
      $votable_id != '' &&
      $user_token != '';
    // $user_id = TokenController::GetUserId($user_token);

    $up_count = 0;
    $down_count = 0;
    $up_value = false;
    $down_value = false;
    $vote = null;
    // if ($user_id != null && $is_valid_content) {
    //   $vote_up = VoteModel::where('user_id', '=', $user_id)
    //     ->where('votable_id', '=', $votable_id)
    //     ->where('votable_type', '=', $votable_type)
    //     ->where('type', '=', 'up')
    //     ->first();
    //   $vote_down = VoteModel::where('user_id', '=', $user_id)
    //     ->where('votable_id', '=', $votable_id)
    //     ->where('votable_type', '=', $votable_type)
    //     ->where('type', '=', 'down')
    //     ->first();
    //   $up_count = VoteModel::where('votable_id', '=', $votable_id)
    //     ->where('votable_type', '=', $votable_type)
    //     ->where('type', '=', 'up')
    //     ->count();
    //   $down_count = VoteModel::where('votable_id', $votable_id)
    //     ->where('votable_type', '=', $votable_type)
    //     ->where('type', '=', 'down')
    //     ->count();
    //   $up_value = $vote_up != null && $vote_up->type == 'up';
    //   $down_value = $vote_down != null && $vote_down->type == 'down';
    // }
    if ($is_valid_content) {
      $user_id = TokenController::GetUserId($user_token);
      $vote_up = VoteModel::where('user_id', '=', $user_id)
        ->where('votable_id', '=', $votable_id)
        ->where('votable_type', '=', $votable_type)
        ->where('type', '=', 'up')
        ->first();
      $vote_down = VoteModel::where('user_id', '=', $user_id)
        ->where('votable_id', '=', $votable_id)
        ->where('votable_type', '=', $votable_type)
        ->where('type', '=', 'down')
        ->first();
      $up_count = VoteModel::where('votable_id', '=', $votable_id)
        ->where('votable_type', '=', $votable_type)
        ->where('type', '=', 'up')
        ->count();
      $down_count = VoteModel::where('votable_id', $votable_id)
        ->where('votable_type', '=', $votable_type)
        ->where('type', '=', 'down')
        ->count();
      $up_value = $vote_up != null && $vote_up->type == 'up';
      $down_value = $vote_down != null && $vote_down->type == 'down';
    }else{
      $up_count = VoteModel::where('votable_id', '=', $votable_id)
        ->where('votable_type', '=', $votable_type)
        ->where('type', '=', 'up')
        ->count();
      $down_count = VoteModel::where('votable_id', $votable_id)
        ->where('votable_type', '=', $votable_type)
        ->where('type', '=', 'down')
        ->count();
    }
    return [
      'is_get' => $vote != null,
      'vote' => [
        'votable_id' => $votable_id,
        'votable_type' => $votable_type,
        'up' => [
          'count' => $up_count,
          'value' => $up_value
        ],
        'down' => [
          'count' => $down_count,
          'value' => $down_value
        ],
      ]
    ];
  }
}
