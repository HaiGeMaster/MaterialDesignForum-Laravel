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
  /**
   * 获取应用基本信息
   * @param string $user_token 用户令牌
   * @return array 返回应用基本信息
   */
  public static function GetAppBaseInfo($user_token = '')
  {
    $langList = self::buildLangList();

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
        'theme_list' => self::GetThemeList(),
      ]
    ];
  }
  /**
   * 设置应用基本信息
   * @param string $user_token 用户令牌
   * @param array  $data       配置项键值对 [option_list, theme_color, lang_locale_list]
   * @return array
   */
  public static function SetAppBaseInfo($user_token = '', $data = [])
  {
    if (!UserGroupController::IsAdmin($user_token)) {
      return [
        'is_set' => false,
        'data'   => null,
      ];
    }

    // 1. 保存 option_list 到数据库
    if (!empty($data['option_list']) && is_array($data['option_list'])) {
      foreach ($data['option_list'] as $name => $value) {
        OptionModel::Set($name, $value);
      }
    }

    // 2. 写入 theme_color 到主题的 setting.json
    if (isset($data['theme_color']) && is_array($data['theme_color'])) {
      $theme = $data['option_list']['theme']
            ?? OptionModel::Get('theme')
            ?? 'MaterialDesignForum-Vuetify4';

      $settingFile = public_path("themes/{$theme}/setting.json");
      if (file_exists($settingFile)) {
        $themeSetting = json_decode(file_get_contents($settingFile), true) ?: [];
        $themeSetting['theme_color'] = $data['theme_color'];
        file_put_contents($settingFile, json_encode($themeSetting, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
      }
    }

    // // 3. 更新语言文件的 langInfo。。暂时不需要更新。
    // if (!empty($data['lang_locale_list']) && is_array($data['lang_locale_list'])) {
    //   foreach ($data['lang_locale_list'] as $locale => $item) {
    //     $langFile = lang_path("{$locale}/Message.php");
    //     if (!file_exists($langFile)) {
    //       continue;
    //     }
    //     $messages = require $langFile;
    //     if (isset($item['Message']['langInfo'])) {
    //       $messages['langInfo'] = $item['Message']['langInfo'];
    //       $export = var_export($messages, true);
    //       $content = "<-?php\n\nreturn {$export};\n";
    //       file_put_contents($langFile, $content);
    //     }
    //   }
    // }

    // 4. 清除配置缓存
    if (function_exists('artisan')) {
      \Illuminate\Support\Facades\Artisan::call('config:clear');
    }

    // 5. 返回更新后的完整数据（与 GetAppBaseInfo 返回结构一致）
    return [
      'is_set' => true,
      'data'   => [
        'option_list'      => OptionController::GetAll(),
        'theme_color'      => $data['theme_color'] ?? null,
        'lang_locale_list' => self::buildLangList(),
        'theme_list' => self::GetThemeList(),
      ],
    ];
  }

  /**
   * 获取主题列表
   * 扫描 public/themes/ 目录，读取每个主题的 theme.json
   * @return array
   */
  private static function GetThemeList()
  {
    $themeList = [];
    $themeDir  = public_path('themes');
    $dirs      = glob($themeDir . '/*', GLOB_ONLYDIR);

    foreach ($dirs as $dir) {
      $themeJson = $dir . '/theme.json';
      if (!file_exists($themeJson)) {
        continue;
      }

      $info = json_decode(file_get_contents($themeJson), true);
      if (!$info) {
        continue;
      }

      $themeName = basename($dir);

      // 读取 setting.json 中的 theme_color
      $setting    = [];
      $settingFile = $dir . '/setting.json';
      if (file_exists($settingFile)) {
        $settingData = json_decode(file_get_contents($settingFile), true);
        // $setting     = $settingData['theme_color'] ?? [];
        $setting     = $settingData ?? [];
      }

      $themeList[] = [
        'name'        => $info['name'] ?? $themeName,
        'version'     => $info['version'] ?? '',
        'description' => $info['description'] ?? '',
        'disabled'    => $info['disabled'] ?? false,
        'setting'     => $setting,
        'path'        => './public/themes/' . $themeName,
      ];
    }

    return $themeList;
  }

  /**
   * 构建语言列表（供 GetAppBaseInfo 和 SetAppBaseInfo 复用）
   * @return array
   */
  private static function buildLangList()
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
            'Message' => [
              'langInfo' => $messages['langInfo'],
            ],
          ];
        }
      }
    }

    return $langList;
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