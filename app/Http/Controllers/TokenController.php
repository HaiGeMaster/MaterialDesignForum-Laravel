<?php

/**
 * Author HaiGeMaster
 * @package MaterialDesignForum
 * @link https://github.com/HaiGeMaster
 * @copyright Copyright (c) 2023 HaiGeMaster
 * @start-date 2023/05/20-15:53:29
 */

namespace App\Http\Controllers;

use App\Models\Token as TokenModel;
use App\Models\User as UserModel;
use App\Http\Controllers\UserGroupController;
use App\Services\Share;
use Carbon\Carbon;

class TokenController extends Controller
{
  /**
   * 生成用户Token 仅允许用户在密码验证成功后调用
   * @param UserModel $user 用户模型
   * @return string token字符串
   */
  public static function SpawnUserToken($user): string
  {
    $device = $_SERVER['HTTP_USER_AGENT'];
    $token_text = md5($user->email . $user->password . $device);

    // 主键是 token，无法通过 update() 安全修改，先删后建
    $oldToken = TokenModel::where('user_id', '=', $user->user_id)->first();
    if ($oldToken) {
      $oldToken->delete();
    }

    //避免一样的token
    $old_token_text = TokenModel::where('token', '=', $token_text)->first();
    if ($old_token_text) {
      $old_token_text->delete();
    }

    $token = new TokenModel();
    $token->token = $token_text;
    $token->user_id = $user->user_id;
    $token->device = $device;
    $token->create_time = Share::ServerTime();
    $token->update_time = Share::ServerTime();
    $token->expire_time = Carbon::now()->addSeconds(86400 * 30);
    $token->save();

    return $token_text;



    // $device = $_SERVER['HTTP_USER_AGENT'];
    // $token_text = md5($user->email . $user->password . $device);

    // $token = TokenModel::where('user_id', '=', $user->user_id)
    //   ->first();

    // $return_token = '';
    // if ($token != null) {
    //   $update_token = TokenModel::where('user_id', '=', $user->user_id)
    //     ->update([
    //       'token' => $token_text,
    //       'device' => $device,
    //       'update_time' => Share::ServerTime(),
    //       'expire_time' => Share::ServerTime() + 86400 * 30
    //     ]);

    //   if ($update_token) {
    //     $return_token = $token_text;
    //   }
    // } else {
    //   $token = new TokenModel();
    //   $token->token = $token_text;
    //   $token->user_id = $user->user_id;
    //   $token->device = $device;
    //   $token->create_time = Share::ServerTime();
    //   $token->update_time = Share::ServerTime();
    //   $token->expire_time = Share::ServerTime() + 86400 * 30;
    //   $token->save();

    //   $return_token = $token->token;
    // }

    // return $return_token;
  }
  
  //  * 【※警告：基于 UserGroup 的类不可以使用，会死循环。】
  /**
   * 通过token获取用户ID 经过验证
   * @param string $token token字符串
   * @return int|null 用户ID 从Token表中获取user_id 之后可做其它比较
   */
  public static function GetUserId($token)
  {
    if ($token == null) return null;
    if ($token == '') return null;
    $query_token = TokenModel::where('token', '=', $token)
      ->where('expire_time', '>', Share::ServerTime())
      ->first();

    // return [
    //   '$query_token->token'=>$query_token->token,
    // ];
    if ($query_token != null) {
      $user_id = $query_token->user_id;
      $user = UserModel::where('user_id', '=', $user_id)->first();
      $device = $_SERVER['HTTP_USER_AGENT'];

      $query_token_token = $query_token->token;
      $query_token_device = $query_token->device;
      if ($user != null) {
        $user_token = md5($user->email . $user->password . $device);
        if (
          $user_token == $token && //用户token与传入token相同
          $query_token_device == $device && //查询到的token的设备与传入的设备相同
          $query_token_token == $user_token //查询到的token与用户token相同
        ) {
          if ($user->disable_time > Share::ServerTime()) { //如果禁用时间大于当前时间，说明未解除禁用
            // return -6;
            return null;
          }
          if (UserGroupController::Ability($token, 'ability_normal_login') == false) { //如果用户组不允许前台登录
            // return -5;
            return null;
          }
          // return -4;
          return $user->user_id;
        } else {
          return [
            // 'user' => $user,
            'token' => $token,
            'device' => $query_token->device == $device,
            'user_token' => $user_token,
            'query_token' => $query_token,
            'query_token_token' => $query_token->token,
            '$query_token->token == $user_token' => $query_token_token == $user_token,

          ];
          return null;
        }
      } else {
        // return -2;
        return null;
      }
    }
    // return -1;
    return null;
  }
  /**
   * 通过token获取用户信息 经过验证
   * @param string $token token字符串
   * @return UserModel|null 用户信息
   */
  public static function GetUser($token)
  {
    $user_id = self::GetUserId($token);
    if ($user_id) {
      $user = UserModel::where('user_id', '=', $user_id)->first();
      if ($user) {
        return $user;
      } else {
        return null;
      }
    } else {
      return null;
    }
  }
  /**
   * 验证token是否是用户自己的 仅适用于用户
   * @param string $token token字符串
   * @param int $target_user_id 要与其对比验证的用户ID
   * @return bool $token->user_id == $target_user_id
   */
  public static function IsUserSelf($token, $target_user_id = null): bool
  {
    $token_user_id = self::GetUserId($token);
    if ($token_user_id == $target_user_id) {
      return true;
    } else {
      return false;
    }
  }
}
