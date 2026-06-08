<?php

namespace App\Http\Controllers;

use App\Models\Oauth;
use Illuminate\Http\Request;

class OauthController extends Controller
{
    /**
     * 添加或更新第三方 OAuth 用户信息
     */
    public static function AddOauthUser($oauthName, $oauthUserId, $oauthUserName, $oauthUserEmail, $oauthSourceResponse, $userId)
    {
        if (!$userId) return null;

        $existing = Oauth::where('oauth_name', $oauthName)
            ->where('oauth_user_id', $oauthUserId)
            ->first();

        if ($existing) {
            $existing->oauth_user_name = $oauthUserName;
            $existing->oauth_user_email = $oauthUserEmail;
            $existing->oauth_source_response = $oauthSourceResponse;
            $existing->save();
            return $existing;
        }

        return Oauth::create([
            'oauth_name'             => $oauthName,
            'oauth_user_id'          => $oauthUserId,
            'oauth_user_name'        => $oauthUserName,
            'oauth_user_email'       => $oauthUserEmail,
            'oauth_source_response'  => $oauthSourceResponse,
            'user_id'                => $userId,
        ]);
    }

    /**
     * 根据平台名和用户 ID 获取 OAuth 记录
     */
    public static function GetOauthUser($oauthName, $oauthUserId)
    {
        return Oauth::where('oauth_name', $oauthName)
            ->where('oauth_user_id', $oauthUserId)
            ->first();
    }

    /**
     * 获取用户所有 OAuth 绑定记录
     * POST /api/oauths/get
     */
    public function GetOauths(Request $request): \Illuminate\Http\JsonResponse
    {
        $user_token = $request->input('user_token', $request->bearerToken());
        $userId = TokenController::GetUserId($user_token);

        if (!$userId) {
            return response()->json([
                'is_get' => false,
                'data' => null,
                'pagination' => null,
            ]);
        }

        $github = Oauth::where('user_id', $userId)->where('oauth_name', 'github')->first();
        $microsoft = Oauth::where('user_id', $userId)->where('oauth_name', 'microsoft')->first();
        $google = Oauth::where('user_id', $userId)->where('oauth_name', 'google')->first();
        $sso = Oauth::where('user_id', $userId)->where('oauth_name', 'sso')->first();

        return response()->json([
            'is_get' => true,
            'data' => [
                'github'             => $github,
                'microsoft'          => $microsoft,
                'google'             => $google,
                'sso'                => $sso,
                'sso_client_main_name' => env('OAUTH2_SSO_CLIENT_MAIN_NAME', ''),
            ],
            'pagination' => null,
        ]);
    }

    /**
     * 删除 OAuth 绑定
     * POST /api/oauth/delete
     */
    public function DeleteOauth(Request $request): \Illuminate\Http\JsonResponse
    {
        $user_token = $request->input('user_token', $request->bearerToken());
        $oauth_id = $request->input('oauth_id');

        $is_delete = false;
        $userId = TokenController::GetUserId($user_token);

        if ($userId) {
            $oauth = Oauth::where('user_id', $userId)
                ->where('oauth_id', $oauth_id)
                ->first();

            if ($oauth) {
                $is_delete = $oauth->delete();
            }
        }

        return response()->json([
            'is_get' => $is_delete,
            'data' => null,
            'pagination' => null,
        ]);
    }

    /**
     * OAuth 回调处理
     * GET /api/oauth/redirect/{oauth_name}
     */
    public function OauthRedirect(Request $request, $oauth_name): \Illuminate\Http\JsonResponse
    {
        $code = $request->input('code');

        if (!$code) {
            return response()->json([
                'is_login' => false,
                'error' => 'Missing authorization code',
            ]);
        }

        // TODO: 实现 OAuth 流程 \MaterialDesignForum\Plugins\Oauth::ExecuteOAuthFlow()
        // 根据 oauth_name 执行第三方登录回调，获取用户信息，然后调用 UserController::OauthLoginOrRegister
        return response()->json([
            'is_login' => false,
            'message' => 'OAuth redirect handler not yet implemented',
        ]);
    }
}
