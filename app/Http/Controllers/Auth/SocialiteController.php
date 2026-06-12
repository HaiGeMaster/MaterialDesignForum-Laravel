<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    // 重定向到 GitHub 授权页
    public function redirectToGithub()
    {
        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver('github');
        return $driver->setScopes(['read:user', 'user:email'])->redirect();
    }

    // GitHub 回调处理
    public function handleGithubCallback(Request $request)
    {
        $githubUser = Socialite::driver('github')->user();

        $cookie_user_token = UserController::OauthLoginOrRegister(
            'github',
            $githubUser->getId(),
            $githubUser->getName() ?? $githubUser->getNickname(),
            $githubUser->getEmail(),
            $githubUser,
            $request->cookie('user_token') ?? $request->input('user_token', $request->bearerToken()),
        )['token'];

        return redirect('/')
            ->withCookie(cookie('user_token', $cookie_user_token, 60 * 24 * 30, null, null, false, false)); // httponly, 30天过期
    }

    // 重定向到 Microsoft 授权页
    public function redirectToMicrosoft()
    {
        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver('microsoft');
        return $driver->scopes(['User.Read', 'offline_access']) // 如需refresh_token加offline_access
            ->redirect();
    }

    // Microsoft 回调处理
    public function handleMicrosoftCallback(Request $request)
    {
        $msUser = Socialite::driver('microsoft')->user();

        $cookie_user_token = UserController::OauthLoginOrRegister(
            'microsoft',
            $msUser->getId(),
            $msUser->getName(),
            $msUser->getEmail(),
            $msUser,
            $request->cookie('user_token') ?? $request->input('user_token', $request->bearerToken()),
        )['token'];

        return redirect('/')
            ->withCookie(cookie('user_token', $cookie_user_token, 60 * 24 * 30, null, null, false, false)); // httponly, 30天过期
    }
}
