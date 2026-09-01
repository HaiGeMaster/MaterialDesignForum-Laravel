<?php

namespace App\Http\Controllers;

use App\Mail\EmailVerificationCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmailVerificationController extends Controller
{
    /**
     * 注册验证码缓存前缀（存于 cache 表）
     */
    private const CACHE_KEY_PREFIX = 'email_verify_code:';

    /**
     * 找回密码验证码缓存前缀（存于 cache 表）
     */
    private const RESET_KEY_PREFIX = 'password_reset_code:';

    /**
     * 验证码有效时长（分钟）
     */
    private const CODE_TTL_MINUTES = 5;

    /**
     * 同邮箱重复发送的冷却时长（秒）
     */
    private const SEND_COOLDOWN_SECONDS = 60;

    /**
     * 发送邮箱注册验证码
     *
     * 验证码存放在现有的 cache 表中（name 为 key，value 存验证码，
     * life_time 为过期时间），不新增任何表或字段。
     */
    public function send(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:320'],
        ]);

        $email = Str::lower($data['email']);

        // 邮箱已被注册则无需发送
        if (User::where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['该邮箱已被注册。'],
            ]);
        }

        $this->sendCode($email, self::CACHE_KEY_PREFIX . $email, 'register');

        return response()->json([
            'message' => __('messages.http_emailverification_1'),
        ]);
    }

    /**
     * 发送邮箱找回密码验证码
     *
     * 仅对已注册邮箱发送，同样复用现有 cache 表存储。
     */
    public function sendReset(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:320'],
        ]);

        $email = Str::lower($data['email']);

        // 邮箱未注册则无法找回
        if (! User::where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['该邮箱尚未注册。'],
            ]);
        }

        $this->sendCode($email, self::RESET_KEY_PREFIX . $email, 'reset');

        return response()->json([
            'message' => __('messages.http_emailverification_1'),
        ]);
    }

    /**
     * 校验邮箱验证码（供注册流程调用）
     *
     * 校验通过后会删除该验证码，确保一次性使用。
     */
    public static function verify(string $email, string $code): void
    {
        self::verifyCode($email, $code, self::CACHE_KEY_PREFIX, __('messages.http_emailverification_2'));
    }

    /**
     * 校验邮箱验证码（供找回密码流程调用）
     *
     * 校验通过后会删除该验证码，确保一次性使用。
     */
    public static function verifyReset(string $email, string $code): void
    {
        self::verifyCode($email, $code, self::RESET_KEY_PREFIX, __('messages.http_emailverification_3'));
    }

    /**
     * 生成验证码写入 cache 表并发送邮件（含冷却限制）
     */
    private function sendCode(string $email, string $key, string $purpose): void
    {
        // 冷却限制：同一场景同一邮箱 60 秒内不能重复发送
        // 开放模式（local 环境）下不限制，便于开发测试
        if (! app()->environment('local')) {
            $row = DB::table('cache')->where('name', $key)->first();
            if ($row && $row->create_time) {
                $createdAt = $row->create_time instanceof Carbon
                    ? $row->create_time
                    : Carbon::parse($row->create_time);

                if (now()->diffInSeconds($createdAt) < self::SEND_COOLDOWN_SECONDS) {
                    $remain = self::SEND_COOLDOWN_SECONDS - (int) now()->diffInSeconds($createdAt);
                    throw ValidationException::withMessages([
                        'email' => ["发送过于频繁，请 {$remain} 秒后再试。"],
                    ]);
                }
            }
        }

        $code = (string) random_int(100000, 999999);

        DB::table('cache')->updateOrInsert(
            ['name' => $key],
            [
                'value' => $code,
                'create_time' => now(),
                'life_time' => now()->addMinutes(self::CODE_TTL_MINUTES),
            ]
        );

        Mail::to($email)->send(
            new EmailVerificationCode($code, config('app.name', __('messages.http_emailverification_4')), $purpose)
        );
    }

    /**
     * 通用验证码校验逻辑
     */
    private static function verifyCode(string $email, string $code, string $prefix, string $label): void
    {
        $key = $prefix . Str::lower($email);
        $row = DB::table('cache')->where('name', $key)->first();

        if (! $row || ! $row->life_time) {
            throw ValidationException::withMessages([
                'email_code' => ["{$label}不存在，请先获取验证码。"],
            ]);
        }

        $expireAt = $row->life_time instanceof Carbon
            ? $row->life_time
            : Carbon::parse($row->life_time);

        if ($expireAt->isPast()) {
            DB::table('cache')->where('name', $key)->delete();
            throw ValidationException::withMessages([
                'email_code' => ['验证码已过期，请重新获取。'],
            ]);
        }

        if (! hash_equals((string) $row->value, (string) $code)) {
            throw ValidationException::withMessages([
                'email_code' => ['验证码不正确。'],
            ]);
        }

        // 一次性使用，校验通过即删除
        DB::table('cache')->where('name', $key)->delete();
    }
}
