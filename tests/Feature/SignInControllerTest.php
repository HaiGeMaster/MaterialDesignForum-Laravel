<?php

namespace Tests\Feature;

use App\Http\Controllers\SignInController;
use App\Models\SignLog;
use App\Models\User;
use App\Models\UserSignSummary;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SignInControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('sign_log');
        Schema::dropIfExists('user_sign_summary');
        Schema::dropIfExists('user');

        Schema::create('user', function (Blueprint $table) {
            $table->increments('user_id');
            $table->unsignedInteger('user_group_id')->default(2);
            $table->string('username');
            $table->string('email');
            $table->string('password');
            $table->timestamp('create_time')->nullable();
            $table->timestamp('update_time')->nullable();
            $table->timestamp('last_login_time')->nullable();
            $table->timestamp('disable_time')->nullable();
        });

        Schema::create('user_sign_summary', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->primary();
            $table->unsignedInteger('total_sign_days')->default(0);
            $table->unsignedInteger('continuous_sign_days')->default(0);
            $table->date('last_sign_date')->nullable();
            $table->unsignedInteger('last_reward_points')->default(0);
            $table->timestamp('create_time')->nullable();
            $table->timestamp('update_time')->nullable();
        });

        Schema::create('sign_log', function (Blueprint $table) {
            $table->increments('sign_log_id');
            $table->unsignedInteger('user_id');
            $table->date('sign_date');
            $table->unsignedInteger('reward_points')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('create_time')->nullable();
            $table->unique(['user_id', 'sign_date']);
        });
    }

    public function test_user_can_check_in_once_per_day_and_summary_updates(): void
    {
        $user = User::query()->create([
            'username' => 'signuser',
            'email' => 'signuser@example.com',
            'password' => bcrypt('password123'),
            'create_time' => now(),
            'update_time' => now(),
            'last_login_time' => now(),
        ]);

        $request = Request::create('/api/sign-in/check-in', 'POST', []);
        $request->setUserResolver(fn () => $user);

        $first = (new SignInController())->checkIn($request);
        $this->assertSame(200, $first->getStatusCode());
        $this->assertSame(1, SignLog::query()->count());
        $this->assertSame(10, UserSignSummary::query()->first()->last_reward_points);
        $this->assertSame(1, UserSignSummary::query()->first()->continuous_sign_days);

        $duplicate = (new SignInController())->checkIn($request);
        $this->assertSame(422, $duplicate->getStatusCode());
    }
}
