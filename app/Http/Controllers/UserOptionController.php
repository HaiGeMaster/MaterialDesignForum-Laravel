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
// use App\Http\Controllers\UserOptionController;
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

class UserOptionController extends Controller
{
  /**
   * 设置用户自定义设置
   * @param string $user_token 令牌
   * @param string $name 自定义设置名称
   * @param string|array $value 自定义设置值  string|array
   * @return array [is_set=>bool, data=>mixed, error=>string|null]
   */
  public static function SetUserOption(string $user_token, string $name, array $value): array
  {
    if (is_array($value)) {
      $value = json_encode($value);
    }
    try {
      $user_id = TokenController::GetUserId($user_token);
      if ($user_id === null) {
        return [
          'is_set' => false,
          'data' => null,
        ];
      }

      // 查找是否已存在该设置
      $data = UserOptionModel::where('user_id', $user_id)->where('name', $name)->first();

      if ($data) {
        // 更新数据
        $data->value = $value;
        $data->save();

        $data->value = json_decode($data->value, true);

        return [
          'is_set' => true,
          'data' => $data,
        ];
      } else {
        // 创建新数据
        $data = UserOptionModel::create([
          'user_id' => $user_id,
          'name' => $name,
          'value' => $value,
        ]);
        $data->value = json_decode($data->value, true);

        return [
          'is_set' => true,
          'data' => $data,
        ];
      }
    } catch (\Exception $e) {
      // 捕获所有可能的异常，如数据库错误等
      return [
        'is_set' => false,
        'data' => null,
        'error' => 'SetUserOption failed: ' . $e->getMessage(),
      ];
    }
  }
  /**
   * 获取用户自定义设置
   * @param string $user_token 令牌
   * @param string $name 自定义设置名称
   * @return array [is_set=>bool, data=>mixed, error=>string|null]
   */
  public static function GetUserOption(string $user_token, string $name): array
  {
    try {
      $user_id = TokenController::GetUserId($user_token);
      if ($user_id === null) {
        return [
          'is_get' => false,
          'data' => null,
        ];
      }

      $data = UserOptionModel::where('user_id', $user_id)->where('name', $name)->first();

      if ($data) {
        $data->value = json_decode($data->value, true);
        return [
          'is_get' => true,
          'data' => $data,
        ];
      } else {
        return [
          'is_get' => false,
          'data' => null,
        ];
      }
    } catch (\Exception $e) {
      return [
        'is_get' => false,
        'data' => null,
        'error' => 'GetUserOption failed: ' . $e->getMessage(),
      ];
    }
  }
  /**
   * 删除用户自定义设置
   * @param string $user_token 令牌
   * @param string $name 自定义设置名称
   * @return array [is_set=>bool, data=>mixed, error=>string|null]
   */
  public static function DeleteUserOption(string $user_token, string $name): array
  {
    try {
      $user_id = TokenController::GetUserId($user_token);
      if ($user_id === null) {
        return [
          'is_delete' => false,
          'data' => null,
        ];
      }
      $data = UserOptionModel::where('user_id', $user_id)->where('name', $name)->first();

      if ($data) {
        $data->delete();
        return [
          'is_delete' => true,
          'data' => $data, // 可能返回已删除的模型数据，视需求可以置为 null
        ];
      } else {
        return [
          'is_delete' => false,
          'data' => null,
        ];
      }
    } catch (\Exception $e) {
      return [
        'is_delete' => false,
        'data' => null,
        'error' => 'DeleteUserOption failed: ' . $e->getMessage(),
      ];
    }
  }
}
