<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SignInSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userId = 1;
        $rows = [];
        $today = Carbon::today();

        for ($i = 99; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $rows[] = [
                'user_id' => $userId,
                'sign_date' => $date->toDateString(),
                'reward_points' => 10,
                'ip_address' => '127.0.0.1',
                'create_time' => $date->copy()->setTime(9, 0, 0)->toDateTimeString(),
            ];
        }

        DB::table('sign_log')->upsert(
            $rows,
            ['user_id', 'sign_date'],
            ['reward_points', 'ip_address', 'create_time']
        );

        DB::table('user_sign_summary')->updateOrInsert(
            ['user_id' => $userId],
            [
                'total_sign_days' => 100,
                'continuous_sign_days' => 100,
                'last_sign_date' => $today->toDateString(),
                'last_reward_points' => 10,
                'create_time' => now(),
                'update_time' => now(),
            ]
        );
    }
}
