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

class CommonController extends Controller
{

  public static function GetAppBaseInfo($user_token = '')
  {
    $langList = [];
    $langDir = lang_path();
    $dirs = glob($langDir . '/*', GLOB_ONLYDIR);

    foreach ($dirs as $dir) {
      $locale = basename($dir);
      $file   = $dir . '/Message.php';

      if (file_exists($file)) {
        $messages = require $file;
        if (isset($messages['langInfo'])) {
          $langList[$locale] = [
            'Message'=>[
              'langInfo'=>$messages['langInfo'],
            ]
          ];
        }
      }
    }

    $options = OptionController::GetAll();

    $theme = $options['theme'] ?? 'MaterialDesignForum-Vuetify4';
    // $theme = 'MaterialDesignForum-Vuetify4';
    $settingFile = public_path("themes/{$theme}/setting.json");
    $themeColor = [];
    if (file_exists($settingFile)) {
      $themeColor = json_decode(file_get_contents($settingFile), true) ?: [];
    }

    return [
      'is_get' => true,
      'data' => [
        'lang_locale_list' => $langList,
        'option_list' => $options,
        'theme_color' => $themeColor['theme_color'],
      ]
    ];
  }

  /**
   * 获取指定的语言数据
   * @param string $locale 语言代码
   * @return array 返回语言 ['Message'=>array]
   */
  public static function GetLanguage($locale)
  {
    $file = lang_path($locale . '/Message.php');
    if (!file_exists($file)) {
      return null;
    }
    return ['Message' => require $file];
  }
}