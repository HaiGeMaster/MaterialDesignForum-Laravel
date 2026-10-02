<?php

namespace App\Http\Controllers;

use App\Models\SignLog;
use App\Models\UserSignSummary;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SignInController extends Controller
{
    /**
     * 获取当前用户签到状态与当月日历。
     */
    public function status(Request $request)
    {
        $user = $request->user();

        $summary = UserSignSummary::query()->firstOrCreate(
            ['user_id' => $user->user_id],
            [
                'total_sign_days' => 0,
                'continuous_sign_days' => 0,
                'last_sign_date' => null,
                'last_reward_points' => 0,
                'create_time' => now(),
                'update_time' => now(),
            ]
        );

        $today = now()->toDateString();
        $signedToday = SignLog::query()
            ->where('user_id', $user->user_id)
            ->whereDate('sign_date', $today)
            ->exists();

        $monthStart = now()->copy()->startOfMonth()->toDateString();
        $monthEnd = now()->copy()->endOfMonth()->toDateString();
        $monthDates = SignLog::query()
            ->where('user_id', $user->user_id)
            ->whereBetween('sign_date', [$monthStart, $monthEnd])
            ->pluck('sign_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->values()
            ->all();

        $totalRewardPoints = (int) SignLog::query()
            ->where('user_id', $user->user_id)
            ->sum('reward_points');

        return response()->json([
            'signed_today' => $signedToday,
            'total_sign_days' => (int) $summary->total_sign_days,
            'continuous_sign_days' => (int) $summary->continuous_sign_days,
            'last_sign_date' => $summary->last_sign_date ? Carbon::parse($summary->last_sign_date)->toDateString() : null,
            'last_reward_points' => (int) $summary->last_reward_points,
            'total_reward_points' => $totalRewardPoints,
            'month_dates' => $monthDates,
        ]);
    }

    /**
     * 签到：同一账号 24 小时内只能签到一次，严格按自然日计算连续奖励。
     */
    public function checkIn(Request $request)
    {
        $user = $request->user();
        $today = now()->toDateString();

        if (SignLog::query()->where('user_id', $user->user_id)->whereDate('sign_date', $today)->exists()) {
            return response()->json([
                'message' => __('messages.http_signin_1'),
            ], 422);
        }

        $summary = UserSignSummary::query()->firstOrCreate(
            ['user_id' => $user->user_id],
            [
                'total_sign_days' => 0,
                'continuous_sign_days' => 0,
                'last_sign_date' => null,
                'last_reward_points' => 0,
                'create_time' => now(),
                'update_time' => now(),
            ]
        );

        $newContinuousDays = 1;
        if ($summary->last_sign_date) {
            $lastDate = Carbon::parse($summary->last_sign_date);
            $diffDays = $lastDate->diffInDays(now(), false);

            if ($diffDays === 1) {
                $newContinuousDays = (int) $summary->continuous_sign_days + 1;
            } elseif ($diffDays > 1) {
                $newContinuousDays = 1;
            }
        }

        $rewardPoints = $this->calculateReward($newContinuousDays);

        return DB::transaction(function () use ($user, $today, $summary, $newContinuousDays, $rewardPoints, $request) {
            $log = SignLog::query()->create([
                'user_id' => $user->user_id,
                'sign_date' => $today,
                'reward_points' => $rewardPoints,
                'ip_address' => $request->ip() ?: '127.0.0.1',
                'create_time' => now(),
            ]);

            $summary->total_sign_days = (int) $summary->total_sign_days + 1;
            $summary->continuous_sign_days = $newContinuousDays;
            $summary->last_sign_date = $today;
            $summary->last_reward_points = $rewardPoints;
            $summary->update_time = now();
            $summary->save();

            $totalRewardPoints = (int) SignLog::query()->where('user_id', $user->user_id)->sum('reward_points');

            return response()->json([
                'message' => __('messages.http_signin_2'),
                'signed_today' => true,
                'reward_points' => $rewardPoints,
                'total_reward_points' => $totalRewardPoints,
                'total_sign_days' => (int) $summary->total_sign_days,
                'continuous_sign_days' => $newContinuousDays,
                'sign_log_id' => $log->sign_log_id,
            ]);
        });
    }

    protected function calculateReward(int $continuousDays): int
    {
        if ($continuousDays >= 30) {
            return 200;
        }

        if ($continuousDays >= 7) {
            return 80;
        }

        if ($continuousDays >= 3) {
            return 25;
        }

        return 10;
    }
}
